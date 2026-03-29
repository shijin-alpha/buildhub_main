@echo off
REM Start FastAPI ML Service on Windows

echo Starting Construction Risk Assessment ML Service...
echo.

REM Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    pause
    exit /b 1
)

REM Check if virtual environment exists
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
    if errorlevel 1 (
        echo ERROR: Failed to create virtual environment
        pause
        exit /b 1
    )
)

REM Activate virtual environment
call venv\Scripts\activate.bat

REM Install dependencies
echo Installing dependencies...
pip install -r requirements.txt

REM Start the service
echo.
echo Starting FastAPI service on http://localhost:8000
echo Press Ctrl+C to stop the service
echo.
python -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload

pause
