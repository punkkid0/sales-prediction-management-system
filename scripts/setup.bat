@echo off
setlocal EnableExtensions EnableDelayedExpansion
title SPMS Setup - Install dependencies and database
cd /d "%~dp0.."
set "ROOT=%cd%"
set "XAMPP=C:\xampp"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "PHP=%XAMPP%\php\php.exe"
set "HTDOCS_LINK=%XAMPP%\htdocs\spms"
set "PUBLIC=%ROOT%\web\public"
set "ERR=0"

echo.
echo ============================================================
echo   Sales Prediction Management System - FIRST TIME SETUP
echo ============================================================
echo   Project folder:
echo   %ROOT%
echo.
echo   This will check/install:
echo     - Python + ML packages (Flask, torch, etc.)
echo     - XAMPP (Apache + MySQL + PHP) if missing
echo     - Database schema + seed data
echo     - Link the web app into XAMPP htdocs
echo     - ngrok (for public demo link)
echo ============================================================
echo.
pause

REM ---------- Admin tip ----------
net session >nul 2>&1
if errorlevel 1 (
  echo [WARN] Not running as Administrator.
  echo        Some installs (XAMPP/Python via winget) may ask for permission.
  echo.
)

REM ---------- Python ----------
echo.
echo [1/6] Checking Python...
where python >nul 2>&1
if errorlevel 1 (
  echo [INFO] Python not found. Trying winget install...
  winget install -e --id Python.Python.3.12 --accept-package-agreements --accept-source-agreements
  if errorlevel 1 (
    echo [ERROR] Could not install Python automatically.
    echo         Install Python 3.10+ from https://www.python.org/downloads/
    echo         IMPORTANT: tick "Add python.exe to PATH"
    set ERR=1
    goto :end
  )
  echo [INFO] Re-open this setup AFTER Python install finishes, if python is still not found.
  set "PATH=%LocalAppData%\Programs\Python\Python312;%LocalAppData%\Programs\Python\Python312\Scripts;%PATH%"
)

where python >nul 2>&1
if errorlevel 1 (
  echo [ERROR] Python still not on PATH. Close this window, open a NEW cmd, run setup again.
  set ERR=1
  goto :end
)

for /f "tokens=*" %%V in ('python -V 2^>^&1') do echo [OK] %%V

echo.
echo [2/6] Installing Python packages (this can take several minutes)...
python -m pip install --upgrade pip
python -m pip install -r "%ROOT%\ml_service\requirements.txt"
if errorlevel 1 (
  echo [ERROR] Failed to install requirements.txt
  set ERR=1
  goto :end
)

echo [INFO] Installing PyTorch CPU (for LSTM)...
python -m pip install torch --index-url https://download.pytorch.org/whl/cpu
if errorlevel 1 (
  echo [WARN] Torch install failed. Linear Regression may still work; LSTM may fall back.
) else (
  echo [OK] Torch installed
)

REM ---------- XAMPP ----------
echo.
echo [3/6] Checking XAMPP (Apache + MySQL + PHP)...
if not exist "%XAMPP%\xampp-control.exe" (
  echo [INFO] XAMPP not found at %XAMPP%
  echo [INFO] Installing XAMPP 8.2 via winget (may take a while / need admin)...
  winget install -e --id ApacheFriends.Xampp.8.2 --accept-package-agreements --accept-source-agreements
  if not exist "%XAMPP%\xampp-control.exe" (
    echo [ERROR] XAMPP install not detected.
    echo         Install manually from https://www.apachefriends.org/ and re-run setup.
    set ERR=1
    goto :end
  )
)
echo [OK] XAMPP found at %XAMPP%

if not exist "%MYSQL%" (
  echo [ERROR] mysql.exe missing: %MYSQL%
  set ERR=1
  goto :end
)

REM ---------- Start MySQL ----------
echo.
echo [4/6] Starting MySQL and importing database...
call :ensure_mysql
if errorlevel 1 (
  echo [ERROR] Could not start/connect to MySQL.
  echo         Open XAMPP Control Panel and Start MySQL, then re-run setup.
  set ERR=1
  goto :end
)

echo [INFO] Importing schema.sql ...
"%MYSQL%" -u root -h 127.0.0.1 < "%ROOT%\database\schema.sql"
if errorlevel 1 (
  echo [ERROR] schema import failed
  set ERR=1
  goto :end
)

