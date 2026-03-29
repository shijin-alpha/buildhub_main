"""
Dataset generator for construction risk assessment — Kerala-specific.

Features:
  - kerala_district_code (0-13): encodes material cost, labor, terrain, flood risk
  - construction_start_month (1-12): encodes monsoon exposure
  - monsoon_exposure_score (0.0-1.0): continuous derived feature — fraction of
    first 6 construction months that fall in monsoon season
  - district_risk_tier (0-2): derived from weighted average of district climate
    profiles (terrain, rainfall, flood risk) — no longer hardcoded per district
  - terrain_code (0-5): flat=0, midland=1, coastal=2, backwater=3, hilly=4, highland=5
  - rainfall_code (0-3): low=0, moderate=1, high=2, very_high=3
  - flood_risk_code (0-2): low=0, moderate=1, high=2
  - effective_monsoon_score (0.0-1.0): monsoon_exposure × rainfall multiplier
    (captures that Munnar's very_high rainfall makes monsoon far more impactful
    than Palakkad's low rainfall zone)

v7-env-risk: District risk now computed from environmental factors (terrain,
rainfall, flood) rather than hardcoded per-district values. N=4000.
"""

import pandas as pd
import numpy as np
import os

np.random.seed(42)
N = 4000  # rows per dataset

# ── Kerala district encoding ──────────────────────────────────────────────────
KERALA_DISTRICTS = [
    'Thiruvananthapuram',  # 0
    'Kollam',              # 1
    'Pathanamthitta',      # 2  hilly, high rainfall
    'Alappuzha',           # 3  backwater, flood-prone
    'Kottayam',            # 4  hilly, high rainfall
    'Idukki',              # 5  very hilly, remote — highest risk
    'Ernakulam',           # 6  urban, best supply — lowest risk
    'Thrissur',            # 7  urban, moderate
    'Palakkad',            # 8  inland, drier
    'Malappuram',          # 9
    'Kozhikode',           # 10
    'Wayanad',             # 11 hilly, remote — high risk
    'Kannur',              # 12
    'Kasaragod',           # 13
]

# district_risk_tier: derived from environmental factors — see _district_risk_tier() below.
# No longer hardcoded; computed from the weighted average of each district's
# climate profile (terrain difficulty, rainfall intensity, flood risk).

# Per-district additive risk modifiers (cost_mod, time_mod) for label scoring.
# Removed: replaced by _env_cost_mod() and _env_time_mod() which compute these
# from terrain_code, rainfall_code, and flood_risk_code directly.

