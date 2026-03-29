@echo off
echo Starting PHP development server...
echo Server will be available at: http://localhost:8000
echo Press Ctrl+C to stop the server
cd /d "%~dp0"
php -S localhost:8000
pause