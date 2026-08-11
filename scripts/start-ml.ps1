# Start Flask prediction API (from repo root or scripts/)
$Root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $Root "ml_service\app.py"))) {
  $Root = $PSScriptRoot
}
$python = (Get-Command python -ErrorAction SilentlyContinue).Source
if (-not $python) { $python = "C:\Users\HP\AppData\Local\Programs\Python\Python314\python.exe" }
$app = Join-Path $Root "ml_service\app.py"
$dir = Join-Path $Root "ml_service"

$existing = Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue | Where-Object { $_.LocalPort -eq 5000 }
if ($existing) {
  Write-Host "Something already listening on port 5000."
} else {
  $cmd = "`"$python`" -u `"$app`""
  $r = Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{
    CommandLine = $cmd
    CurrentDirectory = $dir
  }
  Write-Host "Started Flask PID=$($r.ProcessId)"
  Start-Sleep -Seconds 3
}

try {
  $h = Invoke-WebRequest -Uri "http://127.0.0.1:5000/health" -UseBasicParsing -TimeoutSec 5
  Write-Host "Health:" $h.Content
} catch {
  Write-Host "Health check failed:" $_.Exception.Message
}
Write-Host "API: http://127.0.0.1:5000"
