@echo off
echo ========================================
echo  Collaborative AI Pipeline Service
echo ========================================
echo.
echo Starting enhanced Room Improvement Assistant
echo with Gemini + Diffusion integration...
echo.

cd ai_service

echo Checking Python installation...
python --version
if %errorlevel% neq 0 (
    echo ERROR: Python is not installed or not in PATH
    pause
    exit /b 1
)

echo.
echo Checking environment configuration...
if not exist .env (
    echo WARNING: .env file not found
    echo Please copy .env.example to .env and configure your API keys
    pause
)

echo.
echo Starting AI service on http://127.0.0.1:8000
echo Press Ctrl+C to stop the service
echo.

python main.py

echo.
echo AI service stopped.
pause