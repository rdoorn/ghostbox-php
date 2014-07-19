$( document ).ready(function() {


  $( document ).on({
    'click' : function(e) {
        e.preventDefault();
        $(this).blur();
        $(this).focus();
    },
    'focus' : function(e) {
        before = $(this).text();
    },
    'blur' : function(e) {
        if (before != $(this).text()) { $(this).trigger('change'); }
    },
    'keydown' : function(e) {
        // ESC=27, Enter=13
        //console.log(e.which);
        if (e.which == 27) {
            $(this).text(before);
            $(this).trigger('blur');
        } else if (e.which == 13) {
            $(this).trigger('blur');
            return false;
        }
    },
    'change' : function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $thisText = $(this).text();
        //console.log($thisText);
        var changeitem = $(this);
        var arr = { Id: $(this).parent().parent().parent().attr('id'), Item: $(this).attr('type'), Value: $(this).text() };
        $.ajax({
            url: '',
            type: 'PUT',
            data: JSON.stringify(arr),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            async: false,
            success: function(msg) {
                e.preventDefault();
                e.stopPropagation();
                //console.log(msg);
                if (msg.data['Href']) {
                    $( '#'+msg.data['Id'] ).attr('href',msg.data['Href']);
                }
            },
            error: function( jqXHR, textStatus, errorThrown ) {
//                console.log('XHR:'+jqXHR.responseText+' status:'+textStatus+' error:'+errorThrown);
                msg = jQuery.parseJSON(jqXHR.responseText);
                //console.log('message:'+msg.Message)
                showError(msg.Message);
                changeitem.text(before);
            }
        });        

    }
  }, '.mini_info [contenteditable]');


});

