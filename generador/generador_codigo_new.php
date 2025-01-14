<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
$conexion = $conexion;

// Modulo y submodulo respectivamente
/*$modulo="1";
$submodulo="1";
require_once("../includes/rsusuario.php"); */


$parametros_array_string = '	$parametros_array=array(';
$parametros_array_function = '';
$texto_sql = "";

$table_schema = $database;
$columnadoble = "S";
$tipo_accion = "";
if ($_GET['coldoble'] == 'n') {
    $columnadoble = "N";
}
if ($_GET['tipo']) {
    $tipo_accion = $_GET['tipo'];
}


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

$pagredir = $tabla.".php";

/*$consulta="
SELECT column_name, *
FROM information_schema.key_column_usage
WHERE
TABLE_NAME='$tabla'
ORDER BY
CASE WHEN
    constraint_name like '%_pk'
THEN
    1
ELSE
    0
END desc,
CASE WHEN
    constraint_name like 'pk_%'
THEN
    1
ELSE
    0
END desc
LIMIT 1
";
$rspri=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$yav_primaria=trim($rspri->fields['column_name']);*/
/*$consulta="
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
$yav_primaria=trim($rspri->fields['column_name']);*/
$consulta = "	
	SHOW COLUMNS 
	from $tabla
	";
$rspri = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$yav_primaria = trim($rspri->fields['Field']);
// clave primaria
while (!$rspri->EOF) {
    if ($rspri->fields['Key'] == 'PRI') {
        $yav_primaria = trim($rspri->fields['Field']);
        echo $yav_primaria;
        break;
    }
    $rspri->MoveNext();
}
$rspri->MoveFirst();


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
    if ($yav_primaria != $nombrecol) {
        if ($nombrecol != 'borrado_el' && $nombrecol != 'borrado_por') {
            $colinsert .= $nombrecol.', ';
        }
        if ($nombrecol != 'registrado_el' && $nombrecol != 'registrado_por' && $nombrecol != 'borrado_el' && $nombrecol != 'borrado_por' && $nombrecol != 'estado') {
            $colupdate .= '	'.$nombrecol.'=$'.$nombrecol.',
		';
        }
    }

    //obligatoriedad
    if ($nulo == 'NO') {
        $obligatorio = "SI";
    } else {
        $obligatorio = "NO";
    }
    //echo $tipodatocorto;
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

    if ($yav_primaria != $nombrecol && $nombrecol != 'registrado_el' && $nombrecol != 'registrado_por' && $nombrecol != 'borrado_el' && $nombrecol != 'borrado_por' && $nombrecol != 'estado') {

        // limpieza segun tipo de dato
        if ($tipodatocorto == 'NUMERIC' or $tipodatocorto == 'DECIMAL') {
            $limpiaant = "floatval";
        } elseif ($tipodatocorto == 'INT') {
            $limpiaant = "intval";
        } else {
            $limpiaant = "htmlentities";
        }


        if ($columnadoble == 'S') {

            $htmladd .= '
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">'.$nombrecollindo.' '.$obligamarca.'</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="'.$tipocampohtml.'" name="'.$nombrecol.'" id="'.$nombrecol.'" value="<?php  if(isset($_POST[\''.$nombrecol.'\'])){ echo '.$limpiaant.'($_POST[\''.$nombrecol.'\']); }else{ echo '.$limpiaant.'($rs->fields[\''.$nombrecol.'\']); }?>" placeholder="'.$nombrecollindo.'" class="form-control" '.$obliga.' />                    
	</div>
</div>
';

        } else {
            $htmladd .= '
<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">'.$nombrecollindo.' '.$obligamarca.'</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="'.$tipocampohtml.'" name="'.$nombrecol.'" id="'.$nombrecol.'" value="<?php  if(isset($_POST[\''.$nombrecol.'\'])){ echo '.$limpiaant.'($_POST[\''.$nombrecol.'\']); }else{ echo '.$limpiaant.'($rs->fields[\''.$nombrecol.'\']); }?>" placeholder="'.$nombrecollindo.'" class="form-control" '.$obliga.' />                    
	</div>
</div>
';
        }

    }


    if ($yav_primaria != $nombrecol) {
        // carga de parametros en el array
        $parametros_array_string .= '
		"'.$nombrecol.'" => $'.$nombrecol.',';

        //////////////////////////////////////////////////////////////////////////////////////////////
        //$parametros_array_function.='$'.$nombrecol.'=$parametros_array[\''.$nombrecol.'\'];';
    }


    if ($obligatorio == 'NO') {
        $validacionestxt .= "
/* 
$nombrecol ";
    }

    // ***** datos character, date, etc, que necesitan comillas, NO USAR DECIMAL ACA por que sino le hace un intval y quita las comas
    if ($tipodatocorto != 'INT' && $tipodatocorto != 'BIGINT' && $tipodatocorto != 'SERIAL' && $tipodatocorto != 'DECIMAL' && $tipodatocorto != 'NUMERIC') {

        // datos para la consulta
        if ($yav_primaria != $nombrecol) {
            if ($nombrecol != 'borrado_el') {
                $valorinsert .= '$'.$nombrecol.', ';
            }
        }
        //$valorupdate.='$'.$nombrecol.' ';

        // recepcion de parametros
        if ($nombrecol == 'registrado_el') {
            $parametrostxt .= '	$'.$nombrecol.'='.'$ahora;
			
';
            $parametros_array_function .= '
	$'.$nombrecol.'=antisqlinyeccion($parametros_array[\''.$nombrecol.'\'],"text");';
        } else {
            if ($nombrecol != 'borrado_el') {
                $parametrostxt .= '	$'.$nombrecol.'='.'$_POST[\''.$nombrecol.'\'];
';
                if ($nombrecol == "estado") {
                    $parametrostxt .= '	$'.$nombrecol.'='.'1;
';
                }
                $parametros_array_function .= '
	$'.$nombrecol.'=antisqlinyeccion($parametros_array[\''.$nombrecol.'\'],"text");';
            }

        }





        // validaciones
        if ($nombrecol != 'registrado_el' && $nombrecol != 'borrado_el') {
            $validacionestxt .= '
	if(trim($parametros_array[\''.$nombrecol.'\']) == \'\'){
		$valido="N";
		$errores.=" - El campo '.$nombrecol.' no puede estar vacio.'.htmlentities('<br />').'";	
	}';
        }
        //***** datos numericos integer y serial
    } else {
        // datos para la consulta
        if ($yav_primaria != $nombrecol) {
            if ($nombrecol != 'borrado_por') {
                $valorinsert .= "$".$nombrecol.", ";
            }
        }
        // recepcion de parametros
        if ($tipodatocorto == 'DECIMAL' or $tipodatocorto == 'NUMERIC') {
            $parametrostxt .= '	$'.$nombrecol.'='.'$_POST[\''.$nombrecol.'\'];
';
            $parametros_array_function .= '
	$'.$nombrecol.'=antisqlinyeccion($parametros_array[\''.$nombrecol.'\'],"float");';


            // validaciones
            $validacionestxt .= '
	if(floatval($parametros_array[\''.$nombrecol.'\']) <= 0){
		$valido="N";
		$errores.=" - El campo '.$nombrecol.' no puede ser cero o negativo.'.htmlentities('<br />').'";	
	}';


            if ($nombrecol == 'estado') {
                $parametrostxt .= '	$'.$nombrecol.'='.'1;
		';
                $parametros_array_function .= '
	$'.$nombrecol.'=antisqlinyeccion($parametros_array[\''.$nombrecol.'\'],"int");';
            }
        } else { //if($tipodatocorto == 'DECIMAL'){
            if ($yav_primaria != $nombrecol) {

                // recepcion de parametros
                if ($nombrecol == 'registrado_por') {
                    $parametrostxt .= '	$'.$nombrecol.'='.'$idusu;
			
';
                    $parametros_array_function .= '
	$'.$nombrecol.'=antisqlinyeccion($parametros_array[\''.$nombrecol.'\'],"int");';
                } else {
                    if ($nombrecol != 'borrado_por' && $nombrecol != 'estado') {
                        $parametrostxt .= '	$'.$nombrecol.'='.'$_POST[\''.$nombrecol.'\'];
';
                        $parametros_array_function .= '
	$'.$nombrecol.'=antisqlinyeccion($parametros_array[\''.$nombrecol.'\'],"int");';
                    }
                }
                if ($nombrecol == 'estado') {
                    $parametrostxt .= '	$'.$nombrecol.'='.'1;
';
                    $parametros_array_function .= '
	$'.$nombrecol.'=antisqlinyeccion($parametros_array[\''.$nombrecol.'\'],"int");';
                }



                // validaciones
                if ($nombrecol != 'registrado_el' && $nombrecol != 'registrado_por' && $nombrecol != 'borrado_el' && $nombrecol != 'borrado_por' && $nombrecol != 'estado') {
                    $validacionestxt .= '
	if(intval($parametros_array[\''.$nombrecol.'\']) == 0){
		$valido="N";
		$errores.=" - El campo '.$nombrecol.' no puede ser cero o nulo.'.htmlentities('<br />').'";	
	}';
                }

            } // if($yav_primaria != $nombrecol){

        } // if($tipodatocorto == 'DECIMAL'){



    }
    /******/

    if ($obligatorio == 'NO') {
        $validacionestxt .= "
*/";
    }

    $i++;
}



// Obtén la longitud del parametros_array_string
$length = strlen($parametros_array_string);

// Borra el último carácter
if ($length > 0) {
    $parametros_array_string = substr($parametros_array_string, 0, $length - 1);
}
$parametros_array_string .= '
	);';


$colinsert = substr($colinsert, 0, -2);
$valorinsert = substr($valorinsert, 0, -2);
$colupdate = substr(rtrim($colupdate), 0, -1);
$valorupdate = substr($valorupdate, 0, -2);

// generar inicio de condicion para insercion
$codigogen = "";

if ($accion == "update") {

    // Obtén la longitud del parametros_array_string
    $length = strlen($parametros_array_string);

    // Borra el último carácter
    if ($length > 0) {
        $parametros_array_string = substr($parametros_array_string, 0, $length - 5);
    }

    // carga de parametros en el array
    $parametros_array_string .= ',
		"'.$yav_primaria.'" => $'.$yav_primaria.'
	);';
    $parametros_array_function .= '
	$'.$yav_primaria.'=antisqlinyeccion($parametros_array[\''.$yav_primaria.'\'],"int");';

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

}


$codigogen .= '



if(isset($_POST[\'MM_'.$accion.'\']) && $_POST[\'MM_'.$accion.'\'] == \'form1\'){

	// validaciones basicas
	$valido="S";
	$errores="";
	
	// control de formularios, seguridad para evitar doble envio y ataques via bots
	if($_SESSION[\'form_control\'] != $_POST[\'form_control\']){
		$errores.="- Se detecto un intento de envio doble, recargue la pagina.'.htmlentities('<br />').'";
		$valido="N";
	}
	if(trim($_POST[\'form_control\']) == \'\'){
		$errores.="- Control del formularios no activado.'.htmlentities('<br />').'";
		$valido="N";
	}
	$_SESSION[\'form_control\'] = md5(rand());
	// control de formularios, seguridad para evitar doble envio y ataques via bots
	

	// recibe parametros
'.$parametrostxt.'


'.$parametros_array_string.'

	// si todo es correcto actualiza
	if($valido == "S"){
		$res='.$tabla.'_'.$tipo_accion.'($parametros_array);
		if ($res["valido"]=="S") {
			header("location: '.$pagredir.'");
			exit;
		}else{
			$errores.=$res["errores"];
		}
		
	}else{
		$errores.=$res["errores"];
	}

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
		$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		
	}
';
    // $codigogen.=$texto_sql_insert;
    $texto_sql = $texto_sql_insert;
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
			'.$yav_primaria.' = $'.$yav_primaria.'
			and estado = 1
		";
		$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	
	}
';
    // $codigogen.=$texto_sql_update;
    $texto_sql = $texto_sql_update;

}


$codigogen .= '
}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION[\'form_control\'] = md5(rand());

// se puede mover esta funcion al archivo funciones_'.$tabla.'.php y realizar un require_once
function '.$tabla.'_'.$tipo_accion.'($parametros_array){
	global $conexion;
	global $saltolinea;

	// validaciones basicas
	$valido="S";
	$errores="";
	
    '.$parametros_array_function.'
	'.$validacionestxt.'
	'.$texto_sql.'

	return array("errores"=>$errores,"valido"=>$valido);
}

';


$html = "";
$html .= '<?php if(trim($errores) != ""){ ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
';

if ($columnadoble == 'S') {
    $offset = "5";
    $md = 5;
} else {
    $offset = "3";
    $md = 9;
}
//<div class="col-md-'.$md.' col-sm-'.$md.' col-xs-12 col-md-offset-'.$offset.'">

$html .= '<form id="form1" name="form1" method="post" action="">
'.$htmladd.'
<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href=\''.$tabla.'.php\'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_'.$accion.'" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION[\'form_control\']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />';
echo "<html>
<head>
<title>Generador de Codigo</title>
<!--<script src=\"vendors/jquery/dist/jquery.min.js\"></script>-->
";
echo '<script>
function copyToClipboard(element) {
  var $temp = $("<input>");
  $("body").append($temp);
  $temp.val($(element).text()).select();
  document.execCommand("copy");
  $temp.remove();
}
</script>';
echo "</head>
<body>
";
echo "<strong>Tabla:</strong> $tabla <br />";
echo "<strong>Accion:</strong> $accion <br />";
echo "<strong>Pagina de Redireccion:</strong> $pagredir <br />";
echo "<br />";
echo "<strong>Codigo PHP:</strong> <!--<button onclick=\"copyToClipboard('#cod_php')\">Copiar</button>--><br />";
echo '<div style="border:1px solid #000000" id="cod_php">';
echo "<pre>".$codigogen."</pre>";
echo '</div>';
echo "<strong>Codigo HTML:</strong> <!--<button onclick=\"copyToClipboard('#cod_html')\">Copiar</button>--><br />";
echo '<div style="border:1px solid #000000" id="cod_html">';
echo "<pre>".htmlentities($html)."</pre>";
;
echo '</div>';
echo "
</body>
</html>
";
