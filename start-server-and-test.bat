@echo off
echo ========================================
echo BuildHub Playwright Testing
echo ========================================
echo.

echo Step 1: Checking if server is already running...
netstat -ano | findstr :3000 >nul
if %ERRORLEVEL% EQU 0 (
    echo Server is already running on port 3000
    echo.
    goto :run_tests
)

echo Server not running. Starting Node server...
start "BuildHub Server" cmd /k "node server.js"
echo Waiting for server to start...
timeout /t 10 /nobreak >nul
echo.

:run_tests
echo Step 2: Running simple login test first...
call npm run test:simple
echo.

echo Step 3: Do you want to run full progress tests? (Y/N)
set /p continue="Continue with full tests? "
if /i "%continue%"=="Y" (
    echo Running full progress tests...
    call npm run test:progress
)

echo.
echo ========================================
echo Tests Complete!
echo ========================================
echo View HTML report: npx playwright show-report
echo.
pause
