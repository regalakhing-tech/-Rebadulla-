@echo off
setlocal
cd /d "%~dp0"
if not exist ".env.supabase" (
    echo The saved .env.supabase configuration is missing.
    pause
    exit /b 1
)
where php >nul 2>nul
if errorlevel 1 (
    echo PHP was not found. Add PHP to PATH and try again.
    pause
    exit /b 1
)
copy /Y ".env.supabase" ".env" >nul
if errorlevel 1 exit /b 1
set "APP_URL=http://127.0.0.1:8000"
echo Rebadulla Animal Care - hosted Supabase database
echo Internet and access to the Supabase PostgreSQL port are required.
echo Stop any other local server before starting this mode.
echo Open http://127.0.0.1:8000/ in your browser.
echo.
php -S 127.0.0.1:8000 router.php
if errorlevel 1 pause
