Get-Process httpd,mysqld -ErrorAction SilentlyContinue | Stop-Process -Force
Write-Host "Apache and MySQL stopped."
