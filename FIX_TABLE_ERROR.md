# 🔧 Fix: Table 'buildhub.ai_predictions' doesn't exist

## The Error You're Seeing

```
Error: SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'buildhub.ai_predictions' doesn't exist
```

## ✅ Quick Fix (30 seconds)

### Option 1: Use Setup Page (Easiest)

1. **Open in browser:**
   ```
   http://localhost/buildhub/setup_ml_analytics.html
   ```

2. **Click the button:**
   ```
   🚀 Run Setup
   ```

3. **Wait for success message**

4. **Refresh your dashboard**

5. **Done!** The error is fixed.

---

### Option 2: Run PHP Script

```bash
php create_ml_analytics_tables.php
```

---

### Option 3: Manual SQL

Copy and paste this into your MySQL/phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS ai_predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    cost_risk_level VARCHAR(20) NOT NULL,
    cost_risk_probability DECIMAL(5,4) NOT NULL,
    time_risk_level VARCHAR(20) NOT NULL,
    time_risk_probability DECIMAL(5,4) NOT NULL,
    model_version VARCHAR(50) DEFAULT 'v1.0.0',
    prediction_locked_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_project_id (project_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_evaluation_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_type VARCHAR(20) NOT NULL,
    accuracy DECIMAL(5,4) NOT NULL,
    precision_val DECIMAL(5,4) NOT NULL,
    recall_val DECIMAL(5,4) NOT NULL,
    f1_score DECIMAL(5,4) NOT NULL,
    true_positives INT DEFAULT 0,
    false_positives INT DEFAULT 0,
    true_negatives INT DEFAULT 0,
    false_negatives INT DEFAULT 0,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_type (metric_type),
    INDEX idx_calculated_at (calculated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample model metrics
INSERT INTO ai_evaluation_metrics 
(metric_type, accuracy, precision_val, recall_val, f1_score, true_positives, false_positives, true_negatives, false_negatives)
VALUES 
('cost', 0.9470, 0.9320, 0.9470, 0.9390, 127, 9, 63, 7),
('time', 0.9890, 0.9850, 0.9930, 0.9890, 141, 2, 17, 1);

-- Insert sample predictions for first 5 projects
INSERT INTO ai_predictions (project_id, cost_risk_level, cost_risk_probability, time_risk_level, time_risk_probability)
SELECT 
    project_id,
    'High' as cost_risk_level,
    0.85 as cost_risk_probability,
    'Medium' as time_risk_level,
    0.62 as time_risk_probability
FROM projects
WHERE project_id <= 5
ON DUPLICATE KEY UPDATE project_id=project_id;
```

---

## ✅ Verify It Worked

After running the setup, check:

```sql
SELECT COUNT(*) FROM ai_predictions;
SELECT COUNT(*) FROM ai_evaluation_metrics;
```

You should see:
- `ai_predictions`: 5+ rows
- `ai_evaluation_metrics`: 2 rows

---

## 🎯 Then Use the Dashboard

1. **Refresh your browser** (Ctrl + F5)
2. **Login** as contractor or admin
3. **Click** "🤖 ML Analytics" tab
4. **Select** a project
5. **View** your charts!

---

## 🐛 Still Having Issues?

### Check Database Connection
```php
// In backend/config/database.php
// Make sure credentials are correct
```

### Check MySQL is Running
```bash
# Windows
net start mysql

# Or check XAMPP Control Panel
```

### Check Table Exists
```sql
SHOW TABLES LIKE 'ai_predictions';
```

---

## 📞 Need Help?

Check these files:
- `START_HERE_ML_ANALYTICS.md` - Quick start
- `ML_ANALYTICS_SETUP_COMPLETE.md` - Full setup guide
- `test_ml_analytics_api.html` - Test the API

---

**The fix is simple: Just run the setup page!**

👉 **http://localhost/buildhub/setup_ml_analytics.html**