# ── Panchayat climate profiles per district ───────────────────────────────────
# terrain_code: flat=0, midland=1, coastal=2, backwater=3, hilly=4, highland=5
# rainfall_code: low=0, moderate=1, high=2, very_high=3
# flood_risk_code: low=0, moderate=1, high=2
#
# Each district has a distribution of terrain/rainfall/flood profiles
# reflecting the real mix of panchayats in that district.
# Format: list of (terrain_code, rainfall_code, flood_risk_code, weight)
DISTRICT_CLIMATE_PROFILES = {
    0: [  # Thiruvananthapuram — coastal + midland, moderate rainfall
        (2, 1, 1, 0.25),  # coastal, moderate, moderate flood
        (1, 1, 0, 0.40),  # midland, moderate, low flood
        (4, 2, 0, 0.20),  # hilly, high, low flood
        (0, 1, 0, 0.15),  # flat, moderate, low flood
    ],
    1: [  # Kollam — coastal + backwater, high rainfall
        (2, 2, 1, 0.20),  # coastal, high, moderate
        (3, 2, 2, 0.25),  # backwater, high, high flood
        (1, 2, 0, 0.35),  # midland, high, low
        (4, 2, 0, 0.20),  # hilly, high, low
    ],
    2: [  # Pathanamthitta — midland + hilly, high to very_high
        (1, 2, 1, 0.30),  # midland, high, moderate
        (4, 2, 0, 0.30),  # hilly, high, low
        (5, 3, 0, 0.25),  # highland, very_high, low
        (0, 2, 1, 0.15),  # flat, high, moderate
    ],
    3: [  # Alappuzha — backwater + coastal, high rainfall, HIGH flood
        (3, 2, 2, 0.50),  # backwater, high, high flood
        (2, 2, 2, 0.25),  # coastal, high, high flood
        (0, 2, 1, 0.15),  # flat, high, moderate
        (1, 2, 1, 0.10),  # midland, high, moderate
    ],
    4: [  # Kottayam — midland + hilly + backwater, high to very_high
        (1, 2, 0, 0.30),  # midland, high, low
        (4, 2, 0, 0.25),  # hilly, high, low
        (5, 3, 0, 0.20),  # highland, very_high, low
        (3, 2, 2, 0.15),  # backwater, high, high
        (0, 2, 1, 0.10),  # flat, high, moderate
    ],
    5: [  # Idukki — highland, very_high rainfall, landslide risk
        (5, 3, 0, 0.60),  # highland, very_high, low (elevation drains)
        (4, 3, 0, 0.30),  # hilly, very_high, low
        (1, 2, 0, 0.10),  # midland, high, low
    ],
    6: [  # Ernakulam — urban + coastal + flat, high rainfall
        (0, 2, 1, 0.30),  # flat, high, moderate
        (2, 2, 2, 0.20),  # coastal, high, high
        (3, 2, 2, 0.15),  # backwater, high, high
        (1, 2, 0, 0.25),  # midland, high, low
        (4, 2, 0, 0.10),  # hilly, high, low
    ],
    7: [  # Thrissur — flat + coastal, high rainfall
        (0, 2, 0, 0.40),  # flat, high, low
        (2, 2, 1, 0.25),  # coastal, high, moderate
        (1, 2, 0, 0.20),  # midland, high, low
        (4, 3, 0, 0.15),  # hilly (Athirappilly), very_high, low
    ],
    8: [  # Palakkad — flat + dry (Palakkad Gap), LOW rainfall, HIGH heat
        (0, 0, 0, 0.55),  # flat, low, low
        (1, 1, 0, 0.20),  # midland, moderate, low
        (4, 1, 0, 0.15),  # hilly, moderate, low
        (5, 1, 0, 0.10),  # highland, moderate, low
    ],
    9: [  # Malappuram — flat + coastal + hilly, high rainfall
        (0, 2, 1, 0.35),  # flat, high, moderate
        (2, 2, 1, 0.20),  # coastal, high, moderate
        (4, 2, 0, 0.25),  # hilly, high, low
        (1, 2, 0, 0.20),  # midland, high, low
    ],
    10: [  # Kozhikode — coastal + hilly, high rainfall
        (2, 2, 1, 0.30),  # coastal, high, moderate
        (0, 2, 0, 0.25),  # flat, high, low
        (4, 2, 0, 0.30),  # hilly, high, low
        (1, 2, 0, 0.15),  # midland, high, low
    ],
    11: [  # Wayanad — highland, very_high rainfall
        (5, 3, 0, 0.70),  # highland, very_high, low
        (4, 3, 0, 0.20),  # hilly, very_high, low
        (1, 2, 0, 0.10),  # midland, high, low
    ],
    12: [  # Kannur — coastal + flat + hilly, high rainfall
        (2, 2, 1, 0.30),  # coastal, high, moderate
        (0, 2, 0, 0.30),  # flat, high, low
        (4, 2, 0, 0.25),  # hilly, high, low
        (1, 2, 0, 0.15),  # midland, high, low
    ],
    13: [  # Kasaragod — coastal + flat, high rainfall
        (2, 2, 1, 0.35),  # coastal, high, moderate
        (0, 2, 0, 0.30),  # flat, high, low
        (4, 2, 0, 0.20),  # hilly, high, low
        (1, 2, 0, 0.15),  # midland, high, low
    ],
}

# Rainfall multiplier for effective monsoon score
RAINFALL_MULTIPLIER = {0: 0.4, 1: 0.7, 2: 1.0, 3: 1.3}

# ── Environmental risk functions ──────────────────────────────────────────────
# These replace the old hardcoded DISTRICT_RISK lookup.
# Risk is computed from the sampled panchayat's terrain, rainfall, and flood
# codes — the same values already present in DISTRICT_CLIMATE_PROFILES.

# Terrain cost/time modifiers: highland terrain = expensive access + slow work
TERRAIN_COST_MOD = {0: 0, 1: 1, 2: 1, 3: 2, 4: 2, 5: 3}   # flat→highland
TERRAIN_TIME_MOD = {0: 0, 1: 1, 2: 1, 3: 2, 4: 2, 5: 3}

# Flood risk modifiers: high flood risk = material damage + work stoppages
FLOOD_COST_MOD = {0: 0, 1: 1, 2: 2}
FLOOD_TIME_MOD = {0: 0, 1: 1, 2: 3}

# Rainfall cost/time modifiers: higher rainfall = more material wastage + delays
RAINFALL_COST_MOD = {0: -1, 1: 0, 2: 1, 3: 2}   # low rainfall = slight saving
RAINFALL_TIME_MOD = {0: -1, 1: 0, 2: 1, 3: 2}


