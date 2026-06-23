@echo off
REM ============================================================
REM  Kizora - start the Laravel dev server
REM  Uses XAMPP's PHP (now 8.4) on the PATH.
REM ============================================================
cd /d "%~dp0"
echo Starting Kizora on http://127.0.0.1:8000  (Ctrl+C to stop)
php artisan serve --host=127.0.0.1 --port=8000
