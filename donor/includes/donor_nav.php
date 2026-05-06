<?php
// donor/includes/donor_nav.php
?>
<button class="module-menu-btn" onclick="document.getElementById('mobile-module-nav').classList.add('mobile-open')"><i class="fa-solid fa-layer-group"></i> Open System Navigation</button>
<div class="admin-sidebar" id="mobile-module-nav" style="left: 0px;">
<button class="close-module-btn" onclick="document.getElementById('mobile-module-nav').classList.remove('mobile-open')"><i class="fa-solid fa-xmark"></i></button>
    <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1);">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 50%; margin: 0 auto 1rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: white;">
            <i class="fa-solid fa-heart"></i>
        </div>
        <h3 style="margin: 0; color: white; font-size: 1.25rem; font-weight: 700;">Donor Portal</h3>
        <p style="margin: 0.5rem 0 0 0; color: #94A3B8; font-size: 0.875rem;"><?= h($_SESSION['user_name']) ?></p>
    </div>

    <nav>
        <!-- Home / Overview -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Overview</div>
        <a href="<?= BASE_URL ?>/donor/dashboard.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie" style="width: 20px;"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= BASE_URL ?>/campaigns.php" class="admin-sidebar-link">
            <i class="fa-solid fa-search" style="width: 20px;"></i>
            <span>Find Campaigns</span>
        </a>

        <!-- Giving History -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Giving History</div>
        <a href="<?= BASE_URL ?>/donor/donations.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'donations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-hand-holding-dollar" style="width: 20px;"></i>
            <span>My Donations</span>
        </a>
        <a href="<?= BASE_URL ?>/donor/pledges.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'pledges.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-hand-holding-heart" style="width: 20px;"></i>
            <span>In-Kind Pledges</span>
        </a>

        <!-- Analytics -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Analytics</div>
        <a href="<?= BASE_URL ?>/donor/impact.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'impact.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line" style="width: 20px;"></i>
            <span>Impact Tracker</span>
        </a>

        <!-- Settings -->
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #94A3B8; font-weight: 700; letter-spacing: 1px; margin: 1.5rem 0 0.5rem 1.25rem;">Settings</div>
        <a href="<?= BASE_URL ?>/donor/profile.php" class="admin-sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-circle" style="width: 20px;"></i>
            <span>My Profile</span>
        </a>
    </nav>

    <div style="margin-top: auto; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);">
        <a href="<?= BASE_URL ?>/actions/logout_action.php" class="admin-sidebar-link" style="color: #ef4444;">
            <i class="fa-solid fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>