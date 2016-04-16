<?php
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.
header("Content-Type: text/javascript");
if ( isset($_GET['sub']) && $_GET['sub']=="story" )
{
?>
$(document).ready(function() {
    $("#category-select").tokenInput("./index.php?ajax=story,search",{
    	method: "get",
    	queryParam: "category",
    	preventDuplicates: true,
    	prePopulate: <?php echo $_GET['p_c']; ?>
    });
});

$(document).ready(function() {
    $("#author-select").tokenInput("./index.php?ajax=story,search",{
    	method: "get",
    	queryParam: "author",
    	tokenLimit: 1,
    	prePopulate: <?php echo $_GET['p_a']; ?>
    });
});

$(document).ready(function() {
    $("#tag-select").tokenInput("./index.php?ajax=story,search",{
    	method: "get",
    	queryParam: "tag",
    	preventDuplicates: true,
    	prePopulate: <?php echo $_GET['p_t']; ?>
    });
});

$(function () {
	$("#chapter-sort").sortable({
		cursor: "move",
		containment: "parent",
		update: function () {
			var neworder = $('#chapter-sort').sortable('toArray');
			$.ajax({
				type: "GET",
				url: "./index.php?ajax=controlpanel&chaptersort=<?php echo $_GET['sid']; ?>",
				data: { neworder: neworder },
				dataType: "html"
//				"order=" + order,
//				dataType: "json",
//				success: function (data) {
//				}
			});
		}
	});
});

<?php
}
if ( isset($_GET['sub']) && $_GET['sub']=="storysearch" )
{
?>
$(document).ready(function() {
	$("#story-select").tokenInput("index.php?ajax=controlpanel&author&uid=<?php echo $_GET['uid']; ?>",{
		method: "get",
		queryParam: "story",
		hintText: "Start typing ...",
		noResultsText: "No matches.",
		tokenLimit: 1
	});
	$('#search').click(function(event) {
		event.preventDefault();
		story = $("#story-select" ).val();
		window.location = '<?php echo $_GET['return']; ?>&story='+story;
	});
});
<?php
}
if ( isset($_GET['sub']) && $_GET['sub']=="library" )
{
?>
$( "select" ).change(function ()
{
	item_value = $( this ).val();	// ok
	item_id		 = $( this ).attr("id");
	$.ajax({
		type: "GET",
		url: "?ajax=userpanel",
		data: { visibility: '<?php echo $_GET['type']; ?>', id: item_id, value: item_value },
		dataType: "html"
	});
});
<?php
}

if ( isset($_GET['sub']) && $_GET['sub']=="confirmDelete" )
{
?>
$(document).ready (function () {
	$('.deleteItem').click (function (event) {
		event.preventDefault();
		var href = $( this ).attr("href");
		var id = this.href.split('id=')[1];
		if ( confirm ("Are you sure you want to delete entry: \n\n" + $(this).attr ("title") + "?") )
		{
			$('<form action="'+href+'" method="POST"/>')
        .append($('<input type="hidden" name="confirmed" value="'+id+'">'))
        .appendTo($(document.body)) //it has to be added somewhere into the <body>
        .submit();
		}
	})
}) ; 
<?php
}
?>