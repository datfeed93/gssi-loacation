jQuery(document).ready(function ($) {
    /**
     * add event to the top navigation
     */
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (pos) {
            if (!$('.my-location-top').hasClass('has-ricky')) {
                $('.header-location-button .change-location').hide();
                getClosetStoreLocator(pos);
            }
        });
        navigator.geolocation.watchPosition(function(position) {
            
        }, function (error) { 
            if (error.code == error.PERMISSION_DENIED) {
                if (!$('.my-location-top').hasClass('has-ricky')) {
                    $('.top-choose-locations').show();
                    $('.my-location-top').hide();
                }
            }
        });
    } else {
        $('.top-choose-locations').show();
        $('.my-location-top').hide();
    }

    function getClosetStoreLocator(position) {
        var latitude = position.coords.latitude;
        var longitude = position.coords.longitude;
        $.ajax({
            type: 'POST',
            url: gssiSettings.ajaxurl,
            data: {action: 'location_closet', lat: latitude, lng: longitude},
            beforeSend: function () {
                $('.header-location').prepend($("<img class='loading'/>").attr("src", gssiSettings.url + "/includes/frontend/img/ajax-loader.gif"));
            },
            success: function (locations) {
                if (locations.length) {
                    $('.header-location-button').show();
                    $('.header-location-button a').attr('data-location-id', locations[0].id);
                    $('.header-location-link').html(locations[0].location);
                    $('.header-location-link').attr('href', locations[0].detail_url);
                }
            },
            complete: function () {
                $('.header-location').find('img.loading').remove();
            }
        });
    }

    $('.header-location-button a').click(function () {
        var locationId = $(this).attr('data-location-id');
        $.ajax({
            type: 'POST',
            url: gssiSettings.ajaxurl,
            data: {action: 'make_ricky', location_id: locationId},
            beforeSend: function () {
                $('.header-location').append($("<img class='loading'/>").attr("src", gssiSettings.url + "includes/frontend/img/ajax-loader.gif"));
            },
            success: function (result) {
                if (result) {
                    window.location.reload();
                }
            },
            complete: function () {
                $('.header-location').find('img.loading').remove();
            }
        });
    });

    // end add event to the top navigation
});