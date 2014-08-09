<?php

/**************************************************************

    COPYRIGHT 2014, BENOIT TOUCHETTE (DRAEKKO).
    This program comes with ABSOLUTELY NO WARRANTY;
    https://www.gnu.org/licenses/gpl-3.0.html
    https://www.gnu.org/licenses/quick-guide-gplv3.html
    Licensed GPLv3.

 **************************************************************/

if ( ! defined( 'MC_CASL_TRUE' ) ) {
    define('MC_CASL_TRUE', 1, true );
}
if ( ! defined( 'MC_CASL_FALSE' ) ) {
    define('MC_CASL_FALSE', 0, true );
}

class MailChimp_CASL_DB extends SQLite3 {

    public $db_path;
    public $db_filename;
    public $db_fpath;

    /*********************************************************************/

    public function set_path($path) {
        $this->$db_path = $path;
    }

    /*********************************************************************/

    public function get_path() {
        return $this->$db_path;
    }

    /*********************************************************************/

    public function set_fpath($fpath) {
        $this->$db_path = $path;
    }

    /*********************************************************************/

    public function get_fpath() {
        return $this->$db_fpath;
    }

    /*********************************************************************/

    public function set_filename($filename) {
        $this->$db_filename = $filename;
    }

    /*********************************************************************/

    public function get_filename() {
        return $this->$db_filename;
    }

    /*********************************************************************/

	function __construct( $dbfname, $encrypt ) {
        $this->opendb( $dbfname, $encrypt );
    }

    /*********************************************************************/

	public function opendb( $dbfname, $encrypt ) {
        $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        if ( !isset( $encrypt ) ) {
			$this->open( $dbfname, $flags );
        } else if ( $encrypt == false || $encrypt == '' ) {
			$this->open( $dbfname, $flags );
		} else {
			$this->open( $dbfname, $flags, $encrypt );
		}
	}

    /*********************************************************************/

	public function closedb() {
	    $this->close();
	}

    /*********************************************************************/

	public function querydb( $findthis ) {
		return $this->query( $findthis );
	}

    /*********************************************************************/

	public function execute( $dothis ) {
		return $this->exec( $dothis );
	}

    /*********************************************************************/

	public function getrows($db_data) {
	    //$searchme  = 'WHERE email="' . $db_data['email'] . '"';
	    $searchme  = 'WHERE ';
	    $search = false;
        if ( isset( $db_data['email'] ) ) {
            $searchme .= 'email="' . $db_data['email'] . '"';
	        $search = true;
	    }
        if ( isset( $db_data['useremail'] ) ) {
            if ($search == true) $searchme .= ' OR ';
            $searchme .= 'useremail="' . $db_data['useremail'] . '"';
	        $search = true;
	    }
        if ( isset( $db_data['username'] ) ) {
            if ($search == true) $searchme .= ' OR ';
    	    $searchme .= 'username="' . $db_data['username'] . '"';
	        $search = true;
    	}
        if ( isset( $db_data['useragent'] ) ) {
            if ($search == true) $searchme .= ' OR ';
    	    $searchme .= 'useragent="' . $db_data['useragent'] . '"';
	        $search = true;
    	}
        if ( isset( $db_data['id'] ) ) {
            if ($search == true) $searchme .= ' OR ';
	        $searchme .= 'userid=' . strval( $db_data['id'] );
	        $search = true;
        }
	    $orderby   = 'ORDER BY timestamp DESC';
        return $this->querydb( "SELECT * FROM subscription " . $searchme . " " . $orderby . ";" );
    }

    public function fetcharray($rows) {
        return $rows->fetchArray(SQLITE3_ASSOC);
    }

    /*********************************************************************/

	public function getrowcount() {
        $rows = $this->query( "SELECT COUNT(*) as count FROM subscription" );
        $row = $rows->fetchArray();
        $count = intval ( $row['count'] );
        return $count;
	}

    /*********************************************************************/

