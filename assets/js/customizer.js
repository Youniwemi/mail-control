(function($) {
    wp.customize.bind('ready', function() {
        rangeSlider();
    });

    var rangeSlider = function() {
        $('input[type=range]').each(function(i, el){
        	var clone = el.cloneNode(true)
        	clone.type='number';
        	clone.id='n_'+clone.id
        	clone.oninput=function() {
        		var range = this.previousElementSibling;
        		range.value=this.value;
        		range.dispatchEvent(new Event('input', { 'bubbles': true }));
        	}
        	el.oninput= function() { this.nextElementSibling.value=this.value; }
        	el.after(clone)
        })
    };

})(jQuery);
