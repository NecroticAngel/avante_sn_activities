document.addEventListener('DOMContentLoaded', function() {
    var apiUrl = (window.AVANTE && window.AVANTE.apiUrl) ? window.AVANTE.apiUrl : 'api/index.php';
    var grid = document.getElementById('activity-grid');
    var filtersEl = document.getElementById('activity-filters');
    var queryEl = document.getElementById('activity-query');
    var countEl = document.getElementById('activity-count');
    var activities = [];
    var activeCategory = 'All';

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function telHref(phone) {
        return 'tel:' + String(phone).replace(/[^\d+]/g, '');
    }

    function mapsHref(item) {
        if (item.lat == null || item.lng == null) {
            return '';
        }
        return 'https://www.google.com/maps?q=' + encodeURIComponent(item.lat + ',' + item.lng);
    }

    function renderFilters(categories) {
        var cats = ['All'].concat(categories || []);
        filtersEl.innerHTML = cats.map(function(cat) {
            var active = cat === activeCategory ? ' is-active' : '';
            return '<button type="button" class="activity-filter' + active + '" data-category="' + esc(cat) + '">' + esc(cat) + '</button>';
        }).join('');
    }

    function visibleItems() {
        var q = (queryEl.value || '').trim().toLowerCase();
        return activities.filter(function(item) {
            if (activeCategory !== 'All' && item.category !== activeCategory) {
                return false;
            }
            if (!q) {
                return true;
            }
            var blob = [item.name, item.company, item.location, item.description, item.category].join(' ').toLowerCase();
            return blob.indexOf(q) !== -1;
        });
    }

    function render() {
        var items = visibleItems();
        countEl.textContent = items.length + ' of ' + activities.length + ' activities';
        if (!items.length) {
            grid.innerHTML = '<div class="activity-empty">No activities match that filter. Try another category or search.</div>';
            return;
        }
        grid.innerHTML = items.map(function(item) {
            var img = item.image_url
                ? '<img src="' + esc(item.image_url) + '" alt="' + esc(item.name) + '">'
                : '';
            var map = mapsHref(item);
            var html = '<article class="activity-card">';
            html += '<div class="activity-card-media">' + img + '<span class="activity-price-tag">' + esc(item.price_label || 'Variable') + '</span></div>';
            html += '<div class="activity-card-body">';
            html += '<p class="activity-kicker">' + esc(item.category) + '</p>';
            html += '<h2>' + esc(item.name) + '</h2>';
            html += '<p class="activity-operator">' + esc(item.company) + (item.location ? ' · ' + esc(item.location) : '') + '</p>';
            if (item.description) {
                html += '<p class="activity-desc">' + esc(item.description) + '</p>';
            }
            html += '<div class="activity-meta">';
            if (item.phone) {
                html += '<a href="' + esc(telHref(item.phone)) + '">' + esc(item.phone) + '</a>';
            }
            if (item.email) {
                html += '<a href="mailto:' + esc(item.email) + '">' + esc(item.email) + '</a>';
            }
            if (map) {
                html += '<a href="' + esc(map) + '" target="_blank" rel="noopener noreferrer">Map</a>';
            }
            html += '</div>';
            html += '<button type="button" class="activity-book" data-book-activity="' + esc(item.id) + '">Make booking</button>';
            html += '</div></article>';
            return html;
        }).join('');
    }

    filtersEl.addEventListener('click', function(event) {
        var button = event.target.closest('[data-category]');
        if (!button) {
            return;
        }
        activeCategory = button.getAttribute('data-category') || 'All';
        Array.from(filtersEl.querySelectorAll('.activity-filter')).forEach(function(el) {
            el.classList.toggle('is-active', el.getAttribute('data-category') === activeCategory);
        });
        render();
    });

    queryEl.addEventListener('input', render);

    var modal = document.getElementById('activityBookingModal');
    var form = document.getElementById('activity-booking-form');
    var overview = document.getElementById('activity-booking-overview');
    var messageEl = document.getElementById('activity-booking-message');
    var byId = {};

    function closeModal() {
        if (!modal) {
            return;
        }
        modal.hidden = true;
        if (form) {
            form.hidden = false;
            form.reset();
            form.people.value = '2';
        }
        if (messageEl) {
            messageEl.hidden = true;
            messageEl.textContent = '';
        }
    }

    function openModal(item) {
        if (!modal || !form || !item) {
            return;
        }
        form.activity_id.value = item.id;
        overview.innerHTML =
            '<p><strong>' + esc(item.name) + '</strong></p>' +
            '<p>' + esc(item.company) + (item.price_label ? ' · ' + esc(item.price_label) : '') + '</p>';
        form.hidden = false;
        messageEl.hidden = true;
        modal.hidden = false;
        form.full_name.focus();
    }

    document.addEventListener('click', function(event) {
        var bookBtn = event.target.closest('[data-book-activity]');
        if (bookBtn) {
            event.preventDefault();
            openModal(byId[bookBtn.getAttribute('data-book-activity')]);
            return;
        }
        if (event.target.closest('[data-close-booking]') || event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });

    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            var submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
                submit.textContent = 'Sending...';
            }
            fetch(apiUrl + '?action=activity_booking', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    activity_id: form.activity_id.value,
                    full_name: form.full_name.value,
                    email: form.email.value,
                    phone: form.phone.value,
                    preferred_date: form.preferred_date.value,
                    people: form.people.value,
                    message: form.message.value
                })
            }).then(function(res) {
                return res.json().then(function(data) {
                    if (!res.ok || data.ok === false) {
                        throw new Error(data.error || 'Could not send booking request.');
                    }
                    return data;
                });
            }).then(function() {
                form.hidden = true;
                messageEl.hidden = false;
                messageEl.className = 'activity-booking-message is-ok';
                messageEl.innerHTML = '<p><strong>Thank you.</strong> We have your request and will be in touch to confirm.</p>';
                setTimeout(closeModal, 2800);
            }).catch(function(error) {
                messageEl.hidden = false;
                messageEl.className = 'activity-booking-message is-bad';
                messageEl.textContent = error.message;
            }).finally(function() {
                if (submit) {
                    submit.disabled = false;
                    submit.textContent = 'Send booking request';
                }
            });
        });
    }

    fetch(apiUrl + '?action=activities').then(function(res) {
        return res.json().then(function(data) {
            if (!res.ok || data.ok === false) {
                throw new Error(data.error || 'Could not load activities.');
            }
            return data;
        });
    }).then(function(data) {
        activities = data.activities || [];
        byId = {};
        activities.forEach(function(item) {
            byId[item.id] = item;
        });
        renderFilters(data.categories);
        render();
    }).catch(function(error) {
        grid.innerHTML = '<div class="activity-error">' + esc(error.message) + '</div>';
    });
});
