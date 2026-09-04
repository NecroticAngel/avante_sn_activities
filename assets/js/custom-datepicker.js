jQuery(document).ready(function($) {
    var dateFormat = "yy-mm-dd";
    var startDate = null;
    var endDate = null;
    
    // Helper function to remove inline width from datepicker
    function removeDatepickerWidth() {
        setTimeout(function() {
            $(".ui-datepicker").css('width', '');
        }, 10);
    }
    
    $("#daterange").datepicker({
        numberOfMonths: 2,
        dateFormat: dateFormat,
        minDate: 0,
        changeMonth: false,
        changeYear: false,
        beforeShow: function(input, inst) {
            // Prevent datepicker from showing if a modal is open
            if ($('.modal-overlay').is(':visible') || $('#bookNowModal').is(':visible') || $('#iframeModal').is(':visible')) {
                return false;
            }
            setTimeout(function() {
                inst.dpDiv.find('.ui-datepicker-next').removeClass('ui-state-disabled');
                inst.dpDiv.find('.ui-datepicker-prev').removeClass('ui-state-disabled');
                inst.dpDiv.addClass('daterange-picker');
                // Remove inline width to allow CSS width to take effect
                inst.dpDiv.css('width', '');
            }, 1);
        },
        beforeShowDay: function(date) {
            var highlight = false;
            var cssClass = '';
            
            if (startDate && endDate) {
                if (date >= startDate && date <= endDate) {
                    highlight = true;
                    cssClass = 'date-range-selected';
                }
            }
            
            if (startDate && date.getTime() === startDate.getTime()) {
                cssClass += ' date-range-start';
            }
            if (endDate && date.getTime() === endDate.getTime()) {
                cssClass += ' date-range-end';
            }
            
            return [true, cssClass];
        },
        onSelect: function(dateText) {
            var selectedDate = $.datepicker.parseDate(dateFormat, dateText);
            
            if (!startDate || (startDate && endDate)) {
                // First selection or reset
                startDate = selectedDate;
                endDate = null;
                $("#checkin_date").val(dateText);
                $("#checkout_date").val('');
                $("#daterange").val(dateText);
                $(this).datepicker('refresh');
                removeDatepickerWidth();
            } else if (selectedDate < startDate) {
                // Selected date is before start, make it the new start
                endDate = startDate;
                startDate = selectedDate;
                $("#checkin_date").val(dateText);
                $("#checkout_date").val($.datepicker.formatDate(dateFormat, endDate));
                $("#daterange").val(dateText + ' - ' + $.datepicker.formatDate(dateFormat, endDate));
                $(this).datepicker('refresh');
                removeDatepickerWidth();
            } else {
                // Second selection (end date)
                endDate = selectedDate;
                $("#checkout_date").val(dateText);
                $("#daterange").val($.datepicker.formatDate(dateFormat, startDate) + ' - ' + dateText);
                $(this).datepicker('refresh');
                removeDatepickerWidth();
                
                // Close the calendar after selecting both dates
                setTimeout(function() {
                    // Try multiple methods to close the calendar
                    $("#daterange").datepicker('hide');
                    $(".ui-datepicker").hide();
                    $(".ui-datepicker").remove();
                }, 150);
            }
        }
    });
    
    // Clear button functionality
    $('#clear-destination').on('click', function() {
        $('#destination').val('');
        $('#destination').focus();
    });
    
    // Close datepicker when clicking outside (but not on form elements)
    $(document).on('click', function(e) {
        var $target = $(e.target);
        var isDatepickerElement = $target.closest('#daterange, .ui-datepicker, .ui-datepicker-header, .ui-datepicker-calendar').length > 0;
        var isFormElement = $target.closest('form, .horizontal-form-container, .input-container, input, select, button').length > 0;
        var isAutocompleteElement = $target.closest('.ui-autocomplete, .autocomplete-item, .ui-menu-item').length > 0;
        var isModalElement = $target.closest('.modal-overlay, .modal-content, .booking-form-modal, .iframe-modal, #bookNowModal, #iframeModal').length > 0;
        var isModalButton = $target.closest('.open-modal-button, .stock_external').length > 0;
        
        // Close datepicker if clicking on modal button or if modal is open
        if (isModalButton || $('.modal-overlay').is(':visible') || $('#bookNowModal').is(':visible') || $('#iframeModal').is(':visible')) {
            $('#daterange').datepicker('hide');
            return;
        }
        
        // Only close if clicking completely outside the form area, autocomplete, and modals
        if (!isDatepickerElement && !isFormElement && !isAutocompleteElement && !isModalElement) {
            $('#daterange').datepicker('hide');
        }
    });
});
