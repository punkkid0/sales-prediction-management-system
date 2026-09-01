@echo off
REM Public link helper - run AFTER 2-START-SERVERS.bat
cd /d "%~dp0"
call "%~dp0scripts\start-ngrok-public.bat"
