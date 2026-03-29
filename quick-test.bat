@echo off
echo ========================================
echo Quick Test - Cost and Time Overrun
echo ========================================
echo.

REM Check if server is running
curl -s http://localhost:8000 >nul 2>&1
if %errorlevel% neq 0 (
    echo Starting backend server...
    start "BuildHub Backend" cmd /k "node server.js"
    echo Waiting for server to start...
    timeout /t 3 >nul
)

echo Opening test in browser...
start http://localhost:8000/run_overrun_test.php

echo.
echo ========================================
echo Test is running in your browser!
echo ========================================
echo.
echo Expected Results:
echo   Time Overrun: 11.11%%
echo   Cost Overrun: 6.0%%
echo   All tests: PASSED
echo ========================================
