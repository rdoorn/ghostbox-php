$( document ).ready(function() {


    $( '#gallery_list' ).on({
        'click' : function(e) {
            e.preventDefault();
            e.stopPropagation();
            //showError('you pressed'+$(this).attr("action"))
            switch($(this).attr("action")) {
                case 'highlight':
                    sendEditorEvent( $(this).parent().parent().parent().parent().attr('id'), 'highlight' )
                    break;
                case 'rotateLeft':
                    sendEditorEvent( $(this).parent().parent().parent().parent().attr('id'), 'rotateLeft' )
                    break;
                case 'rotateRight':
                    sendEditorEvent( $(this).parent().parent().parent().parent().attr('id'), 'rotateRight' )
                    break;
                case 'delete':
                    if (! $(e.target).is('a')) {
                        $(this).html('Are you Sure? <a action="yes" class="red">Yes</a> / <a action="no" class="green">No</a>');
                    } else {
                        if ( $(e.target).attr('action') == 'yes' ) {
                            sendEditorEvent( $(this).parent().parent().parent().parent().attr('id'), 'delete' )
                        } else {
                            $(this).html('Delete');
                        }
                    }
                    break;
                case 'personalRating':

                    var Offset = $(this).offset();
                    var Width = $(this).width();
                    var Padding = parseInt($(this).css('padding-right'));
                    var relX = (Width+Padding)-(e.pageX - Offset.left);

                    var rating = Math.floor(relX/29)+1;
                    if (rating > 5) { rating = 5; }
                    sendEditorEvent( $(this).parent().parent().parent().parent().attr('id'), 'setRating', rating );
                    break;
                case 'addTags':
                    if (! $(e.target).is('div')) {
                        $(this).find('[contenteditable]').focus();
                    }
                case 'deleteTags':
                    if ($(e.target).is('a')) {
                        sendEditorEvent( $(this).parent().parent().parent().parent().attr('id'), 'deleteTag', $(e.target).attr('value') );
                        $(e.target).parent().remove();
                    }
                    break;
                default:
                    console.log('no action defined: '+$(this).attr("action")) 
                } 
        }
    },  '.image_editor ul li');

    // add Tags
    $( '#gallery_list' ).on({
        'click' : function(e) {
            e.preventDefault();
            e.stopPropagation();
            //console.log('clicking editable content')
            /*$(this).focus();*/
            $(this).blur();
            $(this).focus();

        },
        'focus' : function(e) {
            $(this).text('');
        },
        'blur' : function(e) {
            $(this).text('add tag...');
        },
        'keydown' : function(e) {
            // ESC=27, Enter=13
            //$(this).text( $(this).text().replace(/[^a-zA-Z0-9_ &,\.\+-]/g,'') );
            //console.log('key:'+e.which)
            if (e.which == 27) {
                $(this).trigger('blur');
            } else if (e.which == 13) {
                if ( ( $(this).text() != '') && ( $(this).text() != 'add tag...') ) {
                    sendEditorEvent( $(this).parent().parent().parent().parent().parent().attr('id'), 'addTag', $(this).text() );
                }
                //$(this).parent().parent().find("li.showTags").append('<div class="keyword">'+$(this).text()+' <a action="delete" value="'+$(this).text()+'"> x </a></div> ')
                $(this).trigger('blur');
            }
        }
    },  '.image_editor ul li [contenteditable]');
    

    // correct viewable editor when hovering the image
    $( '#gallery_list' ).on({
        'mouseover' : function(e) {
            if ( $(this).children(".image_editor").attr('type') == 'directory') {
                $(this).find("li.files").hide();
                $(this).find("li.directories").show();
            } else {
                $(this).find("li.directories").hide();
                $(this).find("li.files").show();
            }
        }
    }, 'a .frame') 


    // correct rating when entering the menu
    $( '#gallery_list' ).on({
        'mouseover' : function(e) {
            //console.log('rating and tags')

                if ($(this).find("li.personalRating").attr("preset") != 1 ) {
                    //console.log (' no rating found yet' );
                    sendEditorEvent( $(this).parent().parent().parent().attr('id'), 'getRating' )
                    $(this).find("li.personalRating").attr("preset", 1)
                }
                if ($(this).find("li.showTags").attr("preset") != 1 ) {
                    //console.log (' no tag found yet' );
                    sendEditorEvent( $(this).parent().parent().parent().attr('id'), 'getTags' )
                    $(this).find("li.showTags").attr("preset", 1)
                } 

        }
    }, '.image_editor ul') /*,  '.image_editor ul');*/
});

