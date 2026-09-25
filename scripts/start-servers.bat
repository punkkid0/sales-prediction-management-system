@echo off
setlocal EnableExtensions EnableDelayedExpansion
title SPMS - Start Servers
cd /d "%~dp0.."
set "ROOT=%CD%"
set "XAMPP=C:\xampp"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "MYSQLD=%XAMPP%\mysql\bin\mysqld.exe"
set "HTTPD=%XAMPP%\apache\bin\httpd.exe"
set "ML=%ROOT%\ml_service"
set "PYEXE="

echo.
echo ============================================================
echo   Starting SPMS servers
echo ============================================================
echo   %ROOT%
echo.

REM Find Python
where python >nul 2>&1 && set "PYEXE=python"
if not defined PYEXE if exist "%LocalAppData%\Programs\Python\Python314\python.exe" set "PYEXE=%LocalAppData%\Programs\Python\Python314\python.exe"
if not defined PYEXE if exist "%LocalAppData%\Programs\Python\Python312\python.exe" set "PYEXE=%LocalAppData%\Programs\Python\Python312\python.exe"
if not defined PYEXE if exist "C:\Python312\python.exe" set "PYEXE=C:\Python312\python.exe"

if not exist "%XAMPP%\xampp-control.exe" (
  echo [ERROR] XAMPP not found at %XAMPP%
  echo         Run 1-SETUP.bat first.
  pause
  exit /b 1
)

REM ----- MySQL -----
"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if not errorlevel 1 goto mysql_ok
echo [..] Starting MySQL...
if not exist "%MYSQLD%" (
  echo [ERROR] mysqld.exe missing. Run 1-SETUP.bat first.
  pause
  exit /b 1
)
start "SPMS-MySQL" /MIN "%MYSQLD%" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
ping -n 7 127.0.0.1 >nul
"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
  echo [ERROR] MySQL did not start.
  echo         Open XAMPP Control Panel and click Start on MySQL.
  start "" "%XAMPP%\xampp-control.exe"
  pause
  exit /b 1
)
:mysql_ok
echo [OK] MySQL ready

REM ----- Apache -----
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if not errorlevel 1 goto apache_ok
echo [..] Starting Apache...
if not exist "%HTTPD%" (
  echo [ERROR] httpd.exe missing. Run 1-SETUP.bat first.
  pause
  exit /b 1
)
start "SPMS-Apache" /MIN "%HTTPD%" -d "%XAMPP%\apache"
ping -n 4 127.0.0.1 >nul
:apache_ok
echo [OK] Apache ready

REM ----- Flask -----
if not defined PYEXE (
  echo [ERROR] Python not found. Run 1-SETUP.bat first.
  pause
  exit /b 1
)

echo [..] Starting Flask API with: %PYEXE%
REM Free port 5000 if busy
for /f "tokens=5" %%P in ('netstat -ano ^| findstr ":5000" ^| findstr "LISTENING"') do (
  echo [..] Stopping old PID %%P on port 5000
  taskkill /PID %%P /F >nul 2>&1
)
ping -n 2 127.0.0.1 >nul

start "SPMS-Flask" /D "%ML%" /MIN "%PYEXE%" -u app.py
ping -n 8 127.0.0.1 >nul

echo.
echo Checking services...
powershell -NoProfile -Command "try { Write-Host '[OK] Web' (Invoke-WebRequest 'http://127.0.0.1/spms/' -UseBasicParsing -TimeoutSec 5).StatusCode } catch { Write-Host '[WARN] Web not ready yet' }"
powershell -NoProfile -Command "try { Write-Host '[OK] Flask' (Invoke-WebRequest 'http://127.0.0.1:5000/health' -UseBasicParsing -TimeoutSec 8).Content } catch { Write-Host '[WARN] Flask not ready yet - wait 5s and refresh browser' }"

echo.
echo ============================================================
echo   SERVERS STARTED
echo ============================================================
echo   Open:  http://localhost/spms/
echo.
echo   Logins - password: password123
echo     manager@spms.local
echo     admin@spms.local
echo.
echo   If Forecasts says the engine is offline, wait 10 seconds
echo   and refresh the page. Flask can take a moment to load.
echo.
echo   Public link next:
echo     Double-click  3-START-NGROK.bat
echo ============================================================
echo.
start "" "http://localhost/spms/index.php?page=login"
pause
endlocal
exit /b 0
