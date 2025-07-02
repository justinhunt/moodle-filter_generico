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

/**
 * Template script generator
 *
 * @package    filter_generico
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_script_generator {
    /** @var mixed int index of template */
    public $templateindex;

    /**
     * Constructor
     * @param mixed $templateindex
     */
    public function __construct($templateindex) {
        $this->templateindex = $templateindex;
    }

    /**
     * Get template script
     * @return string
     */
    public function get_template_script() {
        global $CFG;

        $tindex = $this->templateindex;
        $conf = get_config('filter_generico');
        $template = $conf->{'template_' . $tindex};

        // Are we AMD and Moodle 2.9 or more?
        $requireamd = $conf->{'template_amd_' . $tindex} && $CFG->version >= 2015051100;

        // Get presets.
        $thescript = $conf->{'templatescript_' . $tindex};

        // Fetch all the variables we use (make sure we have no duplicates).
        $allvariables = generico_utils::fetch_variables($thescript . $template);
        $uniquevariables = array_unique($allvariables);

        // These props are in the opts array in the allopts[] array on the page
        // since we are writing the JS we write the opts['name'] into the js, but
        // have to remove quotes from template eg "@@VAR@@" => opts['var'] //NB no quotes.
        // thats worth knowing for the admin who writed the JS load code for the template.
        foreach ($uniquevariables as $propname) {
            // Case: single quotes.
            $thescript = str_replace("'@@" . $propname . "@@'", 'opts["' . $propname . '"]', $thescript);
            // Case: double quotes.
            $thescript = str_replace('"@@' . $propname . '@@"', "opts['" . $propname . "']", $thescript);
            // Case: no quotes.
            $thescript = str_replace('@@' . $propname . '@@', "opts['" . $propname . "']", $thescript);
        }

        if ($requireamd) {

            // Figure out if this is https or http. We don't want to scare the browser.
            $scheme = 'http:';
            if (strpos(strtolower($CFG->wwwroot), 'https') === 0) {
                $scheme = 'https:';
            }

            // This is for loading as dependencies the uploaded or linked files
            // massage the js URL depending on schemes and rel. links etc. Then insert it.
            $requiredjs = $conf->{'templaterequire_js_' . $tindex};
            $requiredjsshim = trim($conf->{'templaterequire_js_shim_' . $tindex});
            if ($requiredjs) {
                if (strpos($requiredjs, '//') === 0) {
                    $requiredjs = $scheme . $requiredjs;
                } else if (strpos($requiredjs, '/') === 0) {
                    $requiredjs = $CFG->wwwroot . $requiredjs;
                }
            }

            // If we have an uploaded JS file, then lets include that.
            $uploadjsfile = $conf->{'uploadjs' . $tindex};
            $uploadjsshim = trim($conf->{'uploadjs_shim_' . $tindex});
            if ($uploadjsfile) {
                $uploadjs = generico_utils::setting_file_url($uploadjsfile, 'uploadjs' . $tindex);
            }

            // Create the dependency stuff in the output js.
            $requires = [];
            $params = [];

            // These arrays are used for shimming.
            $shimkeys = [];
            $shimpaths = [];
            $shimexports = [];

            // Current key.
            $currentkey = $conf->{'templatekey_' . $tindex};

            // If we have a url based required js
            // either load it, or shim and load it.
            if ($requiredjs) {
                if ($requiredjsshim != '') {
                    $shimkeys[] = $currentkey . '-requiredjs';

                    // Remove .js from end of js filepath if its there.
                    if (strrpos($requiredjs, '.js') == (strlen($requiredjs) - 3)) {
                        $requiredjs = substr($requiredjs, 0, -3);
                    }

                    $shimpaths[] = $requiredjs;
                    $shimexports[] = $requiredjsshim;
                    $requires[] = "'" . $currentkey . '-requiredjs' . "'";
                    $params[] = $requiredjsshim;
                } else {
                    $requires[] = "'" . $requiredjs . "'";
                    $params[] = "requiredjs_" . $currentkey;
                }
            }

            // If we have an uploadedjs library
            // either load it, or shim and load it.
            if ($uploadjsfile) {
                if ($uploadjsshim != '') {
                    $shimkeys[] = $currentkey . '-uploadjs';

                    // Remove .js from end of js filepath if its there.
                    if (strrpos($uploadjs, '.js') == (strlen($uploadjs) - 3)) {
                        $uploadjs = substr($uploadjs, 0, -3);
                    }

                    $shimpaths[] = $uploadjs;
                    $shimexports[] = $uploadjsshim;
                    $requires[] = "'" . $currentkey . '-uploadjs' . "'";
                    $params[] = $uploadjsshim;
                } else {
                    $requires[] = "'" . $uploadjs . "'";
                    $params[] = "uploadjs_" . $currentkey;
                }
            }

            // If we have a shim, lets build the javascript for that
            // actually we build a php object first, and then we will json_encode it.
            $theshim = $this->build_shim_function($currentkey, $shimkeys, $shimpaths, $shimexports);

            // Load a different jquery based on path if we are shimming
            // this is because, sigh, Moodle used no conflict for jquery, but
            // shimmed plugins rely on jquery n global scope
            // see: http://www.requirejs.org/docs/jquery.html#noconflictmap
            // so we add a separate load of jquery with name '[currentkey]-jquery' and export it as '$', and don't use the
            // already set up (by mooodle and AMD) 'jquery' path.
            // we add jquery to beginning of requires and params using unshift. But the end would be find too.
            if (!empty($shimkeys)) {
                array_unshift($requires, "'" . $currentkey . '-jquery' . "'");
                array_unshift($params, '$');
            } else {
                array_unshift($requires, "'" . 'jquery' . "'");
                array_unshift($params, '$');
            }

            // Assemble the final javascript to pass to browser.
            $thefunction = "define('filter_generico_d" . $tindex . "',[" . implode(',', $requires) . "], function(" .
                    implode(',', $params) . "){ ";
            $thefunction .= "return function(opts){" . $thescript . " \r\n}; });";
            $returnjs = $theshim . $thefunction;

            // If not AMD return regular JS.
        } else {

            $returnjs = "if(typeof filter_generico_extfunctions == 'undefined'){filter_generico_extfunctions={};}";
            $returnjs .= "filter_generico_extfunctions['" . $tindex . "']= function(opts) {" . $thescript . " \r\n};";
        }
        return $returnjs;
    }

    /**
     * Build shim functions
     * @param string $currentkey
     * @param array $shimkeys
     * @param array $shimpaths
     * @param array $shimexports
     * @return string
     */
    protected function build_shim_function($currentkey, $shimkeys, $shimpaths, $shimexports) {
        global $CFG;

        $theshim = "";
        $theshimtemplate = "requirejs.config(@@THESHIMCONFIG@@);";
        if (!empty($shimkeys)) {
            $paths = new \stdClass();
            $shim = new \stdClass();

            // Add a path to  a separetely loaded jquery for shimmed libraries.
            $paths->{$currentkey . '-jquery'} = $CFG->wwwroot . '/filter/generico/jquery/jquery-3.7.0.min';
            $jqueryshimconfig = new \stdClass();
            $jqueryshimconfig->exports = '$';
            $shim->{$currentkey . '-jquery'} = $jqueryshimconfig;

            for ($i = 0; $i < count($shimkeys); $i++) {
                $paths->{$shimkeys[$i]} = $shimpaths[$i];
                $oneshimconfig = new \stdClass();
                $oneshimconfig->exports = $shimexports[$i];
                $oneshimconfig->deps = [$currentkey . '-jquery'];
                $shim->{$shimkeys[$i]} = $oneshimconfig;
            }

            // Build the actual function that will set up our shim
            // we use php object -> json to kep it simple.
            // But its still not simple.
            $theshimobject = new \stdClass();
            $theshimobject->paths = $paths;
            $theshimobject->shim = $shim;
            $theshimconfig = json_encode($theshimobject, JSON_UNESCAPED_SLASHES);
            $theshim = str_replace('@@THESHIMCONFIG@@', $theshimconfig, $theshimtemplate);
        }
        return $theshim;
    }
}