function sendEditorEvent( itemid, action, value ) {
        var arr = { Id: itemid, Item: action, Value: value };
        //console.log('sending itemid:'+itemid+' and action:'+action)
        $.ajax({
            url: '',
            type: 'PUT',
            data: JSON.stringify(arr),
            contentType: 'application/json; charset=utf-8',
            dataType: 'json',
            /*async: false,*/
            success: function(msg) {
                //console.log('got back:'+msg)
                if (msg.data.Message) { showMessage(msg.data.Message); }
                if (msg.data.SwapXY) { 
                    var oheight = $( '#'+itemid ).children( ".frame" ).attr("oheight");
                    $( '#'+itemid ).children( ".frame" ).attr("oheight", $( '#'+itemid ).children( ".frame" ).attr("owidth") )
                    $( '#'+itemid ).children( ".frame" ).attr("owidth", oheight )
                }
                if (msg.data.Cache) { 
                    $( '#'+itemid ).children( ".frame" ).children( "img" ).attr('src',"") // clear image
                    $( '#'+itemid ).children( ".frame" ).children( "img" ).attr('src',msg.data.Cache)
                }
                if (msg.data.Remove) { 
                    $( '#'+itemid ).remove();
                }
                if ((msg.data.Rating !== null) && (typeof msg.data.Rating !== 'undefined')) {
                    //console.log('rating is not null:'+msg.data.Rating)
                    setRating( itemid, msg.data.Rating )
                }
                if ((msg.data.Tags !== null) && (typeof msg.data.Tags !== 'undefined')) {
                    //console.log('got tags:'+msg.data.Tags)
                    setTags( itemid, msg.data.Tags )
                }
                if (msg.data.Resize) { resize_images("#gallery_list .frame"); }
            },
            error: function( jqXHR, textStatus, errorThrown ) {
                msg = jQuery.parseJSON(jqXHR.responseText);
                showError(msg.Message);
            }
        });        

}

function setRating(itemid, rating) {
    $( '#'+itemid ).find("li.personalRating").attr('preset', 1).removeClass("editrating0").removeClass("editrating1").removeClass("editrating2").removeClass("editrating3").removeClass("editrating4").removeClass("editrating5").addClass("editrating"+rating);
}


function setTags(itemid, tags) {
    //console.log('got taggs!')
    if (tags && $.isArray( tags )) {
        $( '#'+itemid ).find("li.showTags").attr("preset", 1).html('');
        $.each( tags, function( key, val ) {
             $( '#'+itemid ).find("li.showTags").append('<div class="keyword">'+val+' <a action="delete" value="'+val+'"> x </a></div> ')
        })
        //console.log(' replacing '+$( '#'+itemid ).find("div.keyword").css('background-color')+' with '+$( '#'+itemid ).children(".frame").css('background-color'));

                var currentColor = $( '#'+itemid ).children(".frame").css('background-color');
                var lastComma = currentColor.lastIndexOf(')');
                var newColor = currentColor.slice(0, lastComma - 5) + ", "+ 1 + ")";
                $( '#'+itemid ).find("div.keyword").css('background-color', newColor);
                //console.log(' replacing '+$( '#'+itemid ).find("div.keyword").css('background-color')+' with '+newColor);
        //$( '#'+itemid ).find("div.keyword").css('background-color', $( '#'+itemid ).children(".frame").css('background-color') );
    } else {
        //console.log('no tags...')
    }

}

