@echo off
setlocal
cd /d "%~dp0"
echo Stop the website with Ctrl+C in its server window first.
"%~dp0.local\offline-runtime\node_modules\@embedded-postgres\windows-x64\native\bin\pg_ctl.exe" -D "%LOCALAPPDATA%\RebadullaAnimalCare\offline-db" -m fast -w stop
if errorlevel 1 pause
