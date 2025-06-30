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
 * Generico utilities tests
 *
 * @package    filter_generico
 * @subpackage generico
 * @copyright  2025 Catalyst IT Australia
 * @author     Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_generico\generico_utils
 */
final class utils_test extends advanced_testcase {
    /** @var string course context key */
    private const CONTEXT_KEY_COURSE = 'course';

    /** @var string mod page context key */
    private const CONTEXT_KEY_MOD_PAGE = 'mod_page';

    /** @var string contextid placeholder */
    private const PLACEHOLDER_CONTEXTID = 'placeholder_contextid';

    /**
     * Provides values to test_is_context_allowed_contextkey
     *
     * @return array
     */
    public static function is_context_allowed_contextkey_provider(): array {
        return [
            'not set, allowed' => [
                'contextkey' => self::CONTEXT_KEY_COURSE,
                'allowedcontexts' => '',
                'expectedallowed' => true,
            ],
            'not set (false), allowed' => [
                'contextkey' => self::CONTEXT_KEY_COURSE,
                'allowedcontexts' => false, // Simulates default / not set.
                'expectedallowed' => true,
            ],
            'course, allowed' => [
                'contextkey' => self::CONTEXT_KEY_COURSE,
                'allowedcontexts' => 'course',
                'expectedallowed' => true,
            ],
            'course and system, allowed' => [
                'contextkey' => self::CONTEXT_KEY_COURSE,
                'allowedcontexts' => 'course,system',
                'expectedallowed' => true,
            ],
            'garbage and whitespace in config, but still allowed' => [
                'contextkey' => self::CONTEXT_KEY_COURSE,
                'allowedcontexts' => ', + ,  a ,course        ,      system    ',
                'expectedallowed' => true,
            ],
            'course context but system config, not allowed' => [
                'contextkey' => self::CONTEXT_KEY_COURSE,
                'allowedcontexts' => 'system',
                'expectedallowed' => false,
            ],
            'mod activity context, allowed' => [
                'contextkey' => self::CONTEXT_KEY_MOD_PAGE,
                'allowedcontexts' => 'mod_page',
                'expectedallowed' => true,
            ],
            'mod activity context, not allowed' => [
                'contextkey' => self::CONTEXT_KEY_MOD_PAGE,
                'allowedcontexts' => 'course',
                'expectedallowed' => false,
            ],
            'no context given, not allowed' => [
                'contextkey' => null,
                'allowedcontexts' => 'course',
                'expectedallowed' => false,
            ],
            'no context given, allowed' => [
                'contextkey' => null,
                'allowedcontexts' => '',
                'expectedallowed' => true,
            ],
        ];
    }

    /**
     * Tests is_context_allowed function
     *
     * @param string|null $contextkey which context to use for testing
     * @param mixed $allowedcontexts config to set for allowed contexts
     * @param bool $expectedallowed if expected to be allowed to run in this context
     * @dataProvider is_context_allowed_contextkey_provider
     */
    public function test_is_context_allowed_contextkey(?string $contextkey, $allowedcontexts, bool $expectedallowed): void {
        $this->resetAfterTest();
        $context = $contextkey != null ? $this->context_from_key($contextkey) : null;
        set_config('allowedcontexts_1', $allowedcontexts, 'filter_generico');
        $this->assertEquals($expectedallowed, generico_utils::is_context_allowed($context, 1));
    }

    /**
     * Tests is_context_allowed context level filtering when actually filtering text
     *
     * @param string|null $contextkey which context to use for testing
     * @param mixed $allowedcontexts config to set for allowed contexts
     * @param bool $expectedallowed if expected to be allowed to run in this context
     * @dataProvider is_context_allowed_contextkey_provider
     */
    public function test_is_context_allowed_contextkey_when_filtering(?string $contextkey, $allowedcontexts,
        bool $expectedallowed): void {
        global $PAGE, $CFG;
        $this->resetAfterTest();
        $context = $contextkey != null ? $this->context_from_key($contextkey) : null;
        set_config('allowedcontexts_1', $allowedcontexts, 'filter_generico');

        // Render some text in this context, and check if we get ignored, or allowed.
        $this->setAdminUser();
        $PAGE->set_context($context);
        filter_set_global_state('generico', TEXTFILTER_ON);
        set_config('templatekey_1', 'welcomeuser', 'filter_generico');
        set_config('template_1', "@@WWWROOT@@", 'filter_generico');
        $out = format_text('{GENERICO:type="welcomeuser"}');

        if ($expectedallowed) {
            $this->assertStringContainsString($CFG->wwwroot, $out);
        } else {
            $this->assertStringNotContainsString($CFG->wwwroot, $out);
        }
    }

