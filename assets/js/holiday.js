document.addEventListener('DOMContentLoaded', function() {
    var apiUrl = (window.AVANTE && window.AVANTE.apiUrl) ? window.AVANTE.apiUrl : 'api/index.php';
    var stayResults = document.getElementById('holiday-stay-results');
    var filtersEl = document.getElementById('activity-filters');
    var queryEl = document.getElementById('activity-query');
    var grid = document.getElementById('activity-grid');
    var trayBody = document.getElementById('holiday-tray-body');
    var checkoutBtn = document.getElementById('holiday-open-checkout');
    var modal = document.getElementById('holidayCheckoutModal');
    var form = document.getElementById('holiday-checkout-form');
    var summaryEl = document.getElementById('holiday-checkout-summary');
    var messageEl = document.getElementById('holiday-checkout-message');
    var childAgesEl = document.getElementById('holiday-child-ages');
    var searchForm = document.getElementById('query-stock-form');
    var unitSelect = document.getElementById('unit_size');
    var activities = [];
    var lastStays = [];
    var stayByKey = {};
    var activeCategory = 'All';
    var trip = { stay: null, activities: [] };
    var activityDateWrap = document.getElementById('holiday-activity-date-wrap');

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function isoDay(value) {
        if (!value) {
            return '';
        }
        return String(value).slice(0, 10);
    }

    function formatDay(value) {
        var day = isoDay(value);
        if (!day) {
            return '';
        }
        var date = new Date(day + 'T00:00:00');
        if (isNaN(date.getTime())) {
            return day;
        }
        return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function stayPayload(resort, unit) {
        var rate = unit.rate || {};
        var uniqueId = rate.uniqueId || rate.rateId || '';
        var image = (resort.imageLinks && resort.imageLinks[0] && resort.imageLinks[0].url) || '';
        return {
            resortId: resort.resortId || '',
            resortName: resort.name || '',
            location: resort.location || '',
            image: image,
            unitName: unit.name || '',
            unitNameId: unit.resortUnitTypeId || '',
            unitSize: unit.unitSize || unit.sleeper || '',
            unitSizeTypeId: unit.unitSizeTypeId || '',
            uniqueStockId: uniqueId,
            reservationRateId: uniqueId,
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

    function stayKey(payload) {
        return [payload.uniqueStockId, isoDay(payload.checkInDate), payload.unitNameId].join('|');
    }

    function defaultActivityDate() {
        return isoDay(trip.stay && trip.stay.checkInDate) || isoDay(searchForm.checkin_date.value);
    }

    function renderTray() {
        var html = '';
        if (!trip.stay && !trip.activities.length) {
            html = '<p class="holiday-tray-empty">Nothing in this holiday yet. Add a stay or an activity.</p>';
        }
        if (trip.stay) {
            html += '<div class="holiday-tray-item"><strong>' + esc(trip.stay.resortName) + '</strong>';
            html += esc(trip.stay.unitName) + ' · ' + esc(formatDay(trip.stay.checkInDate)) + ' – ' + esc(formatDay(trip.stay.checkOutDate));
            if (trip.stay.amountIncl != null) {
                html += '<div>R ' + esc(trip.stay.amountIncl) + '</div>';
            }
            html += '<button type="button" data-remove-stay>Remove stay</button></div>';
        }
        trip.activities.forEach(function(item) {
            html += '<div class="holiday-tray-item"><strong>' + esc(item.name) + '</strong>';
            html += esc(item.company) + (item.preferred_date ? ' · ' + esc(formatDay(item.preferred_date)) : '');
            html += '<button type="button" data-remove-activity="' + esc(item.id) + '">Remove</button></div>';
        });
        trayBody.innerHTML = html;
        checkoutBtn.disabled = !trip.stay && !trip.activities.length;
        renderActivities();
    }

    function visibleActivities() {
        var q = (queryEl.value || '').trim().toLowerCase();
        return activities.filter(function(item) {
            if (activeCategory !== 'All' && item.category !== activeCategory) {
                return false;
            }
            if (!q) {
                return true;
            }
            return [item.name, item.company, item.location, item.description, item.category].join(' ').toLowerCase().indexOf(q) !== -1;
        });
    }

    function renderFilters(categories) {
        var cats = ['All'].concat(categories || []);
        filtersEl.innerHTML = cats.map(function(cat) {
            var active = cat === activeCategory ? ' is-active' : '';
            return '<button type="button" class="activity-filter' + active + '" data-category="' + esc(cat) + '">' + esc(cat) + '</button>';
        }).join('');
    }

    function renderActivities() {
        var items = visibleActivities();
        if (!items.length) {
            grid.innerHTML = '<div class="activity-empty">No activities match that filter.</div>';
            return;
        }
        var added = {};
        trip.activities.forEach(function(item) { added[item.id] = true; });
        grid.innerHTML = items.map(function(item) {
            var inTrip = !!added[item.id];
            var html = '<article class="activity-card">';
            html += '<div class="activity-card-media">';
            if (item.image_url) {
                html += '<img src="' + esc(item.image_url) + '" alt="' + esc(item.name) + '">';
            }
            html += '<span class="activity-price-tag">' + esc(item.price_label || 'Variable') + '</span></div>';
            html += '<div class="activity-card-body">';
            html += '<p class="activity-kicker">' + esc(item.category) + '</p>';
            html += '<h2>' + esc(item.name) + '</h2>';
            html += '<p class="activity-operator">' + esc(item.company) + '</p>';
            html += '<button type="button" class="activity-book' + (inTrip ? ' is-in' : '') + '" data-add-activity="' + esc(item.id) + '">';
            html += inTrip ? 'Added' : 'Add to holiday';
            html += '</button></div></article>';
            return html;
        }).join('');
    }

    function renderStays(resorts) {
        lastStays = resorts || [];
        stayByKey = {};
        if (!lastStays.length) {
            stayResults.innerHTML = '<p class="holiday-empty">No stays matched. Try different dates or a nearby town.</p>';
            return;
        }
        var html = '';
        lastStays.forEach(function(resort) {
            (resort.unitTypes || []).slice(0, 4).forEach(function(unit) {
                var payload = stayPayload(resort, unit);
                if (!payload.uniqueStockId) {
                    return;
                }
                var key = stayKey(payload);
                stayByKey[key] = payload;
                var selected = trip.stay && stayKey(trip.stay) === key;
                html += '<article class="holiday-stay-card">';
                html += payload.image ? '<img src="' + esc(payload.image) + '" alt="">' : '<div></div>';
                html += '<div><h3>' + esc(payload.resortName) + '</h3>';
                html += '<p>' + esc(payload.unitName) + ' · ' + esc(payload.unitSize) + '</p>';
                html += '<p>' + esc(formatDay(payload.checkInDate)) + ' – ' + esc(formatDay(payload.checkOutDate));
                if (payload.amountIncl != null) {
                    html += ' · R ' + esc(payload.amountIncl);
                }
                html += '</p></div>';
                html += '<button type="button" class="holiday-add' + (selected ? ' is-in' : '') + '" data-add-stay="' + esc(key) + '">';
                html += selected ? 'Selected' : 'Add stay';
                html += '</button></article>';
            });
        });
        stayResults.innerHTML = html || '<p class="holiday-empty">Those stays cannot be booked from here. Try another unit.</p>';
    }

    function renderChildAges() {
        var count = parseInt(form.elements.children.value, 10) || 0;
        if (count < 1) {
            childAgesEl.innerHTML = '';
            childAgesEl.hidden = true;
            return;
        }
        var html = '';
        for (var i = 0; i < count; i++) {
            html += '<div class="form-group"><label>Child ' + (i + 1) + ' age *<input type="number" min="0" max="17" name="child_age_' + i + '" required></label></div>';
        }
        childAgesEl.innerHTML = html;
        childAgesEl.hidden = false;
    }

    function openCheckout() {
        var lines = [];
        if (trip.stay) {
            lines.push('<p><strong>' + esc(trip.stay.resortName) + '</strong> · ' + esc(trip.stay.unitName) + '</p>');
        }
        trip.activities.forEach(function(item) {
            lines.push('<p>' + esc(item.name) + ' · ' + esc(item.company) + '</p>');
        });
        summaryEl.innerHTML = lines.join('');
        form.hidden = false;
        messageEl.hidden = true;
        if (activityDateWrap) {
            var needsDate = trip.activities.length > 0 && !defaultActivityDate();
            activityDateWrap.hidden = !needsDate;
            form.elements.activity_date.required = needsDate;
            if (!needsDate) {
                form.elements.activity_date.value = defaultActivityDate();
            }
        }
        modal.hidden = false;
        form.elements.full_name.focus();
    }

    function closeCheckout() {
        modal.hidden = true;
    }

    searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var payload = {
            checkin_date: searchForm.checkin_date.value,
            checkout_date: searchForm.checkout_date.value,
            destination: searchForm.destination.value,
            unit_size: searchForm.unit_size.value,
            amenities: [],
            experiences: [],
            activities: []
        };
        if (!payload.checkin_date || !payload.checkout_date) {
            stayResults.innerHTML = '<p class="holiday-empty">Choose check-in and check-out dates.</p>';
            return;
        }
        stayResults.innerHTML = '<p class="holiday-hint">Searching stays…</p>';
        fetch(apiUrl + '?action=search', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function(res) {
            return res.json().then(function(data) {
                if (!res.ok || data.ok === false) {
                    throw new Error(data.error || 'Search failed.');
                }
                return data;
            });
        }).then(function(data) {
            renderStays(data.stockAvailability || []);
        }).catch(function(error) {
            stayResults.innerHTML = '<p class="holiday-empty">' + esc(error.message) + '</p>';
        });
    });

    document.addEventListener('click', function(event) {
        var addStay = event.target.closest('[data-add-stay]');
        if (addStay) {
            var chosen = stayByKey[addStay.getAttribute('data-add-stay')];
            if (chosen) {
                trip.stay = chosen;
                trip.activities.forEach(function(item) {
                    if (!item.preferred_date) {
                        item.preferred_date = isoDay(chosen.checkInDate);
                    }
                });
            }
            renderTray();
            renderStays(lastStays);
            return;
        }
        var addActivity = event.target.closest('[data-add-activity]');
        if (addActivity) {
            var id = addActivity.getAttribute('data-add-activity');
            var found = activities.filter(function(item) { return item.id === id; })[0];
            if (found && trip.activities.some(function(item) { return item.id === id; })) {
                trip.activities = trip.activities.filter(function(item) { return item.id !== id; });
            } else if (found) {
                trip.activities.push({
                    id: found.id,
                    activity_id: found.id,
                    name: found.name,
                    company: found.company,
                    preferred_date: defaultActivityDate()
                });
            }
            renderTray();
            return;
        }
        if (event.target.closest('[data-remove-stay]')) {
            trip.stay = null;
            renderTray();
            if (lastStays.length) {
                renderStays(lastStays);
            }
            return;
        }
        var removeActivity = event.target.closest('[data-remove-activity]');
        if (removeActivity) {
            var removeId = removeActivity.getAttribute('data-remove-activity');
            trip.activities = trip.activities.filter(function(item) { return item.id !== removeId; });
            renderTray();
            return;
        }
        if (event.target.closest('[data-close-checkout]') || event.target === modal) {
            closeCheckout();
        }
    });

    filtersEl.addEventListener('click', function(event) {
        var button = event.target.closest('[data-category]');
        if (!button) {
            return;
        }
        activeCategory = button.getAttribute('data-category') || 'All';
        Array.from(filtersEl.querySelectorAll('.activity-filter')).forEach(function(el) {
            el.classList.toggle('is-active', el.getAttribute('data-category') === activeCategory);
        });
        renderActivities();
    });

    queryEl.addEventListener('input', renderActivities);
    checkoutBtn.addEventListener('click', openCheckout);
    form.elements.children.addEventListener('change', renderChildAges);
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeCheckout();
        }
    });

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        var children = parseInt(form.elements.children.value, 10) || 0;
        var adults = parseInt(form.elements.adults.value, 10) || 0;
        var childAges = Array.from(childAgesEl.querySelectorAll('input')).map(function(input) {
            return parseInt(input.value, 10);
        });
        if (children !== childAges.length || childAges.some(function(age) { return isNaN(age); })) {
            messageEl.hidden = false;
            messageEl.className = 'activity-booking-message is-bad';
            messageEl.textContent = 'Please enter an age for each child.';
            return;
        }
        var activityDate = isoDay(form.elements.activity_date && form.elements.activity_date.value) || defaultActivityDate();
        var peopleCount = Math.max(1, adults + children);
        var checkoutActivities = trip.activities.map(function(item) {
            return {
                activity_id: item.activity_id || item.id,
                name: item.name,
                preferred_date: isoDay(item.preferred_date) || activityDate,
                people: String(peopleCount)
            };
        });
        if (checkoutActivities.some(function(item) { return !item.preferred_date; })) {
            messageEl.hidden = false;
            messageEl.className = 'activity-booking-message is-bad';
            messageEl.textContent = 'Choose a stay or an activity date so we know when to book the activities.';
            return;
        }
        var submit = form.querySelector('button[type="submit"]');
        submit.disabled = true;
        submit.textContent = 'Sending...';
        fetch(apiUrl + '?action=holiday_checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                guest: {
                    fullName: form.elements.full_name.value,
                    email: form.elements.email.value,
                    phone: form.elements.phone.value,
                    notes: form.elements.message.value,
                    adults: adults,
                    children: children,
                    childAges: childAges,
                    membershipNo: form.elements.membership_no.value,
                    activityDate: activityDate
                },
                stay: trip.stay,
                activities: checkoutActivities
            })
        }).then(function(res) {
            return res.json().then(function(data) {
                if (!res.ok || data.ok === false) {
                    throw new Error(data.error || 'Could not check out.');
                }
                return data;
            });
        }).then(function(data) {
            form.hidden = true;
            var bits = ['<p><strong>Thank you.</strong> Your holiday request is in.</p>'];
            if (data.packageRef) {
                bits.push('<p>Package: ' + esc(data.packageRef) + '</p>');
            }
            if (data.stay && data.stay.reservationRefNo) {
                bits.push('<p>Stay: ' + esc(data.stay.reservationRefNo) + ' · <a href="manage.php?ref=' + encodeURIComponent(data.stay.reservationRefNo) + '">Manage</a></p>');
            }
            (data.activities || []).forEach(function(item) {
                if (item.reference) {
                    bits.push('<p>Activity: ' + esc(item.reference) + '</p>');
                }
            });
            if (data.errors && data.errors.length) {
                bits.push('<p>' + esc(data.errors.join(' ')) + '</p>');
            }
            messageEl.hidden = false;
            messageEl.className = 'activity-booking-message is-ok';
            messageEl.innerHTML = bits.join('');
            trip = { stay: null, activities: [] };
            renderTray();
        }).catch(function(error) {
            messageEl.hidden = false;
            messageEl.className = 'activity-booking-message is-bad';
            messageEl.textContent = error.message;
        }).finally(function() {
            submit.disabled = false;
            submit.textContent = 'Send holiday request';
        });
    });

    fetch(apiUrl + '?action=options').then(function(res) { return res.json(); }).then(function(options) {
        if (!unitSelect || !Array.isArray(options.unitSizes)) {
            return;
        }
        var selectedId = '';
        options.unitSizes.forEach(function(size) {
            var name = String(size.name || '').toLowerCase();
            if (name === 'all' || name.indexOf('all unit') !== -1) {
                selectedId = size.unitSizeId;
            }
        });
        unitSelect.innerHTML = options.unitSizes.map(function(size) {
            var selected = String(size.unitSizeId) === String(selectedId) ? ' selected' : '';
            return '<option value="' + esc(size.unitSizeId) + '"' + selected + '>' + esc(size.name) + '</option>';
        }).join('');
    }).catch(function() {});

    fetch(apiUrl + '?action=activities').then(function(res) {
        return res.json().then(function(data) {
            if (!res.ok || data.ok === false) {
                throw new Error(data.error || 'Could not load activities.');
            }
            return data;
        });
    }).then(function(data) {
        activities = data.activities || [];
        renderFilters(data.categories);
        renderActivities();
    }).catch(function(error) {
        grid.innerHTML = '<div class="activity-error">' + esc(error.message) + '</div>';
    });

    renderTray();
});
