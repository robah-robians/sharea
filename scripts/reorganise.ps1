# reorganise.ps1 — Full project restructure script
# Run from: C:\Users\Administrator\OneDrive\Desktop\xampp2\htdocs\share_hope\

$root = "C:\Users\Administrator\OneDrive\Desktop\xampp2\htdocs\share_hope"

# ── 1. Create new directory tree ─────────────────────────────────────────────
$dirs = @(
    "app\controllers",
    "app\core",
    "app\views\admin\includes",
    "app\views\donor\includes",
    "app\views\ngo\includes",
    "public\assets\css",
    "public\assets\js",
    "public\assets\uploads\campaigns",
    "public\assets\uploads\docs",
    "public\assets\uploads\images",
    "storage\logs",
    "storage\backups"
)
foreach ($d in $dirs) {
    New-Item -ItemType Directory -Path "$root\$d" -Force | Out-Null
    Write-Host "Created: $d"
}

# ── 2. Move actions → app/controllers ────────────────────────────────────────
Get-ChildItem "$root\actions\*.php" | ForEach-Object {
    Move-Item $_.FullName "$root\app\controllers\" -Force
    Write-Host "Moved controller: $($_.Name)"
}

# ── 3. Move includes → app/core ──────────────────────────────────────────────
Get-ChildItem "$root\includes\*.php" | ForEach-Object {
    Move-Item $_.FullName "$root\app\core\" -Force
    Write-Host "Moved core: $($_.Name)"
}

# ── 4. Move admin pages → app/views/admin ────────────────────────────────────
Get-ChildItem "$root\admin\*.php" | ForEach-Object {
    Move-Item $_.FullName "$root\app\views\admin\" -Force
    Write-Host "Moved admin view: $($_.Name)"
}
Move-Item "$root\admin\includes\admin_nav.php" "$root\app\views\admin\includes\" -Force
Write-Host "Moved: admin_nav.php"

# ── 5. Move donor pages → app/views/donor ────────────────────────────────────
Get-ChildItem "$root\donor\*.php" | ForEach-Object {
    Move-Item $_.FullName "$root\app\views\donor\" -Force
    Write-Host "Moved donor view: $($_.Name)"
}
Move-Item "$root\donor\includes\donor_nav.php" "$root\app\views\donor\includes\" -Force
Write-Host "Moved: donor_nav.php"

# ── 6. Move ngo pages → app/views/ngo ────────────────────────────────────────
Get-ChildItem "$root\ngo\*.php" | ForEach-Object {
    Move-Item $_.FullName "$root\app\views\ngo\" -Force
    Write-Host "Moved ngo view: $($_.Name)"
}
Move-Item "$root\ngo\includes\ngo_nav.php" "$root\app\views\ngo\includes\" -Force
Write-Host "Moved: ngo_nav.php"

# ── 7. Move public pages → public/ ───────────────────────────────────────────
$publicFiles = @(
    "index.php","login.php","register.php","logout.php",
    "campaigns.php","donate.php","payment.php","payment_success.php",
    "donation_receipt.php","receipt.php","impact.php","about.php",
    "awareness_detail.php","ngo_profile.php","forgot_password.php",
    "reset_password.php","verify_email.php","maintenance.php",
    "message_detail.php",".htaccess"
)
foreach ($f in $publicFiles) {
    if (Test-Path "$root\$f") {
        Move-Item "$root\$f" "$root\public\" -Force
        Write-Host "Moved public: $f"
    }
}

# ── 8. Move assets → public/assets ───────────────────────────────────────────
if (Test-Path "$root\assets\css\style.css") {
    Copy-Item "$root\assets\css\style.css" "$root\public\assets\css\style.css" -Force
}
if (Test-Path "$root\assets\js\main.js") {
    Copy-Item "$root\assets\js\main.js" "$root\public\assets\js\main.js" -Force
}
# Move uploads
Get-ChildItem "$root\assets\uploads" -Recurse -File | ForEach-Object {
    $rel = $_.FullName.Substring("$root\assets\uploads\".Length)
    $dest = "$root\public\assets\uploads\$rel"
    $destDir = Split-Path $dest
    if (!(Test-Path $destDir)) { New-Item -ItemType Directory $destDir -Force | Out-Null }
    Move-Item $_.FullName $dest -Force
}
Write-Host "Moved assets"

# ── 9. Move logs/backups → storage/ ──────────────────────────────────────────
if (Test-Path "$root\logs\emails.json") {
    Move-Item "$root\logs\emails.json" "$root\storage\logs\" -Force
    Write-Host "Moved: emails.json"
}
Get-ChildItem "$root\backups" -Recurse -File | ForEach-Object {
    $rel = $_.FullName.Substring("$root\backups\".Length)
    $dest = "$root\storage\backups\$rel"
    $destDir = Split-Path $dest
    if (!(Test-Path $destDir)) { New-Item -ItemType Directory $destDir -Force | Out-Null }
    Move-Item $_.FullName $dest -Force
}
Write-Host "Moved storage"

# ── 10. Remove now-empty old directories ─────────────────────────────────────
$oldDirs = @("actions","includes","admin","donor","ngo","assets","logs","backups")
foreach ($d in $oldDirs) {
    if (Test-Path "$root\$d") {
        Remove-Item "$root\$d" -Recurse -Force -ErrorAction SilentlyContinue
        Write-Host "Removed old: $d"
    }
}

Write-Host "`n✅ Directory restructure complete."
