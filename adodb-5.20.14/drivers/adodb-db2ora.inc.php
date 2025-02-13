<?php
/*
@version   v5.20.14  06-Jan-2019
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
Set tabs to 4 for best viewing.

  Latest version is available at http://adodb.org/

  Microsoft Visual FoxPro data driver. Requires ODBC. Works only on MS Windows.
*/

// security - hide paths
if (!defined('ADODB_DIR')) {
    die();
}
include(ADODB_DIR."/drivers/adodb-db2.inc.php");


if (!defined('ADODB_DB2OCI')) {
    define('ADODB_DB2OCI', 1);


    /**
     * Callback function for preg_replace in _colonscope()
     * @param array $p matched patterns
     * return string '?' if parameter replaced, :N if not
     */
    function _colontrack($p)
    {
        global $_COLONARR, $_COLONSZ;
        $v = (int) substr($p[1], 1);
        if ($v > $_COLONSZ) {
            return $p[1];
        }
        $_COLONARR[] = $v;
        return '?';
    }

    /**
     * smart remapping of :0, :1 bind vars to ? ?
     * @param string $sql SQL statement
     * @param array  $arr parameters
     * @return array
     */
    function _colonscope($sql, $arr)
    {
        global $_COLONARR,$_COLONSZ;

        $_COLONARR = [];
        $_COLONSZ = sizeof($arr);

        $sql2 = preg_replace_callback('/(:[0-9]+)/', '_colontrack', $sql);

        if (empty($_COLONARR)) {
            return [$sql,$arr];
        }

        foreach ($_COLONARR as $k => $v) {
            $arr2[] = $arr[$v];
        }

        return [$sql2,$arr2];
    }

    class ADODB_db2oci extends ADODB_db2
    {
        public $databaseType = "db2oci";
        public $sysTimeStamp = 'sysdate';
        public $sysDate = 'trunc(sysdate)';

        public function _Execute($sql, $inputarr = false)
        {
            if ($inputarr) {
                list($sql, $inputarr) = _colonscope($sql, $inputarr);
            }
            return parent::_Execute($sql, $inputarr);
        }
    };


    class ADORecordSet_db2oci extends ADORecordSet_odbc
    {
        public $databaseType = "db2oci";

        public function __construct($id, $mode = false)
        {
            return parent::__construct($id, $mode);
        }
    }

} //define
