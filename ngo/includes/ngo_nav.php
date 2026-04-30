<?php
// ngo/includes/ngo_nav.php
?>
<button class="module-menu-btn" onclick="document.getElementById('mobile-module-nav').classList.add('mobile-open')"><i class="fa-solid fa-layer-group"></i> Open System Navigation</button>
<div class="admin-sidebar" id="mobile-module-nav" style="left: 0px;">
<button class="close-module-btn" onclick="document.getElementById('mobile-module-nav').classList.remove('mobile-open')"><i class="fa-solid fa-xmark"></i></button>
    <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">
            <i class="fa-solid fa-hands-helping"></i>
        </div>
        <h3 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">NGO Portal</h3>
        <p style="margin: 0.5rem 0 0 0; color: #94A3B8; font-size: 0.875rem;"><?= h($_SESSION['user_name']) ?></p>
    </div>

    <nav>
        <!-- Operations -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Operations</div>
        <a href="/share_hope/ngo/dashboard.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high" style="width: 20px;"></i>
            <span>Dashboard</span>
        </a>
        <a href="/share_hope/impact.php" class="admin-sidebar-link">
            <i class="fa-solid fa-earth-africa" style="width: 20px;"></i>
            <span>Global Impact Map</span>
        </a>

        <!-- Activity & Metrics -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Activity & Metrics</div>
        <a href="/share_hope/ngo/donations.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'donations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-receipt" style="width: 20px;"></i>
            <span>Acquisition History</span>
        </a>
        <a href="/share_hope/ngo/reports.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice-dollar" style="width: 20px;"></i>
            <span>Reports</span>
        </a>

        <!-- Infrastructure -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Infrastructure</div>
        <a href="/share_hope/ngo/profile.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-server" style="width: 20px;"></i>
            <span>Profile</span>
        </a>
    </nav>

    <div style="margin-top: auto; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);">
        <a href="/share_hope/actions/logout_action.php" class="admin-sidebar-link" style="color: #ef4444;">
            <i class="fa-solid fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>