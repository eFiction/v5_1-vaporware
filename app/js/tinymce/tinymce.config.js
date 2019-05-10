tinymce.init({
	selector:'textarea#tinymce',
	plugins: "link table image",
	entity_encoding : "raw",
  forced_root_block : "", //!important
  force_br_newlines : true, //!important
  force_p_newlines : false, //!important
  tools: "inserttable",
	contextmenu: "link image inserttable | cell row column deletetable | copy cut paste",
 	menubar : "FALSE",
	toolbar: [
		"undo redo | copy cut paste | bold italic underline strikethrough removeformat | bullist numlist | link image | alignleft aligncenter alignright | table"
	],
	valid_elements : "h1,h2,h3,h4,h5,h6,ol,li,a[href|target=_blank],img[src],strong,b,div[align|class],span[class],em,i,br,p[align],table[class|width|cellspacing|cellpadding],tr,td[align],colgroup,col[width]",
	paste_word_valid_elements: "a,center,strong,b,div,em,i,br,table,tr,td,p,colgroup,col",
	height: 350
});