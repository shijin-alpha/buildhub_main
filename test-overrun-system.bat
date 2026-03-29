@echo off
echo ========================================
echo Cost and Time Overrun System Test
echo ========================================
echo.
echo This will test the overrun calculation system
echo without waiting for real construction completion.
echo.
echo Starting test...
echo.

REM Start the backend server if not running
echo Checking if backend server is running...
curl -s http://localhost:8000 >nul 2>&1
if %errorlevel% neq 0 (
    echo Starting backend server on port 8000...
    start "BuildHub Backend" cmd /k "node server.js"
    timeout /t 3 >nul
)

echo Backend server is running!
echo.
echo Opening test interface in browser...
echo.

REM Open the test page in default browser (use port 8000 for PHP execution)
start http://localhost:8000/run_overrun_test.php

echo.
echo ========================================
echo Test page opened in your browser!
echo ========================================
echo.
echo Instructions:
echo 1. Click "Run Complete Test Suite" button
echo 2. Wait for tests to complete (5-10 seconds)
echo 3. Review the results
echo.
echo Expected Results:
echo   - Time Overrun: 11.11%%
echo   - Cost Overrun: 6.0%%
echo   - All 6 tests should PASS
echo.
echo ========================================
pause
