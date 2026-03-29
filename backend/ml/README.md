# Machine Learning-Driven Decision Support Framework

## Overview

This module implements a comprehensive ML-driven decision support framework for managing cost and time overrun risks in residential construction projects. The system provides planning-stage risk assessment using trained machine learning models to predict cost overrun and time delay risks based on project characteristics.

## Architecture

### Core Components

1. **Risk Prediction Pipeline** (`risk_prediction_pipeline.py`)
   - Loads frozen datasets for training
   - Implements stratified train-test splitting
   - Trains multiple models (Logistic Regression, Random Forest, Gradient Boosting)
   - Selects best performing models based on F1-score and recall for high-risk class
   - Extracts feature importance for explainability

2. **Risk Predictor** (`risk_predictor.py`)
   - Real-time risk assessment interface
   - Converts form inputs to ML features
   - Loads trained models and makes predictions
   - Provides explainable risk assessments

3. **API Integration** (`predict_construction_risks.php`, `predict_risks_api.py`)
   - PHP endpoint for frontend integration
   - Python script for model execution
   - JSON-based request/response format

4. **Frontend Integration** (`RiskAssessmentPreview.jsx`)
   - React component for risk preview display
   - Interactive risk assessment interface
   - Explainable AI visualization

## Dataset Usage

### Frozen Datasets
- **Cost Overrun Dataset**: `data/cost_overrun_risk_dataset.csv` (1000 rows, 15 features)
- **Time Delay Dataset**: `data/time_delay_risk_dataset.csv` (1000 rows, 10 features)

### Features

#### Cost Overrun Risk Features
1. `plot_size_sqft` - Total plot area in square feet
2. `building_size_sqft` - Built-up area in square feet
3. `num_floors` - Number of floors
4. `budget_amount` - Total project budget in INR
5. `budget_per_sqft` - Budget per square foot (calculated)
6. `plot_shape_code` - Plot shape (0: rectangular, 1: square, 2: irregular, 3: L-shaped)
7. `topography_code` - Site topography (0: flat, 1: gentle slope, 2: steep slope, 3: hilly)
8. `num_bedrooms` - Number of bedrooms
9. `num_bathrooms` - Number of bathrooms
10. `total_rooms` - Total number of rooms (calculated)
11. `design_style_code` - Architectural style (0: modern, 1: traditional, 2: contemporary, 3: colonial)
12. `customization_level` - Level of customization (0-4)
13. `design_complexity_score` - Overall design complexity (0-15)
14. `development_constraint_level` - Site development constraints (0-3)

#### Time Delay Risk Features
1. `plot_size_sqft` - Total plot area in square feet
2. `building_size_sqft` - Built-up area in square feet
3. `num_floors` - Number of floors
4. `planned_duration_months` - Estimated project duration
5. `plot_shape_code` - Plot shape encoding
6. `topography_code` - Site topography encoding
7. `design_complexity_score` - Overall design complexity
8. `customization_level` - Level of customization
9. `site_difficulty_score` - Site access and construction difficulty

### Target Variables
- **Risk Levels**: 0 (Low), 1 (Medium), 2 (High)
- **Distribution**: 
  - Cost Overrun: Low (30.6%), Medium (4.0%), High (65.4%)
  - Time Delay: Low (8.8%), Medium (20.2%), High (71.0%)

## Model Selection

### Training Process
1. **Stratified Split**: 80% training, 20% testing
2. **Model Comparison**: Logistic Regression (baseline), Random Forest, Gradient Boosting
3. **Evaluation Metrics**: F1-score and recall for high-risk class (primary), overall F1-score (secondary)
4. **Feature Scaling**: Applied for Logistic Regression only

### Selected Models

#### Cost Overrun Risk Model
- **Algorithm**: Gradient Boosting Classifier
- **Performance**: 
  - F1-score (High Risk): 94.7%
  - Recall (High Risk): 94.7%
  - Overall F1-score: 93.0%

#### Time Delay Risk Model
- **Algorithm**: Random Forest Classifier
- **Performance**:
  - F1-score (High Risk): 98.9%
  - Recall (High Risk): 99.3%
  - Overall F1-score: 98.0%

### Feature Importance

#### Cost Overrun Risk - Top Factors
1. **Design Complexity Score** (46.2%) - Most critical factor
2. **Budget Per Sqft** (33.2%) - Cost efficiency indicator
3. **Budget Amount** (6.3%) - Project scale impact
4. **Plot Size** (2.6%) - Site size influence
5. **Total Rooms** (1.8%) - Project scope complexity

#### Time Delay Risk - Top Factors
1. **Number of Floors** (49.5%) - Construction complexity
2. **Site Difficulty Score** (19.8%) - Access and logistics
3. **Planned Duration** (9.7%) - Timeline realism
4. **Topography** (6.7%) - Site conditions
5. **Plot Shape** (5.2%) - Construction efficiency

## Integration Logic

### Homeowner Custom Request Flow Integration

1. **Form Data Collection**: Standard homeowner request form captures project details
2. **Feature Conversion**: Form inputs are automatically converted to ML features
3. **Risk Prediction**: Trained models predict cost and time overrun risks
4. **Explainable Preview**: Risk assessment displayed with key contributing factors
5. **User Decision**: Homeowner can revise details or proceed with submission

