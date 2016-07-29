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
		var ajaxurl = base + '/story/ajax/review_comment_form';
		var data = { childof: $(this).attr('id') };
		$.ajax({
            type: "POST",
            url: ajaxurl,
            data: data,
			success: function (html) {	
				//create the new form
				form.nextAll('.ajaxform:first').hide().html(html).show('slow');
			}		
        });
	}
});

$('#cancelreview').click(function (c) {
	$(this).parents('.ajaxform').hide('slow').html('');
	c.preventDefault();
});
