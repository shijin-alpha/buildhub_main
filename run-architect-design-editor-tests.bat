@echo off
echo ========================================
echo Running Architect Design Editor Tests
echo ========================================
echo.
echo Testing with credentials:
echo Email: saviojoseph2026@mca.ajce.in
echo Password: Savio@123
echo.
echo Starting tests...
echo.

npx playwright test tests/e2e/architect-design-editor.spec.js --reporter=html

echo.
echo ========================================
echo Tests completed!
echo ========================================
echo.
echo To view the test report, run:
echo npx playwright show-report
echo.
pause