echo [INFO] Importing seed.sql ...
"%MYSQL%" -u root -h 127.0.0.1 < "%ROOT%\database\seed.sql"
if errorlevel 1 (
  echo [ERROR] seed import failed
  set ERR=1
  goto :end
)

REM Fix demo passwords with PHP password_hash if PHP available
if exist "%PHP%" (
  echo [INFO] Refreshing demo user passwords with PHP...
  "%PHP%" -r "$h=password_hash('password123', PASSWORD_DEFAULT); file_put_contents(getenv('TEMP').'/spms_pwd.sql', \"USE sales_prediction_db; UPDATE users SET password_hash='\".$h.\"';\");"
  if exist "%TEMP%\spms_pwd.sql" (
    "%MYSQL%" -u root -h 127.0.0.1 < "%TEMP%\spms_pwd.sql"
    del "%TEMP%\spms_pwd.sql" >nul 2>&1
  )
)

echo [OK] Database sales_prediction_db ready

REM ---------- htdocs link ----------
echo.
echo [5/6] Linking web app into XAMPP htdocs as /spms ...
if exist "%HTDOCS_LINK%" (
  rmdir "%HTDOCS_LINK%" >nul 2>&1
  if exist "%HTDOCS_LINK%" (
    echo [WARN] Could not replace existing %HTDOCS_LINK%
    echo        Delete it manually if the site does not load.
  )
)
if not exist "%HTDOCS_LINK%" (
  mklink /J "%HTDOCS_LINK%" "%PUBLIC%"
  if errorlevel 1 (
    echo [WARN] mklink failed. Trying copy fallback is skipped.
    echo        Manually link %PUBLIC% to %HTDOCS_LINK%
  ) else (
    echo [OK] Linked %HTDOCS_LINK% -^> %PUBLIC%
  )
)

REM ---------- ngrok ----------
echo.
echo [6/6] Checking ngrok...
where ngrok >nul 2>&1
if errorlevel 1 (
  echo [INFO] Installing ngrok via winget...
  winget install -e --id Ngrok.Ngrok --accept-package-agreements --accept-source-agreements
)
where ngrok >nul 2>&1
if errorlevel 1 (
  echo [WARN] ngrok not on PATH yet. Open a NEW terminal later, or use 3-START-NGROK.bat after PATH refresh.
) else (
  for /f "tokens=*" %%V in ('ngrok version 2^>^&1') do echo [OK] %%V
  echo.
  echo [IMPORTANT] One-time ngrok login:
  echo   1. Create free account: https://dashboard.ngrok.com/signup
  echo   2. Copy authtoken:     https://dashboard.ngrok.com/get-started/your-authtoken
  echo   3. Run:
  echo      ngrok config add-authtoken YOUR_TOKEN_HERE
)

echo.
echo [OPTIONAL] Demo sales history for better forecasts is already helped by
echo            trained models in ml_service\models\
echo            If you want to regenerate history later:
echo   cd ml_service
echo   python generate_seed_sales.py --clear --days 300
echo   python train.py

echo.
echo ============================================================
echo   SETUP FINISHED
echo ============================================================
echo   Next steps for him:
echo     1. Double-click  2-START-SERVERS.bat
echo     2. Open          http://localhost/spms/
echo     3. Optional:     3-START-NGROK.bat  (public link)
echo.
echo   Demo logins (password for all: password123)
echo     admin@spms.local
echo     manager@spms.local
echo     staff1@spms.local
echo ============================================================
goto :end

:ensure_mysql
REM Try connect; if fail start mysqld
"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if not errorlevel 1 exit /b 0

echo [INFO] Starting MySQL...
start "" /MIN "%XAMPP%\mysql_start.bat"
timeout /t 6 /nobreak >nul

REM Also try direct mysqld if still down
"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if not errorlevel 1 exit /b 0

start "" "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
timeout /t 6 /nobreak >nul

"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if errorlevel 1 exit /b 1
exit /b 0

:end
echo.
if "%ERR%"=="1" (
  echo Setup completed with ERRORS. Fix the messages above and re-run.
) else (
  echo Setup OK. You can close this window.
)
pause
endlocal
exit /b %ERR%
