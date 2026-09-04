jQuery(document).ready(function($) {
    var $destination = $("#destination");
    var $destinationContainer = $destination.closest(".input-container.destination");
    var apiUrl = (window.AVANTE && window.AVANTE.apiUrl) ? window.AVANTE.apiUrl : "api/index.php";

    if ($destinationContainer.length && !$destinationContainer.find(".destination-spinner").length) {
        $destinationContainer.append('<span class="destination-spinner" aria-hidden="true"></span>');
    }
    var $spinner = $destinationContainer.find(".destination-spinner");

    function showDestinationSpinner() {
        $destinationContainer.addClass("is-loading");
        $spinner.show();
    }

    function hideDestinationSpinner() {
        $destinationContainer.removeClass("is-loading");
        $spinner.hide();
    }

    hideDestinationSpinner();

    $destination.autocomplete({
        source: function(request, response) {
            showDestinationSpinner();
            $.ajax({
                url: apiUrl,
                dataType: "json",
                data: {
                    action: "locations",
                    term: request.term
                },
                success: function(data) {
                    response(Array.isArray(data) ? data : []);
                },
                error: function() {
                    response([]);
                },
                complete: function() {
                    hideDestinationSpinner();
                }
            });
        },
        minLength: 2,
        search: function() {
            showDestinationSpinner();
        },
        response: function() {
            hideDestinationSpinner();
        },
        select: function(event, ui) {
            $destination.val(ui.item.value);
            return false;
        }
    }).autocomplete("instance")._renderItem = function(ul, item) {
        var iconHtml = "";
        if (item.type === "accommodation") {
            iconHtml = '<svg class="autocomplete-icon accommodation-icon" viewBox="0 0 640 512" width="16" height="16"><path fill="currentColor" d="M176 256c-44.2 0-80-35.8-80-80s35.8-80 80-80s80 35.8 80 80s-35.8 80-80 80zm352-128H304c-8.8 0-16 7.2-16 16v144H64V128c0-8.8-7.2-16-16-16H16C7.2 112 0 119.2 0 128v256c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V208h224v176c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V144c0-8.8-7.2-16-16-16z"/></svg>';
        } else {
            iconHtml = '<svg class="autocomplete-icon location-icon" viewBox="0 0 384 512" width="16" height="16"><path fill="currentColor" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0zM192 272c44.183 0 80-35.817 80-80s-35.817-80-80-80-80 35.817-80 80 35.817 80 80 80z"/></svg>';
        }

        var html = '<div class="autocomplete-item">' +
            '<div class="autocomplete-icon-container">' + iconHtml + '</div>' +
            '<div class="autocomplete-content">' +
                '<div class="autocomplete-label">' + $("<div>").text(item.label || "").html() + '</div>';

        if (item.location) {
            html += '<div class="autocomplete-location">' + $("<div>").text(item.location).html() + '</div>';
        }

        html += '</div></div>';

        return $("<li>")
            .append(html)
            .appendTo(ul);
    };

    $("#clear-destination").on("click", function() {
        $destination.val("").focus();
        hideDestinationSpinner();
        $(this).hide();
    });

    $destination.on("input", function() {
        if ($(this).val().length > 0) {
            $("#clear-destination").show();
        } else {
            $("#clear-destination").hide();
            hideDestinationSpinner();
        }
    });

    if ($destination.val().length === 0) {
        $("#clear-destination").hide();
    }
});
