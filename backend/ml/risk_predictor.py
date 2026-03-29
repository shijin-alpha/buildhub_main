"""
Risk Predictor Module — Hybrid Construction Risk Evaluation System

Combines ML model predictions with rule-based domain knowledge to produce
stable, explainable risk assessments. ML alone is unreliable when confidence
is low or inputs fall outside the training distribution.

Hybrid strategy:
  1. Compute derived metrics (budget/sqft, expected duration, complexity score).
  2. Evaluate three independent rule-based risk dimensions:
       - Cost Risk   : budget/sqft vs realistic construction benchmarks
       - Time Risk   : planned timeline vs expected duration for the project scope
       - Complexity  : floors, design features, site constraints
  3. Run ML models and extract class probabilities.
  4. If ML confidence is weak (max probability < CONFIDENCE_THRESHOLD), down-weight
     the ML contribution and rely more on rule-based scores.
  5. Blend all four scores into a single 0-100 risk score.
  6. Map score → Low / Medium / High.
  7. Apply safety overrides for extreme / impossible inputs.
"""

import joblib
import numpy as np
import json
import os
from typing import Dict, Tuple, Optional

try:
    import shap
    SHAP_AVAILABLE = True
except ImportError:
    SHAP_AVAILABLE = False

# ── Tuning constants ──────────────────────────────────────────────────────────
# Kerala construction benchmark (₹/sqft)
COST_BENCHMARK_CRITICAL = 500    # below this → critically underfunded
COST_BENCHMARK_LOW      = 800    # below this → elevated risk
COST_BENCHMARK_STANDARD = 1500   # realistic minimum for standard build
COST_BENCHMARK_GOOD     = 2500   # comfortable budget

# Expected duration benchmark: months = (sqft / SQFT_PER_MONTH) + (floors * MONTHS_PER_FLOOR)
SQFT_PER_MONTH   = 400
MONTHS_PER_FLOOR = 1.5

# ── Kerala district encoding (0-13) ──────────────────────────────────────────
KERALA_DISTRICTS = [
    'Thiruvananthapuram', 'Kollam', 'Pathanamthitta', 'Alappuzha',
    'Kottayam', 'Idukki', 'Ernakulam', 'Thrissur',
    'Palakkad', 'Malappuram', 'Kozhikode', 'Wayanad',
    'Kannur', 'Kasaragod',
]
DISTRICT_NAME_TO_CODE = {d.lower(): i for i, d in enumerate(KERALA_DISTRICTS)}

# ── Environmental risk lookup tables ─────────────────────────────────────────
# District penalties are no longer hardcoded per district name.
# Instead they are computed from the actual environmental characteristics
# (terrain, rainfall, flood risk) of the sampled panchayat or district profile.
# This keeps the rule engine consistent with how the training data was generated.

# terrain_code: flat=0, midland=1, coastal=2, backwater=3, hilly=4, highland=5
_TERRAIN_COST_PENALTY = {0: 0, 1: 5, 2: 5, 3: 10, 4: 10, 5: 15}
_TERRAIN_TIME_PENALTY = {0: 0, 1: 5, 2: 5, 3: 10, 4: 10, 5: 15}

# rainfall_code: low=0, moderate=1, high=2, very_high=3
_RAINFALL_COST_PENALTY = {0: -5, 1: 0, 2: 5, 3: 10}
_RAINFALL_TIME_PENALTY = {0: -5, 1: 0, 2: 5, 3: 10}

# flood_risk_code: low=0, moderate=1, high=2
_FLOOD_COST_PENALTY = {0: 0, 1: 5, 2: 10}
_FLOOD_TIME_PENALTY = {0: 0, 1: 5, 2: 15}


def _env_cost_penalty(terrain_code: int, rainfall_code: int, flood_risk_code: int) -> float:
    """
    Compute a cost risk penalty (points on 0-100 scale) from environmental
    factors. Replaces the old DISTRICT_COST_PENALTY hardcoded lookup.
    Range: roughly -5 (Palakkad flat/dry) to +35 (Idukki highland/very_high).
    """
    return (
        _TERRAIN_COST_PENALTY.get(terrain_code, 0)
        + _RAINFALL_COST_PENALTY.get(rainfall_code, 0)
        + _FLOOD_COST_PENALTY.get(flood_risk_code, 0)
    )


def _env_time_penalty(terrain_code: int, rainfall_code: int, flood_risk_code: int) -> float:
    """
    Compute a time risk penalty (points on 0-100 scale) from environmental
    factors. Replaces the old DISTRICT_TIME_PENALTY hardcoded lookup.
    Range: roughly -5 (Palakkad flat/dry) to +40 (Idukki highland/very_high).
    """
    return (
        _TERRAIN_TIME_PENALTY.get(terrain_code, 0)
        + _RAINFALL_TIME_PENALTY.get(rainfall_code, 0)
        + _FLOOD_TIME_PENALTY.get(flood_risk_code, 0)
    )


# Representative climate profile per district — used as fallback when no
# panchayat-level data is available. Values are the weighted-average
# terrain/rainfall/flood codes from DISTRICT_CLIMATE_PROFILES in generate_dataset.py.
# Format: (terrain_code, rainfall_code, flood_risk_code)
_DISTRICT_DEFAULT_CLIMATE = {
    0:  (1, 1, 0),   # Thiruvananthapuram — midland, moderate, low
    1:  (1, 2, 1),   # Kollam — midland, high, moderate
    2:  (4, 2, 0),   # Pathanamthitta — hilly, high, low
    3:  (3, 2, 2),   # Alappuzha — backwater, high, high
    4:  (1, 2, 0),   # Kottayam — midland, high, low
    5:  (5, 3, 0),   # Idukki — highland, very_high, low
    6:  (0, 2, 1),   # Ernakulam — flat, high, moderate
    7:  (0, 2, 0),   # Thrissur — flat, high, low
    8:  (0, 0, 0),   # Palakkad — flat, low, low
    9:  (0, 2, 1),   # Malappuram — flat, high, moderate
    10: (2, 2, 1),   # Kozhikode — coastal, high, moderate
    11: (5, 3, 0),   # Wayanad — highland, very_high, low
    12: (2, 2, 1),   # Kannur — coastal, high, moderate
    13: (2, 2, 1),   # Kasaragod — coastal, high, moderate
}

# ── Kerala monsoon calendar ───────────────────────────────────────────────────
# SW monsoon: Jun(6)-Sep(9) — heavy; NE monsoon: Oct(10)-Nov(11) — moderate
_SW_MONSOON  = {6, 7, 8, 9}
_NE_MONSOON  = {10, 11}

