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
            SHARE HOPE guarantees transparency and trust. We verify every participant and ensure complete accountability in every transaction.</p>
    </div>
</section>

<!-- Mission & Vision -->
<section class="container"
    style="padding: 0 1.5rem 4rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
    <div style="background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border-top: 4px solid transparent; background-image: linear-gradient(var(--surface), var(--surface)), linear-gradient(135deg, var(--primary), var(--accent)); background-origin: border-box; background-clip: padding-box, border-box; transition: transform 0.3s, box-shadow 0.3s;"
        onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='var(--shadow-float)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--shadow-md)'">
        <div
            style="width: 60px; height: 60px; background: linear-gradient(135deg, rgba(0, 102, 255, 0.15), rgba(0, 217, 255, 0.15)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-bullseye" style="font-size: 1.5rem; color: var(--primary);"></i>
        </div>
        <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Our Mission</h2>
        <p style="color: var(--text-muted); line-height: 1.6;">SHARE HOPE is the trusted intermediary that ensures complete transparency and accountability. We verify NGOs, manage campaigns, and guarantee that every donation reaches its intended purpose with full traceability and integrity.</p>
    </div>

    <div style="background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border-top: 4px solid transparent; background-image: linear-gradient(var(--surface), var(--surface)), linear-gradient(135deg, var(--secondary), var(--accent)); background-origin: border-box; background-clip: padding-box, border-box; transition: transform 0.3s, box-shadow 0.3s;"
        onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='var(--shadow-float)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--shadow-md)'">
        <div
            style="width: 60px; height: 60px; background: linear-gradient(135deg, rgba(227, 242, 253, 0.3), rgba(0, 217, 255, 0.15)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
            <i class="fa-solid fa-eye" style="font-size: 1.5rem; color: var(--secondary);"></i>
        </div>
        <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Our Vision</h2>
        <p style="color: var(--text-muted); line-height: 1.6;">A world where every act of kindness is amplified, where
            charitable giving is completely fraud-free, and where the impact of every single dollar can be visibly
            traced and celebrated.</p>
    </div>
</section>

<!-- Why Choose Us -->
<section style="background: linear-gradient(135deg, rgba(0, 102, 255, 0.03), rgba(0, 217, 255, 0.03)); padding: 4rem 0;">
    <div class="container">
        <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 1rem; background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Our Core Values</h2>
        <div style="width: 80px; height: 4px; background: linear-gradient(90deg, var(--primary), var(--accent)); margin: 0 auto 3rem; border-radius: 2px;"></div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
            <!-- Transparency -->
            <div style="text-align: center; padding: 2rem; background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: all 0.3s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent'; this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)'">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, rgba(0, 102, 255, 0.2), rgba(0, 217, 255, 0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 1.5rem; color: var(--primary);"></i>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Transparency</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Clear visibility of contributions and their use.</p>
            </div>
            <!-- Integrity -->
            <div style="text-align: center; padding: 2rem; background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: all 0.3s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--secondary)'; this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent'; this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)'">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, rgba(227, 242, 253, 0.3), rgba(0, 217, 255, 0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fa-solid fa-shield-heart" style="font-size: 1.5rem; color: var(--secondary);"></i>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Integrity</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Honest and ethical handling of all activities.</p>
            </div>
            <!-- Community -->
            <div style="text-align: center; padding: 2rem; background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: all 0.3s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--accent)'; this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent'; this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)'">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, rgba(0, 217, 255, 0.2), rgba(0, 102, 255, 0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fa-solid fa-users" style="font-size: 1.5rem; color: var(--accent);"></i>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Community</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Bringing people together for a common cause.</p>
            </div>
            <!-- Impact -->
            <div style="text-align: center; padding: 2rem; background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: all 0.3s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent'; this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)'">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, rgba(0, 102, 255, 0.2), rgba(0, 217, 255, 0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fa-solid fa-bolt" style="font-size: 1.5rem; color: var(--primary);"></i>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Impact</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Focus on meaningful, measurable outcomes.</p>
            </div>
            <!-- Compassion -->
            <div style="text-align: center; padding: 2rem; background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: all 0.3s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--secondary)'; this.style.boxShadow='var(--shadow-md)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.borderColor='transparent'; this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)'">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, rgba(227, 242, 253, 0.3), rgba(0, 217, 255, 0.1)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i class="fa-solid fa-hand-holding-heart" style="font-size: 1.5rem; color: var(--secondary);"></i>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Compassion</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Genuine care for individuals and communities in need.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="container" style="padding: 4rem 1.5rem; text-align: center;">
    <div
        style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 4rem 2rem; border-radius: var(--radius-lg); border: none; color: white;">
        <h2 style="font-size: 2rem; margin-bottom: 1rem; color: white; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">Ready to make an impact?</h2>
        <p
            style="color: rgba(255,255,255,0.9); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">
            Join thousands of donors and NGOs already using Share Hope to change lives across the world.</p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="/share_hope/campaigns.php" class="btn btn-primary"
                style="font-size: 1.125rem; padding: 0.75rem 2rem; background: rgba(255,255,255,0.2); border: 2px solid white; color: white; box-shadow: none;">Browse Campaigns</a>
            <a href="/share_hope/register.php" class="btn btn-outline"
                style="font-size: 1.125rem; padding: 0.75rem 2rem; background: white; color: var(--primary); border: none;">Register Now</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>