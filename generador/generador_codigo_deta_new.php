<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
$conexion = $conexion;

// Modulo y submodulo respectivamente
/*$modulo="1";
$submodulo="1";
require_once("../includes/rsusuario.php"); */

$table_schema = $database;


if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1') {
    echo "acceso denegado ".$_SERVER['REMOTE_ADDR'];
    exit;
}



// configuracion
$tabla = "factura_formato";
$accion = "update"; // insert o update
$pagredir = "index.php";

if ($_GET['t'] != '') {
    $tabla = strtolower(antisqlinyeccion(trim($_GET['t']), "text-notnull"));
    $ac = trim($_GET['ac']);
    if ($ac == 'u') {
        $accion = "update";
    } else {
        $accion = "insert";
    }
}

$consulta = "	
SHOW COLUMNS 
from $tabla
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

/*
    $consulta="

SELECT *
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name   = '$tabla'";

    $rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));


    $consulta="
    SELECT tc.table_name,
    tc.constraint_name,
    tc.constraint_type,
    kcu.column_name,
    tc.is_deferrable,
    tc.initially_deferred,
    rc.match_option AS match_type,
    rc.update_rule AS on_update,
    rc.delete_rule AS on_delete,
    ccu.table_name AS references_table,
    ccu.column_name AS references_field
    FROM information_schema.table_constraints tc
    LEFT JOIN information_schema.key_column_usage kcu
    ON tc.constraint_catalog = kcu.constraint_catalog
    AND tc.constraint_schema = kcu.constraint_schema
    AND tc.constraint_name = kcu.constraint_name
    LEFT JOIN information_schema.referential_constraints rc
    ON tc.constraint_catalog = rc.constraint_catalog
    AND tc.constraint_schema = rc.constraint_schema
    AND tc.constraint_name = rc.constraint_name
    LEFT JOIN information_schema.constraint_column_usage ccu
    ON rc.unique_constraint_catalog = ccu.constraint_catalog
    AND rc.unique_constraint_schema = ccu.constraint_schema
    AND rc.unique_constraint_name = ccu.constraint_name
    WHERE lower(tc.constraint_type) = 'primary key'
    and tc.table_name = '$tabla'
    ORDER BY tc.table_name
    limit 1
    ";
    $rspri=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $yav_primaria=trim($rspri->fields['column_name']);
*/

$consulta = "	
	SHOW COLUMNS 
	from $tabla
	";
$rspri = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$yav_primaria = trim($rspri->fields['Field']);

$array = $rs->fields;
//print_r($array);


/*
name: name of column
type: native field type of column
max_length: maximum length of field. Some databases such as MySQL do not return the maximum length of the field correctly. In these cases max_length will be set to -1.
C: character fields that should be shown in a <input type="text"> tag.
X: TeXt, large text fields that should be shown in a <textarea>
B: Blobs, or Binary Large Objects. Typically images.
D: Date field
T: Timestamp field
L: Logical field (boolean or bit-field)
I: Integer field
N: Numeric field. Includes autoincrement, numeric, floating point, real and integer.
R: Serial field. Includes serial, autoincrement integers. This works for selected databases.
*/

$i = 0;
while (!$rs->EOF) {
    //for($i=0;$i<=1000;$i++){
    /*$fld = $rs->FetchField($i);
    $type = $rs->MetaType($fld->type);
     if($fld->name != ''){
         $columnas[$i]['nombre']=$fld->name;
         $columnas[$i]['tipo']=$type;
     }*/
    //echo $type;
    // echo $fld->name;
    //[Field] => idcliente [Type] => int(10) [Null] => NO [Key] => PRI [Default] => [Extra] => auto_increment
    $columnas[$i]['nombre'] = $rs->fields['Field'];
    $columnas[$i]['tipo'] = $rs->fields['Type'];
    $columnas[$i]['nulo'] = $rs->fields['Null']; // YES or NO
    $columnas[$i]['primaria'] = $rs->fields['Key']; // PRI or ''
    $columnas[$i]['extra'] = $rs->fields['Extra']; //auto_increment

    //print_r($rs->fields);
    //echo "<br />";

    $i++;
    $rs->MoveNext();
}
//  $rs->Close(); # optional
//	$conexion->Close();
//print_r($columnas);
//echo $columnas[0]['nombre'];

