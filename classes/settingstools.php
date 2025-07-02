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

use filter_generico\presets_control;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * This is a class containing static functions for general PoodLL filter things like embedding recorders and managing them
 *
 * @package   filter_generico
 * @since      Moodle 3.1
 * @copyright  2016 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settingstools {

    /**
     * Make a readable template name for menus and lists etc
     *
     * @param mixed $conf
     * @param string $tindex
     * @param bool $typeprefix
     * @return string
     */
    public static function fetch_template_title($conf, $tindex, $typeprefix = true) {
        // Template display name.
        $tname = '';
        if ($conf && property_exists($conf, 'templatename_' . $tindex)) {
            $tname = $conf->{'templatename_' . $tindex};
        }
        if (empty($tname) && $conf && property_exists($conf, 'templatekey_' . $tindex)) {
            $tname = $conf->{'templatekey_' . $tindex};
        }
        if (empty($tname)) {
            $tname = $tindex;
        }

        if (!$typeprefix) {
            return $tname;
        }

        if ($conf && property_exists($conf, 'templatekey_' . $tindex) && property_exists($conf, 'template_showatto_' . $tindex) &&
                $conf->{'template_showatto_' . $tindex} > 0) {
            $templatetitle = get_string('templatepagewidgetheading', 'filter_poodll', $tname);
        } else if ($conf && property_exists($conf, 'templatekey_' . $tindex) &&
                property_exists($conf, 'template_showplayers_' . $tindex) && $conf->{'template_showplayers_' . $tindex} > 0) {
            $templatetitle = get_string('templatepageplayerheading', 'filter_poodll', $tname);
        } else {
            $templatetitle = get_string('templatepageheading', 'filter_poodll', $tname);
        }
        return $templatetitle;
    }

    /**
     * Fetch template pages
     *
     * @param mixed $conf
     */
    public static function fetch_template_pages($conf) {
        $pages = [];

        // Add the template pages.
        if ($conf && property_exists($conf, 'templatecount')) {
            $templatecount = $conf->templatecount;
        } else {
            $templatecount = \filter_generico\generico_utils::FILTER_GENERICO_TEMPLATE_COUNT;
        }

        // Fetch preset data, just once so we do nto need to repeat the call a zillion times.
        $presetdata = presets_control::fetch_presets();

        for ($tindex = 1; $tindex <= $templatecount; $tindex++) {

            // Template display name.
            if ($conf && property_exists($conf, 'templatekey_' . $tindex)) {
                $tname = $conf->{'templatekey_' . $tindex};
                if (empty($tname)) {
                    $tname = $tindex;
                }
            } else {
                $tname = $tindex;
            }

            // Template settings Page Settings (we append hidden=true as 4th param to keep out of site menu).
            $settingspage = new \admin_settingpage('filter_generico_templatepage_' . $tindex,
                    get_string('templatepageheading', 'filter_generico', $tname), 'filter/generico:managetemplates', true);

            // Template page heading.
            $settingspage->add(new \admin_setting_heading('filter_generico/templateheading_' . $tindex,
                    get_string('templateheading', 'filter_generico', $tname), ''));

            // Presets.
            $settingspage->add(new \filter_generico\presets_control('filter_generico/templatepresets_' . $tindex,
                    get_string('presets', 'filter_generico'), get_string('presets_desc', 'filter_generico'), $tindex, $presetdata));

            // Template key.
            $settingspage->add(new \admin_setting_configtext('filter_generico/templatekey_' . $tindex,
                    get_string('templatekey', 'filter_generico', $tindex),
                    get_string('templatekey_desc', 'filter_generico'),
                    '', PARAM_ALPHANUMEXT));

            // Template name.
            $settingspage->add(new \admin_setting_configtext('filter_generico/templatename_' . $tindex,
                    get_string('templatename', 'filter_generico', $tindex),
                    get_string('templatename_desc', 'filter_generico'),
                    '', PARAM_RAW));

            // Template version.
            $settingspage->add(new \admin_setting_configtext('filter_generico/templateversion_' . $tindex,
                    get_string('templateversion', 'filter_generico', $tindex),
                    get_string('templateversion_desc', 'filter_generico'),
                    '', PARAM_TEXT));

            // Template instructions.
            $settingspage->add(new \admin_setting_configtextarea('filter_generico/templateinstructions_' . $tindex,
                    get_string('templateinstructions', 'filter_generico', $tindex),
                    get_string('templateinstructions_desc', 'filter_generico'),
                    '', PARAM_RAW));

            // Template body.
            $settingspage->add(new \admin_setting_configtextarea('filter_generico/template_' . $tindex,
                    get_string('template', 'filter_generico', $tindex),
                    get_string('template_desc', 'filter_generico'), ''));

            // Template body end.
            $settingspage->add(new \admin_setting_configtextarea('filter_generico/templateend_' . $tindex,
                    get_string('templateend', 'filter_generico', $tindex),
                    get_string('templateend_desc', 'filter_generico'), ''));

            // Template defaults.
            $settingspage->add(new \admin_setting_configtextarea('filter_generico/templatedefaults_' . $tindex,
                    get_string('templatedefaults', 'filter_generico', $tindex),
                    get_string('templatedefaults_desc', 'filter_generico'), ''));

            // Template page JS heading.
            $settingspage->add(new \admin_setting_heading('filter_generico/templateheading_js' . $tindex,
                    get_string('templateheadingjs', 'filter_generico', $tname), ''));

            // Additional JS (external link).
            $settingspage->add(new \admin_setting_configtext('filter_generico/templaterequire_js_' . $tindex,
                    get_string('templaterequire_js', 'filter_generico', $tindex),
                    get_string('templaterequire_js_desc', 'filter_generico'),
                    '', PARAM_RAW, 50));

            // Template requiredjs_shim.
            $defvalue = '';
            $settingspage->add(new \admin_setting_configtext('filter_generico/templaterequire_js_shim_' . $tindex,
                    get_string('templaterequirejsshim', 'filter_generico', $tindex),
                    get_string('templaterequirejsshim_desc', 'filter_generico'),
                    $defvalue, PARAM_RAW));

            // Template amd.
            $yesno = ['0' => get_string('no'), '1' => get_string('yes')];
            $settingspage->add(new \admin_setting_configselect('filter_generico/template_amd_' . $tindex,
                    get_string('templaterequire_amd', 'filter_generico', $tindex),
                    get_string('templaterequire_amd_desc', 'filter_generico'),
                    1, $yesno));

            // Template body script.
            $setting = new \admin_setting_configtextarea('filter_generico/templatescript_' . $tindex,
                    get_string('templatescript', 'filter_generico', $tindex),
                    get_string('templatescript_desc', 'filter_generico'),
                    '', PARAM_RAW);
            $setting->set_updatedcallback('filter_generico_update_revision');
            $settingspage->add($setting);

            // Additional JS (upload)
            // see here: for integrating this https://moodle.org/mod/forum/discuss.php?d=227249 .
            $name = 'filter_generico/uploadjs' . $tindex;
            $title = get_string('uploadjs', 'filter_generico', $tindex);
            $description = get_string('uploadjs_desc', 'filter_generico');
            $settingspage->add(new \admin_setting_configstoredfile($name, $title, $description, 'uploadjs' . $tindex));

            // Template uploadjs_shim.
            $defvalue = '';
            $settingspage->add(new \admin_setting_configtext('filter_generico/uploadjs_shim_' . $tindex,
                    get_string('templateuploadjsshim', 'filter_generico', $tindex),
                    get_string('templateuploadjsshim_desc', 'filter_generico'),
                    $defvalue, PARAM_RAW));

            // Template page CSS heading.
            $settingspage->add(new \admin_setting_heading('filter_generico/templateheading_css_' . $tindex,
                    get_string('templateheadingcss', 'filter_generico', $tname), ''));

            // Additional CSS (external link).
            $settingspage->add(new \admin_setting_configtext('filter_generico/templaterequire_css_' . $tindex,
                    get_string('templaterequire_css', 'filter_generico', $tindex),
                    get_string('templaterequire_css_desc', 'filter_generico'),
                    '', PARAM_RAW, 50));

            // Template body css.
            $setting = new \admin_setting_configtextarea('filter_generico/templatestyle_' . $tindex,
                    get_string('templatestyle', 'filter_generico', $tindex),
                    get_string('templatestyle_desc', 'filter_generico'),
                    '', PARAM_RAW);
            $setting->set_updatedcallback('filter_generico_update_revision');
            $settingspage->add($setting);

            // Additional CSS (upload).
            $name = 'filter_generico/uploadcss' . $tindex;
            $title = get_string('uploadcss', 'filter_generico', $tindex);
            $description = get_string('uploadcss_desc', 'filter_generico');
            $settingspage->add(new \admin_setting_configstoredfile($name, $title, $description, 'uploadcss' . $tindex));

            // Dataset heading.
            $settingspage->add(new \admin_setting_heading('filter_generico/datasetheading_' . $tindex,
                get_string('datasetheading', 'filter_generico'), ''));

            // Dataset.
            $settingspage->add(new \admin_setting_configtextarea('filter_generico/dataset_' . $tindex,
                    get_string('dataset', 'filter_generico', $tindex),
                    get_string('dataset_desc', 'filter_generico'),
                    '', PARAM_RAW));

            // Dataset vars.
            $settingspage->add(new \admin_setting_configtext('filter_generico/datasetvars_' . $tindex,
                    get_string('datasetvars', 'filter_generico', $tindex),
                    get_string('datasetvars_desc', 'filter_generico'),
                    '', PARAM_RAW, 50));

            // Alternate content heading.
            $settingspage->add(new \admin_setting_heading('filter_generico/alternateheading_' . $tindex,
                get_string('alternateheading', 'filter_generico'), ''));

            // Alternative content.
            $settingspage->add(new \admin_setting_configtextarea('filter_generico/templatealternate_' . $tindex,
                    get_string('templatealternate', 'filter_generico', $tindex),
                    get_string('templatealternate_desc', 'filter_generico'),
                    '', PARAM_RAW));

            // Alternative content end.
            $settingspage->add(new \admin_setting_configtextarea('filter_generico/templatealternate_end_' . $tindex,
                    get_string('templatealternate_end', 'filter_generico', $tindex),
                    get_string('templatealternate_end_desc', 'filter_generico'),
                    '', PARAM_RAW));

            // Security heading.
            $settingspage->add(new \admin_setting_heading('filter_generico/securityheading_' . $tindex,
                get_string('securityheading', 'filter_generico'), ''));

            $settingspage->add(new \admin_setting_configtext('filter_generico/allowedcontexts_' . $tindex,
                get_string('allowedcontexts', 'filter_generico'),
                get_string('allowedcontexts_desc', 'filter_generico'),
                ''
            ));

            $settingspage->add(new \admin_setting_configtext('filter_generico/allowedcontextids_' . $tindex,
                get_string('allowedcontextids', 'filter_generico'),
                get_string('allowedcontextids_desc', 'filter_generico'),
                ''
            ));

            $pages[] = $settingspage;
        }

        return $pages;
    }
}
