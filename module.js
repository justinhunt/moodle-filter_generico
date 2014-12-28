/**
 * Javascript for loading generico
 *
 * @copyright &copy; 2012 Justin Hunt
 * @author poodllsupport@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package filter_poodll
 */

M.filter_generico = {

	allopts: {},
	
	extscripts: {},
	
	csslinks: Array(),
	
	gyui: null,
	
	injectcss: function(csslink){
		var link = document.createElement("link");
		link.href = csslink;
		link.type = "text/css";
		link.rel = "stylesheet";
		document.getElementsByTagName("head")[0].appendChild(link);	
	},
	
	// Replace poodll_flowplayer divs with flowplayers
	loadgenerico: function(Y,opts) {
		//stash our Y and opts for later use
		this.gyui = Y;
		console.log(opts);
		//load our css in head if required
		//only do it once per extension though
		if(opts['CSSLINK']){
			if (this.csslinks.indexOf(opts['TEMPLATEID'])<0){
				this.csslinks.push(opts['TEMPLATEID']);
				this.injectcss(opts['CSSLINK']);
			}
		}
		
		if(typeof filter_generico_extfunctions != 'undefined'){ 
			if(typeof filter_generico_extfunctions[opts['TEMPLATEID']] == 'function'){ 
				filter_generico_extfunctions[opts['TEMPLATEID']](opts);
			}
		}
		
	}//end of function
}//end of class