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

use advanced_testcase;

/**
 * Generico filter tests
 *
 * @package    filter_generico
 * @subpackage generico
 * @copyright  2025 Catalyst IT Australia
 * @author     Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_generico\text_filter
 */
final class filter_test extends advanced_testcase {
    /** @var string username placeholder */
    const USERNAME = 'username';

    /** @var string wwwroot placeholder */
    const WWWROOT = 'wwwroot';

    /**
     * Provides filter testcases
     * @return array
     */
    public static function filter_provider(): array {
        return [
            'user firstname and wwwroot' => [
                'name' => 'welcomeuser',
                'template' => 'Hi @@USER:firstname@@. <a href="@@WWWROOT@@/blocks/profilepic/view.php">',
                'input' => '{GENERICO:type="welcomeuser"}',
                'outputcontains' => [
                    self::USERNAME,
                    self::WWWROOT,
                ],
            ],
        ];
    }

    /**
     * Filter tests
     *
     * @dataProvider filter_provider
     * @param string $name
     * @param string $template
     * @param string $input
     * @param array $outputcontains
     */
    public function test_filter(string $name, string $template, string $input, array $outputcontains): void {
        global $USER, $CFG;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        filter_set_global_state('generico', TEXTFILTER_ON);

        set_config('templatekey_1', $name, 'filter_generico');
        set_config('template_1', $template, 'filter_generico');
        $out = format_text($input);

        foreach ($outputcontains as $expectedoutput) {
            // Replace any placeholders with the actual values.
            switch ($expectedoutput) {
                case self::USERNAME:
                    $expectedoutput = ucfirst($USER->username);
                    break;
                case self::WWWROOT:
                    $expectedoutput = $CFG->wwwroot;
                    break;
            }
            $this->assertStringContainsString($expectedoutput, $out);
        }
    }
}
