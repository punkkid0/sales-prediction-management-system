@echo off
setlocal EnableExtensions
title SPMS - Start Servers
cd /d "%~dp0.."
set "ROOT=%cd%"
set "XAMPP=C:\xampp"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "ML=%ROOT%\ml_service"

echo.
echo ============================================================
echo   Starting SPMS servers (MySQL + Apache + Flask)
echo ============================================================
echo.

REM ----- MySQL -----
"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
  echo [..] Starting MySQL...
  if exist "%XAMPP%\mysql\bin\mysqld.exe" (
    start "SPMS-MySQL" /MIN "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
  ) else (
    echo [ERROR] XAMPP MySQL not found. Run 1-SETUP.bat first.
    pause
    exit /b 1
  )
  timeout /t 5 /nobreak >nul
) else (
  echo [OK] MySQL already running
)

"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
  echo [ERROR] MySQL did not start. Open XAMPP Control Panel and Start MySQL.
  start "" "%XAMPP%\xampp-control.exe"
  pause
  exit /b 1
)
echo [OK] MySQL ready

REM ----- Apache -----
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 (
  echo [..] Starting Apache...
  if exist "%XAMPP%\apache\bin\httpd.exe" (
    start "SPMS-Apache" /MIN "%XAMPP%\apache\bin\httpd.exe" -d "%XAMPP%\apache"
  ) else (
    echo [ERROR] Apache not found. Run 1-SETUP.bat first.
    pause
    exit /b 1
  )
  timeout /t 3 /nobreak >nul
) else (
  echo [OK] Apache already running
)

REM ----- Flask -----
echo [..] Starting Flask prediction API...
REM stop old flask on 5000 if we can (best-effort: start new window always with unique title)
for /f "tokens=5" %%P in ('netstat -ano ^| findstr ":5000" ^| findstr "LISTENING"') do (
  echo [..] Stopping old process on port 5000 PID %%P
  taskkill /PID %%P /F >nul 2>&1
)
timeout /t 1 /nobreak >nul

where python >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Python not found on PATH. Run 1-SETUP.bat first.
  pause
  exit /b 1
)

start "SPMS-Flask" /MIN cmd /c "cd /d \"%ML%\" && python -u app.py"
timeout /t 5 /nobreak >nul

REM Health checks
echo.
echo Checking services...
curl -s -o nul -w "Apache/web: %%{http_code}\n" http://127.0.0.1/spms/ 2>nul
if errorlevel 1 (
  powershell -NoProfile -Command "try { (Invoke-WebRequest 'http://127.0.0.1/spms/' -UseBasicParsing -TimeoutSec 5).StatusCode } catch { 'FAIL' }"
)

powershell -NoProfile -Command "try { (Invoke-WebRequest 'http://127.0.0.1:5000/health' -UseBasicParsing -TimeoutSec 8).Content } catch { 'Flask FAIL - wait a few seconds and refresh' }"

echo.
echo ============================================================
echo   SERVERS STARTED
echo ============================================================
echo   Open the app:
echo     http://localhost/spms/
echo.
echo   Login examples:
echo     manager@spms.local  /  password123
echo     admin@spms.local    /  password123
echo.
echo   For a PUBLIC link (send to someone online):
echo     Double-click  3-START-NGROK.bat
echo.
echo   Keep this PC awake. Do not close Flask/Apache windows.
echo ============================================================
echo.
start "" "http://localhost/spms/index.php?page=login"
pause
endlocal
