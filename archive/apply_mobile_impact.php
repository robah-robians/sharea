<?php
// 1. header.php
$header = file_get_contents('includes/header.php');
$header = str_replace('href="<?= BASE_URL ?>/ngos.php">Our NGOs</a>', 'href="<?= BASE_URL ?>/impact.php">Impact</a>', $header);
file_put_contents('includes/header.php', $header);

// 2. impact.php
$impact = file_get_contents('impact.php');
$impact = str_replace('<h1>Our Partner NGOs</h1>', '<h1>Global Impact Portfolio</h1>', $impact);
$impact = str_replace('<p>Discover and support verified organizations making a real impact on the ground.</p>', '<p>Discover the verifiable projects and regions our organizational partners are actively transforming.</p>', $impact);
$impact = str_replace('Global NGO Stakeholder Map', 'Active Impact Regions', $impact);
file_put_contents('impact.php', $impact);

// 3. style.css 
$css = file_get_contents('assets/css/style.css');
$mobile_css = "/* Responsive */
@media (max-width: 1024px) {
    .admin-layout { flex-direction: column; gap: 1.5rem !important; }
    .module-menu-btn { display: flex; align-items: center; justify-content: center; width: 100%; padding: 1rem; background: var(--primary); color: white; border-radius: var(--radius-md); font-weight: 700; margin-bottom: 1.5rem; cursor: pointer; border: none; font-size: 1rem; gap: 0.5rem; box-shadow: var(--shadow-sm); transition: transform 0.2s; }
    .module-menu-btn:active { transform: translateY(2px); }
    .admin-sidebar { display: none !important; }
    .admin-sidebar.mobile-open { display: block !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; z-index: 9999 !important; background: var(--text-main) !important; margin: 0 !important; border-radius: 0 !important; width: 100% !important; padding: 5rem 2rem 2rem !important; overflow-y: auto !important; height: 100vh !important; }
    .close-module-btn { display: flex; align-items: center; justify-content: center; position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(255,255,255,0.1); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; font-size: 1.25rem; cursor: pointer; transition: background 0.2s; }
    .close-module-btn:active { background: var(--danger); }
    .admin-main [style*=\"grid-template-columns: 1fr 1fr\"] { grid-template-columns: 1fr !important; }
}
@media (min-width: 1025px) { .module-menu-btn, .close-module-btn { display: none !important; } }";

// Replace the @media (max-width: 1024px) block in style.css exactly.
$css = preg_replace('/\/\* Responsive \*\/\s*@media \(max-width: 1024px\) \{[\s\S]*?\.admin-main \[style\*="grid-template-columns: 1fr 1fr"\] \{ grid-template-columns: 1fr !important; \}\s*\}/m', $mobile_css, $css);
file_put_contents('assets/css/style.css', $css);

// 4. Nav files
$nav_files = ['admin/includes/admin_nav.php', 'donor/includes/donor_nav.php', 'ngo/includes/ngo_nav.php'];
foreach($nav_files as $f) {
    if(file_exists($f)) {
        $nav = file_get_contents($f);
        if (strpos($nav, 'module-menu-btn') === false) {
            $nav = str_replace('<div class="admin-sidebar" style="left: 0px;">', '<button class="module-menu-btn" onclick="document.getElementById(\'mobile-module-nav\').classList.add(\'mobile-open\')"><i class="fa-solid fa-layer-group"></i> Open System Navigation</button>' . "\n" . '<div class="admin-sidebar" id="mobile-module-nav" style="left: 0px;">'."\n".'<button class="close-module-btn" onclick="document.getElementById(\'mobile-module-nav\').classList.remove(\'mobile-open\')"><i class="fa-solid fa-xmark"></i></button>', $nav);
            file_put_contents($f, $nav);
        }
    }
}

// 5. admin/dashboard.php
if(file_exists('admin/dashboard.php')) {
    $dash = file_get_contents('admin/dashboard.php');
    $dash = str_replace('Global Impact Network', 'Active Impact Regions', $dash);
    $dash = str_replace('Systems Online', 'Live Campaign Tracking', $dash);
    file_put_contents('admin/dashboard.php', $dash);
}

echo "All complete!";
?>
