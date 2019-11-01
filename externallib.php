<?php

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
 * External Web Service
 *
 * @package    localreportlog
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once(__DIR__.'/../../config.php');

class local_reportlog_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function getlog_parameters() {
        return new external_function_parameters(
                array('welcomemessage' => new external_value(PARAM_TEXT, 'The welcome message. By default it is "Hello world,"', VALUE_DEFAULT, 'Hello world, '))
        );
    }

    /**
     * Returns welcome message
     * @param string $welcomemessage
     * @return string welcome message
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function getlog($welcomemessage = 'Hello world, ') {
        global $USER;
        global $DB;

        // Parameter validation
        // REQUIRED
        $params = self::validate_parameters(self::getlog_parameters(),
                array('welcomemessage' => $welcomemessage));

        // Context validation
        // OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        // Capability checking
        // OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $count = $DB->count_records('config_plugins');

        return $params['welcomemessage'] . $USER->firstname . ". You have {$count} plugins!" ;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function getlog_returns() {
        return new external_value(PARAM_TEXT, 'The welcome message + user first name');
    }
}
