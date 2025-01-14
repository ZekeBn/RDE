<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
$adodb_conn = $conexion;

// Modulo y submodulo respectivamente
/*$modulo="1";
$submodulo="1";
require_once("../includes/rsusuario.php"); */


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
	
SELECT *
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name   = '$tabla'";

$rs = $adodb_conn->Execute($consulta) or die(errorpg($adodb_conn, $consulta));

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
    $columnas[$i]['nombre'] = $rs->fields['column_name'];
    $columnas[$i]['tipo'] = $rs->fields['data_type'];
    $columnas[$i]['nulo'] = $rs->fields['is_nullable']; // YES or NO
    $columnas[$i]['primaria'] = $rs->fields['Key']; // PRI or ''
    $columnas[$i]['extra'] = $rs->fields['Extra']; //auto_increment

    //print_r($rs->fields);
    //echo "<br />";

    $i++;
    $rs->MoveNext();
}
//  $rs->Close(); # optional
//	$adodb_conn->Close();
//print_r($columnas);
//echo $columnas[0]['nombre'];

$i = 0;
foreach ($columnas as $key => $value) {

    $nombrecol = $columnas[$i]['nombre'];
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
    }
    if ($tipodatocorto == 'DATETIME') {
        $tipocampohtml = "datetime";
    }
    if ($tipodatocorto == 'INTEGER') {
        $tipodatocorto = "INT";
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
		<td align="center">'.$obligamarca.''.$nombrecol.'</td>
		<td width="130" align="left"><input type="'.$tipocampohtml.'" name="'.$nombrecol.'" id="'.$nombrecol.'" value="<?php  if(isset($_POST[\''.$nombrecol.'\'])){ echo htmlentities($_POST[\''.$nombrecol.'\']); }else{ echo htmlentities($rs->fields[\''.$nombrecol.'\']); }?>" placeholder="'.$nombrecol.'" '.$obliga.' /></td>
	</tr>
';
    if ($obligatorio == 'NO') {
        $validacionestxt .= "
/*
	// campo no obligatorio por base de datos, pero quizas tu necesites que sea obligatorio";
    }

    // ***** datos character, date, etc, que necesitan comillas, NO USAR DECIMAL ACA por que sino le hace un intval y quita las comas
    if ($tipodatocorto != 'INT' && $tipodatocorto != 'BIGINT' && $tipodatocorto != 'SERIAL' && $tipodatocorto != 'DECIMAL') {

        // datos para la consulta
        $valorinsert .= '$'.$nombrecol.', ';
        //$valorupdate.='$'.$nombrecol.' ';

        // recepcion de parametros
        $parametrostxt .= '	$'.$nombrecol.'='.'antisqlinyeccion($_POST[\''.$nombrecol.'\'],"text");
';

        // validaciones
        $validacionestxt .= '
	if(trim($_POST[\''.$nombrecol.'\']) == \'\'){
		$valido="N";
		$errores.=" - El campo '.$nombrecol.' no puede estar vacio.'.htmlentities('<br />').'";	
	}';
        //***** datos numericos integer y serial
    } else {
        // datos para la consulta
        $valorinsert .= "$".$nombrecol.", ";

        // recepcion de parametros
        if ($tipodatocorto == 'DECIMAL') {
            $parametrostxt .= '	$'.$nombrecol.'='.'antisqlinyeccion($_POST[\''.$nombrecol.'\'],"float");
';
            // validaciones
            $validacionestxt .= '
	if(floatval($_POST[\''.$nombrecol.'\']) <= 0){
		$valido="N";
		$errores.=" - El campo '.$nombrecol.' no puede ser cero o negativo.'.htmlentities('<br />').'";	
	}';
        } else {
            $parametrostxt .= '	$'.$nombrecol.'='.'antisqlinyeccion($_POST[\''.$nombrecol.'\'],"int");
';
            // validaciones
            $validacionestxt .= '
	if(intval($_POST[\''.$nombrecol.'\']) == 0){
		$valido="N";
		$errores.=" - El campo '.$nombrecol.' no puede ser cero o nulo.'.htmlentities('<br />').'";	
	}';
        }



    }
    /******/

    if ($obligatorio == 'NO') {
        $validacionestxt .= "
*/
";
    }

    $i++;
}


$colinsert = substr($colinsert, 0, -2);
$valorinsert = substr($valorinsert, 0, -2);
$colupdate = substr(rtrim($colupdate), 0, -1);
$valorupdate = substr($valorupdate, 0, -2);

// generar inicio de condicion para insercion
$codigogen = "";
$codigogen .= 'if(isset($_POST[\'MM_'.$accion.'\']) && $_POST[\'MM_'.$accion.'\'] == \'form1\'){

	// recibe parametros
'.$parametrostxt.'

	// validaciones basicas
	$valido="S";
	$errores="";
'.$validacionestxt.'
';


// genera texto para insert
if ($accion == 'insert') {
    $texto_sql_insert = '

	// si todo es correcto inserta
	if($valido == "S"){
		
		$consulta="
		insert into '.$tabla.'
		('.$colinsert.')
		values
		('.$valorinsert.')
		";
		$adodb_conn->Execute($consulta) or die(errorpg($adodb_conn,$consulta));
		
		header("location: '.$pagredir.'");
		exit;
		
	}
';
    $codigogen .= $texto_sql_insert;
}
if ($accion == 'update') {
    $texto_sql_update = '

	// si todo es correcto actualiza
	if($valido == "S"){
		
		$consulta="
		update '.$tabla.'
		set
		'.$colupdate.'
		where
			idempresa=0
			/* AND XXXXXX */
			/* AQUI VAN LOS CONDICIONALES */
		";
		$adodb_conn->Execute($consulta) or die(errorpg($adodb_conn,$consulta));
		
		header("location: '.$pagredir.'");
		exit;
		
	}
';
    $codigogen .= $texto_sql_update;
}


$codigogen .= "
}";


$html = "";
$html .= '<?php if(trim($errores) != ""){ ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
';

$html .= '<form id="form1" name="form1" method="post" action="">
<table width="400" border="1" class="tablaconborde" align="center">
  <tbody>
'.$htmladd.'
  </tbody>
</table>
<br />
<p align="center">
  <input type="submit" name="button" id="button" value="Registrar" />
  <input type="hidden" name="MM_'.$accion.'" value="form1" />
</p>
<br />
</form>';

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
