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
    /** @var string user firstname placeholder */
    const USER_FIRSTNAME = 'user_firstname';

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
                    self::USER_FIRSTNAME,
                    self::WWWROOT,
                ],
                'outputnotcontains' => [],
            ],
            '@@WWWROOT@@ override (not allowed)' => [
                'name' => 'welcomeuser',
                'template' => 'My url is @@WWWROOT@@',
                'input' => '{GENERICO:type="welcomeuser",WWWROOT=mycustomwwwroot.invalid}',
                'outputcontains' => [
                    self::WWWROOT,
                ],
                'outputnotcontains' => [
                    'mycustomwwwroot.invalid',
                ],
            ],
            'Template injection via @@USER@@ (not allowed)' => [
                'name' => 'welcomeuser',
                'template' => 'My name is @@USER:firstname@@',
                'input' => '{GENERICO:type="welcomeuser",USER:firstname="@@WWWROOT@@"}',
                'outputcontains' => [
                    self::USER_FIRSTNAME,
                ],
                'outputnotcontains' => [
                    self::WWWROOT,
                ],
            ],
            'Template injection via @@AUTOID@@ (not allowed)' => [
                'name' => 'welcomeuser',
                'template' => 'My id is @@AUTOID@@',
                'input' => '{GENERICO:type="welcomeuser",AUTOID="@@WWWROOT@@"}',
                'outputcontains' => [],
                'outputnotcontains' => [
                    self::WWWROOT,
                ],
            ],
            '@@AUTOID@@ override (not allowed)' => [
                'name' => 'welcomeuser',
                'template' => 'My id is @@AUTOID@@',
                'input' => '{GENERICO:type="welcomeuser",AUTOID="mycustomautoid"}',
                'outputcontains' => [
                    // Difficult to simulate AUTOID id so not checking for it here.
                ],
                'outputnotcontains' => [
                    'mycustomautoid',
                ],
            ],
            '@@MOODLEPAGEID@@ override (not allowed)' => [
                'name' => 'welcomeuser',
                'template' => 'My id is @@MOODLEPAGEID@@',
                'input' => '{GENERICO:type="welcomeuser",MOODLEPAGEID="mypageid"}',
                'outputcontains' => [
                    // Difficult to simulate page id so not checking for it here.
                ],
                'outputnotcontains' => [
                    'mypageid',
                ],
            ],
        ];
    }

    /**
     * Replace value with placeholder (if it is one)
     *
     * @param string $value
     * @return string
     */
    private function replace_placeholder(string $value): string {
        global $USER, $CFG;

        switch ($value) {
            case self::USER_FIRSTNAME:
                return ucfirst($USER->firstname);
            case self::WWWROOT:
                return $CFG->wwwroot;
        }

        // Not a placeholder.
        return $value;
    }

    /**
     * Filter tests
     *
     * @dataProvider filter_provider
     * @param string $name
     * @param string $template
     * @param string $input
     * @param array $outputcontains
     * @param array $outputnotcontains
     */
    public function test_filter(string $name, string $template, string $input, array $outputcontains,
        array $outputnotcontains): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        filter_set_global_state('generico', TEXTFILTER_ON);

        set_config('templatekey_1', $name, 'filter_generico');
        set_config('template_1', $template, 'filter_generico');
        $out = format_text($input);

        // Check output contains.
        foreach ($outputcontains as $expectedoutput) {
            $expectedoutput = $this->replace_placeholder($expectedoutput);
            $this->assertStringContainsString($expectedoutput, $out);
        }

        // Check output NOT contains.
        foreach ($outputnotcontains as $expectednotoutput) {
            $expectednotoutput = $this->replace_placeholder($expectednotoutput);
            $this->assertStringNotContainsString($expectednotoutput, $out);
        }
    }
}
