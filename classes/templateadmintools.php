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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/adminlib.php');

/**
 * Template admin tools
 *
 * @package    filter_generico
 * @subpackage generico
 * @copyright  2014 Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class templateadmintools {

    /**
     * Returns an HTML string
     *
     * @return string Returns an HTML string
     */
    public static function fetch_template_table() {
        global $OUTPUT, $CFG;
        $conf = get_config('filter_generico');
        $templatedetails = self::fetch_template_details($conf);
        $haveupdates = false;

        $table = new \html_table();
        $table->id = 'filter_generico_template_list';
        $table->head = [
                get_string('name'),
                get_string('key', 'filter_generico'),
                get_string('version'),
                get_string('description'),
        ];
        $table->headspan = [1, 1, 1, 1];
        $table->colclasses = [
                'templatenamecol', 'templatekeycol', 'templateversioncol', 'templateinstructionscol',
        ];

        // Loop through templates and add to table.
        foreach ($templatedetails as $item) {
            $row = new \html_table_row();

            $titlelink = \html_writer::link($item->url, $item->title);
            $titlecell = new \html_table_cell($titlelink);

            // Templatekey cell.
            $templatekeycell = new \html_table_cell($item->templatekey);

            // Version cell.
            $updateversion = presets_control::template_has_update($item->index);
            if ($updateversion) {
                $button = new \single_button(
                        new \moodle_url($CFG->wwwroot . '/filter/generico/genericotemplatesadmin.php',
                                ['updatetemplate' => $item->index]),
                        get_string('updatetoversion', 'filter_generico', $updateversion));
                $updatehtml = $OUTPUT->render($button);
                $versioncell = new \html_table_cell($item->version . $updatehtml);
                $haveupdates = true;
            } else {
                $versioncell = new \html_table_cell($item->version);
            }

            $instructionscell = new \html_table_cell($item->instructions);

            $row->cells = [
                    $titlecell, $templatekeycell, $versioncell, $instructionscell,
            ];
            $table->data[] = $row;
        }

        $templatetable = \html_writer::table($table);

        // If have_updates.
        $updateallhtml = '';
        if ($haveupdates) {
            $allbutton = new \single_button(
                    new \moodle_url($CFG->wwwroot . '/filter/generico/genericotemplatesadmin.php', ['updatetemplate' => -1]),
                    get_string('updateall', 'filter_generico'));
            $updateallhtml = $OUTPUT->render($allbutton);
        }

        return $updateallhtml . $templatetable;

    }// End of output html.

    /**
     * Fetch template details
     *
     * @param mixed $conf
     * @return array
     */
    public static function fetch_template_details($conf) {
        $ret = [];

        // Get template count.
        if ($conf && property_exists($conf, 'templatecount')) {
            $templatecount = $conf->templatecount;
        } else {
            $templatecount = generico_utils::FILTER_GENERICO_TEMPLATE_COUNT;
        }
        for ($tindex = 1; $tindex <= $templatecount; $tindex++) {

            // Template display name.
            if ($conf && property_exists($conf, 'templatename_' . $tindex)) {
                $templatetitle = $conf->{'templatename_' . $tindex};
                if (empty($templatetitle)) {
                    if (property_exists($conf, 'templatekey_' . $tindex)) {
                        $templatetitle = $conf->{'templatekey_' . $tindex};
                    }
                    if (empty($templatetitle)) {
                        $templatetitle = $tindex;
                    }
                }
            } else if ($conf && property_exists($conf, 'templatekey_' . $tindex)) {
                $templatetitle = $conf->{'templatekey_' . $tindex};
                if (empty($templatetitle)) {
                    $templatetitle = $tindex;
                }
            } else {
                $templatetitle = $tindex;
            }

            $templatedetails = new \stdClass();
            $templatedetails->index = $tindex;
            $templatedetails->title = $templatetitle;

            if ($conf && property_exists($conf, 'templatekey_' . $tindex)) {
                $templatedetails->templatekey = $conf->{'templatekey_' . $tindex};;
            } else {
                $templatedetails->templatekey = '';
            }

            $templatedetails->version = "";
            if (property_exists($conf, 'templateversion_' . $tindex)) {
                $templatedetails->version = $conf->{'templateversion_' . $tindex};
            }

            $templatedetails->instructions = "";
            if (property_exists($conf, 'templateinstructions_' . $tindex)) {
                $templatedetails->instructions = $conf->{'templateinstructions_' . $tindex};
            }

            $templatedetails->url =
                    new \moodle_url('/admin/settings.php', ['section' => 'filter_generico_templatepage_' . $tindex]);
            $ret[] = $templatedetails;
        }
        return $ret;
    }// End of fetch_templates function.
}// End of class.