$i = 0;
foreach ($columnas as $key => $value) {

    $nombrecol = $columnas[$i]['nombre'];
    $nombrecollindo = str_replace('_', ' ', capitalizar(trim($nombrecol)));
    $tipodato = $columnas[$i]['tipo'];
    $tipodatocortoar = explode("(", $tipodato);
    $tipodatocorto = strtoupper($tipodatocortoar[0]);
    $nulo = $columnas[$i]['nulo'];
    $primaria = $columnas[$i]['primaria'];
    $colinsert .= $nombrecol.', ';
    $colupdate .= '	'.$nombrecol.'=$'.$nombrecol.',
		';

    //obligatoriedad
    if ($nulo == 'NO') {
        $obligatorio = "SI";
    } else {
        $obligatorio = "NO";
    }

    // para el html
    $tipocampohtml = "text";

    if ($tipodatocorto == 'DATE') {
        $tipocampohtml = "date";
        $tipodatocorto = "DATE";
    } elseif ($tipodatocorto == 'DATETIME') {
        $tipocampohtml = "datetime";
        $tipodatocorto = "DATETIME";
    } elseif ($tipodatocorto == 'INT') {
        $tipodatocorto = "INT";
    } elseif ($tipodatocorto == 'NUMERIC' or $tipodatocorto == 'DECIMAL') {
        $tipodatocorto = "NUMERIC";
    } else {
        $tipodatocorto = "TEXT";
    }
    // campo obligatorio
    $obliga = "";
    $obligamarca = "";
    if ($obligatorio == 'SI') {
        $obliga = 'required="required"';
        $obligamarca = "*";
    }
    $htmladd .= '
		<tr>
			<th align="center">'.$nombrecollindo.'</th>';
    if ($tipodatocorto == 'TEXT') {
        $htmladd .= '
			<td align="center"><?php echo antixss($rs->fields[\''.$nombrecol.'\']); ?></td>';
    } elseif ($tipodatocorto == 'DATE') {
        $htmladd .= '
			<td align="center"><?php if($rs->fields[\''.$nombrecol.'\'] != ""){ echo date("d/m/Y",strtotime($rs->fields[\''.$nombrecol.'\'])); } ?></td>';
    } elseif ($tipodatocorto == 'DATETIME') {
        $htmladd .= '
			<td align="center"><?php if($rs->fields[\''.$nombrecol.'\'] != ""){ echo date("d/m/Y H:i:s",strtotime($rs->fields[\''.$nombrecol.'\'])); }  ?></td>';
    } elseif ($tipodatocorto == 'INT') {
        $htmladd .= '
			<td align="center"><?php echo intval($rs->fields[\''.$nombrecol.'\']); ?></td>';
    } elseif ($tipodatocorto == 'NUMERIC') {
        $htmladd .= '
			<td align="right"><?php echo formatomoneda($rs->fields[\''.$nombrecol.'\']);  ?></td>';
    }
    $htmladd .= "
		</tr>";




    $i++;
}


$colinsert = substr($colinsert, 0, -2);
$valorinsert = substr($valorinsert, 0, -2);
$colupdate = substr(rtrim($colupdate), 0, -1);
$valorupdate = substr($valorupdate, 0, -2);


$codigogen .= '
		
		

$'.$yav_primaria.'=intval($_GET[\'id\']);
if($'.$yav_primaria.' == 0){
	header("location: '.$tabla.'.php");
	exit;	
}

// consulta a la tabla	
$consulta="
select * 
from '.$tabla.' 
where 
'.$yav_primaria.' = $'.$yav_primaria.'
and estado = 1
limit 1
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$'.$yav_primaria.'=intval($rs->fields[\''.$yav_primaria.'\']);
if($'.$yav_primaria.' == 0){
	header("location: '.$tabla.'.php");
	exit;	
}



';


$html = "";
$html .= '
';

$html .= '
<p><a href="'.$tabla.'.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
'.$htmladd.'

'.$htmladd2.'
</table>
 </div>
<br />';

echo "<strong>Tabla:</strong> $tabla <br />";
echo "<strong>Accion:</strong> $accion <br />";
echo "<strong>Pagina de Redireccion:</strong> $pagredir <br />";
echo "<br />";
echo "<strong>Codigo PHP:</strong><br />";
echo '<div style="border:1px solid #000000">';
echo "<pre>".$codigogen."</pre>";
echo '</div>';
echo "<strong>Codigo HTML:</strong><br />";
echo '<div style="border:1px solid #000000">';
echo "<pre>".htmlentities($html)."</pre>";
;
echo '</div>';
