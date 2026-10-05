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
 * Plugin administration pages are defined here.
 *
 * @package    local_quizwizard
 * @copyright  2026 Quiz Wizard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_quizwizard_settings', new lang_string('pluginname', 'local_quizwizard'));

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'local_quizwizard/aiheading',
            get_string('aiheading', 'local_quizwizard'),
            get_string('aiheading_desc', 'local_quizwizard')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_quizwizard/aienabled',
            get_string('aienabled', 'local_quizwizard'),
            get_string('aienabled_desc', 'local_quizwizard'),
            0
        ));

        $settings->add(new admin_setting_configtext(
            'local_quizwizard/aiendpoint',
            get_string('aiendpoint', 'local_quizwizard'),
            get_string('aiendpoint_desc', 'local_quizwizard'),
            '',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_quizwizard/aiapikey',
            get_string('aiapikey', 'local_quizwizard'),
            get_string('aiapikey_desc', 'local_quizwizard'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_quizwizard/aimodel',
            get_string('aimodel', 'local_quizwizard'),
            get_string('aimodel_desc', 'local_quizwizard'),
            'gpt-4o-mini',
            PARAM_TEXT
        ));
    }

    $ADMIN->add('localplugins', $settings);
}
