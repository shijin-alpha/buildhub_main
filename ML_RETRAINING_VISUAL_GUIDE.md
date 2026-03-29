# ML Model Retraining Pipeline - Visual Guide

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    CONSTRUCTION AI RISK ASSESSMENT SYSTEM                    │
│                         WITH AUTOMATIC RETRAINING                            │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│ PHASE 1: PREDICTION                                                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  Homeowner Request Form                                                      │
│         │                                                                     │
│         ├─ plot_size_sqft                                                    │
│         ├─ building_size_sqft                                                │
│         ├─ num_floors                                                        │
│         ├─ budget_amount                                                     │
│         ├─ num_bedrooms                                                      │
│         └─ num_bathrooms                                                     │
│         │                                                                     │
│         ▼                                                                     │
│  ┌──────────────────────┐                                                    │
│  │ predict_risks_api.py │                                                    │
│  └──────────────────────┘                                                    │
│         │                                                                     │
│         ├─ Reads: current_model.json                                         │
│         ├─ Loads: cost_overrun_model_v2.pkl                                  │
│         └─ Loads: time_delay_model_v2.pkl                                    │
│         │                                                                     │
│         ▼                                                                     │
│  ┌─────────────────────────────────────┐                                     │
│  │ AI Predictions                      │                                     │
│  ├─────────────────────────────────────┤                                     │
│  │ • cost_overrun_risk: High/Med/Low   │                                     │
│  │ • time_delay_risk: High/Med/Low     │                                     │
│  │ • confidence scores                 │                                     │
│  │ • explanations                      │                                     │
│  └─────────────────────────────────────┘                                     │
│         │                                                                     │
│         ▼                                                                     │
│  Stored in construction_projects table                                       │
│                                                                               │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│ PHASE 2: MONITORING                                                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  Project Construction                                                        │
│         │                                                                     │
│         ├─ Track actual costs                                                │
│         ├─ Track timeline progress                                           │
│         └─ Monitor milestones                                                │
│         │                                                                     │
│         ▼                                                                     │
│  Real-time data collection                                                   │
│                                                                               │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│ PHASE 3: EVALUATION                                                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                    