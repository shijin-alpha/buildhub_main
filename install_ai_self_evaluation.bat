@echo off
REM ============================================================================
REM AI Self-Evaluation Framework - Installation Script
REM ============================================================================
REM Purpose: Install self-evaluating AI framework for cost/time overrun system
REM Compatibility: 100% backward compatible
REM ============================================================================

echo.
echo ========================================================================
echo AI Self-Evaluation Framework Installation
echo ========================================================================
echo.
echo This script will install the self-evaluating AI framework.
echo All changes are backward compatible and nullable.
echo.
echo Press Ctrl+C to cancel, or
pause

echo.
echo [1/3] Applying database schema...
echo.

mysql -u root -p buildhub < backend/database/ai_self_evaluation_schema.sql

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Database schema installation failed!
    echo Please check your MySQL connection and try again.
    pause
    exit /b 1
)

echo.
echo [2/3] Verifying installation...
echo.

mysql -u root -p buildhub -e "SELECT COUNT(*) as new_columns FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'buildhub' AND TABLE_NAME = 'construction_projects' AND COLUMN_NAME LIKE '%%predict%%';"

mysql -u root -p buildhub -e "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'buildhub' AND TABLE_NAME IN ('ai_evaluation_config', 'ai_evaluation_metrics', 'ai_prediction_audit');"

echo.
echo [3/3] Installation complete!
echo.
echo ========================================================================
echo Installation Summary
echo ========================================================================
echo.
echo ✓ Database schema extended with AI evaluation fields
echo ✓ Configuration table created with default thresholds
echo ✓ Metrics aggregation table created
echo ✓ Audit trail table created
echo ✓ Stored procedures installed
echo ✓ Triggers configured for automatic evaluation
echo ✓ Views created for easy metric access
echo.
echo ========================================================================
echo Next Steps
echo ========================================================================
echo.
echo 1. Test the installation:
echo    - Open test_ai_self_evaluation.html in your browser
echo.
echo 2. Integrate with frontend:
echo    - Call save_ai_predictions.php after AI analysis
echo    - Display metrics using get_ai_evaluation_metrics.php
echo.
echo 3. Review documentation:
echo    - AI_SELF_EVALUATION_FRAMEWORK.md
echo.
echo 4. Configure thresholds (optional):
echo    - Default: 5%% for both cost and time overruns
echo    - Adjust in ai_evaluation_config table
echo.
echo ========================================================================
echo.
pause