def _env_cost_mod(terrain_code: int, rainfall_code: int, flood_risk_code: int) -> int:
    """Compute additive cost risk modifier from environmental factors."""
    return (
        TERRAIN_COST_MOD[terrain_code]
        + RAINFALL_COST_MOD[rainfall_code]
        + FLOOD_COST_MOD[flood_risk_code]
    )


def _env_time_mod(terrain_code: int, rainfall_code: int, flood_risk_code: int) -> int:
    """Compute additive time risk modifier from environmental factors."""
    return (
        TERRAIN_TIME_MOD[terrain_code]
        + RAINFALL_TIME_MOD[rainfall_code]
        + FLOOD_TIME_MOD[flood_risk_code]
    )


def _district_risk_tier(district_code: int) -> int:
    """
    Derive district_risk_tier (0=low, 1=moderate, 2=high) from the weighted
    average environmental risk of the district's climate profile.
    Replaces the old hardcoded DISTRICT_RISK_TIER lookup.
    """
    profiles = DISTRICT_CLIMATE_PROFILES[district_code]
    total_weight = sum(p[3] for p in profiles)
    avg_env_cost = sum(
        _env_cost_mod(p[0], p[1], p[2]) * p[3]
        for p in profiles
    ) / total_weight
    # avg_env_cost range: roughly -2 (Palakkad) to +7 (Idukki/Alappuzha)
    if avg_env_cost >= 4.0:
        return 2   # high
    elif avg_env_cost >= 1.5:
        return 1   # moderate
    else:
        return 0   # low


def _sample_climate(district_code):
    """Sample a (terrain_code, rainfall_code, flood_risk_code) for a district."""
    profiles = DISTRICT_CLIMATE_PROFILES[district_code]
    weights  = [p[3] for p in profiles]
    idx = np.random.choice(len(profiles), p=np.array(weights) / sum(weights))
    t, r, f, _ = profiles[idx]
    return t, r, f

# ── Monsoon helpers ───────────────────────────────────────────────────────────
_SW = {6, 7, 8, 9}    # SW monsoon — heavy
_NE = {10, 11}         # NE monsoon — moderate

def monsoon_exposure(start_month: int, window: int = 6) -> float:
    """Continuous 0-1 score: fraction of first `window` months in monsoon."""
    raw = 0.0
    for i in range(window):
        m = ((start_month - 1 + i) % 12) + 1
        if m in _SW:
            raw += 1.5
        elif m in _NE:
            raw += 0.8
    return round(min(1.0, raw / 9.0), 4)

def monsoon_cost_add(start_month: int) -> int:
    """Additive cost risk score from monsoon (material wastage, rework)."""
    raw = 0.0
    for i in range(6):
        m = ((start_month - 1 + i) % 12) + 1
        if m in _SW:
            raw += 1.0
        elif m in _NE:
            raw += 0.5
    return int(min(4, raw))

def monsoon_time_add(start_month: int) -> int:
    """Additive time risk score from monsoon (work stoppages, delays)."""
    raw = 0.0
    for i in range(6):
        m = ((start_month - 1 + i) % 12) + 1
        if m in _SW:
            raw += 1.5
        elif m in _NE:
            raw += 0.8
    return int(min(5, raw))


