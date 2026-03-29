# 🚀 Construction AI System - Quick Start Guide

**For:** Developers, System Administrators, and Technical Users  
**Last Updated:** March 11, 2026

---

## 📋 Prerequisites

- MySQL/MariaDB database
- PHP 7.4+ with mysqli extension
- Python 3.8+ with required ML libraries
- Web server (Apache/Nginx)
- Node.js (for frontend build)

---

## ⚡ 5-Minute Setup

### Step 1: Apply Database Schema (2 minutes)

```bash
# Navigate to project root
cd /path/to/buildhub

# Apply the prediction storage fix
mysql -u your_username -p buildhub < backend/database/prediction_storage_fix.sql

# Verify trigger creation
mysql -u your_username -p buildhub -e "SHOW TRIGGERS LIKE 'construction_projects'"
```

**Expected Output:**
```
copy_predictions_to_project | AFTER INSERT | construction_projects
```

### Step 2: Verify API Files (1 minute)

```bash
# Check new API files exist
ls -la backend/api/ml/save_estimate_prediction.php
ls -la backend/api/ml/get_evaluation_metrics.php
ls -la backend/api/budget_tracking.php

# Set proper permissions
chmod 644 backend/api/ml/*.php
chmod 644 backend/api/budget_tracking.php
```

### Step 3: Test Prediction Storage (2 minutes)

```bash
# Test estimate prediction storage
curl -X POST http://localhost/buildhub/backend/api/ml/save_estimate_prediction.php \
  -H "Content-Type: application/json" \
  -d '{
    "estimate_id": 1,
    "cost_risk_level": "High",
    "cost_probability": 0.85,
    "time_risk_level": "Medium",
    "time_probability": 0.62,
    "model_version": "v1.0.0"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "AI prediction saved to estimate successfully",
  "data": { ... }
}
```

---

## 🧪 Testing the Complete Workflow

### Test 1: Prediction Storage and Transfer

```sql
-- 1. Create test estimate
INSERT INTO contractor_send_estimates (
  contractor_id, homeowner_id, total_cost, timeline, status, created_at
) VALUES (1, 1, 5000000, '12 months', 'pending', NOW());

SET @estimate_id = LAST_INSERT_ID();

-- 2. Store prediction (via API or direct SQL)
UPDATE contractor_send_estimates
SET predicted_cost_risk_level = 'High',
    predicted_cost_probability = 0.85,
    predicted_time_risk_level = 'Medium',
    predicted_time_probability = 0.62,
    prediction_generated_at = NOW(),
    model_version = 'v1.0.0'
WHERE id = @estimate_id;

-- 3. Create project from estimate
INSERT INTO construction_projects (
  estimate_id, homeowner_id, contractor_id, project_name,
  estimated_cost, status, created_at
) VALUES (
  @estimate_id, 1, 1, 'Test AI Integration',
  5000000, 'planning', NOW()
);

SET @project_id = LAST_INSERT_ID();

-- 4. Verify predictions copied automatically
SELECT 
  id, project_name,
  predicted_cost_risk_level,
  predicted_time_risk_level,
  predictions_locked
FROM construction_projects
WHERE id = @project_id;
```

**Expected Result:**
- `predicted_cost_risk_level` = 'High'
- `predicted_time_risk_level` = 'Medium'
- `predictions_locked` = 0

### Test 2: Automatic Evaluation

```sql
-- 1. Start project (locks predictions)
UPDATE construction_projects
SET actual_start_date = NOW(),
    planned_start_date = DATE_SUB(NOW(), INTERVAL 1 DAY),
    planned_end_date = DATE_ADD(NOW(), INTERVAL 12 MONTH)
WHERE id = @project_id;

-- 2. Add payments (simulate 10% cost overrun)
INSERT INTO stage_payment_requests (
  project_id, stage_name, amount, status, request_date
) VALUES
  (@project_id, 'Foundation', 1500000, 'paid', NOW()),
  (@project_id, 'Structure', 2000000, 'paid', NOW()),
  (@project_id, 'Finishing', 2000000, 'paid', NOW());

-- Total: 5,500,000 (10% overrun on 5,000,000 estimate)

-- 3. Complete project (triggers evaluation)
UPDATE construction_projects
SET status = 'completed',
    actual_end_date = NOW()
WHERE id = @project_id;

-- 4. Verify evaluation completed
SELECT 
  id, project_name,
  predicted_cost_risk_level,
  cost_ground_truth_label,
  cost_prediction_classification,
  cost_prediction_correct,
  actual_cost_overrun_percentage,
  evaluation_completed_at
FROM construction_projects
WHERE id = @project_id;
```

