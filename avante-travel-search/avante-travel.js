document.addEventListener('DOMContentLoaded', function() {
    // Display version and siteCode if available
    if (typeof avante_travel_data !== 'undefined') {
        if (avante_travel_data.version) {
            console.log('Avante Travel Plugin Version:', avante_travel_data.version);
        }
        if (avante_travel_data.site_code) {
            console.log('Site Code:', avante_travel_data.site_code);
        }
    }
    
    var wrapper = document.getElementById('avante-travel-search-wrapper');
    if (!wrapper) {
        return;
    }

    var ajaxurl = avante_travel_data.ajaxurl;

    var form = wrapper.querySelector('form#query-stock-form');

    function getResultsContainer() {
        try {
            var targetSelector = wrapper.getAttribute('data-results-target');
            if (targetSelector) {
                var external = document.querySelector(targetSelector);
                if (external) return external;
            }
        } catch (e) {
            // ignore
        }
        return wrapper.querySelector('#query-stock-results');
    }

    function handleOpenModalClick(openButton) {
        var url = openButton.getAttribute('data-url');

        // Check if this is a resort info button (should show resort info modal)
        if (openButton.classList.contains('resort-info-link')) {
            console.log('Opening resort info modal for:', url);
            openIframeModal(url, 'Resort Information');
            return;
        }

        // Check if this is a unit info button (should show unit info modal)
        if (openButton.classList.contains('unit-info-link')) {
            console.log('Opening unit info modal for:', url);
            openIframeModal(url, 'Unit Information');
            return;
        }

        // Handle unit info button (booking or live iframe depending on setting)
        var resortName = openButton.getAttribute('data-resort-name') || '';
        var unitName = openButton.getAttribute('data-unit-name') || '';
        var unitPrice = openButton.getAttribute('data-price') || '';
        var checkinDate = openButton.getAttribute('data-checkin') || '';
        var checkoutDate = openButton.getAttribute('data-checkout') || '';

        if (url) {
            try {
                var bookingMode = (avante_travel_data && avante_travel_data.booking_mode) ? String(avante_travel_data.booking_mode) : 'email';
            } catch (e) {
                var bookingMode = 'email';
            }

            if (bookingMode === 'live') {
                openIframeModal(url, 'Complete Your Booking');
                return;
            }

            // Otherwise, show the email booking modal (collect details and email)
            var modal = wrapper.querySelector('#bookNowModal');
            if (modal) {
                // Populate hidden fields with booking information
                var bookingUrlInput = modal.querySelector('#booking_url');
                var resortNameInput = modal.querySelector('#resort_name');
                var unitNameInput = modal.querySelector('#unit_name');
                var unitPriceInput = modal.querySelector('#unit_price');
                var checkinDateInput = modal.querySelector('#checkin_date');
                var checkoutDateInput = modal.querySelector('#checkout_date');

                if (bookingUrlInput) bookingUrlInput.value = url;
                if (resortNameInput) resortNameInput.value = resortName;
                if (unitNameInput) unitNameInput.value = unitName;
                if (unitPriceInput) unitPriceInput.value = unitPrice;
                if (checkinDateInput) checkinDateInput.value = checkinDate;
                if (checkoutDateInput) checkoutDateInput.value = checkoutDate;

                // Populate and show the overview section
                var overviewResort = modal.querySelector('#overview-resort');
                var overviewUnit = modal.querySelector('#overview-unit');
                var overviewPrice = modal.querySelector('#overview-price');
                var overviewCheckin = modal.querySelector('#overview-checkin');
                var overviewCheckout = modal.querySelector('#overview-checkout');
                var overviewSection = modal.querySelector('#booking-overview');

                if (overviewResort) overviewResort.textContent = resortName || 'Not specified';
                if (overviewUnit) overviewUnit.textContent = unitName || 'Not specified';
                if (overviewPrice) overviewPrice.textContent = unitPrice ? 'R ' + unitPrice : 'Not specified';
                if (overviewCheckin) overviewCheckin.textContent = checkinDate || 'Not specified';
                if (overviewCheckout) overviewCheckout.textContent = checkoutDate || 'Not specified';
                if (overviewSection) overviewSection.style.display = 'block';

                // Display the booking URL
                var bookingUrlDisplay = modal.querySelector('#booking-url-display');
                if (bookingUrlDisplay) {
                    bookingUrlDisplay.textContent = url || 'No URL available';
                }

                // Clear form fields
                var form = modal.querySelector('#booking-form');
                if (form) {
                    form.reset();
                    // Repopulate hidden fields after reset
                    if (bookingUrlInput) bookingUrlInput.value = url;
                    if (resortNameInput) resortNameInput.value = resortName;
                    if (unitNameInput) unitNameInput.value = unitName;
                    if (unitPriceInput) unitPriceInput.value = unitPrice;
                    if (checkinDateInput) checkinDateInput.value = checkinDate;
                    if (checkoutDateInput) checkoutDateInput.value = checkoutDate;
                    
                    // Repopulate booking URL display after reset
                    if (bookingUrlDisplay) {
                        bookingUrlDisplay.textContent = url || 'No URL available';
                    }
                }

                // Hide any previous messages
                var messageDiv = modal.querySelector('#booking-form-message');
                if (messageDiv) {
                    messageDiv.style.display = 'none';
                }

                // Close datepicker if it's open (prevents it from appearing on top of modal)
                // Use multiple methods to ensure it closes
                if (typeof jQuery !== 'undefined') {
                    jQuery('#daterange').datepicker('hide');
                    jQuery('.ui-datepicker').hide();
                    jQuery('.ui-datepicker').removeClass('ui-datepicker');
                }

                modal.style.display = 'flex';
                console.log('Modal display style set to flex.');
            } else {
                console.error('Modal element not found!');
            }
        } else {
            console.error('No data-url found on the button.');
        }
    }
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            // Hide the more-options-container when search is submitted
            var moreOptionsContainer = wrapper.querySelector('#more-options-container');
            if (moreOptionsContainer) {
                moreOptionsContainer.style.display = 'none';
                // Also update the toggle button text
                var moreOptionsToggle = wrapper.querySelector('#more-options-toggle');
                if (moreOptionsToggle) {
                    moreOptionsToggle.textContent = 'More Filters';
                }
            }

            // Build a FormData instance early so we can read values for the parameters display
            var formData = new FormData(form);

            var paramsDiv = wrapper.querySelector('#search-parameters');
            if (paramsDiv) {
                try {
                    var searchOptions = (window.avante_travel_search_options || {});
                } catch (e) {
                    var searchOptions = {};
                }

                var unitSizeId = formData.get('unit_size');
                var unitSizeName = '';
                if (searchOptions.unitSizes && Array.isArray(searchOptions.unitSizes)) {
                    var found = searchOptions.unitSizes.find(function(u){ return String(u.unitSizeId) === String(unitSizeId); });
                    if (found && found.name) unitSizeName = found.name;
                }

                function collectNames(key) {
                    var names = [];
                    var selected = Array.from(form.querySelectorAll('input[name="' + key + '[]"]:checked')).map(function(cb){ return String(cb.value); });
                    var pool = (searchOptions[key] || []);
                    var map = {};
                    pool.forEach(function(item){ if (item && item.amenityTypeId) { map[String(item.amenityTypeId)] = item.name || ''; } });
                    selected.forEach(function(id){ if (map[id]) { names.push(map[id]); } });
                    return names;
                }

                var amenitiesNames = collectNames('amenities');
                var experiencesNames = collectNames('experiences');
                var activitiesNames = collectNames('activities');

                var parts = [];
                var destinationVal = formData.get('destination') || '';
                var ci = formData.get('checkin_date') || '';
                var co = formData.get('checkout_date') || '';
                parts.push('Search parameters: ' + destinationVal + ', ' + ci + ' to ' + co);
                if (unitSizeName) parts.push('Unit size: ' + unitSizeName);
                if (amenitiesNames.length) parts.push('Amenities: ' + amenitiesNames.join(', '));
                if (experiencesNames.length) parts.push('Experiences: ' + experiencesNames.join(', '));
                if (activitiesNames.length) parts.push('Activities: ' + activitiesNames.join(', '));
                paramsDiv.textContent = parts.join(' | ');
            }

            var resultsNode = getResultsContainer();
            if (resultsNode) {
                resultsNode.innerHTML = '<div class="loading">Searching for available properties...</div>';
            }
            formData.append('action', 'handle_query_stock');

            // Build a JSON payload mirroring what PHP constructs so we can inspect it in the browser
            // Collect all checked checkboxes from amenities, experiences, and activities
            var allAmenities = [];
            

            
            // Get all checked checkboxes for amenities
            var amenityCheckboxes = form.querySelectorAll('input[name="amenities[]"]:checked');
            amenityCheckboxes.forEach(function(checkbox) {
                allAmenities.push({ amenityTypeId: checkbox.value });
            });
            
            // Get all checked checkboxes for experiences
            var experienceCheckboxes = form.querySelectorAll('input[name="experiences[]"]:checked');
            experienceCheckboxes.forEach(function(checkbox) {
                allAmenities.push({ amenityTypeId: checkbox.value });
            });
            
            // Get all checked checkboxes for activities
            var activityCheckboxes = form.querySelectorAll('input[name="activities[]"]:checked');
            activityCheckboxes.forEach(function(checkbox) {
                allAmenities.push({ amenityTypeId: checkbox.value });
            });

            var payloadForApi = {
                CheckInDate: formData.get('checkin_date') + 'T00:00:00+02:00',
                CheckOutDate: formData.get('checkout_date') + 'T00:00:00+02:00',
                Region: {
                    RegionID: null,
                    Name: '',
                    Country: 'South Africa',
                    CountryID: null,
                    IsRCIRegion: false,
                    RegionCode: null
                },
                Amenities: allAmenities,
                unitSizes: [
                    {
                        unitSizeId: formData.get('unit_size')
                    }
                ],
                Geocoordinates: null,
                Pricing: {
                    MinPrice: parseFloat(avante_travel_data.min_price) || 0.0,
                    MaxPrice: 50000.0
                },
                searchText: formData.get('destination'),
                ResortID: null,
                GroupStockToMatchDates: true,
                ExtendDatesIfNoMatchFound: true,
                IgnoreLocationData: false
            };

            // Console log the API payload for debugging
            // console.log('=== API PAYLOAD BEING SENT ===');
            // console.log('Form Data:', Object.fromEntries(formData));
            // console.log('Total Checked Amenities:', allAmenities.length);
            // console.log('All Selected Amenities/Experiences/Activities:', allAmenities);
            // console.log('API Payload:', payloadForApi);
            // console.log('================================');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.timeout = 600000;
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            var resultsNode = getResultsContainer();
                            if (resultsNode) {
                                resultsNode.innerHTML = xhr.responseText;
                            }
                            // Datepicker is handled by custom-datepicker.js
                        } catch (e) {
                            console.error('Error updating results:', e);
                            var resultsNode = getResultsContainer();
                            if (resultsNode) {
                                resultsNode.innerHTML = '<div class="error">Error displaying results. Please try again.</div>';
                            }
                        }
                    } else {
                        console.error('AJAX request failed with status: ' + xhr.status);
                        var resultsNode = getResultsContainer();
                        if (resultsNode) {
                            resultsNode.innerHTML = '<div class="error">Search failed. Please try again.</div>';
                        }
                    }
                }
            };
            xhr.ontimeout = function() {
                console.error('AJAX request timed out');
                var resultsNode = getResultsContainer();
                if (resultsNode) {
                    resultsNode.innerHTML = '<div class="error">Request timed out. Please try again.</div>';
                }
            };
            xhr.onerror = function() {
                console.error('AJAX request encountered a network error');
            };
            var encodedData = new URLSearchParams(formData).toString();
            xhr.send(encodedData);
        });
    }

    wrapper.addEventListener('click', function(event) {

        var showMoreButton = event.target.closest('.show-more-button');
        if (showMoreButton) {
            event.preventDefault();
            var targetId = showMoreButton.getAttribute('data-target');
            var targetElement = document.getElementById(targetId);
            if (targetElement) {
                if (targetElement.style.maxHeight) {
                    targetElement.style.maxHeight = null;
                    showMoreButton.textContent = 'More options';
                } else {
                    targetElement.style.maxHeight = targetElement.scrollHeight + "px";
                    showMoreButton.textContent = 'Less options';
                }
            }
            return;
        }

        var openButton = event.target.closest('.open-modal-button');
        if (openButton) {
            event.preventDefault();
            handleOpenModalClick(openButton);
            return;
        }

        // Handle modal close (X button, cancel button, or clicking outside modal)
        if (event.target.classList.contains('modal-close') || event.target.classList.contains('btn-cancel')) {
            var bookModal = wrapper.querySelector('#bookNowModal');
            var iframeModal = wrapper.querySelector('#iframeModal');
            
            if (bookModal && bookModal.style.display === 'flex') {
                bookModal.style.display = 'none';
            }
            if (iframeModal && iframeModal.style.display === 'flex') {
                iframeModal.style.display = 'none';
                // Clear iframe source when closing
                var iframe = iframeModal.querySelector('#iframeContent');
                if (iframe) {
                    iframe.src = '';
                }
            }
            return;
        }
        
        // Close modal when clicking outside modal content
        var bookModal = wrapper.querySelector('#bookNowModal');
        var iframeModal = wrapper.querySelector('#iframeModal');
        
        if (bookModal && bookModal.style.display === 'flex' && event.target === bookModal) {
            bookModal.style.display = 'none';
        }
        
        if (iframeModal && iframeModal.style.display === 'flex' && event.target === iframeModal) {
            iframeModal.style.display = 'none';
            // Clear iframe source when closing
            var iframe = iframeModal.querySelector('#iframeContent');
            if (iframe) {
                iframe.src = '';
            }
        }
    });

    // Also handle Book Now clicks if results are rendered outside the wrapper
    document.addEventListener('click', function(event) {
        var openButton = event.target.closest('.open-modal-button');
        if (!openButton) return;
        // If button is inside wrapper, let the wrapper handler process it
        if (wrapper && wrapper.contains(openButton)) return;
        event.preventDefault();
        handleOpenModalClick(openButton);
    });

    // Listen for height messages from iframe content to dynamically resize the modal iframe
    window.addEventListener('message', function(event) {
        try {
            var data = event.data || {};
            if (typeof data === 'string') {
                try { data = JSON.parse(data); } catch (e) { /* ignore */ }
            }
            if (data && data.type === 'setHeight' && typeof data.height === 'number') {
                var iframe = wrapper.querySelector('#iframeContent');
                if (iframe) {
                    var titleElement = wrapper.querySelector('#iframeModalTitle');
                    var titleHeight = titleElement ? titleElement.offsetHeight : 0;
                    // Keep iframe within viewport (90% of viewport height minus header and padding)
                    var maxIframeHeight = Math.max(0, Math.floor(window.innerHeight * 0.9) - titleHeight - 30);
                    var targetHeight = data.height;
                    if (maxIframeHeight > 0) {
                        targetHeight = Math.min(targetHeight, maxIframeHeight);
                    }
                    iframe.style.height = String(targetHeight) + 'px';
                }
            }
        } catch (err) {
            // no-op
        }
    });

    // Toggle for More Options
    var moreOptionsToggle = wrapper.querySelector('#more-options-toggle');
    var moreOptionsContainer = wrapper.querySelector('#more-options-container');

    if (moreOptionsToggle && moreOptionsContainer) {
        moreOptionsToggle.addEventListener('click', function(event) {
            event.preventDefault();
            if (moreOptionsContainer.style.display === 'none' || moreOptionsContainer.style.display === '') {
                moreOptionsContainer.style.display = 'block';
                moreOptionsToggle.textContent = 'Less Filters';
            } else {
                moreOptionsContainer.style.display = 'none';
                moreOptionsToggle.textContent = 'More Filters';
            }
        });
    }

    // Handle booking form submission
    var bookingForm = wrapper.querySelector('#booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            var submitButton = bookingForm.querySelector('.btn-submit');
            var messageDiv = wrapper.querySelector('#booking-form-message');
            
            // Show loading state
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Sending...';
            }
            
            var formData = new FormData(bookingForm);
            formData.append('action', 'handle_booking_request');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.timeout = 30000;
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    // Reset button state
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = 'Send Booking Request';
                    }
                    
                    if (xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (messageDiv) {
                                messageDiv.style.display = 'block';
                                if (response.success) {
                                    messageDiv.className = 'form-message success';
                                    messageDiv.innerHTML = '<p><strong>Thank you for your booking!</strong> Please look out for an email with banking details to confirm your booking within 24 hours after receiving your email.</p>';
                                    // Clear form fields
                                    bookingForm.reset();
                                    // Close modal after 3 seconds
                                    setTimeout(function() {
                                        var modal = wrapper.querySelector('#bookNowModal');
                                        if (modal) {
                                            modal.style.display = 'none';
                                        }
                                    }, 3000);
                                } else {
                                    messageDiv.className = 'form-message error';
                                    messageDiv.innerHTML = '<p><strong>Error:</strong> ' + (response.data || 'Failed to send booking request. Please try again.') + '</p>';
                                }
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            if (messageDiv) {
                                messageDiv.style.display = 'block';
                                messageDiv.className = 'form-message error';
                                messageDiv.innerHTML = '<p><strong>Error:</strong> Failed to send booking request. Please try again.</p>';
                            }
                        }
                    } else {
                        if (messageDiv) {
                            messageDiv.style.display = 'block';
                            messageDiv.className = 'form-message error';
                            messageDiv.innerHTML = '<p><strong>Error:</strong> Failed to send booking request. Please try again.</p>';
                        }
                    }
                }
            };
            
            xhr.ontimeout = function() {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Send Booking Request';
                }
                if (messageDiv) {
                    messageDiv.style.display = 'block';
                    messageDiv.className = 'form-message error';
                    messageDiv.innerHTML = '<p><strong>Error:</strong> Request timed out. Please try again.</p>';
                }
            };
            
            var encodedData = new URLSearchParams(formData).toString();
            xhr.send(encodedData);
        });
    }
    
    // Function to open iframe modal
    function openIframeModal(url, title) {
        var modal = wrapper.querySelector('#iframeModal');
        var iframe = wrapper.querySelector('#iframeContent');
        var titleElement = wrapper.querySelector('#iframeModalTitle');
        
        if (modal && iframe && titleElement) {
            // Close datepicker if it's open (prevents it from appearing on top of modal)
            // Use multiple methods to ensure it closes
            if (typeof jQuery !== 'undefined') {
                jQuery('#daterange').datepicker('hide');
                jQuery('.ui-datepicker').hide();
                jQuery('.ui-datepicker').removeClass('ui-datepicker');
            }
            
            // Set the title
            titleElement.textContent = title;
            
            // Set the iframe source
            iframe.src = url;
            
            // Fallback height so content is visible immediately
            iframe.style.height = Math.floor(window.innerHeight * 0.8) + 'px';

            // Request the height from the child document once it loads
            var onLoad = function() {
                try {
                    iframe.contentWindow && iframe.contentWindow.postMessage({ type: 'getHeight' }, '*');
                } catch (e) {
                    // ignore
                }
            };
            iframe.addEventListener('load', onLoad, { once: true });
            
            // Show the modal
            modal.style.display = 'flex';
        } else {
            console.error('Iframe modal elements not found!');
        }
    }
}); 