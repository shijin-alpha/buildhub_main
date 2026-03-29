@echo off
echo ========================================
echo  Immutable Payment Audit System Setup
echo ========================================
echo.

echo Setting up the complete audit system...
echo.
php setup_immutable_audit_system.php

echo.
echo Testing the audit system...
echo.
php test_existing_payment_audit_trails.php

echo.
echo ========================================
echo  Setup Complete!
echo ========================================
echo.
echo You can now:
echo 1. View the demo: demo_immutable_audit_system.html
echo 2. Check audit trails via API endpoints
echo 3. Monitor new payments for automatic auditing
echo.
pause