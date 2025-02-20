<?php
/**
* @version   v5.20.14  06-Jan-2019
* @copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
* @copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
* Released under both BSD license and Lesser GPL library license.
* Whenever there is any discrepancy between the two licenses,
* the BSD license will take precedence.
*
* Set tabs to 4 for best viewing.
*
* Latest version is available at http://adodb.org/
*
* Informix 9 driver that supports SELECT FIRST
*
*/

// security - hide paths
if (!defined('ADODB_DIR')) {
    die();
}

include_once(ADODB_DIR.'/drivers/adodb-informix72.inc.php');

class ADODB_informix extends ADODB_informix72
{
    public $databaseType = "informix";
    public $hasTop = 'FIRST';
    public $ansiOuter = true;

    public function IfNull($field, $ifNull)
    {
        return " NVL($field, $ifNull) "; // if Informix 9.X or 10.X
    }
}

class ADORecordset_informix extends ADORecordset_informix72
{
    public $databaseType = "informix";

    public function __construct($id, $mode = false)
    {
        parent::__construct($id, $mode);
    }
}
