@echo off
REM ============================================================
REM  Kizora - full dev mode: Laravel server + Vite hot-reload
REM  Opens two windows. Close them (or Ctrl+C) to stop.
REM ============================================================
cd /d "%~dp0"
start "Kizora - Laravel" cmd /k "php artisan serve --host=127.0.0.1 --port=8000"
start "Kizora - Vite"    cmd /k "npm run dev"
echo Launched Laravel (http://127.0.0.1:8000) and Vite in separate windows.
