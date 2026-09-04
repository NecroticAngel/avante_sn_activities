<?php
$pageTitle = 'Activities — Avante Travel';
$headerTitle = 'Things to do';
$activeNav = 'activities';
$extraCss = ['assets/css/activities.css'];
require __DIR__ . '/includes/header.php';
?>
        <p class="avante-lede">Plettenberg Bay operators we work with — kloofing, trails, golf, padel and wine. Book them directly, or ask us to add it to your stay.</p>

        <div class="activity-toolbar">
            <div class="activity-filters" id="activity-filters" role="tablist" aria-label="Activity categories"></div>
            <label class="activity-search">
                <span class="visually-hidden">Search activities</span>
                <input type="search" id="activity-query" placeholder="Search by name or operator">
            </label>
        </div>

        <p class="activity-count" id="activity-count"></p>
        <div class="activity-grid" id="activity-grid">
            <div class="activity-loading">Loading activities…</div>
        </div>

        <p class="avante-powered-by"><a href="https://avantetravel.co.za/" target="_blank" rel="noopener noreferrer">Powered by Avante Travel</a></p>

        <div id="activityBookingModal" class="activity-modal" hidden>
            <div class="activity-modal-card" role="dialog" aria-modal="true" aria-labelledby="activity-booking-title">
                <button type="button" class="activity-modal-close" data-close-booking aria-label="Close">&times;</button>
                <h2 id="activity-booking-title">Make a booking</h2>
                <div id="activity-booking-overview" class="activity-booking-overview"></div>
                <form id="activity-booking-form" class="activity-booking-form">
                    <input type="hidden" name="activity_id" id="booking_activity_id">
                    <label>Full name *
                        <input type="text" name="full_name" required>
                    </label>
                    <label>Email *
                        <input type="email" name="email" required>
                    </label>
                    <label>Phone
                        <input type="tel" name="phone">
                    </label>
                    <div class="activity-booking-row">
                        <label>Preferred date *
                            <input type="date" name="preferred_date" required>
                        </label>
                        <label>Number of people *
                            <select name="people" required>
                                <option value="">Select</option>
                                <?php for ($i = 1; $i <= 16; $i++): ?>
                                    <option value="<?php echo $i; ?>"<?php echo $i === 2 ? ' selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>
                    <label>Message
                        <textarea name="message" rows="3" placeholder="Anything we should know…"></textarea>
                    </label>
                    <div class="activity-booking-actions">
                        <button type="button" class="activity-booking-cancel" data-close-booking>Cancel</button>
                        <button type="submit" class="activity-book">Send booking request</button>
                    </div>
                </form>
                <div id="activity-booking-message" class="activity-booking-message" hidden></div>
            </div>
        </div>
    </div>
    <script>window.AVANTE = { apiUrl: 'api/index.php' };</script>
    <script src="assets/js/activities.js"></script>
</body>
</html>
