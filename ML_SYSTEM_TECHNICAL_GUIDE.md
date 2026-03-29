# ML Prediction System - Technical Guide

## Quick Overview

**Purpose:** Real-time risk prediction for construction projects  
**Models:** Gradient Boosting (cost) + Random Forest (time)  
**Accuracy:** 94.7% cost | 98.9% time  
**Response Time:** 50-100ms  

## Architecture

```
User Form → RiskAssessmentPreview.jsx → predict_construction_risks.php 
→ FastAPI (localhost:8000) → risk_predictor.py → Trained Models → Response
```

## Key Files

### 1. Frontend
- `frontend/src/components/RiskAssessmentPreview.jsx` - UI component
- Calls: `/buildhub/backend/api/ml/predict_construction_risks.php`

### 2. PHP Bridge
- `backend/api/ml/predict_construction_risks.php`
- Validates input, forwards to FastAPI service

### 3. FastAPI Service
- `backend/ml_service/main.py` - Persistent service on port 8000
- Endpoint: `POST /predict`
- Models loaded once at startup

### 4. ML Engine
- `backend/ml/risk_predictor.py` - Core prediction logic
- Class: `ConstructionRiskPredictor`
- Method: `predict_risks(form_data)`

### 5. Trained Models
- `backend/ml/models/cost_overrun_risk_model.pkl` - Gradient Boosting
- `backend/ml/models/time_delay_risk_model.pkl` - Random Forest
- `backend/ml/models/model_metadata.json` - Features & metrics

## Data Flow

### Input (6 required fields)
```json
{
  "plot_size_sqft": 2500,
  "building_size_sqft": 2000,
  "num_floors": 2,
  "budget_amount": 2500000,
  "num_bedrooms": 3,
  "num_bathrooms": 2
}
```

### Feature Engineering
**Cost Model (14 features):**
- plot_size_sqft, building_size_sqft, num_floors
- budget_amount, budget_per_sqft (calculated)
- total_rooms (calculated)
- design_complexity_score (calculated)
- plot_shape_code, topography_code, design_style_code
- customization_level, development_constraint_level

**Time Model (9 features):**
- plot_size_sqft, building_size_sqft, num_floors
- planned_duration_months (calculated)
- plot_shape_code, topography_code
- design_complexity_score, customization_level
- site_difficulty_score (calculated)

### Output
```json
{
  "success": true,
  "cost_overrun_risk": {
    "risk_level": "High",
    "probability": 0.955,
    "probabilities": {"Low": 0.02, "Medium": 0.025, "High": 0.955},
    "explanation": ["Design complexity score of 12...", "..."]
  },
  "time_delay_risk": {
    "risk_level": "Low",
    "probability": 0.152,
    "probabilities": {"Low": 0.848, "Medium": 0.0, "High": 0.152},
    "explanation": ["Number of floors (2)...", "..."]
  }
}
```

## ML Models

### Cost Overrun Model
- **Algorithm:** Gradient Boosting Classifier
- **Training Data:** 1000 synthetic projects
- **Features:** 14 project characteristics
- **Performance:** F1-score 94.7% (High Risk)
- **Top Factors:**
  1. Design Complexity (46.2%)
  2. Budget Per Sq.Ft (33.2%)
  3. Budget Amount (6.3%)

### Time Delay Model
- **Algorithm:** Random Forest Classifier
- **Training Data:** 1000 synthetic projects
- **Features:** 9 project characteristics
- **Performance:** F1-score 98.9% (High Risk)
- **Top Factors:**
  1. Number of Floors (49.5%)
  2. Site Difficulty (19.8%)
  3. Planned Duration (9.7%)

## Risk Level Classification

**Thresholds:**
- Low: probability < 0.40 (40%)
- Medium: 0.40 ≤ probability < 0.70
- High: probability ≥ 0.70 (70%)

**Blocking Logic:**
- Project submission blocked if BOTH cost AND time risks are HIGH
- User must revise project details before proceeding

## Service Startup

### Start FastAPI Service
```bash
cd backend/ml_service
python main.py
# Or use: uvicorn main:app --host 0.0.0.0 --port 8000
```

### Check Service Health
```bash
curl http://localhost:8000/health
```

### Test Prediction
```bash
curl -X POST http://localhost:8000/predict \
  -H "Content-Type: application/json" \
  -d '{"plot_size_sqft":2500,"building_size_sqft":2000,"num_floors":2,"budget_amount":2500000,"num_bedrooms":3,"num_bathrooms":2}'
```

## Prediction Storage

### Database Tables
- `contractor_send_estimates` - Predictions stored with estimate
- `construction_projects` - Predictions copied when project created

### Storage Columns
- predicted_cost_risk_level (ENUM: Low/Medium/High)
- predicted_cost_probability (DECIMAL 0-1)
- predicted_time_risk_level (ENUM: Low/Medium/High)
- predicted_time_probability (DECIMAL 0-1)
- prediction_generated_at (TIMESTAMP)
- model_version (VARCHAR)

### Storage Flow
1. Prediction generated → displayed in modal
2. If estimate_id exists → save to contractor_send_estimates
3. When project created → trigger copies to construction_projects
4. When work starts → predictions locked (immutable)

## Troubleshooting

### Issue: "ML service connection failed"
**Cause:** FastAPI service not running  
**Fix:** Start service with `python backend/ml_service/main.py`

### Issue: "Models not loaded"
**Cause:** Model files missing or corrupted  
**Fix:** Check `backend/ml/models/` contains .pkl files

### Issue: "Invalid form data"
**Cause:** Missing required fields  
**Fix:** Ensure all 6 required fields are provided

### Issue: Predictions not saving
**Cause:** Database columns missing  
**Fix:** Run `apply_prediction_columns_fix.php`

## Performance Optimization

### Model Loading
- Models loaded ONCE at service startup
- Kept in memory for fast predictions
- No disk I/O during prediction

### Response Time
- Before (exec): ~1600ms
- After (FastAPI): ~50-100ms
- Improvement: 16-32x faster

### Scalability
- Single service handles multiple concurrent requests
- Can be deployed behind load balancer
- Docker-ready for containerization

## Model Retraining

### When to Retrain
- After collecting 50+ completed projects
- When prediction accuracy drops
- When adding new features

### Retraining Process
```bash
cd backend/ml
python run_training.py
```

### Output
- New model files in `backend/ml/models/`
- Updated metadata.json with performance metrics
- Service automatically uses new models on restart

## API Reference

### POST /predict
**Request:**
```json
{
  "plot_size_sqft": float,
  "building_size_sqft": float,
  "num_floors": int,
  "budget_amount": float,
  "num_bedrooms": int,
  "num_bathrooms": int,
  "plot_shape": string (optional),
  "topography": string (optional),
  "design_style": string (optional)
}
```

**Response:**
```json
{
  "success": true,
  "cost_overrun_risk": {...},
  "time_delay_risk": {...}
}
```

### GET /health
**Response:**
```json
{
  "status": "healthy",
  "models_loaded": true,
  "model_version": "v1"
}
```

## Security Considerations

- FastAPI service runs on localhost only
- PHP API validates all inputs
- No sensitive data in predictions
- Models are read-only after training
- Predictions locked after work begins

## Future Enhancements

1. Add confidence intervals to predictions
2. Implement A/B testing for model versions
3. Add real-time model monitoring
4. Integrate with project outcome data for continuous learning
5. Add explainability dashboard for contractors
