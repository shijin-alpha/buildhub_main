# Quick Start: Test Your AI Evaluation System

## The Problem
You can't see if your AI predictions are accurate because you don't have completed projects yet.

## The Solution
Run this command to create 5 test projects with different outcomes:

```bash
php simulate_completed_projects_for_ai_testing.php
```

## What Happens

This creates 5 completed projects that test all scenarios:

1. ✅ **Luxury Villa** - AI predicted HIGH risk, actually HIGH (Correct!)
2. ⚠️ **Simple House** - AI predicted HIGH risk, actually LOW (False alarm)
3. ✅ **Standard Home** - AI predicted LOW risk, actually LOW (Correct!)
4. ❌ **Complex Design** - AI predicted LOW risk, actually HIGH (Missed risk!)
5. 🔀 **Modern Apartment** - Mixed results (one right, one wrong)

## View Results

### In Terminal
The script shows you the results immediately:
```
📊 EVALUATION RESULTS:
- Cost: TP ✓ Correct
- Time: TP ✓ Correct
- Actual Cost Overrun: 12.5%
- Actual Time Overrun: 15.0%
```

### Via API
```bash
# Get performance metrics
curl http://localhost/backend/api/ml/get_evaluation_metrics.php?action=latest

# Get confusion matrix
curl http://localhost/backend/api/ml/get_evaluation_metrics.php?action=confusion_matrix
```

### In Database
```sql
SELECT 
    project_name,
    predicted_cost_risk_level,
    actual_cost_overrun_percentage,
    cost_prediction_classification,
    cost_prediction_correct
FROM construction_projects
WHERE evaluation_completed_at IS NOT NULL;
```

## Understanding Results

### Confusion Matrix
- **TP (True Positive)**: Predicted high, was high ✓
- **TN (True Negative)**: Predicted low, was low ✓
- **FP (False Positive)**: Predicted high, was low ✗ (false alarm)
- **FN (False Negative)**: Predicted low, was high ✗ (dangerous!)

### Performance Metrics
- **Accuracy**: Overall correctness (target: >80%)
- **Precision**: How many high-risk predictions were correct (target: >70%)
- **Recall**: How many actual high-risk projects did we catch (target: >80%)
- **F1 Score**: Balanced measure (target: >75%)

## What's Next?

1. ✅ Verify the evaluation system works
2. 📊 Build a dashboard to display these metrics
3. 🔄 Let real projects accumulate naturally
4. 📈 After 50+ projects, retrain the model with real data

## Alternative: Just Want Demo Data?

If you just want to see metrics without creating projects:

```bash
php populate_ai_evaluation_with_synthetic_data.php
```

This creates 30 days of historical metrics showing improvement over time.

---

**That's it!** Run the script and you'll immediately see how your AI evaluation system works.
