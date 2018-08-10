jQuery(document).ready(function ($) {

    // get closet location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            if (!$('.closest-location').hasClass('has-position')) {
                getClosetStoreLocator(pos);
            }
        }, function (error) {
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    console.log(gssiGeolocationErrors.denied);
                    break;
                case error.POSITION_UNAVAILABLE:
                    console.log(gssiGeolocationErrors.unavailable);
                    break;
                case error.TIMEOUT:
                    console.log(gssiGeolocationErrors.timeout);
                    break;
                default:
                    console.log(gssiGeolocationErrors.generalError);
                    break;
            }
            if (!gssiSettings.isSingle) {
                getClosetStoreLocator();
            }
        });
    }

    function getClosetStoreLocator(position) {
        var defaultPos = gssiSettings.startLatlng.split(',');
        var latitude = defaultPos[0];
        var longitude = defaultPos[1];
        if (position) {
            var latitude = position.coords.latitude;
            var longitude = position.coords.longitude;
        }
        $.ajax({
            type: 'POST',
            url: gssiSettings.ajaxurl,
            data: {action: 'location_closet', lat: latitude, lng: longitude, is_listpage: true},
            beforeSend: function () {
                //$('.header-location').prepend($("<img class='loading'/>").attr("src", gssiSettings.url + "/includes/frontend/img/ajax-loader.gif"));
            },
            success: function (locations) {
                if (locations.length) {
                    _.extend(locations[0], templateHelpers);
                    var template = $('#gssi-closest-template').html();
                    var locationHtml = _.template(template)(locations[0]);
                    $('.closest-location').append(locationHtml);
                    makeMyRicky();
                }
            },
            complete: function () {
                //$('.header-location').find('img.loading').remove();
            }
        });
    }


    // end get closet location
    
    function makeMyRicky() {
        $('.btn-make-ricky').click(function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var locationId = $(this).attr('data-location-id');
            $.ajax({
                type: 'POST',
                url: locationAjaxUrl,
                data: {action: 'make_ricky', location_id: locationId},
                beforeSend: function () {
                    //$('.header-location').append($("<img class='loading'/>").attr("src", gssiSettings.url + "includes/frontend/img/ajax-loader.gif"));
                },
                success: function (result) {
                    if (result) {
                        window.location.reload();
                    }
                },
                complete: function () {
                    //$('.header-location').find('img.loading').remove();
                }
            });
        });
    }
    
    makeMyRicky();

    var geocoder, map, directionsDisplay, directionsService, geolocationLatlng, autoCompleteLatLng,
            activeWindowMarkerId, infoWindow, markerClusterer, startMarkerData, startAddress,
            openInfoWindow = [],
            markersArray = [],
            mapsArray = [],
            markerSettings = {},
            directionMarkerPosition = {},
            mapDefaults = {},
            resetMap = false,
            streetViewAvailable = false,
            autoLoad = (typeof gssiSettings !== "undefined") ? gssiSettings.autoLoad : "",
            firstZoom;
            
    /**
     * Helper methods for the underscore templates.
     * 
     * @link	 http://underscorejs.org/#template
     * @requires underscore.js
     */
    var templateHelpers = {
        /**
         * Make the phone number clickable if we are dealing with a mobile useragent.
         * 
         * @param	{string} phoneNumber The phone number
         * @returns {string} phoneNumber Either just the plain number, or with a link wrapped around it with tel:
         */
        formatPhoneNumber: function (phoneNumber) {
            if (checkMobileUserAgent()) {
                phoneNumber = "<a href='tel:" + templateHelpers.formatClickablePhoneNumber(phoneNumber) + "'>" + phoneNumber + "</a>";
            }

            return phoneNumber;
        },
        /**
         * Replace spaces - . and () from phone numbers. 
         * Also if the number starts with a + we check for a (0) and remove it.
         * 
         * @param	{string} phoneNumber The phone number
         * @returns {string} phoneNumber The 'cleaned' number
         */
        formatClickablePhoneNumber: function (phoneNumber) {
            if ((phoneNumber.indexOf("+") != -1) && (phoneNumber.indexOf("(0)") != -1)) {
                phoneNumber = phoneNumber.replace("(0)", "");
            }

            return phoneNumber.replace(/(-| |\(|\)|\.|)/g, "");
        },
        /**
         * Create the html for the info window action.
         * 
         * @param	{string} id		The location id
         * @returns {string} output The html for the info window actions
         */
        createInfoWindowActions: function (id) {
            var output,
                    streetView = "",
                    zoomTo = "";

            if ($("#gssi-location-gmap").length) {
                if (streetViewAvailable) {
                    streetView = "<a class='gssi-streetview' href='#'>" + gssiLocationLabels.streetView + "</a>";
                }

                if (gssiSettings.markerZoomTo == 1) {
                    zoomTo = "<a class='gssi-zoom-here' href='#'>" + gssiLocationLabels.zoomHere + "</a>";
                }

                output = "<div class='gssi-info-actions'>" + templateHelpers.createDirectionUrl(id) + streetView + zoomTo + "</div>";
            }

            return output;
        },
        /**
         * Create the url that takes the user to the maps.google.com page 
         * and shows the correct driving directions.
         * 
         * @param	{string} id			  The location id
         * @returns {string} directionUrl The full maps.google.com url with the encoded start + end address
         */
        createDirectionUrl: function (id) {
            var directionUrl, destinationAddress, zip,
                    url = {};

            if (gssiSettings.directionRedirect == 1) {

                // If we somehow failed to determine the start address, just set it to empty.
                if (typeof startAddress === "undefined") {
                    startAddress = "";
                }

                url.target = "target='_blank'";

                // If the id exists the user clicked on a marker we get the direction url from the search results.
                if (typeof id !== "undefined") {
                    url.src = $("[data-location-id=" + id + "] .gssi-directions").attr("href");
                } else {

                    // Only add a , after the zip if the zip value exists.
                    if (this.zip) {
                        zip = this.zip + ", ";
                    } else {
                        zip = "";
                    }

                    destinationAddress = this.address + ", " + this.city + ", " + zip + this.country;

                    url.src = "https://www.google.com/maps/dir/?api=1&origin=" + templateHelpers.rfc3986EncodeURIComponent(startAddress) + "&destination=" + templateHelpers.rfc3986EncodeURIComponent(destinationAddress) + "&travelmode=" + gssiSettings.directionsTravelMode.toLowerCase() + "";
                }
            } else {
                url = {
                    src: "#",
                    target: ""
                };
            }

            directionUrl = "<a class='gssi-directions' " + url.target + " href='" + url.src + "'><i class='fas fa-map-marker'></i>" + gssiLocationLabels.directions + "</a>";

            return directionUrl;
        },
        /**
         * Make the URI encoding compatible with RFC 3986.
         * 
         * !, ', (, ), and * will be escaped, otherwise they break the string.
         * 
         * @param	{string} str The string to encode
         * @returns {string} The encoded string
         */
        rfc3986EncodeURIComponent: function (str) {
            return encodeURIComponent(str).replace(/[!'()*]/g, escape);
        }
    };

    /** 
     * Set the underscore template settings.
     * 
     * Defining them here prevents other plugins 
     * that also use underscore / backbone, and defined a
     * different _.templateSettings from breaking the 
     * rendering of the location template.
     * 
     * @link	 http://underscorejs.org/#template
     * @requires underscore.js
     */
    _.templateSettings = {
        evaluate: /\<\%(.+?)\%\>/g,
        interpolate: /\<\%=(.+?)\%\>/g,
        escape: /\<\%-(.+?)\%\>/g
    };

// Only continue if a map is present.
    if ($(".gssi-location-gmap-canvas").length) {
        $("<img />").attr("src", gssiSettings.url + "includes/frontend/img/ajax-loader.gif");

        /* 
         * The [gssi] shortcode can only exist once on a page, 
         * but the [gssi_map] shortcode can exist multiple times.
         * 
         * So to make sure we init all the maps we loop over them.
         */
        $(".gssi-location-gmap-canvas").each(function (mapIndex) {
            var mapId = $(this).attr("id");

            initializeGmap(mapId, mapIndex);
        });

        /*
         * Check if we are dealing with a map that's placed in a tab,
         * if so run a fix to prevent the map from showing up grey.
         */
        maybeApplyTabFix();
    }

    /**
     * Initialize the map with the correct settings.
     *
     * @param   {string} mapId    The id of the map div
     * @param   {number} mapIndex Number of the map
     * @returns {void}
     */
    function initializeGmap(mapId, mapIndex) {
        var mapOptions, mapDetails, settings, infoWindow, latLng,
                bounds, mapData, zoomLevel,
                defaultZoomLevel = Number(gssiSettings.zoomLevel),
                maxZoom = Number(gssiSettings.autoZoomLevel);

        // Get the settings that belongs to the current map.
        settings = getMapSettings(mapIndex);

        /*
         * This is the value from either the settings page,
         * or the zoom level set through the shortcode.
         */
        zoomLevel = Number(settings.zoomLevel);

        /*
         * If they are not equal, then the zoom value is set through the shortcode.
         * If this is the case, then we use that as the max zoom level.
         */
        if (zoomLevel !== defaultZoomLevel) {
            maxZoom = zoomLevel;
        }

        // Create a new infoWindow, either with the infobox libray or use the default one.
        infoWindow = newInfoWindow();

        geocoder = new google.maps.Geocoder();
        directionsDisplay = new google.maps.DirectionsRenderer();
        directionsService = new google.maps.DirectionsService();

        // Set the map options.
        mapOptions = {
            zoom: zoomLevel,
            center: settings.startLatLng,
            mapTypeId: google.maps.MapTypeId[ settings.mapType.toUpperCase() ],
            mapTypeControl: Number(settings.mapTypeControl) ? true : false,
            scrollwheel: Number(settings.scrollWheel) ? true : false,
            streetViewControl: Number(settings.streetView) ? true : false,
            gestureHandling: settings.gestureHandling,
            zoomControlOptions: {
                position: google.maps.ControlPosition[ settings.controlPosition.toUpperCase() + '_TOP' ]
            }
        };

        // Get the correct marker path & properties.
        markerSettings = getMarkerSettings();

        map = new google.maps.Map(document.getElementById(mapId), mapOptions);

        // Check if we need to apply a map style.
        maybeApplyMapStyle(settings.mapStyle);

        if ((typeof window[ "gssiMap_" + mapIndex ] !== "undefined") && (typeof window[ "gssiMap_" + mapIndex ].locations !== "undefined")) {
            bounds = new google.maps.LatLngBounds(),
                    mapData = window[ "gssiMap_" + mapIndex ].locations;

            // Loop over the map data, create the infowindow object and add each marker.
            $.each(mapData, function (index) {
                latLng = new google.maps.LatLng(mapData[index].lat, mapData[index].lng);
                addMarker(latLng, mapData[index].id, mapData[index], false, infoWindow);
                bounds.extend(latLng);
            });

            // If we have more then one location on the map, then make sure to not zoom to far.
            if (mapData.length > 1) {
                // Make sure we don't zoom to far when fitBounds runs.
                attachBoundsChangedListener(map, maxZoom);

                // Make all the markers fit on the map.
                map.fitBounds(bounds);
            }

            /*
             * If we need to apply the fix for the map showing up grey because
             * it's used in a tabbed nav multiple times, then collect the active maps.
             *
             * See the fixGreyTabMap function.
             */
            if (_.isArray(gssiSettings.mapTabAnchor)) {
                mapDetails = {
                    map: map,
                    bounds: bounds,
                    maxZoom: maxZoom
                };

                mapsArray.push(mapDetails);
            }
        }

        // Only run this part if the location exist and we don't just have a basic map.
        if ($("#gssi-location-gmap").length) {

            if (gssiSettings.autoComplete == 1) {
                activateAutocomplete();
            }

            /* 
             * Not the most optimal solution, but we check the useragent if we should enable the styled dropdowns.
             * 
             * We do this because several people have reported issues with the styled dropdowns on
             * iOS and Android devices. So on mobile devices the dropdowns will be styled according 
             * to the browser styles on that device.
             */
            if (!checkMobileUserAgent() && $(".gssi-dropdown").length && gssiSettings.enableStyledDropdowns == 1) {
                createDropdowns();
            } else {
                $("#gssi-search-wrap select").show();

                if (checkMobileUserAgent()) {
                    $("#gssi-wrap").addClass("gssi-mobile");
                } else {
                    $("#gssi-wrap").addClass("gssi-default-filters");
                }
            }

            // Check if we need to autolocate the user, or autoload the location locations.
            if (!$(".gssi-search").hasClass("gssi-widget")) {
                if (gssiSettings.autoLocate == 1) {
                    checkGeolocation(settings.startLatLng, infoWindow);
                } else if (gssiSettings.autoLoad == 1) {
                    showStores(settings.startLatLng, infoWindow);
                }
            }

            // Move the mousecursor to the location search field if the focus option is enabled.
            if (gssiSettings.mouseFocus == 1 && !checkMobileUserAgent()) {
                $("#gssi-search-input").focus();
            }

            // Bind location search button.
            searchLocationBtn(infoWindow);

            // Add the 'reload' and 'find location' icon to the map.
            mapControlIcons(settings, map, infoWindow);

            // Check if the user submitted a search through a search widget.
            checkWidgetSubmit();
        }

        // Bind the zoom_changed listener.
        zoomChangedListener();
        
        if (gssiSettings.isSingle) {
            
            _.extend(window[ "gssiMap_" + mapIndex ].locations[0], templateHelpers);
            var template = $('#gssi-direction-single-template').html();
            
            var locationHtml = _.template(template)(window[ "gssiMap_" + mapIndex ].locations[0]);
            $('.single-direction').append(locationHtml);
        }
    }

    /**
     * Activate the autocomplete for the location search.
     * 
     * @link https://developers.google.com/maps/documentation/javascript/places-autocomplete
     * @returns {void}
     */
    function activateAutocomplete() {
        var input, autocomplete, place,
                options = {};

        // Check if we need to set the geocode component restrictions.
        if (typeof gssiSettings.geocodeComponents !== "undefined" && !$.isEmptyObject(gssiSettings.geocodeComponents)) {
            options.componentRestrictions = gssiSettings.geocodeComponents;
        }

        input = document.getElementById("gssi-search-input");
        autocomplete = new google.maps.places.Autocomplete(input, options);

        autocomplete.addListener("place_changed", function () {
            place = autocomplete.getPlace();

            /* 
             * Assign the returned latlng to the autoCompleteLatLng var. 
             * This var is used when the users submits the search.
             */
            if (place.geometry) {
                autoCompleteLatLng = place.geometry.location;
            }
        });
    }

    /**
     * Make sure that the 'Zoom here' link in the info window 
     * doesn't zoom past the max auto zoom level.
     * 
     * The 'max auto zoom level' is set on the settings page.
     *
     * @returns {void}
     */
    function zoomChangedListener() {
        google.maps.event.addListener(map, "zoom_changed", function (event) {            
            if (!gssiSettings.isSingle) {
                if (!firstZoom) {
                    firstZoom = true;
                } else {
                    var distance = getDistance(map.getBounds().getSouthWest(), map.getCenter());
                    loadMarkerOnZoom(map.getCenter(), distance);
                }
            }
        });
    }
    
    function loadMarkerOnZoom(startLatLng, distance) {
        var latLng, noResultsMsg, ajaxData,
                locationData = "",
                draggable = false,
                template = $("#gssi-listing-template").html(),
                $locationList = $("#gssi-locations ul"),
                preloader = gssiSettings.url + "includes/frontend/img/ajax-loader.gif";

        ajaxData = {
                    action: "location_search",
                    lat: startLatLng.lat(),
                    lng: startLatLng.lng(),
                    max_results: gssiSettings.maxResults,
                    zoom: true,
                    search_radius: distance
                };
        var infoWindow = newInfoWindow();
        
        // Add the preloader.
        $locationList.append("<li class='gssi-preloader'><img src='" + preloader + "'/>" + gssiLocationLabels.preloader + "</li>");

        $("#gssi-wrap").removeClass("gssi-no-results");

        $.get(gssiSettings.ajaxurl, ajaxData, function (response) {
            // Remove the preloaders and no results msg.
            $(".gssi-preloader, .no-results").remove();
            
            // Remove all single markers from the map.
                if (markersArray.length) {
                for (i = 0, len = markersArray.length; i < len; i++) {
                    if(markersArray[i].locationId != 0) {
                        markersArray[i].setMap(null);
                    }
                }
            }
            
            if (response.length > 0) {
                // Loop over the returned locations.
                $.each(response, function (index) {
                    var flag = false;
                    _.extend(response[index], templateHelpers);

                    // Add the location maker to the map.
                    latLng = new google.maps.LatLng(response[index].lat, response[index].lng);
                    if(map.getBounds().contains(latLng)){
                        addMarker(latLng, response[index].id, response[index], draggable, infoWindow);

                        // Create the HTML output with help from underscore js.
                        locationData = locationData + _.template(template)(response[index]);
                    }
                    
                });

                $("#gssi-result-list").off("click", ".gssi-directions");
                
                // Remove the old search results.
                $locationList.empty();
                
                // Add the html for the location listing to the <ul>.
                $locationList.append(locationData);
                makeMyRicky();

                $("#gssi-result-list").on("click", ".gssi-directions", function () {

                    // Check if we need to render the direction on the map.
                    if (gssiSettings.directionRedirect != 1) {
                        renderDirections($(this));

                        return false;
                    }
                });

                // Do we need to create a marker cluster?
                checkMarkerClusters();

                $("#gssi-result-list p:empty").remove();
            } else {
                noResultsMsg = getNoResultsMsg();

                $("#gssi-wrap").addClass("gssi-no-results");

                $locationList.html("<li class='gssi-no-results-msg'>" + noResultsMsg + "</li>");
            }
        });

        // Move the mousecursor to the location search field if the focus option is enabled.
        if (gssiSettings.mouseFocus == 1 && !checkMobileUserAgent()) {
            $("#gssi-search-input").focus();
        }
    }
    
    var rad = function (x) {
        return x * Math.PI / 180;
    };

    var getDistance = function (p1, p2) {
        var R = 6371; // Earth’s mean radius in meter
        var dLat = rad(p2.lat() - p1.lat());
        var dLong = rad(p2.lng() - p1.lng());
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(rad(p1.lat())) * Math.cos(rad(p2.lat())) *
                Math.sin(dLong / 2) * Math.sin(dLong / 2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        var d = R * c;
        return d; // returns the distance in km
    };

    /**
     * Get the correct map settings.
     *
     * @param	{number} mapIndex    Number of the map
     * @returns {object} mapSettings The map settings either set through a shortcode or the default settings 
     */
    function getMapSettings(mapIndex) {
        var j, len, shortCodeVal,
                settingOptions = ["zoomLevel", "mapType", "mapTypeControl", "mapStyle", "streetView", "scrollWheel", "controlPosition"],
                mapSettings = {
                    zoomLevel: gssiSettings.zoomLevel,
                    mapType: gssiSettings.mapType,
                    mapTypeControl: gssiSettings.mapTypeControl,
                    mapStyle: gssiSettings.mapStyle,
                    streetView: gssiSettings.streetView,
                    scrollWheel: gssiSettings.scrollWheel,
                    controlPosition: gssiSettings.controlPosition,
                    gestureHandling: gssiSettings.gestureHandling
                };

        // If there are settings that are set through the shortcode, then we use them instead of the default ones.
        if ((typeof window[ "gssiMap_" + mapIndex ] !== "undefined") && (typeof window[ "gssiMap_" + mapIndex ].shortCode !== "undefined")) {
            for (j = 0, len = settingOptions.length; j < len; j++) {
                shortCodeVal = window[ "gssiMap_" + mapIndex ].shortCode[ settingOptions[j] ];

                // If the value is set through the shortcode, we overwrite the default value.
                if (typeof shortCodeVal !== "undefined") {
                    mapSettings[ settingOptions[j] ] = shortCodeVal;
                }
            }
        }

        mapSettings.startLatLng = getStartLatlng(mapIndex);

        return mapSettings;
    }

    /**
     * Get the latlng coordinates that are used to init the map.
     *
     * @param	{number} mapIndex    Number of the map
     * @returns {object} startLatLng The latlng value where the map will initially focus on 
     */
    function getStartLatlng(mapIndex) {
        var startLatLng, latLng,
                firstLocation = "";

        /* 
         * Maps that are added with the [gssi_map] shortcode will have the locations key set. 
         * If it exists we use the coordinates from the first location to center the map on. 
         */
        if ((typeof window[ "gssiMap_" + mapIndex ] !== "undefined") && (typeof window[ "gssiMap_" + mapIndex ].locations !== "undefined")) {
            firstLocation = window[ "gssiMap_" + mapIndex ].locations[0];
        }

        /* 
         * Either use the coordinates from the first location as the start coordinates 
         * or the default start point defined on the settings page.
         * 
         * If both are not available we set it to 0,0 
         */
        if ((typeof firstLocation !== "undefined" && typeof firstLocation.lat !== "undefined") && (typeof firstLocation.lng !== "undefined")) {
            startLatLng = new google.maps.LatLng(firstLocation.lat, firstLocation.lng);
        } else if (gssiSettings.startLatlng !== "") {
            latLng = gssiSettings.startLatlng.split(",");
            startLatLng = new google.maps.LatLng(latLng[0], latLng[1]);
        } else {
            startLatLng = new google.maps.LatLng(0, 0);
        }

        return startLatLng;
    }

    /**
     * Create a new infoWindow object.
     * 
     * Either use the default infoWindow or use the infobox library.
     * 
     * @return {object} infoWindow The infoWindow object
     */
    function newInfoWindow() {
        var boxClearance, boxPixelOffset,
                infoBoxOptions = {};

        // Do we need to use the infobox script or use the default info windows?
        if ((typeof gssiSettings.infoWindowStyle !== "undefined") && (gssiSettings.infoWindowStyle == "infobox")) {

            // See http://google-maps-utility-library-v3.googlecode.com/svn/trunk/infobox/docs/reference.html.
            boxClearance = gssiSettings.infoBoxClearance.split(",");
            boxPixelOffset = gssiSettings.infoBoxPixelOffset.split(",");
            infoBoxOptions = {
                alignBottom: true,
                boxClass: gssiSettings.infoBoxClass,
                closeBoxMargin: gssiSettings.infoBoxCloseMargin,
                closeBoxURL: gssiSettings.infoBoxCloseUrl,
                content: "",
                disableAutoPan: (Number(gssiSettings.infoBoxDisableAutoPan)) ? true : false,
                enableEventPropagation: (Number(gssiSettings.infoBoxEnableEventPropagation)) ? true : false,
                infoBoxClearance: new google.maps.Size(Number(boxClearance[0]), Number(boxClearance[1])),
                pixelOffset: new google.maps.Size(Number(boxPixelOffset[0]), Number(boxPixelOffset[1])),
                zIndex: Number(gssiSettings.infoBoxZindex),
                boxStyle: {
                    width: "300px",
                    height: "200px"
                }
            };

            infoWindow = new InfoBox(infoBoxOptions);
        } else {
            infoWindow = new google.maps.InfoWindow();
        }

        return infoWindow;
    }

    /**
     * Get the required marker settings.
     * 
     * @return {object} settings The marker settings.
     */
    function getMarkerSettings() {
        var markerProp,
                markerProps = gssiSettings.markerIconProps,
                settings = {};

        // Use the correct marker path.
        if (typeof markerProps.url !== "undefined") {
            settings.url = markerProps.url;
        } else if (typeof markerProps.categoryMarkerUrl !== "undefined") {
            settings.categoryMarkerUrl = markerProps.categoryMarkerUrl;
        } else if (typeof markerProps.alternateMarkerUrl !== "undefined") {
            settings.alternateMarkerUrl = markerProps.alternateMarkerUrl;
        } else {
            settings.url = gssiSettings.url + "includes/frontend/img/markers/";
        }

        for (var key in markerProps) {
            if (markerProps.hasOwnProperty(key)) {
                markerProp = markerProps[key].split(",");

                if (markerProp.length == 2) {
                    settings[key] = markerProp;
                }
            }
        }

        return settings;
    }

    /**
     * Check if we have a map style that we need to apply to the map.
     * 
     * @param  {string} mapStyle The id of the map
     * @return {void}
     */
    function maybeApplyMapStyle(mapStyle) {

        // Make sure the JSON is valid before applying it as a map style.
        mapStyle = tryParseJSON(mapStyle);

        if (mapStyle) {
            map.setOptions({styles: mapStyle});
        }
    }

    /**
     * Make sure the JSON is valid. 
     * 
     * @link   http://stackoverflow.com/a/20392392/1065294 
     * @param  {string} jsonString The JSON data
     * @return {object|boolean}	The JSON string or false if it's invalid json.
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
     * Add the start marker and call the function that inits the location search.
     *
     * @param	{object} startLatLng The start coordinates
     * @param	{object} infoWindow  The infoWindow object
     * @returns {void}
     */
    function showStores(startLatLng, infoWindow) {
        addMarker(startLatLng, 0, '', true, infoWindow); // This marker is the 'start location' marker. With a locationId of 0, no name and is draggable
        findStoreLocations(startLatLng, resetMap, autoLoad, infoWindow);
    }

    /**
     * Compare the current useragent to a list of known mobile useragents ( not optimal, I know ).
     *
     * @returns {boolean} Whether the useragent is from a known mobile useragent or not.
     */
    function checkMobileUserAgent() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    /**
     * Check if Geolocation detection is supported. 
     * 
     * If there is an error / timeout with determining the users 
     * location, then we use the 'start point' value from the settings 
     * as the start location through the showStores function. 
     *
     * @param	{object} startLatLng The start coordinates
     * @param	{object} infoWindow  The infoWindow object
     * @returns {void}
     */
    function checkGeolocation(startLatLng, infoWindow) {

        if (navigator.geolocation) {
            var geolocationInProgress, locationTimeout,
                    keepStartMarker = false,
                    timeout = Number(gssiSettings.geoLocationTimout);

            // Make the direction icon flash every 600ms to indicate the geolocation attempt is in progress.
            geolocationInProgress = setInterval(function () {
                $(".gssi-icon-direction").toggleClass("gssi-active-icon");
            }, 600);

            /* 
             * If the user doesn't approve the geolocation request within the value set in 
             * gssiSettings.geoLocationTimout, then the default map is loaded. 
             * 
             * You can increase the timeout value with the gssi_geolocation_timeout filter. 
             */
            locationTimeout = setTimeout(function () {
                geolocationFinished(geolocationInProgress);
                showStores(startLatLng, infoWindow);
            }, timeout);

            navigator.geolocation.getCurrentPosition(function (position) {
                geolocationFinished(geolocationInProgress);
                clearTimeout(locationTimeout);

                /* 
                 * If the timeout is triggerd and the user later decides to enable 
                 * the geolocation detection again, it gets messy with multiple start markers. 
                 * 
                 * So we first clear the map before adding new ones.
                 */
                deleteOverlays(keepStartMarker);
                handleGeolocationQuery(startLatLng, position, resetMap, infoWindow);

                /*
                 * Workaround for this bug in Firefox https://bugzilla.mozilla.org/show_bug.cgi?id=1283563.
                 * to keep track if the geolocation code has already run.
                 * 
                 * Otherwise after the users location is determined succesfully the code 
                 * will also detect the returned error, and triggers showStores() to 
                 * run with the start location set in the incorrect location.
                 */

                $(".gssi-search").addClass("gssi-geolocation-run");
            }, function (error) {

                /* 
                 * Only show the geocode errors if the user actually clicked on the direction icon. 
                 * 
                 * Otherwise if the "Attempt to auto-locate the user" option is enabled on the settings page, 
                 * and the geolocation attempt fails for whatever reason ( blocked in browser, unavailable etc ). 
                 * Then the first thing the visitor will see on pageload is an alert box, which isn't very userfriendly.
                 * 
                 * If an error occurs on pageload without the user clicking on the direction icon,
                 * the default map is shown without any alert boxes.
                 */
                if ($(".gssi-icon-direction").hasClass("gssi-user-activated") && !$(".gssi-search").hasClass("gssi-geolocation-run")) {
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            alert(gssiGeolocationErrors.denied);
                            break;
                        case error.POSITION_UNAVAILABLE:
                            alert(gssiGeolocationErrors.unavailable);
                            break;
                        case error.TIMEOUT:
                            alert(gssiGeolocationErrors.timeout);
                            break;
                        default:
                            alert(gssiGeolocationErrors.generalError);
                            break;
                    }

                    $(".gssi-icon-direction").removeClass("gssi-active-icon");
                } else if (!$(".gssi-search").hasClass("gssi-geolocation-run")) {
                    clearTimeout(locationTimeout);
                    showStores(startLatLng, infoWindow);
                }
            },
                    {maximumAge: 60000, timeout: timeout, enableHighAccuracy: true});
        } else {
            alert(gssiGeolocationErrors.unavailable);
            showStores(startLatLng, infoWindow);
        }
    }

    /**
     * Clean up after the geolocation attempt finished.
     * 
     * @param	{number} geolocationInProgress
     * @returns {void}
     */
    function geolocationFinished(geolocationInProgress) {
        clearInterval(geolocationInProgress);
        $(".gssi-icon-direction").removeClass("gssi-active-icon");
    }

    /**
     * Handle the data returned from the Geolocation API.
     * 
     * If there is an error / timeout determining the users location,
     * then we use the 'start point' value from the settings as the start location through the showStores function. 
     *
     * @param	{object}  startLatLng The start coordinates
     * @param	{object}  position    The latlng coordinates from the geolocation attempt
     * @param	{boolean} resetMap    Whether we should reset the map or not
     * @param	{object}  infoWindow  The infoWindow object
     * @returns {void}
     */
    function handleGeolocationQuery(startLatLng, position, resetMap, infoWindow) {

        if (typeof (position) === "undefined") {
            showStores(startLatLng, infoWindow);
        } else {
            var latLng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

            /* 
             * Store the latlng from the geolocation for when the user hits "reset" again 
             * without having to ask for permission again.
             */
            geolocationLatlng = position;

            reverseGeocode(latLng); // Set the zipcode that belongs to the latlng in the input field
            map.setCenter(latLng);
            addMarker(latLng, 0, '', true, infoWindow); // This marker is the 'start location' marker. With a locationId of 0, no name and is draggable
            findStoreLocations(latLng, resetMap, autoLoad, infoWindow);
        }
    }

    /**
     * Handle clicks on the location search button.
     * 
     * @todo disable button while AJAX request still runs.
     * @param	{object} infoWindow The infoWindow object
     * @returns {void}
     */
    function searchLocationBtn(infoWindow) {

        $("#gssi-search-btn").unbind("click").bind("click", function (e) {
            var keepStartMarker = false;

            $("#gssi-search-input").removeClass();

            $("#gssi-result-list ul").empty();
            $("#gssi-locations").show();
            $(".gssi-direction-before, .gssi-direction-after").remove();
            $("#gssi-direction-details").hide();

            resetMap = false;

            // Force the open InfoBox info window to close.
            closeInfoBoxWindow();

            deleteOverlays(keepStartMarker);
            deleteStartMarker();

            /*
             * Check if we need to geocode the user input,
             * or if autocomplete is enabled and we already
             * have the latlng values.
             */
            if (gssiSettings.autoComplete == 1 && typeof autoCompleteLatLng !== "undefined") {
                prepareStoreSearch(autoCompleteLatLng, infoWindow);
            } else {
                codeAddress(infoWindow);
            }
            return false;
        });
    }

    /**
     * Force the open InfoBox info window to close
     * 
     * This is required if the user makes a new search, 
     * or clicks on the "Directions" link.
     *
     * @return {void}
     */
    function closeInfoBoxWindow() {
        if ((typeof gssiSettings.infoWindowStyle !== "undefined") && (gssiSettings.infoWindowStyle == "infobox") && typeof openInfoWindow[0] !== "undefined") {
            openInfoWindow[0].close();
        }
    }

    /**
     * Add the 'reload' and 'find location' icon to the map.
     *
     * @param  {object} settings   Map settings
     * @param  {object} map		   The map object
     * @param  {object} infoWindow The info window object
     * @return {void}
     */
    function mapControlIcons(settings, map, infoWindow) {

        // Once the map has finished loading include the map control button(s).
        google.maps.event.addListenerOnce(map, "tilesloaded", function () {

            // Add the html for the map controls to the map.
            $(".gm-style").append(gssiSettings.mapControls);

            if ($(".gssi-icon-reset, #gssi-reset-map").length > 0) {

                // Bind the reset map button.
                resetMapBtn(settings.startLatLng, infoWindow);

                /* 
                 * Hide it to prevent users from clicking it before 
                 * the location location are placed on the map. 
                 */
                $(".gssi-icon-reset").hide();
            }

            // Bind the direction button to trigger a new geolocation request.
            $(".gssi-icon-direction").on("click", function () {
                $(this).addClass("gssi-user-activated");
                checkGeolocation(settings.startLatLng, infoWindow);
            });
        });
    }

    /**
     * Handle clicks on the "Reset" button.
     * 
     * @param	{object} startLatLng The start coordinates
     * @param	{object} infoWindow  The infoWindow object
     * @returns {void}
     */
    function resetMapBtn(startLatLng, infoWindow) {
        $(".gssi-icon-reset, #gssi-reset-map").on("click", function () {
            var keepStartMarker = false,
                    resetMap = true;

            /* 
             * Check if a map reset is already in progress, 
             * if so prevent another one from starting. 
             */
            if ($(this).hasClass("gssi-in-progress")) {
                return;
            }

            /* 
             * When the start marker is dragged the autoload value is set to false. 
             * So we need to check the correct value when the reset button is 
             * pushed before reloading the locations. 
             */
            if (gssiSettings.autoLoad == 1) {
                autoLoad = 1;
            }

            // Check if the latlng or zoom has changed since pageload, if so there is something to reset.
            if ((((map.getCenter().lat() !== mapDefaults.centerLatlng.lat()) || (map.getCenter().lng() !== mapDefaults.centerLatlng.lng()) || (map.getZoom() !== mapDefaults.zoomLevel)))) {
                deleteOverlays(keepStartMarker);

                $("#gssi-search-input").val("").removeClass();

                // We use this to prevent multiple reset request.
                $(".gssi-icon-reset").addClass("gssi-in-progress");

                // If marker clusters exist, remove them from the map.
                if (markerClusterer) {
                    markerClusterer.clearMarkers();
                }

                // Remove the start marker.
                deleteStartMarker();

                // Reset the dropdown values.
                resetDropdowns();

                if (gssiSettings.autoLocate == 1) {
                    handleGeolocationQuery(startLatLng, geolocationLatlng, resetMap, infoWindow);
                } else {
                    showStores(startLatLng, infoWindow);
                }
            }

            // Make sure the locations are shown and the direction details are hidden.
            $("#gssi-locations").show();
            $("#gssi-direction-details").hide();
        });
    }

    /**
     * Remove the start marker from the map.
     *
     * @returns {void}
     */
    function deleteStartMarker() {
        if ((typeof (startMarkerData) !== "undefined") && (startMarkerData !== "")) {
            startMarkerData.setMap(null);
            startMarkerData = "";
        }
    }

    /**
     * Reset the dropdown values for the max results, 
     * and search radius after the "reset" button is triggerd.
     * 
     * @returns {void}
     */
    function resetDropdowns() {
        var i, arrayLength, dataValue, catText, $customDiv, $customFirstLi, customSelectedText, customSelectedData,
                defaultFilters = $("#gssi-wrap").hasClass("gssi-default-filters"),
                defaultValues = [gssiSettings.searchRadius + ' ' + gssiSettings.distanceUnit, gssiSettings.maxResults],
                dropdowns = ["gssi-radius", "gssi-results"];

        for (i = 0, arrayLength = dropdowns.length; i < arrayLength; i++) {
            $("#" + dropdowns[i] + " select").val(parseInt(defaultValues[i]));
            $("#" + dropdowns[i] + " li").removeClass();

            if (dropdowns[i] == "gssi-radius") {
                dataValue = gssiSettings.searchRadius;
            } else if (dropdowns[i] == "gssi-results") {
                dataValue = gssiSettings.maxResults;
            }

            $("#" + dropdowns[i] + " li").each(function () {
                if ($(this).text() === defaultValues[i]) {
                    $(this).addClass("gssi-selected-dropdown");

                    $("#" + dropdowns[i] + " .gssi-selected-item").html(defaultValues[i]).attr("data-value", dataValue);
                }
            });
        }

        /** 
         * Reset the category dropdown.
         * @todo look for other way to do this in combination with above code. Maybe allow users to define a default cat on the settings page?
         */
        if ($("#gssi-category").length) {
            $("#gssi-category select").val(0);
            $("#gssi-category li").removeClass();
            $("#gssi-category li:first-child").addClass("gssi-selected-dropdown");

            catText = $("#gssi-category li:first-child").text();

            $("#gssi-category .gssi-selected-item").html(catText).attr("data-value", 0);
        }

        // If any custom dropdowns exist, then we reset them as well.
        if ($(".gssi-custom-dropdown").length > 0) {
            $(".gssi-custom-dropdown").each(function (index) {

                // Check if we are dealing with the styled dropdowns, or the default select dropdowns.
                if (!defaultFilters) {
                    $customDiv = $(this).siblings("div");
                    $customFirstLi = $customDiv.find("li:first-child");
                    customSelectedText = $customFirstLi.text();
                    customSelectedData = $customFirstLi.attr("data-value");

                    $customDiv.find("li").removeClass();
                    $customDiv.prev().html(customSelectedText).attr("data-value", customSelectedData);
                } else {
                    $(this).find("option").removeAttr("selected");
                }
            });
        }
    }

// Handle the click on the back button when the route directions are displayed.
    $("#gssi-result-list").on("click", ".gssi-back", function () {
        var i, len;

        // Remove the directions from the map.
        directionsDisplay.setMap(null);

        // Restore the location markers on the map.
        for (i = 0, len = markersArray.length; i < len; i++) {
            markersArray[i].setMap(map);
        }

        // Restore the start marker on the map.
        if ((typeof (startMarkerData) !== "undefined") && (startMarkerData !== "")) {
            startMarkerData.setMap(map);
        }

        // If marker clusters are enabled, restore them.
        if (markerClusterer) {
            checkMarkerClusters();
        }

        map.setCenter(directionMarkerPosition.centerLatlng);
        map.setZoom(directionMarkerPosition.zoomLevel);

        $(".gssi-direction-before, .gssi-direction-after").remove();
        $("#gssi-locations").show();
        $("#gssi-direction-details").hide();

        return false;
    });

    /**
     * Show the driving directions.
     * 
     * @param	{object} e The clicked elemennt
     * @returns {void}
     */
    function renderDirections(e) {
        var i, start, end, len, locationId;
        return false;
        // Force the open InfoBox info window to close.
        closeInfoBoxWindow();

        /* 
         * The locationId is placed on the li in the results list, 
         * but in the marker it will be on the wrapper div. So we check which one we need to target.
         */
        if (e.parents("li").length > 0) {
            locationId = e.parents("li").data("location-id");
        } else {
            locationId = e.parents(".gssi-info-window").data("location-id");
        }

        // Check if we need to get the start point from a dragged marker.
        if ((typeof (startMarkerData) !== "undefined") && (startMarkerData !== "")) {
            start = startMarkerData.getPosition();
        }

        // Used to restore the map back to the state it was in before the user clicked on 'directions'.
        directionMarkerPosition = {
            centerLatlng: map.getCenter(),
            zoomLevel: map.getZoom()
        };

        // Find the latlng that belongs to the start and end point.
        for (i = 0, len = markersArray.length; i < len; i++) {

            // Only continue if the start data is still empty or undefined.
            if ((markersArray[i].locationId == 0) && ((typeof (start) === "undefined") || (start === ""))) {
                start = markersArray[i].getPosition();
            } else if (markersArray[i].locationId == locationId) {
                end = markersArray[i].getPosition();
            }
        }

        if (start && end) {
            $("#gssi-direction-details ul").empty();
            $(".gssi-direction-before, .gssi-direction-after").remove();
            calcRoute(start, end);
        } else {
            alert(gssiLocationLabels.generalError);
        }
    }

    /**
     * Check what effect is triggerd once a user hovers over the location list. 
     * Either bounce the corresponding marker up and down, open the info window or ignore it.
     */
    if ($("#gssi-location-gmap").length) {
        if (gssiSettings.markerEffect == 'bounce') {
            $("#gssi-locations").on("mouseenter", "li", function () {
                letsBounce($(this).data("location-id"), "start");
            });

            $("#gssi-locations").on("mouseleave", "li", function () {
                letsBounce($(this).data("location-id"), "stop");
            });
        } else if (gssiSettings.markerEffect == 'info_window') {
            $("#gssi-locations").on("mouseenter", "li", function () {
                var i, len;

                for (i = 0, len = markersArray.length; i < len; i++) {
                    if (markersArray[i].locationId == $(this).data("location-id")) {
                        google.maps.event.trigger(markersArray[i], "click");
                        map.setCenter(markersArray[i].position);
                    }
                }
            });
        }
    }

    /**
     * Let a single marker bounce.
     * 
     * @param	{number} locationId The locationId of the marker that we need to bounce on the map
     * @param	{string} status  Indicates whether we should stop or start the bouncing
     * @returns {void}
     */
    function letsBounce(locationId, status) {
        var i, len, marker;

        // Find the correct marker to bounce based on the locationId.
        for (i = 0, len = markersArray.length; i < len; i++) {
            if (markersArray[i].locationId == locationId) {
                marker = markersArray[i];

                if (status == "start") {
                    marker.setAnimation(google.maps.Animation.BOUNCE);
                } else {
                    marker.setAnimation(null);
                }
            }
        }
    }

    /**
     * Calculate the route from the start to the end.
     * 
     * @param	{object} start The latlng from the start point
     * @param	{object} end   The latlng from the end point
     * @returns {void}
     */
    function calcRoute(start, end) {
        var legs, len, step, index, direction, i, j, distanceUnit, directionOffset,
                directionStops = "",
                request = {};

        if (gssiSettings.distanceUnit == "km") {
            distanceUnit = 'METRIC';
        } else {
            distanceUnit = 'IMPERIAL';
        }

        request = {
            origin: start,
            destination: end,
            travelMode: gssiSettings.directionsTravelMode,
            unitSystem: google.maps.UnitSystem[ distanceUnit ]
        };

        directionsService.route(request, function (response, status) {
            if (status == google.maps.DirectionsStatus.OK) {
                directionsDisplay.setMap(map);
                directionsDisplay.setDirections(response);

                if (response.routes.length > 0) {
                    direction = response.routes[0];

                    // Loop over the legs and steps of the directions.
                    for (i = 0; i < direction.legs.length; i++) {
                        legs = direction.legs[i];

                        for (j = 0, len = legs.steps.length; j < len; j++) {
                            step = legs.steps[j];
                            index = j + 1;
                            directionStops = directionStops + "<li><div class='gssi-direction-index'>" + index + "</div><div class='gssi-direction-txt'>" + step.instructions + "</div><div class='gssi-direction-distance'>" + step.distance.text + "</div></li>";
                        }
                    }

                    $("#gssi-direction-details ul").append(directionStops).before("<div class='gssi-direction-before'><a class='gssi-back' id='gssi-direction-start' href='#'>" + gssiLocationLabels.back + "</a><div><span class='gssi-total-distance'>" + direction.legs[0].distance.text + "</span> - <span class='gssi-total-durations'>" + direction.legs[0].duration.text + "</span></div></div>").after("<p class='gssi-direction-after'>" + response.routes[0].copyrights + "</p>");
                    $("#gssi-direction-details").show();

                    // Remove all single markers from the map.
                    for (i = 0, len = markersArray.length; i < len; i++) {
                        markersArray[i].setMap(null);
                    }

                    // Remove the marker clusters from the map.
                    if (markerClusterer) {
                        markerClusterer.clearMarkers();
                    }

                    // Remove the start marker from the map.
                    if ((typeof (startMarkerData) !== "undefined") && (startMarkerData !== "")) {
                        startMarkerData.setMap(null);
                    }

                    $("#gssi-locations").hide();

                    // Make sure the start of the route directions are visible if the location listings are shown below the map.				
                    if (gssiSettings.templateId == 1) {
                        directionOffset = $("#gssi-location-gmap").offset();
                        $(window).scrollTop(directionOffset.top);
                    }
                }
            } else {
                directionErrors(status);
            }
        });
    }

    /**
     * Geocode the user input.
     * 
     * @param	{object} infoWindow The infoWindow object
     * @returns {void}
     */
    function codeAddress(infoWindow) {
        var request = {
            'address': $('#custom_form').attr('data-address')
        };
        if($("#gssi-search-input").val()){
            request = {
                'address': $("#gssi-search-input").val()
            };
        }
        var latLng;
        // Check if we need to set the geocode component restrictions.
        if (typeof gssiSettings.geocodeComponents !== "undefined" && !$.isEmptyObject(gssiSettings.geocodeComponents)) {
            request.componentRestrictions = gssiSettings.geocodeComponents;
        }

        geocoder.geocode(request, function (response, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                latLng = response[0].geometry.location;

                prepareStoreSearch(latLng, infoWindow);
            } else {
                geocodeErrors(status);
            }
        });
    }

    /**
     * Prepare a new location search.
     * 
     * @param	{object} latLng
     * @param	{object} infoWindow The infoWindow object.
     * @returns {void}
     */
    function prepareStoreSearch(latLng, infoWindow) {
        var autoLoad = false, onSearch = 1;

        // Add a new start marker.
        addMarker(latLng, 0, '', true, infoWindow);

        // Try to find locations that match the radius, location criteria.
        findStoreLocations(latLng, resetMap, autoLoad, infoWindow, onSearch);
        firstZoom = false;
    }

    /**
     * Geocode the user input and set the returned zipcode in the input field.
     *
     * @param	{object} latLng The coordinates of the location that should be reverse geocoded
     * @returns {void}
     */
    function reverseGeocode(latLng) {
        var zipCode;

        geocoder.geocode({'latLng': latLng}, function (response, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                zipCode = filterApiResponse(response);

                if (zipCode !== "") {
                    $("#gssi-search-input").val(zipCode);
                }
            } else {
                geocodeErrors(status);
            }
        });
    }

    /**
     * Filter out the zipcode from the response.
     *
     * @param	{object} response The complete Google API response
     * @returns {string} zipcode  The zipcode
     */
    function filterApiResponse(response) {
        var zipcode, responseType, i,
                addressLength = response[0].address_components.length;

        // Loop over the API response.
        for (i = 0; i < addressLength; i++) {
            responseType = response[0].address_components[i].types;

            // filter out the postal code.
            if ((/^postal_code$/.test(responseType)) || (/^postal_code,postal_code_prefix$/.test(responseType))) {
                zipcode = response[0].address_components[i].long_name;
            }
        }

        return zipcode;
    }

    /**
     * Call the function to make the ajax request to load the location locations. 
     * 
     * If we need to show the driving directions on maps.google.com itself, 
     * we first need to geocode the start latlng into a formatted address.
     * 
     * @param	{object}  startLatLng The latlng used as the starting point
     * @param	{boolean} resetMap    Whether we should reset the map or not
     * @param	{string}  autoLoad    Check if we need to autoload all the locations
     * @param	{object}  infoWindow  The infoWindow object
     * @returns {void}
     */
    function findStoreLocations(startLatLng, resetMap, autoLoad, infoWindow, onSearch) {

        // Check if we need to open a new window and show the route on the Google Maps site itself.
        if (gssiSettings.directionRedirect == 1) {
            findFormattedAddress(startLatLng, function () {
                makeAjaxRequest(startLatLng, resetMap, autoLoad, infoWindow, onSearch);
            });
        } else {
            makeAjaxRequest(startLatLng, resetMap, autoLoad, infoWindow, onSearch);
        }
    }

    /**
     * Convert the latlng into a formatted address.
     * 
     * @param	{object} latLng The latlng to geocode
     * @param	{callback} callback
     * @returns {void}
     */
    function findFormattedAddress(latLng, callback) {
        geocoder.geocode({'latLng': latLng}, function (response, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                startAddress = response[0].formatted_address;
                callback();
            } else {
                geocodeErrors(status);
            }
        });
    }

    /**
     * Make the AJAX request to load the location data.
     * 
     * @param	{object}  startLatLng The latlng used as the starting point
     * @param	{boolean} resetMap    Whether we should reset the map or not
     * @param	{string}  autoLoad    Check if we need to autoload all the locations
     * @param	{object}  infoWindow  The infoWindow object
     * @returns {void}
     */
    function makeAjaxRequest(startLatLng, resetMap, autoLoad, infoWindow, onSearch) {
        var latLng, noResultsMsg, ajaxData,
                locationData = "",
                draggable = false,
                template = $("#gssi-listing-template").html(),
                $locationList = $("#gssi-locations ul"),
                preloader = gssiSettings.url + "includes/frontend/img/ajax-loader.gif";

        ajaxData = collectAjaxData(startLatLng, resetMap, autoLoad, onSearch);

        // Add the preloader.
        $locationList.empty().append("<li class='gssi-preloader'><img src='" + preloader + "'/>" + gssiLocationLabels.preloader + "</li>");

        $("#gssi-wrap").removeClass("gssi-no-results");

        $.get(gssiSettings.ajaxurl, ajaxData, function (response) {
            // Remove the preloaders and no results msg.
            $(".gssi-preloader, .no-results").remove();

            if (response.length > 0) {

                // Loop over the returned locations.
                $.each(response, function (index) {
                    _.extend(response[index], templateHelpers);

                    // Add the location maker to the map.
                    latLng = new google.maps.LatLng(response[index].lat, response[index].lng);
                    addMarker(latLng, response[index].id, response[index], draggable, infoWindow);

                    // Create the HTML output with help from underscore js.
                    locationData = locationData + _.template(template)(response[index]);
                });

                $("#gssi-result-list").off("click", ".gssi-directions");

                // Remove the old search results.
                $locationList.empty();

                // Add the html for the location listing to the <ul>.
                $locationList.append(locationData);
                makeMyRicky();

                $("#gssi-result-list").on("click", ".gssi-directions", function () {

                    // Check if we need to render the direction on the map.
                    if (gssiSettings.directionRedirect != 1) {
                        renderDirections($(this));

                        return false;
                    }
                });

                // Do we need to create a marker cluster?
                checkMarkerClusters();

                $("#gssi-result-list p:empty").remove();
            } else {
                addMarker(startLatLng, 0, '', true, infoWindow);

                noResultsMsg = getNoResultsMsg();

                $("#gssi-wrap").addClass("gssi-no-results");

                $locationList.html("<li class='gssi-no-results-msg'>" + noResultsMsg + "</li>");
            }

            /*
             * Do we need to adjust the zoom level so that all the markers fit in the viewport,
             * or just center the map on the start marker.
             */
            if (gssiSettings.runFitBounds == 1) {
                fitBounds();
            } else {
                map.setZoom(Number(gssiSettings.zoomLevel));
                map.setCenter(markersArray[0].position);
            }

            /* 
             * Store the default zoom and latlng values the first time 
             * all the locations are added to the map.
             * 
             * This way when a user clicks the reset button we can check if the 
             * zoom/latlng values have changed, and if they have, then we know we 
             * need to reload the map.
             */
            if (gssiSettings.resetMap == 1) {
                if ($.isEmptyObject(mapDefaults)) {
                    google.maps.event.addListenerOnce(map, "tilesloaded", function () {
                        mapDefaults = {
                            centerLatlng: map.getCenter(),
                            zoomLevel: map.getZoom()
                        };

                        /* 
                         * Because the reset icon exists, we need to adjust 
                         * the styling of the direction icon. 
                         */
                        $("#gssi-map-controls").addClass("gssi-reset-exists");

                        /* 
                         * The reset initialy is set to hidden to prevent 
                         * users from clicking it before the map is loaded. 
                         */
                        $(".gssi-icon-reset, #gssi-reset-map").show();
                    });
                }

                $(".gssi-icon-reset").removeClass("gssi-in-progress");
            }
        });

        // Move the mousecursor to the location search field if the focus option is enabled.
        if (gssiSettings.mouseFocus == 1 && !checkMobileUserAgent()) {
            $("#gssi-search-input").focus();
        }
    }

    /**
     * Collect the data we need to include in the AJAX request.
     * 
     * @param	{object}  startLatLng The latlng used as the starting point
     * @param	{boolean} resetMap    Whether we should reset the map or not
     * @param	{string}  autoLoad    Check if we need to autoload all the locations
     * @returns {object}  ajaxData	  The collected data.
     */
    function collectAjaxData(startLatLng, resetMap, autoLoad, onSearch) {
        var maxResult, radius, customDropdownName, customDropdownValue,
                customCheckboxName,
                categoryId = "",
                isMobile = $("#gssi-wrap").hasClass("gssi-mobile"),
                defaultFilters = $("#gssi-wrap").hasClass("gssi-default-filters"),
                ajaxData = {
                    action: "location_search",
                    lat: startLatLng.lat(),
                    lng: startLatLng.lng()
                };
        if (onSearch) {
            ajaxData.on_search = 1;
        }

        /* 
         * If we reset the map we use the default dropdown values instead of the selected values. 
         * Otherwise we first make sure the filter val is valid before including the radius / max_results param
         */
        if (resetMap) {
            ajaxData.max_results = gssiSettings.maxResults;
            ajaxData.search_radius = gssiSettings.searchRadius;
        } else {
            if (isMobile || defaultFilters) {
                maxResult = parseInt($("#gssi-results .gssi-dropdown").val());
                radius = parseInt($("#gssi-radius .gssi-dropdown").val());
            } else {
                maxResult = parseInt($("#gssi-results .gssi-selected-item").attr("data-value"));
                radius = parseInt($("#gssi-radius .gssi-selected-item").attr("data-value"));
            }

            // If the max results or radius filter values are NaN, then we use the default value.
            if (isNaN(maxResult)) {
                ajaxData.max_results = gssiSettings.maxResults;
            } else {
                ajaxData.max_results = maxResult;
            }

            if (isNaN(radius)) {
                ajaxData.search_radius = gssiSettings.searchRadius;
            } else {
                ajaxData.search_radius = radius;
            }

            /* 
             * If category ids are set through the gssi shortcode, then we always need to include them.
             * Otherwise check if the category dropdown exist, or if the checkboxes are used.
             */
            if (typeof gssiSettings.categoryIds !== "undefined") {
                ajaxData.filter = gssiSettings.categoryIds;
            } else if ($("#gssi-category").length > 0) {
                if (isMobile || defaultFilters) {
                    categoryId = parseInt($("#gssi-category .gssi-dropdown").val());
                } else {
                    categoryId = parseInt($("#gssi-category .gssi-selected-item").attr("data-value"));
                }

                if ((!isNaN(categoryId) && (categoryId !== 0))) {
                    ajaxData.filter = categoryId;
                }
            } else if ($("#gssi-checkbox-filter").length > 0) {
                if ($("#gssi-checkbox-filter input:checked").length > 0) {
                    ajaxData.filter = getCheckboxIds();
                }
            }

            // Include values from custom dropdowns.
            if ($(".gssi-custom-dropdown").length > 0) {
                $(".gssi-custom-dropdown").each(function (index) {
                    customDropdownName = '';
                    customDropdownValue = '';

                    if (isMobile || defaultFilters) {
                        customDropdownName = $(this).attr("name");
                        customDropdownValue = $(this).val();
                    } else {
                        customDropdownName = $(this).attr("name");
                        customDropdownValue = $(this).next(".gssi-selected-item").attr("data-value");
                    }

                    if (customDropdownName && customDropdownValue) {
                        ajaxData[customDropdownName] = customDropdownValue;
                    }
                });
            }

            // Include values from custom checkboxes
            if ($(".gssi-custom-checkboxes").length > 0) {
                $(".gssi-custom-checkboxes").each(function (index) {
                    customCheckboxName = $(this).attr("data-name");

                    if (customCheckboxName) {
                        ajaxData[customCheckboxName] = getCustomCheckboxValue(customCheckboxName);
                    }
                });
            }
        }

        /*
         * If the autoload option is enabled, then we need to check if the included latlng 
         * is based on a geolocation attempt before including the autoload param.
         * 
         * Because if both the geolocation and autoload options are enabled, 
         * and the geolocation attempt was successful, then we need to to include
         * the skip_cache param. 
         * 
         * This makes sure the results don't come from an older transient based on the 
         * start location from the settings page, instead of the users actual location. 
         */
        if (autoLoad == 1) {
            if (typeof geolocationLatlng !== "undefined") {
                ajaxData.skip_cache = 1;
            } else {
                ajaxData.autoload = 1;

                /* 
                 * If the user set the 'category' attr on the gssi shortcode, then include the cat ids 
                 * to make sure only locations from the set categories are loaded on autoload.
                 */
                if (typeof gssiSettings.categoryIds !== "undefined") {
                    ajaxData.filter = gssiSettings.categoryIds;
                }
            }
        }

        // If the collection of statistics is enabled, then we include the searched value.
        if (typeof gssiSettings.collectStatistics !== "undefined" && autoLoad == 0) {
            ajaxData.search = $("#gssi-search-input").val();
        }

        return ajaxData;
    }

    /**
     * Get custom checkbox values by data-name group.
     *
     * If multiple selection are made, then the returned
     * values are comma separated
     *
     * @param  {string} customCheckboxName The data-name value of the custom checkbox
     * @return {string} customValue		   The collected checkbox values separated by a comma
     */
    function getCustomCheckboxValue(customCheckboxName) {
        var dataName = $("[data-name=" + customCheckboxName + "]"),
                customValue = [];

        $(dataName).find("input:checked").each(function (index) {
            customValue.push($(this).val());
        });

        return customValue.join();
    }

    /**
     * Check which no results msg we need to show. 
     * 
     * Either the default txt or a longer custom msg.
     * 
     * @return string noResults The no results msg to show.
     */
    function getNoResultsMsg() {
        var noResults;

        if (typeof gssiSettings.noResults !== "undefined" && gssiSettings.noResults !== "") {
            noResults = gssiSettings.noResults;
        } else {
            noResults = gssiLocationLabels.noResults;
        }

        return noResults;
    }

    /**
     * Collect the ids of the checked checkboxes.
     * 
     * @return string catIds The cat ids from the checkboxes.
     */
    function getCheckboxIds() {
        var catIds = $("#gssi-checkbox-filter input:checked").map(function () {
            return $(this).val();
        });

        catIds = catIds.get();
        catIds = catIds.join(',');

        return catIds;
    }

    /**
     * Check if cluster markers are enabled.
     * If so, init the marker clustering with the 
     * correct gridsize and max zoom.
     * 
     * @return {void}
     */
    function checkMarkerClusters() {
        if (gssiSettings.markerClusters == 1) {
            var markers, markersArrayNoStart,
                    clusterZoom = Number(gssiSettings.clusterZoom),
                    clusterSize = Number(gssiSettings.clusterSize);

            if (isNaN(clusterZoom)) {
                clusterZoom = "";
            }

            if (isNaN(clusterSize)) {
                clusterSize = "";
            }

            /*
             * Remove the start location marker from the cluster so the location
             * count represents the actual returned locations, and not +1 for the start location.
             */
            if (typeof gssiSettings.excludeStartFromCluster !== "undefined" && gssiSettings.excludeStartFromCluster == 1) {
                markersArrayNoStart = markersArray.slice(0);
                markersArrayNoStart.splice(0, 1);
            }

            markers = (typeof markersArrayNoStart === "undefined") ? markersArray : markersArrayNoStart;

            markerClusterer = new MarkerClusterer(map, markers, {
                gridSize: clusterSize,
                maxZoom: clusterZoom
            });
        }
    }

    /**
     * Add a new marker to the map based on the provided location (latlng).
     * 
     * @param  {object}  latLng		    The coordinates
     * @param  {number}  locationId		The location id
     * @param  {object}  infoWindowData The data we need to show in the info window
     * @param  {boolean} draggable      Should the marker be draggable
     * @param  {object}  infoWindow     The infoWindow object
     * @return {void}
     */
    function addMarker(latLng, locationId, infoWindowData, draggable, infoWindow) {
        var url, mapIcon, marker,
                keepStartMarker = true;

        if (locationId === 0) {
            infoWindowData = {
                location: gssiLocationLabels.startPoint
            };

            url = markerSettings.url + gssiSettings.startMarker;
        } else if (typeof infoWindowData.alternateMarkerUrl !== "undefined" && infoWindowData.alternateMarkerUrl) {
            url = infoWindowData.alternateMarkerUrl;
        } else if (typeof infoWindowData.categoryMarkerUrl !== "undefined" && infoWindowData.categoryMarkerUrl) {
            url = infoWindowData.categoryMarkerUrl;
        } else {
            url = gssiSettings.locationMarker;
        }

        mapIcon = {
            url: url,
            //scaledSize: new google.maps.Size(Number(markerSettings.scaledSize[0]), Number(markerSettings.scaledSize[1])), //retina format
            origin: new google.maps.Point(Number(markerSettings.origin[0]), Number(markerSettings.origin[1])),
            anchor: new google.maps.Point(Number(markerSettings.anchor[0]), Number(markerSettings.anchor[1]))
        };

        marker = new google.maps.Marker({
            position: latLng,
            map: map,
            optimized: false, //fixes markers flashing while bouncing
            title: decodeHtmlEntity(infoWindowData.location),
            draggable: draggable,
            locationId: locationId,
            icon: mapIcon
        });

        // Store the marker for later use.
        markersArray.push(marker);

        google.maps.event.addListener(marker, "click", (function (currentMap) {
            return function () {

                // The start marker will have a location id of 0, all others won't.
                if (locationId != 0) {

                    // Check if streetview is available at the clicked location.
                    if (typeof gssiSettings.markerStreetView !== "undefined" && gssiSettings.markerStreetView == 1) {
                        checkStreetViewStatus(latLng, function () {
                            setInfoWindowContent(marker, createInfoWindowHtml(infoWindowData), infoWindow, currentMap);
                        });
                    } else {
                        setInfoWindowContent(marker, createInfoWindowHtml(infoWindowData), infoWindow, currentMap);
                    }
                    currentMap.setCenter(marker.getPosition());
                } else {
                    setInfoWindowContent(marker, gssiLocationLabels.startPoint, infoWindow, currentMap);
                }

                google.maps.event.clearListeners(infoWindow, "domready");

                google.maps.event.addListener(infoWindow, "domready", function () {
                    infoWindowClickActions(marker, currentMap);
                    checkMaxZoomLevel();
                    makeMyRicky();
                });
            };
        }(map)));

        // Only the start marker will be draggable.
        if (draggable) {
            google.maps.event.addListener(marker, "dragend", function (event) {
                deleteOverlays(keepStartMarker);
                map.setCenter(event.latLng);
                reverseGeocode(event.latLng);
                findStoreLocations(event.latLng, resetMap, autoLoad = false, infoWindow);
            });
        }
    }

    /**
     * Decode HTML entities.
     * 
     * @link	https://gist.github.com/CatTail/4174511
     * @param	{string} str The string to decode.
     * @returns {string} The string with the decoded HTML entities.
     */
    function decodeHtmlEntity(str) {
        if (str) {
            return str.replace(/&#(\d+);/g, function (match, dec) {
                return String.fromCharCode(dec);
            });
        }
    }
    ;

