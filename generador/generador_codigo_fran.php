<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
$conexion = $conexion;

// Modulo y submodulo respectivamente
/*$modulo="1";
$submodulo="1";
require_once("../includes/rsusuario.php"); */


if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1') {
    echo "acceso denegado ".$_SERVER['REMOTE_ADDR'];
    exit;
}


/*
        'categorias' => $categorias_ar,
        'subcategorias' => $subcategorias_ar,
        'marcas' =>  $marcas_ar,
        'productos' =>  $productos_ar,
        'insumos' =>  $insumos_ar,
        'ingredientes' => $ingredientes_ar,
        'recetas' => $recetas_ar,
        'recetas_det' => $recetas_det_ar,
        'productos_sucursales' => $productos_sucursales_ar,
        'productos_combinado' => $productos_combinado_ar,
        'combos_listas' => $combos_listas_ar,
        'combos_listas_det' => $combos_listas_det_ar,
        'agregado' => $agregado_ar,
        'prod_lista_objetivos' => $prod_lista_objetivos_ar,
        'recetas_produccion' => $recetas_produccion_ar,
        'recetas_detalles_produccion' => $recetas_detalles_produccion_ar,
        'productos_franquicias' => $productos_franquicias_ar,
        'productos_listaprecios_fran' => $productos_listaprecios_fran_ar,
        'cn_grupos' => $cn_grupos_ar,
        'cn_conceptos' => $cn_conceptos_ar,
        'lista_precios_venta' => $lista_precios_venta_ar,
        'canal_venta' => $canal_venta_ar,
        'productos_listaprecios' => $productos_listaprecios_ar,
*/


$tablas_lista = '
categorias_tmp
sub_categorias_tmp
productos_tmp
insumos_lista_tmp
recetas_tmp
recetas_detalles_tmp
productos_combinado_tmp
combos_listas_tmp
combos_listas_det_tmp
agregado_tmp
productos_sucursales_tmp
prod_lista_objetivos_tmp
recetas_produccion_tmp
recetas_detalles_produccion_tmp
ingredientes_tmp
productos_franquicias
cn_grupos_tmp
cn_conceptos_tmp
lista_precios_venta_tmp
productos_listaprecios_tmp
canal_venta_tmp

marcas_tmp
productos_listaprecios_fran
';
$tablas_lista = nl2br(trim($tablas_lista));
//echo $tablas_lista;
$tablas_lista_ar = explode("<br />", $tablas_lista);
// recorre y limpia saltos de linea
$i = 0;
foreach ($tablas_lista_ar as $tabla) {
    $tabla_ar[$i] = trim($tabla);
    $i++;
}

foreach ($tabla_ar as $tabla_item) {


    $tabla = strtolower(antisqlinyeccion(trim($tabla_item), "text-notnull"));
    //$table_schema=$database;
    $tabla_sintmp = explode('_tmp', $tabla)[0];
    //echo $tabla;
    $consulta = "	
	SHOW COLUMNS 
	from $tabla
	";
    //echo $consulta;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




    $array = $rs->fields;
    $columnas = [];

    $i = 0;
    while (!$rs->EOF) {
        $columnas[$i]['nombre'] = $rs->fields['Field'];
        $columnas[$i]['tipo'] = $rs->fields['Type'];
        $columnas[$i]['nulo'] = $rs->fields['Null']; // YES or NO
        $columnas[$i]['primaria'] = $rs->fields['Key']; // PRI or ''
        $columnas[$i]['extra'] = $rs->fields['Extra']; //auto_increment

        $i++;
        $rs->MoveNext();
    }
    //print_r($columnas);

    $colinsert = '';
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


        $i++;
    }


    $colinsert = substr($colinsert, 0, -2);
    $valorinsert = substr($valorinsert, 0, -2);
    $colupdate = substr(rtrim($colupdate), 0, -1);
    $valorupdate = substr($valorupdate, 0, -2);





    // generar inicio de condicion para insercion
    $codigogen = '';

    $codigogen .= '
<pre>
/* INICIO TABLA '.$tabla.' */

// vaciar tabla temporal
$consulta="truncate table $bd_franquicia.'.$tabla.';";
if($mostrar_consulta == "S"){
	echo $consulta."&lt;br /&gt;";
}else{
	$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
}

// insert de la tabla: '.$tabla.'
$consulta="
insert into $bd_franquicia.'.$tabla.'
('.$colinsert.')
SELECT 
'.$colinsert.'
from $bd_master.'.$tabla_sintmp.';
";
if($mostrar_consulta == "S"){
	echo $consulta."&lt;hr /&gt;";
}else{
	$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
}
</pre>
';





    echo $codigogen;


}
