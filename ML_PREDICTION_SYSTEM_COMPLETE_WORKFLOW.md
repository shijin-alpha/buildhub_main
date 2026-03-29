# ML Prediction System - Complete Technical Workflow

## Executive Summary

The BuildHub ML Prediction System uses trained machine learning models to predict construction project risks in real-time. The system analyzes homeowner project requirements and provides risk assessments for cost overruns and time delays.

**Key Components:**
- **ML Models:** Gradient Boosting (cost) + Random Forest (time)
- **Service Architecture:** FastAPI persistent service
- **Accuracy:** 94.7% (cost) | 98.9% (time)
- **Response Time:** 50-100ms per prediction

---

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND LAYER                                │
│  RiskAssessmentPreview.jsx                                       │
│  - Collects form data                                            │
│  - Displays risk assessment                                      │
│  - Handles user interaction                                      │
└────────────────────────┬────────────────────────────────────────┘
                         │ HTTP POST (JSON)
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                    PHP API LAYER                                 │
│  predict_construction_risks.php                                  │
│  - Validates input                                               │
│  - Forwards to ML service                                        │
│  - Returns formatted response                                    │
└────────────────────────┬────────────────────────────────────────┘
                         │ HTTP POST to localhost:8000
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                    FASTAPI ML SERVICE                            │
│  main.py (Port 8000)                                             │
│  - Persistent service                                            │
│  - Models loaded in memory                                       │
│  - Fast predictions                                              │
└────────────────────────┬────────────────────────────────────────┘
                         │ Calls predict_risks()
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                    ML PREDICTION ENGINE                          │
│  risk_predictor.py                                               │
│  - Feature engineering                                           │
│  - Model inference                                               │
│  - Explanation generation                                        │
└────────────────────────┬────────────────────────────────────────┘
                         │ Loads from disk
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│                    TRAINED MODELS                                │
│  backend/ml/models/                                              │
│  - cost_overrun_risk_model.pkl (Gradient Boosting)              │
│  - time_delay_risk_model.pkl (Random Forest)                    │
│  - model_metadata.json (Features & Performance)                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Complete Data Flow (Step-by-Step)

### Step 1: User Initiates Request

**Location:** Frontend - Homeowner Dashboard

**Component:** `frontend/src/components/HomeownerRequestWizard.jsx`

**Action:** User fills custom request form with project details

**Data Collected:**
