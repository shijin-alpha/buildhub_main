@echo off
REM Start FastAPI ML Service on Windows - Fixed for Python 3.13

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

REM Install only FastAPI packages (minimal requirements)
echo Installing FastAPI packages...
pip install fastapi uvicorn[standard] pydantic

REM Check if ML packages are available
echo.
echo Checking ML packages...
python -c "import numpy, pandas, sklearn, joblib" 2>nul
if errorlevel 1 (
    echo.
    echo WARNING: ML packages not found in venv
    echo Attempting to install ML packages...
    pip install numpy pandas scikit-learn joblib
    if errorlevel 1 (
        echo.
        echo ERROR: Failed to install ML packages
        echo.
        echo SOLUTION: Install Python 3.11 instead of 3.13
        echo Python 3.13 is too new and doesn't have pre-built wheels
        echo.
        echo Download Python 3.11 from: https://www.python.org/downloads/
        echo Then delete the venv folder and run this script again
        pause
        exit /b 1
    )
)

REM Start the service
echo.
echo Starting FastAPI service on http://localhost:8000
echo Press Ctrl+C to stop the service
echo.
python -m uvicorn main:app --host 0.0.0.0 --port 8000 --reload

pause
