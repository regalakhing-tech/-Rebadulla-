@echo off
setlocal
cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 (
    echo PHP was not found. Add PHP to PATH and try again.
    pause
    exit /b 1
)

set "PG_CTL=%~dp0.local\offline-runtime\node_modules\@embedded-postgres\windows-x64\native\bin\pg_ctl.exe"
set "PG_DATA=%LOCALAPPDATA%\RebadullaAnimalCare\offline-db"
if not exist "%PG_CTL%" (
    echo The offline PostgreSQL runtime is missing from .local\offline-runtime.
    pause
    exit /b 1
)
if not exist "%PG_DATA%\PG_VERSION" (
    echo The offline database is missing from %PG_DATA%.
    pause
    exit /b 1
)
if not exist ".env.offline" (
    echo The saved .env.offline configuration is missing.
    pause
    exit /b 1
)

"%PG_CTL%" -D "%PG_DATA%" status >nul 2>nul
if errorlevel 1 (
    "%PG_CTL%" -D "%PG_DATA%" -l "%LOCALAPPDATA%\RebadullaAnimalCare\postgres.log" -w -t 30 start
    if errorlevel 1 (
        echo Could not start the offline database. Check the PostgreSQL log.
        pause
        exit /b 1
    )
)
copy /Y ".env.offline" ".env" >nul
if errorlevel 1 exit /b 1
set "APP_URL=http://127.0.0.1:8000"
echo Rebadulla Animal Care - offline database
echo Open http://127.0.0.1:8000/ in your browser.
echo Keep this window open. Press Ctrl+C to stop the website.
echo Login credentials are saved in .env.offline.
echo.
php -S 127.0.0.1:8000 router.php
if errorlevel 1 pause