def _monsoon_exposure(start_month: int, window_months: int = 6) -> float:
    """
    Compute a 0-1 monsoon exposure score for a project starting in start_month.
    Looks at the first `window_months` of construction.
    SW monsoon counts 1.5x, NE monsoon counts 0.8x.
    Max possible raw score ≈ 9 (6 SW months × 1.5).
    """
    raw = 0.0
    for offset in range(window_months):
        m = ((start_month - 1 + offset) % 12) + 1
        if m in _SW_MONSOON:
            raw += 1.5
        elif m in _NE_MONSOON:
            raw += 0.8
    return min(1.0, raw / 9.0)   # normalise to 0-1

# ML confidence threshold — below this the ML vote is treated as uncertain
CONFIDENCE_THRESHOLD = 0.55

# Blend weights when ML is confident vs uncertain
WEIGHTS_ML_CONFIDENT  = {'cost': 0.20, 'time': 0.20, 'complexity': 0.20, 'ml': 0.40}
WEIGHTS_ML_UNCERTAIN  = {'cost': 0.30, 'time': 0.30, 'complexity': 0.25, 'ml': 0.15}

# Score → risk level thresholds
SCORE_LOW_MAX    = 35
SCORE_MEDIUM_MAX = 65


# ── Helper: map a risk level label to a numeric score ─────────────────────────
_LEVEL_TO_SCORE = {'Low': 15, 'Medium': 50, 'High': 85}
_SCORE_TO_LEVEL = lambda s: 'Low' if s <= SCORE_LOW_MAX else ('Medium' if s <= SCORE_MEDIUM_MAX else 'High')


