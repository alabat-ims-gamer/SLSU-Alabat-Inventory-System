@echo off
setlocal

:: Target directory in XAMPP
set "XAMPP_HTDOCS=C:\xampp\htdocs\slsu-inventory"

:: Current project path (where this script lives)
set "PROJECT_DIR=%~dp0"
:: Remove trailing backslash if present
if "%PROJECT_DIR:~-1%"=="\" set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"

echo ===================================================
echo   SLSU-Alabat IMS - XAMPP Link Setup
echo ===================================================
echo.
echo Project Directory : %PROJECT_DIR%
echo Target in XAMPP   : %XAMPP_HTDOCS%
echo.

:: Check if C:\xampp\htdocs exists
if not exist "C:\xampp\htdocs" (
    echo [ERROR] Could not find C:\xampp\htdocs.
    echo Please make sure XAMPP is installed at C:\xampp.
    echo.
    pause
    exit /b 1
)

:: If link or folder already exists, remove junction or prompt
if exist "%XAMPP_HTDOCS%" (
    echo [INFO] Found existing link/folder at %XAMPP_HTDOCS%.
    echo Updating junction...
    rmdir "%XAMPP_HTDOCS%" 2>nul
)

:: Create Windows NTFS Directory Junction
mklink /J "%XAMPP_HTDOCS%" "%PROJECT_DIR%"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [SUCCESS] Junction created successfully!
    echo.
    echo You can now access your project in XAMPP without copying files:
    echo   http://localhost/slsu-inventory/
    echo.
) else (
    echo.
    echo [ERROR] Failed to create junction link.
    echo Please try right-clicking this script and selecting "Run as administrator".
    echo.
)

pause
