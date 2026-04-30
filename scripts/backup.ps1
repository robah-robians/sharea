
param(
    [string]$ProjectRoot = (Resolve-Path "$PSScriptRoot\.."),
    [string]$BackupRoot = "$PSScriptRoot\..\backups",
    [string]$DbName = 'share_hope',
    [string]$DbUser = 'root',
    [string]$DbPassword = '',
    [string]$MySqlBin = 'C:\xampp\mysql\bin',
    [int]$RetentionDays = 14,
    [switch]$SkipFiles
)

$ErrorActionPreference = 'Stop'
$ts = Get-Date -Format 'yyyyMMdd_HHmmss'
$target = Join-Path $BackupRoot $ts
New-Item -ItemType Directory -Path $target -Force | Out-Null

$mysqldump = Join-Path $MySqlBin 'mysqldump.exe'
if (-not (Test-Path $mysqldump)) {
    throw "mysqldump not found at $mysqldump"
}
$sqlFile = Join-Path $target ("$DbName`_$ts.sql")
$dbArgs = @('--single-transaction','--routines','--triggers','--events',"-u$DbUser")
if ($DbPassword -ne '') { $dbArgs += "-p$DbPassword" }
$dbArgs += $DbName

$dump = & $mysqldump @dbArgs 2>&1
if ($LASTEXITCODE -ne 0) {
    throw "Database backup failed: $dump"
}
$dump | Out-File -FilePath $sqlFile -Encoding UTF8

if (-not $SkipFiles) {
    $zipFile = Join-Path $target ("project_$ts.zip")
    $items = Get-ChildItem -Path $ProjectRoot -Force | Where-Object { $_.Name -notin @('backups','.git') }
    Compress-Archive -Path $items.FullName -DestinationPath $zipFile -CompressionLevel Optimal
}
$old = Get-ChildItem -Path $BackupRoot -Directory -ErrorAction SilentlyContinue |
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$RetentionDays) }
foreach ($dir in $old) {
    Remove-Item -Path $dir.FullName -Recurse -Force
}

Write-Output "Backup complete: $target"
