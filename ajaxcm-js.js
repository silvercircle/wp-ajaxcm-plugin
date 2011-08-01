/*
 * ajaxcm supporting code
 * (C) 2011
 */
 
function isValidObject(T) {
  if (null == T || typeof(T) == 'undefined') 
  	return false;
  
  return true;
};
 
ajaxcm = {
	term : null,
	search_active : false,

	init : function() {
		var I = this.I, s, form, _o, f, p1, p2;

		if ( f = I('postcomments') ) f.style.display = 'block';
		if ( s = I('ajcom_srchform') ) s.style.display = 'block';
		if ( p1 = I('pagenav1') ) p1.style.display = 'block';
		if ( p2 = I('pagenav2') ) p2.style.display = 'block';

		if(form = I('commentform')) {
			_o = document.createElement('input');
			_o.setAttribute('type', 'hidden');
			_o.value = '1';
			_o.name = 'ajax_post';
			form.appendChild(_o);
			form.onsubmit = function()
				{
					if (ajaxcm.sanitizeForm())
						ajaxcm.commentPost(0);
					return false;
				};
		}
	},

	scrollTo : function(_id) {
		var _o = document.getElementById(_id);
		var curtop = 0;
		
		if(!isValidObject(_o))
			return;
			
		if (_o.offsetParent) {
			do {
				curtop += _o.offsetTop;
			} while (_o = _o.offsetParent);
		}
		window.scroll(0, curtop);
	},
	
	showError : function(errorMessage) {
		var I = this.I;
		
		I('ajaxcm_errorbox').innerHTML = errorMessage;
		I('ajaxcm_errorbox').style.display = 'block';
	},

	setTimeOut : function(t) {
		this.timer = window.setTimeout(function(){ajaxcm.timeOutError();},t);
	},
	
	clearTimeOut : function() {
		if(this.timer) {
			window.clearTimeout(this.timer);
			this.timer = null;
		}
	},
	
	sendRequest : function(uri, request, anchor_element) {
		var xmlrequest = new XMLHttpRequest();

		if(xmlrequest) {
			this.setTimeOut(15000);
			this.req = xmlrequest;
			xmlrequest.onreadystatechange = function() { ajaxcm.response(anchor_element) };
			xmlrequest.open('POST', uri, true);
			xmlrequest.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			xmlrequest.send(request);
		}
	},

	resetCommentForm : function() {
	    var cancel_button = document.getElementById('cancel-comment-reply-link');
		if(isValidObject(cancel_button)) {
			if(cancel_button.style.display != 'none') {
				try {
					cancel_button.click();
				}
				catch(e) {
					var evt = document.createEvent("MouseEvents");
  					evt.initMouseEvent("click", true, true, window,0, 0, 0, 0, 0, false, false, false, false, 0, null);
  					var _r = !cancel_button.dispatchEvent(evt);
				}
			}
		}
	},
	
	commentPost : function(mode){
		var str = '', err;
		var form = this.I('commentform');
		var link;
		
		if(mode)
			link = ajaxcm_permalink;
		else
			link = form.action;
			
		var elems = form.elements || form.templateElements;
		
		for (i=0; i<elems.length; i++) {
			if (elems[i].tagName == "INPUT") {
				if (elems[i].type == "text" || elems[i].type == "hidden") {
					str += elems[i].name + "=" + encodeURIComponent(elems[i].value) + "&";
				}
				if (elems[i].type == "checkbox") {
					if (elems[i].checked) str += elems[i].name + "=" + encodeURIComponent(elems[i].value) + "&";
					else str += elems[i].name + "=&";
				}
				if (elems[i].type == "radio") {
					if (elems[i].checked) str += elems[i].name + "=" + encodeURIComponent(elems[i].value) + "&";
				}
			}
			if (elems[i].tagName == "SELECT") {
				var sel = elems[i];
				str += sel.name + "=" + encodeURIComponent(sel.options[sel.selectedIndex].value) + "&";
			}
			if (elems[i].tagName == "TEXTAREA") {
				str += elems[i].name + "=" + encodeURIComponent(elems[i].value) + "&";
			}
		}
		if(mode)
			str += ('ajaxcm_preview=1');
		else
			str += ('apage=' + ajaxcm_cpage);
	
		this.term = null;
		this.err_clear();
		this.setFormBusy(1);
		if(!mode)
			this.resetCommentForm();

		if(mode) {
			this.sendRequest(link, str, 'ajaxcm_preview');
		}
		else {
			this.clearPreview();
			this.sendRequest(link, str, ajaxcm_containerid);
		}
	},

	clearPreview : function() {
		var _o = document.getElementById('ajaxcm_preview');
		if(isValidObject(_o))
			_o.innerHTML = '';
	},
	
	formError : function(err) {
		var out, r;
	
		if(!(r = this.I('respond'))) 
			return;
		if(!err || typeof(err) != 'string') 
			err = 'Server error...';
		var p1 = err.indexOf('<p>');
		var p2 = err.lastIndexOf('</p>');
	
		if(p1 != -1 && p2 != -1) 
			out = err.substring( p1, ( p2 + 4 ) );
		else 
			out = err;
	
		if ( out.length > 250 ) 
			out = 'Server error...';

		this.showError(out);
		this.setFormBusy(0);
	},

	err_clear : function() {
		var err;
		if ( err = this.I('ajcom-error') ) err.parentNode.removeChild(err);
	},

	setContainerBusy : function(mode) {
		var container = this.I(ajaxcm_containerid);
		var links = container.getElementsByTagName('A');
		var alpha;
	
		if (mode) {
			if(this.container_busy)
				return;
			this.container_busy = true;
			alpha = 30;
			for(i = 0; i < links.length; i++) {
				links[i].disabled = true;
			}
		} else {
			if (!this.container_busy)
				return;
			this.container_busy = false;
			alpha = 100;
			for(i = 0; i < links.length; i++)
				links[i].disabled = null;
		}
	
		container.style.opacity = (alpha / 100);
		this.err_clear();
	},

	setFormBusy : function(mode) {
		var alpha, state;
		var form = this.I('commentform');
	
		if (mode) {
			if (this.form_busy) 
				return;
			this.form_busy = true;
			alpha = 30;
			state = 'disabled';
		} else {
			if (!this.form_busy )
				return;
			this.form_busy = false;
			alpha = 100;
			state = '';
		}
		var _e = form.elements || form.templateElements;
		for(i = 0; i < _e.length; i++) {
			if(_e[i].tagName != "FIELDSET" ) _e[i].disabled = state;
		}
		form.style.opacity = (alpha / 100);
	},

	fetch : function(request, anchor) {
		
		if (anchor && anchor.disabled) 
			return;
		
		this.resetCommentForm();
		
		var _r = request.split('=');
		
		ajaxcm_cpage = _r[1];
		var full_request = 'postid=' + ajaxcm_postID + '&ajax_getcomments=1&' + request;
		if(this.search_active) {
			full_request += ('&ajaxcm_find=' + this.term);
		}
		//this.sendRequest(ajaxcm_blogurl + '/wp-load.php', full_request, ajaxcm_containerid);
		this.sendRequest(ajaxcm_permalink, full_request, ajaxcm_containerid);
		this.setContainerBusy(1);
	},

	timeOutError : function() {
		if ( this.req ) 
			ajaxcm.req.abort();
		this.showError('Error: Connection has timed out.');
		this.setContainerBusy(0);
		this.setFormBusy(0);
	},

	response : function(anchor) {
		var com, srch, err;
	
		try {
			if(this.req.readyState == 4) {
				this.clearTimeOut();
				if(this.req.status == 200) {
					if (this.term) 
						this.I(anchor).innerHTML = this.doHighlight( this.req.responseText, this.term );
					else 
						this.I(anchor).innerHTML = this.req.responseText;
		
					//if(com = this.I('comment')) 
					//	com.value = '';
		
					this.walk_comment_paging_links();
					try {
						eval(ajaxcm_js_eval);
					}
					catch(e) {}
					this.setFormBusy(0);
					this.setContainerBusy(0);
					if(anchor == ajaxcm_containerid && ajaxcm_force_scroll == 'on')
						this.scrollTo(ajaxcm_containerid);
				} else if(this.req.status == 500) {
					this.clearTimeOut();
					err = this.req.responseText || this.req.statusText;
					this.formError( err );
					this.setContainerBusy(0);
				}
			}
		} catch(e) {
			this.showError('Unspecified server or script error.');
		}
	},

	ajSearch : function() {
		var term = this.I('ajcom_search').value;
	
		if ( term.length < 3 ) {
			alert('Search term must have a minimum length of 3 characters.');
			return false;
		}

		term = term.replace(/</g, '&lt;');
		term = term.replace(/>/g, '&gt;');
		term = encodeURIComponent(term);
		this.term = term;
		this.fetch('apage=1&ajaxcm_find='+term);
		this.search_active = true;
		this.I('ajaxcm_resetbtn').style.display = 'inline';
	},

	ajReset : function() {
		this.term = null;
		this.search_active = false;
		this.fetch('apage=1');
		this.I('ajaxcm_resetbtn').style.display = 'none';
	},

	doHighlight : function(text,term) {

		var startTag = '<span style="color:'+ajaxcm_fgcolor+';background-color:'+ajaxcm_bgcolor+';">';
		var endTag = '</span>';
		
		term = decodeURIComponent(term);
		if ( term.length < 3 ) return text;
		
		term = term.replace(/\x22|\x27/g, "%%%%%%%");
	
		var lcTerm = term.toLowerCase();
		var lcText = text.toLowerCase();
		lcText = lcText.replace(/&#8220;|&#8221;|&#8217;/g, "%%%%%%%");
	
		var newText = "";
		var i = -1;
	
		var patt = /&[a-zA-Z0-9#]{2,6};/;
		var tl = term.length;
		var tenc = term.search(patt);
	
		while( text.length > 0 ) {
			i = lcText.indexOf(lcTerm, i+1);
			if ( i < 0 ) {
				newText += text;
				text = '';
			} else {
				if ( text.lastIndexOf(">", i) >= text.lastIndexOf("<", i) ) {
					if ( text.substr( (i - 4), (tl + 8) ).search(patt) == -1 || tenc != -1 )	{
						newText += text.substring(0, i) + startTag + text.substr(i, tl) + endTag;
						text = text.substr(i + tl);
						lcText = text.toLowerCase();
						i = -1;
					}
				}
			}
		}
		return newText;
	},

	/*
	 * check comment posting form for completeness
	 */
	sanitizeForm : function() {
		var t = /^[a-z0-9]([a-z0-9_\-\.]+)@([a-z0-9_\-\.]+)(\.[a-z]{2,7})$/i;
		var I = this.I, err = 0, author = I('author'), email;
	
		I('ajaxcm_errorbox').style.display = 'none';
		if (!author)
			return true;
	
		if(author.value == '') {
			author.style.border = '1px solid red';
			author.focus();
			err = 1;
		}
	
		if(comment = this.I('comment')) {
			if(comment.value == '') {
				comment.focus();
				err = 1;
			}
		}
	
		if(email = this.I('email')) {
			if(email.value == '' || ! t.test( email.value)) {
				email.style.border = '1px solid red';
				email.focus();
				err = 1;
			}
		}
	
		if(err) {
			this.showError('Error while trying to post comment, some required fields were not filled out.');
			return false;
		}
		return true;
	},

	/*
	 * add the required onclick attributes to all comment
	 * page links. runs at document.ready and triggered from the
	 * response - complete handler after a new page of comments
	 * has been loaded
	 */
	walk_comment_paging_links : function() {
		var _o = document.getElementById(ajaxcm_containerid);
		
		if(!isValidObject(_o)) {
			ajaxcm.showError('AJAXCM_PLUGIN_ERROR: Your comments template is missing the wrapper object. Please refer to the docs.');
			return;
		}
			
		var _links = _o.getElementsByTagName('A');
		var i, _pos, _pos1, _nr, _r, _offset;
	
		if(_links.length) {
			for(i = 0; i < _links.length; i++) {
				_r = _links[i].href;
				_pos = _r.search(/comment-page/i);
				if(_pos <= 2) {
					_pos = _r.search(/cpage=/i);
					if(_pos > 1)
						_offset = 6;
				}
				else {
					_offset = 13;
				}
				_pos1 = _r.search(/#comments/i);
				if(_pos > 1 && _pos1 > 1) {
					_nr = _r.charAt(_pos + _offset);
					_links[i].setAttribute('onclick', 'ajaxcm.fetch(\'apage='+_nr+'\', this);return(false);');
				}
			}
		}
		
		_o = document.getElementById('commentform');
		var _p = document.getElementById('ajaxcm_preview');
		if(isValidObject(_o) && !isValidObject(_p)) {
			_v = document.createElement('DIV');
			_v.id = 'ajaxcm_preview';
			_o.parentNode.insertBefore(_v, _o);
			
			var _s = document.getElementById('submit');
			if(isValidObject(_s)) {
				_i = document.createElement('INPUT');
				_i.type = 'button';
				_i.className = 'button';
				_i.id = 'preview';
				_i.name = 'preview';
				_i.value = 'Preview comment';
				_i.setAttribute('onclick', 'ajaxcm.commentPost(1); return(false);');
				_s.parentNode.insertBefore(_i, _s);
			}
		}
	},
	
	I : function(_e) {
		return document.getElementById(_e);
	}

};

function c_quote(authorId, commentId, commentBodyId, commentBox) {
	var author = document.getElementById(authorId).innerHTML;
	var comment = document.getElementById(commentBodyId).innerHTML;

	var insertStr = '<blockquote cite="#' + commentBodyId + '">';
	insertStr += '\n<strong><a href="#' + commentId + '">' + author.replace(/\t|\n|\r\n/g, "") + '</a> :</strong>';
	insertStr += comment.replace(/\t/g, "");
	insertStr += '</blockquote>\n';

	insertQuote(insertStr, commentBox);
};

function insertQuote(insertStr, commentBox) {
	if(document.getElementById(commentBox) && document.getElementById(commentBox).type == 'textarea') {
		field = document.getElementById(commentBox);

	} else {
		alert("The comment box does not exist!");
		return false;
	}

	if(document.selection) {
		field.focus();
		sel = document.selection.createRange();
		sel.text = insertStr;
		field.focus();

	} else if (field.selectionStart || field.selectionStart == '0') {
		var startPos = field.selectionStart;
		var endPos = field.selectionEnd;
		var cursorPos = startPos;
		field.value = field.value.substring(0, startPos)
					+ insertStr
					+ field.value.substring(endPos, field.value.length);
		cursorPos += insertStr.length;
		field.focus();
		field.selectionStart = cursorPos;
		field.selectionEnd = cursorPos;

	} else {
		field.value += insertStr;
		field.focus();
	}
};

/*
 * old school version of $(document).ready
 */

if(document.addEventListener) {
	document.addEventListener("DOMContentLoaded", function()
		{ 
			document.removeEventListener( "DOMContentLoaded", arguments.callee, false);
			var _o = document.getElementById('comment');
			/*
			if(isValidObject(_o)) {
				//_o.setAttribute('onkeyup', 'growTextarea(\'comment\',document.documentElement.clientHeight);');
				_o.setAttribute('onkeyup', 'growTextarea(\'comment\',200);');
				_o.style.overflow = 'hidden';
			}
			*/
			ajaxcm.walk_comment_paging_links();
		}, false);
}
else if(document.attachEvent) {			// IE is different...
	document.attachEvent("onreadystatechange", function()
	{
    	if ( document.readyState === "complete" ) {
      		document.detachEvent( "onreadystatechange", arguments.callee );
			/*
			var _o = document.getElementById('comment');
			if(isValidObject(_o)) {
				//_o.setAttribute('onkeyup', 'growTextarea(\'comment\',document.documentElement.clientHeight);');
				_o.setAttribute('onkeyup', 'growTextarea(\'comment\',200);');
				_o.style.overflow = 'hidden';
			}
			*/
      		ajaxcm.walk_comment_paging_links();
    	}
  	});
}


var initialHeight = -1; 
  
function growTextarea(id, maxHeight) 
{ 
    var _o = document.getElementById(id); 
    var curHeight = _o.clientHeight; 
  
    _o.style.overflow = 'hidden';       // avoid flashing vertical scroll bars (ugly) 
    /* 
     * the initial (default) height of the text area is our reference for shrinking it 
     * should that become necessary. It will never be smaller than its initial height. 
     */
    if(initialHeight == -1) 
        initialHeight = _o.clientHeight; 
  
    /* 
     * shrink it 
     */
    if(_o.clientHeight > initialHeight) { 
    	//alert('shrink ch = ' + _o.clientHeight + ' sh = ' + _o.scrollHeight + ' oh = ' + _o.offsetHeight);
        //curHeight = Math.max(_o.scrollHeight, initialHeight); 
        //_o.style.height = curHeight + "px"; 
        //return; 
    } 
  
    /* 
     * if the height exceeds the defined max height, we simply enable the 
     * scroll bar. No further growing will be performed 
     */
    if(_o.scrollHeight > maxHeight) { 
        _o.style.overflow = 'auto'; 
        return; 
    } 
  
    if(!maxHeight || maxHeight > curHeight) { 
        curHeight = Math.max(_o.scrollHeight, curHeight); 
        if(maxHeight) 
            curHeight = Math.min(maxHeight, curHeight); 
        if (curHeight > _o.clientHeight) 
            _o.style.height = curHeight + "px"; 
    } 
};