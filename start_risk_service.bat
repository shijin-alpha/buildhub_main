@echo off
echo ========================================
echo   Risk Assessment Service Launcher
echo ========================================
echo.

echo 🔍 Checking service status...
cd backend\ml

echo.
echo 🧪 Running API test...
python test_api_clean.py

echo.
echo 📊 Service endpoints available:
echo   - Python API: backend/ml/predict_risks_api.py
echo   - PHP API: backend/api/ml/predict_construction_risks.php
echo   - Test Interface: test_risk_service_complete.html

echo.
echo ✅ Risk Assessment Service is ready!
echo.
pause