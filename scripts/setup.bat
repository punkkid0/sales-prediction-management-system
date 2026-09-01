@echo off
setlocal EnableExtensions EnableDelayedExpansion
title SPMS Setup
cd /d "%~dp0.."
set "ROOT=%CD%"
set "XAMPP=C:\xampp"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "PHP=%XAMPP%\php\php.exe"
set "HTDOCS_LINK=%XAMPP%\htdocs\spms"
set "PUBLIC=%ROOT%\web\public"
set "ERR=0"

echo.
echo ============================================================
echo   SPMS FIRST-TIME SETUP
echo ============================================================
echo   Folder: %ROOT%
echo.
echo   Will check/install Python packages, XAMPP/MySQL,
echo   import database, link web app, check ngrok.
echo ============================================================
echo.
if /I not "%SKIP_PAUSE%"=="1" pause

echo.
net session >nul 2>&1
if errorlevel 1 (
  echo [WARN] Not running as Administrator.
  echo        Winget installs may ask for permission.
  echo.
)

REM ============================================================
REM 1) PYTHON
REM ============================================================
echo [1/6] Checking Python...
set "PYEXE="
where python >nul 2>&1 && set "PYEXE=python"
if not defined PYEXE where py >nul 2>&1 && set "PYEXE=py -3"
if not defined PYEXE (
  if exist "%LocalAppData%\Programs\Python\Python312\python.exe" set "PYEXE=%LocalAppData%\Programs\Python\Python312\python.exe"
)
if not defined PYEXE (
  if exist "%LocalAppData%\Programs\Python\Python314\python.exe" set "PYEXE=%LocalAppData%\Programs\Python\Python314\python.exe"
)
if not defined PYEXE (
  if exist "C:\Python312\python.exe" set "PYEXE=C:\Python312\python.exe"
)

if not defined PYEXE (
  echo [INFO] Python not found. Installing with winget...
  winget install -e --id Python.Python.3.12 --accept-package-agreements --accept-source-agreements
  if exist "%LocalAppData%\Programs\Python\Python312\python.exe" (
    set "PYEXE=%LocalAppData%\Programs\Python\Python312\python.exe"
    set "PATH=%LocalAppData%\Programs\Python\Python312;%LocalAppData%\Programs\Python\Python312\Scripts;%PATH%"
  )
)

if not defined PYEXE (
  echo [ERROR] Python still not found.
  echo         Install from https://www.python.org/downloads/
  echo         Enable: Add python.exe to PATH
  echo         Then run this setup again.
  set ERR=1
  goto :done
)

echo [OK] Using: %PYEXE%
%PYEXE% -V
if errorlevel 1 (
  echo [ERROR] Python failed to run.
  set ERR=1
  goto :done
)

REM ============================================================
REM 2) PIP PACKAGES
REM ============================================================
echo.
echo [2/6] Installing Python packages - please wait...
%PYEXE% -m pip install --upgrade pip
if errorlevel 1 (
  echo [WARN] pip upgrade failed - continuing...
)
%PYEXE% -m pip install -r "%ROOT%\ml_service\requirements.txt"
if errorlevel 1 (
  echo [ERROR] Could not install ml_service\requirements.txt
  set ERR=1
  goto :done
)
echo [OK] Core Python packages installed

echo [INFO] Installing PyTorch CPU for LSTM - please wait...
%PYEXE% -m pip install torch --index-url https://download.pytorch.org/whl/cpu
if errorlevel 1 (
  echo [WARN] Torch install failed. LSTM may use fallback. Continuing...
) else (
  echo [OK] Torch installed
)

REM ============================================================
REM 3) XAMPP
REM ============================================================
echo.
echo [3/6] Checking XAMPP...
if not exist "%XAMPP%\xampp-control.exe" (
  echo [INFO] XAMPP not found. Installing via winget - this can take a while...
  winget install -e --id ApacheFriends.Xampp.8.2 --accept-package-agreements --accept-source-agreements
)
if not exist "%XAMPP%\xampp-control.exe" (
  echo [ERROR] XAMPP still not at %XAMPP%
  echo         Download from https://www.apachefriends.org/
  echo         Install to C:\xampp then run setup again.
  set ERR=1
  goto :done
)
echo [OK] XAMPP found

if not exist "%MYSQL%" (
  echo [ERROR] Missing MySQL client: %MYSQL%
  set ERR=1
  goto :done
)

