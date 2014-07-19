var THUMB_SIZE = 400;
// on document ready when DOm has been loaded
$( document ).ready(function() {
    resize_images("#gallery_list .frame");
});

// on document ready when ALL has been loaded 
$( window ).ready(function() {
    resize_images("#gallery_list .frame");
});

// on resize
$(window).on('resize', function(){
    resize_images("#gallery_list .frame");
});

// resize the images in frame
function resize_images(frame) {
//    console.time('benchmark');

    image = {}
    // setup initial hash - account for X number of images next to eachother
    for (var i=0; i<20; i++) {
        image[i] = {}
    }
    counter=0;
    currentwidth=0;
    currentheight=0;



    maxheight = THUMB_SIZE;
    framemargin = 24;
    maxwidth= $(window).width()-20;

    //console.log('RESIZE');
    
    $(frame).each(function() {
        // theory: height must be the same for all pictures
        //         width must fill the width of the window
        //          must add/remove picture depending of violating max height



        // calculate if 1 or 2 image will fill the screen
        
        /*image[counter]['height'] = parseInt($(this).attr("oheight"));
        image[counter]['width'] = parseInt($(this).attr("owidth"));*/
        // rescale to thumbnail size
        image[counter]['height'] = THUMB_SIZE;
        image[counter]['width'] = Math.floor((THUMB_SIZE/parseInt($(this).attr("oheight")))*parseInt($(this).attr("owidth")));
        
        //image[counter]['id'] = $(this).attr('id');
        image[counter]['item'] = $(this);

        //console.log(JSON.parse(JSON.stringify(image)));

        //image[0]['height'] = $(this).find('img').height();
        //console.log('original image:'+counter+' width:'+image[counter]['width']);
        //console.log('alternate image:'+counter+' width:'+$(this).width());

        currentwidth += image[counter]['width']
        //currentheight += image[counter]['height']

        if (currentwidth > (maxwidth - (counter*framemargin))) {
            //console.log('maxwidth reached -  width: '+currentwidth+' calculated:'+(currentwidth + (counter*framemargin))+' maxwidth:'+maxwidth+' counter:'+counter);
            // we have enough data to fill the line, calculate needed height
            adjustperc=((maxwidth - (counter*framemargin))/currentwidth);
            //console.log('adjustment:'+adjustperc)
            //newwidth=0;
            newheight=0;
            for (var i=0; i<counter+1; i++) {
                    // set width
                    $(image[i]['item']).width(Math.floor(image[i]['width']*adjustperc));

                    // make sure height is the same everywhere too - always recalculate based on origin 
                    if (newheight == 0) { 
                        newheight=Math.floor(image[i]['height']*adjustperc)
                    }
                    $(image[i]['item']).height(newheight);
                    
                    //console.log('adjusting '+i+' id:'+$(image[i]['item']).attr("id")+' w: '+$(image[i]['item']).width()+' h: '+$(image[i]['item']).height()) 
                    //newwidth+=$(image[i]['item']).width();

            }
            currentwidth=0;
            counter=0;
            firstset=1
        } else {
            counter++;
        }


        // make sure the remaining items have their limits too
        if ($(this).attr("id") == $(frame).last().attr("id")) {
            //console.log("last item detected");
            for (var i=0; i<counter; i++) {
                $(image[i]['item']).width( image[i]['width'] );
                $(image[i]['item']).height( image[i]['height'] );
            }
        } 
/*

        if (($(this).attr("id") == $(frame).last().attr("id")) && (counter != 0)) {
            //console.log('preload more!');
            var x=1;
            if (bla == 0) {
                   bla = 1
                    x = PreloadImages(1);
                    //x = PreloadImages(1);
                    //resize_images("#gallery_list .frame");

            }
            console.log('x = '+x);
            //if (PreloadImages(1) == true) { 
              //  console.log('got an extra image');
                //resize_images("#gallery_list .frame");
            //}
        }
*/
//console.timeEnd('benchmark');


    });
}