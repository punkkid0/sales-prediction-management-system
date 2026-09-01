@echo off
setlocal EnableExtensions EnableDelayedExpansion
title SPMS - ngrok public link
cd /d "%~dp0.."

echo.
echo ============================================================
echo   Start ngrok public link
echo ============================================================
echo   1. Make sure 2-START-SERVERS.bat already ran
echo   2. Keep this window OPEN while sharing the link
echo ============================================================
echo.

REM Refresh PATH from registry for double-click launches
for /f "tokens=2*" %%A in ('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v Path 2^>nul') do set "PATH=%%B;%PATH%"
for /f "tokens=2*" %%A in ('reg query "HKCU\Environment" /v Path 2^>nul') do set "PATH=%%B;%PATH%"

REM Locate ngrok.exe
set "NGROK="
where ngrok >nul 2>&1 && for /f "delims=" %%I in ('where ngrok') do if not defined NGROK set "NGROK=%%I"

if not defined NGROK if exist "%LocalAppData%\Microsoft\WinGet\Packages\Ngrok.Ngrok_Microsoft.Winget.Source_8wekyb3d8bbwe\ngrok.exe" (
  set "NGROK=%LocalAppData%\Microsoft\WinGet\Packages\Ngrok.Ngrok_Microsoft.Winget.Source_8wekyb3d8bbwe\ngrok.exe"
)
if not defined NGROK if exist "%LocalAppData%\ngrok-bin\ngrok.exe" set "NGROK=%LocalAppData%\ngrok-bin\ngrok.exe"
if not defined NGROK if exist "%ProgramFiles%\ngrok\ngrok.exe" set "NGROK=%ProgramFiles%\ngrok\ngrok.exe"
if not defined NGROK if exist "%LocalAppData%\Programs\ngrok\ngrok.exe" set "NGROK=%LocalAppData%\Programs\ngrok\ngrok.exe"

REM Search WinGet packages folder broadly
if not defined NGROK (
  for /f "delims=" %%F in ('dir /s /b "%LocalAppData%\Microsoft\WinGet\Packages\ngrok.exe" 2^>nul') do (
    if not defined NGROK set "NGROK=%%F"
  )
)

if not defined NGROK (
  echo [ERROR] ngrok.exe not found.
  echo.
  echo Install it, then run this again:
  echo   winget install -e --id Ngrok.Ngrok
  echo Or run 1-SETUP.bat again.
  pause
  exit /b 1
)

echo [OK] ngrok: %NGROK%
"%NGROK%" version

REM Authtoken check
set "NGCFG=%LOCALAPPDATA%\ngrok\ngrok.yml"
set "HAS_TOKEN=0"
if exist "%NGCFG%" (
  findstr /I /C:"authtoken:" "%NGCFG%" >nul 2>&1 && set "HAS_TOKEN=1"
)

if "%HAS_TOKEN%"=="0" (
  echo.
  echo [NEED] Free ngrok authtoken - one time only
  echo   Open: https://dashboard.ngrok.com/get-started/your-authtoken
  echo   Copy token, paste below, press Enter
  echo.
  start "" "https://dashboard.ngrok.com/get-started/your-authtoken"
  set /p TOK=Paste authtoken here: 
  if "!TOK!"=="" (
    echo Cancelled.
    pause
    exit /b 1
  )
  "%NGROK%" config add-authtoken !TOK!
  if errorlevel 1 (
    echo [ERROR] Could not save authtoken.
    pause
    exit /b 1
  )
  echo [OK] Authtoken saved.
)

echo.
echo [..] Checking local website on port 80...
powershell -NoProfile -Command "try { $r=Invoke-WebRequest 'http://127.0.0.1/spms/' -UseBasicParsing -TimeoutSec 5; Write-Host '[OK] Website' $r.StatusCode; exit 0 } catch { Write-Host '[ERROR] Website down. Run 2-START-SERVERS.bat first.'; exit 1 }"
if errorlevel 1 (
  pause
  exit /b 1
)

powershell -NoProfile -Command "try { $r=Invoke-WebRequest 'http://127.0.0.1:5000/health' -UseBasicParsing -TimeoutSec 5; Write-Host '[OK] Flask' $r.Content } catch { Write-Host '[WARN] Flask offline - Forecasts will fail until you run 2-START-SERVERS.bat' }"

REM Kill old ngrok
taskkill /IM ngrok.exe /F >nul 2>&1
ping -n 2 127.0.0.1 >nul

echo.
echo ============================================================
echo   Starting tunnel to port 80
echo   KEEP THIS WINDOW OPEN
echo.
echo   After a few seconds, inspector opens:
echo     http://127.0.0.1:4040
echo   Copy the https://.... URL then add:
echo     /spms/
echo   Example:
echo     https://abc123.ngrok-free.dev/spms/
echo ============================================================
echo.

start "" cmd /c "ping -n 5 127.0.0.1 >nul & start http://127.0.0.1:4040"

"%NGROK%" http 80
set "EC=!errorlevel!"

echo.
echo ngrok stopped.
pause
endlocal & exit /b %EC%