**Expected Result:**
- `actual_cost_overrun_percentage` = 10.00
- `cost_ground_truth_label` = 'High' (10% > 5% threshold)
- `cost_prediction_classification` = 'TP' (True Positive)
- `cost_prediction_correct` = 1
- `evaluation_completed_at` = [timestamp]

---

## 🔌 API Usage Examples

### 1. Get Latest Metrics

```bash
curl http://localhost/buildhub/backend/api/ml/get_evaluation_metrics.php?type=latest
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cost_overrun": {
      "accuracy": 0.85,
      "precision": 0.82,
      "recall": 0.88,
      "f1_score": 0.85,
      "confusion_matrix": {
        "true_positives": 45,
        "false_positives": 10,
        "true_negatives": 38,
        "false_negatives": 7
      }
    },
    "time_delay": { ... }
  }
}
```

### 2. Get Project Evaluation

```bash
curl http://localhost/buildhub/backend/api/ml/get_evaluation_metrics.php?type=project&project_id=1
```

### 3. Get Budget Summary

```bash
curl http://localhost/buildhub/backend/api/budget_tracking.php?project_id=1&action=summary
```

**Response:**
```json
{
  "success": true,
  "data": {
    "budget": {
      "estimated_cost": 5000000,
      "total_cost": 5500000,
      "remaining_budget": -500000,
      "budget_utilization_pct": 110
    },
    "overrun": {
      "is_over_budget": true,
      "overrun_amount": 500000,
      "overrun_percentage": 10,
      "status": "OVER BUDGET"
    }
  }
}
```

### 4. Get Payment Breakdown

```bash
curl http://localhost/buildhub/backend/api/budget_tracking.php?project_id=1&action=breakdown
```

---

## 🎯 Frontend Integration

### Using RiskAssessmentPreview Component

```jsx
import RiskAssessmentPreview from './components/RiskAssessmentPreview';

function ProjectForm() {
  const [showRiskAssessment, setShowRiskAssessment] = useState(false);
  const [formData, setFormData] = useState({
    estimate_id: 123,
    plot_size: 2000,
    building_size: 1500,
    floors: 2,
    bedrooms: 3,
    bathrooms: 2,
    budget: 5000000,
    // ... other fields
  });

  return (
    <>
      <form onSubmit={() => setShowRiskAssessment(true)}>
        {/* Form fields */}
      </form>

      <RiskAssessmentPreview
        formData={formData}
        isVisible={showRiskAssessment}
        onProceed={() => {
          // Submit project
          setShowRiskAssessment(false);
        }}
        onRevise={() => {
          // Go back to form
          setShowRiskAssessment(false);
        }}
      />
    </>
  );
}
```

**What Happens Automatically:**
1. Component displays risk assessment to user
2. Calls `save_estimate_prediction.php` in background
3. Predictions stored with estimate_id
4. User sees risk report and can proceed or revise
5. When project is created later, predictions auto-copy via trigger

---

## 🔍 Monitoring and Debugging

### Check Prediction Storage

```sql
-- View recent predictions on estimates
SELECT 
  id, contractor_id, homeowner_id,
  predicted_cost_risk_level,
  predicted_time_risk_level,
  prediction_generated_at,
  model_version
FROM contractor_send_estimates
WHERE predicted_cost_risk_level IS NOT NULL
ORDER BY prediction_generated_at DESC
LIMIT 10;
```

### Check Trigger Execution

```sql
-- Verify predictions copied to projects
SELECT 
  cp.id,
  cp.project_name,
  cp.estimate_id,
  cse.predicted_cost_risk_level as estimate_prediction,
  cp.predicted_cost_risk_level as project_prediction,
  CASE 
    WHEN cse.predicted_cost_risk_level = cp.predicted_cost_risk_level 
    THEN 'COPIED ✓' 
    ELSE 'MISMATCH ✗' 
  END as status
FROM construction_projects cp
JOIN contractor_send_estimates cse ON cp.estimate_id = cse.id
WHERE cse.predicted_cost_risk_level IS NOT NULL
ORDER BY cp.created_at DESC
LIMIT 10;
```

### Check Evaluation Status

```sql
-- View evaluation completion status
SELECT 
  id, project_name, status,
  predicted_cost_risk_level,
  cost_ground_truth_label,
  cost_prediction_classification,
  cost_prediction_correct,
  evaluation_completed_at
FROM construction_projects
WHERE status = 'completed'
ORDER BY evaluation_completed_at DESC
LIMIT 10;
```

### Check Metrics Updates

```sql
-- View latest metrics
SELECT * FROM ai_evaluation_metrics
WHERE evaluation_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY evaluation_date DESC, metric_type;
```

---

## 🐛 Troubleshooting

### Issue: Predictions not saving to estimate

**Check:**
```bash
# Verify API file exists and is readable
ls -la backend/api/ml/save_estimate_prediction.php

# Check PHP error log
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/nginx/error.log
```

