document.addEventListener('DOMContentLoaded', function() {
    var apiUrl = (window.AVANTE && window.AVANTE.apiUrl) ? window.AVANTE.apiUrl : 'api/index.php';
    var wrapper = document.getElementById('avante-travel-search-wrapper');
    if (!wrapper) {
        return;
    }

    var searchOptions = {};
    var form = wrapper.querySelector('#query-stock-form');
    var resultsNode = wrapper.querySelector('#query-stock-results');
    var paramsDiv = wrapper.querySelector('#search-parameters');
    var moreOptionsContainer = wrapper.querySelector('#more-options-container');
    var moreOptionsToggle = wrapper.querySelector('#more-options-toggle');
    var unitSelect = wrapper.querySelector('#unit_size');
    var banner = document.getElementById('avante-setup-banner');

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function apiGet(action, params) {
        var query = new URLSearchParams(Object.assign({ action: action }, params || {}));
        return fetch(apiUrl + '?' + query.toString()).then(function(res) {
            return res.json().then(function(data) {
                if (!res.ok || (data && data.ok === false)) {
                    throw new Error((data && data.error) || ('Request failed (' + res.status + ')'));
                }
                return data;
            });
        });
    }

    function applyTheme(config) {
        var root = document.documentElement;
        root.style.setProperty('--avante-main-color', config.main_color || '#0dcdc2');
        root.style.setProperty('--avante-secondary-color', config.secondary_color || '#172d72');
        root.style.setProperty('--avante-form-bg-color', config.form_bg_color || '#ffffff');
        root.style.setProperty('--avante-button-color', config.button_color || '#0dcdc2');
        if (config.hide_more_filters && moreOptionsToggle) {
            moreOptionsToggle.style.display = 'none';
        }
        if (banner) {
            banner.classList.toggle('is-visible', !config.hasToken);
        }
    }

    function populateUnitSizes(unitSizes) {
        if (!unitSelect || !Array.isArray(unitSizes) || !unitSizes.length) {
            return;
        }
        var selectedId = '';
        unitSizes.forEach(function(size) {
            var description = String(size.description || '').toLowerCase();
            var name = String(size.name || '').toLowerCase();
            if (description.indexOf('all unit sizes') !== -1 || name === 'all' || name.indexOf('all unit') !== -1) {
                selectedId = size.unitSizeId;
            }
        });
        unitSelect.innerHTML = unitSizes.map(function(size) {
            var selected = String(size.unitSizeId) === String(selectedId) ? ' selected' : '';
            return '<option value="' + esc(size.unitSizeId) + '"' + selected + '>' + esc(size.name) + '</option>';
        }).join('');
    }

    function renderFilterSection(title, items, filterName) {
        if (!Array.isArray(items) || !items.length) {
            return '';
        }
        var sorted = items.slice().sort(function(a, b) {
            return String(a.name || '').localeCompare(String(b.name || ''));
        });
        var cells = sorted.map(function(item) {
            if (!item || !item.name) {
                return '';
            }
            return '<div class="filter-item"><label><input type="checkbox" name="' + esc(filterName) + '[]" value="' + esc(item.amenityTypeId) + '"> ' + esc(item.name) + '</label></div>';
        }).join('');
        return '<div class="filter-section"><h4>' + esc(title) + '</h4><div class="filter-grid">' + cells + '</div></div>';
    }

    function populateFilters(options) {
        if (!moreOptionsContainer) {
            return;
        }
        moreOptionsContainer.innerHTML =
            renderFilterSection('Amenities', options.amenities, 'amenities') +
            renderFilterSection('Experiences', options.experiences, 'experiences') +
            renderFilterSection('Activities', options.activities, 'activities');
    }

    function formatMd(iso) {
        if (!iso) {
            return '';
        }
        var date = new Date(iso);
        if (isNaN(date.getTime())) {
            return '';
        }
        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
    }

    function minRate(resort) {
        var min = Number.POSITIVE_INFINITY;
        (resort.unitTypes || []).forEach(function(unit) {
            var rate = unit && unit.rate && unit.rate.rate;
            if (typeof rate === 'number' || (typeof rate === 'string' && rate !== '' && !isNaN(Number(rate)))) {
                min = Math.min(min, Number(rate));
            }
        });
        return min;
    }

    function bookingPayload(resort, unit) {
        var rate = unit.rate || {};
        var uniqueId = rate.uniqueId || rate.rateId || '';
        return {
            resortId: resort.resortId || '',
            resortName: resort.name || '',
            unitName: unit.name || '',
            unitNameId: unit.resortUnitTypeId || unit.roomId || '',
            unitSize: unit.unitSize || unit.sleeper || '',
            unitSizeTypeId: unit.unitSizeTypeId || '',
            uniqueStockId: uniqueId,
            reservationRateId: rate.rateId || uniqueId,
            amountIncl: rate.rate,
            currency: rate.currency || resort.currency || 'ZAR',
            currencySymbol: rate.currencySymbol || 'R',
            checkInDate: unit.checkInDate || '',
            checkOutDate: unit.checkOutDate || '',
            numberOfNights: unit.nights || 0,
            source: unit.source || '',
            sharingStockSourceId: unit.sharingSiteId || unit.sharedStockSourceId || 0,
            requiresAdditionalInformation: !!unit.requiresAdditionalInformation
        };
    }

    function renderUnit(unit, resort) {
        var unitName = unit.name || 'Room';
        var unitDescription = unit.description || 'No description available';
        var sleeper = unit.sleeper || 'N/A';
        var unitsAvailable = parseInt(unit.unitsAvailable, 10) || 0;
        var rate = (unit.rate && unit.rate.rate != null) ? unit.rate.rate : 'Price on request';
        var normalRate = (unit.rate && unit.rate.normalRate > 0) ? unit.rate.normalRate : rate;
        var nights = unit.nights || '';
        var checkInDate = formatMd(unit.checkInDate);
        var checkOutDate = formatMd(unit.checkOutDate);
        var payload = bookingPayload(resort, unit);
        var html = '<div class="sn_list_item_details">';
        html += '<div class="sn_room_size">';
        if (unit.url) {
            html += '<a href="#" data-url="' + esc(unit.url) + '" title="More information about ' + esc(unitName) + '" class="unit-info-link open-modal-button">';
            html += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="14" height="14">';
            html += '<path fill="orange" d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0z"/>';
            html += '<path fill="white" d="M232 152c0-13.3 10.7-24 24-24s24 10.7 24 24v24c0 13.3-10.7 24-24 24s-24-10.7-24-24v-24zm0 96h48c13.3 0 24 10.7 24 24v88c0 13.3-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24V272c0-13.3 10.7-24 24-24z"/>';
            html += '</svg></a>';
        }
        html += '<span class="sn_roomtype">' + esc(unitName) + '</span>';
        html += '<span class="sn_roomsize"> Sleeps: ' + esc(sleeper) + '</span>';
        if (unitsAvailable > 0) {
            html += '<span class="sn_units_available">Units Available: ' + esc(unitsAvailable) + '</span>';
        }
        html += '</div>';
        html += '<div class="sn_room_size"><span class="sn_roomdesc">' + esc(unitDescription) + '</span></div>';
        html += '<div class="sn_item_details">';
        if (checkInDate && checkOutDate) {
            html += '<div class="sn_dates"><span>' + esc(checkInDate) + '</span><span> - ' + esc(checkOutDate) + '</span></div>';
        }
        if (nights) {
            html += '<div class="sn_nights">Nights: ' + esc(nights) + '</div>';
        }
        html += '<div class="sn_price">';
        if (typeof normalRate === 'number' && typeof rate === 'number' && normalRate > rate) {
            html += '<span class="strike">R ' + esc(normalRate) + '</span> ';
        }
        html += 'R ' + esc(rate) + '</div>';
        if (payload.uniqueStockId) {
            html += '<span class="sn_book_now_single">';
            html += '<button type="button" class="stock_external book-now-button" data-booking="' + esc(JSON.stringify(payload)) + '">';
            html += '<span>Book Now</span></button></span>';
        }
        html += '</div></div>';
        return html;
    }

    function renderResults(data) {
        var resorts = Array.isArray(data.stockAvailability) ? data.stockAvailability.slice() : [];
        if (!resorts.length) {
            resultsNode.innerHTML = '<div class="no_results">It seems there are no available accommodations that fit your search. How about trying different dates or locations?</div>';
            return;
        }

        resorts.forEach(function(resort) {
            resort.min_rate_for_sorting = minRate(resort);
        });
        resorts.sort(function(a, b) {
            var featuredA = !!a.isFeaturedResort;
            var featuredB = !!b.isFeaturedResort;
            if (featuredA && !featuredB) return -1;
            if (!featuredA && featuredB) return 1;
            return a.min_rate_for_sorting - b.min_rate_for_sorting;
        });

        var html = '<div class="stock_wrapper">';
        resorts.forEach(function(resort, index) {
            if (!resort.name || !Array.isArray(resort.unitTypes) || !resort.unitTypes.length) {
                return;
            }
            var units = resort.unitTypes.slice().sort(function(a, b) {
                var rateA = (a.rate && !isNaN(Number(a.rate.rate))) ? Number(a.rate.rate) : Number.POSITIVE_INFINITY;
                var rateB = (b.rate && !isNaN(Number(b.rate.rate))) ? Number(b.rate.rate) : Number.POSITIVE_INFINITY;
                return rateA - rateB;
            });
            html += '<div class="stock_item">';
            if (resort.isFeaturedResort) {
                html += '<div class="featured-banner">Featured</div>';
            }
            html += '<div class="stock_item_header">';
            if (Array.isArray(resort.imageLinks) && resort.imageLinks.length) {
                html += '<div class="sn_imagebar"><section class="slideshow"><div class="slideshow-container">';
                resort.imageLinks.slice(0, 4).forEach(function(image, imageIndex) {
                    html += '<div class="res_img"><img src="' + esc(image.url) + '" alt="' + esc(resort.name) + ' image ' + (imageIndex + 1) + '"></div>';
                });
                html += '</div></section></div><hr>';
            }
            html += '<div class="stock_details"><h3 class="stock_title">';
            if (resort.resortInfoUrl) {
                html += '<a href="#" data-url="' + esc(resort.resortInfoUrl) + '" title="More information about ' + esc(resort.name) + '" class="resort-info-link open-modal-button">';
                html += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16">';
                html += '<path fill="navy" d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256s256-114.6 256-256S397.4 0 256 0z"/>';
                html += '<path fill="white" d="M232 152c0-13.3 10.7-24 24-24s24 10.7 24 24v24c0 13.3-10.7 24-24 24s-24-10.7-24-24v-24zm0 96h48c13.3 0 24 10.7 24 24v88c0 13.3-10.7 24-24 24h-48c-13.3 0-24-10.7-24-24V272c0-13.3 10.7-24 24-24z"/>';
                html += '</svg></a>';
            }
            html += esc(resort.name) + '</h3>';
            html += '<div class="stock_location"><svg class="location-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="11" height="14"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/></svg> ' + esc(resort.location || 'Location not available') + '</div>';
            html += '<div class="stock_description">' + esc(resort.description || 'No description available') + '</div>';
            html += '</div></div><div class="stock_availability">';

            units.forEach(function(unit, unitIndex) {
                var unitHtml = renderUnit(unit, resort);
                if (unitIndex === 0) {
                    html += unitHtml;
                } else if (unitIndex === 1) {
                    html += '<div class="show-more-container"><button class="show-more-button" data-target="hidden_units_' + index + '">More options</button></div>';
                    html += '<div class="hidden_units" id="hidden_units_' + index + '">' + unitHtml;
                } else {
                    html += unitHtml;
                }
            });
            if (units.length > 1) {
                html += '</div>';
            }
            html += '</div></div>';
        });
        html += '</div>';
        html += '<div class="avante-powered-by"><a href="https://avantetravel.co.za/" target="_blank" rel="noopener noreferrer">Powered by Avante Travel</a></div>';
        resultsNode.innerHTML = html;
    }

    function collectChecked(name) {
        return Array.from(form.querySelectorAll('input[name="' + name + '[]"]:checked')).map(function(input) {
            return input.value;
        });
    }

    function namesFor(key, ids) {
        var pool = searchOptions[key] || [];
        var map = {};
        pool.forEach(function(item) {
            if (item && item.amenityTypeId) {
                map[String(item.amenityTypeId)] = item.name || '';
            }
        });
        return ids.map(function(id) { return map[id]; }).filter(Boolean);
    }

    function updateParamsDisplay(payload) {
        if (!paramsDiv) {
            return;
        }
        var unitSizeName = '';
        if (Array.isArray(searchOptions.unitSizes)) {
            var found = searchOptions.unitSizes.find(function(unit) {
                return String(unit.unitSizeId) === String(payload.unit_size);
            });
            if (found && found.name) {
                unitSizeName = found.name;
            }
        }
        var parts = ['Search parameters: ' + payload.destination + ', ' + payload.checkin_date + ' to ' + payload.checkout_date];
        if (unitSizeName) {
            parts.push('Unit size: ' + unitSizeName);
        }
        var amenities = namesFor('amenities', payload.amenities);
        var experiences = namesFor('experiences', payload.experiences);
        var activities = namesFor('activities', payload.activities);
        if (amenities.length) parts.push('Amenities: ' + amenities.join(', '));
        if (experiences.length) parts.push('Experiences: ' + experiences.join(', '));
        if (activities.length) parts.push('Activities: ' + activities.join(', '));
        paramsDiv.textContent = parts.join(' | ');
    }

    function openIframeModal(url, title) {
        var modal = wrapper.querySelector('#iframeModal');
        var iframe = wrapper.querySelector('#iframeContent');
        var titleElement = wrapper.querySelector('#iframeModalTitle');
        if (!modal || !iframe || !titleElement) {
            return;
        }
        if (typeof jQuery !== 'undefined') {
            jQuery('#daterange').datepicker('hide');
            jQuery('.ui-datepicker').hide();
        }
        titleElement.textContent = title;
        iframe.src = url;
        iframe.style.height = Math.floor(window.innerHeight * 0.8) + 'px';
        modal.style.display = 'flex';
    }

    function closeIframeModal() {
        var modal = wrapper.querySelector('#iframeModal');
        var iframe = wrapper.querySelector('#iframeContent');
        if (modal) {
            modal.style.display = 'none';
        }
        if (iframe) {
            iframe.src = '';
        }
    }

    var bookingForm = wrapper.querySelector('#booking-form');
    var bookingModal = wrapper.querySelector('#bookNowModal');
    var bookingMessage = wrapper.querySelector('#booking-form-message');
    var extraInfoEl = wrapper.querySelector('#booking-extra-info');
    var childAgesEl = wrapper.querySelector('#booking-child-ages');
    var mealPlanRow = wrapper.querySelector('#booking-meal-plan-row');
    var mealPlanSelect = wrapper.querySelector('#booking_meal_plan');
    var currentBooking = null;

    function hideBookingMessage() {
        if (!bookingMessage) {
            return;
        }
        bookingMessage.hidden = true;
        bookingMessage.className = 'form-message';
        bookingMessage.textContent = '';
    }

    function showBookingMessage(type, html) {
        if (!bookingMessage) {
            return;
        }
        bookingMessage.hidden = false;
        bookingMessage.className = 'form-message ' + type;
        bookingMessage.innerHTML = html;
    }

    function childrenSelect() {
        return bookingForm ? bookingForm.elements.namedItem('children') : null;
    }

    function renderChildAges() {
        if (!childAgesEl || !bookingForm) {
            return;
        }
        var count = parseInt((childrenSelect() || {}).value, 10) || 0;
        if (count < 1) {
            childAgesEl.innerHTML = '';
            childAgesEl.hidden = true;
            return;
        }
        var html = '';
        for (var i = 0; i < count; i++) {
            html += '<div class="form-group"><label>Child ' + (i + 1) + ' age *';
            html += '<input type="number" min="0" max="17" name="child_age_' + i + '" required></label></div>';
        }
        childAgesEl.innerHTML = html;
        childAgesEl.hidden = false;
    }

    function collectChildAges() {
        if (!childAgesEl) {
            return [];
        }
        return Array.from(childAgesEl.querySelectorAll('input')).map(function(input) {
            return parseInt(input.value, 10);
        });
    }

    function closeBookingModal() {
        if (!bookingModal) {
            return;
        }
        bookingModal.style.display = 'none';
        currentBooking = null;
        if (bookingForm) {
            bookingForm.hidden = false;
            bookingForm.reset();
            bookingForm.elements.adults.value = '2';
            childrenSelect().value = '0';
            renderChildAges();
        }
        if (extraInfoEl) {
            extraInfoEl.hidden = true;
            extraInfoEl.innerHTML = '';
        }
        if (mealPlanRow) {
            mealPlanRow.hidden = true;
        }
        hideBookingMessage();
    }

    function applyExtraInfo(info) {
        if (!info) {
            return true;
        }
        var notes = [];
        if (info.maxOccupancy) {
            notes.push('Sleeps up to ' + info.maxOccupancy);
        }
        if (info.maxAdults) {
            notes.push('Max adults: ' + info.maxAdults);
        }
        if (info.minLOS && info.minLOS > 1) {
            notes.push('Minimum stay: ' + info.minLOS + ' nights');
        }
        if (info.childPolicy && info.childPolicy.childrenAllowed === false) {
            notes.push('Children are not allowed');
        }
        if (info.cancellationPolicy && info.cancellationPolicy.description) {
            notes.push(info.cancellationPolicy.description);
        }
        if (extraInfoEl) {
            extraInfoEl.innerHTML = notes.map(function(note) { return '<p>' + esc(note) + '</p>'; }).join('');
            extraInfoEl.hidden = notes.length === 0;
        }
        var plans = Array.isArray(info.mealPlans) ? info.mealPlans : [];
        if (mealPlanRow && mealPlanSelect) {
            if (!plans.length) {
                mealPlanRow.hidden = true;
                mealPlanSelect.innerHTML = '';
            } else {
                mealPlanSelect.innerHTML = plans.map(function(plan) {
                    var label = plan.description || 'Meal plan';
                    if (plan.hasAdditionalCharge && plan.additionalCharge) {
                        label += ' (+R ' + plan.additionalCharge + ')';
                    }
                    var selected = plan.isDefaultMealPlan ? ' selected' : '';
                    return '<option value="' + esc(plan.mealPlanRateId) + '"' + selected + '>' + esc(label) + '</option>';
                }).join('');
                mealPlanRow.hidden = false;
            }
        }
        if (info.reservationCanContinue === false) {
            showBookingMessage('error', esc(info.errorMessage || 'This unit cannot be booked for those dates.'));
            return false;
        }
        return true;
    }

    function openBookingModal(payload) {
        if (!bookingModal || !bookingForm || !payload) {
            return;
        }
        currentBooking = payload;
        hideBookingMessage();
        bookingForm.hidden = false;
        bookingForm.reset();
        bookingForm.elements.adults.value = '2';
        childrenSelect().value = '0';
        renderChildAges();
        if (extraInfoEl) {
            extraInfoEl.hidden = true;
            extraInfoEl.innerHTML = '';
        }
        if (mealPlanRow) {
            mealPlanRow.hidden = true;
        }
        wrapper.querySelector('#overview-resort').textContent = payload.resortName || 'Not specified';
        wrapper.querySelector('#overview-unit').textContent = payload.unitName || 'Not specified';
        wrapper.querySelector('#overview-price').textContent = payload.amountIncl != null ? 'R ' + payload.amountIncl : 'Not specified';
        wrapper.querySelector('#overview-checkin').textContent = formatMd(payload.checkInDate) || payload.checkInDate || 'Not specified';
        wrapper.querySelector('#overview-checkout').textContent = formatMd(payload.checkOutDate) || payload.checkOutDate || 'Not specified';
        if (typeof jQuery !== 'undefined') {
            jQuery('#daterange').datepicker('hide');
            jQuery('.ui-datepicker').hide();
        }
        bookingModal.style.display = 'flex';
        bookingForm.elements.full_name.focus();

        if (!payload.requiresAdditionalInformation) {
            return;
        }
        if (extraInfoEl) {
            extraInfoEl.hidden = false;
            extraInfoEl.innerHTML = '<p>Checking stay requirements…</p>';
        }
        fetch(apiUrl + '?action=additional_info', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                resortId: payload.resortId,
                roomId: payload.unitNameId,
                checkInDate: payload.checkInDate,
                checkOutDate: payload.checkOutDate
            })
        }).then(function(res) {
            return res.json().then(function(data) {
                if (!res.ok || data.ok === false) {
                    throw new Error(data.error || 'Could not load stay requirements.');
                }
                return data.info || {};
            });
        }).then(function(info) {
            currentBooking.extraInfo = info;
            applyExtraInfo(info);
        }).catch(function(error) {
            if (extraInfoEl) {
                extraInfoEl.innerHTML = '';
                extraInfoEl.hidden = true;
            }
            showBookingMessage('error', esc(error.message));
        });
    }

    if (moreOptionsToggle && moreOptionsContainer) {
        moreOptionsToggle.addEventListener('click', function(event) {
            event.preventDefault();
            var hidden = moreOptionsContainer.style.display === 'none' || moreOptionsContainer.style.display === '';
            moreOptionsContainer.style.display = hidden ? 'block' : 'none';
            moreOptionsToggle.textContent = hidden ? 'Less Filters' : 'More Filters';
        });
    }

    wrapper.addEventListener('click', function(event) {
        var showMore = event.target.closest('.show-more-button');
        if (showMore) {
            event.preventDefault();
            var target = document.getElementById(showMore.getAttribute('data-target'));
            if (!target) {
                return;
            }
            if (target.style.maxHeight) {
                target.style.maxHeight = null;
                showMore.textContent = 'More options';
            } else {
                target.style.maxHeight = target.scrollHeight + 'px';
                showMore.textContent = 'Less options';
            }
            return;
        }

        var bookButton = event.target.closest('.book-now-button');
        if (bookButton) {
            event.preventDefault();
            try {
                openBookingModal(JSON.parse(bookButton.getAttribute('data-booking') || '{}'));
            } catch (e) {
                showBookingMessage('error', 'Could not open this booking. Please search again.');
            }
            return;
        }

        if (event.target.closest('[data-close-booking]') || event.target === bookingModal) {
            closeBookingModal();
            return;
        }

        var openButton = event.target.closest('.open-modal-button');
        if (openButton) {
            event.preventDefault();
            var url = openButton.getAttribute('data-url');
            if (!url) {
                return;
            }
            if (openButton.classList.contains('resort-info-link')) {
                openIframeModal(url, 'Resort Information');
            } else if (openButton.classList.contains('unit-info-link')) {
                openIframeModal(url, 'Unit Information');
            }
            return;
        }

        if (event.target.classList.contains('modal-close') || event.target.id === 'iframeModal') {
            closeIframeModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && bookingModal && bookingModal.style.display === 'flex') {
            closeBookingModal();
        }
    });

    if (childrenSelect()) {
        childrenSelect().addEventListener('change', renderChildAges);
    }

    if (bookingForm) {
        bookingForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (!currentBooking) {
                showBookingMessage('error', 'Please choose a unit again.');
                return;
            }
            var children = parseInt((childrenSelect() || {}).value, 10) || 0;
            var childAges = collectChildAges();
            if (children !== childAges.length || childAges.some(function(age) { return isNaN(age); })) {
                showBookingMessage('error', 'Please enter an age for each child.');
                return;
            }
            var submit = bookingForm.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Sending...';
            }
            hideBookingMessage();
            var body = {
                fullName: bookingForm.elements.full_name.value,
                emailAddress: bookingForm.elements.email.value,
                cellphone: bookingForm.elements.phone.value,
                notes: bookingForm.elements.message.value,
                adultOccupancy: bookingForm.elements.adults.value,
                childOccupancy: children,
                childAges: childAges,
                mealRatePlanId: mealPlanSelect && !mealPlanRow.hidden ? mealPlanSelect.value : null,
                resortId: currentBooking.resortId,
                resortName: currentBooking.resortName,
                unitName: currentBooking.unitName,
                unitNameId: currentBooking.unitNameId,
                unitSize: currentBooking.unitSize,
                unitSizeTypeId: currentBooking.unitSizeTypeId,
                uniqueStockId: currentBooking.uniqueStockId,
                reservationRateId: currentBooking.reservationRateId,
                amountIncl: currentBooking.amountIncl,
                currency: currentBooking.currency,
                currencySymbol: currentBooking.currencySymbol,
                checkInDate: currentBooking.checkInDate,
                checkOutDate: currentBooking.checkOutDate,
                numberOfNights: currentBooking.numberOfNights,
                source: currentBooking.source,
                sharingStockSourceId: currentBooking.sharingStockSourceId,
                requiresAdditionalInformation: currentBooking.requiresAdditionalInformation
            };
            fetch(apiUrl + '?action=accommodation_booking', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            }).then(function(res) {
                return res.json().then(function(data) {
                    if (!res.ok || data.ok === false) {
                        throw new Error(data.error || 'Could not send booking request.');
                    }
                    return data;
                });
            }).then(function(data) {
                bookingForm.hidden = true;
                var ref = data.reservationRefNo ? ' Reference: ' + data.reservationRefNo + '.' : '';
                showBookingMessage('success', '<p><strong>Thank you.</strong> Your booking request has been sent to Stock Network.' + esc(ref) + '</p>');
            }).catch(function(error) {
                showBookingMessage('error', esc(error.message));
            }).finally(function() {
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Send booking request';
                }
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (moreOptionsContainer) {
                moreOptionsContainer.style.display = 'none';
            }
            if (moreOptionsToggle) {
                moreOptionsToggle.textContent = 'More Filters';
            }

            var payload = {
                checkin_date: form.checkin_date.value,
                checkout_date: form.checkout_date.value,
                destination: form.destination.value,
                unit_size: form.unit_size.value,
                amenities: collectChecked('amenities'),
                experiences: collectChecked('experiences'),
                activities: collectChecked('activities')
            };

            if (!payload.checkin_date || !payload.checkout_date) {
                resultsNode.innerHTML = '<div class="error">Please choose check-in and check-out dates.</div>';
                return;
            }

            updateParamsDisplay(payload);
            resultsNode.innerHTML = '<div class="loading">Searching for available properties...</div>';

            fetch(apiUrl + '?action=search', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function(res) {
                return res.json().then(function(data) {
                    if (!res.ok || data.ok === false) {
                        throw new Error(data.error || ('Search failed (' + res.status + ')'));
                    }
                    return data;
                });
            }).then(renderResults).catch(function(error) {
                resultsNode.innerHTML = '<div class="error-message-api">' + esc(error.message || 'Search failed. Please try again.') + '</div>';
            });
        });
    }

    apiGet('config').then(applyTheme).catch(function() {});
    apiGet('options').then(function(options) {
        searchOptions = options;
        populateUnitSizes(options.unitSizes);
        populateFilters(options);
        if (options.siteCode) {
            console.log('Site Code:', options.siteCode);
        }
    }).catch(function(error) {
        if (resultsNode) {
            resultsNode.innerHTML = '<div class="error-message-api">' + esc(error.message) + '</div>';
        }
    });
});
