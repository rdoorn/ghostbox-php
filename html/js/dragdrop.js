$( document ).ready(function() {

        init_dragdrop();

});


function init_dragdrop() {
  $( '#gallery_list' ).on({
    'dragstart' : function(e) {
        var dataTransfer = e.originalEvent.dataTransfer;
        dataTransfer.effectAllowed = 'move';
        dataTransfer.setData('Text', this.id);
        
        /*e.preventDefault();
        e.stopPropagation();*/

        //console.log('dragstart'+this.id);

    }
  }, 'a');

  $( '#gallery_list' ).on({
/*    'mouseout' : function(e) {
        var event = e.originalEvent;
        if (event.preventDefault) {
            event.preventDefault();
        }
            $(this).removeClass('leftdrag centerdrag rightdrag')
        },*/
    'dragover' : function(e) {
        var event = e.originalEvent;
        if (event.preventDefault) {
            event.preventDefault();
        }
        event.dataTransfer.dropEffect = 'move';

        var Offset = $(this).offset();
        var Width = $(this).width();
        var relX = event.pageX - Offset.left;
        var relY = event.pageY - Offset.top;

        //console.log('your dragging over '+this.id+' relX:'+relX+' relY:'+relY+' width:'+Width)


        // 3rds of the image
        areaSize=parseInt(Width/3);
        // reset view
        $( '#gallery_list a .frame' ).removeClass('centerdrag').removeClass('leftdrag').removeClass('rightdrag');
        if (relX < areaSize) { // left side
//            if ( !$(this).addClass('leftdrag') ) {
                $(this).children( ".frame" ).addClass('leftdrag');
            //}
        } else if (relX < (areaSize*2)) { // middle
            //if ( !$(this).addClass('centerdrag') ) {
                $(this).children( ".frame" ).addClass('centerdrag');
            //}
        } else { // right
            //if ( !$(this).addClass('rightdrag') ) {
                $(this).children( ".frame" ).addClass('rightdrag');
            //}
        }



        return false;    },
    'drop' : function(e) {
        var event = e.originalEvent;
        if (event.preventDefault) {
            event.preventDefault();
        }
        if (event.stopPropagation) {
            event.stopPropagation();
        }
        var dataTransfer = event.dataTransfer;    
        var draggedId = dataTransfer.getData('Text');

        var Offset = $(this).offset();
        var Width = $(this).width();
        var relX = event.pageX - Offset.left;
        var relY = event.pageY - Offset.top;

        //console.log('you dragged '+draggedId+' to '+this.id+' relX:'+relX+' relY:'+relY+' width:'+Width)

        // hide drop locations
        $( '#gallery_list a .frame' ).removeClass('centerdrag').removeClass('leftdrag').removeClass('rightdrag');

        if (this.id == draggedId) { return; }
        // add whatever we dragged to multiselect, and if that means its just us, thats fine
        multiSelected.push(draggedId);
        areaSize=parseInt(Width/3);
        var action
        if (relX < areaSize) { // left side
            dropOrderLeft(multiSelected, this.id)
            action='dropLeft'
        } else if (relX < (areaSize*2)) { // middle
            dropOrderReplace(multiSelected, this.id)
            action='dropReplace'
        } else { // right
            dropOrderRight(multiSelected, this.id)
            action='dropRight'
        }




        var arr = { Id: this.id , Item: action, Value: multiSelected };
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
            },
            error: function( jqXHR, textStatus, errorThrown ) {
//                //console.log('XHR:'+jqXHR.responseText+' status:'+textStatus+' error:'+errorThrown);
                msg = jQuery.parseJSON(jqXHR.responseText);
                //console.log('message:'+msg.Message)
                showError(msg.Message);
            }
        });        


        reset_multiselect();
        resize_images("#gallery_list .frame");
        return false;
    }
  }, 'a');

}

function dropOrderLeft(items, target) {
    var destination = $( '#'+target );
    jQuery.each( items, function( i, item ) {
        var source = $( '#'+item );
        source.insertBefore( destination )
    });
}


function dropOrderRight(items, target) {
    var destination = $( '#'+target );
    jQuery.each( items, function( i, item ) {
        var source = $( '#'+item );
        source.insertAfter( destination )
    });
}


function dropOrderReplace(items, target) {
    var destination = $( '#'+target );

    //console.log('replacing '+target+' with '+items);
    //$( '<div id="dragdroptmp"></div>' ).insertBefore( destination );

    // put a anchor at destination - here we'll move all sources
    var tempdestination = $( '<div id="dragdroptmp"></div>' ).insertBefore( destination );
    // move target back to source location
    var tempsource = $( '#'+items[0] );
    destination.insertAfter( tempsource )

    // move sources to destination anchor - effectively we've swapped their places
    jQuery.each( items, function( i, item ) {
        var source = $( '#'+item );
        source.insertAfter( tempdestination )
    });
    tempdestination.remove();


/*    if (items.length == 1) {
        // replace
        //console.log('items:'+items[0])
        if ( items != target ) {


            var source = $( '#'+items );
            var destination = $( '#'+target );

            var sourceContent = source.clone().attr('id', 'source1');
            var destinationContent = destination.clone().attr('id', 'destination1');;
    
            source.replaceWith(destinationContent);
            destination.replaceWith(sourceContent);
        }
    } else {
        jQuery.each( items, function( i, item ) {
            var source = $( '#'+item );
            source.insertAfter( destination )
        });

    }*/
}


/*
$('.todrag').on('dragstart', function (e) {
    var dataTransfer = e.originalEvent.dataTransfer;
    dataTransfer.effectAllowed = 'copy';
    dataTransfer.setData('Text', this.id);
});
 
$('.todraginto').on('dragover', function (e) {
    var event = e.originalEvent;
    if (event.preventDefault) {
        event.preventDefault();
    }
    event.dataTransfer.dropEffect = 'copy';
    return false;
});
 
$('#dragTargetInput.todraginto').on('drop', function (e) {
    var event = e.originalEvent;
    if (event.stopPropagation) {
        event.stopPropagation();
    }
    var dataTransfer = event.dataTransfer;
    var draggedId = dataTransfer.getData('Text');
    var draggedElement = $('#' + draggedId);
    var draggedText = draggedElement.text();
    draggedElement.remove();
    e.currentTarget.value = draggedText;
    return false;
});
 
$('#mainCanvas.todraginto').on('drop', function (e) {
    var event = e.originalEvent;
    if (event.stopPropagation) {
        event.stopPropagation();
    }
    var dataTransfer = event.dataTransfer;
    if (dataTransfer.files && dataTransfer.files.length > 0) {
        var reader = new FileReader();
        reader.onload = function (onloadEvent) {
            var coordinates = onloadEvent.target.result.split(',');
            var canvas = $('#mainCanvas');
            var ctx = canvas[0].getContext('2d');
            ctx.beginPath();
            ctx.moveTo(coordinates[0], coordinates[1]);
            ctx.lineTo(coordinates[2], coordinates[3]);
            ctx.lineTo(coordinates[4], coordinates[5]);
            ctx.fill();
 
        };
 
        var firstFile = dataTransfer.files[0];
        reader.readAsText(firstFile);
    }
});
*/