# IEEE Research Paper — BuildHub AI Risk Prediction System

## BuildHub: A Hybrid Machine Learning System for Pre-Construction Cost and Time Risk Prediction in Kerala Residential Projects

---

## I. ABSTRACT

Residential construction projects in Kerala, India, routinely suffer from cost overruns and schedule delays. Homeowners lack the technical expertise to evaluate whether their budget and timeline are realistic before committing to a project. This paper presents BuildHub's AI risk prediction system — a hybrid machine learning and rule-based framework that predicts cost overrun risk and time delay risk at the pre-construction stage. The system combines trained Random Forest classifiers with domain-expert rule-based scoring, Kerala-specific environmental intelligence (panchayat-level climate profiles, monsoon exposure scoring, terrain and flood risk), per-instance SHAP explanations, and counterfactual suggestions. Both models are trained on synthetically generated datasets of 4,000 samples each, grounded in real Kerala construction domain knowledge.

---

## II. PROBLEM STATEMENT

Kerala residential construction projects face two primary failure modes before a single brick is laid:

1. **Cost Overrun** — the actual construction cost exceeds the homeowner's stated budget.
2. **Time Delay** — the actual construction duration exceeds the homeowner's planned timeline.

These failures stem from homeowners setting unrealistic budgets (below Kerala construction benchmarks) and timelines (ignoring monsoon seasons, terrain difficulty, and project complexity). There is no standardized tool that assesses this risk at the planning stage using the homeowner's own inputs.

---

## III. DATASET

### Overview

| Dataset | File | Rows | Features | Target |
|---|---|---|---|---|
| Cost Overrun Risk | `cost_overrun_risk_dataset.csv` | 4,000 | 22 | `cost_overrun_risk` (0/1/2) |
| Time Delay Risk | `time_delay_risk_dataset.csv` | 4,000 | 17 | `time_delay_risk` (0/1/2) |

### Generation Method

Both datasets are synthetically generated using `generate_dataset.py` with `numpy.random.seed(42)` for full reproducibility. The generation logic encodes real Kerala construction domain knowledge:

- Kerala construction cost benchmarks (₹/sqft ranges per quality tier)
- Panchayat-level climate profiles for all 14 Kerala districts (terrain, rainfall, flood risk)
- Kerala monsoon calendar (SW monsoon Jun–Sep, NE monsoon Oct–Nov)
- District-weighted sampling reflecting actual population distribution

Dataset version: **v7-env-risk** — district risk tier is derived from environmental factors (terrain, rainfall, flood) rather than hardcoded per-district values, making the feature space consistent and generalizable.

### Climate Reference Sources
IMD Kerala rainfall data, Kerala State Disaster Management Authority flood risk data, Kerala Flood Atlas 2018, KSEB watershed maps.

### Cost Overrun Dataset — 22 Features

| # | Feature | Description |
|---|---|---|
| 1 | `plot_size_sqft` | Total plot area (sq ft) |
| 2 | `building_size_sqft` | Built-up area (sq ft) |
| 3 | `num_floors` | Number of floors |
| 4 | `budget_amount` | Total project budget (INR) |
| 5 | `budget_per_sqft` | Budget ÷ building size (derived) |
| 6 | `plot_shape_code` | 0=rectangular, 1=square, 2=irregular, 3=l_shaped |
| 7 | `topography_code` | 0=flat, 1=gentle_slope, 2=steep_slope, 3=hilly |
| 8 | `num_bedrooms` | Number of bedrooms |
| 9 | `num_bathrooms` | Number of bathrooms |
| 10 | `total_rooms` | bedrooms + bathrooms + 2 (derived) |
| 11 | `design_style_code` | 0=modern, 1=traditional, 2=contemporary, 3=colonial |
| 12 | `customization_level` | 0–5 (count of special feature fields filled) |
| 13 | `design_complexity_score` | 0–18 (floors×2 + customization×2 + topography) |
| 14 | `development_constraint_level` | 0–3 (site access, utilities, soil issues) |
| 15 | `kerala_district_code` | 0–13 (14 Kerala districts) |
| 16 | `construction_start_month` | 1–12 |
| 17 | `monsoon_exposure_score` | 0–1 (SW monsoon ×1.5, NE monsoon ×0.8, normalized) |
| 18 | `district_risk_tier` | 0=low, 1=moderate, 2=high (derived from climate profile) |
| 19 | `terrain_code` | 0=flat, 1=midland, 2=coastal, 3=backwater, 4=hilly, 5=highland |
| 20 | `rainfall_code` | 0=low, 1=moderate, 2=high, 3=very_high |
| 21 | `flood_risk_code` | 0=low, 1=moderate, 2=high |
| 22 | `effective_monsoon_score` | monsoon_exposure × rainfall multiplier (0.4–1.3) |

