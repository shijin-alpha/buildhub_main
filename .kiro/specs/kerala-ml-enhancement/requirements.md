# Requirements Document

## Introduction

This feature enhances the BuildHub construction risk prediction ML system with two targeted improvements for Kerala homeowners:

1. **Kerala-only location data**: Replace the all-India location dropdown with a scoped selector covering only Kerala districts, panchayats, and municipalities. The training dataset and risk benchmarks are updated to reflect Kerala-specific construction economics (labor costs, material costs, monsoon-driven patterns, and regional topography).

2. **Construction start month input**: Add a new form field for the planned month of construction start. This feeds into the ML model as a feature because Kerala's monsoon season (June–September) significantly elevates time delay risk and can drive cost overruns through material price spikes, labor unavailability, and site access issues.

Together these changes make the system more accurate and relevant for its primary user base: Kerala homeowners planning residential construction.

## Glossary

- **ML_Service**: The FastAPI-based prediction service at `backend/ml_service/main.py` that loads trained models and serves `/predict` requests.
- **Risk_Predictor**: The hybrid prediction engine at `backend/ml/risk_predictor.py` that blends rule-based scores with ML model probabilities.
- **Dataset_Generator**: The script at `backend/ml/generate_dataset.py` that produces synthetic training data for cost and time delay models.
- **Location_Selector**: The UI component in `HomeownerRequestWizard.jsx` that allows a homeowner to choose their project location.
- **Wizard**: The `HomeownerRequestWizard` React component that collects project inputs and triggers risk assessment.
- **Monsoon_Season**: The period June through September in Kerala, characterised by heavy rainfall that disrupts outdoor construction work.
- **Monsoon_Risk_Factor**: A numeric multiplier (1.0–2.0) applied to time delay risk score when construction start month falls within Monsoon_Season.
- **Start_Month**: The calendar month (1–12) in which the homeowner plans to begin construction.
- **Kerala_District**: One of the 14 official administrative districts of Kerala.
- **Kerala_Location**: A Kerala_District, municipality, or panchayat within Kerala.
- **Cost_Benchmark**: The ₹/sqft threshold used by Risk_Predictor to classify budget adequacy (currently: critical < ₹500, low < ₹800, standard < ₹1500, good ≥ ₹2500).

## Requirements

### Requirement 1: Kerala-Only Location Selector

**User Story:** As a Kerala homeowner, I want to select my project location from a list of Kerala districts, panchayats, and municipalities, so that the system presents only relevant locations and can apply region-specific risk logic.

#### Acceptance Criteria

1. THE Location_Selector SHALL display only Kerala_Locations (all 14 Kerala districts plus their associated panchayats and municipalities).
2. WHEN a homeowner opens the location dropdown, THE Location_Selector SHALL not display districts or cities from any state other than Kerala.
3. WHEN a homeowner selects a Kerala_Location, THE Wizard SHALL store the selected value and pass it to the risk assessment payload.
4. IF a homeowner submits the form without selecting a Kerala_Location, THEN THE Wizard SHALL display a validation error and prevent progression to the risk assessment step.
5. THE Location_Selector SHALL support text-based search so that a homeowner can filter Kerala_Locations by typing partial names.

---

### Requirement 2: Kerala-Specific Training Dataset

**User Story:** As a system operator, I want the ML training dataset to reflect Kerala construction economics, so that the trained models produce accurate risk predictions for Kerala projects.

#### Acceptance Criteria

1. THE Dataset_Generator SHALL generate cost and time delay training rows using Kerala-specific budget benchmarks (₹/sqft ranges calibrated to Kerala labor and material costs).
2. THE Dataset_Generator SHALL include a `start_month` feature column (integer 1–12) in every generated training row.
3. WHEN generating time delay training rows, THE Dataset_Generator SHALL assign a higher `time_delay_risk` label to rows where `start_month` is in {6, 7, 8, 9} (Monsoon_Season), all else being equal.
4. THE Dataset_Generator SHALL produce at least 2000 rows per dataset with a balanced distribution across risk classes (Low, Medium, High).
5. THE Dataset_Generator SHALL include a `district_tier` feature column encoding the construction cost tier of the selected Kerala_District (e.g., Ernakulam/Thiruvananthapuram = tier 1 high-cost, other districts = tier 2 standard).

---

### Requirement 3: Construction Start Month Input Field

**User Story:** As a Kerala homeowner, I want to specify the month I plan to start construction, so that the system can factor in monsoon season risk when predicting delays and cost overruns.

#### Acceptance Criteria

