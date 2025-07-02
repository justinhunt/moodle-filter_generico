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

namespace filter_generico;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/adminlib.php');

/**
 * Presets control
 *
 * @package    filter_generico
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class presets_control extends \admin_setting {
    /**
     * @var mixed int index of template
     * */
    public $templateindex;

    /**
     * @var array template data for spec index
     * */
    public $presetdata;

    /**
     * @var string $visiblename
     */
    public $visiblename;

    /**
     * @var string $information
     */
    public $information;

    /**
     * not a setting, just text
     *
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in
     *         config_plugins.
     * @param string $visiblename
     * @param string $information text in box
     * @param mixed $templateindex
     * @param mixed $presetdata
     */
    public function __construct($name, $visiblename, $information, $templateindex, $presetdata=false) {
        $this->nosave = true;
        $this->templateindex = $templateindex;
        if (!$presetdata) {
            $presetdata = $this->fetch_presets();
        }
        $this->presetdata = $presetdata;
        $this->visiblename = $visiblename;
        $this->information = $information;
        parent::__construct($name, $visiblename, $information, $templateindex);
    }

    /**
     * Always returns true
     *
     * @return bool Always returns true
     */
    public function get_setting() {
        return true;
    }

    /**
     * Always returns true
     *
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Never write settings
     *
     * @param mixed $data
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
        // Do not write any setting.
        return '';
    }

    /**
     * Returns an HTML string
     *
     * @param mixed $data
     * @param string $query
     * @return string Returns an HTML string
     */
    public function output_html($data, $query = '') {
        global $PAGE;

        // Build our select form.
        $keys = array_keys($this->presetdata);
        $usearray = [];

        foreach ($keys as $key) {
            $name = $this->presetdata[$key]['name'];
            if (empty($name)) {
                $name = $key;
            }
            $usearray[$key] = $name;
        }

        $presetsjson = json_encode($this->presetdata);
        $presetscontrol = \html_writer::tag('input', '',
                ['id' => 'id_s_filter_generico_presetdata_' . $this->templateindex, 'type' => 'hidden',
                        'value' => $presetsjson]);

        // Add javascript handler for presets.
        $PAGE->requires->js_call_amd('filter_generico/generico_presets_amd',
                'init', [['templateindex' => $this->templateindex]]);

        $select = \html_writer::select($usearray, 'filter_generico/presets', '', '--custom--');

        $dragdropsquare = \html_writer::tag('div', get_string('bundle', 'filter_generico'),
                ['id' => 'id_s_filter_generico_dragdropsquare_' . $this->templateindex,
                        'class' => 'filter_generico_dragdropsquare']);

        return format_admin_setting($this, $this->visiblename,
                $dragdropsquare . '<div class="form-text defaultsnext">' . $presetscontrol . $select . '</div>',
                $this->information, true, '', '', $query);

    }

    /**
     * Parse preset template
     *
     * @param \SplFileInfo $fileinfo
     * @return array|false
     */
    protected static function parse_preset_template(\SplFileInfo $fileinfo) {
        $file = $fileinfo->openFile("r");
        $content = "";
        while (!$file->eof()) {
            $content .= $file->fgets();
        }
        $presetobject = json_decode($content);
        if ($presetobject && is_object($presetobject)) {
            return get_object_vars($presetobject);
        } else {
            return false;
        }
    }// End of parse preset template.

    /**
     * Fetch presets
     * @return array
     */
    public static function fetch_presets() {
        global $CFG, $PAGE;
        // Init return array.
        $ret = [];
        $dirs = [];

        // We search the Generico "presets" and the themes "generico" folders for presets.
        $genericopresetsdir = $CFG->dirroot . '/filter/generico/presets';
        $themegenericodir = $PAGE->theme->dir . '/generico';
        if (file_exists($genericopresetsdir)) {
            $dirs[] = new \DirectoryIterator($genericopresetsdir);
        }
        if (file_exists($themegenericodir)) {
            $dirs[] = new \DirectoryIterator($themegenericodir);
        }
        foreach ($dirs as $dir) {
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot() && !$fileinfo->isDir()) {
                    $preset = self::parse_preset_template($fileinfo);
                    if ($preset) {
                        $ret[] = $preset;
                    }
                }
            }
        }
        return $ret;
    }// End of fetch presets function.

    /**
     * Set preset to config
     *
     * @param array $preset
     * @param string $templateindex
     */
    public static function set_preset_to_config($preset, $templateindex) {
        $fields = [];
        $fields['name'] = 'templatename';
        $fields['key'] = 'templatekey';
        $fields['instructions'] = 'templateinstructions';
        $fields['body'] = 'template';
        $fields['bodyend'] = 'templateend';
        $fields['requirecss'] = 'templaterequire_css';
        $fields['requirejs'] = 'templaterequire_js';
        $fields['shim'] = 'templaterequire_js_shim';
        $fields['version'] = 'templateversion';
        $fields['defaults'] = 'templatedefaults';
        $fields['amd'] = 'template_amd';
        $fields['script'] = 'templatescript';
        $fields['style'] = 'templatestyle';
        $fields['dataset'] = 'dataset';
        $fields['datavars'] = 'datavars';

        foreach ($fields as $fieldkey => $fieldname) {
            if (array_key_exists($fieldkey, $preset)) {
                $fieldvalue = $preset[$fieldkey];
            } else {
                $fieldvalue = '';
            }
            set_config($fieldname . '_' . $templateindex, $fieldvalue, 'filter_generico');
        }
    }// End of set_preset_to_config.

    /**
     * If template has update
     *
     * @param string $templateindex
     * @return mixed|false
     */
    public static function template_has_update($templateindex) {
        $presets = self::fetch_presets();
        foreach ($presets as $preset) {
            if (get_config('filter_generico', 'templatekey_' . $templateindex) == $preset['key']) {
                $templateversion = get_config('filter_generico', 'templateversion_' . $templateindex);
                $presetversion = $preset['version'];
                if (version_compare($presetversion, $templateversion) > 0) {
                    return $presetversion;
                }// End of version compare.
            }//  End of if keys match.
        }// End of presets loop.
        return false;
    }

    /**
     * Update all templates
     */
    public static function update_all_templates() {
        $templatecount = get_config('filter_generico', 'templatecount');
        $updatecount = 0;
        for ($x = 1; $x < $templatecount + 1; $x++) {
            $updated = self::update_template($x);
            if ($updated) {
                $updatecount++;
            }
        }// End of templatecount loop.
        return $updatecount;
    }// End of function.

    /**
     * Update template
     *
     * @param string $templateindex
     * @return bool
     */
    public static function update_template($templateindex) {
        $updated = false;
        $presets = self::fetch_presets();
        foreach ($presets as $preset) {
            if (get_config('filter_generico', 'templatekey_' . $templateindex) == $preset['key']) {
                $templateversion = get_config('filter_generico', 'templateversion_' . $templateindex);
                $presetversion = $preset['version'];
                if (version_compare($presetversion, $templateversion) > 0) {
                    self::set_preset_to_config($preset, $templateindex);
                    $updated = true;
                }// End of version compare.
                return $updated;
            }// End of if keys match.
        }// End of presets loop.
        return false;
    }// End of function.

}
