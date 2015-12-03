// calendar boot function
$(document).ready(function ()
{ getCalendar("current"); });

// trigger calendar load
function getCalendar(data)
{
	$.ajax
	({
			url: "<?php echo $_GET['base']; ?>/news/calendar/"+data,	
			type: "GET",		
			cache: false,
			success: function (data) { $('#sb_cell_calendar').html(data); }		
	});
}