**Solution:**
- Verify database connection in `backend/config/database.php`
- Check estimate_id exists in database
- Verify risk levels are 'Low', 'Medium', or 'High'
- Check probabilities are between 0 and 1

### Issue: Predictions not copying to project

**Check:**
```sql
-- Verify trigger exists
SHOW TRIGGERS LIKE 'construction_projects';

-- Check if estimate has predictions
SELECT * FROM contractor_send_estimates WHERE id = [estimate_id];

-- Check if project has estimate_id
SELECT * FROM construction_projects WHERE id = [project_id];
```

**Solution:**
- Re-run `prediction_storage_fix.sql`
- Verify estimate_id is set when creating project
- Check MySQL error log for trigger execution errors

### Issue: Evaluation not running automatically

**Check:**
```sql
-- Verify trigger exists
SHOW TRIGGERS WHERE `Trigger` = 'auto_evaluate_on_completion';

-- Check stored procedure exists
SHOW PROCEDURE STATUS WHERE Name = 'evaluate_project_predictions';
```

**Solution:**
- Re-run `ai_self_evaluation_schema.sql`
- Verify project status is exactly 'completed'
- Check predictions_locked = 1 before completion

### Issue: Metrics API returning empty data

**Check:**
```sql
-- Verify views exist
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Check if metrics table has data
SELECT COUNT(*) FROM ai_evaluation_metrics;

-- Check if any projects have been evaluated
SELECT COUNT(*) FROM construction_projects 
WHERE evaluation_completed_at IS NOT NULL;
```

**Solution:**
- Complete at least one test project
- Verify evaluation trigger executed
- Check database views are created correctly

---

## 📊 Performance Optimization

### Database Indexes

```sql
-- Verify indexes exist
SHOW INDEX FROM contractor_send_estimates;
SHOW INDEX FROM construction_projects;
SHOW INDEX FROM ai_evaluation_metrics;

-- Add additional indexes if needed
CREATE INDEX idx_project_status ON construction_projects(status);
CREATE INDEX idx_evaluation_date ON ai_evaluation_metrics(evaluation_date);
```

### API Caching

Consider implementing caching for:
- Latest metrics (cache for 1 hour)
- Historical metrics (cache for 24 hours)
- Project evaluations (cache indefinitely once completed)

---

## 🔐 Security Best Practices

### 1. Authentication

Add session validation to all APIs:

```php
// At the top of each API file
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}
```

### 2. Authorization

Implement project-based access control:

```php
// Verify user has access to project
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if ($user_role !== 'admin') {
    // Check if user is homeowner or contractor for this project
    $check_query = "SELECT id FROM construction_projects 
                    WHERE id = ? AND (homeowner_id = ? OR contractor_id = ?)";
    // ... execute and verify
}
```

### 3. Input Validation

Always validate and sanitize inputs:

```php
// Validate risk levels
$valid_risk_levels = ['Low', 'Medium', 'High'];
if (!in_array($cost_risk_level, $valid_risk_levels)) {
    // Reject request
}

// Validate probabilities
if ($probability < 0 || $probability > 1) {
    // Reject request
}
```

---

## 📈 Next Steps

### After Initial Deployment

1. **Monitor First 10 Projects**
   - Track prediction accuracy
   - Identify common failure patterns
   - Collect user feedback

2. **Analyze Performance Metrics**
   - Review confusion matrix
   - Calculate precision and recall
   - Identify areas for improvement

3. **Model Retraining**
   - Collect new training data from completed projects
   - Retrain models with updated dataset
   - Deploy improved models with new version number

4. **Dashboard Development**
   - Create admin dashboard for metrics visualization
   - Add charts for historical trends
   - Implement alerts for low accuracy

---

## 📚 Additional Resources

- **Complete Documentation:** `AI_SYSTEM_INTEGRATION_COMPLETE.md`
- **Architecture Audit:** `CONSTRUCTION_AI_SYSTEM_COMPLETE_AUDIT.md`
- **Status Report:** `CONSTRUCTION_AI_INTEGRATION_STATUS.md`
- **Database Schema:** `backend/database/prediction_storage_fix.sql`
- **Evaluation Framework:** `backend/database/ai_self_evaluation_schema.sql`

---

## 💡 Tips for Success

1. **Start Small:** Test with 5-10 projects before full rollout
2. **Monitor Closely:** Watch for prediction storage failures
3. **Collect Feedback:** Ask users about risk assessment clarity
4. **Iterate Quickly:** Fix issues as they arise
5. **Document Changes:** Keep track of model versions and updates

---

**System Status:** PRODUCTION READY ✅  
**Confidence Level:** HIGH  
**Support:** Check documentation or review code comments in API files

