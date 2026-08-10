# Start SPMS Phase 2 Flask prediction API (detached)
$python = (Get-Command python -ErrorAction SilentlyContinue).Source
if (-not $python) { $python = "C:\Users\HP\AppData\Local\Programs\Python\Python314\python.exe" }
$app = "C:\Users\HP\Desktop\Sales Prediction Management System\ml_service\app.py"
$dir = "C:\Users\HP\Desktop\Sales Prediction Management System\ml_service"

# Avoid double-start
$existing = Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue | Where-Object { $_.LocalPort -eq 5000 }
if ($existing) {
  Write-Host "Something already listening on port 5000."
} else {
  $cmd = "`"$python`" `"$app`""
  $r = Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{
    CommandLine = $cmd
    CurrentDirectory = $dir
  }
  Write-Host "Started Flask PID=$($r.ProcessId)"
  Start-Sleep -Seconds 2
}

try {
  $h = Invoke-WebRequest -Uri "http://127.0.0.1:5000/health" -UseBasicParsing -TimeoutSec 5
  Write-Host "Health:" $h.Content
} catch {
  Write-Host "Health check failed:" $_.Exception.Message
}
Write-Host "API: http://127.0.0.1:5000"
