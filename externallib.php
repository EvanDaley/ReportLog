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

        $plugin = (new self);

        try {
            $queryObject = $plugin->validateAndParseInput($USER, $queryObject);

            if (!isset($queryObject['dateRange']) || !isset($queryObject['dateRange']['start']) || !isset($queryObject['dateRange']['end'])) {
                return json_encode([
                    'status' => 422,
                    'message' => "'dateRange.start' and 'dateRange.end' are required.",
                ]);
            }

            $queryString = $plugin->getSelectSql($queryObject);
            $queryString .= $plugin->getJoinSql($queryObject);
            $queryString .= $plugin->getDateSql($queryObject);
            $queryString .= $plugin->getWhereSql($queryObject);
            $queryString .= $plugin->getWhereInSql($queryObject);

            // PREPARE TO COUNT RECORDS
            $pageSize = 500;
            $page = 1;
            if (isset($queryObject['pageSize'])) { $pageSize = $queryObject['pageSize']; }
            if (isset($queryObject['page'])) { $page = $queryObject['page']; }
            $countQuery = "select count(*) from ({$queryString}) as counted";

            // SET UP DEBUGGING INFO
            if (isset($queryObject['debug']) && $queryObject['debug'] === true) {
                $response['selectQuery'] = $queryString;
                $response['countQuery'] = $countQuery;
            }

            // RUN COUNT
            if (isset($queryObject['withCount']) && $queryObject['withCount'] === true) {
                $response['page_count'] = ceil($DB->count_records_sql($countQuery) / $pageSize);
            }

            // PREPARE LIMIT AND OFFSET
            $pageOffset = ($page -1)*$pageSize;
            $queryString .= " limit {$pageSize} offset {$pageOffset} ";

            // RUN QUERY
            $response['data'] = $DB->get_records_sql($queryString);

        } catch (\Throwable $e) {
            $response['status'] = 500;
            $response['message'] = $e->getMessage();
        }

        return json_encode($response);
    }

    public static function validateAndParseInput($USER, $queryObject) {
        $params = self::validate_parameters(self::getlog_parameters(), array('queryObject' => $queryObject));

        $queryObject = json_decode($params['queryObject'], true);

        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        return $queryObject;
    }

    public static function getSelectSql($queryObject) {
        if (isset($queryObject['desiredColumns']) && count($queryObject['desiredColumns']) > 0) {
            $result = implode(', ', $queryObject['desiredColumns']);
        } else {
            $result = '*';
        }

        return "select {$result} from mdl_logstore_standard_log ";
    }

    public static function getJoinSql($queryObject) {
        $result = '';
        if (isset($queryObject['joinWithGroups']) && count($queryObject['joinWithGroups']) > 0) {
            $result .= ' join mdl_groups_members on mdl_groups_members.userid = mdl_logstore_standard_log.userid ';
        }

        return "{$result} where ";
    }

    public static function getDateSql($queryObject) {
        $result = "timecreated > {$queryObject['dateRange']['start']} ";
        $result .= "and timecreated < {$queryObject['dateRange']['end']}";

        return $result;
    }

    public static function getWhereSql($queryObject) {
        $result = '';
        if (isset($queryObject['where'])) {
            foreach ($queryObject['where']  as $column=>$value) {
                $result .= " AND {$column} = '{$value}' ";
            }
        }

        return $result;
    }
    public static function getWhereInSql($queryObject) {
        $result = '';
        if (isset($queryObject['whereIn'])) {
            foreach($queryObject['whereIn'] as $column=>$array) {
                $arrayString = implode(',', $array);
                $result .= " AND {$column} in ({$arrayString}) " ;
            }
        }

        return $result;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function getlog_returns() {
        return new external_value(PARAM_RAW, 'The welcome message + user first name');
    }
}
