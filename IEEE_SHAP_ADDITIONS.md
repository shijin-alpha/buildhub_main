# SHAP Integration — New Additions to IEEE Research Paper

This file contains only the new/changed content added to `IEEE_RESEARCH_PAPER_REPORT.md` as a result of the SHAP integration.

---

## SECTION I — New Contribution #6

**Added to Key Innovation / Contribution list:**

> 6. Provides per-instance SHAP (SHapley Additive exPlanations) values for every prediction, surfacing the top feature drivers in a visual waterfall chart so homeowners understand exactly why a risk level was assigned.

---

## SECTION II-A — ProjectHealthPanel Update

**Changed description of ProjectHealthPanel.jsx:**

> Displays sparkline trend, estimated financial impact (extra INR, extra weeks), location climate profile card, top risk factors, an interactive action checklist, and a **SHAP waterfall chart** (`ShapWaterfall` component) showing the top 6 feature drivers for both cost and time models. Red bars indicate features that increase risk; green bars indicate features that reduce risk.

---

## SECTION II-D — Model Metadata Update

**Changed model_metadata.json description:**

> `model_metadata.json` — Feature column order, sklearn feature importance values, and **SHAP global importance** (mean |SHAP| per feature, stored under `cost_overrun_shap` and `time_delay_shap` keys)

---

## SECTION II-E — Interaction Flow Update

**Added two new steps to the flow diagram:**

```
...
Safety overrides for extreme inputs
    ↓
Counterfactual suggestions generated
    ↓
SHAP per-instance explanation computed (TreeExplainer / LinearExplainer)   ← NEW
    ↓
JSON response → PHP → Frontend
    ↓
RiskAssessmentPreview displays results + SHAP waterfall chart               ← UPDATED
...
```

---

## SECTION IV — Training Process Update

**Added step 8 to Training Process:**

> 8. Compute **SHAP global importance** using `shap.TreeExplainer` (Random Forest, Gradient Boosting) or `shap.LinearExplainer` (Logistic Regression) on up to 500 training samples. Mean |SHAP| values per feature are stored in `model_metadata.json` under `cost_overrun_shap` and `time_delay_shap`.

---

## SECTION VI — Explainable AI (Full Replacement)

**Previous:** 2-layer system (Feature Importance + Rule-Based Reason Strings). No SHAP.

**New:** 3-layer system:

### Layer 1 — SHAP Global Importance (Training-Time)

After training, `shap.TreeExplainer` (for Gradient Boosting and Random Forest) or `shap.LinearExplainer` (for Logistic Regression) is applied to up to 500 training samples. Mean absolute SHAP values per feature are computed and stored in `model_metadata.json` under `cost_overrun_shap` and `time_delay_shap`. For multi-class models, SHAP values are averaged across all class indices. This provides a theoretically grounded global feature ranking that is consistent across model types, unlike Gini importance which can be biased toward high-cardinality features.

### Layer 2 — SHAP Per-Instance Explanation (Inference-Time)

For every live prediction, `_compute_shap_explanation()` in `ConstructionRiskPredictor` computes SHAP values for the specific input:
- `shap.TreeExplainer` is used for Gradient Boosting and Random Forest models.
- `shap.LinearExplainer` is used if Logistic Regression is selected.
- For multi-class output, SHAP values for the predicted class are extracted.
- The top 10 features by |SHAP value| are returned, each with their raw input value and signed SHAP contribution.
- Returned in the API response under `shap_explanation.cost` and `shap_explanation.time`.

### Layer 3 — Rule-Based Reason Strings (Inference-Time) *(unchanged)*

### SHAP Explainer Selection Logic (New Table)

| Model Type | SHAP Explainer | Rationale |
|---|---|---|
| GradientBoostingClassifier | `TreeExplainer` | Exact SHAP values via tree path enumeration |
| RandomForestClassifier | `TreeExplainer` | Same — ensemble of decision trees |
| LogisticRegression | `LinearExplainer` | Exact SHAP for linear models |

### SHAP Waterfall Visualization — `ShapWaterfall` Component (New)

