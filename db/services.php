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
 * Web service local plugin with external functions and service definitions.
 *
 * @package    localreportlog
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_reportlog_getlog' => array(
                'classname'   => 'local_reportlog_external',
                'methodname'  => 'getlog',
                'classpath'   => 'local/reportlog/externallib.php',
                'description' => 'Return Hello World FIRSTNAME. Can change the text (Hello World) sending a new text as parameter',
                'type'        => 'read',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'reportlog' => array(
                'functions' => array ('local_reportlog_getlog'),
                'restrictedusers' => 0,
                'enabled'=>1,
                'shortname'=>'reportlog',
        )
);
