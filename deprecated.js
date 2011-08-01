if(document.addEventListener)
	document.addEventListener("DOMContentLoaded", function()
		{ 
			document.removeEventListener( "DOMContentLoaded", arguments.callee, false);
			var _o = document.getElementById('comment');
			is_running = 1;
			if(isValidObject(_o))
				_o.setAttribute('onkeyup', 'growTextarea(\'comment\',document.documentElement.clientHeight);');
			ajaxcm.walk_comment_paging_links() 
		}, false);
		
else if(document.all && !window.opera) {			// IE is different...
  	document.write('<script type="text/javascript" id="cnt_loaded" defer="defer" src="javascript:void(0)"><\/script>');
  	var loaded_check = document.getElementById("cnt_loaded");
  	loaded_check.onreadystatechange = function() {
    	if(this.readyState == "complete") {
			var _o = document.getElementById('comment');
			is_running = 1;
			if(isValidObject(_o))
				_o.setAttribute('onkeyup', 'growTextarea(\'comment\',document.documentElement.clientHeight);');
      		ajaxcm.walk_comment_paging_links();
   		}
  	};
}

