@echo off
setlocal
title SPMS - Stop Servers
echo Stopping ngrok, Flask, Apache, MySQL...

taskkill /IM ngrok.exe /F >nul 2>&1
taskkill /IM httpd.exe /F >nul 2>&1
taskkill /IM mysqld.exe /F >nul 2>&1

REM Stop Flask windows / python serving app (best effort)
for /f "tokens=5" %%P in ('netstat -ano ^| findstr ":5000" ^| findstr "LISTENING"') do (
  taskkill /PID %%P /F >nul 2>&1
)

echo Done. Servers stopped.
pause
endlocal
