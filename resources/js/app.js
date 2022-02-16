require('./bootstrap');

window.tabApp = () => {
    return { 
        tab: window.location.hash ? window.location.hash.substring(1) : 'details',
    }
}

window.flash = message => window.dispatchEvent( new CustomEvent('flash', {detail: message}));

function poiAddHighlight(e){
    var markerr = $('.'+$(this).attr('id'));
    markerr.addClass('poiMarkerWithHighlight');
    // markerr.css("box-shadow","0px 0px 10px 3px red");
}
function poiRemoveHighlight(e){
    var markerr = $('.'+$(this).attr('id'));
    markerr.removeClass('poiMarkerWithHighlight');
    // markerr.css("box-shadow","none");
}

$(document).ready(function () {

    jQuery('.poi-list .grid').each(function(index) {
        $(this).on("mouseover", poiAddHighlight );
        $(this).on("mouseout", poiRemoveHighlight );
    });
    
    jQuery('.closePOIpan').each(function(index) {
        $(this).on("click", function(){
            $('.poiMarkerWithHighlight').removeClass('poiMarkerWithHighlight');
        });
    });

    // if get parameters exists open success registration alert
    var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
        return false;
    };
    var poiID = getUrlParameter('poiid');

    if (window.location.hash.substring(1)) {
        $('html,body').animate({
            scrollTop: $('#map').offset().top - 40
        },'slow');
    }

    if (poiID) {
        var poiToOpen = $('#poi-'+poiID);
        poiToOpen.click();
        poiToOpen.mouseover();
    }
});