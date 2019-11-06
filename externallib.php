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
                array('queryObject' => new external_value(PARAM_RAW, 'The query options!"', VALUE_DEFAULT, '{}! '))
        );
    }

    /**
     * Returns welcome message
     * @param string $queryObject
     * @return string welcome message
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function getlog($queryObject = 'Param1! ') {
        global $USER;
        global $DB;

        $response = [
            'status' => 200
        ];

        // TODO: PREVENT SQL INJECTION
        // TODO: PREVENT SQL INJECTION
        // TODO: Verify that 'desiredColumns' are valid.
        // TODO: Verify that 'desiredColumns' are valid.

        try {
            $params = self::validate_parameters(self::getlog_parameters(),
                array('queryObject' => $queryObject));

            $context = get_context_instance(CONTEXT_USER, $USER->id);
            self::validate_context($context);

            if (!has_capability('moodle/user:viewdetails', $context)) {
                throw new moodle_exception('cannotviewprofile');
            }

            $queryObject = json_decode($params['queryObject'], true);

            $columnList = '';
            if (isset($queryObject['desiredColumns']) && count($queryObject['desiredColumns']) > 0) {
                $columnList = implode(', ', $queryObject['desiredColumns']);
            } else {
                $columnList = '*';
            }

            $queryString = "select {$columnList} from mdl_logstore_standard_log where ";

            if ($queryObject['dateRange'] && $queryObject['dateRange']['start'] && $queryObject['dateRange']['end']) {
                $queryString .= "timecreated > {$queryObject['dateRange']['start']} ";
                $queryString .= "and timecreated < {$queryObject['dateRange']['end']}";
            } else {
                return json_encode([
                    'status' => 422,
                    'message' => "'dateRange.start' and 'dateRange.end' are required.",
                ]);
            }

            if ($queryObject['exactMatches']) {
                foreach ($queryObject['exactMatches']  as $column=>$value) {
                    $queryString .= " AND {$column} = '{$value}' ";
                }
            }

            $countQuery = "select count(*) from ($queryString) as counted";

            if (isset($queryObject['debug']) && $queryObject['debug'] === true) {
                $response['selectQuery'] = $queryString;
                $response['countQuery'] = $countQuery;
            }

            if (isset($queryObject['withCount']) && $queryObject['withCount'] === true) {
                $response['count'] = $DB->count_records_sql($countQuery);
            }

            $response['data'] = $DB->get_records_sql($queryString);

        } catch (\Throwable $e) {
            $response['status'] = 500;
            $response['message'] = $e->getMessage();
        }

        return json_encode($response);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function getlog_returns() {
        return new external_value(PARAM_RAW, 'The welcome message + user first name');
    }
}
