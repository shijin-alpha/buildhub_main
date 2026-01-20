@echo off
echo ========================================
echo Starting BuildHub AI Service
echo ========================================
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://www.python.org/
    pause
    exit /b 1
)

echo Python found!
echo.

REM Navigate to ai_service directory
cd /d "%~dp0ai_service"

REM Check if virtual environment exists
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
    echo.
)

REM Activate virtual environment
echo Activating virtual environment...
call venv\Scripts\activate.bat

REM Check if requirements are installed
echo Checking dependencies...
pip show fastapi >nul 2>&1
if errorlevel 1 (
    echo Installing dependencies... This may take several minutes.
    echo NOTE: First run will download ~5-10GB of AI models
    pip install -r requirements.txt
    echo.
)

echo.
echo ========================================
echo Starting FastAPI AI Service
echo ========================================
echo Service will run on: http://127.0.0.1:8000
echo Health check: http://127.0.0.1:8000/health
echo.
echo Press Ctrl+C to stop the service
echo ========================================
echo.

REM Start the service
python main.py

pause