# ── Cost dataset ──────────────────────────────────────────────────────────────
def generate_cost_dataset(n):
    rows = []
    for _ in range(n):
        plot_size     = np.random.randint(600, 5000)
        building_size = np.random.randint(400, 4000)
        num_floors    = np.random.randint(1, 6)
        plot_shape    = np.random.randint(0, 4)
        topography    = np.random.randint(0, 4)
        design_style  = np.random.randint(0, 4)
        num_bedrooms  = np.random.randint(1, 7)
        num_bathrooms = np.random.randint(1, 5)
        total_rooms   = num_bedrooms + num_bathrooms + 2
        customization = np.random.randint(0, 6)
        dev_constraint = np.random.randint(0, 4)
        design_complexity = min(18, num_floors * 2 + customization * 2 + topography)

        # District — weighted toward more common districts
        district_code = np.random.choice(
            list(range(14)),
            p=[0.12, 0.07, 0.05, 0.06, 0.07, 0.04, 0.14, 0.10, 0.07, 0.07, 0.08, 0.04, 0.06, 0.03]
        )

        # Start month — slight bias toward dry season (Dec-Mar)
        start_month = np.random.choice(
            list(range(1, 13)),
            p=[0.10, 0.10, 0.10, 0.09, 0.08, 0.06, 0.06, 0.07, 0.08, 0.07, 0.08, 0.11]
        )

        # Panchayat-level climate profile for this district
        terrain_code, rainfall_code, flood_risk_code = _sample_climate(district_code)

        # Derived features
        m_exposure   = monsoon_exposure(start_month)
        d_risk_tier  = _district_risk_tier(district_code)
        # Effective monsoon: scaled by panchayat rainfall intensity
        eff_monsoon  = round(min(1.0, m_exposure * RAINFALL_MULTIPLIER[rainfall_code]), 4)

        # Budget per sqft
        bps_cat = np.random.choice(
            ['very_low', 'low', 'medium', 'high', 'very_high'],
            p=[0.10, 0.15, 0.35, 0.25, 0.15]
        )
        bps_map = {
            'very_low':  (300,  600),
            'low':       (600,  1000),
            'medium':    (1000, 2500),
            'high':      (2500, 5000),
            'very_high': (5000, 9000),
        }
        lo, hi = bps_map[bps_cat]
        budget_per_sqft = np.random.randint(lo, hi)
        budget_amount   = budget_per_sqft * building_size

        # ── Label logic ──
        risk_score = 0

        # Budget adequacy (strongest signal)
        if budget_per_sqft < 500:
            risk_score += 5
        elif budget_per_sqft < 800:
            risk_score += 3
        elif budget_per_sqft < 1200:
            risk_score += 1

        # Design complexity
        if design_complexity >= 14:
            risk_score += 3
        elif design_complexity >= 9:
            risk_score += 1

        # Floors
        if num_floors >= 4:
            risk_score += 2
        elif num_floors >= 3:
            risk_score += 1

        # Site constraints
        risk_score += dev_constraint
        risk_score += topography // 2

        # Environmental cost modifier — derived from sampled panchayat climate
        # (replaces old DISTRICT_RISK hardcoded lookup)
        risk_score += _env_cost_mod(terrain_code, rainfall_code, flood_risk_code)

        # Monsoon cost exposure (uses effective monsoon — panchayat-aware)
        risk_score += monsoon_cost_add(start_month)
        risk_score += int(eff_monsoon * 2)  # extra signal from rainfall intensity

        # Extra boost from derived features (so model learns them directly)
        risk_score += d_risk_tier          # 0, 1, or 2
        risk_score += int(m_exposure * 3)  # 0-3 based on raw monsoon exposure
        risk_score += rainfall_code        # 0-3: low→very_high rainfall zone

        if risk_score >= 12:
            label = 2  # High
        elif risk_score >= 5:
            label = 1  # Medium
        else:
            label = 0  # Low

        rows.append([
            plot_size, building_size, num_floors, budget_amount, budget_per_sqft,
            plot_shape, topography, num_bedrooms, num_bathrooms, total_rooms,
            design_style, customization, design_complexity, dev_constraint,
            district_code, start_month, m_exposure, d_risk_tier,
            terrain_code, rainfall_code, flood_risk_code, eff_monsoon,
            label
        ])

    cols = [
        'plot_size_sqft', 'building_size_sqft', 'num_floors', 'budget_amount',
        'budget_per_sqft', 'plot_shape_code', 'topography_code', 'num_bedrooms',
        'num_bathrooms', 'total_rooms', 'design_style_code', 'customization_level',
        'design_complexity_score', 'development_constraint_level',
        'kerala_district_code', 'construction_start_month',
        'monsoon_exposure_score', 'district_risk_tier',
        'terrain_code', 'rainfall_code', 'flood_risk_code', 'effective_monsoon_score',
        'cost_overrun_risk'
    ]
    return pd.DataFrame(rows, columns=cols)


