$( document ).ready(function() {
	shoutbox_data ( '<?php echo $_GET['base']; ?>/shoutbox/load/top' );
	function shoutbox_data ( ajax_data )
	{
		$.ajax({
			dataType: "json",
//			async: false,
			url: ajax_data, type: "GET", cache: false, 
			success: function( json_data )
			{
				if (json_data[0] != ""){
					$( "#sboxLines" ).html( json_data[0] );
					$("#sboxContent").data("offset", json_data[2]);
				}
				if (json_data[1] != "") $( "#sboxForm" ).html(  json_data[1] );
				if (json_data[3]==1)
				{
					$('#sboxInput').hide('slow');
					$('#sboxForm').html("");
					json_data="";
					shoutbox_data ( '<?php echo $_GET['base']; ?>/shoutbox/load/top' );
				}
			},
			error: function(  )
			{
				$( "#sboxLines" ).html( "Test" );
			}
		});
	}

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
			ajax_data = false;
			
			if(sb_dir)
			{
				off = ($("#sboxContent").data("offset"));
				ajax_data  = '<?php echo $_GET['base']; ?>/shoutbox/load/'+sb_dir+','+off;
			}
			else if(sb_act)
			{
				if(sb_act=="submit")
				{
					ajax_data = $('#sboxInput').serialize()+'&ajax=blocks&shoutbox=save';
				}
				else if(sb_act=="reset")
				{
					$.ajax({
						url: "index.php", type: "GET", cache: false, data: 'ajax=blocks&shoutbox=formreset',
						success: function( data )
						{
							$( "#sboxForm" ).html( data );
						}
					});
				}
				else if(sb_act=="shout") 
				{
					if($('#sboxForm').html() == "")
					{
						shoutbox_data ( '<?php echo $_GET['base']; ?>/shoutbox/form/build' );
						$('#sboxInput').delay( 200 ).show('slow');
					}
					else
					{
						$('#sboxInput').hide('slow');
						$('#sboxForm').html("");
					}
				}
			}

			if(ajax_data)
			{
				shoutbox_data ( ajax_data );
			} // end: if(ajax_data)
		});
});