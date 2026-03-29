@echo off
echo Checking if Node server is running on port 3000...
echo.

netstat -ano | findstr :3000

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Server is running on port 3000
) else (
    echo.
    echo Server is NOT running on port 3000
    echo Please start the server with: node server.js
)

echo.
pause