# ── Time dataset ──────────────────────────────────────────────────────────────
def generate_time_dataset(n):
    rows = []
    for _ in range(n):
        plot_size     = np.random.randint(600, 5000)
        building_size = np.random.randint(400, 4000)
        num_floors    = np.random.randint(1, 6)
        plot_shape    = np.random.randint(0, 4)
        topography    = np.random.randint(0, 4)
        customization = np.random.randint(0, 6)
        design_complexity = min(18, num_floors * 2 + customization * 2 + topography)
        site_difficulty   = min(10, topography * 2 + np.random.randint(0, 4))

        district_code = np.random.choice(
            list(range(14)),
            p=[0.12, 0.07, 0.05, 0.06, 0.07, 0.04, 0.14, 0.10, 0.07, 0.07, 0.08, 0.04, 0.06, 0.03]
        )
        start_month = np.random.choice(
            list(range(1, 13)),
            p=[0.10, 0.10, 0.10, 0.09, 0.08, 0.06, 0.06, 0.07, 0.08, 0.07, 0.08, 0.11]
        )

        # Panchayat-level climate profile for this district
        terrain_code, rainfall_code, flood_risk_code = _sample_climate(district_code)

        # Derived features
        m_exposure  = monsoon_exposure(start_month)
        d_risk_tier = _district_risk_tier(district_code)
        # Effective monsoon: scaled by panchayat rainfall intensity
        eff_monsoon = round(min(1.0, m_exposure * RAINFALL_MULTIPLIER[rainfall_code]), 4)

        # Planned duration
        dur_cat = np.random.choice(
            ['very_short', 'short', 'medium', 'long', 'very_long'],
            p=[0.05, 0.10, 0.40, 0.30, 0.15]
        )
        dur_map = {
            'very_short': (3,  6),
            'short':      (6,  10),
            'medium':     (10, 18),
            'long':       (18, 28),
            'very_long':  (28, 36),
        }
        lo, hi = dur_map[dur_cat]
        planned_duration = np.random.randint(lo, hi + 1)

        # ── Label logic ──
        risk_score = 0

        realistic_min = max(3, building_size / 200 + num_floors * 2)
        if planned_duration < realistic_min * 0.5:
            risk_score += 5
        elif planned_duration < realistic_min * 0.75:
            risk_score += 3
        elif planned_duration < realistic_min:
            risk_score += 1

        if num_floors >= 4:
            risk_score += 3
        elif num_floors >= 3:
            risk_score += 2
        elif num_floors >= 2:
            risk_score += 1

        if site_difficulty >= 7:
            risk_score += 2
        elif site_difficulty >= 4:
            risk_score += 1

        if design_complexity >= 12:
            risk_score += 1

        # Environmental time modifier — derived from sampled panchayat climate
        # (replaces old DISTRICT_RISK hardcoded lookup)
        risk_score += _env_time_mod(terrain_code, rainfall_code, flood_risk_code)

        # Monsoon time exposure (uses effective monsoon — panchayat-aware)
        risk_score += monsoon_time_add(start_month)
        risk_score += int(eff_monsoon * 3)  # extra signal from rainfall intensity

        # Extra boost from derived features
        risk_score += d_risk_tier          # 0, 1, or 2
        risk_score += int(m_exposure * 4)  # 0-4 based on raw monsoon exposure
        risk_score += rainfall_code        # 0-3: low→very_high rainfall zone

        if risk_score >= 12:
            label = 2
        elif risk_score >= 5:
            label = 1
        else:
            label = 0

        rows.append([
            plot_size, building_size, num_floors, planned_duration,
            plot_shape, topography, design_complexity, customization,
            site_difficulty, district_code, start_month,
            m_exposure, d_risk_tier,
            terrain_code, rainfall_code, flood_risk_code, eff_monsoon,
            label
        ])

    cols = [
        'plot_size_sqft', 'building_size_sqft', 'num_floors', 'planned_duration_months',
        'plot_shape_code', 'topography_code', 'design_complexity_score',
        'customization_level', 'site_difficulty_score',
        'kerala_district_code', 'construction_start_month',
        'monsoon_exposure_score', 'district_risk_tier',
        'terrain_code', 'rainfall_code', 'flood_risk_code', 'effective_monsoon_score',
        'time_delay_risk'
    ]
    return pd.DataFrame(rows, columns=cols)


if __name__ == '__main__':
    out_dir = os.path.join(os.path.dirname(__file__), 'data')
    os.makedirs(out_dir, exist_ok=True)

    cost_df = generate_cost_dataset(N)
    time_df = generate_time_dataset(N)

    cost_path = os.path.join(out_dir, 'cost_overrun_risk_dataset.csv')
    time_path = os.path.join(out_dir, 'time_delay_risk_dataset.csv')

    cost_df.to_csv(cost_path, index=False)
    time_df.to_csv(time_path, index=False)

    print(f"Cost dataset: {len(cost_df)} rows × {len(cost_df.columns)} features")
    print("Cost label dist:\n", cost_df['cost_overrun_risk'].value_counts().sort_index())
    print(f"\nTime dataset: {len(time_df)} rows × {len(time_df.columns)} features")
    print("Time label dist:\n", time_df['time_delay_risk'].value_counts().sort_index())
    print("\nDistrict dist (cost):\n", cost_df['kerala_district_code'].value_counts().sort_index())
    print("\nMonsoon exposure sample:\n", cost_df['monsoon_exposure_score'].describe())
    print("\nDistrict risk tier dist:\n", cost_df['district_risk_tier'].value_counts().sort_index())
    print("\nDatasets saved.")
