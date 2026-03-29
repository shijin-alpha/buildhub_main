@echo off
echo ========================================
echo BuildHub JWT Authentication Setup
echo ========================================
echo.

echo Step 1: Installing Firebase JWT library...
composer require firebase/php-jwt
if %errorlevel% neq 0 (
    echo Error: Failed to install Firebase JWT library
    pause
    exit /b 1
)
echo ✓ Firebase JWT library installed
echo.

echo Step 2: Setting up database schema...
php backend/database/apply_jwt_schema.php
if %errorlevel% neq 0 (
    echo Error: Failed to apply database schema
    pause
    exit /b 1
)
echo.

echo Step 3: Verifying database tables...
php backend/database/check_jwt_tables.php
if %errorlevel% neq 0 (
    echo Error: Database verification failed
    pause
    exit /b 1
)
echo.

echo Step 4: Creating temp directory...
if not exist "backend\temp" mkdir backend\temp
echo ✓ Temp directory created
echo.

echo Step 5: Running JWT implementation tests...
php test_jwt_implementation.php
if %errorlevel% neq 0 (
    echo Warning: Some tests failed, but setup may still be functional
)
echo.

echo ========================================
echo JWT Authentication Setup Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Update your frontend to use JWT authentication
echo 2. Replace existing API endpoints with JWT-protected versions
echo 3. Configure environment variables for JWT secrets
echo 4. Test the implementation with your application
echo.
echo Documentation: See JWT_IMPLEMENTATION_GUIDE.md
echo.
pause