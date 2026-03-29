# ML Prediction System - Detailed Flow Diagram

## Complete Request-Response Cycle

```
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 1: USER ACTION                                                       │
│ Location: Homeowner Dashboard                                             │
│ Component: HomeownerRequestWizard.jsx                                     │
├──────────────────────────────────────────────────────────────────────────┤
│ User fills form:                                                          │
│ • Plot size: 2500 sqft                                                    │
│ • Building size: 2000 sqft                                                │
│ • Floors: 2                                                               │
│ • Budget: ₹25,00,000                                                      │
│ • Bedrooms: 3                                                             │
│ • Bathrooms: 2                                                            │
│ • Optional: plot_shape, topography, design_style, etc.                   │
│                                                                            │
│ User clicks "Submit" → Risk Assessment Modal Opens                        │
└────────────────────────────┬───────────────────────────────────────────┘
                             │
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 2: FRONTEND INITIATES PREDICTION                                     │
│ Component: RiskAssessmentPreview.jsx                                      │
│ Function: performRiskAssessment()                                         │
├──────────────────────────────────────────────────────────────────────────┤
│ JavaScript Code:                                                          │
│ ```javascript                                                             │
│ const response = await fetch(                                             │
│   '/buildhub/backend/api/ml/predict_construction_risks.php',             │
│   {                                                                        │
│     method: 'POST',                                                       │
│     headers: {'Content-Type': 'application/json'},                        │
│     body: JSON.stringify(formData)                                        │
│   }                                                                        │
│ );                                                                         │
│ ```                                                                        │
│                                                                            │
│ Request Body (JSON):                                                      │
│ {                                                                          │
│   "plot_size_sqft": 2500,                                                 │
│   "building_size_sqft": 2000,                                             │
│   "num_floors": 2,                                                        │
│   "budget_amount": 2500000,                                               │
│   "num_bedrooms": 3,                                                      │
│   "num_bathrooms": 2,                                                     │
│   "plot_shape": "rectangular",                                            │
│   "topography": "flat",                                                   │
│   "design_style": "modern"                                                │
│ }                                                                          │
└────────────────────────────┬───────────────────────────────────────────┘
                             │ HTTP POST
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 3: PHP API RECEIVES REQUEST                                          │
│ File: backend/api/ml/predict_construction_risks.php                       │
├──────────────────────────────────────────────────────────────────────────┤
│ 1. Validate HTTP method (must be POST)                                    │
│ 2. Parse JSON input                                                       │
│ 3. Validate required fields:                                              │
│    - plot_size_sqft (numeric, > 0)                                        │
│    - building_size_sqft (numeric, > 0)                                    │
│    - num_floors (numeric, >= 1)                                           │
│    - budget_amount (numeric, > 0)                                         │
│    - num_bedrooms (numeric, >= 1)                                         │
│    - num_bathrooms (numeric, >= 1)                                        │
│                                                                            │
│ 4. Prepare cURL request to FastAPI service                                │
│    URL: http://localhost:8000/predict                                     │
│    Method: POST                                                           │
│    Headers: Content-Type: application/json                                │
│    Body: Same JSON from frontend                                          │
│    Timeout: 10 seconds                                                    │
└────────────────────────────┬───────────────────────────────────────────┘
                             │ HTTP POST to localhost:8000
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 4: FASTAPI SERVICE RECEIVES REQUEST                                  │
│ File: backend/ml_service/main.py                                          │
│ Endpoint: POST /predict                                                   │
│ Function: predict_risks(request: PredictionRequest)                       │
├──────────────────────────────────────────────────────────────────────────┤
│ 1. Validate request using Pydantic model                                  │
│    - Automatic type checking                                              │
│    - Range validation (e.g., plot_size > 0)                               │
│                                                                            │
│ 2. Check if models are loaded                                             │
│    if predictor is None or not predictor.is_loaded:                       │
│        return 503 error                                                   │
│                                                                            │
│ 3. Convert request to dict                                                │
│    form_data = request.dict()                                             │
│                                                                            │
│ 4. Call prediction engine                                                 │
│    result = predictor.predict_risks(form_data)                            │
│                                                                            │
│ 5. Return result as JSON                                                  │
└────────────────────────────┬───────────────────────────────────────────┘
                             │ Calls predict_risks()
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 5: ML PREDICTION ENGINE - FEATURE ENGINEERING                        │
│ File: backend/ml/risk_predictor.py                                        │
│ Class: ConstructionRiskPredictor                                          │
│ Method: convert_form_to_features(form_data)                               │
├──────────────────────────────────────────────────────────────────────────┤
│ A. Extract Raw Features:                                                  │
│    plot_size = 2500                                                       │
│    building_size = 2000                                                   │
│    num_floors = 2                                                         │
│    budget_amount = 2500000                                                │
│    num_bedrooms = 3                                                       │
│    num_bathrooms = 2                                                      │
│                                                                            │
│ B. Calculate Derived Features:                                            │
│    budget_per_sqft = 2500000 / 2000 = 1250                                │
│    total_rooms = 3 + 2 + 2 = 7 (bedrooms + bathrooms + kitchen + living) │
│                                                                            │
│ C. Encode Categorical Features:                                           │
│    plot_shape_code = 0 (rectangular)                                      │
│    topography_code = 0 (flat)                                             │
│    design_style_code = 0 (modern)                                         │
│                                                                            │
│ D. Calculate Complexity Scores:                                           │
│    customization_level = count of special features (0-4)                  │
│    design_complexity_score = floors*2 + customization*2 + features (0-15) │
│    development_constraint_level = site challenges (0-3)                   │
│                                                                            │
│ E. Calculate Time-Specific Features:                                      │
│    planned_duration_months = building_size/100 + floors*2 + custom        │
│    site_difficulty_score = topography*2 + constraints*2 + remote (0-10)   │
│                                                                            │
│ F. Create Feature Arrays:                                                 │
│    Cost Features (14): [plot_size, building_size, num_floors,            │
│                         budget_amount, budget_per_sqft, plot_shape_code,  │
│                         topography_code, num_bedrooms, num_bathrooms,     │
│                         total_rooms, design_style_code,                   │
│                         customization_level, design_complexity_score,     │
│                         development_constraint_level]                     │
│                                                                            │
│    Time Features (9): [plot_size, building_size, num_floors,             │
│                        planned_duration_months, plot_shape_code,          │
│                        topography_code, design_complexity_score,          │
│                        customization_level, site_difficulty_score]        │
└────────────────────────────┬───────────────────────────────────────────┘
                             │ Returns feature arrays
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 6: MODEL INFERENCE                                                   │
│ Method: predict_risks(form_data)                                          │
├──────────────────────────────────────────────────────────────────────────┤
│ A. Cost Overrun Prediction:                                               │
│    1. Load cost_overrun_risk_model.pkl (Gradient Boosting)                │
│    2. Apply scaler if exists (standardization)                            │
│    3. Predict class: model.predict(cost_features)                         │
│       → Returns: 2 (High Risk)                                            │
│    4. Get probabilities: model.predict_proba(cost_features)               │
│       → Returns: [0.02, 0.025, 0.955]                                     │
│       → Low: 2%, Medium: 2.5%, High: 95.5%                                │
│                                                                            │
│ B. Time Delay Prediction:                                                 │
│    1. Load time_delay_risk_model.pkl (Random Forest)                      │
│    2. Apply scaler if exists                                              │
│    3. Predict class: model.predict(time_features)                         │
│       → Returns: 0 (Low Risk)                                             │
│    4. Get probabilities: model.predict_proba(time_features)               │
│       → Returns: [0.848, 0.0, 0.152]                                      │
│       → Low: 84.8%, Medium: 0%, High: 15.2%                               │
│                                                                            │
│ C. Map Predictions to Risk Levels:                                        │
│    risk_levels = {0: 'Low', 1: 'Medium', 2: 'High'}                       │
│    cost_risk_level = 'High'                                               │
│    time_risk_level = 'Low'                                                │
└────────────────────────────┬───────────────────────────────────────────┘
                             │
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 7: EXPLANATION GENERATION                                            │
│ Method: _generate_explanation()                                           │
├──────────────────────────────────────────────────────────────────────────┤
│ A. Get Feature Importance from metadata.json:                             │
│    Cost Top 3:                                                            │
│    1. design_complexity_score: 46.2%                                      │
│    2. budget_per_sqft: 33.2%                                              │
│    3. budget_amount: 6.3%                                                 │
│                                                                            │
│    Time Top 3:                                                            │
│    1. num_floors: 49.5%                                                   │
│    2. site_difficulty_score: 19.8%                                        │
│    3. planned_duration_months: 9.7%                                       │
│                                                                            │
│ B. Generate Human-Readable Explanations:                                  │
│    Cost:                                                                  │
│    - "Design complexity score of 8 is a key factor in cost overrun risk"  │
│    - "Budget per sq.ft of ₹1250 significantly influences cost risk"      │
│    - "Budget amount impacts overall risk"                                 │
│                                                                            │
│    Time:                                                                  │
│    - "Number of floors (2) contributes to time delay risk"                │
│    - "Site difficulty score of 2 impacts time delay risk"                 │
│    - "Planned duration of 10 months affects time delay probability"       │
└────────────────────────────┬───────────────────────────────────────────┘
                             │ Returns complete result
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 8: RESPONSE FORMATTING                                               │
│ Returns to FastAPI → PHP → Frontend                                       │
├──────────────────────────────────────────────────────────────────────────┤
│ Final JSON Response:                                                      │
│ {                                                                          │
│   "success": true,                                                        │
│   "cost_overrun_risk": {                                                  │
│     "prediction": 2,                                                      │
│     "risk_level": "High",                                                 │
│     "probabilities": {                                                    │
│       "Low": 0.02,                                                        │
│       "Medium": 0.025,                                                    │
│       "High": 0.955                                                       │
│     },                                                                     │
│     "explanation": [                                                      │
│       "Design complexity score of 8 is a key factor...",                  │
│       "Budget per sq.ft of ₹1250 significantly influences...",            │
│       "Budget amount impacts overall risk"                                │
│     ]                                                                      │
│   },                                                                       │
│   "time_delay_risk": {                                                    │
│     "prediction": 0,                                                      │
│     "risk_level": "Low",                                                  │
│     "probabilities": {                                                    │
│       "Low": 0.848,                                                       │
│       "Medium": 0.0,                                                      │
│       "High": 0.152                                                       │
│     },                                                                     │
│     "explanation": [                                                      │
│       "Number of floors (2) contributes to time delay risk",              │
│       "Site difficulty score of 2 impacts time delay risk",               │
│       "Planned duration of 10 months affects time delay probability"      │
│     ]                                                                      │
│   }                                                                        │
│ }                                                                          │
└────────────────────────────┬───────────────────────────────────────────┘
                             │ Returns to frontend
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 9: FRONTEND DISPLAYS RESULTS                                         │
│ Component: RiskAssessmentPreview.jsx                                      │
├──────────────────────────────────────────────────────────────────────────┤
│ A. Parse Response:                                                        │
│    setRiskAssessment(result.data)                                         │
│                                                                            │
│ B. Display Risk Cards:                                                    │
│    ┌─────────────────────────────────────┐                                │
│    │ 💰 Budget Risk                      │                                │
│    │ 🔴 HIGH (95.5% probability)         │                                │
│    │                                     │                                │
│    │ Important: Your project may cost    │                                │
│    │ more than planned. Consider         │                                │
│    │ increasing budget by 15-20%.        │                                │
│    │                                     │                                │
│    │ Why this matters:                   │                                │
│    │ • Design complexity score of 8...   │                                │
│    │ • Budget per sq.ft of ₹1250...      │                                │
│    └─────────────────────────────────────┘                                │
│                                                                            │
│    ┌─────────────────────────────────────┐                                │
│    │ ⏰ Timeline Risk                    │                                │
│    │ 🟢 LOW (15.2% probability)          │                                │
│    │                                     │                                │
│    │ Excellent! Your project should      │                                │
│    │ complete on time as planned.        │                                │
│    │                                     │                                │
│    │ Why this matters:                   │                                │
│    │ • Number of floors (2)...           │                                │
│    │ • Site difficulty score of 2...     │                                │
│    └─────────────────────────────────────┘                                │
│                                                                            │
│ C. Check Blocking Condition:                                              │
│    if (cost_risk === 'High' && time_risk === 'High') {                    │
│      // Block submission, show warning                                    │
│      // User must revise project details                                  │
│    }                                                                       │
└────────────────────────────┬───────────────────────────────────────────┘
                             │ If estimate_id exists
                             ↓
┌──────────────────────────────────────────────────────────────────────────┐
│ STEP 10: SAVE PREDICTION TO DATABASE                                      │
│ Function: savePredictionToDatabase()                                      │
│ API: backend/api/ml/save_estimate_prediction.php                          │
├──────────────────────────────────────────────────────────────────────────┤
│ SQL UPDATE:                                                               │
│ UPDATE contractor_send_estimates                                          │
│ SET predicted_cost_risk_level = 'High',                                   │
│     predicted_cost_probability = 0.955,                                   │
│     predicted_time_risk_level = 'Low',                                    │
│     predicted_time_probability = 0.152,                                   │
│     prediction_generated_at = NOW(),                                      │
│     model_version = 'v1.0.0'                                              │
│ WHERE id = estimate_id;                                                   │
│                                                                            │
│ Result: Predictions stored in database for future evaluation              │
└────────────────────────────────────────────────────────────────────────┘

## Timing Breakdown

| Step | Component | Time |
|------|-----------|------|
| 1-2 | Frontend | ~10ms |
| 3 | PHP API | ~5ms |
| 4 | FastAPI | ~2ms |
| 5 | Feature Engineering | ~3ms |
| 6 | Model Inference | ~30ms |
| 7 | Explanation | ~5ms |
| 8-9 | Response | ~10ms |
| 10 | Database Save | ~15ms |
| **Total** | **End-to-End** | **~80ms** |

## Error Handling

Each step includes error handling:
- Frontend: Try-catch with user-friendly messages
- PHP: Validates input, checks service availability
- FastAPI: Pydantic validation, model loading checks
- ML Engine: Feature validation, model error handling
- Database: Transaction rollback on failure

## Performance Optimization

1. **Model Loading:** Once at startup (not per request)
2. **Feature Caching:** Metadata loaded once
3. **Connection Pooling:** FastAPI handles concurrent requests
4. **Response Compression:** JSON minification
5. **Timeout Management:** 10s max per request