### Time Delay Dataset — 17 Features

| # | Feature | Description |
|---|---|---|
| 1 | `plot_size_sqft` | Total plot area (sq ft) |
| 2 | `building_size_sqft` | Built-up area (sq ft) |
| 3 | `num_floors` | Number of floors |
| 4 | `planned_duration_months` | Homeowner's planned construction duration |
| 5 | `plot_shape_code` | 0=rectangular, 1=square, 2=irregular, 3=l_shaped |
| 6 | `topography_code` | 0=flat, 1=gentle_slope, 2=steep_slope, 3=hilly |
| 7 | `design_complexity_score` | Overall design complexity (0–18) |
| 8 | `customization_level` | 0–5 |
| 9 | `site_difficulty_score` | 0–10 (topography×2 + constraints + remote flag) |
| 10 | `kerala_district_code` | 0–13 |
| 11 | `construction_start_month` | 1–12 |
| 12 | `monsoon_exposure_score` | 0–1 |
| 13 | `district_risk_tier` | 0–2 (derived from district climate profile) |
| 14 | `terrain_code` | 0=flat … 5=highland |
| 15 | `rainfall_code` | 0=low … 3=very_high |
| 16 | `flood_risk_code` | 0=low, 1=moderate, 2=high |
| 17 | `effective_monsoon_score` | monsoon_exposure × rainfall multiplier |

### Target Variable — Label Distribution

| Label | Cost Overrun | Time Delay |
|---|---|---|
| 0 — Low | 93 (2.3%) | 139 (3.5%) |
| 1 — Medium | 1,457 (36.4%) | 1,194 (29.9%) |
| 2 — High | 2,450 (61.3%) | 2,667 (66.7%) |

The dominance of High-risk labels reflects real Kerala construction patterns where budget and timeline overruns are common. Model selection prioritized recall for the High-risk class.

### Key Derived Features Explained

**monsoon_exposure_score** — computed from construction start month over a 6-month window:
```
SW monsoon (Jun–Sep): each month contributes 1.5
NE monsoon (Oct–Nov): each month contributes 0.8
Score = min(1.0, raw_sum / 9.0)
```

**effective_monsoon_score** — scales monsoon exposure by panchayat rainfall intensity:
```
effective = min(1.0, monsoon_exposure × rainfall_multiplier)
rainfall_multiplier: low=0.4, moderate=0.7, high=1.0, very_high=1.3
```
This captures that a project in Munnar (very_high rainfall) faces far greater monsoon risk than one in Palakkad (low rainfall) even with the same start month.

**district_risk_tier** — derived from the weighted average environmental cost modifier across a district's panchayat climate profiles:
```
avg_env_cost ≥ 4.0 → tier 2 (high)
avg_env_cost ≥ 1.5 → tier 1 (moderate)
else              → tier 0 (low)
```

### Data Preprocessing Steps
1. Categorical encoding: plot shape, topography, design style mapped to integer codes.
2. Derived feature computation: `budget_per_sqft`, `design_complexity_score`, `site_difficulty_score`.
3. Monsoon exposure scoring from construction start month using SW/NE monsoon calendar.
4. Effective monsoon score: `monsoon_exposure × rainfall_multiplier` per panchayat rainfall intensity.
5. Environmental penalty computation: terrain, rainfall, flood risk codes combined into cost/time modifiers.
6. District risk tier derivation from weighted average of district climate profiles.
7. Feature scaling: StandardScaler applied for Logistic Regression baseline only; tree-based models use raw features.
8. Stratified train-test split: 80% training (3,200), 20% testing (800), stratified by risk label.

---

## IV. ML MODELS

### Algorithm Selection

Both models were selected from a 3-model comparison: Logistic Regression (baseline), Random Forest, and Gradient Boosting. Selection criterion: F1-score for the High-risk class (primary) and overall weighted F1 (secondary).

| Task | Selected Model | Reason |
|---|---|---|
| Cost Overrun Risk | Random Forest Classifier | Highest F1 for High-risk class |
| Time Delay Risk | Random Forest Classifier | Highest recall for High-risk class |

### Training Process
1. Load frozen datasets from CSV files (`numpy.random.seed(42)`).
2. Stratified 80/20 train-test split (stratified by risk label).
3. Train all three candidate models per task.
4. Evaluate on F1-score for High-risk class and overall weighted F1.
5. Select best model per task.
6. Serialize with `joblib` to `.pkl` files in `backend/ml/models/`.
7. Extract sklearn feature importance and store in `model_metadata.json`.
8. Compute SHAP global importance using `shap.TreeExplainer` on up to 500 training samples. Mean |SHAP| values per feature stored under `cost_overrun_shap` and `time_delay_shap` in `model_metadata.json`.

