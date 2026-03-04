<?php
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero"
    style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 6rem 0; text-align: center; border-radius: 0 0 2rem 2rem; margin-bottom: 4rem;">
    <div class="container">
        <h1
            style="font-size: 3.5rem; margin-bottom: 1rem; color: #ffffff !important; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
            About <span style="font-weight: 300;">Share
                Hope</span></h1>
        <p
            style="font-size: 1.25rem; max-width: 600px; margin: 0 auto; opacity: 0.9; color: #ffffff !important; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">
            Bridging the gap between generous
            hearts and meaningful causes. Transparency and impact at the core of everything we do.</p>
    </div>
</section>

<!-- Mission & Vision -->
<section class="container"
    style="padding: 0 1.5rem 4rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
    <div style="background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border-top: 4px solid var(--primary); transition: transform 0.3s;"
        onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
        <div
            style="width: 60px; height: 60px; background: rgba(79, 70, 229, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-bullseye" style="font-size: 1.5rem; color: var(--primary);"></i>
        </div>
        <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Our Mission</h2>
        <p style="color: var(--text-muted); line-height: 1.6;">To empower non-governmental organizations with a secure,
            transparent, and easy-to-use platform that connects them directly with donors who want to make a real
            difference in the world.</p>
    </div>

    <div style="background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border-top: 4px solid var(--secondary); transition: transform 0.3s;"
        onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
        <div
            style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-eye" style="font-size: 1.5rem; color: var(--secondary);"></i>
        </div>
        <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Our Vision</h2>
        <p style="color: var(--text-muted); line-height: 1.6;">A world where every act of kindness is amplified, where
            charitable giving is completely fraud-free, and where the impact of every single dollar can be visibly
            traced and celebrated.</p>
    </div>
</section>

<!-- Why Choose Us -->
<section style="background: var(--background); padding: 4rem 0;">
    <div class="container">
        <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem;">The Share Hope Difference</h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
            <div style="text-align: center; padding: 2rem;">
                <i class="fa-solid fa-shield-halved"
                    style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">100% Verified NGOs</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Every organization on our platform undergoes a
                    strict background check and document verification by our specialized admin team.</p>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <i class="fa-solid fa-chart-line"
                    style="font-size: 3rem; color: var(--secondary); margin-bottom: 1rem;"></i>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Total Transparency</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">NGOs are required to post follow-up impact
                    updates with proof-of-work images so you know exactly where your money went.</p>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <i class="fa-solid fa-lock" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Bank-Level Security</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Your data and donations are protected by
                    industry-standard encryption, CSRF tokens, and bcrypt password hashing algorithms.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="container" style="padding: 4rem 1.5rem; text-align: center;">
    <div
        style="background: var(--surface); padding: 4rem 2rem; border-radius: var(--radius-lg); border: 1px dashed var(--border);">
        <h2 style="font-size: 2rem; margin-bottom: 1rem;">Ready to make an impact?</h2>
        <p
            style="color: var(--text-muted); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto;">
            Join thousands of donors and NGOs already using Share Hope to change lives across the world.</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="/share_hope/campaigns.php" class="btn btn-primary"
                style="font-size: 1.125rem; padding: 0.75rem 2rem;">Browse Campaigns</a>
            <a href="/share_hope/register.php" class="btn btn-outline"
                style="font-size: 1.125rem; padding: 0.75rem 2rem;">Register Now</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>