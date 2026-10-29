@echo off
title Stop Smart Tax Planning Server
color 0C
cls
echo ===============================================================================
echo                STOPPING SMART TAX PLANNING SERVER
echo ===============================================================================
echo.

for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8000 ^| findstr LISTENING') do (
    echo Terminating process PID %%a on port 8000...
    taskkill /F /PID %%a >nul 2>nul
)

echo.
echo [OK] Server stopped successfully.
timeout /t 2 /nobreak >nul
exit /b 0
