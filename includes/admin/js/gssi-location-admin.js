jQuery(document).ready(function ($) {
    
    $('#gssi-api-settings input[type=checkbox]').click(function() {
        if($(this).is(':checked')) {
            $(this).closest('p').find('input[type=hidden]').val(1);
        } else {
            $(this).closest('p').find('input[type=hidden]').val(0);
        }
        return true;
    });
    
    $('.upload_marker_button').click(function () {
        var send_attachment_bkp = wp.media.editor.send.attachment;
        var button = $(this);
        wp.media.editor.send.attachment = function (props, attachment) {
            $(button).parent().find('img').attr('src', attachment.url);
            $(button).prev().val(attachment.id);
            wp.media.editor.send.attachment = send_attachment_bkp;
        };
        wp.media.editor.open(button);
        return false;
    });
    
    
    var map, geocoder, markersArray = [];

    if ($("#gssi-gmap-wrap").length) {
        initializeGmap();
    }

    /*
     * If we're on the settings page, then check for returned
     * browser key errors from the autocomplete field
     * and validate the server key when necessary.
     */
    if ($("#gssi-map-settings").length) {
        /**
         * Check if we need to validate the server key, and if no
         * error message is already visible after saving the setting page.
         */
        if ($("#gssi-api-server-key").hasClass("gssi-validate-me") && !$("#setting-error-server-key").length) {
            validateServerKey();
        }
    }

    /**
     * Initialize the map with the correct settings.
     *
     * @returns {void}
     */
    function initializeGmap() {
        var defaultLatLng = gssiSettings.defaultLatLng.split(","),
                latLng = new google.maps.LatLng(defaultLatLng[0], defaultLatLng[1]),
                mapOptions = {};

        mapOptions = {
            zoom: parseInt(gssiSettings.defaultZoom),
            center: latLng,
            mapTypeId: google.maps.MapTypeId[ gssiSettings.mapType.toUpperCase() ],
            mapTypeControl: false,
            streetViewControl: false,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_TOP
            }
        };

        geocoder = new google.maps.Geocoder();
        map = new google.maps.Map(document.getElementById("gssi-gmap-wrap"), mapOptions);

        checkEditStoreMarker();
    }

    /**
     * Check if we have an existing latlng value.
     * 
     * If there is an latlng value, then we add a marker to the map.
     * This can only happen on the edit location page.
     *
     * @returns {void}
     */
    function checkEditStoreMarker() {
        var location,
                lat = $("#gssi-lat").val(),
                lng = $("#gssi-lng").val();

        if ((lat) && (lng)) {
            location = new google.maps.LatLng(lat, lng);

            map.setCenter(location);
            map.setZoom(16);
            addMarker(location);
        }
    }

// If we have a city/country input field enable the autocomplete.
    if ($("#gssi-start-name").length) {
        activateAutoComplete();
    }

    /**
     * Activate the autocomplete function for the city/country field.
     *
     * @returns {void}
     */
    function activateAutoComplete() {
        var latlng,
                input = document.getElementById("gssi-start-name"),
                options = {
                    types: ['geocode']
                },
                autocomplete = new google.maps.places.Autocomplete(input, options);

        google.maps.event.addListener(autocomplete, "place_changed", function () {
            latlng = autocomplete.getPlace().geometry.location;
            setLatlng(latlng, "zoom");
        });
    }

    /**
     * Add a new marker to the map based on the provided location (latlng).
     *
     * @param   {object} location The latlng value
     * @returns {void}
     */
    function addMarker(location) {
        var marker = new google.maps.Marker({
            position: location,
            map: map,
            draggable: true
        });

        markersArray.push(marker);

        // If the marker is dragged on the map, make sure the latlng values are updated.
        google.maps.event.addListener(marker, "dragend", function () {
            setLatlng(marker.getPosition(), "location");
        });
    }

// Lookup the provided location with the Google Maps API.
    $("#gssi-lookup-location").on("click", function (e) {
        e.preventDefault();
        codeAddress();
    });

    /**
     * Update the hidden input field with the current latlng values.
     *
     * @param   {object} latLng The latLng values
     * @param   {string} target The location where we need to set the latLng
     * @returns {void}
     */
    function setLatlng(latLng, target) {
        var coordinates = stripCoordinates(latLng),
                lat = roundCoordinate(coordinates[0]),
                lng = roundCoordinate(coordinates[1]);

        if (target == "location") {
            $("#gssi-lat").val(lat);
            $("#gssi-lng").val(lng);
        } else if (target == "zoom") {
            $("#gssi-latlng").val(lat + ',' + lng);
        }
    }

    /**
     * Geocode the user input. 
     *
     * @returns {void}
     */
    function codeAddress() {
        var filteredResponse, geocodeAddress;

        // Check if we have all the required data before attempting to geocode the address.
        if (!validatePreviewFields()) {
            geocodeAddress = createGeocodeAddress();

            geocoder.geocode({'address': geocodeAddress}, function (response, status) {
                if (status === google.maps.GeocoderStatus.OK) {

                    // If we have a previous marker on the map we remove it.
                    if (typeof (markersArray[0]) !== "undefined") {
                        if (markersArray[0].draggable) {
                            markersArray[0].setMap(null);
                            markersArray.splice(0, 1);
                        }
                    }

                    // Center and zoom to the searched location.
                    map.setCenter(response[0].geometry.location);
                    map.setZoom(16);

                    addMarker(response[0].geometry.location);
                    setLatlng(response[0].geometry.location, "location");

                    filteredResponse = filterApiResponse(response);

                    $("#gssi-country").val(filteredResponse.country.long_name);
                    $("#gssi-country_iso").val(filteredResponse.country.short_name);
                } else {
                    alert(gssiSettings.geocodeFail + ": " + status);
                }
            });

            return false;
        } else {

            alert(gssiSettings.missingGeoData);

            return true;
        }
    }

    /**
     * Check that all required fields for the map preview are there. 
     *
     * @returns {boolean} error  Whether all the required fields contained data.
     */
    function validatePreviewFields() {
        var i, fieldData, requiredFields,
                error = false;

        $(".gssi-location-meta input").removeClass("gssi-error");

        // Check which fields are required.
        if (typeof gssiSettings.requiredFields !== "undefined" && _.isArray(gssiSettings.requiredFields)) {
            requiredFields = gssiSettings.requiredFields;

            // Check if all the required fields contain data.
            for (i = 0; i < requiredFields.length; i++) {
                fieldData = $.trim($("#gssi-" + requiredFields[i]).val());

                if (!fieldData) {
                    $("#gssi-" + requiredFields[i]).addClass("gssi-error");
                    error = true;
                }

                fieldData = '';
            }
        }

        return error;
    }

    /**
     * Build the address that's send to the Geocode API.
     *
     * @returns {string} geocodeAddress The address separated by , that's send to the Geocoder.
     */
    function createGeocodeAddress() {
        var i, part,
                address = [],
                addressParts = ["address", "city", "state", "zip", "country"];

        for (i = 0; i < addressParts.length; i++) {
            part = $.trim($("#gssi-" + addressParts[i]).val());

            /*
             * At this point we already know the address, city and country fields contain data.
             * But no need to include the zip and state if they are empty.
             */
            if (part) {
                address.push(part);
            }

            part = "";
        }

        return address.join();
    }

    /**
     * Filter out the country name from the API response.
     *
     * @param   {object} response	   The response of the geocode API
     * @returns {object} collectedData The short and long country name
     */
    function filterApiResponse(response) {
        var i, responseType,
                country = {},
                collectedData = {},
                addressLength = response[0].address_components.length;

        // Loop over the API response.
        for (i = 0; i < addressLength; i++) {
            responseType = response[0].address_components[i].types;

            // Filter out the country name.
            if (/^country,political$/.test(responseType)) {
                country = {
                    long_name: response[0].address_components[i].long_name,
                    short_name: response[0].address_components[i].short_name
                };
            }
        }

        collectedData = {
            country: country
        };

        return collectedData;
    }

    /**
     * Round the coordinate to 6 digits after the comma. 
     *
     * @param   {string} coordinate   The coordinate
     * @returns {number} roundedCoord The rounded coordinate
     */
    function roundCoordinate(coordinate) {
        var roundedCoord, decimals = 6;

        roundedCoord = Math.round(coordinate * Math.pow(10, decimals)) / Math.pow(10, decimals);

        return roundedCoord;
    }

    /**
     * Strip the '(' and ')' from the captured coordinates and split them. 
     *
     * @param   {string} coordinates The coordinates
     * @returns {object} latLng      The latlng coordinates
     */
    function stripCoordinates(coordinates) {
        var latLng = [],
                selected = coordinates.toString(),
                latLngStr = selected.split(",", 2);

        latLng[0] = latLngStr[0].replace("(", "");
        latLng[1] = latLngStr[1].replace(")", "");

        return latLng;
    }

    $(".gssi-marker-list input[type=radio]").click(function () {
        $(this).parents(".gssi-marker-list").find("li").removeClass();
        $(this).parent("li").addClass("gssi-active-marker");
    });

    $(".gssi-marker-list li").click(function () {
        $(this).parents(".gssi-marker-list").find("input").prop("checked", false);
        $(this).find("input").prop("checked", true);
        $(this).siblings().removeClass();
        $(this).addClass("gssi-active-marker");
    });

// Handle a click on the dismiss button. So that the warning msg that no starting point is set is disabled.
    $(".gssi-dismiss").click(function () {
        var $link = $(this),
                data = {
                    action: "disable_location_warning",
                    _ajax_nonce: $link.attr("data-nonce")
                };

        $.post(ajaxurl, data);

        $(".gssi-dismiss").parents(".error").remove();

        return false;
    });

// Detect changes in checkboxes that have a conditional option.
    $(".gssi-has-conditional-option").on("change", function () {
        $(this).parent().next(".gssi-conditional-option").toggle();
    });

    /* 
     * Detect changes to the location template dropdown. If the template is selected to 
     * show the location list under the map then we show the option to hide the scrollbar.
     */
    $("#gssi-location-template").on("change", function () {
        var $scrollOption = $("#gssi-listing-below-no-scroll");

        if ($(this).val() == "below_map") {
            $scrollOption.show();
        } else {
            $scrollOption.hide();
        }
    });

    $("#gssi-api-region").on("change", function () {
        var $geocodeComponent = $("#gssi-geocode-component");

        if ($(this).val()) {
            $geocodeComponent.show();
        } else {
            $geocodeComponent.hide();
        }
    });

// Make sure the correct hour input format is visible.
    $("#gssi-editor-hour-input").on("change", function () {
        $(".gssi-" + $(this).val() + "-hours").show().siblings("div").hide();
        $(".gssi-hour-notice").toggle();
    });

// Make sure the required location fields contain data.
    if ($("#gssi_location_location").length) {
        $("#publish").click(function () {
            var firstErrorElem, currentTabClass, elemClass,
                    errorMsg = '<div class="error"><p>' + gssiSettings.requiredFields + '</p></div>',
                    missingData = false;

            // Remove error messages and css classes from previous submissions.
            $("#wpbody-content .wrap #message").remove();
            $(".gssi-required").removeClass("gssi-error");

            // Loop over the required fields and check for a value.
            $(".gssi-required").each(function () {
                if ($(this).val() == "") {
                    $(this).addClass("gssi-error");

                    $(this).closest('.postbox').find('h2').after(errorMsg);

                    if (typeof firstErrorElem === "undefined") {
                        firstErrorElem = getFirstErrorElemAttr($(this));
                    }

                    missingData = true;
                }
            });

            // If one of the required fields are empty, then show the error msg and make sure the correct tab is visible.
            if (missingData) {

                if (typeof firstErrorElem.val !== "undefined") {
                    if (firstErrorElem.type == "id") {
                        $("html, body").scrollTop(Math.round($("#" + firstErrorElem.val + "").offset().top - 100));
                    } else if (firstErrorElem.type == "class") {
                        elemClass = firstErrorElem.val.replace(/gssi-required|gssi-error/g, "");
                        $("html, body").scrollTop(Math.round($("." + elemClass + "").offset().top - 100));
                    }
                }
                $("#publish").removeClass("button-primary-disabled");
                $(".spinner").hide();

                return false;
            } else {
                return true;
            }
        });
    }

    /**
     * Get the id or class of the first element that's an required field, but is empty.
     * 
     * We need this to determine which tab we need to set active,
     * which will be the tab were the first error occured. 
     * 
     * @param   {object} elem			The element the error occured on
     * @returns {object} firstErrorElem The id/class set on the first elem that an error occured on and the attr value
     */
    function getFirstErrorElemAttr(elem) {
        var firstErrorElem = {"type": "id", "val": elem.attr("id")};

        // If no ID value exists, then check if we can get the class name.
        if (typeof firstErrorElem.val === "undefined") {
            firstErrorElem = {"type": "class", "val": elem.attr("class")};
        }

        return firstErrorElem;
    }

// If we have a location hours dropdown, init the event handler.
    if ($("#gssi-location-hours").length) {
        initHourEvents();
    }

    /**
     * Assign an event handler to the button that enables 
     * users to remove an opening hour period.
     * 
     * @returns {void}
     */
    function initHourEvents() {
        $("#gssi-location-hours .gssi-icon-cancel-circled").off();
        $("#gssi-location-hours .gssi-icon-cancel-circled").on("click", function () {
            removePeriod($(this));
        });
    }

// Add new openings period to the openings hours table.
    $(".gssi-add-period").on("click", function (e) {
        var newPeriod,
                hours = {},
                returnList = true,
                $tr = $(this).parents("tr"),
                periodCount = currentPeriodCount($(this)),
                periodCss = (periodCount >= 1) ? "gssi-current-period gssi-multiple-periods" : "gssi-current-period",
                day = $tr.find(".gssi-opening-hours").attr("data-day"),
                selectName = "gssi_location[hours]";

        newPeriod = '<div class="' + periodCss + '">';
        newPeriod += '<select autocomplete="off" name="' + selectName + '[' + day + '_open][]" class="gssi-open-hour">' + createHourOptionList(returnList) + '</select>';
        newPeriod += '<span> - </span>';
        newPeriod += '<select autocomplete="off" name="' + selectName + '[' + day + '_close][]" class="gssi-close-hour">' + createHourOptionList(returnList) + '</select>';
        newPeriod += '<div class="gssi-icon-cancel-circled"></div>';
        newPeriod += '</div>';

        $tr.find(".gssi-location-closed").remove();
        $("#gssi-hours-" + day + "").append(newPeriod).end();

        initHourEvents();

        if ($("#gssi-editor-hour-format").val() == 24) {
            hours = {
                "open": "09:00",
                "close": "17:00"
            };
        } else {
            hours = {
                "open": "9:00 AM",
                "close": "5:00 PM"
            };
        }

        $tr.find(".gssi-open-hour:last option[value='" + hours.open + "']").attr("selected", "selected");
        $tr.find(".gssi-close-hour:last option[value='" + hours.close + "']").attr("selected", "selected");

        e.preventDefault();
    });

    /**
     * Remove an openings period
     * 
     * @param  {object} elem The clicked element
     * @return {void}
     */
    function removePeriod(elem) {
        var periodsLeft = currentPeriodCount(elem),
                $tr = elem.parents("tr"),
                day = $tr.find(".gssi-opening-hours").attr("data-day");

        // If there was 1 opening hour left then we add the 'Closed' text.
        if (periodsLeft == 1) {
            $tr.find(".gssi-opening-hours").html("<p class='gssi-location-closed'>" + gssiSettings.closedDate + "<input type='hidden' name='gssi[hours][" + day + "_open]' value='' /></p>");
        }

        // Remove the selected openings period.
        elem.parent().closest(".gssi-current-period").remove();

        // If the first element has the multiple class, then we need to remove it.
        if ($tr.find(".gssi-opening-hours div:first-child").hasClass("gssi-multiple-periods")) {
            $tr.find(".gssi-opening-hours div:first-child").removeClass("gssi-multiple-periods");
        }
    }

    /**
     * Count the current opening periods in a day block
     * 
     * @param  {object} elem		   The clicked element
     * @return {string} currentPeriods The ammount of period divs found
     */
    function currentPeriodCount(elem) {
        var currentPeriods = elem.parents("tr").find(".gssi-current-period").length;

        return currentPeriods;
    }

    /**
     * Create an option list with the correct opening hour format and interval
     * 
     * @param  {string} returnList Whether to return the option list or call the setSelectedOpeningHours function
     * @return {mixed}  optionList The html for the option list of or void
     */
    function createHourOptionList(returnList) {
        var openingHours, openingHourInterval, hour, hrFormat,
                pm = false,
                twelveHrsAfternoon = false,
                pmOrAm = "",
                optionList = "",
                openingTimes = [],
                openingHourOptions = {
                    "hours": {
                        "hr12": [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
                        "hr24": [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23]
                    },
                    "interval": ['00', '15', '30', '45']
                };

        hrFormat = 24;

        $("#gssi-location-hours td").removeAttr("style");

        $("#gssi-location-hours").removeClass().addClass("gssi-twentyfour-format");
        openingHours = openingHourOptions.hours.hr24;

        openingHourInterval = openingHourOptions.interval;

        for (var i = 0; i < openingHours.length; i++) {
            hour = openingHours[i];

            if (hour.toString().length == 1) {
                hour = "0" + hour;
            }

            // Collect the new opening hour format and interval.
            for (var j = 0; j < openingHourInterval.length; j++) {
                openingTimes.push(hour + ":" + openingHourInterval[j] + " " + pmOrAm);
            }
        }

        // Create the <option> list.
        for (var i = 0; i < openingTimes.length; i++) {
            optionList = optionList + '<option value="' + $.trim(openingTimes[i]) + '">' + $.trim(openingTimes[i]) + '</option>';
        }

        if (returnList) {
            return optionList;
        } else {
            setSelectedOpeningHours(optionList, hrFormat);
        }
    }

    /**
     * Set the correct selected opening hour in the dropdown
     * 
     * @param  {string} optionList The html for the option list 
     * @param  {string} hrFormat   The html for the option list
     * @return {void}
     */
    function setSelectedOpeningHours(optionList, hrFormat) {
        var splitHour, hourType, periodBlock,
                hours = {};

        /* 
         * Loop over each open/close block and make sure the selected 
         * value is still set as selected after changing the hr format.
         */
        $(".gssi-current-period").each(function () {
            periodBlock = $(this),
                    hours = {
                        "open": $(this).find(".gssi-open-hour").val(),
                        "close": $(this).find(".gssi-close-hour").val()
                    };

            // Set the new hour format for both dropdowns.
            $(this).find("select").html(optionList).promise().done(function () {

                // Select the correct start/end hours as selected.
                for (var key in hours) {
                    if (hours.hasOwnProperty(key)) {

                        // Breakup the hour, so we can check the part before and after the : separately.
                        splitHour = hours[key].split(":");

                        if (hrFormat == 12) {
                            hourType = "";

                            // Change the hours to a 12hr format and add the correct AM or PM.
                            if (hours[key].charAt(0) == 0) {
                                hours[key] = hours[key].substr(1);
                                hourType = " AM";
                            } else if ((splitHour[0].length == 2) && (splitHour[0] > 12)) {
                                hours[key] = (splitHour[0] - 12) + ":" + splitHour[1];
                                hourType = " PM";
                            } else if (splitHour[0] < 12) {
                                hours[key] = splitHour[0] + ":" + splitHour[1];
                                hourType = " AM";
                            } else if (splitHour[0] == 12) {
                                hours[key] = splitHour[0] + ":" + splitHour[1];
                                hourType = " PM";
                            }

                            // Add either AM or PM behind the time.
                            if ((splitHour[1].indexOf("PM") == -1) && (splitHour[1].indexOf("AM") == -1)) {
                                hours[key] = hours[key] + hourType;
                            }

                        } else if (hrFormat == 24) {

                            // Change the hours to a 24hr format and remove the AM or PM.
                            if (splitHour[1].indexOf("PM") != -1) {
                                if (splitHour[0] == 12) {
                                    hours[key] = "12:" + splitHour[1].replace(" PM", "");
                                } else {
                                    hours[key] = (+splitHour[0] + 12) + ":" + splitHour[1].replace(" PM", "");
                                }
                            } else if (splitHour[1].indexOf("AM") != -1) {
                                if (splitHour[0].toString().length == 1) {
                                    hours[key] = "0" + splitHour[0] + ":" + splitHour[1].replace(" AM", "");
                                } else {
                                    hours[key] = splitHour[0] + ":" + splitHour[1].replace(" AM", "");
                                }
                            } else {
                                hours[key] = splitHour[0] + ":" + splitHour[1]; // When the interval is changed
                            }
                        }

                        // Set the correct value as the selected one.
                        periodBlock.find(".gssi-" + key + "-hour option[value='" + $.trim(hours[key]) + "']").attr("selected", "selected");
                    }
                }

            });
        });
    }

// Update the opening hours format if one of the dropdown values change.
    $("#gssi-editor-hour-format, #gssi-editor-hour-interval").on("change", function () {
        createHourOptionList();
    });

// Show the tooltips.
    $(".gssi-info").on("mouseover", function () {
        $(this).find(".gssi-info-text").show();
    });

    $(".gssi-info").on("mouseout", function () {
        $(this).find(".gssi-info-text").hide();
    });

// If the start location is empty, then we color the info icon red instead of black.
    if ($("#gssi-latlng").length && !$("#gssi-latlng").val()) {
        $("#gssi-latlng").siblings("label").find(".gssi-info").addClass("gssi-required-setting");
    }

    /** 
     * Try to apply the custom style data to the map.
     * 
     * If the style data is invalid json we show an error.
     * 
     * @return {void}
     */
    function tryCustomMapStyle() {
        var validStyle = "",
                mapStyle = $.trim($("#gssi-map-style").val());

        $(".gssi-style-preview-error").remove();

        if (mapStyle) {

            // Make sure the data is valid json.
            validStyle = tryParseJSON(mapStyle);

            if (!validStyle) {
                $("#gssi-style-preview").after("<div class='gssi-style-preview-error'>" + gssiSettings.styleError + "</div>");
            }
        }

        map.setOptions({styles: validStyle});
    }

// Handle the map style changes on the settings page.
    if ($("#gssi-map-style").val()) {
        tryCustomMapStyle();
    }

// Handle clicks on the map style preview button.
    $("#gssi-style-preview").on("click", function () {
        tryCustomMapStyle();

        return false;
    });

    /**
     * Make sure the JSON is valid. 
     * @param  {string} jsonString The JSON data
     * @return {object|boolean}	   The JSON string or false if it's invalid json.
     */
    function tryParseJSON(jsonString) {

        try {
            var o = JSON.parse(jsonString);

            /* 
             * Handle non-exception-throwing cases:
             * Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
             * but... JSON.parse(null) returns 'null', and typeof null === "object", 
             * so we must check for that, too.
             */
            if (o && typeof o === "object" && o !== null) {
                return o;
            }
        } catch (e) {
        }

        return false;
    }

    /**
     * Make a request to the geocode API with the
     * provided server key to check for any errors.
     *
     * @return void
     */
    function validateServerKey() {
        var ajaxData = {
            action: "validate_server_key",
            server_key: $("#gssi-api-server-key").val()
        };

        $.get(gssiSettings.ajaxurl, ajaxData, function (response) {
            if (!response.valid && typeof response.msg !== "undefined") {
                createErrorNotice(response.msg, "server-key");
            } else {
                $("#gssi-api-server-key").removeClass("gssi-error");
            }
        });
    }

    /**
     * Create the error notice.
     *
     * @param {string} errorMsg The error message to show
     * @param {string} type 	The type of API key we need to show the notice for
     * @return void
     */
    function createErrorNotice(errorMsg, type) {
        var errorNotice, noticeLocation;

        errorNotice = '<div id="setting-error-' + type + '" class="error settings-error notice is-dismissible">';
        errorNotice += '<p><strong>' + errorMsg + '</strong></p>';
        errorNotice += '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' + gssiSettings.dismissNotice + '</span></button>';
        errorNotice += '</div>';

        noticeLocation = ($("#gssi-tabs").length) ? 'gssi-tabs' : 'gssi-settings-form';

        $("#" + noticeLocation + "").before(errorNotice);
        $("#gssi-api-" + type + "").addClass("gssi-error");
    }

// Make sure the custom error notices can be removed
    $("#gssi-wrap").on("click", "button.notice-dismiss", function () {
        $(this).closest('div.notice').remove();
    });

});


