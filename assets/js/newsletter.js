(function($) {
    $('.email-register form').submit(function(e){
    	e.preventDefault();
    	var $form =  $(this);
    	var data = $form.serialize();
    	$.ajax( {
    		url: $form.attr('action'),
    		data: data,
    		method: 'POST',
    		dataType: 'json'
    	}).done(function( result ) {
			if ('success' in result){
				var $message =$form.next('.response');
				$message.html("<p>"+result.success +"</p")
				$message.show('slow');
				$form.remove();
			} else {
				alert(result.error);
			}
			return false;
		}).fail(function (json , error ){
            alert(error);   
        });
        return false;
    });
})(jQuery);
