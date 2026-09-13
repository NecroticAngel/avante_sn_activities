        </main>
    </div>
    <footer class="avante-site-footer">
        <div class="avante-footer-grid">
            <section>
                <h2>About us</h2>
                <p>Thoughtful stays, memorable experiences and local expertise — brought together by Avante Travel.</p>
                <a class="avante-footer-cta" href="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>map.php">Explore our properties</a>
            </section>
            <section>
                <h2>Plan your trip</h2>
                <nav class="avante-footer-nav" aria-label="Footer">
                    <a href="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>index.php">Find accommodation</a>
                    <a href="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>holiday.php">Build a holiday</a>
                    <a href="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>activities.php">Browse activities</a>
                    <a href="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>manage.php">Manage a booking</a>
                </nav>
            </section>
            <section>
                <h2>Travel inspiration</h2>
                <div class="avante-footer-gallery" aria-label="Travel inspiration gallery">
                    <img src="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>assets/img/footer/coast.jpg" alt="Coastal destination">
                    <img src="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>assets/img/footer/waterfall.jpg" alt="Mountain waterfall">
                    <img src="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>assets/img/footer/island.jpg" alt="Island destination">
                    <img src="<?php echo htmlspecialchars($base ?? '', ENT_QUOTES, 'UTF-8'); ?>assets/img/footer/city.jpg" alt="Historic city destination">
                </div>
            </section>
        </div>
        <div class="avante-footer-bottom">&copy; <?php echo date('Y'); ?> Avante Travel <span aria-hidden="true">•</span> Site by D-Zine</div>
    </footer>
