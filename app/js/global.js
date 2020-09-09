$('.toggle').click(function(event) {
    var div = $(this).nextAll('.toggle_container');
		div.toggle("slow");
    $(this).children('.toggle_less').toggle();
    $(this).children('.toggle_more').toggle();
    event.preventDefault()
});

$("#hideMessage").change( function(c){
	var date = new Date();
	if (this.checked) {
		date.setTime(date.getTime()+(1000*24*60*60*1000));
		expires="; expires="+date.toGMTString();
	}
	else
	{
		date.setTime(date.getTime()-1);
		expires="; expires="+date.toGMTString();
	}
	document.cookie = 'skip_redirect_message=1'+expires+'; path=/'
});

// http://stackoverflow.com/questions/12264205/jquery-ajax-and-chrome-caching-issue
// heavily modified
function getCaptchaImage(){
	$.ajax({
		url: base + '/captcha',
		method: "POST", 
		data: { random: Date.now() },
		cache: false,
		success: function(response){
			//console.log(reference);
			$('.captchaBox').html('<img src="data:image/png;base64,' + response + '" />');
		},
		error: function(response){
			//alert ("Ajax Error");
		}
	});
}

$.extend(
{
    redirectPost: function(location, args)
    {
        var form = '';
        $.each( args, function( key, value ) {
            value = value.split('"').join('\"')
            form += '<input type="hidden" name="'+key+'" value="'+value+'">';
        });
        $('<form action="' + location + '" method="POST">' + form + '</form>').appendTo($(document.body)).submit();
    }
});


document.addEventListener('DOMContentLoaded', () => {
  // Get all "navbar-burger" elements
  const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

  // Check if there are any navbar burgers
  if ($navbarBurgers.length > 0) {

	// Add a click event on each of them
	$navbarBurgers.forEach( el => {
	  el.addEventListener('click', () => {

		// Get the target from the "data-target" attribute
		const target = el.dataset.target;
		const $target = document.getElementById(target);

		// Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
		el.classList.toggle('is-active');
		$target.classList.toggle('is-active');

	  });
	});
  }
});
