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

npx playwright test tests/e2e/architect-design-editor.spec.js --headed

echo.
echo ========================================
echo Test execution completed!
echo ========================================
pause
