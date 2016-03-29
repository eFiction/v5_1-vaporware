// calendar boot function
$(document).ready(function ()
{ getCalendar("current"); });

// trigger calendar load
function getCalendar(data)
{
	$.ajax
	({
			url: "<?php echo $_GET['base']; ?>/blocks/calendar/"+data,	
			type: "GET",		
			cache: false,
			contentType: "application/x-www-form-urlencoded;charset=UTF-8",
			success: function (data) { $('#sb_cell_calendar').html(data); }		
	});
}