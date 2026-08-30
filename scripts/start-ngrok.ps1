# Start public tunnel to local Apache (SPMS at /spms/)
# Prerequisites: Apache + MySQL + Flask running; ngrok authtoken configured once:
#   ngrok config add-authtoken YOUR_TOKEN

$env:Path = [System.Environment]::GetEnvironmentVariable('Path','Machine') + ';' + [System.Environment]::GetEnvironmentVariable('Path','User')

$ngrok = (Get-Command ngrok -ErrorAction SilentlyContinue).Source
if (-not $ngrok) {
  Write-Host "ngrok not found. Install: winget install Ngrok.Ngrok"
  exit 1
}

# Quick local checks
try {
  $null = Invoke-WebRequest 'http://127.0.0.1/spms/' -UseBasicParsing -TimeoutSec 3
  Write-Host "Local web OK"
} catch {
  Write-Host "WARNING: http://127.0.0.1/spms/ not reachable. Start Apache first (scripts/start-xampp.ps1)."
}

try {
  $null = Invoke-WebRequest 'http://127.0.0.1:5000/health' -UseBasicParsing -TimeoutSec 3
  Write-Host "Local Flask OK"
} catch {
  Write-Host "WARNING: Flask API not up. Forecasts will fail until you run scripts/start-ml.ps1"
}

Write-Host ""
Write-Host "Starting ngrok on port 80..."
Write-Host "After it starts, open http://127.0.0.1:4040 for the public URL."
Write-Host "Share: https://YOUR-SUBDOMAIN.ngrok-free.app/spms/"
Write-Host "Keep this window open. PC must stay on."
Write-Host ""

# Run in foreground so tunnel stays up
& ngrok http 80
