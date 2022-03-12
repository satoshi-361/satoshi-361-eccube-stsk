function class_cookielib(){
	this.getCookie = getCookie;
	this.setCookie = setCookie;
	this.removeCookie = removeCookie;
	
	var expireDate = new Date(); 
	expireDate.setFullYear(expireDate.getFullYear()+1); 
	expireStr = "expires=" + expireDate.toUTCString(); 
	
	function getCookie(name){ 
		var gc=name+"="; 
		var Cookie=document.cookie; 

		if (Cookie.length>0) { 
			var start=Cookie.indexOf(gc);
			
			if (start!=-1) {
				start+=gc.length;
				terminus=Cookie.indexOf(";",start);
				if (terminus==-1) terminus=Cookie.length;
				return unescape(Cookie.substring(start,terminus));
			}
		}
		return '';
	}
	
	function setCookie() {
		var key = arguments[0];
		var val = arguments[1];
		var path = (typeof(arguments[2]) != 'undefined' ? arguments[2] : '/');
		var exp = (typeof(arguments[3]) != 'undefined' ? arguments[3] : expireStr);
		var sc = key + "=" + escape(val) + "; path=" + path + "; " + exp;
		document.cookie = sc;
	}
	
	function removeCookie(key,path) {
		if(!path){
			path = '/';
		}
		var rc = key + "=; path=" + path + "; expires=Thu, 1 Jan 1970 00:00:00 UTC";
		document.cookie = rc;
	}
}

var cookieObj = new class_cookielib(); 

function onresize_handler(){
	if(document.layers){
		window.location.reload();
	}
}

window.onresize = onresize_handler; 

var txtsize_val = 3; 
var txtsize_css_size = new Array(); 
txtsize_css_size[0] = '70%'; 
txtsize_css_size[1] = '80%'; 
txtsize_css_size[2] = '90%'; 
txtsize_css_size[3] = '100%'; 
txtsize_css_size[4] = '110%'; 
txtsize_css_size[5] = '120%'; 
txtsize_css_size[6] = '130%'; 
txtsize_css_size[7] = '140%'; 
txtsize_css_size[8] = '150%'; 
txtsize_css_size[9] = '160%'; 
txtsize_css_size[10] = '170%'; 

function setTextSize(){
	if(cookieObj.getCookie('txtsize') != ''){
		txtsize_val = 1 * cookieObj.getCookie('txtsize');
	}
	document.write('<style type="text/css">');
	document.write('* body { font-size:' + txtsize_css_size[txtsize_val] + '; }');
	document.write('</style>');
} 

function changeTextSize(num){
	var fl_update = false;
	var tmp_val = txtsize_val + num;
	if(tmp_val >= 0 && tmp_val < txtsize_css_size.length){
		txtsize_val = tmp_val;
		fl_update = true;
	}
	if(fl_update){
		cookieObj.setCookie('txtsize',txtsize_val,'/','');
		window.location.reload();
	}
} 

function defaultTextSize(){
	var fl_update = false;
	var tmp_val = 3;
	if(tmp_val >= 0 && tmp_val < txtsize_css_size.length){
		txtsize_val = tmp_val;
		fl_update = true;
	}
	if(fl_update){
		cookieObj.setCookie('txtsize',txtsize_val,'/','');
		window.location.reload();
	}
}