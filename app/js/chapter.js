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
		var data = { childof: $(this).attr('id'), level: $(this).data("level") };
		review_data ( ajaxurl, data, form );
	}
});

function review_data ( ajax_url, ajax_data, ajax_form )
{
		$.ajax({
        type: "POST",
        url: ajax_url,
        data: ajax_data,
		success: function (html) {	
			html = html['BODY'];
			number = 0;
			//create the new form and make it visible
			ajax_form.nextAll('.ajaxform:first').hide().html(html[1]).show('slow');
			if ( html[2] != "" )
			{
				var span = document.getElementById(html[2]);
				span.removeChild( span.firstChild );
				//console.log(html[2]);
			}
			$('.reviewButton').click(function(review) {
				review.preventDefault();
				review_act = ($(this).data("action"));
				
				if(review_act=="submit")
				{
					review_data ( ajax_url, $('#write_review').serialize(), ajax_form );
				}
			});

		}		
    });
}
