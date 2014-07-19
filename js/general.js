function showError(message) {
    $( '#error' ).stop().text(message).slideDown().delay( 4000 ).fadeOut(500);
}

function showMessage(message) {
    $( '#message' ).stop().text(message).slideDown().delay( 4000 ).fadeOut(500);
}

function startLoading(message) {
    $( '#loading' ).stop(true).delay(500).text(message).show();
}

function stopLoading() {
    $( '#loading' ).stop(true).fadeOut(500);
}


function myUrl() {
    // fix annoying facebook behaviour
    var href = window.location.href
    if (href.indexOf('#_=_')) {
        return href.substr(0, href.indexOf('#_=_'));
    } else {
        return href;
    }
}

function insertParam(url, key, value) {
    key = encodeURIComponent(key); value = encodeURIComponent(value);
    if (url.indexOf('?') == -1) {
        url = url + '?' + key + '=' + value;
    } else {
        var kvp = url.split('&');
        var i = kvp.length; var x; while (i--) {
            x = kvp[i].split('=');
            if (x[0] == key) {
                x[1] = value;
                kvp[i] = x.join('=');
                break;
            }
        }
        if (i < 0) { kvp[kvp.length] = [key, value].join('='); }
        url = kvp.join('&');
    }
    return url;
}


function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
}