### API Workflow

```
Frontend Form → PHP API → Python Script → ML Models → Risk Assessment → Frontend Display
```

### Feature Mapping

The system automatically maps form inputs to ML features:

```javascript
// Example form data to ML features conversion
{
  plot_size_sqft: 2500,
  building_size_sqft: 2000,
  num_floors: 2,
  budget_amount: 2500000,
  num_bedrooms: 3,
  num_bathrooms: 2,
  plot_shape: 'rectangular',
  topography: 'flat',
  design_style: 'modern'
  // ... additional fields
}
```

## Installation & Setup

### 1. Install Python Dependencies
```bash
cd backend/ml
pip install -r requirements.txt
```

### 2. Train Models
```bash
python run_training.py
```

### 3. Test API
```bash
python test_api.py
```

### 4. Verify Integration
- Models saved in `backend/ml/models/`
- API endpoint: `backend/api/ml/predict_construction_risks.php`
- Frontend component: `frontend/src/components/RiskAssessmentPreview.jsx`

## Usage Examples

### API Request
```json
{
  "plot_size_sqft": 2500,
  "building_size_sqft": 2000,
  "num_floors": 2,
  "budget_amount": 2500000,
  "num_bedrooms": 3,
  "num_bathrooms": 2,
  "plot_shape": "rectangular",
  "topography": "flat",
  "design_style": "modern"
}
```

### API Response
```json
{
  "success": true,
  "cost_overrun_risk": {
    "prediction": 2,
    "risk_level": "High",
    "probabilities": {
      "Low": 0.0001,
      "Medium": 0.0000,
      "High": 0.9999
    },
    "explanation": [
      "Design complexity score of 10 is a key factor in cost overrun risk",
      "Budget per sq.ft of ₹1250 significantly influences cost overrun risk"
    ]
  },
  "time_delay_risk": {
    "prediction": 0,
    "risk_level": "Low",
    "probabilities": {
      "Low": 0.9414,
      "Medium": 0.0332,
      "High": 0.0254
    },
    "explanation": [
      "Number of floors (2) contributes to time delay risk",
      "Site difficulty score of 0 impacts time delay risk"
    ]
  }
}
```

## File Structure

```
backend/ml/
├── data/
│   ├── cost_overrun_risk_dataset.csv    # Frozen cost dataset
│   └── time_delay_risk_dataset.csv      # Frozen time dataset
├── models/
│   ├── cost_overrun_risk_model.pkl      # Trained cost model
│   ├── time_delay_risk_model.pkl        # Trained time model
│   └── model_metadata.json              # Model metadata & feature importance
├── risk_prediction_pipeline.py          # Training pipeline
├── risk_predictor.py                    # Prediction interface
├── predict_risks_api.py                 # API script
├── run_training.py                       # Training script
├── test_api.py                          # API test script
├── requirements.txt                      # Python dependencies
└── README.md                            # This file

backend/api/ml/
└── predict_construction_risks.php       # PHP API endpoint

frontend/src/components/
└── RiskAssessmentPreview.jsx           # React risk preview component
```

## Key Design Decisions

### 1. Planning-Stage Focus
- Models predict risk levels (Low/Medium/High) rather than exact values
- No execution-stage monitoring or runtime retraining
- Focus on pre-submission risk awareness

### 2. Explainable AI
- Feature importance extraction from trained models
- Human-readable explanations for risk factors
- Transparent decision-making process

### 3. Non-Intrusive Integration
- No new database tables or input fields required
- Seamless integration with existing custom request flow
- Optional risk preview before submission

### 4. Model Performance Priority
- High-risk class recall prioritized (94.7% and 99.3%)
- Stratified sampling to handle class imbalance
- Cross-validation for robust model selection

## Limitations & Considerations

1. **Dataset Scope**: Models trained on 1000 synthetic projects
2. **Feature Engineering**: Automated mapping may not capture all nuances
3. **Model Updates**: No runtime retraining (models are frozen)
4. **Risk Categories**: Three-level classification (Low/Medium/High)
5. **Regional Factors**: Models may not account for location-specific risks

## Future Enhancements

1. **Expanded Datasets**: Incorporate real project data
2. **Advanced Features**: Weather, material costs, labor availability
3. **Continuous Learning**: Periodic model retraining
4. **Risk Mitigation**: Actionable recommendations for risk reduction
5. **Integration Expansion**: Extend to other project types

## Troubleshooting

### Common Issues

1. **Models Not Found**: Run `python run_training.py` to train models
2. **Feature Mismatch**: Ensure form data includes all required fields
3. **API Errors**: Check Python dependencies and file permissions
4. **Performance Issues**: Consider model optimization for large-scale deployment

### Debug Commands

```bash
# Test model training
python run_training.py

# Test API functionality
python test_api.py

# Check model files
ls -la models/

# Verify Python dependencies
pip list | grep -E "(scikit-learn|pandas|numpy)"
```

## Support

For technical issues or questions about the ML framework:
1. Check model training logs for errors
2. Verify API endpoint accessibility
3. Test with sample data using provided scripts
4. Review feature mapping logic for form integration

---

**Note**: This framework is designed for planning-stage risk assessment only. It does not replace professional project management or detailed risk analysis by construction experts.

