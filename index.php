<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Avante Travel Search</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="assets/css/base-stock-styles.css">
    <link rel="stylesheet" href="assets/css/dynamic-styles.css">
    <link rel="stylesheet" href="assets/css/page.css">
</head>
<body class="avante-search-page">
    <div class="avante-page">
        <header class="avante-page-header">
            <a class="avante-logo-link" href="index.php">
                <img src="assets/img/avantetravel.png" alt="Avante Travel">
            </a>
            <div class="avante-header-copy">
                <h1>Search accommodation</h1>
                <nav class="avante-nav" aria-label="Primary">
                    <a href="index.php" class="is-active">Accommodation</a>
                    <a href="activities.php">Activities</a>
                </nav>
            </div>
        </header>

        <div id="avante-setup-banner" class="avante-setup-banner">
            Add your Stock Network bearer token to <code>config.php</code> (the same token from the WordPress plugin settings) to run live searches. Destination suggestions still work from cached options.
        </div>

        <div id="avante-travel-search-wrapper">
            <div class="horizontal-form-container">
                <form id="query-stock-form" method="post" class="horizontal-form">
                    <div class="input-container date-range">
                        <svg aria-hidden="true" class="e-font-icon-svg e-far-calendar-alt" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
                            <path d="M148 288h-40c-6.6 0-12-5.4-12-12v-40c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v40c0 6.6-5.4 12-12 12zm108-12v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 96v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm-96 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm192 0v-40c0-6.6-5.4-12-12-12h-40c-6.6 0-12 5.4-12 12v40c0 6.6 5.4 12 12 12h40c6.6 0 12-5.4 12-12zm96-260v352c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V112c0-26.5 21.5-48 48-48h48V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h128V12c0-6.6 5.4-12 12-12h40c6.6 0 12 5.4 12 12v52h48c26.5 0 48 21.5 48 48zm-48 346V160H48v298c0 3.3 2.7 6 6 6h340c3.3 0 6-2.7 6-6z"></path>
                        </svg>
                        <input type="text" id="daterange" name="daterange" placeholder="Check-In - Check-Out" class="daterange-input" required autocomplete="off">
                        <input type="hidden" id="checkin_date" name="checkin_date">
                        <input type="hidden" id="checkout_date" name="checkout_date">
                    </div>
                    <div class="input-container destination">
                        <svg aria-hidden="true" class="e-font-icon-svg e-fas-map-marker-alt" viewBox="0 0 384 512" xmlns="http://www.w3.org/2000/svg">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"></path>
                        </svg>
                        <input type="text" id="destination" name="destination" placeholder="Destination" class="large" required>
                        <button type="button" id="clear-destination" class="clear-button">x</button>
                    </div>
                    <div class="input-container2">
                        <select name="unit_size" id="unit_size" required>
                            <option value="all" selected>All Unit Sizes</option>
                        </select>
                        <svg aria-hidden="true" class="e-font-icon-svg e-fas-user-plus" viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg">
                            <path d="M624 208h-64v-64c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v64h-64c-8.8 0-16 7.2-16 16v32c0 8.8 7.2 16 16 16h64v64c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16v-64h64c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16zm-400 48c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"></path>
                        </svg>
                    </div>
                    <div class="submit-button-container">
                        <a href="#" id="more-options-toggle">More Filters</a>
                        <input type="submit" name="submit_query" value="Search">
                    </div>
                    <div class="more-options-container" id="more-options-container" style="display: none;"></div>
                </form>
            </div>

            <div id="search-parameters" class="debug-info" style="text-align: right;"></div>
            <div id="query-stock-results"></div>

            <div id="iframeModal" class="modal-overlay">
                <div class="modal-content iframe-modal">
                    <span class="modal-close">&times;</span>
                    <div class="iframe-container">
                        <h3 id="iframeModalTitle">Information</h3>
                        <iframe id="iframeContent" src="" title="Property information"></iframe>
                    </div>
                </div>
            </div>

            <div id="bookNowModal" class="modal-overlay">
                <div class="modal-content booking-form-modal">
                    <span class="modal-close" data-close-booking>&times;</span>
                    <div class="booking-form-container">
                        <h3>Book Your Accommodation</h3>
                        <div id="booking-overview" class="booking-overview">
                            <h4>Accommodation Details</h4>
                            <div class="overview-content">
                                <div class="overview-item"><strong>Resort:</strong> <span id="overview-resort">-</span></div>
                                <div class="overview-item"><strong>Unit Type:</strong> <span id="overview-unit">-</span></div>
                                <div class="overview-item"><strong>Price:</strong> <span id="overview-price">-</span></div>
                                <div class="overview-item"><strong>Check-in:</strong> <span id="overview-checkin">-</span></div>
                                <div class="overview-item"><strong>Check-out:</strong> <span id="overview-checkout">-</span></div>
                            </div>
                        </div>
                        <div id="booking-extra-info" class="booking-extra-info" hidden></div>
                        <form id="booking-form" class="booking-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="booking_full_name">Full Name *</label>
                                    <input type="text" id="booking_full_name" name="full_name" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="booking_email">Email Address *</label>
                                    <input type="email" id="booking_email" name="email" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="booking_phone">Cellphone *</label>
                                    <input type="tel" id="booking_phone" name="phone" required placeholder="+27…">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="booking_adults">Adults (17+) *</label>
                                    <select id="booking_adults" name="adults" required>
                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"<?php echo $i === 2 ? ' selected' : ''; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="booking_children">Children (0–17)</label>
                                    <select id="booking_children" name="children">
                                        <?php for ($i = 0; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"<?php echo $i === 0 ? ' selected' : ''; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div id="booking-child-ages" class="booking-child-ages" hidden></div>
                            <div class="form-row" id="booking-meal-plan-row" hidden>
                                <div class="form-group">
                                    <label for="booking_meal_plan">Meal plan</label>
                                    <select id="booking_meal_plan" name="mealRatePlanId"></select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group full-width">
                                    <label for="booking_message">Additional message</label>
                                    <textarea id="booking_message" name="message" rows="3" placeholder="Any special requests…"></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn-cancel" data-close-booking>Cancel</button>
                                <button type="submit" class="btn-submit">Send booking request</button>
                            </div>
                        </form>
                        <div id="booking-form-message" class="form-message" hidden></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.AVANTE = { apiUrl: 'api/index.php' };
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="assets/js/custom-datepicker.js"></script>
    <script src="assets/js/autocomplete.js"></script>
    <script src="assets/js/search.js"></script>
</body>
</html>
