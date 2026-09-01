# Start ngrok public tunnel to Apache :80
$env:Path = [Environment]::GetEnvironmentVariable('Path','Machine') + ';' +
            [Environment]::GetEnvironmentVariable('Path','User')

$candidates = @(
  (Get-Command ngrok -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source),
  "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\Ngrok.Ngrok_Microsoft.Winget.Source_8wekyb3d8bbwe\ngrok.exe",
  "$env:LOCALAPPDATA\ngrok-bin\ngrok.exe",
  "$env:ProgramFiles\ngrok\ngrok.exe"
) | Where-Object { $_ -and (Test-Path $_) }

$ngrok = $candidates | Select-Object -First 1
if (-not $ngrok) {
  $found = Get-ChildItem -Path "$env:LOCALAPPDATA\Microsoft\WinGet\Packages" -Filter ngrok.exe -Recurse -ErrorAction SilentlyContinue |
    Select-Object -First 1 -ExpandProperty FullName
  $ngrok = $found
}

if (-not $ngrok) {
  Write-Host "ngrok not found. Install: winget install -e --id Ngrok.Ngrok"
  exit 1
}

Write-Host "Using: $ngrok"

try {
  Invoke-WebRequest 'http://127.0.0.1/spms/' -UseBasicParsing -TimeoutSec 4 | Out-Null
  Write-Host "Local web OK"
} catch {
  Write-Host "ERROR: http://127.0.0.1/spms/ not up. Run 2-START-SERVERS.bat first."
  exit 1
}

try {
  Invoke-WebRequest 'http://127.0.0.1:5000/health' -UseBasicParsing -TimeoutSec 4 | Out-Null
  Write-Host "Local Flask OK"
} catch {
  Write-Host "WARNING: Flask offline - Forecasts will fail"
}

Write-Host ""
Write-Host "Starting ngrok on port 80. Keep this window open."
Write-Host "Then open http://127.0.0.1:4040 and copy the https URL + /spms/"
Write-Host ""

Start-Process 'http://127.0.0.1:4040'
& $ngrok http 80
