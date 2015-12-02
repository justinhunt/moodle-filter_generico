/* jshint ignore:start */
define(['jquery','core/log'], function($, log) {

  "use strict"; // jshint ;_;

  log.debug('Filter Generico initialising');

  return {
	  
	  	allopts: {},
		
		extscripts: {},
		
		csslinks: Array(),
		
		jslinks: Array(),
		
		appendjspath: function(jslink, theprefix){
			 require.config({
				paths: {
					theprefix: jslink
				}});
		},
		
		injectcss: function(csslink){
			var link = document.createElement("link");
			link.href = csslink;
			if(csslink.toLowerCase().lastIndexOf('.html')==csslink.length-5){
				link.rel = 'import';
			}else{
				link.type = "text/css";
				link.rel = "stylesheet";	
			}
			document.getElementsByTagName("head")[0].appendChild(link);	
		},
		
		// load all generico stuff and stash all our variables
		loadgenerico: function(opts) {
		//	log.debug('Filter Generico loading');
			log.debug(opts);
		//	log.debug('opts over');

			//add paths
			/*
			if(opts['JSLINK']){
				if (this.jslinks.indexOf(opts['JSLINK'])<0){
					this.jslinks.push(opts['JSLINK']);
					this.appendjspath(opts['JSLINK'],'whatprefixshouldweuse?');
				}
			}
			if(opts['JSUPLOAD']){
				if (this.jslinks.indexOf(opts['JSUPLOAD'])<0){
					this.jslinks.push(opts['JSUPLOAD']);
					this.appendjspath(opts['JSUPLOAD'],'whatprefixshouldweuse?');
				}
			}
			*/
			
			//load our css in head if required
			//only do it once per extension though
			if(opts['CSSLINK']){
				if (this.csslinks.indexOf(opts['CSSLINK'])<0){
					this.csslinks.push(opts['CSSLINK']);
					this.injectcss(opts['CSSLINK']);
				}
			}
			//load our css in head if required
			//only do it once per extension though
			if(opts['CSSUPLOAD']){
				if (this.csslinks.indexOf(opts['CSSUPLOAD'])<0){
					this.csslinks.push(opts['CSSUPLOAD']);
					this.injectcss(opts['CSSUPLOAD']);
				}
			}
			
			//load our css in head if required
			//only do it once per extension though
			if(opts['CSSCUSTOM']){
				if (this.csslinks.indexOf(opts['CSSCUSTOM'])<0){
					this.csslinks.push(opts['CSSCUSTOM']);
					this.injectcss(opts['CSSCUSTOM']);
				}
			}
			
			
			/* Efforts to shim non amd dependencies were just hopeless
			In the end the module integrator really needs knowledge of require.js to 
			detail the "exports" entry and I am not sure it would work even then
			Also if shimming you should trim the ".js" from the cdn path
			in filter.php and genericojs.php. The code is there, you just have to 
			uncomment it. Oh ..and you will have to uncomment the JSLINK filter setting output
			in filter.php. Look for $filterprops['JSLINK']=false;
			
			Anyway, I think this is a dead end. Because the user would
			ultimately need too much knowledge of AMD and shimming to use shimming in 
			Generico. J 20150803
			
			*/
			/*
			var reqconfig ={};
			var paths = {};
			var shim = {};
			paths["recjs" + opts['TEMPLATEID']] = opts['JSLINK'];
			shim["recjs" + opts['TEMPLATEID']] = {deps: ['jquery'], exports: "fn"};
			reqconfig["paths"] = paths;
			reqconfig["shim"] = shim; 
			log.debug("shim herehere");
			log.debug(reqconfig);
			requirejs.config(reqconfig);
			log.debug("shimmed");
			*/

			
			//filter/generico/genericojs.php?t=
			//here require, then load the template scripts and js
			/*
			require(['http://chipmunkyou.com/moodle/cr/filter/generico/genericojs.php?t=' + opts['TEMPLATEID']],function(d){
				d(opts);
			});
			*/

			//here require, then load the template scripts and js
			require(['filter_generico_d' + opts['TEMPLATEID']],function(d){
				d(opts);
			});

			
		}//end of function

	}
});
/* jshint ignore:end */