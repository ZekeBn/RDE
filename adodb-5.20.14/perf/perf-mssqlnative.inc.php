<?php

/*
@version   v5.20.14  06-Jan-2019
@copyright (c) 2000-2013 John Lim (jlim#natsoft.com). All rights reserved.
@copyright (c) 2014      Damien Regad, Mark Newnham and the ADOdb community
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence. See License.txt.
  Set tabs to 4 for best viewing.

  Latest version is available at http://adodb.org/

  Library for basic performance monitoring and tuning

*/

// security - hide paths
if (!defined('ADODB_DIR')) {
    die();
}

/*
    MSSQL has moved most performance info to Performance Monitor
*/
class perf_mssqlnative extends adodb_perf
{
    public $sql1 = 'cast(sql1 as text)';
    public $createTableSQL = "CREATE TABLE adodb_logsql (
		  created datetime NOT NULL,
		  sql0 varchar(250) NOT NULL,
		  sql1 varchar(4000) NOT NULL,
		  params varchar(3000) NOT NULL,
		  tracer varchar(500) NOT NULL,
		  timer decimal(16,6) NOT NULL
		)";

    public $settings = [
    'Ratios',
        'data cache hit ratio' => ['RATIO',
            "select round((a.cntr_value*100.0)/b.cntr_value,2) from master.dbo.sysperfinfo a, master.dbo.sysperfinfo b where a.counter_name = 'Buffer cache hit ratio' and b.counter_name='Buffer cache hit ratio base'",
            '=WarnCacheRatio'],
        'prepared sql hit ratio' => ['RATIO',
            ['dbcc cachestats','Prepared',1,100],
            ''],
        'adhoc sql hit ratio' => ['RATIO',
            ['dbcc cachestats','Adhoc',1,100],
            ''],
    'IO',
        'data reads' => ['IO',
        "select cntr_value from master.dbo.sysperfinfo where counter_name = 'Page reads/sec'"],
        'data writes' => ['IO',
        "select cntr_value from master.dbo.sysperfinfo where counter_name = 'Page writes/sec'"],

    'Data Cache',
        'data cache size' => ['DATAC',
        "select cntr_value*8192 from master.dbo.sysperfinfo where counter_name = 'Total Pages' and object_name='SQLServer:Buffer Manager'",
            '' ],
        'data cache blocksize' => ['DATAC',
            "select 8192",'page size'],
    'Connections',
        'current connections' => ['SESS',
            '=sp_who',
            ''],
        'max connections' => ['SESS',
            "SELECT @@MAX_CONNECTIONS",
            ''],

        false
    ];


    public function __construct(&$conn)
    {
        if ($conn->dataProvider == 'odbc') {
            $this->sql1 = 'sql1';
            //$this->explain = false;
        }
        $this->conn = & $conn;
    }

    public function Explain($sql, $partial = false)
    {

        $save = $this->conn->LogSQL(false);
        if ($partial) {
            $sqlq = $this->conn->qstr($sql.'%');
            $arr = $this->conn->GetArray("select distinct sql1 from adodb_logsql where sql1 like $sqlq");
            if ($arr) {
                foreach ($arr as $row) {
                    $sql = reset($row);
                    if (crc32($sql) == $partial) {
                        break;
                    }
                }
            }
        }

        $s = '<p><b>Explain</b>: '.htmlspecialchars($sql).'</p>';
        $this->conn->Execute("SET SHOWPLAN_ALL ON;");
        $sql = str_replace('?', "''", $sql);
        global $ADODB_FETCH_MODE;

        $save = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
        $rs = & $this->conn->Execute($sql);
        //adodb_printr($rs);
        $ADODB_FETCH_MODE = $save;
        if ($rs) {
            $rs->MoveNext();
            $s .= '<table bgcolor=white border=0 cellpadding="1" callspacing=0><tr><td nowrap align=center> Rows<td nowrap align=center> IO<td nowrap align=center> CPU<td align=left> &nbsp; &nbsp; Plan</tr>';
            while (!$rs->EOF) {
                $s .= '<tr><td>'.round($rs->fields[8], 1).'<td>'.round($rs->fields[9], 3).'<td align=right>'.round($rs->fields[10], 3).'<td nowrap><pre>'.htmlspecialchars($rs->fields[0])."</td></pre></tr>\n"; ## NOTE CORRUPT </td></pre> tag is intentional!!!!
                $rs->MoveNext();
            }
            $s .= '</table>';

            $rs->NextRecordSet();
        }

        $this->conn->Execute("SET SHOWPLAN_ALL OFF;");
        $this->conn->LogSQL($save);
        $s .= $this->Tracer($sql);
        return $s;
    }

    public function Tables($orderby = '1')
    {
        global $ADODB_FETCH_MODE;

        $save = $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_NUM;
        //$this->conn->debug=1;
        $s = '<table border=1 bgcolor=white><tr><td><b>tablename</b></td><td><b>size_in_k</b></td><td><b>index size</b></td><td><b>reserved size</b></td></tr>';
        $rs1 = $this->conn->Execute("select distinct name from sysobjects where xtype='U'");
        if ($rs1) {
            while (!$rs1->EOF) {
                $tab = $rs1->fields[0];
                $tabq = $this->conn->qstr($tab);
                $rs2 = $this->conn->Execute("sp_spaceused $tabq");
                if ($rs2) {
                    $s .= '<tr><td>'.$tab.'</td><td align=right>'.$rs2->fields[3].'</td><td align=right>'.$rs2->fields[4].'</td><td align=right>'.$rs2->fields[2].'</td></tr>';
                    $rs2->Close();
                }
                $rs1->MoveNext();
            }
            $rs1->Close();
        }
        $ADODB_FETCH_MODE = $save;
        return $s.'</table>';
    }

    public function sp_who()
    {
        $arr = $this->conn->GetArray('sp_who');
        return sizeof($arr);
    }

    public function HealthCheck($cli = false)
    {

        $this->conn->Execute('dbcc traceon(3604)');
        $html = adodb_perf::HealthCheck($cli);
        $this->conn->Execute('dbcc traceoff(3604)');
        return $html;
    }


}
