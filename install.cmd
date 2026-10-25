@echo off
REM NovaPHP one-click installer (Windows)
REM Copies default config, .env and error views into the current project.
setlocal EnableExtensions

set "SCRIPT_DIR=%~dp0"
set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

where php >nul 2>nul
if errorlevel 1 (
    echo PHP is not in PATH. Install PHP 8.3+ and try again.
    exit /b 1
)

for /f "delims=" %%V in ('php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;"') do set "PHP_VER=%%V"
for /f "tokens=1,2 delims=." %%A in ("%PHP_VER%") do (
    set "PHP_MAJOR=%%A"
    set "PHP_MINOR=%%B"
)
if %PHP_MAJOR% LSS 8 (
    echo PHP 8.3+ is required. Found %PHP_VER%.
    exit /b 1
)
if %PHP_MAJOR% EQU 8 if %PHP_MINOR% LSS 3 (
    echo PHP 8.3+ is required. Found %PHP_VER%.
    exit /b 1
)

php "%SCRIPT_DIR%\bin\nova" install %*
exit /b %ERRORLEVEL%