### Model Performance

| Model | Task | F1 (High Risk) | Recall (High Risk) | Overall Weighted F1 | Accuracy |
|---|---|---|---|---|---|
| Random Forest | Cost Overrun | 93.8% | 93.9% | 89.7% | 90.1% |
| Random Forest | Time Delay | 94.2% | 94.0% | 90.4% | 90.5% |

### Feature Importance (sklearn Gini)

**Cost Overrun Model**

| Rank | Feature | Importance |
|---|---|---|
| 1 | effective_monsoon_score | 17.4% |
| 2 | design_complexity_score | 10.6% |
| 3 | monsoon_exposure_score | 10.6% |
| 4 | terrain_code | 7.2% |
| 5 | development_constraint_level | 6.8% |
| 6 | num_floors | 6.6% |
| 7 | budget_per_sqft | 6.4% |
| 8 | construction_start_month | 6.3% |

**Time Delay Model**

| Rank | Feature | Importance |
|---|---|---|
| 1 | effective_monsoon_score | 20.9% |
| 2 | monsoon_exposure_score | 11.9% |
| 3 | num_floors | 10.5% |
| 4 | construction_start_month | 8.0% |
| 5 | design_complexity_score | 6.7% |
| 6 | terrain_code | 6.6% |
| 7 | district_risk_tier | 6.3% |
| 8 | planned_duration_months | 6.0% |

### Stored Model Files
```
backend/ml/models/
├── cost_overrun_risk_model.pkl     — Random Forest Classifier (cost)
├── time_delay_risk_model.pkl       — Random Forest Classifier (time)
└── model_metadata.json             — feature order, importance, SHAP global values
```

---

## V. HOW THE SYSTEM WORKS

### Overview

The system uses a **hybrid prediction strategy** that combines four independent scoring dimensions and blends them into a single 0–100 risk score. This prevents unreliable ML predictions from dominating when inputs fall outside the training distribution.

```
Input: homeowner project data (budget, size, location, timeline, start month)
    ↓
Feature Engineering (derived metrics)
    ↓
┌─────────────────────────────────────────────────────┐
│  Dimension 1: Rule-Based Cost Score (0–100)         │
│  Dimension 2: Rule-Based Time Score (0–100)         │
│  Dimension 3: Complexity Score (0–100)              │
│  Dimension 4: ML Model Score (0–100)                │
└─────────────────────────────────────────────────────┘
    ↓
Adaptive Blending (weights depend on ML confidence)
    ↓
Safety Overrides (hard rules for extreme inputs)
    ↓
Risk Level: Low / Medium / High
    ↓
SHAP Explanation (top features driving this prediction)
    ↓
Counterfactual Suggestions (what to change to reduce risk)
```

### Step 1 — Feature Engineering

The raw homeowner inputs are converted into derived metrics:

- `budget_per_sqft` = budget_amount ÷ building_size_sqft
- `expected_duration` = (building_size / 400) + (num_floors × 1.5) months, floored at 3
- `design_complexity_score` = floors×2 + customization×2 + extras (basement, terrace, parking)
- `site_difficulty_score` = topography×2 + constraints×2 + remote_flag
- `monsoon_exposure_score` — from construction start month (SW ×1.5, NE ×0.8)
- `effective_monsoon_score` — monsoon_exposure × panchayat rainfall multiplier
- `district_risk_tier` — from weighted average of district climate profile
- `terrain_code`, `rainfall_code`, `flood_risk_code` — from panchayat climate lookup

### Step 2 — Rule-Based Scoring

**Cost Risk Score (0–100)**

Compares `budget_per_sqft` against Kerala construction benchmarks:

| Budget/sqft | Base Score | Interpretation |
|---|---|---|
| < ₹500 | 90 | Critically underfunded |
| ₹500–₹800 | 75–85 | Well below minimum |
| ₹800–₹1,500 | 40–75 | Below standard |
| ₹1,500–₹2,500 | 15–40 | Acceptable range |
| > ₹2,500 | 15 (base) | Comfortable |

Then adjusted by:
- Environmental penalty: terrain + rainfall + flood risk codes → +0 to +35 points
- Monsoon penalty: `monsoon_score × 12` → up to +12 points

**Time Risk Score (0–100)**

Compares `planned_duration` against `expected_duration`:

