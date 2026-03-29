@echo off
echo 🏗️ Starting Architect Recommendation Engine...

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Python is not installed. Please install Python first.
    pause
    exit /b 1
)

REM Check if pip is installed
pip --version >nul 2>&1
if errorlevel 1 (
    echo ❌ pip is not installed. Please install pip first.
    pause
    exit /b 1
)

echo 📦 Installing required packages...
pip install -r requirements.txt

REM Create models directory
if not exist models mkdir models

REM Test the API before starting
echo 🧪 Testing API setup...
python test_api.py

echo 🚀 Starting Architect Recommendation API on port 5001...
echo 📊 Using existing architect data from your database...
echo 🌐 CORS enabled for http://localhost:3000
python architect_recommendation_api.py

pause