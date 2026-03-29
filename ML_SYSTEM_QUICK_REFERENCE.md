# ML Prediction System - Quick Reference

## System Components

| Component | File | Purpose |
|-----------|------|---------|
| Frontend UI | `frontend/src/components/RiskAssessmentPreview.jsx` | Display risk assessment modal |
| PHP Bridge | `backend/api/ml/predict_construction_risks.php` | Validate & forward requests |
| FastAPI Service | `backend/ml_service/main.py` | Persistent ML service (port 8000) |
| ML Engine | `backend/ml/risk_predictor.py` | Feature engineering & prediction |
| Cost Model | `backend/ml/models/cost_overrun_risk_model.pkl` | Gradient Boosting classifier |
| Time Model | `backend/ml/models/time_delay_risk_model.pkl` | Random Forest classifier |
| Metadata | `backend/ml/models/model_metadata.json` | Features & performance metrics |

## Request Flow

```
User Form → RiskAssessmentPreview.jsx → predict_construction_risks.php 
→ FastAPI:8000/predict → risk_predictor.py → Models → Response
```

## Input Requirements

**Required Fields (6):**
- plot_size_sqft (float > 0)
- building_size_sqft (float > 0)
- num_floors (int ≥ 1)
- budget_amount (float > 0)
- num_bedrooms (int ≥ 1)
- num_bathrooms (int ≥ 1)

**Optional Fields:**
- plot_shape, topography, design_style
- basement, terrace, parking (boolean)
- site_access_difficult, soil_issues, remote_location (boolean)

## Feature Engineering

**Cost Model (14 features):**
- Raw: plot_size, building_size, floors, budget, bedrooms, bathrooms
- Calculated: budget_per_sqft, total_rooms, design_complexity_score
- Encoded: plot_shape_code, topography_code, design_style_code
- Derived: customization_level, development_constraint_level

**Time Model (9 features):**
- Raw: plot_size, building_size, floors
- Calculated: planned_duration_months, site_difficulty_score
- Encoded: plot_shape_code, topography_code
- Derived: design_complexity_score, customization_level

## ML Models

### Cost Overrun Model
- Algorithm: Gradient Boosting
- Accuracy: 94.7% F1-score (High Risk)
- Top Factor: Design Complexity (46.2%)

### Time Delay Model
- Algorithm: Random Forest
- Accuracy: 98.9% F1-score (High Risk)
- Top Factor: Number of Floors (49.5%)

## Risk Classification

| Risk Level | Probability Range |
|------------|-------------------|
| Low | < 40% |
| Medium | 40% - 70% |
| High | ≥ 70% |

**Blocking Rule:** Submission blocked if BOTH cost AND time are HIGH

## Output Format

```json
{
  "success": true,
  "cost_overrun_risk": {
    "risk_level": "High",
    "probability": 0.955,
    "probabilities": {"Low": 0.02, "Medium": 0.025, "High": 0.955},
    "explanation": ["Factor 1...", "Factor 2...", "Factor 3..."]
  },
  "time_delay_risk": {
    "risk_level": "Low",
    "probability": 0.152,
    "probabilities": {"Low": 0.848, "Medium": 0.0, "High": 0.152},
    "explanation": ["Factor 1...", "Factor 2...", "Factor 3..."]
  }
}
```

## Database Storage

**Tables:**
- contractor_send_estimates (predictions with estimate)
- construction_projects (predictions copied when project created)

**Columns:**
- predicted_cost_risk_level (ENUM)
- predicted_cost_probability (DECIMAL)
- predicted_time_risk_level (ENUM)
- predicted_time_probability (DECIMAL)
- prediction_generated_at (TIMESTAMP)
- model_version (VARCHAR)

## Service Management

### Start Service
```bash
cd backend/ml_service
python main.py
```

### Check Health
```bash
curl http://localhost:8000/health
```

### Test Prediction
```bash
curl -X POST http://localhost:8000/predict \
  -H "Content-Type: application/json" \
  -d '{"plot_size_sqft":2500,"building_size_sqft":2000,"num_floors":2,"budget_amount":2500000,"num_bedrooms":3,"num_bathrooms":2}'
```

## Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| "ML service connection failed" | Service not running | Start FastAPI service |
| "Models not loaded" | Missing model files | Check backend/ml/models/ |
| "Invalid form data" | Missing required fields | Provide all 6 required fields |
| Predictions not saving | Missing DB columns | Run apply_prediction_columns_fix.php |
| Slow predictions | Using exec() instead of FastAPI | Ensure FastAPI service is running |

## Performance Metrics

| Metric | Value |
|--------|-------|
| Response Time | 50-100ms |
| Model Loading | Once at startup |
| Concurrent Requests | Supported |
| Accuracy (Cost) | 94.7% |
| Accuracy (Time) | 98.9% |

## Key Functions

### Frontend
```javascript
// RiskAssessmentPreview.jsx
performRiskAssessment() // Calls PHP API
savePredictionToDatabase() // Stores in DB
```

### PHP
```php
// predict_construction_risks.php
// Validates input, forwards to FastAPI
```

### FastAPI
```python
# main.py
@app.post("/predict")
async def predict_risks(request: PredictionRequest)
```

### ML Engine
```python
# risk_predictor.py
class ConstructionRiskPredictor:
    def predict_risks(form_data: Dict) -> Dict
    def convert_form_to_features(form_data: Dict)
```

## Model Retraining

```bash
cd backend/ml
python run_training.py
```

Output: New models in backend/ml/models/

## Documentation Files

- `ML_SYSTEM_TECHNICAL_GUIDE.md` - Complete technical documentation
- `ML_PREDICTION_DETAILED_FLOW.md` - Step-by-step workflow with timing
- `ML_SYSTEM_QUICK_REFERENCE.md` - This file (quick lookup)
- `AI_PREDICTION_STORAGE_ANALYSIS.md` - Database storage analysis
- `FASTAPI_MIGRATION_COMPLETE.md` - Migration from exec() to FastAPI

## Architecture Diagram

```
┌─────────────┐
│   Browser   │ User fills form
└──────┬──────┘
       │ HTTP POST
       ↓
┌─────────────────────────┐
│ RiskAssessmentPreview   │ React component
│ .jsx                    │
└──────┬──────────────────┘
       │ fetch()
       ↓
┌─────────────────────────┐
│ predict_construction    │ PHP API
│ _risks.php              │
└──────┬──────────────────┘
       │ cURL
       ↓
┌─────────────────────────┐
│ FastAPI Service         │ Port 8000
│ main.py                 │
└──────┬──────────────────┘
       │ predict_risks()
       ↓
┌─────────────────────────┐
│ ConstructionRisk        │ ML Engine
│ Predictor               │
└──────┬──────────────────┘
       │ load models
       ↓
┌─────────────────────────┐
│ Trained Models          │ .pkl files
│ - cost_overrun_risk     │
│ - time_delay_risk       │
└─────────────────────────┘
```

## Next Steps

1. Ensure FastAPI service is running
2. Test with sample request
3. Verify predictions display correctly
4. Check database storage
5. Monitor performance metrics
