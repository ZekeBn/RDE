<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
$dirsup = "S";
require_once("../includes/rsusuario.php");

//print_r($_POST);
if (intval($_POST['idcategoria']) > 0) {
    $idcategoria = intval($_POST['idcategoria']);
} else {
    $idcategoria = htmlentities($rsminip->fields['idcategoria']);
}
if (intval($_POST['idsubcate']) > 0) {
    $idsubcate = intval($_POST['idsubcate']);
} else {
    $idsubcate = htmlentities($rsminip->fields['idsubcate']);
}
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


} elseif ($idsubcate > 0) {
    $consulta = "
	SELECT idsubcate, descripcion
	FROM sub_categorias
	where
	estado = 1
	and idsubcate = $idsubcate
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
if (isset($_POST['idsubcate'])) {
    $value_selected = htmlentities($_POST['idsubcate']);
} else {
    $value_selected = htmlentities($rsminip->fields['idsubcate']);
}
// parametros
$parametros_array = [
    'nombre_campo' => 'idsubcate',
    'id_campo' => 'idsubcate',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idsubcate',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="subcategorias_secundarias(this.value);" '.$disabled,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
