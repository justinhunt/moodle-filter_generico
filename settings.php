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
 * Generico settings
 *
 * @package    filter_generico
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use  filter_generico\constants;

$settings = null;

if (has_capability('filter/generico:managetemplates', context_system::instance())) {
    global $PAGE;

    // Add folder in property tree for settings pages.
    $genericocategoryname = 'generico_category';
    $genericocategory = new admin_category($genericocategoryname, 'Generico');
    $ADMIN->add('filtersettings', $genericocategory);
    $conf = get_config('filter_generico');

    // Add the common settings page
    // we changed this to use the default settings id for the top page. This way in the settings link on the manage filters
    // page, we will arrive here. Else the link will show there, but it will error out if clicked.
    $settingspage = new admin_settingpage('filtersettinggenerico', get_string('commonpageheading', 'filter_generico'));

    $settingspage->add(new admin_setting_configtext('filter_generico/templatecount',
            get_string('templatecount', 'filter_generico'),
            get_string('templatecount_desc', 'filter_generico'),
            \filter_generico\generico_utils::FILTER_GENERICO_TEMPLATE_COUNT, PARAM_INT, 20));

    // Cloud poodll credentials.
    $settingspage->add(new admin_setting_heading('filter_generico_cpapi_settings', get_string('cpapi_heading', 'filter_generico'),
            get_string('cpapi_heading_desc', 'filter_generico')));
    $settingspage->add(new admin_setting_configtext('filter_generico/cpapiuser', get_string('cpapiuser', 'filter_generico'),
            get_string('cpapiuser_details', 'filter_generico'), ''));
    // We show a summary of the users apps if we can get the info.
    $apiuser = get_config(constants::MOD_FRANKY, 'cpapiuser');
    $apisecret = get_config(constants::MOD_FRANKY, 'cpapisecret');
    if ($apiuser && $apisecret) {
        $gu = new \filter_generico\generico_utils();
        $tokeninfo = $gu->fetch_token_for_display($apiuser, $apisecret);
    } else {
        $tokeninfo = get_string('cpapisecret_details', constants::MOD_FRANKY);
    }
    $settingspage->add(new admin_setting_configtext('filter_generico/cpapisecret', get_string('cpapisecret', 'filter_generico'),
            $tokeninfo, ''));

    // Add page to category.
    $ADMIN->add($genericocategoryname, $settingspage);

    $genericotemplatesadminsettings = new admin_externalpage('genericotemplatesadmin', get_string('templates', 'filter_generico'),
            $CFG->wwwroot . '/filter/generico/genericotemplatesadmin.php', 'filter/generico:managetemplates');

    $ADMIN->add($genericocategoryname, $genericotemplatesadminsettings);


    // Templates.
    if ($ADMIN->fulltree) {
        $templatepages = \filter_generico\settingstools::fetch_template_pages($conf);
        foreach ($templatepages as $templatepage) {
            $ADMIN->add($genericocategoryname, $templatepage);
        }
    }

}
