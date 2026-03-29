# Cost & Time Overrun System - Visual Summary

## 🎯 Two-Stage System Overview

```
┌──────────────────────────────────────────────────────────────────┐
│                         STAGE 1: PLANNING                        │
│                    (Before Project Starts)                       │
│                                                                  │
│  Homeowner Form → AI Analysis → Risk Preview → Decision         │
│                                                                  │
│  📊 AI Models:                                                   │
│  • Cost Overrun: 94.7% accuracy                                 │
│  • Time Delay: 98.9% accuracy                                   │
│                                                                  │
│  🚫 Risk Blocking:                                              │
│  • If BOTH risks HIGH → Cannot submit                           │
│  • Otherwise → Can proceed                                      │
└──────────────────────────────────────────────────────────────────┘
                              ↓
                     PROJECT APPROVED
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│                        STAGE 2: EXECUTION                        │
│                   (During Construction)                          │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  SCHEDULE TRACKING (Time Overrun)                          │ │
│  │  ────────────────────────────────────                      │ │
│  │  1. Set Planned Dates (Feb 1 - May 1 = 90 days)          │ │
│  │  2. Record Actual Start (Feb 5) → 🔒 Dates Locked        │ │
│  │  3. Record Actual End (May 15)                            │ │
│  │  4. Auto-Calculate: 100 days = 11.11% overrun 🔴         │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  BUDGET TRACKING (Cost Overrun)                            │ │
│  │  ────────────────────────────────────                      │ │
│  │  Original Estimate:    ₹2,500,000                         │ │
│  │  Stage Payments:       ₹2,200,000                         │ │
│  │  Custom Payments:      ₹450,000                           │ │
│  │  ─────────────────────────────────                        │ │
│  │  Total Cost:           ₹2,650,000                         │ │
│  │  Overrun:              ₹150,000 (6.0%) 🔴                │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  PROGRESS MONITORING                                       │ │
│  │  ────────────────────────────────────                      │ │
│  │  • Daily reports with photos                               │ │
│  │  • Stage completion tracking                               │ │
│  │  • Worker & hours logging                                  │ │
│  │  • Weather & delay tracking                                │ │
│  └────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
                              ↓
┌──────────────────────────────────────────────────────────────────┐
│                      FINAL REPORTING                             │
│  • Cost Overrun: 6.0%                                           │
│  • Time Overrun: 11.11%                                         │
│  • AI Predicted vs Actual Comparison                            │
└──────────────────────────────────────────────────────────────────┘
```

## 📐 Key Formulas

### Cost Overrun %
```
((Total Cost - Original Estimate) / Original Estimate) × 100

Example:
((2,650,000 - 2,500,000) / 2,500,000) × 100 = 6.0%
```

### Time Overrun %
```
((Actual Duration - Planned Duration) / Planned Duration) × 100

Example:
((100 days - 90 days) / 90 days) × 100 = 11.11%
```

## 🎨 Risk Level Colors

- 🟢 **LOW:** 0-40% probability
- 🟡 **MEDIUM:** 40-70% probability  
- 🔴 **HIGH:** 70-100% probability

## 🚫 Risk Blocking Matrix

```
┌─────────────┬─────────────┬──────────────┐
│  Cost Risk  │  Time Risk  │    Result    │
├─────────────┼─────────────┼──────────────┤
│   🔴 HIGH   │   🔴 HIGH   │  🚫 BLOCKED  │
├─────────────┼─────────────┼──────────────┤
│   🔴 HIGH   │   🟡 MED    │  ✅ ALLOWED  │
│   🔴 HIGH   │   🟢 LOW    │  ✅ ALLOWED  │
│   🟡 MED    │   🔴 HIGH   │  ✅ ALLOWED  │
│   🟡 MED    │   🟡 MED    │  ✅ ALLOWED  │
│   🟡 MED    │   🟢 LOW    │  ✅ ALLOWED  │
│   🟢 LOW    │   🔴 HIGH   │  ✅ ALLOWED  │
│   🟢 LOW    │   🟡 MED    │  ✅ ALLOWED  │
│   🟢 LOW    │   🟢 LOW    │  ✅ ALLOWED  │
└─────────────┴─────────────┴──────────────┘

Only 1 out of 9 combinations is blocked!
```

## 🔄 Complete User Flows

### Homeowner Flow
```
1. Fill Form → 2. View AI Risk → 3. Submit → 4. Monitor Budget & Timeline → 5. Completion
```

### Contractor Flow
```
1. Receive Project → 2. Set Schedule → 3. Start Work (Lock) → 4. Daily Reports → 5. Complete
```

## 📊 System Components

| Component | File | Purpose |
|-----------|------|---------|
| AI Models | `backend/ml/models/*.pkl` | Risk prediction |
| Risk API | `backend/api/ml/predict_construction_risks.php` | AI endpoint |
| Budget API | `backend/api/contractor/get_project_budget_summary.php` | Cost tracking |
| Schedule API | `backend/api/schedule_tracking.php` | Time tracking |
| Frontend | `frontend/src/components/RiskAssessmentPreview.jsx` | Risk display |

## ✅ System Status

| Feature | Status | Performance |
|---------|--------|-------------|
| Cost AI | ✅ Ready | 94.7% |
| Time AI | ✅ Ready | 98.9% |
| Risk Blocking | ✅ Active | 100% |
| Budget Tracking | ✅ Live | Real-time |
| Schedule Tracking | ✅ Live | Auto-calc |

---

**Quick Reference Guide**  
**Version:** 1.0  
**Date:** February 16, 2026