The `ProjectHealthPanel.jsx` renders a horizontal bar chart for both cost and time models:
- Each bar represents one feature's SHAP contribution to the prediction.
- Bar width is proportional to |SHAP value| relative to the maximum in the set.
- Red bars (positive SHAP) indicate features pushing risk higher.
- Green bars (negative SHAP) indicate features pushing risk lower.
- Feature names are mapped to human-readable labels (e.g., `budget_per_sqft` → "Budget/sqft").
- Top 6 features are shown per model.
- Section labeled "Why this prediction? (SHAP)" — only renders when `shap_explanation` data is present.

---

## SECTION VII — Workflow Updates

**Step 6 — added to hybrid prediction execution list:**

> - `_compute_shap_explanation()` — per-instance SHAP values for cost and time models (top 10 features each)

**Step 7 — updated response fields:**

> JSON with `cost_overrun_risk`, `time_delay_risk`, `complexity_risk`, `final_risk`, `risk_score`, `derived_metrics`, `risk_reduction_suggestions`, and **`shap_explanation: { cost: [...], time: [...] }`**

**Step 8 — updated display description:**

> `ProjectHealthPanel` renders health score, risk badges, explanations, climate profile, counterfactual suggestions, and the **SHAP waterfall chart** showing the top 6 feature drivers for cost and time models.

---

## SECTION VIII — Libraries Table Update

**Added row:**

| Library | Version | Purpose |
|---|---|---|
| shap | ≥0.44.0 | Per-instance and global SHAP explanations (TreeExplainer, LinearExplainer) |

---

## SECTION X — New Novelty Contribution #3

**Inserted as new item #3 (old items 3/4/5 renumbered to 4/5/6):**

### 3. SHAP-Based Explainability at Two Levels

The system integrates SHAP (SHapley Additive exPlanations) at both training time and inference time. At training time, `TreeExplainer` computes global mean |SHAP| importance across 500 training samples, providing a theoretically grounded feature ranking stored in `model_metadata.json`. At inference time, `_compute_shap_explanation()` computes per-instance SHAP values for each prediction, identifying the exact features driving that specific homeowner's risk score. These are rendered as a waterfall bar chart in the UI — a level of transparency not found in existing construction risk tools.

---

## SECTION XII — Future Work Update

**Replaced (Short-Term):**

> ~~Implement SHAP values for more rigorous feature attribution.~~

**With:**

> Extend SHAP explanations to include force plots and summary plots for batch analysis.

---

## API Response Schema Change

The `/predict` endpoint now returns an additional top-level field:

```json
{
  "shap_explanation": {
    "cost": [
      { "feature": "budget_per_sqft", "value": 1200.0, "shap_value": 0.312 },
      { "feature": "design_complexity_score", "value": 8, "shap_value": 0.187 },
      ...
    ],
    "time": [
      { "feature": "num_floors", "value": 3, "shap_value": 0.421 },
      { "feature": "site_difficulty_score", "value": 6, "shap_value": 0.198 },
      ...
    ]
  }
}
```

Each array contains up to 10 entries, sorted by `|shap_value|` descending. Positive `shap_value` = pushes toward High risk. Negative = pushes toward Low risk.

---

## Files Changed

| File | Change |
|---|---|
| `backend/ml/requirements.txt` | Added `shap>=0.44.0` |
| `backend/ml/risk_prediction_pipeline.py` | Added SHAP import, `_compute_shap_importance()` method, called after training |
| `backend/ml/risk_predictor.py` | Added SHAP import, `_compute_shap_explanation()` method, called in `_predict_risks_core()` |
| `backend/ml_service/main.py` | Added `location`, `climate_modifiers`, `planned_duration_months` to `PredictionRequest` |
| `frontend/src/components/ProjectHealthPanel.jsx` | Added `ShapWaterfall` component, `shapExplanation` prop, SHAP section in panel JSX |
| `frontend/src/components/RiskAssessmentPreview.jsx` | Passes `shapExplanation={riskAssessment.shap_explanation}` to `ProjectHealthPanel` |
| `frontend/src/components/ProjectHealthWidget.jsx` | Passes `shapExplanation={data.shap_explanation}` to `ProjectHealthPanel` |