    /**
     * Provides values to test_is_context_allowed_contextid
     *
     * @return array
     */
    public static function is_context_allowed_contextid_provider(): array {
        return [
            'empty, allowed' => [
                'allowedcontextids' => '',
                'expectedallowed' => true,
            ],
            'empty whitespace, allowed' => [
                'allowedcontextids' => '    , ,   ,  ',
                'expectedallowed' => true,
            ],
            'false config, allowed' => [
                'allowedcontextids' => false,
                'expectedallowed' => true,
            ],
            'contextid included (with garbage), allowed' => [
                'allowedcontextids' => 'aa,  ,' . self::PLACEHOLDER_CONTEXTID . ',  ',
                'expectedallowed' => true,
            ],
        ];
    }

    /**
     * Test is_context_allowed when using the contextid allowlist.
     *
     * @param mixed $allowedcontextids config to set for allowed context ids
     * @param bool $expectedallowed
     * @dataProvider is_context_allowed_contextid_provider
     */
    public function test_is_context_allowed_contextid($allowedcontextids, bool $expectedallowed): void {
        $this->resetAfterTest();

        // We just use any context, lets go with course.
        $context = $this->context_from_key(self::CONTEXT_KEY_COURSE);

        // Replace any placeholders.
        $allowedcontextids = str_replace(self::PLACEHOLDER_CONTEXTID, $context->id, $allowedcontextids);
        set_config('allowedcontextids_1', $allowedcontextids, 'filter_generico');

        $this->assertEquals($expectedallowed, generico_utils::is_context_allowed($context, 1));
    }

    /**
     * Tests is_context_allowed's contextid filtering when actually filtering text
     *
     * @param mixed $allowedcontextids config to set for allowed context ids
     * @param bool $expectedallowed if expected to be allowed to run in this context
     * @dataProvider is_context_allowed_contextid_provider
     */
    public function test_is_context_allowed_contextid_when_filtering($allowedcontextids, bool $expectedallowed): void {
        global $PAGE, $CFG;
        $this->resetAfterTest();
        $context = $this->context_from_key(self::CONTEXT_KEY_COURSE);

        // Replace any placeholders.
        $allowedcontextids = str_replace(self::PLACEHOLDER_CONTEXTID, $context->id, $allowedcontextids);
        set_config('allowedcontextids_1', $allowedcontextids, 'filter_generico');

        // Render some text in this context, and check if we get ignored, or allowed.
        $this->setAdminUser();
        $PAGE->set_context($context);
        filter_set_global_state('generico', TEXTFILTER_ON);
        set_config('templatekey_1', 'welcomeuser', 'filter_generico');
        set_config('template_1', "@@WWWROOT@@", 'filter_generico');
        $out = format_text('{GENERICO:type="welcomeuser"}');

        if ($expectedallowed) {
            $this->assertStringContainsString($CFG->wwwroot, $out);
        } else {
            $this->assertStringNotContainsString($CFG->wwwroot, $out);
        }
    }

    /**
     * Generate a context from a given key
     *
     * @param string $contextkey one of CONTEXT_KEY_* constants
     * @return \context
     */
    private function context_from_key(string $contextkey): \context {
        switch ($contextkey) {
            case self::CONTEXT_KEY_COURSE:
                $course = $this->getDataGenerator()->create_course();
                return \context_course::instance($course->id);
            case self::CONTEXT_KEY_MOD_PAGE:
                $course = $this->getDataGenerator()->create_course();
                $generator = $this->getDataGenerator()->get_plugin_generator('mod_page');
                $pageinstance = $generator->create_instance(['course' => $course->id]);
                return \context_module::instance($pageinstance->cmid);
            default:
                throw new \coding_exception("Unknown contextkey " . $contextkey);
        }
    }
}
