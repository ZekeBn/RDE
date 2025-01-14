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
$tipogri = "ACTIVA";
if ($_GET['tg'] != 'i') {
    $tipogri = "ACTIVA";
} else {
    $tipogri = "INACTIVA";
}

if ($_GET['t'] != '') {
    $tabla = strtolower(antisqlinyeccion(trim($_GET['t']), "text-notnull"));
    $ac = trim($_GET['ac']);
    if ($ac == 'u') {
        $accion = "update";
    } else {
        $accion = "insert";
    }
}

// obtener llave primaria
$consulta = "	
	SHOW COLUMNS 
	from $tabla
	";

$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


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
$rspri=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
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

    $sumatoria_acciones .= "";


    $htmladd .= '
			<th align="center">'.$nombrecollindo.'</th>';

    if ($nombrecol == 'registrado_por' or $nombrecol == 'borrado_por') {
        $htmladd2 .= '
			<td align="center"><?php echo antixss($rs->fields[\''.$nombrecol.'\']); ?></td>';
        $htmladd3 .= '
			<td></td>';
        $sumatoria_acciones .= "";
    } else {
        if ($tipodatocorto == 'TEXT') {
            $htmladd2 .= '
			<td align="center"><?php echo antixss($rs->fields[\''.$nombrecol.'\']); ?></td>';
            $htmladd3 .= '
			<td></td>';
            $sumatoria_acciones .= "";
        } elseif ($tipodatocorto == 'DATE') {
            $htmladd2 .= '
			<td align="center"><?php if($rs->fields[\''.$nombrecol.'\'] != ""){ echo date("d/m/Y",strtotime($rs->fields[\''.$nombrecol.'\'])); } ?></td>';
            $htmladd3 .= '
			<td></td>';
            $sumatoria_acciones .= "";
        } elseif ($tipodatocorto == 'DATETIME') {
            $htmladd2 .= '
			<td align="center"><?php if($rs->fields[\''.$nombrecol.'\'] != ""){ echo date("d/m/Y H:i:s",strtotime($rs->fields[\''.$nombrecol.'\'])); }  ?></td>';
            $htmladd3 .= '
			<td></td>';
            $sumatoria_acciones .= "";
        } elseif ($tipodatocorto == 'INT') {
            $htmladd2 .= '
			<td align="center"><?php echo intval($rs->fields[\''.$nombrecol.'\']); ?></td>';
            /*$htmladd3.='
            <td align="center"><?php echo intval($'.$nombrecol.'_acum); ?></td>';
            $sumatoria_acciones.='$'.$nombrecol.'_acum+=$rs->fields[\''.$nombrecol.'\'];'.$saltolinea;
            */
            $htmladd3 .= '
			<td></td>';

        } elseif ($tipodatocorto == 'NUMERIC') {
            $htmladd2 .= '
			<td align="right"><?php echo formatomoneda($rs->fields[\''.$nombrecol.'\']);  ?></td>';
            $htmladd3 .= '
			<td align="center"><?php echo formatomoneda($'.$nombrecol.'_acum); ?></td>';
            $sumatoria_acciones .= '$'.$nombrecol.'_acum+=$rs->fields[\''.$nombrecol.'\'];'.$saltolinea;
        }
    } // if($nombrecol == 'registrado_por' or $nombrecol == 'borrado_por'){




    $i++;
}


$colinsert = substr($colinsert, 0, -2);
$valorinsert = substr($valorinsert, 0, -2);
$colupdate = substr(rtrim($colupdate), 0, -1);
$valorupdate = substr($valorupdate, 0, -2);



if ($tipogri == "ACTIVA") {
    $whereadd = " estado = 1 ";
} else {
    $whereadd = " estado = 6 ";
}

$codigogen .= '
		

$pagina_actual = $_SERVER[\'REQUEST_URI\'];
$urlParts = parse_url($pagina_actual);



// Verificar si hay parámetros GET
if (isset($urlParts[\'query\'])) {
  // Convertir los parámetros GET en un arreglo asociativo
  parse_str($urlParts[\'query\'], $queryParams);

  // Eliminar el parámetro \'pag\' (si existe)
  unset($queryParams[\'pag\']);
  // Reconstruir los parámetros GET sin \'pag\'
  $newQuery = http_build_query($queryParams);
  // Reconstruir la URL completa
  if(isset($newQuery) == false || empty($newQuery)){
    $newUrl = $urlParts[\'path\'].\'?\' ;
  }else{
    $newUrl = $urlParts[\'path\'] . \'?\' . $newQuery .\'&\';
  }
  
  $pagina_actual=$newUrl;
}else{
  $pagina_actual =  $urlParts[\'path\'].\'?\' ;
}


// paginado del index

$limit ="";
$consulta_numero_filas="
select 
count(*) as filas from   '.$tabla.'
";
$rs_filas=$conexion->Execute($consulta_numero_filas) or die(errorpg($conexion,$consulta_numero_filas));
$num_filas= $rs_filas->fields[\'filas\'];
$filas_por_pagina = 20;
$paginas_num_max=ceil($num_filas/$filas_por_pagina);

$limit ="  LIMIT $filas_por_pagina";

		
$num_pag=intval($_GET[\'pag\']);
$offset=null;
if(($_GET[\'pag\'])>0){
  $numero = (intval($_GET[\'pag\'])-1)*$filas_por_pagina;
$offset=" offset $numero";	
}else{
$offset=" ";	
  $num_pag=1;
}
////////////////////////////////

		
$consulta="
select *,
(select usuario from usuarios where '.$tabla.'.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where '.$tabla.'.borrado_por = usuarios.idusu) as borrado_por
from '.$tabla.' 
where 
'.$whereadd.'
order by '.$yav_primaria.' asc
$limit $offset
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));


';

if ($tipogri == "ACTIVA") {
    $accionesbtn = '
				<div class="btn-group">
					<a href="'.$tabla.'_det.php?id=<?php echo $rs->fields[\''.$yav_primaria.'\']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="'.$tabla.'_edit.php?id=<?php echo $rs->fields[\''.$yav_primaria.'\']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="'.$tabla.'_del.php?id=<?php echo $rs->fields[\''.$yav_primaria.'\']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>
';
    $btn_nuevo = '<p><a href="'.$tabla.'_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />';
} else {
    $accionesbtn = '
				<div class="btn-group">
					<a href="'.$tabla.'_res.php?id=<?php echo $rs->fields[\''.$yav_primaria.'\']; ?>" class="btn btn-sm btn-default" title="Restaurar" data-toggle="tooltip" data-placement="right"  data-original-title="Restaurar"><span class="fa fa-recycle"></span></a>
				</div>
';
    $btn_nuevo = '';
}

$html = "";
$html .= '
';

$html .= '
'.$btn_nuevo.'
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>'.$htmladd.'
		</tr>
	  </thead>
	  <tbody>
<?php while(!$rs->EOF){ ?>
		<tr>
			<td>
				'.$accionesbtn.'
			</td>'.$htmladd2.'
		</tr>
<?php 
'.$sumatoria_acciones.'
$rs->MoveNext(); } //$rs->MoveFirst(); ?>
<tr>
	<td align="center" colspan="20">
		<div class="btn-group">
			<?php 
			$last_index=0;
			if( $num_pag+10 > $paginas_num_max){
				$last_index = $paginas_num_max; 
			}else{
				$last_index = $num_pag+10;
			}
			if($num_pag != 1){ ?>
				<a href="<?php echo $pagina_actual ?>pag=<?php echo ($num_pag-1);?>" class="btn btn-sm btn-default" title="<?php echo ($num_pag-1);?>"  data-placement="right"  data-original-title="<?php echo ($num_pag-1);?>"><span class="fa fa-arrow-left"></span></a>
			<?php }
			$inicio_pag=0;
			if($num_pag !=1 && $num_pag-5 > 0 ){
				$inicio_pag=$num_pag-5;
			}else{
				$inicio_pag=1;
			}
			for ($i=$inicio_pag; $i <= $last_index; $i++) { 
				?>
				<a href="<?php echo $pagina_actual ?>pag=<?php echo ($i);?>" class="btn btn-sm btn-default <?php echo $i == $num_pag ? " selected_pag " : "" ?>" title="<?php echo ($i);?>"  data-placement="right"  data-original-title="<?php echo ($i);?>"><?php echo ($i);?></a>
				<?php if($i == $last_index && ( $num_pag + 1 < $paginas_num_max )){?>
					<a href="<?php echo $pagina_actual ?>pag=<?php echo ($num_pag + 1);?>" class="btn btn-sm btn-default" title="<?php echo ($num_pag + 1);?>"  data-placement="right"  data-original-title="<?php echo ($num_pag + 1);?>"><span class="fa fa-arrow-right"></span></a>
				<?php } ?>
			<?php } ?>
		</div>
	</td>
</tr>
	  </tbody>
	  <tfoot>
		<tr>
			<td>Totales</td>'.$htmladd3.'
		</tr>
	  </tfoot>
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
