$( document ).ready(function() {
	shoutbox_data ( 'load/top' );
	
	$('#captchaBox').click(getCaptchaImage);
	
	$('.emotic').click(function(e) {
		e_code = ($(this).data("code"));
		$('#shout').val(function(i, text) {
			return text + ' ' + e_code;
		});
	});

	$('.shoutButton').click(function(sb) {
		sb.preventDefault();
		sb_dir = ($(this).data("direction"));
		sb_act = ($(this).data("action"));
		
		if(sb_dir)
		{
			off = ($("#sboxContent").data("offset"));
			shoutbox_data( 'load/'+sb_dir+','+off );
		}
		else if(sb_act)
		{
			if(sb_act=="submit")
			{
				shoutbox_data ( 'shout', $('#sboxInput').serialize() );
			}
			else if(sb_act=="reset")
			{
				$('#sboxError').hide('slow');
				$('#sboxInput').hide('slow');
				$('#sboxForm').html("");
			}
			else if(sb_act=="shout") 
			{
				if($('#sboxForm').html() == "")
				{
					shoutbox_data ( 'form/build' );
					$('#sboxInput').delay( 200 ).show('slow');
					
				}
				else
				{
					$('#sboxError').hide('slow');
					$('#sboxInput').hide('slow');
					$('#sboxForm').html("");
				}
			}
		}
	});
});

function shoutbox_data ( ajax_url, ajax_data )
{
	$('#sboxError').hide('slow');
	$.ajax({
		dataType: "json",
		//async: false,
		url: base + '/shoutbox/' + ajax_url, 
		method: "POST", 
		cache: false, 
		data: { random: Date.now(), data: ajax_data },
		success: function( json_data )
		{
			json_data = json_data['BODY'];
			if (json_data[3]==1)
			{
				$('#sboxInput').hide('slow');
				$('#sboxForm').html("");
				json_data="";
				shoutbox_data ( 'load/top' );
			}
			else if (json_data[3]==2)
			{
				$('#sboxError').delay( 200 ).show('slow');
				$('#sboxError').html( json_data[1] );
			}
			else if (json_data[0] != ""){
				$( "#sboxLines" ).html( json_data[0] );
				$("#sboxContent").data("offset", json_data[2]);
			}
			else if (json_data[1] != "")
			{
				$( "#sboxForm" ).html(  json_data[1] );
				getCaptchaImage();
				$('#captchaBox').click(getCaptchaImage);
			}
		},
		error: function(  )
		{
			$( "#sboxLines" ).html( "Error!" );
		}
	});
}

