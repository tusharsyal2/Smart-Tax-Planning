@echo off
title Smart Tax Planning Assistance - Server Launcher
color 0B
cls
echo ===============================================================================
echo                SMART TAX PLANNING ASSISTANCE - SERVER LAUNCHER
echo ===============================================================================
echo.
echo Detecting PHP environment...

set PHP_BIN=C:\xampp\php\php.exe
if not exist "%PHP_BIN%" (
    where php >nul 2>nul
    if %errorlevel% equ 0 (
        set PHP_BIN=php
    ) else (
        color 0C
        echo [ERROR] PHP executable not found at C:\xampp\php\php.exe or in system PATH!
        echo Please ensure XAMPP is installed or PHP is added to your environment variables.
        pause
        exit /b 1
    )
)

echo [OK] Using PHP: %PHP_BIN%
echo.

:: Check if port 8000 is already running
netstat -ano | findstr :8000 | findstr LISTENING >nul 2>nul
if %errorlevel% equ 0 (
    echo [INFO] A server is already running on port 8000.
) else (
    echo Starting PHP built-in web server on http://127.0.0.1:8000 ...
    start /min "Smart Tax Planning Server" "%PHP_BIN%" -S 127.0.0.1:8000 -t "%~dp0"
    timeout /t 2 /nobreak >nul
)

echo [OK] Server active at http://127.0.0.1:8000
echo.
echo Launching default web browser...
start http://127.0.0.1:8000/
echo.
echo ===============================================================================
echo                        LOGIN CREDENTIALS & DEMO ACCOUNTS
echo ===============================================================================
echo.
echo  [ADMINISTRATOR ACCOUNT]
echo    URL:      http://127.0.0.1:8000/login.php
echo    Email:    admin@smarttax.local
echo    Password: Admin@123
echo    Switch:   http://127.0.0.1:8000/admin/switch.php?role=admin
echo.
echo  [SALARIED DEMO USER]
echo    Email:    salaried.demo@smarttax.local
echo    Password: Demo@123
echo.
echo  [FREELANCER / PROFESSIONAL]
echo    Email:    freelance.demo@smarttax.local
echo    Password: Demo@123
echo.
echo  [STUDENT DEMO USER]
echo    Email:    student.demo@smarttax.local
echo    Password: Demo@123
echo.
echo ===============================================================================
echo  Press any key to close this launcher window (server will keep running).
echo  To stop the server, double-click STOP_APP.bat
echo ===============================================================================
pause >nul
