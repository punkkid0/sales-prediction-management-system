@echo off
setlocal EnableExtensions EnableDelayedExpansion
title SPMS - Start ngrok public link
cd /d "%~dp0.."

echo.
echo ============================================================
echo   Start ngrok (public HTTPS link to this PC)
echo ============================================================
echo.
echo   Prerequisites:
echo     - Run 2-START-SERVERS.bat first
echo     - Free ngrok authtoken configured once
echo.

where ngrok >nul 2>&1
if errorlevel 1 (
  echo [ERROR] ngrok not found on PATH.
  echo         Run 1-SETUP.bat, then open a NEW Command Prompt.
  echo         Or: winget install Ngrok.Ngrok
  pause
  exit /b 1
)

set "NGCFG=%LOCALAPPDATA%\ngrok\ngrok.yml"
findstr /I /C:"authtoken:" "%NGCFG%" >nul 2>&1
if errorlevel 1 (
  echo [WARN] No ngrok authtoken configured yet.
  echo.
  echo   1. Open: https://dashboard.ngrok.com/get-started/your-authtoken
  echo   2. Copy the token
  echo   3. Paste it below
  echo.
  set /p TOK=Authtoken: 
  if "!TOK!"=="" (
    echo Cancelled - no token entered.
    pause
    exit /b 1
  )
  ngrok config add-authtoken !TOK!
  if errorlevel 1 (
    echo [ERROR] Failed to save authtoken.
    pause
    exit /b 1
  )
  echo [OK] Authtoken saved.
  echo.
)

echo [..] Checking local site...
powershell -NoProfile -Command "try { $r=Invoke-WebRequest 'http://127.0.0.1/spms/' -UseBasicParsing -TimeoutSec 4; Write-Host '[OK] Web' $r.StatusCode; exit 0 } catch { Write-Host '[ERROR] Site not up - run 2-START-SERVERS.bat first'; exit 1 }"
if errorlevel 1 (
  pause
  exit /b 1
)

powershell -NoProfile -Command "try { $r=Invoke-WebRequest 'http://127.0.0.1:5000/health' -UseBasicParsing -TimeoutSec 4; Write-Host '[OK] Flask' $r.Content } catch { Write-Host '[WARN] Flask offline - Forecasts will not work until Flask is started' }"

echo.
echo ============================================================
echo   Starting ngrok on port 80
echo   KEEP THIS WINDOW OPEN
echo.
echo   Then open http://127.0.0.1:4040 to copy the HTTPS URL
echo   Share with him:  https://xxxxx.ngrok-free.app/spms/
echo ============================================================
echo.

start "" cmd /c "timeout /t 5 /nobreak >nul && start http://127.0.0.1:4040"

ngrok http 80

echo.
echo ngrok stopped.
pause
endlocal