| Ratio (planned/expected) | Base Score | Interpretation |
|---|---|---|
| < 0.40 | 90 | Critically short |
| 0.40–0.65 | 72 | Significantly short |
| 0.65–0.85 | 50 | Somewhat tight |
| 0.85–1.20 | 20 | Well aligned |
| > 1.20 | 25 | Generous (scope creep risk) |

Then adjusted by:
- Environmental penalty: terrain + rainfall + flood risk → +0 to +40 points
- Monsoon penalty: `monsoon_score × 20` → up to +20 points

**Complexity Score (0–100)**

```
complexity = floor_score×0.35 + complexity_score×0.30
           + constraint_score×0.20 + site_score×0.15

where:
  floor_score      = min(100, (floors−1) × 20)
  complexity_score = (design_complexity / 15) × 100
  constraint_score = (constraints / 3) × 100
  site_score       = (site_difficulty / 10) × 100
```

### Step 3 — ML Model Score (0–100)

The trained Random Forest returns class probabilities P(Low), P(Medium), P(High). These are converted to a continuous score:

```
ml_score = P(Low)×15 + P(Medium)×50 + P(High)×85
```

### Step 4 — Adaptive Blending

The four scores are blended using weights that depend on ML confidence (max class probability):

| Condition | Cost | Time | Complexity | ML |
|---|---|---|---|---|
| ML confident (max prob ≥ 0.55) | 0.20 | 0.20 | 0.20 | 0.40 |
| ML uncertain (max prob < 0.55) | 0.30 | 0.30 | 0.25 | 0.15 |

```
final_score = cost×w1 + time×w2 + complexity×w3 + ml×w4
```

### Step 5 — Risk Level Mapping

```
final_score ≤ 35  →  Low
final_score 36–65 →  Medium
final_score > 65  →  High
```

### Step 6 — Safety Overrides (Hard Rules)

These fire after blending to catch extreme inputs the model may underweight:

| Condition | Override |
|---|---|
| `budget_per_sqft < ₹500` | Force cost risk → High |
| `budget_per_sqft < ₹800` AND cost risk is Low | Force cost risk → Medium |
| `planned < 40% of expected` | Force time risk → High |
| `planned < 65% of expected` AND time risk is Low | Force time risk → Medium |
| `floors ≥ 3` AND `building_size > 1,500 sqft` AND time risk is Low | Force time risk → Medium |

### Step 7 — SHAP Explanation

For every prediction, `_compute_shap_explanation()` runs `shap.TreeExplainer` on the specific input and returns the top 10 features by |SHAP value| for both cost and time models.

| Model | SHAP Explainer |
|---|---|
| Random Forest (Cost) | `shap.TreeExplainer` |
| Random Forest (Time) | `shap.TreeExplainer` |
| Logistic Regression (baseline only) | `shap.LinearExplainer` |

Each feature entry in the response:
```json
{ "feature": "budget_per_sqft", "value": 1200.0, "shap_value": 0.312 }
```
Positive `shap_value` = pushes toward High risk. Negative = pushes toward Low risk.

The frontend renders these as a horizontal waterfall bar chart (red = risk-increasing, green = risk-reducing) showing the top 6 features per model.

### Step 8 — Counterfactual Suggestions

`_generate_counterfactuals()` computes exact actionable changes:

1. **Budget** — nearest Kerala cost benchmark tier above current budget/sqft, with exact INR amount needed.
2. **Timeline** — gap between planned and expected duration; suggests extending to halfway and full expected.
3. **Start month** — calendar month with lowest monsoon exposure score.

Each suggestion includes the predicted new risk level if the change is made.

### Step 9 — Decision Gate

If both `cost_risk == High` AND `time_risk == High`, the frontend blocks form submission. The homeowner must revise their inputs before proceeding.

---

## VI. SYSTEM ARCHITECTURE

### Components

```
Frontend (React.js)
  └── HomeownerRequestWizard.jsx  — 8-step input wizard
  └── RiskAssessmentPreview.jsx   — calls prediction API, shows results
  └── ProjectHealthPanel.jsx      — health score, SHAP waterfall chart
  └── climateData.js              — panchayat-level climate lookup table

Backend API (PHP 8.2)
  └── predict_construction_risks.php  — receives JSON, calls FastAPI via cURL
  └── PHP Rule-Based Fallback         — full rule engine reimplemented in PHP

ML Service (Python FastAPI)
  └── main.py                     — FastAPI app, /predict endpoint
  └── risk_predictor.py           — ConstructionRiskPredictor class

ML Models
  └── cost_overrun_risk_model.pkl — Random Forest (cost)
  └── time_delay_risk_model.pkl   — Random Forest (time)
  └── model_metadata.json         — feature order, importance, SHAP values
```

