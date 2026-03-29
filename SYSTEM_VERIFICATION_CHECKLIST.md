# ✅ Construction AI System - Complete Verification Checklist

**Purpose:** Step-by-step guide to verify the entire system is working  
**Time Required:** 30-45 minutes  
**Date:** March 11, 2026

---

## 🎯 PRE-VERIFICATION SETUP

### Step 1: Check Database Schema (5 minutes)

```bash
# Connect to your database
mysql -u root -p buildhub

# Or if using SQLite
sqlite3 buildhub.db
```

Run these verification queries:

```sql
-- 1. Check if prediction fields exist in estimates table
DESCRIBE contractor_send_estimates;
-- Look for: predicted_cost_risk_level, predicted_cost_probability, etc.

-- 2. Check if triggers exist
SHOW TRIGGERS;
-- Look for: copy_predictions_to_project, lock_predictions_on_start, auto_evaluate_on_completion

-- 3. Check if evaluation tables exist
SHOW TABLES LIKE '%evaluation%';
-- Look for: ai_evaluation_metrics, ai_evaluation_config

-- 4. Check if views exist
SHOW FULL TABLES WHERE Table_type = 'VIEW';
-- Look for: v_latest_ai_metrics, v_project_evaluation_summary

-- 5. Check stored procedures
SHOW PROCEDURE STATUS WHERE Db = 'buildhub';
-- Look for: evaluate_project_predictions
```

**Expected Results:**
- ✅ All prediction fields present in contractor_send_estimates
- ✅ 3 triggers visible
- ✅ Evaluation tables exist
- ✅ Database views created
- ✅ Stored procedures present

**If Missing:** Run these schema files:
```bash
mysql -u root -p buildhub < backend/database/prediction_storage_fix.sql
mysql -u root -p buildhub < backend/database/ai_self_evaluation_schema.sql
```

---

## 🧪 PHASE 1: API VERIFICATION (10 minutes)

### Test 1: Prediction API

```bash
# Test ML prediction endpoint
curl -X POST http://localhost/buildhub/backend/api/ml/predict_construction_risks.php \
  -H "Content-Type: applicatio