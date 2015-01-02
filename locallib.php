<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package filter_generico
 * @copyright  2014 Justin Hunt (http://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/adminlib.php');

/**
 * No setting - just heading and text.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_genericopresets extends admin_setting {

	  /** @var mixed int index of template*/
    public $templateindex;
    /** @var array template data for spec index */
    public $presetdata;
    public $visiblename;
    public $information;

    /**
     * not a setting, just text
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $heading heading
     * @param string $information text in box
     */
    public function __construct($name, $visiblename, $information,$templateindex,$presetdata) {
        $this->nosave = true;
        $this->templateindex = $templateindex;
        $this->presetdata = $presetdata;
        $this->visiblename=$visiblename;
        $this->information=$information;
        parent::__construct($name, $visiblename, $information, '',$templateindex,$presetdata);
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
    // do not write any setting
        return '';
    }

    /**
     * Returns an HTML string
     * @return string Returns an HTML string
     */
    public function output_html($data, $query='') {
        global $OUTPUT;
     
        //make our js
        $jscallback = 'filter_generico_fillfields_' . $this->templateindex ;
		$js ="<script>";
		$js .="function $jscallback(presetindex){";
		$js .="if(!presetindex){return;}";
		$js .="var presets = " . json_encode($this->presetdata) .";";
		$js .="var key = document.getElementById('id_s_filter_generico_templatekey_' + $this->templateindex);";
		$js .="var requirecss = document.getElementById('id_s_filter_generico_templaterequire_css_' + $this->templateindex);";
		$js .="var requirejs = document.getElementById('id_s_filter_generico_templaterequire_js_' + $this->templateindex);";
		$js .="var defaults = document.getElementById('id_s_filter_generico_templatedefaults_' + $this->templateindex);";
		$js .="var jquery = document.getElementById('id_s_filter_generico_templaterequire_jquery_' + $this->templateindex);";
		$js .="var body = document.getElementById('id_s_filter_generico_template_' + $this->templateindex);";
		$js .="var bodyend = document.getElementById('id_s_filter_generico_templateend_' + $this->templateindex);";
		$js .="var script = document.getElementById('id_s_filter_generico_templatescript_' + $this->templateindex);";
		$js .="var style = document.getElementById('id_s_filter_generico_templatestyle_' + $this->templateindex);";


		$js .="key.value=presets[presetindex]['key'];";
		$js .="requirecss.value=presets[presetindex]['requirecss'];";
		$js .="requirejs.value=presets[presetindex]['requirejs'];";
		$js .="defaults.value=presets[presetindex]['defaults'];";
		$js .="jquery.value=presets[presetindex]['jquery'];";
		$js .="body.value=presets[presetindex]['body'];";
		$js .="bodyend.value=presets[presetindex]['bodyend'];";
		$js .="script.value=presets[presetindex]['script'];";
		$js .="style.value=presets[presetindex]['style'];";
		$js .="}";
		$js .="</script>";
		
        //build our select form
        $keys = array_keys($this->presetdata);
        $usearray = array();
        
        foreach($keys as $key){
        	$usearray[$key]=$this->presetdata[$key]['key'];
        }
        $select = html_writer::select($usearray,'filter_generico/presets','','--custom--', array('onchange'=>$jscallback . '(this.value)'));
	
		return format_admin_setting($this, $this->visiblename,
        '<div class="form-text defaultsnext">'. $js . $select . '</div>',
        $this->information, true, '','', $query);

    }
}