class ConstructionRiskPredictor:
    """
    Hybrid construction risk predictor.
    Loads trained sklearn models and blends their output with rule-based
    domain knowledge to produce stable, explainable risk assessments.
    """

    def __init__(self, models_dir=None):
        if models_dir is None:
            script_dir = os.path.dirname(os.path.abspath(__file__))
            models_dir = os.path.join(script_dir, 'models')
        self.models_dir = models_dir
        self.cost_model  = None
        self.cost_scaler = None
        self.time_model  = None
        self.time_scaler = None
        self.metadata    = None
        self.model_config = None
        self.is_loaded   = False
        self.config_file = os.path.join(self.models_dir, 'current_model.json')

    # ── Model loading ─────────────────────────────────────────────────────────

    def load_model_config(self) -> bool:
        try:
            if os.path.exists(self.config_file):
                with open(self.config_file, 'r') as f:
                    self.model_config = json.load(f)
            else:
                self.model_config = {
                    'cost_model': 'cost_overrun_risk_model.pkl',
                    'time_model': 'time_delay_risk_model.pkl',
                    'version': 'v1'
                }
            return True
        except Exception:
            return False

    def load_models(self) -> bool:
        try:
            if not self.load_model_config():
                return False

            cost_path = os.path.join(self.models_dir, self.model_config.get('cost_model', 'cost_overrun_risk_model.pkl'))
            time_path = os.path.join(self.models_dir, self.model_config.get('time_model', 'time_delay_risk_model.pkl'))

            if not os.path.exists(cost_path) or not os.path.exists(time_path):
                return False

            self.cost_model = joblib.load(cost_path)
            self.time_model = joblib.load(time_path)

            cost_scaler_path = os.path.join(self.models_dir, 'cost_overrun_scaler.pkl')
            time_scaler_path = os.path.join(self.models_dir, 'time_delay_scaler.pkl')
            if os.path.exists(cost_scaler_path):
                self.cost_scaler = joblib.load(cost_scaler_path)
            if os.path.exists(time_scaler_path):
                self.time_scaler = joblib.load(time_scaler_path)

            metadata_path = os.path.join(self.models_dir, 'model_metadata.json')
            if not os.path.exists(metadata_path):
                return False
            with open(metadata_path, 'r') as f:
                self.metadata = json.load(f)

            self.is_loaded = True
            return True
        except Exception:
            return False

    # ── Derived metrics ───────────────────────────────────────────────────────

    def _compute_derived_metrics(self, form_data: Dict) -> Dict:
        """
        Compute project-level derived metrics used by both rule engine and ML.

        Returns a flat dict of all extracted / computed values.
        """
        building_size   = float(form_data.get('building_size_sqft', 0))
        plot_size       = float(form_data.get('plot_size_sqft', 0))
        num_floors      = int(form_data.get('num_floors', 1))
        budget_amount   = float(form_data.get('budget_amount', 0))
        num_bedrooms    = int(form_data.get('num_bedrooms', 2))
        num_bathrooms   = int(form_data.get('num_bathrooms', 1))
        planned_dur     = float(form_data.get('planned_duration_months', 0))

        # Budget per square foot
        budget_per_sqft = budget_amount / building_size if building_size > 0 else 0

        # Expected construction duration (Kerala benchmark)
        expected_duration = (building_size / SQFT_PER_MONTH) + (num_floors * MONTHS_PER_FLOOR)
        expected_duration = max(3.0, expected_duration)   # floor at 3 months

        # If no planned duration provided, use expected as the baseline
        if planned_dur <= 0:
            planned_dur = expected_duration

        # Categorical encodings
        plot_shape_map   = {'rectangular': 0, 'square': 1, 'irregular': 2, 'l_shaped': 3}
        topography_map   = {'flat': 0, 'gentle_slope': 1, 'steep_slope': 2, 'hilly': 3}
        design_style_map = {'modern': 0, 'traditional': 1, 'contemporary': 2, 'colonial': 3}

        plot_shape_code   = plot_shape_map.get(form_data.get('plot_shape', 'rectangular'), 0)
        topography_code   = topography_map.get(form_data.get('topography', 'flat'), 0)
        design_style_code = design_style_map.get(form_data.get('design_style', 'modern'), 0)

        # Customization / complexity
        customization_level = min(4, sum(1 for k in ['special_features', 'custom_requirements', 'architectural_preferences']
                                         if form_data.get(k, '')))

        design_complexity_score = min(15, (
            num_floors * 2 +
            customization_level * 2 +
            (1 if form_data.get('basement', False) else 0) +
            (1 if form_data.get('terrace', False) else 0) +
            (1 if form_data.get('parking',  False) else 0)
        ))

        development_constraint_level = min(3, sum(1 for k in ['site_access_difficult', 'utility_connections_needed', 'soil_issues']
                                                   if form_data.get(k, False)))

        site_difficulty_score = min(10, (
            topography_code * 2 +
            development_constraint_level * 2 +
            (1 if form_data.get('remote_location', False) else 0)
        ))

        total_rooms = num_bedrooms + num_bathrooms + 2

        # ── Kerala district ───────────────────────────────────────────────────
        raw_district = form_data.get('kerala_district', '') or form_data.get('district', '')
        if isinstance(raw_district, int):
            district_code = max(0, min(13, raw_district))
        else:
            district_code = DISTRICT_NAME_TO_CODE.get(str(raw_district).lower().strip(), 6)  # default Ernakulam

        # ── Construction start month ──────────────────────────────────────────
        start_month = int(form_data.get('construction_start_month', 1))
        start_month = max(1, min(12, start_month))

        # Monsoon exposure score (0-1)
        monsoon_score = _monsoon_exposure(start_month)

        # Derived location+season features (explicit columns the model was trained on)
        # district_risk_tier is now derived from the district's default climate profile
        # rather than a hardcoded lookup, consistent with generate_dataset.py.
        t_code, r_code, f_code = _DISTRICT_DEFAULT_CLIMATE.get(district_code, (0, 2, 0))
        avg_env = _env_cost_penalty(t_code, r_code, f_code)
        district_risk_tier = 2 if avg_env >= 20 else (1 if avg_env >= 5 else 0)

        # ── Panchayat-level climate modifiers ─────────────────────────────────
        # If the frontend sends climate_modifiers (derived from panchayat data),
        # use them to refine the district-level penalties.
        climate_mods = form_data.get('climate_modifiers') or {}
        panchayat_cost_mod  = float(climate_mods.get('cost_mod',  0))
        panchayat_time_mod  = float(climate_mods.get('time_mod',  0))
        panchayat_flood_mod = float(climate_mods.get('flood_mod', 0))
        terrain_label       = str(climate_mods.get('terrain_label', ''))
        rainfall_label      = str(climate_mods.get('rainfall_label', ''))
        location_name       = str(form_data.get('location', ''))

        # Blend: panchayat overrides district when available (panchayat is more precise)
        # If no panchayat data, panchayat_*_mod will be 0 and district penalties stand.
        # When panchayat data IS present, use it as the primary modifier.
        has_panchayat = bool(location_name and climate_mods)

        # Effective monsoon score: scale by rainfall intensity at panchayat level
        rainfall_multiplier = {
            'very_high': 1.3,
            'high':      1.0,
            'moderate':  0.7,
            'low':       0.4,
        }.get(rainfall_label, 1.0) if has_panchayat else 1.0
        effective_monsoon_score = min(1.0, monsoon_score * rainfall_multiplier)

        # Terrain-based site difficulty boost (panchayat-level)
        terrain_difficulty = {
            'highland':  3,
            'hilly':     2,
            'midland':   1,
            'coastal':   1,
            'backwater': 2,
            'flat':      0,
        }.get(terrain_label, 0) if has_panchayat else 0

        return {
            'building_size':              building_size,
            'plot_size':                  plot_size,
            'num_floors':                 num_floors,
            'budget_amount':              budget_amount,
            'budget_per_sqft':            budget_per_sqft,
            'num_bedrooms':               num_bedrooms,
            'num_bathrooms':              num_bathrooms,
            'total_rooms':                total_rooms,
            'planned_duration':           planned_dur,
            'expected_duration':          expected_duration,
            'plot_shape_code':            plot_shape_code,
            'topography_code':            topography_code,
            'design_style_code':          design_style_code,
            'customization_level':        customization_level,
            'design_complexity_score':    design_complexity_score,
            'development_constraint_level': development_constraint_level,
            'site_difficulty_score':      min(10, site_difficulty_score + terrain_difficulty),
            'kerala_district_code':       district_code,
            'construction_start_month':   start_month,
            'monsoon_score':              effective_monsoon_score,
            'monsoon_exposure_score':     effective_monsoon_score,   # explicit dataset column name
            'district_risk_tier':         district_risk_tier,
            # Panchayat-level extras (used by rule engine and suggestions)
            'panchayat_cost_mod':         panchayat_cost_mod,
            'panchayat_time_mod':         panchayat_time_mod,
            'panchayat_flood_mod':        panchayat_flood_mod,
            'terrain_label':              terrain_label,
            'rainfall_label':             rainfall_label,
            'location_name':              location_name,
            'has_panchayat_data':         has_panchayat,
        }

    # ── Rule-based risk dimensions ────────────────────────────────────────────

    def _evaluate_cost_risk(self, m: Dict) -> Tuple[float, str]:
        """
        Evaluate cost risk from budget/sqft vs construction benchmarks,
        adjusted for Kerala district, panchayat climate, and monsoon exposure.
        Returns (score 0-100, reason string).
        """
        bps    = m['budget_per_sqft']
        floors = m['num_floors']
        dist   = m['kerala_district_code']
        monsoon = m['monsoon_score']   # 0-1 (already scaled by panchayat rainfall)

        if bps <= 0:
            return 85.0, "No budget information provided"

        if bps < COST_BENCHMARK_CRITICAL:
            return 90.0, f"Budget ₹{bps:.0f}/sqft is critically below minimum viable cost"
        if bps < COST_BENCHMARK_LOW:
            score = 75.0 + (COST_BENCHMARK_LOW - bps) / COST_BENCHMARK_LOW * 10
            return min(85.0, score), f"Budget ₹{bps:.0f}/sqft is well below recommended minimum"
        if bps < COST_BENCHMARK_STANDARD:
            ratio = (bps - COST_BENCHMARK_LOW) / (COST_BENCHMARK_STANDARD - COST_BENCHMARK_LOW)
            score = 75.0 - ratio * 35.0
            if floors >= 2:
                score = min(85.0, score + 10.0)
            return score, f"Budget ₹{bps:.0f}/sqft is below standard construction cost"
        if bps < COST_BENCHMARK_GOOD:
            ratio = (bps - COST_BENCHMARK_STANDARD) / (COST_BENCHMARK_GOOD - COST_BENCHMARK_STANDARD)
            score = 40.0 - ratio * 25.0
            return max(15.0, score), f"Budget ₹{bps:.0f}/sqft is within acceptable range"

        base_score = 15.0

        # Environmental penalty: computed from terrain, rainfall, flood risk.
        # Uses panchayat-level data when available, falls back to district default.
        if m.get('has_panchayat_data'):
            terrain_code  = {'flat': 0, 'midland': 1, 'coastal': 2, 'backwater': 3, 'hilly': 4, 'highland': 5}.get(m.get('terrain_label', ''), 0)
            rainfall_code = {'low': 0, 'moderate': 1, 'high': 2, 'very_high': 3}.get(m.get('rainfall_label', ''), 2)
            flood_code    = 2 if m.get('panchayat_flood_mod', 0) > 10 else (1 if m.get('panchayat_flood_mod', 0) > 5 else 0)
        else:
            terrain_code, rainfall_code, flood_code = _DISTRICT_DEFAULT_CLIMATE.get(dist, (0, 2, 0))

        env_penalty = _env_cost_penalty(terrain_code, rainfall_code, flood_code)
        base_score  = max(0.0, min(100.0, base_score + env_penalty))

        # Monsoon adjustment: starting during monsoon increases material wastage risk
        monsoon_penalty = monsoon * 12.0   # up to +12 points at full monsoon exposure
        base_score = min(100.0, base_score + monsoon_penalty)

        district_name = KERALA_DISTRICTS[dist] if 0 <= dist < len(KERALA_DISTRICTS) else 'Unknown'
        location_name = m.get('location_name', '')
        reason = f"Budget ₹{bps:.0f}/sqft is comfortable for the project scope"
        if location_name:
            reason += f"; {location_name} ({district_name})"
        if m.get('has_panchayat_data') and m.get('terrain_label', '') in ('highland', 'hilly', 'backwater'):
            reason += f"; {m.get('terrain_label')} terrain adds material cost pressure"
        elif env_penalty > 5:
            reason += f"; {district_name} district environmental conditions add cost pressure"
        if monsoon_penalty > 5:
            reason += "; monsoon start timing increases material wastage risk"

        return base_score, reason

    def _evaluate_time_risk(self, m: Dict) -> Tuple[float, str]:
        """
        Evaluate time risk by comparing planned vs expected duration,
        adjusted for Kerala district, panchayat climate, and monsoon exposure.
        Returns (score 0-100, reason string).
        """
        planned  = m['planned_duration']
        expected = m['expected_duration']
        dist     = m['kerala_district_code']
        monsoon  = m['monsoon_score']   # 0-1 (already scaled by panchayat rainfall)

        if expected <= 0:
            return 50.0, "Unable to compute expected duration"

        ratio = planned / expected   # < 1 means tighter than expected

        if ratio < 0.40:
            base = 90.0
            reason = f"Timeline {planned:.1f}mo is critically short (expected ≥{expected:.1f}mo)"
        elif ratio < 0.65:
            base = 72.0
            reason = f"Timeline {planned:.1f}mo is significantly shorter than expected {expected:.1f}mo"
        elif ratio < 0.85:
            base = 50.0
            reason = f"Timeline {planned:.1f}mo is somewhat tight vs expected {expected:.1f}mo"
        elif ratio <= 1.20:
            base = 20.0
            reason = f"Timeline {planned:.1f}mo aligns well with expected {expected:.1f}mo"
        else:
            base = 25.0
            reason = f"Timeline {planned:.1f}mo is generous; monitor for scope creep"

        # Environmental penalty: computed from terrain, rainfall, flood risk.
        # Uses panchayat-level data when available, falls back to district default.
        if m.get('has_panchayat_data'):
            terrain_code  = {'flat': 0, 'midland': 1, 'coastal': 2, 'backwater': 3, 'hilly': 4, 'highland': 5}.get(m.get('terrain_label', ''), 0)
            rainfall_code = {'low': 0, 'moderate': 1, 'high': 2, 'very_high': 3}.get(m.get('rainfall_label', ''), 2)
            flood_code    = 2 if m.get('panchayat_flood_mod', 0) > 10 else (1 if m.get('panchayat_flood_mod', 0) > 5 else 0)
        else:
            terrain_code, rainfall_code, flood_code = _DISTRICT_DEFAULT_CLIMATE.get(dist, (0, 2, 0))

        env_penalty = _env_time_penalty(terrain_code, rainfall_code, flood_code)
        base = max(0.0, min(100.0, base + env_penalty))

        # Monsoon penalty: heavy monsoon exposure significantly increases delay risk
        monsoon_penalty = monsoon * 20.0   # up to +20 points at full monsoon exposure
        base = min(100.0, base + monsoon_penalty)

        district_name = KERALA_DISTRICTS[dist] if 0 <= dist < len(KERALA_DISTRICTS) else 'Unknown'
        location_name = m.get('location_name', '')
        if location_name:
            reason += f" ({location_name}, {district_name})"
        elif env_penalty > 5 and not m.get('has_panchayat_data'):
            reason += f"; {district_name} district has elevated delay risk"

        terrain = m.get('terrain_label', '')
        if terrain in ('highland', 'hilly') and m.get('has_panchayat_data'):
            reason += f"; {terrain} terrain increases construction time"
        if m.get('panchayat_flood_mod', 0) > 10 and m.get('has_panchayat_data'):
            reason += "; high flood risk area — waterlogging can cause significant delays"
        if monsoon_penalty > 8:
            reason += "; construction start during monsoon season significantly increases delay risk"
        elif monsoon_penalty > 3:
            reason += "; partial monsoon exposure may cause schedule disruption"

        return base, reason

    def _evaluate_complexity_risk(self, m: Dict) -> Tuple[float, str]:
        """
        Evaluate complexity risk from floors, design features, and site constraints.
        Returns (score 0-100, reason string).
        """
        floors      = m['num_floors']
        complexity  = m['design_complexity_score']   # 0-15
        constraints = m['development_constraint_level']  # 0-3
        site_diff   = m['site_difficulty_score']     # 0-10

        # Normalise each sub-dimension to 0-100 then blend
        floor_score      = min(100, (floors - 1) * 20)          # 1F=0, 2F=20, 3F=40, 4F=60, 5F=80+
        complexity_score = (complexity / 15) * 100
        constraint_score = (constraints / 3) * 100
        site_score       = (site_diff / 10) * 100

        blended = (floor_score * 0.35 + complexity_score * 0.30 +
                   constraint_score * 0.20 + site_score * 0.15)

        reasons = []
        if floors >= 3:
            reasons.append(f"{floors}-floor structure")
        if complexity >= 8:
            reasons.append("high design complexity")
        if constraints >= 2:
            reasons.append("significant site constraints")
        if site_diff >= 6:
            reasons.append("difficult site conditions")

        reason = ("Elevated complexity: " + ", ".join(reasons)) if reasons else "Standard complexity project"
        return blended, reason

    # ── ML prediction ─────────────────────────────────────────────────────────

    def _build_feature_arrays(self, m: Dict) -> Tuple[Optional[np.ndarray], Optional[np.ndarray]]:
        """Build cost and time feature arrays from derived metrics dict."""
        if not self.is_loaded:
            return None, None

        cost_features_order = self.metadata.get('cost_features', [])
        time_features_order = self.metadata.get('time_features', [])

        # Encode panchayat climate fields for the ML model
        terrain_map   = {'flat': 0, 'midland': 1, 'coastal': 2, 'backwater': 3, 'hilly': 4, 'highland': 5}
        rainfall_map  = {'low': 0, 'moderate': 1, 'high': 2, 'very_high': 3}
        flood_map     = {'low': 0, 'moderate': 1, 'high': 2}
        terrain_code  = terrain_map.get(m.get('terrain_label', ''), 0)
        rainfall_code = rainfall_map.get(m.get('rainfall_label', ''), 2)  # default 'high'
        flood_code    = flood_map.get(
            'high' if m.get('panchayat_flood_mod', 0) > 10
            else 'moderate' if m.get('panchayat_flood_mod', 0) > 5
            else 'low', 0
        )
        eff_monsoon   = m.get('monsoon_score', m.get('monsoon_exposure_score', 0))

        cost_dict = {
            'plot_size_sqft':              m['plot_size'],
            'building_size_sqft':          m['building_size'],
            'num_floors':                  m['num_floors'],
            'budget_amount':               m['budget_amount'],
            'budget_per_sqft':             m['budget_per_sqft'],
            'plot_shape_code':             m['plot_shape_code'],
            'topography_code':             m['topography_code'],
            'num_bedrooms':                m['num_bedrooms'],
            'num_bathrooms':               m['num_bathrooms'],
            'total_rooms':                 m['total_rooms'],
            'design_style_code':           m['design_style_code'],
            'customization_level':         m['customization_level'],
            'design_complexity_score':     m['design_complexity_score'],
            'development_constraint_level': m['development_constraint_level'],
            'kerala_district_code':        m['kerala_district_code'],
            'construction_start_month':    m['construction_start_month'],
            'monsoon_exposure_score':      m['monsoon_exposure_score'],
            'district_risk_tier':          m['district_risk_tier'],
            'terrain_code':                terrain_code,
            'rainfall_code':               rainfall_code,
            'flood_risk_code':             flood_code,
            'effective_monsoon_score':     eff_monsoon,
        }

        time_dict = {
            'plot_size_sqft':           m['plot_size'],
            'building_size_sqft':       m['building_size'],
            'num_floors':               m['num_floors'],
            'planned_duration_months':  m['planned_duration'],
            'plot_shape_code':          m['plot_shape_code'],
            'topography_code':          m['topography_code'],
            'design_complexity_score':  m['design_complexity_score'],
            'customization_level':      m['customization_level'],
            'site_difficulty_score':    m['site_difficulty_score'],
            'kerala_district_code':     m['kerala_district_code'],
            'construction_start_month': m['construction_start_month'],
            'monsoon_exposure_score':   m['monsoon_exposure_score'],
            'district_risk_tier':       m['district_risk_tier'],
            'terrain_code':             terrain_code,
            'rainfall_code':            rainfall_code,
            'flood_risk_code':          flood_code,
            'effective_monsoon_score':  eff_monsoon,
        }

        cost_arr = np.array([[cost_dict.get(f, 0) for f in cost_features_order]])
        time_arr = np.array([[time_dict.get(f, 0) for f in time_features_order]])
        return cost_arr, time_arr

    def _run_ml_models(self, m: Dict) -> Dict:
        """
        Run both ML models and return probabilities + confidence flags.
        Falls back gracefully if models are unavailable.
        """
        fallback = {
            'cost_probs': {'Low': 0.33, 'Medium': 0.34, 'High': 0.33},
            'time_probs': {'Low': 0.33, 'Medium': 0.34, 'High': 0.33},
            'cost_confident': False,
            'time_confident': False,
            'ml_available': False,
        }

        if not self.is_loaded:
            return fallback

        cost_arr, time_arr = self._build_feature_arrays(m)
        if cost_arr is None:
            return fallback

        try:
            if self.cost_scaler:
                cost_proba = self.cost_model.predict_proba(self.cost_scaler.transform(cost_arr))[0]
            else:
                cost_proba = self.cost_model.predict_proba(cost_arr)[0]

            if self.time_scaler:
                time_proba = self.time_model.predict_proba(self.time_scaler.transform(time_arr))[0]
            else:
                time_proba = self.time_model.predict_proba(time_arr)[0]

            cost_probs = {'Low': float(cost_proba[0]), 'Medium': float(cost_proba[1]), 'High': float(cost_proba[2])}
            time_probs = {'Low': float(time_proba[0]), 'Medium': float(time_proba[1]), 'High': float(time_proba[2])}

            return {
                'cost_probs':      cost_probs,
                'time_probs':      time_probs,
                'cost_confident':  bool(max(cost_proba) >= CONFIDENCE_THRESHOLD),
                'time_confident':  bool(max(time_proba) >= CONFIDENCE_THRESHOLD),
                'ml_available':    True,
            }
        except Exception:
            return fallback

    # ── Score conversion helpers ──────────────────────────────────────────────

    @staticmethod
    def _probs_to_score(probs: Dict) -> float:
        """Convert class probabilities to a 0-100 risk score."""
        return probs['Low'] * 15 + probs['Medium'] * 50 + probs['High'] * 85

    @staticmethod
    def _level_to_score(level: str) -> float:
        return _LEVEL_TO_SCORE.get(level, 50.0)

    # ── Safety overrides ──────────────────────────────────────────────────────

    def _apply_safety_overrides(self, result: Dict, m: Dict) -> Dict:
        """
        Hard overrides for extreme / impossible inputs that must never produce
        a 'Low' risk output regardless of what the hybrid score says.
        """
        bps     = m['budget_per_sqft']
        floors  = m['num_floors']
        planned = m['planned_duration']
        expected = m['expected_duration']

        # ── Cost overrides ──
        if bps > 0 and bps < COST_BENCHMARK_CRITICAL and floors >= 2:
            result['cost_overrun_risk']['risk_level'] = 'High'
            result['cost_overrun_risk']['override'] = (
                'Safety override: budget critically below minimum for multi-floor construction'
            )
            result['risk_score'] = max(result['risk_score'], 80)

        elif bps > 0 and bps < COST_BENCHMARK_CRITICAL:
            result['cost_overrun_risk']['risk_level'] = 'High'
            result['cost_overrun_risk']['override'] = (
                'Safety override: budget critically insufficient for stated building size'
            )
            result['risk_score'] = max(result['risk_score'], 75)

        elif bps > 0 and bps < COST_BENCHMARK_LOW and result['cost_overrun_risk']['risk_level'] == 'Low':
            result['cost_overrun_risk']['risk_level'] = 'Medium'
            result['cost_overrun_risk']['override'] = (
                'Safety override: budget below recommended minimum — elevated cost risk'
            )
            result['risk_score'] = max(result['risk_score'], 40)

        # ── Time overrides ──
        if expected > 0 and planned > 0 and planned < expected * 0.40:
            result['time_delay_risk']['risk_level'] = 'High'
            result['time_delay_risk']['override'] = (
                'Safety override: timeline critically short for project scope'
            )
            result['risk_score'] = max(result['risk_score'], 80)

        elif expected > 0 and planned > 0 and planned < expected * 0.65 and result['time_delay_risk']['risk_level'] == 'Low':
            result['time_delay_risk']['risk_level'] = 'Medium'
            result['time_delay_risk']['override'] = (
                'Safety override: timeline below recommended duration — elevated delay risk'
            )
            result['risk_score'] = max(result['risk_score'], 40)

        elif floors >= 3 and m['building_size'] > 1500 and result['time_delay_risk']['risk_level'] == 'Low':
            result['time_delay_risk']['risk_level'] = 'Medium'
            result['time_delay_risk']['override'] = (
                'Safety override: large multi-floor project carries inherent schedule risk'
            )
            result['risk_score'] = max(result['risk_score'], 40)

        # Re-derive final_risk from (possibly updated) risk_score
        result['final_risk'] = _SCORE_TO_LEVEL(result['risk_score'])
        return result

    # ── Main hybrid prediction ────────────────────────────────────────────────

    def _predict_risks_core(self, form_data: Dict) -> Dict:
        """
        Core hybrid prediction WITHOUT counterfactual generation.
        Used internally by _generate_counterfactuals to avoid infinite recursion.
        """
        if not self.is_loaded:
            self.load_models()

        m = self._compute_derived_metrics(form_data)

        cost_score,       cost_reason       = self._evaluate_cost_risk(m)
        time_score,       time_reason       = self._evaluate_time_risk(m)
        complexity_score, complexity_reason = self._evaluate_complexity_risk(m)

        ml = self._run_ml_models(m)

        ml_cost_score = self._probs_to_score(ml['cost_probs'])
        ml_time_score = self._probs_to_score(ml['time_probs'])
        ml_score      = (ml_cost_score + ml_time_score) / 2.0

        ml_confident = ml['cost_confident'] and ml['time_confident']
        weights = WEIGHTS_ML_CONFIDENT if ml_confident else WEIGHTS_ML_UNCERTAIN

        final_score = (
            cost_score       * weights['cost'] +
            time_score       * weights['time'] +
            complexity_score * weights['complexity'] +
            ml_score         * weights['ml']
        )
        final_score = round(min(100.0, max(0.0, final_score)), 1)

        cost_level       = _SCORE_TO_LEVEL(cost_score)
        time_level       = _SCORE_TO_LEVEL(time_score)
        complexity_level = _SCORE_TO_LEVEL(complexity_score)
        final_level      = _SCORE_TO_LEVEL(final_score)

        result = {
            'success': True,
            'model_version': self.model_config.get('version', 'unknown') if self.model_config else 'unknown',
            'cost_overrun_risk': {
                'risk_level':   cost_level,
                'rule_score':   round(cost_score, 1),
                'ml_probs':     ml['cost_probs'],
                'probabilities': ml['cost_probs'],   # alias for frontend compatibility
                'ml_confident': ml['cost_confident'],
                'reason':       cost_reason,
                'explanation':  [cost_reason],       # array alias for frontend compatibility
            },
            'time_delay_risk': {
                'risk_level':        time_level,
                'rule_score':        round(time_score, 1),
                'ml_probs':          ml['time_probs'],
                'probabilities':     ml['time_probs'],   # alias for frontend compatibility
                'ml_confident':      ml['time_confident'],
                'reason':            time_reason,
                'explanation':       [time_reason],       # array alias for frontend compatibility
                'expected_duration': round(m['expected_duration'], 1),
                'planned_duration':  round(m['planned_duration'], 1),
            },
            'complexity_risk': {
                'risk_level': complexity_level,
                'score':      round(complexity_score, 1),
                'reason':     complexity_reason,
            },
            'final_risk':  final_level,
            'risk_score':  final_score,
            'hybrid_weights': weights,
            'ml_confidence': {
                'cost_max_prob': round(max(ml['cost_probs'].values()), 3),
                'time_max_prob': round(max(ml['time_probs'].values()), 3),
                'ml_confident':  ml_confident,
                'ml_available':  ml['ml_available'],
            },
            'derived_metrics': {
                'budget_per_sqft':          round(m['budget_per_sqft'], 2),
                'expected_duration':        round(m['expected_duration'], 1),
                'planned_duration':         round(m['planned_duration'], 1),
                'complexity_score':         m['design_complexity_score'],
                'kerala_district':          KERALA_DISTRICTS[m['kerala_district_code']] if 0 <= m['kerala_district_code'] < len(KERALA_DISTRICTS) else 'Unknown',
                'construction_start_month': m['construction_start_month'],
                'monsoon_exposure':         round(m['monsoon_score'], 2),
                'district_risk_tier':       m['district_risk_tier'],
            },
        }

        result = self._apply_safety_overrides(result, m)

        # ── SHAP per-instance explanations ───────────────────────────────────
        if self.is_loaded:
            cost_arr, time_arr = self._build_feature_arrays(m)
            cost_features = self.metadata.get('cost_features', [])
            time_features = self.metadata.get('time_features', [])
            result['shap_explanation'] = {
                'cost': self._compute_shap_explanation(self.cost_model, self.cost_scaler, cost_arr, cost_features),
                'time': self._compute_shap_explanation(self.time_model, self.time_scaler, time_arr, time_features),
            }
        else:
            result['shap_explanation'] = {'cost': [], 'time': []}

        return result

    def _compute_shap_explanation(self, model, scaler, feature_arr: np.ndarray, feature_names: list) -> list:
        """
        Compute per-instance SHAP values using shap.TreeExplainer.
        Works with RandomForestClassifier (multi-class supported).
        Returns top 10 features sorted by |shap_value| descending.
        Falls back to [] on any failure.
        """
        if not SHAP_AVAILABLE or model is None:
            return []
        try:
            X = scaler.transform(feature_arr) if scaler is not None else feature_arr.copy()

            explainer = shap.TreeExplainer(model)
            shap_vals = explainer.shap_values(X)   # list of arrays or 3D array

            # Pick the predicted class
            pred_class = int(model.predict(X)[0])

            # Normalize to a 1D array of shape (n_features,) for the predicted class
            if isinstance(shap_vals, list):
                # list of arrays, one per class — each may be (n_samples, n_features) or (n_features,)
                sv_raw = np.array(shap_vals[pred_class])
                sv = sv_raw[0] if sv_raw.ndim == 2 else sv_raw.flatten()
            elif isinstance(shap_vals, np.ndarray):
                if shap_vals.ndim == 3:
                    # (n_samples, n_features, n_classes)
                    sv = shap_vals[0, :, pred_class]
                elif shap_vals.ndim == 2:
                    # (n_samples, n_features) — binary or single output
                    sv = shap_vals[0]
                else:
                    sv = shap_vals.flatten()
            else:
                sv = np.array(shap_vals).flatten()

            # Ensure sv is 1D and matches feature count
            sv = np.array(sv).flatten()
            raw_vals = np.array(feature_arr[0]).flatten()

            n = min(len(feature_names), len(sv), len(raw_vals))
            result = [
                {'feature': feature_names[i], 'value': float(raw_vals[i]), 'shap_value': float(sv[i])}
                for i in range(n)
            ]
            result.sort(key=lambda x: abs(x['shap_value']), reverse=True)
            top = result[:10]
            import logging
            logging.getLogger(__name__).info(
                f"SHAP OK — top feature: {top[0]['feature']}={top[0]['shap_value']:.4f}" if top else "SHAP OK — no features"
            )
            return top
        except Exception as e:
            import logging
            logging.getLogger(__name__).warning(f"SHAP computation failed: {e}")
            return []

    def predict_risks(self, form_data: Dict) -> Dict:
        """
        Hybrid risk prediction: blends ML probabilities with rule-based scores.

        Returns a structured dict with:
          cost_overrun_risk, time_delay_risk, complexity_risk,
          final_risk, risk_score (0-100), hybrid_weights, ml_confidence,
          risk_reduction_suggestions
        """
        # Core prediction (no counterfactuals — avoids infinite recursion)
        result = self._predict_risks_core(form_data)

        # Counterfactual suggestions (only for Medium / High risk)
        result['risk_reduction_suggestions'] = self._generate_counterfactuals(
            form_data, result['final_risk'], result['risk_score']
        )

        return result

    # ── Counterfactual risk reduction ────────────────────────────────────────

    def _generate_counterfactuals(self, form_data: Dict, current_risk: str, current_score: float) -> list:
        """
        Generate context-aware counterfactual suggestions.

        Deltas are derived from the actual gap between the current input values
        and the nearest benchmark thresholds — NOT hardcoded numbers.

        Adjustable dimensions (building_size and num_floors are never touched):
          - budget_amount   : nudged to reach the next cost benchmark tier
          - planned_duration_months : nudged to close the gap to expected duration
          - special_features / customization : cleared when complexity is a driver
        """
        _rank = {'Low': 0, 'Medium': 1, 'High': 2}

        # For Low risk, still generate month suggestions if monsoon exposure is significant
        current_month_early = int(form_data.get('construction_start_month', 1))
        if current_risk == 'Low' and _monsoon_exposure(current_month_early) <= 0.15:
            return []
        current_rank = _rank.get(current_risk, 2)

        building_size = float(form_data.get('building_size_sqft', 1))
        budget_amount = float(form_data.get('budget_amount', 0))
        current_bps   = budget_amount / building_size if building_size > 0 else 0
        current_dur   = float(form_data.get('planned_duration_months', 0))

        # Compute expected duration from the actual project metrics
        num_floors       = int(form_data.get('num_floors', 1))
        expected_dur     = max(3.0, (building_size / SQFT_PER_MONTH) + (num_floors * MONTHS_PER_FLOOR))
        has_customization = bool(form_data.get('special_features', '') or
                                  form_data.get('custom_requirements', '') or
                                  form_data.get('architectural_preferences', ''))

        candidates = []

        # ── Budget suggestions: target the next benchmark tier above current ──
        budget_tiers = [
            (COST_BENCHMARK_LOW,      "minimum viable"),
            (COST_BENCHMARK_STANDARD, "standard construction"),
            (COST_BENCHMARK_GOOD,     "comfortable"),
        ]
        for tier_value, tier_label in budget_tiers:
            if current_bps < tier_value:
                new_budget = tier_value * building_size
                delta_bps  = tier_value - current_bps
                candidates.append((
                    f"Increase budget to ₹{tier_value:,}/sqft ({tier_label} level) "
                    f"— add ₹{delta_bps:.0f}/sqft (total budget ₹{new_budget:,.0f})",
                    {'budget_amount': new_budget},
                    delta_bps,          # smaller delta = closer to current = sorted first
                ))
                break   # only suggest the nearest tier; next tier appears in combo below

        # Also suggest the tier after the nearest one (bigger jump, bigger improvement)
        reached_first = False
        for tier_value, tier_label in budget_tiers:
            if current_bps < tier_value:
                if reached_first:
                    new_budget = tier_value * building_size
                    delta_bps  = tier_value - current_bps
                    candidates.append((
                        f"Increase budget to ₹{tier_value:,}/sqft ({tier_label} level) "
                        f"— add ₹{delta_bps:.0f}/sqft (total budget ₹{new_budget:,.0f})",
                        {'budget_amount': new_budget},
                        delta_bps,
                    ))
                    break
                reached_first = True

        # ── Timeline suggestions: close the gap to expected duration ──
        if current_dur > 0 and current_dur < expected_dur:
            gap = expected_dur - current_dur

            # Half-gap nudge
            half_target = round(current_dur + gap * 0.5, 1)
            candidates.append((
                f"Extend timeline to {half_target:.0f} months "
                f"(halfway to recommended {expected_dur:.0f} months)",
                {'planned_duration_months': half_target},
                gap * 0.5 * 50,
            ))

            # Full gap — reach the expected duration
            candidates.append((
                f"Extend timeline to {expected_dur:.0f} months "
                f"(recommended duration for this project scope)",
                {'planned_duration_months': expected_dur},
                gap * 50,
            ))

        # ── Customization removal: only suggest if complexity is actually a driver ──
        if has_customization:
            # Check whether removing features meaningfully changes the complexity score
            test_no_features = {**form_data,
                                 'special_features': '',
                                 'custom_requirements': '',
                                 'architectural_preferences': ''}
            m_with    = self._compute_derived_metrics(form_data)
            m_without = self._compute_derived_metrics(test_no_features)
            complexity_drop = m_with['design_complexity_score'] - m_without['design_complexity_score']
            if complexity_drop >= 2:   # only worth suggesting if it actually moves the needle
                candidates.append((
                    f"Remove special/custom design features "
                    f"(reduces complexity score by {complexity_drop} points)",
                    {'special_features': '', 'custom_requirements': '', 'architectural_preferences': ''},
                    150,
                ))

        # ── Combined: nearest budget tier + full timeline fix ──
        if current_dur > 0 and current_dur < expected_dur:
            for tier_value, tier_label in budget_tiers:
                if current_bps < tier_value:
                    new_budget_c = tier_value * building_size
                    candidates.append((
                        f"Increase budget to ₹{tier_value:,}/sqft AND extend timeline "
                        f"to {expected_dur:.0f} months",
                        {'budget_amount': new_budget_c,
                         'planned_duration_months': expected_dur},
                        (tier_value - current_bps) + (expected_dur - current_dur) * 50,
                    ))
                    break

        # ── Construction start month suggestions ──
        # Only suggest if the current month has meaningful monsoon exposure
        current_month = int(form_data.get('construction_start_month', 1))
        current_exposure = _monsoon_exposure(current_month)

        if current_exposure > 0.15:   # only worth suggesting if there's real monsoon impact
            # Dry season months ordered by lowest monsoon exposure
            _DRY_MONTHS = [12, 1, 2, 3, 4, 11, 5, 10]  # Dec-Apr best, then Nov/May/Oct
            _MONTH_NAMES = {
                1: 'January', 2: 'February', 3: 'March', 4: 'April',
                5: 'May', 6: 'June', 7: 'July', 8: 'August',
                9: 'September', 10: 'October', 11: 'November', 12: 'December',
            }

            # Find the best alternative month (lowest exposure, different from current)
            best_month = None
            best_exposure = current_exposure
            for m in _DRY_MONTHS:
                if m == current_month:
                    continue
                exp = _monsoon_exposure(m)
                if exp < best_exposure:
                    best_month = m
                    best_exposure = exp
                    break   # first one is already the best dry month

            if best_month is not None:
                exposure_drop = round((current_exposure - best_exposure) * 100)
                current_name  = _MONTH_NAMES[current_month]
                best_name     = _MONTH_NAMES[best_month]
                candidates.append((
                    f"Start construction in {best_name} instead of {current_name} "
                    f"— reduces monsoon exposure by {exposure_drop}% "
                    f"(dry season start avoids SW/NE monsoon disruption)",
                    {'construction_start_month': best_month},
                    200,   # moderate magnitude — easy change, no cost
                ))

            # Also suggest the absolute best dry month (December) if not already suggested
            if best_month != 12 and current_month != 12:
                dec_exposure = _monsoon_exposure(12)
                if dec_exposure < current_exposure:
                    dec_drop = round((current_exposure - dec_exposure) * 100)
                    candidates.append((
                        f"Start construction in December (best dry season start) "
                        f"— reduces monsoon exposure by {dec_drop}% for maximum schedule reliability",
                        {'construction_start_month': 12},
                        250,
                    ))

        # ── Evaluate each candidate ──
        suggestions = []
        for label, overrides, magnitude in candidates:
            scenario = {**form_data, **overrides}
            try:
                scenario_result = self._predict_risks_core(scenario)
                new_risk  = scenario_result.get('final_risk', current_risk)
                new_score = scenario_result.get('risk_score', current_score)
                new_rank  = _rank.get(new_risk, current_rank)

                # Determine suggestion type for frontend styling
                if 'construction_start_month' in overrides:
                    stype = 'month'
                elif 'budget_amount' in overrides and 'planned_duration_months' not in overrides:
                    stype = 'budget'
                elif 'planned_duration_months' in overrides and 'budget_amount' not in overrides:
                    stype = 'timeline'
                elif 'budget_amount' in overrides and 'planned_duration_months' in overrides:
                    stype = 'combined'
                else:
                    stype = 'other'

                score_delta = round(current_score - new_score, 1)

                # Include if: risk level drops, OR it's a month suggestion with ≥1pt score reduction
                # (month change is zero-cost so worth showing even within same risk level)
                qualifies = (new_rank < current_rank) or (stype == 'month' and score_delta >= 1.0)

                if qualifies:
                    suggestions.append({
                        'suggestion':  label,
                        'new_risk':    new_risk,
                        'new_score':   round(new_score, 1),
                        'score_delta': score_delta,
                        'magnitude':   magnitude,
                        'type':        stype,
                    })
            except Exception:
                continue

        # Sort: biggest risk-level improvement first, then smallest change magnitude
        suggestions.sort(key=lambda x: (_rank.get(x['new_risk'], 2), x['magnitude']))

        # Deduplicate by new_risk level — keep the smallest-change path to each level
        # Month suggestions get their own slot (don't compete with budget/timeline)
        seen_levels = set()
        seen_month  = False
        output = []
        for s in suggestions:
            if s['type'] == 'month' and not seen_month:
                seen_month = True
                output.append({
                    'suggestion':  s['suggestion'],
                    'new_risk':    s['new_risk'],
                    'new_score':   s['new_score'],
                    'score_delta': s['score_delta'],
                    'type':        s['type'],
                })
            elif s['type'] != 'month' and s['new_risk'] not in seen_levels:
                seen_levels.add(s['new_risk'])
                output.append({
                    'suggestion':  s['suggestion'],
                    'new_risk':    s['new_risk'],
                    'new_score':   s['new_score'],
                    'score_delta': s['score_delta'],
                    'type':        s['type'],
                })
            if len(output) == 4:   # allow up to 4: budget, timeline, month, combined
                break

        return output

    # ── Feature conversion (kept for backward compatibility) ─────────────────

    def convert_form_to_features(self, form_data: Dict):
        """Legacy helper — returns (cost_features, time_features) numpy arrays."""
        if not self.is_loaded:
            if not self.load_models():
                return None, None
        m = self._compute_derived_metrics(form_data)
        return self._build_feature_arrays(m)

    # ── Explanation helpers ───────────────────────────────────────────────────

    def _generate_explanation(self, features, feature_names, importance, risk_type):
        try:
            explanations = []
            for feature_name, _ in list(importance.items())[:3]:
                if feature_name in feature_names:
                    val = features[feature_names.index(feature_name)]
                    explanations.append(self._get_feature_explanation(feature_name, val, 0, risk_type))
            return explanations
        except Exception as e:
            return [f"Unable to generate explanation: {str(e)}"]

    def _get_feature_explanation(self, feature_name, value, importance, risk_type):
        templates = {
            'budget_per_sqft':             f"Budget per sq.ft of ₹{value:.0f} influences {risk_type} risk",
            'design_complexity_score':     f"Design complexity score of {value:.0f} is a key factor in {risk_type} risk",
            'building_size_sqft':          f"Building size of {value:.0f} sq.ft affects {risk_type} probability",
            'customization_level':         f"Customization level of {value:.0f} impacts {risk_type} likelihood",
            'num_floors':                  f"Number of floors ({value:.0f}) contributes to {risk_type} risk",
            'development_constraint_level': f"Site constraints (level {value:.0f}) influence {risk_type} risk",
            'planned_duration_months':     f"Planned duration of {value:.0f} months affects {risk_type} probability",
            'site_difficulty_score':       f"Site difficulty score of {value:.0f} impacts {risk_type} risk",
        }
        return templates.get(feature_name,
                              f"{feature_name.replace('_', ' ').title()} (value: {value:.1f}) influences {risk_type} risk")


# ── Module-level singleton ────────────────────────────────────────────────────

_predictor_instance = None

def get_predictor() -> ConstructionRiskPredictor:
    global _predictor_instance
    if _predictor_instance is None:
        _predictor_instance = ConstructionRiskPredictor()
    return _predictor_instance
