<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
$dirsup = "S";
require_once("includes/rsusuario.php");

//print_r($_POST);
if (intval($_POST['idsub_categoria']) > 0) {
    $idsubcate = intval($_POST['idsub_categoria']);
} else {
    $idsubcate = htmlentities($rsminip->fields['idsub_categoria']);
}

if (intval($_POST['idcategoria']) > 0) {
    $idcategoria = intval($_POST['idcategoria']);
} else {
    $idcategoria = htmlentities($rsminip->fields['idcategoria']);
}

$whereadd = null;
$joinadd = null;
if (intval($idcategoria) > 0) {
    $whereadd = " and sub_categorias.idcategoria=$idcategoria ";
}
if (intval($idsubcate) > 0) {

    // consulta
    $consulta = "
	SELECT sub_categorias_secundaria.idsubcate_sec, sub_categorias_secundaria.descripcion,sub_categorias_secundaria.idsubcate,sub_categorias.idcategoria
	FROM sub_categorias_secundaria
	INNER JOIN sub_categorias on sub_categorias.idsubcate = sub_categorias_secundaria.idsubcate
    INNER JOIN categorias on categorias.id_categoria = sub_categorias.idcategoria
	where
	sub_categorias_secundaria.estado = 1
	and sub_categorias_secundaria.idsubcate = $idsubcate
	$whereadd
	order by sub_categorias_secundaria.descripcion asc
	";


} elseif (intval($idcategoria) > 0) {
    // consulta
    $consulta = "
	SELECT sub_categorias_secundaria.idsubcate_sec,
	 sub_categorias_secundaria.descripcion,sub_categorias_secundaria.idsubcate,sub_categorias.idcategoria  as categoria_id
	FROM sub_categorias_secundaria
	INNER JOIN sub_categorias on sub_categorias.idsubcate = sub_categorias_secundaria.idsubcate
    INNER JOIN categorias on categorias.id_categoria = sub_categorias.idcategoria
	where
	sub_categorias_secundaria.estado = 1
	$whereadd
	order by sub_categorias_secundaria.descripcion asc
	";
} else {
    // consulta
    $consulta = "
	SELECT sub_categorias_secundaria.idsubcate_sec, 
	sub_categorias_secundaria.descripcion, 
	sub_categorias_secundaria.idsubcate, 
	sub_categorias.idcategoria as categoria_id
	FROM sub_categorias_secundaria
	INNER JOIN sub_categorias on sub_categorias.idsubcate = sub_categorias_secundaria.idsubcate
    INNER JOIN categorias on categorias.id_categoria = sub_categorias.idcategoria
	where
	sub_categorias_secundaria.estado = 1
	order by descripcion asc
	 ";


}
// echo $consulta;
// echo $_POST['idsubcate_sec'];exit;

// valor seleccionado
if (isset($_POST['idsubcate_sec'])) {
    $value_selected = htmlentities($_POST['idsubcate_sec']);
} elseif (isset($idsubcate_sec)) {
    $value_selected = $idsubcate_sec;
} else {
    $value_selected = htmlentities($rsminip->fields['idsubcate_sec']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsubcate_sec',
    'id_campo' => 'idsubcate_sec',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idsubcate_sec',

    'value_selected' => $value_selected,
    'data_hidden' => "categoria_id",
    'data_hidden2' => "idsubcate",
    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' onchange="subcategorias_secundarias(this)"  '.$disabled,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
