
$(document).keydown(function(event){
    if(event.which=="17")
        cntrlIsPressed = true;
    if(event.which=="27")
        reset_multiselect();
});

$(document).keyup(function(){
    cntrlIsPressed = false;
});

var cntrlIsPressed = false;
var multiSelected = [];

$( document ).ready(function() {

    $( '#gallery_list' ).on({ 
        'click' : function(e) {
            if (cntrlIsPressed) {
                e.preventDefault();
                e.stopPropagation();
                var index = multiSelected.indexOf(this.id)
                if (index > -1) {
                    $(this).children( ".frame" ).removeClass("selectedimage");
                    multiSelected.splice(index, 1);
                } else {
                    $(this).children( ".frame" ).addClass("selectedimage");
                    multiSelected.push(this.id);
                }
                console.log('ctrl+click'+this.id+' total value:'+multiSelected);
                    
            } 
        }
        
    }, 'a');

});

function reset_multiselect() {
    $( '#gallery_list a .frame' ).removeClass("selectedimage");
    multiSelected = [];
}