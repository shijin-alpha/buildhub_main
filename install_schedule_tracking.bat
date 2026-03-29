@echo off
echo ============================================
echo Schedule Tracking Enhancement Installation
echo ============================================
echo.

echo This script will install the schedule tracking enhancement to your BuildHub system.
echo.
echo IMPORTANT: This is a backward-compatible enhancement.
echo - All new fields are nullable
echo - Existing projects will continue to work
echo - No data will be lost
echo.

pause

echo.
echo Step 1: Checking MySQL connection...
echo.

mysql --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: MySQL command not found. Please ensure MySQL is installed and in your PATH.
    pause
    exit /b 1
)

echo MySQL found!
echo.

echo Step 2: Applying database schema...
echo.
echo Please enter your MySQL credentials:
set /p MYSQL_USER="MySQL Username (default: root): "
if "%MYSQL_USER%"=="" set MYSQL_USER=root

set /p MYSQL_DB="Database Name (default: buildhub): "
if "%MYSQL_DB%"=="" set MYSQL_DB=buildhub

echo.
echo Applying schema to database: %MYSQL_DB%
echo.

mysql -u %MYSQL_USER% -p %MYSQL_DB% < backend\database\schedule_tracking_schema.sql

if errorlevel 1 (
    echo.
    echo ERROR: Failed to apply database schema.
    echo Please check your credentials and try again.
    pause
    exit /b 1
)

echo.
echo ============================================
echo Installation Complete!
echo ============================================
echo.
echo The following components have been installed:
echo.
echo DATABASE:
echo   - 6 new columns added to construction_projects table
echo   - schedule_change_audit table created
echo   - calculate_time_overrun stored procedure created
echo   - Triggers for automatic locking and calculation
echo   - project_schedule_summary view created
echo.
echo API ENDPOINTS:
echo   - backend/api/contractor/update_planned_schedule.php
echo   - backend/api/contractor/update_actual_dates.php
echo   - backend/api/project/get_schedule_summary.php
echo.
echo FRONTEND COMPONENTS:
echo   - frontend/src/components/ContractorScheduleInput.jsx
echo   - frontend/src/components/HomeownerScheduleView.jsx
echo.
echo TESTING:
echo   - test_schedule_tracking.html (open in browser)
echo.
echo DOCUMENTATION:
echo   - SCHEDULE_TRACKING_IMPLEMENTATION.md
echo.
echo ============================================
echo Next Steps:
echo ============================================
echo.
echo 1. Open test_schedule_tracking.html in your browser
echo 2. Test the API endpoints with your projects
echo 3. Integrate the React components into your dashboards
echo 4. Review SCHEDULE_TRACKING_IMPLEMENTATION.md for details
echo.
echo All existing functionality remains unchanged!
echo.
pause
