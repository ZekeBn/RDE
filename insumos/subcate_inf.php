<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "11";
$submodulo = "302";
$dirsup = "S";
require_once("../includes/rsusuario.php");

//print_r($_POST);
$idcategoria = intval($_REQUEST['idcategoria']);
if ($idcategoria > 0) {

    // consulta
    $consulta = "
	SELECT idsubcate, descripcion
	FROM sub_categorias
	where
	estado = 1
	and idcategoria = $idcategoria
	order by descripcion asc
	 ";


} else {
    // consulta
    $consulta = "
	SELECT idsubcate, descripcion
	FROM sub_categorias
	where
	estado = 1
	and idcategoria is null
	order by descripcion asc
	 ";


}

// valor seleccionado
if (isset($_REQUEST['idsubcate'])) {
    $value_selected = htmlentities($_REQUEST['idsubcate']);
} else {
    $value_selected = htmlentities($rs->fields['idsubcate']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsubcate',
    'id_campo' => 'idsubcate',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idsubcate',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
