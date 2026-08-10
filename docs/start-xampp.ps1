# Start XAMPP Apache + MySQL (detached)
function Start-Detached($cmdLine) {
  Invoke-CimMethod -ClassName Win32_Process -MethodName Create -Arguments @{ CommandLine = $cmdLine; CurrentDirectory = "C:\xampp" } | Out-Null
}
if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
  Start-Detached '"C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:\xampp\mysql\bin\my.ini --standalone'
  Start-Sleep -Seconds 3
}
if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) {
  Start-Detached '"C:\xampp\apache\bin\httpd.exe" -d C:/xampp/apache'
  Start-Sleep -Seconds 2
}
Get-Process httpd,mysqld -ErrorAction SilentlyContinue | Format-Table Name,Id
Write-Host "Open app: http://localhost/spms/"
Write-Host "phpMyAdmin: http://localhost/phpmyadmin/"
