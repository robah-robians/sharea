    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a href="<?= BASE_URL ?>/" class="logo text-white" style="text-decoration: none;">
                    <i class="fa-solid fa-hands-holding-circle"></i> SHARE <span>HOPE</span>
                </a>
                <p>Empowering a trusted network of verified NGOs to deliver transparent and impactful support.</p>
                <div class="social-links">
                    <a href="https://facebook.com/sharehope" target="_blank" rel="noopener noreferrer" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="https://x.com/sharehope" target="_blank" rel="noopener noreferrer" title="X"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="https://instagram.com/sharehope" target="_blank" rel="noopener noreferrer" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://linkedin.com/company/sharehope" target="_blank" rel="noopener noreferrer" title="LinkedIn"><i class="fa-brands fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Explore</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/campaigns.php"><i class="fa-solid fa-bullhorn" style="font-size:0.8rem; margin-right:0.35rem;"></i> Active Campaigns</a></li>
                    <li><a href="<?= BASE_URL ?>/impact.php"><i class="fa-solid fa-map-location-dot" style="font-size:0.8rem; margin-right:0.35rem;"></i> Global Impact Map</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php"><i class="fa-solid fa-circle-info" style="font-size:0.8rem; margin-right:0.35rem;"></i> About Us</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Get Involved</h4>
                <ul>
                    <li><a href="<?= BASE_URL ?>/about.php#faq"><i class="fa-solid fa-circle-question" style="font-size:0.8rem; margin-right:0.35rem;"></i> Help & FAQs</a></li>
                    <li><a href="<?= BASE_URL ?>/impact.php"><i class="fa-solid fa-shield-halved" style="font-size:0.8rem; margin-right:0.35rem;"></i> Our Transparency</a></li>
                    <li><a href="<?= BASE_URL ?>/login.php"><i class="fa-solid fa-lock" style="font-size:0.8rem; margin-right:0.35rem;"></i> Partner Login</a></li>
                    <li><a href="<?= BASE_URL ?>/register.php?role=ngo"><i class="fa-solid fa-building-ngo" style="font-size:0.8rem; margin-right:0.35rem;"></i> Register NGO</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Empowering change through verified transparency.</p>
                <p style="font-size: 0.8rem; color: var(--text-muted); opacity: 0.7;"><?= APP_NAME ?> Platform v<?= APP_VERSION ?></p>
            </div>
        </div>
    </footer>
    <script src="<?= JS_URL ?>/main.js"></script>
</body>
</html>
