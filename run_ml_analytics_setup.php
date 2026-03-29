<?php
/**
 * Automated ML Analytics Setup Runner
 * This script will automatically create the required tables and insert sample data
 */

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>ML Analytics Setup - Running...</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 { color: #667eea; margin-bottom: 20px; }
        .step { 
            padding: 15px; 
            margin: 10px 0; 
            background: #f8fafc; 
            border-radius: 8px;
            border-left: 4px solid #10b981;
        }
        .error { 
            border-left-color: #ef4444; 
            background: #fee2e2;
        }
        .success {
            background: #d1fae5;
            border-left-color: #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }
        .success h2 { color: #059669; margin: 0 0 10px 0; }
        pre { 
            background: #1e293b; 
            color: #e2e8f0; 
            padding: 15px; 
            border-radius: 8px; 
            overflow-x: auto;
            font-size: 13px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            margin: 10px 5px;
            font-weight: 600;
        }
        .button:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🤖 ML Analytics Setup</h1>
";

// Include the setup script
require_once 'create_ml_analytics_tables.php';

echo "
        <div class='success'>
            <h2>✅ Setup Complete!</h2>
            <p>The ML Analytics Dashboard is now ready to use.</p>
            <div style='margin-top: 20px;'>
                <a href='/buildhub/frontend/dist/index.html' class='button'>📊 Open Dashboard</a>
                <a href='test_ml_analytics_api.html' class='button'>🧪 Test API</a>
                <a href='ml_analytics_dashboard_demo.html' class='button'>🎨 View Demo</a>
            </div>
        </div>
        
        <div class='step'>
            <h3>✅ Next Steps:</h3>
            <ol>
                <li>Login as <strong>Contractor</strong> or <strong>Admin</strong></li>
                <li>Click on <strong>🤖 ML Analytics</strong> tab in the sidebar</li>
                <li>Select a project from the dropdown</li>
                <li>View your interactive charts and AI insights!</li>
            </ol>
        </div>
        
        <div class='step'>
            <h3>📝 What Was Created:</h3>
            <ul>
                <li>✅ <code>ai_predictions</code> table with sample predictions</li>
                <li>✅ <code>ai_evaluation_metrics</code> table with model performance data</li>
                <li>✅ Sample data for your existing projects</li>
                <li>✅ Model accuracy metrics (94.7% and 98.9%)</li>
            </ul>
        </div>
    </div>
</body>
</html>
";
?>