REM ============================================================
REM 4) DATABASE
REM ============================================================
echo.
echo [4/6] Starting MySQL and importing database...
call :start_mysql
if errorlevel 1 (
  echo [ERROR] Cannot connect to MySQL.
  echo         Open C:\xampp\xampp-control.exe
  echo         Click Start next to MySQL
  echo         Then run this setup again.
  start "" "%XAMPP%\xampp-control.exe"
  set ERR=1
  goto :done
)

echo [INFO] Importing schema.sql ...
"%MYSQL%" -u root -h 127.0.0.1 --force < "%ROOT%\database\schema.sql"
if errorlevel 1 (
  echo [ERROR] schema.sql import failed
  set ERR=1
  goto :done
)
echo [OK] schema imported

echo [INFO] Importing seed.sql ...
"%MYSQL%" -u root -h 127.0.0.1 --force < "%ROOT%\database\seed.sql"
if errorlevel 1 (
  echo [ERROR] seed.sql import failed
  set ERR=1
  goto :done
)
echo [OK] seed imported

if exist "%PHP%" (
  echo [INFO] Resetting demo passwords to password123 ...
  for /f "usebackq delims=" %%F in (`"%PHP%" "%ROOT%\scripts\fix_passwords.php"`) do (
    if exist "%%F" (
      "%MYSQL%" -u root -h 127.0.0.1 < "%%F"
      del "%%F" >nul 2>&1
    )
  )
)

echo [OK] Database ready: sales_prediction_db

REM ============================================================
REM 5) HTDOCS LINK
REM ============================================================
echo.
echo [5/6] Linking site to http://localhost/spms/ ...
if exist "%HTDOCS_LINK%" (
  rmdir "%HTDOCS_LINK%" >nul 2>&1
)
if exist "%HTDOCS_LINK%" (
  echo [WARN] Could not remove old link: %HTDOCS_LINK%
  echo        Delete that folder/link manually if the site is wrong.
) else (
  mklink /J "%HTDOCS_LINK%" "%PUBLIC%"
  if errorlevel 1 (
    echo [WARN] mklink failed.
    echo        As Administrator run:
    echo        mklink /J "%HTDOCS_LINK%" "%PUBLIC%"
  ) else (
    echo [OK] Linked htdocs\spms to web\public
  )
)

REM ============================================================
REM 6) NGROK
REM ============================================================
echo.
echo [6/6] Checking ngrok...
where ngrok >nul 2>&1
if errorlevel 1 (
  echo [INFO] Installing ngrok...
  winget install -e --id Ngrok.Ngrok --accept-package-agreements --accept-source-agreements
)
where ngrok >nul 2>&1
if errorlevel 1 (
  echo [WARN] ngrok not on PATH yet. Open a NEW Command Prompt later.
) else (
  echo [OK] ngrok is available
  echo.
  echo For PUBLIC links, one-time steps:
  echo   1. Open https://dashboard.ngrok.com/get-started/your-authtoken
  echo   2. Run: ngrok config add-authtoken YOUR_TOKEN
)

echo.
echo ============================================================
if "%ERR%"=="0" (
  echo   SETUP FINISHED OK
) else (
  echo   SETUP FINISHED WITH ERRORS
)
echo ============================================================
echo   Next:
echo     1. Double-click  2-START-SERVERS.bat
echo     2. Open          http://localhost/spms/
echo     3. Optional      3-START-NGROK.bat
echo.
echo   Logins - password for all is: password123
echo     admin@spms.local
echo     manager@spms.local
echo     staff1@spms.local
echo ============================================================
goto :done

:start_mysql
"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if not errorlevel 1 exit /b 0

echo [INFO] Starting MySQL service...
if exist "%XAMPP%\mysql\bin\mysqld.exe" (
  start "SPMS-MySQL" /MIN "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
)
ping -n 9 127.0.0.1 >nul

"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if not errorlevel 1 exit /b 0

REM retry once more
ping -n 6 127.0.0.1 >nul
"%MYSQL%" -u root -h 127.0.0.1 -e "SELECT 1;" >nul 2>&1
if errorlevel 1 exit /b 1
exit /b 0

:done
echo.
if /I not "%SKIP_PAUSE%"=="1" pause
endlocal & exit /b %ERR%
