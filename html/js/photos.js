
var imgHeight
var imgWidth
asideStatus();

$( window ).ready(function() {
    asideStatus();
    resizeSidebar();
    getRelated();
});

$( window ).load(function() {
    console.log('load2')

    var img = $('#imagecontainer').children('img');        
    imgHeight = img.get(0).height;
    imgWidth = img.get(0).width;    
    photoHeight()
});


// on resize
$(window).on('resize', function(){
    resizeSidebar();
    photoHeight()
});


$( document ).ready(function() {
    resizeSidebar();    

    $( '#menubutton').on({
        'click': function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (parseInt($('aside').width()) == 0) {
                document.cookie = "aside=on; path=/"
                //fullscreen();
                //DoFullScreen()
                //console.log('clicky2')
                //$(this).parent().removeClass("hidden").addClass("show");
                $('aside').animate({
                  width: 250
                }, {
                    duration: 500,
                    step: function( currentLeft, animProperties ){
                        photoHeight()
                    }
                });        
            } else {
                document.cookie = "aside=off; path=/"
                $('aside').animate({
                  width: 0
                }, {
                    duration: 500,
                    step: function( currentLeft, animProperties ){
                        photoHeight()
                    }
                });        
            }
            console.log('clicky!'+$(this).parent().css('margin-left'))
        }
    });

    $( '#fullscreenbutton').on('click', function(){
        fullScreen();
    });
    $( 'article').on({
        'mouseenter': function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('mousenter')
            $( '.photoarticlebutton' ).show();
            return false;
        },
        'mouseleave': function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('mousout')
            $( '.photoarticlebutton' ).hide();
            return false;
        }
    });


    $( '#nextbutton').on('click', function(){
        nextImage();
    });
    $( '#previousbutton').on('click', function(){
        nextImage();
    });
    $( '#previousbutton').on('click', function(){
        previousImage();
    });
    $( document ).on({
        'keydown' : function(e) {
            // ESC=27, Enter=13
            console.log(e.which);
            if (e.which == 39 ) { // arrow right
                nextImage();
            }
            if (e.which == 37 ) { // arrow left
                previousImage();
            }
            if (e.which == 32 ) { // space
                nextImage();
            }
        }
    });
    $(document).swipe( {
        //Generic swipe handler for all directions
        swipeLeft:function(event, direction, distance, duration, fingerCount) {
          //$(this).text("You swiped " + direction + " " + ++count + " times " );  
          nextImage();
        },    
        swipeRight:function(event, direction, distance, duration, fingerCount) {
          //$(this).text("You swiped " + direction + " " + ++count + " times " );  
          previousImage();
        }
    });

/*    $( '#upbutton').on('click', function(){
        upButton();
    });*/

});


function nextImage() {
    console.log('next image:'+$( '#nextbutton').parent().attr('href'))
    if ($( '#nextbutton').parent().attr('href') != undefined) {
        window.location.href = $( '#nextbutton').parent().attr('href')
    }
}
function previousImage() {
    console.log('prev image')
    if ($( '#previousbutton').parent().attr('href') != undefined) {
        window.location.href = $( '#previousbutton').parent().attr('href')
    }
}

/*

$( 'article img' ).load(function() {
    console.log('load1')
    photoHeight()
    //photo_height();
});
*/

// on document ready when ALL has been loaded 