### Prediction Request Flow

```
Homeowner fills wizard (8 steps)
    ↓
Step 7: RiskAssessmentPreview becomes visible
    ↓
POST → /backend/api/ml/predict_construction_risks.php
    ↓
PHP cURL → FastAPI localhost:8000/predict
    ↓
ConstructionRiskPredictor.predict_risks(form_data)
    ↓
Feature engineering → Rule scoring → ML inference
    ↓
Adaptive blend → Safety overrides
    ↓
SHAP explanation → Counterfactuals
    ↓
JSON response → PHP → Frontend
    ↓
Display: risk levels, health score, SHAP chart, suggestions
    ↓
If both High → block submission
If not → homeowner proceeds → prediction saved (immutable)
```

### PHP Fallback

The complete rule-based prediction logic is reimplemented in PHP. If the FastAPI service is unavailable, the PHP fallback runs automatically, ensuring predictions are always returned.

---

## VII. LIBRARIES AND TECHNOLOGIES

| Library | Version | Purpose |
|---|---|---|
| scikit-learn | ≥1.0.0 | RandomForestClassifier, LogisticRegression |
| numpy | ≥1.21.0 | Feature array construction, dataset generation |
| pandas | ≥1.3.0 | Dataset loading and manipulation |
| joblib | ≥1.1.0 | Model serialization (.pkl) |
| shap | ≥0.44.0 | Per-instance and global SHAP explanations |
| fastapi | 0.104.1 | REST API framework for ML service |
| uvicorn | 0.24.0 | ASGI server |
| pydantic | ≥2.4.0 | Input validation |
| matplotlib | ≥3.5.0 | Training visualization |
| seaborn | ≥0.11.0 | Statistical plots |

---

## VIII. RESULTS AND DISCUSSION

### Model Performance Summary

| Model | Task | F1 (High Risk) | Recall (High Risk) | Overall F1 |
|---|---|---|---|---|
| Random Forest | Cost Overrun | 93.8% | 93.9% | 89.7% |
| Random Forest | Time Delay | 94.2% | 94.0% | 90.4% |

### Key Findings

1. **Monsoon features dominate both models.** `effective_monsoon_score` is the top feature for both cost (17.4%) and time (20.9%) models. This validates the design decision to encode Kerala's monsoon calendar directly into the feature space rather than treating it as a generic seasonal variable.

2. **Environmental features outperform budget features for cost risk.** `budget_per_sqft` ranks 7th (6.4%) while monsoon and terrain features collectively account for over 35% of cost model importance. This is counterintuitive but reflects that in the Kerala context, environmental conditions are a stronger predictor of cost overrun than the raw budget figure.

3. **Adaptive blending improves stability.** When ML confidence drops below 0.55, the rule-based scores take a larger share (0.30 each vs 0.20). This prevents the model from making confident-looking predictions on inputs far from the training distribution.

4. **Safety overrides catch edge cases.** The hard rules (e.g., `budget_per_sqft < ₹500` → force High) ensure that physically impossible inputs are always flagged, regardless of what the ML model outputs.

### Limitations

- Both datasets are synthetically generated. Real-world validation against actual completed Kerala projects has not been performed.
- Class imbalance: High-risk labels dominate (61.3% cost, 66.7% time). Models may be biased toward predicting High risk.
- No contractor performance features (experience, past overrun history).
- No real-time material price data (steel, cement, sand).
- Dataset is Kerala-specific; generalization to other regions is not validated.
- The hybrid blend weights (ML: 0.40 confident, 0.15 uncertain) were set empirically without systematic optimization.
- No cross-validation or hyperparameter tuning was performed.

---

## IX. FUTURE WORK

- Collect real project outcome data to validate and retrain models on actual Kerala construction results.
- Add contractor performance features (experience years, past overrun history).
- Integrate real-time material price data from market APIs.
- Develop regression models to predict exact overrun percentages rather than 3-level classification.
- Implement automatic model retraining pipeline triggered by new completed project data.
- Add hyperparameter tuning (GridSearchCV / RandomizedSearchCV) for both models.
- Extend SHAP explanations to include force plots and summary plots for batch analysis.
- Extend environmental profiles to other South Indian states.

---

*Generated from code-level analysis of BuildHub ML prediction system.*
*Dataset: v7-env-risk, 4,000 samples per model. Both deployed models: Random Forest Classifier.*
*District risk tier derived from environmental factors (terrain, rainfall, flood) — not hardcoded per district.*
