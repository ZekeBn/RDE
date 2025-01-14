<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "55";

require_once("includes/rsusuario.php");
$id = intval($_GET['id']);
set_time_limit(0);

$dato = [
    1 => ['idsucu','nombre','sucursales'],
    2 => ['id_categoria','nombre','categorias'],
    3 => ['id_medida','nombre','medidas'],
    4 => ['idsubcate','descripcion','sub_categorias'],
    5 => ['idgrupoinsu','nombre','grupo_insumos'],
    6 => ['idproveedor','nombre','proveedores'],
    7 => ['idconcepto','descripcion','cn_conceptos'],
    8 => ['idcentroprod','descripcion','produccion_centros'],
    9 => ['idagrupacionprod','agrupacion_prod','produccion_agrupacion'],
    10 => ['idserieun','cuenta_completo','cn_plancuentas_detalles'],
    11 => ['idimpresoratk','descripcion','impresoratk '],
    12 => ['idmarca','marca','marca']
];

$col1 = $dato[$id][0]; // id
$col2 = $dato[$id][1]; // descripcion
$tabla = $dato[$id][2]; // tabla

function limpiadepo($texto)
{
    $result = "";
    //eliminando etiquetas html
    $texto = strip_tags($texto);
    //compruebo que los caracteres sean los permitidos
    $permitidos = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ";
    for ($i = 0; $i < strlen($texto); $i++) {
        if (strpos($permitidos, substr($texto, $i, 1)) === false) {
            //echo substr($texto,$i,1);
        } else {
            $result = $result.substr($texto, $i, 1);
        }
    }
    return $result;
}

function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    return $txt;
}


// INICIO CASOS ESPECIALES

// plan de cuentas
if ($id == 10) {
    $col3 = 'descripcion '; //
    $col_add = ','.$col3;
}

//Se consulta el codigo y descripcion de la varible recibida
if ($id == 11) {
    $filtrosucursal = "";
    $idsucursal = intval($_GET['idsucu']);
    if ($idsucursal > 0) {
        $filtrosucursal = " and idsucursal = $idsucursal";
    }

    $condicion = " inner join sucursales on 
    sucursales.idsucu = impresoratk.idsucursal 
    where borrado ='N' and tipo_impresora ='COC' 
    $filtrosucursal";
    $col2 = $col2.",idsucursal,sucursales.nombre as sucursal";
} else {
    $condicion = " where estado = 1 ";
}

// FIN CASOS ESPECIALES


$buscar = "
select ".$col1.",".$col2." ".$col_add." 
from ".$tabla.$condicion." 
order by ".$col1;
//echo $buscar;exit;
$rsconsulta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$rs = $rsconsulta;
$impreso = date("d/m/Y H:i:s");
$rs = $rsconsulta;
$datos = "";


// asigna los datos de la consulta a una variable
$array = $rs->fields;

// CONSTRUYE CABECERA
$array = $rs->fields;
foreach ($array as $key => $value) {
    $i++;
    $datos .= limpiacsv($key).';';
}
reset($array);
$datos .= $saltolinea;

//CONSTRUYE CUERPO
$ante = 0;
$fila = 1;
while (!$rs->EOF) {
    $fila++;
    $array = $rs->fields;
    $i = 0;
    foreach ($array as $key => $value) {
        $i++;
        $datos .= limpiacsv($value).';';
    }
    $datos .= $saltolinea;
    $rs->MoveNext();
}



$impreso = date("d/m/Y H:i:s");

header('Content-Description: File Transfer');
header('Content-Type: application/force-download');
header('Content-Disposition: attachment; filename=Datos_csv_'.$tabla.'_'.$impreso.'.csv');
echo $datos;
exit;
