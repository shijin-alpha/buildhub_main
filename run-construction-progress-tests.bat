@echo off
echo ========================================
echo Construction Progress Button Tests
echo ========================================
echo.

echo Starting tests for Construction Progress buttons...
echo.

REM Run the construction progress button tests
npx playwright test tests/e2e/homeowner-dashboard.spec.js -g "Construction Progress Buttons"

echo.
echo ========================================
echo Tests Complete!
echo ========================================
echo.
echo To view the HTML report, run:
echo npx playwright show-report
echo.

pause
