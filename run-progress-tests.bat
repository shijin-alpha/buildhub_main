@echo off
echo ========================================
echo Running Construction Progress Tests
echo ========================================
echo.

echo Installing dependencies...
call npm install
echo.

echo Installing Playwright browsers...
call npx playwright install chromium
echo.

echo Starting Node server...
start "Node Server" cmd /k "node server.js"
timeout /t 5 /nobreak >nul
echo.

echo Running Construction Progress Tests...
call npx playwright test tests/e2e/construction-progress.spec.js --headed --project=chromium
echo.

echo Running Detailed Progress Tests...
call npx playwright test tests/e2e/construction-progress-detailed.spec.js --headed --project=chromium
echo.

echo ========================================
echo Tests Complete!
echo ========================================
echo.
echo View test results in: test-results/
echo View HTML report: npx playwright show-report
echo.

pause
