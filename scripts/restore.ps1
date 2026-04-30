
param(
    [Parameter(Mandatory=$true)][string]$BackupFolder,
    [string]$DbName = 'share_hope',
    [string]$DbUser = 'root',
    [string]$DbPassword = '',
    [string]$MySqlBin = 'C:\xampp\mysql\bin'
)

$ErrorActionPreference = 'Stop'
$mysql = Join-Path $MySqlBin 'mysql.exe'
if (-not (Test-Path $mysql)) {
    throw "mysql.exe not found at $mysql"
}
if (-not (Test-Path $BackupFolder)) {
    throw "Backup folder not found: $BackupFolder"
}

$sqlFile = Get-ChildItem -Path $BackupFolder -Filter '*.sql' | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if (-not $sqlFile) {
    throw 'No SQL backup file found in backup folder.'
}
$args = @("-u$DbUser")
if ($DbPassword -ne '') { $args += "-p$DbPassword" }
$args += $DbName

Get-Content -Path $sqlFile.FullName -Raw | & $mysql @args
if ($LASTEXITCODE -ne 0) {
    throw "Database restore failed from file: $($sqlFile.FullName)"
}

Write-Output "Restore complete from: $($sqlFile.FullName)"