1. THE Wizard SHALL display a "Planned Construction Start Month" dropdown field with options for all 12 calendar months (January through December).
2. WHEN a homeowner selects a Start_Month, THE Wizard SHALL include `start_month` (integer 1–12) in the risk assessment request payload sent to ML_Service.
3. IF a homeowner does not select a Start_Month, THEN THE Wizard SHALL default to the current calendar month and display a notice that the current month has been assumed.
4. THE Wizard SHALL display an informational tooltip on the start month field explaining that monsoon season (June–September) increases construction delay risk in Kerala.

---

### Requirement 4: Monsoon-Aware Time Delay Risk Scoring

**User Story:** As a Kerala homeowner, I want the risk prediction to account for monsoon season when I plan to start construction, so that I receive an accurate time delay risk assessment.

#### Acceptance Criteria

1. WHEN a prediction request includes a `start_month` in {6, 7, 8, 9}, THE Risk_Predictor SHALL apply a Monsoon_Risk_Factor of 1.4 to the computed time delay rule score before blending.
2. WHEN a prediction request includes a `start_month` in {5, 10} (pre- and post-monsoon transition months), THE Risk_Predictor SHALL apply a Monsoon_Risk_Factor of 1.2 to the time delay rule score.
3. WHEN a prediction request includes a `start_month` outside {5, 6, 7, 8, 9, 10}, THE Risk_Predictor SHALL apply a Monsoon_Risk_Factor of 1.0 (no adjustment).
4. WHEN the Monsoon_Risk_Factor is greater than 1.0, THE Risk_Predictor SHALL include a `monsoon_warning` field in the `time_delay_risk` response object with a human-readable explanation.
5. THE Risk_Predictor SHALL accept `start_month` as an optional integer field in the prediction input; WHEN `start_month` is absent, THE Risk_Predictor SHALL treat it as 1.0 Monsoon_Risk_Factor with no warning.
6. WHEN `start_month` is in Monsoon_Season and the blended time delay score exceeds the Medium threshold, THE Risk_Predictor SHALL include a monsoon-specific suggestion in `risk_reduction_suggestions` recommending the homeowner consider starting in a dry month.

---

### Requirement 5: Kerala District Cost Tier Adjustment

**User Story:** As a Kerala homeowner, I want the cost risk assessment to reflect the construction cost differences between Kerala districts, so that my budget is evaluated against the right regional benchmark.

#### Acceptance Criteria

1. WHEN a prediction request includes a `kerala_location` mapped to a Tier 1 district (Ernakulam, Thiruvananthapuram, Thrissur, Kozhikode), THE Risk_Predictor SHALL apply a 15% upward adjustment to the Cost_Benchmark thresholds.
2. WHEN a prediction request includes a `kerala_location` mapped to a Tier 2 district (all other Kerala districts), THE Risk_Predictor SHALL use the standard Cost_Benchmark thresholds without adjustment.
3. WHEN `kerala_location` is absent from the prediction request, THE Risk_Predictor SHALL use the standard Cost_Benchmark thresholds.
4. WHEN a district tier adjustment is applied, THE Risk_Predictor SHALL include a `district_note` field in the `cost_overrun_risk` response object stating the applied tier and adjusted benchmark values.

---

### Requirement 6: ML_Service API Contract Update

**User Story:** As a backend developer, I want the ML_Service prediction endpoint to accept the new `start_month` and `kerala_location` fields, so that the enhanced features are available to all API consumers.

#### Acceptance Criteria

1. THE ML_Service `/predict` endpoint SHALL accept an optional `start_month` integer field (valid range 1–12) in the request body.
2. THE ML_Service `/predict` endpoint SHALL accept an optional `kerala_location` string field in the request body.
3. IF `start_month` is provided and outside the range 1–12, THEN THE ML_Service SHALL return HTTP 422 with a descriptive validation error.
4. THE ML_Service SHALL pass `start_month` and `kerala_location` to Risk_Predictor unchanged.
5. THE ML_Service `/predict` response SHALL include `start_month` and `monsoon_warning` (when applicable) in the returned JSON so that the Wizard can display contextual feedback to the homeowner.

---

### Requirement 7: Risk Assessment UI Feedback for Monsoon and District

**User Story:** As a Kerala homeowner, I want to see clear warnings and explanations in the risk assessment results when monsoon season or my district affects my risk score, so that I can make informed decisions.

#### Acceptance Criteria

1. WHEN the risk assessment response contains a `monsoon_warning`, THE Wizard SHALL display the warning prominently in the time delay risk section of the Risk Assessment Preview.
2. WHEN the risk assessment response contains a `district_note`, THE Wizard SHALL display the note in the cost overrun risk section.
3. WHEN `start_month` is in Monsoon_Season, THE Wizard SHALL highlight the start month field with a visual indicator (e.g., amber border) and display the text "Monsoon season — higher delay risk".
4. THE Wizard SHALL display the selected Kerala_Location and Start_Month in the Review step summary so the homeowner can verify them before submission.
