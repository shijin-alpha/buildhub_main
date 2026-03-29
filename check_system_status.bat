@echo off
echo ========================================
echo BuildHub AI System Status Check
echo ========================================
echo.

echo [1/5] Checking Python installation...
python --version >nul 2>&1
if errorlevel 1 (
    echo    ❌ Python NOT installed
    echo    Please install Python 3.8+ from https://www.python.org/
    pause
    exit /b 1
) else (
    python --version
    echo    ✅ Python installed
)
echo.

echo [2/5] Checking AI service status...
curl -s http://127.0.0.1:8000/health >nul 2>&1
if errorlevel 1 (
    echo    ❌ AI service NOT running
    echo    Run: start_ai_service.bat
) else (
    echo    ✅ AI service is running
    curl -s http://127.0.0.1:8000/health
)
echo.

echo [3/5] Checking dependencies...
pip show fastapi >nul 2>&1
if errorlevel 1 (
    echo    ❌ FastAPI not installed
    echo    Run: pip install -r ai_service/requirements.txt
) else (
    echo    ✅ FastAPI installed
)

pip show torch >nul 2>&1
if errorlevel 1 (
    echo    ❌ PyTorch not installed
    echo    Run: pip install -r ai_service/requirements.txt
) else (
    echo    ✅ PyTorch installed
)

pip show diffusers >nul 2>&1
if errorlevel 1 (
    echo    ❌ Diffusers not installed
    echo    Run: pip install -r ai_service/requirements.txt
) else (
    echo    ✅ Diffusers installed
)
echo.

echo [4/5] Checking directories...
if exist "uploads\conceptual_images" (
    echo    ✅ Upload directory exists
) else (
    echo    ❌ Upload directory missing
    mkdir "uploads\conceptual_images"
    echo    Created upload directory
)
echo.

echo [5/5] Checking recent images...
dir /b /o-d "uploads\conceptual_images\*.png" 2>nul | findstr /i "conceptual_" >nul
if errorlevel 1 (
    echo    ⚠️  No real AI images found yet
    echo    Only placeholders detected
) else (
    echo    ✅ Real AI images found
    dir /b /o-d "uploads\conceptual_images\conceptual_*.png" 2>nul | findstr /n "^" | findstr "^[1-3]:"
)
echo.

echo ========================================
echo Summary
echo ========================================
curl -s http://127.0.0.1:8000/health >nul 2>&1
if errorlevel 1 (
    echo.
    echo ❌ SYSTEM NOT READY
    echo.
    echo TO FIX:
    echo 1. Install dependencies: pip install -r ai_service/requirements.txt
    echo 2. Start AI service: start_ai_service.bat
    echo 3. Test generation: Open test_real_ai_async_generation.html
    echo.
) else (
    echo.
    echo ✅ SYSTEM READY
    echo.
    echo NEXT STEPS:
    echo 1. Open: http://localhost/buildhub/test_real_ai_async_generation.html
    echo 2. Upload a room image
    echo 3. Wait 30-60 seconds for real AI image
    echo.
)

pause
