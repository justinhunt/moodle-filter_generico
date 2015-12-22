/* jshint ignore:start */
define(['jquery','core/log'], function($, log) {

  "use strict"; // jshint ;_;

  log.debug('Filter Generico Presets initialising');

  return {

	  presetdata: false,

	  populateform: function (templateindex, presetindex, presetdata) {
		  var controls = {};
		  controls.key = document.getElementById('id_s_filter_generico_templatekey_' + templateindex);
		  controls.requirecss = document.getElementById('id_s_filter_generico_templaterequire_css_' + templateindex);
		  controls.requirejs = document.getElementById('id_s_filter_generico_templaterequire_js_' + templateindex);
		  controls.defaults = document.getElementById('id_s_filter_generico_templatedefaults_' + templateindex);
		  controls.jquery = document.getElementById('id_s_filter_generico_templaterequire_jquery_' + templateindex);
		  controls.amd = document.getElementById('id_s_filter_generico_template_amd_' + templateindex);
		  controls.body = document.getElementById('id_s_filter_generico_template_' + templateindex);
		  controls.bodyend = document.getElementById('id_s_filter_generico_templateend_' + templateindex);
		  controls.script = document.getElementById('id_s_filter_generico_templatescript_' + templateindex);
		  controls.style = document.getElementById('id_s_filter_generico_templatestyle_' + templateindex);
		  controls.presetdata = document.getElementById('id_s_filter_generico_presetdata_' + templateindex);

		  //what a rip off there was no selection!!!
		  if(!presetindex){return;}

		  var presetdata  =this.presetdata;
		  var dataitems = ['key', 'requirecss', 'requirejs', 'defaults', 'jquery',
			  'amd', 'body', 'bodyend', 'script', 'style'];
		  $.each(dataitems,
			  function (index, item) {
				 // log.debug(item + ':' + presetindex + ':' + presetdata[presetindex][item]);
				  controls[item].value = presetdata[presetindex][item];
			  }
		  );
		  //"value" and "checked" are separate
		  controls['jquery'].checked = this.presetdata[presetindex]['jquery'] ? true : false;
		  controls['amd'].checked = this.presetdata[presetindex]['amd'] ? true : false;

	  },

	  dopopulate: function(templateindex, templatedata){
		this.populateform(templateindex,0,array(templatedata));
	  },
		
		// load all generico stuff and stash all our variables
		init: function(opts) {
			if (!this.presetdata) {
				var controlid='#id_s_filter_generico_presetdata_' + opts['templateindex'];
				var presetcontrol=$(controlid).get(0);
				this.presetdata = JSON.parse(presetcontrol.value);
				$(controlid).remove();
			}

			var amdpresets = this;
			$("select[name='filter_generico/presets']").change(function(){
				amdpresets.populateform(opts['templateindex'],$(this).val());
			});
		}//end of function

	}
});
/* jshint ignore:end */