@echo off
title Portal Server
color 0A
echo ================================================
echo          PORTAL SERVER STARTER
echo ================================================
echo.
echo IMPORTANT: Make sure XAMPP Control Panel is running!
echo - Start Apache
echo - Start MySQL
echo.
echo Once started, open your browser and go to:
echo http://localhost:8000
echo.
echo ================================================
echo.

cd /d "C:\Users\HP\OneDrive\Documents\Portal"
echo Starting optimized server...
"C:\xampp\php\php.exe" -dopcache.enable=1 -dopcache.enable_cli=1 artisan serve --host=0.0.0.0 --port=8000
