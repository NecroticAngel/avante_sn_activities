<?php
$pageTitle = 'Build a holiday — Avante Travel';
$headerTitle = 'Build a holiday';
$activeNav = 'holiday';
$bodyClass = 'avante-search-page avante-holiday-page';
$extraCss = [
    'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
    'assets/css/base-stock-styles.css',
    'assets/css/dynamic-styles.css',
    'assets/css/activities.css',
    'assets/css/holiday.css',
];
require __DIR__ . '/includes/header.php';
?>
        <p class="avante-lede">Pick a stay, add things to do, then check out once. The stay goes to Stock Network. Activities go to the operators. Avante gets a copy of everything.</p>

        <div class="holiday-layout">
            <div class="holiday-main">
                <section class="holiday-section" id="holiday-stay-section">
                    <h2>1. Your stay</h2>
                    <div class="horizontal-form-container">
                    <form id="query-stock-form" class="horizontal-form holiday-search">
                        <div class="input-container date-range">
                            <input type="text" id="daterange" name="daterange" placeholder="Check-in – Check-out" class="daterange-input" required autocomplete="off">
                            <input type="hidden" id="checkin_date" name="checkin_date">
                            <input type="hidden" id="checkout_date" name="checkout_date">
                        </div>
                        <div class="input-container destination">
                            <input type="text" id="destination" name="destination" placeholder="Destination" required>
                            <button type="button" id="clear-destination" class="clear-button">x</button>
                        </div>
                        <div class="input-container2">
                            <select name="unit_size" id="unit_size">
                                <option value="all" selected>All unit sizes</option>
                            </select>
                        </div>
                        <div class="submit-button-container">
                            <input type="submit" value="Find stays">
                        </div>
                    </form>
                    </div>
                    <div id="holiday-stay-results" class="holiday-stay-results">
                        <p class="holiday-hint">Search a destination and dates to add a stay to this holiday.</p>
                    </div>
                </section>

                <section class="holiday-section" id="holiday-do-section">
                    <h2>2. Things to do</h2>
                    <div class="activity-toolbar">
                        <div class="activity-filters" id="activity-filters"></div>
                        <label class="activity-search">
                            <span class="visually-hidden">Search activities</span>
                            <input type="search" id="activity-query" placeholder="Search activities">
                        </label>
                    </div>
                    <div id="activity-grid" class="activity-grid holiday-activity-grid">
                        <div class="activity-loading">Loading activities…</div>
                    </div>
                </section>
            </div>

            <aside class="holiday-tray" id="holiday-tray">
                <h2>Your holiday</h2>
                <div id="holiday-tray-body"></div>
                <button type="button" class="holiday-checkout-btn" id="holiday-open-checkout" disabled>Check out</button>
            </aside>
        </div>

        <div id="holidayCheckoutModal" class="activity-modal" hidden>
            <div class="activity-modal-card holiday-checkout-card" role="dialog" aria-modal="true">
                <button type="button" class="activity-modal-close" data-close-checkout aria-label="Close">&times;</button>
                <h2>Check out</h2>
                <div id="holiday-checkout-summary" class="activity-booking-overview"></div>
                <form id="holiday-checkout-form" class="activity-booking-form">
                    <label>Full name *
                        <input type="text" name="full_name" required>
                    </label>
                    <label>Email *
                        <input type="email" name="email" required>
                    </label>
                    <label>Cellphone *
                        <input type="tel" name="phone" required placeholder="+27…">
                    </label>
                    <div class="activity-booking-row">
                        <label>Adults *
                            <select name="adults" required>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>"<?php echo $i === 2 ? ' selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label>Children
                            <select name="children">
                                <?php for ($i = 0; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>
                    <div id="holiday-child-ages" class="booking-child-ages" hidden></div>
                    <label id="holiday-activity-date-wrap" hidden>Activity date *
                        <input type="date" name="activity_date">
                    </label>
                    <label>Member / voucher code
                        <input type="text" name="membership_no" placeholder="Optional">
                    </label>
                    <label>Notes
                        <textarea name="message" rows="3" placeholder="Anything we should know…"></textarea>
                    </label>
                    <div class="activity-booking-actions">
                        <button type="button" class="activity-booking-cancel" data-close-checkout>Cancel</button>
                        <button type="submit" class="activity-book">Send holiday request</button>
                    </div>
                </form>
                <div id="holiday-checkout-message" class="activity-booking-message" hidden></div>
            </div>
        </div>
    </div>
    <script>window.AVANTE = { apiUrl: 'api/index.php' };</script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="assets/js/custom-datepicker.js"></script>
    <script src="assets/js/autocomplete.js"></script>
    <script src="assets/js/holiday.js"></script>
</body>
</html>