function photoHeight() {
    console.log('resize');
    /*var img = $('#imagecontainer').children('img');        

    imgHeight = img.get(0).height;
    imgWidth = img.get(0).width;    
    $('#imagecontainer').height(imgHeight);
    */

    var articleHeight = $('article').height();
    var articleWidth = $('article').width();

    var imageHeight = imgHeight;
    var imageWidth = imgWidth;

    if (imageWidth>articleWidth) {
        imageHeight=(articleWidth/imageWidth)*imgHeight;
    }
    if (imageHeight<articleHeight) { // image is smaller then frame
        newHeight = (articleHeight/2) - (imageHeight/2)
        console.log('image<article => '+newHeight )
    } else {
        newHeight = 0
    }
    $( '#imageframe' ).css('margin-top', newHeight);
    $( '#imageframe' ).show();


    $( '#previousbutton' ).css("top", 
        parseInt( $('#imagecontainer img').offset().top + ($('#imagecontainer img').height() /2) -70 ) 
        );
    $( '#previousbutton' ).css("left", 
        parseInt( $('#imagecontainer img').offset().left +15 ) 
        );
    $( '#nextbutton' ).css("top", 
        parseInt( $('#imagecontainer img').offset().top + ($('#imagecontainer img').height() /2) -70 ) 
        );
    $( '#nextbutton' ).css("left", 
        parseInt( $('#imagecontainer img').offset().left + $('#imagecontainer img').width() -40) 
        );
    $( '#nextprev' ).show();    
    console.log('img: top:'+ $('#imagecontainer img').offset().top+' right:'+($('#imagecontainer img').offset().left+$('#imagecontainer img').width()) );
    //console.log('awidth:'+width+' aheight:'+height+' iwidth:'+imgWidth+' iheight:'+imgHeight+' w2:'+w2+' h2:'+h2);*/
}

// vertical align where css fails
/*
function photo_height() {
    var height= $(window).height()-parseInt($( 'header' ).css('height'))-parseInt($( 'footer' ).css('height'));
    var imageheight = parseInt( $( 'article img').height() );

    console.log('height:'+height+' imageheight:'+imageheight)
    $( 'article img' ).css('margin-top', parseInt( height/2 ) - parseInt( imageheight/2 )  );
    $( 'article img' ).show();

}
*/

/*
function upButton() {
    var pathArray = window.location.pathname.split( '/' )
    pathArray = pathArray.splice(4,pathArray.length-4);

    var newPath = '/'+pathArray.join('/');
    console.log(newPath);
    window.location = newPath;
}
*/

function getRelated() {
    var pathArray = window.location.pathname.split( '/' )
    var itemid = pathArray[2];
    pathArray = pathArray.splice(4,pathArray.length-4);

    var url = '/'+pathArray.join('/');
    url = insertParam(url, 'related', itemid)
    console.log(url);
    var jqxhr = $.getJSON( url , function( data ) {
        console.log(data)
    });


}

function asideStatus() {
        var aside = document.cookie.replace(/(?:(?:^|.*;\s*)aside\s*\=\s*([^;]*).*$)|^.*$/, "$1");
        if (aside == 'off') { $('aside').width(0); }
}
function resizeSidebar() {
    var height= $(window).height()-parseInt($( 'header' ).css('height'))-parseInt($( 'footer' ).css('height')); 
    $( 'aside' ).css('height',height);
    $( 'article' ).css('height',height);
}


function fullScreen() {
    var isInFullScreen = (document.fullScreenElement && document.fullScreenElement !==     null) ||    // alternative standard method  
            (document.mozFullScreen || document.webkitIsFullScreen);

    var docElm = document.documentElement;
    if (!isInFullScreen) {
        if (docElm.requestFullscreen) {
            docElm.requestFullscreen();
        }
        else if (docElm.msRequestFullscreen) {
            docElm.msRequestFullscreen();
        }
        else if (docElm.mozRequestFullScreen) {
            docElm.mozRequestFullScreen();
        }
        else if (docElm.webkitRequestFullScreen) {
            docElm.webkitRequestFullScreen();
        }
    } else {
        if (docElm.exitFullscreen) {
            docElm.exitFullscreen();
        }
        else if (docElm.msRequestFullscreen) {
            docElm.msExitFullscreen();
        }
        else if (docElm.mozCancelFullScreen) {
            docElm.mozCancelFullScreen();
            document.mozCancelFullScreen();
                   //mozCancelFullScreen();
        }
        else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
                   //mozCancelFullScreen();
        }
        else if (docElm.webkitCancelFullScreen) {
            docElm.webkitCancelFullScreen();

        }
    }
}