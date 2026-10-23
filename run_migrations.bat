@echo off
setlocal enabledelayedexpansion

echo ===================================================
echo   SLSU-Alabat IMS - Automated Database Migration
echo ===================================================
echo.

:: Detect PHP executable
set "PHP_BIN=php"
if exist "C:\xampp\php\php.exe" (
    set "PHP_BIN=C:\xampp\php\php.exe"
)

echo [*] Using PHP: %PHP_BIN%
echo [*] Executing automated incremental migration...
echo.

"%PHP_BIN%" "%~dp0scripts\migrate.php"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [SUCCESS] Database migrations applied successfully!
    echo.
) else (
    echo.
    echo [ERROR] Migration failed. Please ensure MySQL is started in XAMPP.
    echo.
)

pause
