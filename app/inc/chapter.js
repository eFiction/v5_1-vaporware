function changeChapter() {
	var chap_change = document.getElementById("chap_change");
	var selectedValue = chap_change.options[chap_change.selectedIndex].value;
window.location=url+selectedValue;
}

$('#story-container').on("click", ".openform", function(e) { 
	var form = $(this).parent();
	var all = $(this).parents('.review');
	if ( form.nextAll('.ajaxform:first').html() == '' )
	{
		//clear all old forms
		$(this).parents('.review_container').find('.ajaxform').hide('slow').html('');
		var ajaxurl = url + 'commentform,' + $(this).attr('id');
		$.ajax({
            type: "GET",
            url: ajaxurl,
            data: '',
						success: function (html) {	
							//create the new form
							form.nextAll('.ajaxform:first').hide().html(html).show('slow');
						}		
        });
	}
});