// Check if we are using both the infobox for the info windows and have marker clusters.
    if (typeof gssiSettings.infoWindowStyle !== "undefined" && gssiSettings.infoWindowStyle == "infobox" && gssiSettings.markerClusters == 1) {
        var clusters, clusterLen, markerLen, i, j;

        /* 
         * We need to listen to both zoom_changed and idle. 
         * 
         * If the zoom level changes, then the marker clusters either merges nearby 
         * markers, or changes into individual markers. Which is the moment we 
         * either show or hide the opened info window.
         * 
         * "idle" is necessary to make sure the getClusters() is up 
         * to date with the correct cluster data.
         */
        google.maps.event.addListener(map, "zoom_changed", function () {
            google.maps.event.addListenerOnce(map, "idle", function () {

                if (typeof markerClusterer !== "undefined") {
                    clusters = markerClusterer.clusters_;

                    if (clusters.length) {
                        for (i = 0, clusterLen = clusters.length; i < clusterLen; i++) {
                            for (j = 0, markerLen = clusters[i].markers_.length; j < markerLen; j++) {

                                /* 
                                 * Match the locationId from the cluster marker with the 
                                 * marker id that was set when the info window was opened 
                                 */
                                if (clusters[i].markers_[j].locationId == activeWindowMarkerId) {

                                    /* 
                                     * If there is a visible info window, but the markers_[j].map is null ( hidden ) 
                                     * it means the info window belongs to a marker that is part of a marker cluster.
                                     * 
                                     * If that is the case then we hide the info window ( the individual marker isn't visible ).
                                     *
                                     * The default info window script handles this automatically, but the
                                     * infobox library in combination with the marker clusters doesn't.
                                     */
                                    if (infoWindow.getVisible() && clusters[i].markers_[j].map === null) {
                                        infoWindow.setVisible(false);
                                    } else if (!infoWindow.getVisible() && clusters[i].markers_[j].map !== null) {
                                        infoWindow.setVisible(true);
                                    }

                                    break;
                                }
                            }
                        }
                    }
                }
            });
        });
    }

    /**
     * Set the correct info window content for the marker.
     * 
     * @param	{object} marker			   Marker data
     * @param	{string} infoWindowContent The infoWindow content
     * @param	{object} infoWindow		   The infoWindow object
     * @param	{object} currentMap		   The map object
     * @returns {void}
     */
    function setInfoWindowContent(marker, infoWindowContent, infoWindow, currentMap) {
        openInfoWindow.length = 0;

        infoWindow.setContent(infoWindowContent);
        infoWindow.open(currentMap, marker);

        openInfoWindow.push(infoWindow);

        /* 
         * Store the marker id if both the marker clusters and the infobox are enabled.
         * 
         * With the normal info window script the info window is automatically closed 
         * once a user zooms out, and the marker clusters are enabled, 
         * but this doesn't happen with the infobox library. 
         * 
         * So we need to show/hide it manually when the user zooms out, 
         * and for this to work we need to know which marker to target. 
         */
        if (typeof gssiSettings.infoWindowStyle !== "undefined" && gssiSettings.infoWindowStyle == "infobox" && gssiSettings.markerClusters == 1) {
            activeWindowMarkerId = marker.locationId;
            infoWindow.setVisible(true);
        }
    }

    /**
     * Handle clicks for the different info window actions like, 
     * direction, streetview and zoom here.
     * 
     * @param	{object} marker		Holds the marker data
     * @param	{object} currentMap	The map object
     * @returns {void}
     */
    function infoWindowClickActions(marker, currentMap) {
        $(".gssi-info-actions a").on("click", function (e) {
            var maxZoom = Number(gssiSettings.autoZoomLevel);

            e.stopImmediatePropagation();

            if ($(this).hasClass("gssi-directions")) {

                /* 
                 * Check if we need to show the direction on the map
                 * or send the users to maps.google.com 
                 */
                if (gssiSettings.directionRedirect == 1) {
                    return true;
                } else {
                    renderDirections($(this));
                }
            } else if ($(this).hasClass("gssi-streetview")) {
                activateStreetView(marker, currentMap);
            } else if ($(this).hasClass("gssi-zoom-here")) {
                currentMap.setCenter(marker.getPosition());
                currentMap.setZoom(maxZoom);
            }

            return false;
        });
    }

    /**
     * Check if have reached the max auto zoom level.
     * 
     * If so we hide the 'Zoom here' text in the info window, 
     * otherwise we show it.
     * 
     * @returns {void}
     */
    function checkMaxZoomLevel() {
        var zoomLevel = map.getZoom();

        if (zoomLevel >= gssiSettings.autoZoomLevel) {
            $(".gssi-zoom-here").hide();
        } else {
            $(".gssi-zoom-here").show();
        }
    }

    /**
     * Activate streetview for the clicked location.
     * 
     * @param	{object} marker	    The current marker
     * @param	{object} currentMap The map object
     * @returns {void}
     */
    function activateStreetView(marker, currentMap) {
        var panorama = currentMap.getStreetView();
        panorama.setPosition(marker.getPosition());
        panorama.setVisible(true);

        $("#gssi-map-controls").hide();

        StreetViewListener(panorama, currentMap);
    }

    /**
     * Listen for changes in the streetview visibility.
     * 
     * Sometimes the infowindow offset is incorrect after switching back from streetview.
     * We fix this by zooming in and out. If someone has a better fix, then let me know at
     * info at tijmensmit.com
     * 
     * @param	{object} panorama   The streetview object
     * @param	{object} currentMap The map object
     * @returns {void}
     */
    function StreetViewListener(panorama, currentMap) {
        google.maps.event.addListener(panorama, "visible_changed", function () {
            if (!panorama.getVisible()) {
                var currentZoomLevel = currentMap.getZoom();

                $("#gssi-map-controls").show();

                currentMap.setZoom(currentZoomLevel - 1);
                currentMap.setZoom(currentZoomLevel);
            }
        });
    }

    /**
     * Check the streetview status.
     * 
     * Make sure that a streetview exists for 
     * the latlng for the open info window.
     * 
     * @param	{object}   latLng The latlng coordinates
     * @param	{callback} callback
     * @returns {void}
     */
    function checkStreetViewStatus(latLng, callback) {
        var service = new google.maps.StreetViewService();

        service.getPanoramaByLocation(latLng, 50, function (result, status) {
            streetViewAvailable = (status == google.maps.StreetViewStatus.OK) ? true : false;
            callback();
        });
    }

    /**
     * Create the HTML template used in the info windows on the map.
     * 
     * @param	{object} infoWindowData	The data that is shown in the info window (address, url, phone etc)
     * @returns {string} windowContent	The HTML content that is placed in the info window
     */
    function createInfoWindowHtml(infoWindowData) {
        var windowContent, template;

        if ($("#gssi-base-gmap_0").length) {
            template = $("#gssi-cpt-info-window-template").html();
        } else {
            template = $("#gssi-info-window-template").html();
        }

        windowContent = _.template(template)(infoWindowData); //see http://underscorejs.org/#template

        return windowContent;
    }

    /**
     * Zoom the map so that all markers fit in the window.
     * 
     * @returns {void}
     */
    function fitBounds() {
        var i, markerLen,
                maxZoom = Number(gssiSettings.autoZoomLevel),
                bounds = new google.maps.LatLngBounds();

        // Make sure we don't zoom to far.
        attachBoundsChangedListener(map, maxZoom);

        for (i = 0, markerLen = markersArray.length; i < markerLen; i++) {
            bounds.extend(markersArray[i].position);
        }

        map.fitBounds(bounds);
    }

    /**
     * Remove all existing markers from the map.
     * 
     * @param	{boolean} keepStartMarker Whether or not to keep the start marker while removing all the other markers from the map
     * @returns {void}
     */
    function deleteOverlays(keepStartMarker) {
        var markerLen, i;

        directionsDisplay.setMap(null);

        // Remove all the markers from the map, and empty the array.
        if (markersArray) {
            for (i = 0, markerLen = markersArray.length; i < markerLen; i++) {

                // Check if we need to keep the start marker, or remove everything.
                if (keepStartMarker) {
                    if (markersArray[i].draggable != true) {
                        markersArray[i].setMap(null);
                    } else {
                        startMarkerData = markersArray[i];
                    }
                } else {
                    markersArray[i].setMap(null);
                }
            }

            markersArray.length = 0;
        }

        // If marker clusters exist, remove them from the map.
        if (markerClusterer) {
            markerClusterer.clearMarkers();
        }
    }

    /**
     * Handle the geocode errors.
     * 
     * @param   {string} status Contains the error code
     * @returns {void}
     */
    function geocodeErrors(status) {
        var msg;

        switch (status) {
            case "ZERO_RESULTS":
                msg = gssiLocationLabels.noResults;
                break;
            case "OVER_QUERY_LIMIT":
                msg = gssiLocationLabels.queryLimit;
                break;
            default:
                msg = gssiLocationLabels.generalError;
                break;
        }

        alert(msg);
    }

    /**
     * Handle the driving direction errors.
     * 
     * @param   {string} status Contains the error code
     * @returns {void}
     */
    function directionErrors(status) {
        var msg;

        switch (status) {
            case "NOT_FOUND":
            case "ZERO_RESULTS":
                msg = gssiLocationLabels.noDirectionsFound;
                break;
            case "OVER_QUERY_LIMIT":
                msg = gssiLocationLabels.queryLimit;
                break;
            default:
                msg = gssiLocationLabels.generalError;
                break;
        }

        alert(msg);
    }

    $("#gssi-locations").on("click", ".gssi-location-details", function () {
        var i, len,
                $parentLi = $(this).parents("li"),
                locationId = $parentLi.data("location-id");

        // Check if we should show the 'more info' details.
        if (gssiSettings.moreInfoLocation == "info window") {
            for (i = 0, len = markersArray.length; i < len; i++) {
                if (markersArray[i].locationId == locationId) {
                    google.maps.event.trigger(markersArray[i], "click");
                }
            }
        } else {

            // Check if we should set the 'more info' item to active or not.
            if ($parentLi.find(".gssi-more-info-listings").is(":visible")) {
                $(this).removeClass("gssi-active-details");
            } else {
                $(this).addClass("gssi-active-details");
            }

            $parentLi.siblings().find(".gssi-location-details").removeClass("gssi-active-details");
            $parentLi.siblings().find(".gssi-more-info-listings").hide();
            $parentLi.find(".gssi-more-info-listings").toggle();
        }

        /* 
         * If we show the location listings under the map, we do want to jump to the 
         * top of the map to focus on the opened infowindow 
         */
        if (gssiSettings.templateId != "default" || gssiSettings.moreInfoLocation == "location listings") {
            return false;
        }
    });

    /**
     * Create the styled dropdown filters.
     * 
     * Inspired by https://github.com/patrickkunka/easydropdown
     * 
     * @returns {void}
     */
    function createDropdowns() {
        var maxDropdownHeight = Number(gssiSettings.maxDropdownHeight);

        $(".gssi-dropdown").each(function (index) {
            var active, maxHeight, $this = $(this);

            $this.$dropdownWrap = $this.wrap("<div class='gssi-dropdown'></div>").parent();
            $this.$selectedVal = $this.val();
            $this.$dropdownElem = $("<div><ul/></div>").appendTo($this.$dropdownWrap);
            $this.$dropdown = $this.$dropdownElem.find("ul");
            $this.$options = $this.$dropdownWrap.find("option");

            // Hide the original <select> and remove the css class.
            $this.hide().removeClass("gssi-dropdown");

            // Loop over the options from the <select> and move them to a <li> instead.
            $.each($this.$options, function () {
                if ($(this).val() == $this.$selectedVal) {
                    active = 'class="gssi-selected-dropdown"';
                } else {
                    active = '';
                }

                $this.$dropdown.append("<li data-value=" + $(this).val() + " " + active + ">" + $(this).text() + "</li>");
            });

            $this.$dropdownElem.before("<span data-value=" + $this.find(":selected").val() + " class='gssi-selected-item'>" + $this.find(":selected").text() + "</span>");
            $this.$dropdownItem = $this.$dropdownElem.find("li");

            // Listen for clicks on the 'gssi-dropdown' div.
            $this.$dropdownWrap.on("click", function (e) {

                // Check if we only need to close the current open dropdown.
                if ($(this).hasClass("gssi-active")) {
                    $(this).removeClass("gssi-active");

                    return;
                }

                closeAllDropdowns();

                $(this).toggleClass("gssi-active");
                maxHeight = 0;

                // Either calculate the correct height for the <ul>, or set it to 0 to hide it.
                if ($(this).hasClass("gssi-active")) {
                    $this.$dropdownItem.each(function (index) {
                        maxHeight += $(this).outerHeight();
                    });

                    $this.$dropdownElem.css("height", maxHeight + 2 + "px");
                } else {
                    $this.$dropdownElem.css("height", 0);
                }

                // Check if we need to enable the scrollbar in the dropdown filter.
                if (maxHeight > maxDropdownHeight) {
                    $(this).addClass("gssi-scroll-required");
                    $this.$dropdownElem.css("height", (maxDropdownHeight) + "px");
                }

                e.stopPropagation();
            });

            // Listen for clicks on the individual dropdown items.
            $this.$dropdownItem.on("click", function (e) {

                // Set the correct value as the selected item.
                $this.$dropdownWrap.find($(".gssi-selected-item")).html($(this).text()).attr("data-value", $(this).attr("data-value"));

                // Apply the class to the correct item to make it bold.
                $this.$dropdownItem.removeClass("gssi-selected-dropdown");
                $(this).addClass("gssi-selected-dropdown");

                closeAllDropdowns();

                e.stopPropagation();
            });
        });

        $(document).click(function () {
            closeAllDropdowns();
        });
    }

    /**
     * Close all the dropdowns.
     * 
     * @returns {void}
     */
    function closeAllDropdowns() {
        $(".gssi-dropdown").removeClass("gssi-active");
        $(".gssi-dropdown div").css("height", 0);
    }

    /**
     * Check if the user submitted a search through a search widget.
     *
     * @returns {void}
     */
    function checkWidgetSubmit() {
        if ($(".gssi-search").hasClass("gssi-widget")) {
            $("#gssi-search-btn").trigger("click");
            $(".gssi-search").removeClass("gssi-widget");
        }
    }

    /**
     * Check if we need to run the code to prevent Google Maps
     * from showing up grey when placed inside one or more tabs.
     *
     * @return {void}
     */
    function maybeApplyTabFix() {
        var mapNumber, len;

        if (_.isArray(gssiSettings.mapTabAnchor)) {
            for (mapNumber = 0, len = mapsArray.length; mapNumber < len; mapNumber++) {
                fixGreyTabMap(mapsArray[mapNumber], gssiSettings.mapTabAnchor[mapNumber], mapNumber);
            }
        } else if ($("a[href='#" + gssiSettings.mapTabAnchor + "']").length) {
            fixGreyTabMap(map, gssiSettings.mapTabAnchor);
        }
    }

    /**
     * This code prevents the map from showing a large grey area if
     * the location is placed in a tab, and that tab is actived.
     *
     * The default map anchor is set to 'gssi-map-tab', but you can
     * change this with the 'gssi_map_tab_anchor' filter.
     *
     * Note: If the "Attempt to auto-locate the user" option is enabled,
     * and the user quickly switches to the location tab, before the
     * Geolocation timeout is reached, then the map is sometimes centered in the ocean.
     *
     * I haven't really figured out why this happens. The only option to fix this
     * is to simply disable the "Attempt to auto-locate the user" option if
     * you use the location in a tab.
     *
     * @param   {object} currentMap	  The map object from the current map
     * @param   {string} mapTabAnchor The anchor used in the tab that holds the map
     * @param 	(int) 	 mapNumber    Map number
     * @link    http://stackoverflow.com/questions/9458215/google-maps-not-working-in-jquery-tabs
     * @returns {void}
     */
    function fixGreyTabMap(currentMap, mapTabAnchor, mapNumber) {
        var mapZoom, mapCenter, maxZoom, bounds, tabMap,
                returnBool = Number(gssiSettings.mapTabAnchorReturn) ? true : false,
                $gssi_tab = $("a[href='#" + mapTabAnchor + "']");

        if (typeof currentMap.maxZoom !== "undefined") {
            maxZoom = currentMap.maxZoom;
        } else {
            maxZoom = Number(gssiSettings.autoZoomLevel);
        }

        /*
         * We need to do this to prevent the map from flashing if
         * there's only a single marker on the first click on the tab.
         */
        if (typeof mapNumber !== "undefined" && mapNumber == 0) {
            $gssi_tab.addClass("gssi-fitbounds");
        }

        $gssi_tab.on("click", function () {
            setTimeout(function () {
                if (typeof currentMap.map !== "undefined") {
                    bounds = currentMap.bounds;
                    tabMap = currentMap.map;
                } else {
                    tabMap = currentMap;
                }

                mapZoom = tabMap.getZoom();
                mapCenter = tabMap.getCenter();

                google.maps.event.trigger(tabMap, "resize");

                if (!$gssi_tab.hasClass("gssi-fitbounds")) {

                    //Make sure fitBounds doesn't zoom past the max zoom level.
                    attachBoundsChangedListener(tabMap, maxZoom);

                    tabMap.setZoom(mapZoom);
                    tabMap.setCenter(mapCenter);

                    if (typeof bounds !== "undefined") {
                        tabMap.fitBounds(bounds);
                    } else {
                        fitBounds();
                    }

                    $gssi_tab.addClass("gssi-fitbounds");
                }
            }, 50);

            return returnBool;
        });
    }

    /**
     * Add the bounds_changed event listener to the map object
     * to make sure we don't zoom past the max zoom level.
     *
     * @param object The map object to attach the event listener to
     * @returns {void}
     */
    function attachBoundsChangedListener(map, maxZoom) {
        google.maps.event.addListenerOnce(map, "bounds_changed", function () {
            google.maps.event.addListenerOnce(map, "idle", function () {
                if (this.getZoom() > maxZoom) {
                    this.setZoom(maxZoom);
                }
            });
        });
    }

});