	public function createtable() {
		$create_table  = 'CREATE TABLE IF NOT EXISTS subscription(';
		$create_table .= 'id INTEGER PRIMARY KEY AUTOINCREMENT, loggedin BOOLEAN, frontform BOOLEAN, checkoutform BOOLEAN, language TEXT, timestamp INTEGER, status BOOLEAN, ';
		$create_table .= 'email TEXT, date TEXT, time TEXT, dob TEXT, fname TEXT, lname TEXT, ip TEXT, country TEXT, ';
		$create_table .= 'useremail TEXT, userphone TEXT, userid INTEGER, usercompany TEXT, userfname TEXT, userlname TEXT, username TEXT, useragent TEXT);';
		return $this->execute( $create_table );
	}

    /*********************************************************************/

	public function savedata( $data_array ) {
	    $sql = "INSERT INTO subscription VALUES(null, ";
	    $sql .= $data_array['loggedin'] . ", ";
	    $sql .= $data_array['frontform'] . ", ";
	    $sql .= $data_array['checkoutform'] . ", ";
	    $sql .= "\"".$data_array['language'] . "\", ";
	    $sql .= $data_array['timestamp'] . ", ";
	    $sql .= $data_array['status'] . ", ";
	    $sql .= "\"".$data_array['email'] . "\", ";
	    $sql .= "\"".$data_array['date'] . "\", ";
	    $sql .= "\"".$data_array['time'] . "\", ";
	    $sql .= "\"".$data_array['dob'] . "\", ";
	    $sql .= "\"".$data_array['fname'] . "\", ";
	    $sql .= "\"".$data_array['lname'] . "\", ";
	    $sql .= "\"".$data_array['ip'] . "\", ";
	    $sql .= "\"".$data_array['country'] . "\", ";
	    $sql .= "\"".$data_array['useremail'] . "\", ";
	    $sql .= "\"".$data_array['userphone'] . "\", ";
	    $sql .= "\"".$data_array['userid'] . "\", ";
	    $sql .= "\"".$data_array['usercompany'] . "\", ";
	    $sql .= "\"".$data_array['userfname'] . "\", ";
	    $sql .= "\"".$data_array['userlname'] . "\", ";
	    $sql .= "\"".$data_array['username'] . "\"";
	    $sql .= "\"".$data_array['useragent'] . "\"";
	    $sql .= ")";

	    if ($this->execute($sql)) {
	        return true;
	    }
	    return false;
	}

    /*********************************************************************/

	public function geterrcode() {
		return $this->lastErrorCode();
	}

    /*********************************************************************/

	public function geterrmsg() {
		return $this->lastErrorMsg();
	}

    /*********************************************************************/

	public function savedb_to_csv() {
	    $timestamp = strtotime("now");
	    $temp_dir = get_temp_dir();
        $uld = wp_upload_dir();
        $temp_dir = $uld['basedir'] . "/mailchimp_casl/temp";
        if ( !is_dir( $temp_dir ) ) {
            return false;
        }

	    $temp_file = 'mailchimp_casl_'.$timestamp.'.csv';
	    $temp_path_file = $temp_dir . "/" . $temp_file;

        $fp = fopen( $temp_path_file, 'w' );

        if ( $fp == false ) {
            return false;
        }

		$header = array( 'id', 'loggedin', 'frontform', 'checkoutform', 'language', 'timestamp', 'status',
		                 'email', 'date', 'time', 'dob', 'fname', 'lname', 'ip', 'country',
		                 'useremail', 'userphone', 'userid', 'usercompany', 'userfname', 'userlname', 'username', 'useragent');

        fputcsv($fp, $header);
        $rows = $this->querydb( "SELECT * FROM subscription;" );
        while ( $row = $this->fetcharray( $rows )  ) {
            fputcsv($fp, $row);
        }

        fclose( $fp );

	    return trim( $temp_path_file );
	}
}

?>
