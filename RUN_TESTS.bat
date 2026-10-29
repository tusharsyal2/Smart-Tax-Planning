@echo off
title Smart Tax Planning - Automated Test Suite
color 0A
cls
echo ===============================================================================
echo                RUNNING SMART TAX PLANNING TEST SUITE
echo ===============================================================================
echo.

set PHP_BIN=C:\xampp\php\php.exe
if not exist "%PHP_BIN%" (
    where php >nul 2>nul
    if %errorlevel% equ 0 (
        set PHP_BIN=php
    ) else (
        echo [ERROR] PHP executable not found!
        pause
        exit /b 1
    )
)

"%PHP_BIN%" "%~dp0tests\run_tests.php"
echo.
pause
