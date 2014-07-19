var previous_scroll_state;

$(window).scroll(function() {
    if($(window).scrollTop() + $(window).height() == $(document).height()) {
        PreloadImages(0);
    }
 
 
     if($(window).scrollTop() + $(window).height() > Math.floor($(document).height()/1.5)) {
        if (previous_scroll_state == 'upperhalf') {
            previous_scroll_state = 'lowerhalf';
             PreloadImages(0);
        }
     } else {
         previous_scroll_state = 'upperhalf';
    }

});

var ImageEnd = 0
var Preloading = 0
function PreloadImages( fetchLength ) {
    gotPreloadUpdate = 0;

    if (Preloading == 1) { console.log('already preloading...');return; }
    Preloading = 1
    var numItems = $('.frame').length

    if (ImageEnd == 0) {
        startLoading("Loading images...");
    }
    //$('#gallery_list').append('<p>Loading...</p>');

        var url=myUrl();
        url = insertParam(url, 'start', numItems)
        if (fetchLength>0) { 
            url = insertParam(url, 'length', fetchLength)
        }
        //url = insertParam(url, 'length', 21)

        var sort = getUrlParameter('sort');
        var order = getUrlParameter('order');
        if (sort) {  url = insertParam(url, 'sort', sort) }
        if (order) {  url = insertParam(url, 'order', order) }

    //console.log('getting url:'+url)
    var jqxhr = $.getJSON( url , function( data ) {
        var items = [];
        //$("#gallery_list").append( 'hello'+data );
        // get last child object

        //$.each( data, function( key, val ) {
        var lastitem = $('#gallery_list a').last();
        var insertData = []
        var insertCount = 0
        if (data['Data'] == null) {
            ImageEnd == 1;
            stopLoading();
            return;
        }
        data['Data'].forEach(function(val) {
            //console.log(val)


            // code-here
            //var lastitem = $('a', '#gallery_list').last();


            // clone the last item, but empty the image, so it gets cleared before we start loading the new one
            var newitem = lastitem.clone().children( ".frame" ).children( "img" ).attr('src',"").parent().parent().attr('id', val['itemid'])
            //console.log(lastitem);
            //var newitem = lastitem.clone().find( "img" ).attr('src',"").parent().parent().parent().attr('id', val['itemid']).insertAfter( lastitem )

            newitem.children( ".frame" ).children( "img" ).attr('src',val['cache'][ THUMB_SIZE ])
            //newitem.find( "img" ).attr('src',val['cache'][ THUMB_SIZE ])
            // we only encode the name, as the path is already urlencoded

            if (val['directory'] == 0) {
                newitem.attr("href", '/photos/'+val['itemid']+'/_via/albums/'+val['path'])
            } else {
                newitem.attr("href", '/albums/'+val['path']+'/'+encodeURIComponent(val['name']))
            }
            /*
            newitem.children( ".frame" ).attr("oheight", val['height'])
            newitem.children( ".frame" ).attr("owidth", val['width'])
            newitem.children( ".frame" ).children( ".mini_info" ).children( ".displayname" ).text(val['displayname'])
            newitem.children( ".frame" ).children( ".mini_info" ).children( ".description" ).text(val['description'])
            */
            newitem.find( ".frame" ).attr("oheight", val['height'])
            newitem.find( ".frame" ).attr("owidth", val['width'])
            newitem.find( ".displayname" ).text(val['displayname'])
            newitem.find( ".description" ).text(val['description'])

            // fix color
            /*
            if ((typeof val['color'] != 'undefined') && val['color'] !== null && (val['color'].length == 6)) {
                col = hexToRgb( val['color'] )
                //newitem.children( ".frame" ).css('background-color', 'rgba( '+col.r+','+col.g+','+col.b+', 0.3 )')
                newitem.find( ".frame" ).css('background-color', 'rgba( '+col.r+','+col.g+','+col.b+', 0.3 )')
            }*/
            newitem.find( ".frame" ).css('background-color', 'rgba( '+val['r']+','+val['g']+','+val['b']+', 0.3 )')
            // hide or show folder icon
            if (val['directory'] == 0) {
                //newitem.children( ".frame" ).children( ".foldericon" ).hide();
                //newitem.children( ".frame" ).children( ".image_editor" ).attr('type', 'file');
                newitem.find( ".foldericon" ).hide();
                newitem.find( ".image_editor" ).attr('type', 'file');
            } else {
                newitem.find( ".foldericon" ).show();
                newitem.find( ".image_editor" ).attr('type', 'directory');
//                newitem.children( ".frame" ).children( ".foldericon" ).show();
                //newitem.children( ".frame" ).children( ".image_editor" ).attr('type', 'directory');
            }

            // remove rating
            newitem.find("li.editTags").attr("value", "")
            newitem.find("li.personalRating").attr("value", "")

            //newitem.insertAfter( lastitem )
            insertData[++insertCount] = newitem;

            //if (fetchLength == 0) { resize_images("#gallery_list .frame"); }
            //resize_images("#gallery_list .frame");

            gotPreloadUpdate = 1;

        })
        //$('#gallery_list p').remove();

        lastitem.after( insertData )
        resize_images("#gallery_list .frame");

        Preloading = 0;
        stopLoading();

    });
    // report wether or not we got updates
    if (gotPreloadUpdate == 1) { return true; } else { return false; }

}

function htmlEncode(value){
  //create a in-memory div, set it's inner text(which jQuery automatically encodes)
  //then grab the encoded contents back out.  The div never exists on the page.
//  return $('<div/>').text(value).html();
  //return value.replace('&', '&amp;').replace('"', '&quot;').replace("'", '&#39;').replace('<', '&lt;').replace('>', '&gt;');
  return value.replace('<', '&lt;').replace('>', '&gt;');
}

function htmlDecode(value){
  return $('<div/>').html(value).text();
}

function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}