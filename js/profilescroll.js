//var background_offset_start = $( "#background_photo" ).css("margin-top");

var max_bg_offset=110;
var max_bg_surface=220;
var max_bg_adjustperc=(max_bg_offset/max_bg_surface);

$( document ).ready(function() {
    var background_offset_start = parseInt($('#nav_background_photo').css('margin-top'));

    $( window ).scroll(function() {
        if (!navigator.userAgent.match(/(iPod|iPhone|iPad)/i)) {
            get_background_offset($( "#nav_background_photo" ));
        }
        
    });

/*
    $(window).on({
        'touchmove': function(e) { 
            get_background_offset($( "#nav_background_photo" ));
        }
    });

    $(window).on("touchstart", function(ev) {
        var e = ev.originalEvent;
        console.log(e.touches);
    });        

    $(document).swipe()
        .on('swipeStart', (e) => {
            //console.log('Swipe start', e.swipe);
        })
        .on('swipeMove', (e) => {
            //console.log('Swipe move', e.swipe);
            get_background_offset($( "#nav_background_photo" ));
            // Log mouse position
            //console.log(e.originalEvent.pageX, e.originalEvent.pageY);
        })
        .on('swipeEnd', (e) => {
            //console.log('Swipe end', e.swipe);
        });
*/

    /*$("#status").swipe( {
    $( 'article' ).swipe( {
    swipeStatus:function(event, phase, direction, distance, duration, fingerCount)
    {
      //Here we can check the:
      //phase : 'start', 'move', 'end', 'cancel'
      //direction : 'left', 'right', 'up', 'down'
      //distance : Distance finger is from initial touch point in px
      //duration : Length of swipe in MS 
      //fingerCount : the number of fingers used
      get_background_offset($( "#nav_background_photo" ));
      console.log('event:'+event+' phase:'+phase+' direction:'+direction+' distance'+distance+' duration:'+duration+' fingers:'+fingerCount)
      console.log( $.fn.swipe.pageScroll  )
      },
      allowPageScroll: "vertical"
      
      fingers:1
      
      threshold:100,
      maxTimeThreshold:2500,
      fingers:'all'
      
    });



    "size" {
        "grid" : 8, // word spacing; smaller is more tightly packed but takes longer
        "factor" : 0, // font resizing factor; default "0" means automatically fill the container
        "normalize" : true // reduces outlier weights for a more attractive output
    },
    "color" {
        "background" : "rgba(255,255,255,0)", // default is transparent
        "start" : "#20f", // color of the smallest font
        "end" : "#e00" // color of the largest font
    },
    "options" {
        "color" : "gradient", // if set to "random-light" or "random-dark", color.start and color.end are ignored
        "rotationRatio" : 0.3, // 0 is all horizontal words, 1 is all vertical words
        "printMultiplier" : 1 // 1 will look best on screen and is fastest; setting to 3.5 gives nice 300dpi printer output but takes longer
    },
    "font" : "Futura, Helvetica, sans-serif", // font family, identical to CSS font-family attribute
    "shape" : "circle", // one of "circle", "square", "diamond", "triangle", "triangle-forward", "x", "pentagon" or "star"; this can also be a function with the following prototype - function( theta ) {}

*/
/*
    $( "#tagcloud" ).awesomeCloud( 
    "size" {
        "grid" : 8, // word spacing; smaller is more tightly packed but takes longer
        "factor" : 0, // font resizing factor; default "0" means automatically fill the container
        "normalize" : true // reduces outlier weights for a more attractive output
    },
    "color" {
        "background" : "rgba(255,255,255,0)", // default is transparent
        "start" : "#20f", // color of the smallest font
        "end" : "#e00" // color of the largest font
    },
    "options" {
        "color" : "gradient", // if set to "random-light" or "random-dark", color.start and color.end are ignored
        "rotationRatio" : 0.3, // 0 is all horizontal words, 1 is all vertical words
        "printMultiplier" : 1 // 1 will look best on screen and is fastest; setting to 3.5 gives nice 300dpi printer output but takes longer
    },
    "font" : "Futura, Helvetica, sans-serif", // font family, identical to CSS font-family attribute
    "shape" : "circle", // one of "circle", "square", "diamond", "triangle", "triangle-forward", "x", "pentagon" or "star"; this can also be a function with the following prototype - function( theta ) {}
     );
*/
    function get_background_offset($this) {
    if (background_offset_start) {
        var height = $(document).scrollTop();
        if (height<max_bg_surface) {

           $this.css( "margin-top",  Math.floor(background_offset_start+(height*max_bg_adjustperc)) );

        }

    } 
}
});

            $(document).ready(function(){
                $("#tagcloud").awesomeCloud({
                    "size" : {
                        "grid" : 1,
                        "factor" : 2,
                        "normalize" : true
                    },
                    "color" : {
                        "background" : "#000",
                        "start" : "#FFF",
                        "end" : "#F00"
                    },
                    "options" : {
/*                        "color" : "gradient",*/
                        "rotationRatio" : 0.30,
                        "printMultiplier" : 2,
                        "sort" : "random"
                    },
                    "font" : "verdana",
                    "shape" : "circle"
                });
            });


