@echo off
echo ========================================
echo  Restarting AI Service with Real Images
echo ========================================
echo.

echo 1. Stopping any existing AI service...
taskkill /f /im python.exe 2>nul
timeout /t 2 >nul

echo 2. Navigating to AI service directory...
cd /d "%~dp0ai_service"

echo 3. Checking if main.py uses real Stable Diffusion module...
findstr /c:"from modules.conceptual_generator import" main.py >nul
if %errorlevel%==0 (
    echo ✅ Real Stable Diffusion module is active
) else (
    echo ❌ Still using placeholder module - fixing...
    powershell -Command "(Get-Content main.py) -replace 'from modules.conceptual_generator_simple import', 'from modules.conceptual_generator import' | Set-Content main.py"
    echo ✅ Fixed - now using real Stable Diffusion module
)

echo 4. Checking environment variables...
if not defined GEMINI_API_KEY (
    echo ⚠️  GEMINI_API_KEY not set - Gemini descriptions will use fallback
) else (
    echo ✅ GEMINI_API_KEY is set
)

echo 5. Starting AI service with real image generation...
echo.
echo 🚀 Starting AI service on http://127.0.0.1:8000
echo 📝 Logs will appear below...
echo 💡 Press Ctrl+C to stop the service
echo.

python main.py

pause