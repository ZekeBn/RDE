<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
$dirsup = "S";
require_once("../includes/rsusuario.php");


// consulta
$consulta = "
SELECT id_categoria, nombre
FROM categorias
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idcategoria'])) {
    $value_selected = htmlentities($_POST['idcategoria']);
} else {
    $value_selected = htmlentities($rsminip->fields['idcategoria']);
    //echo $value_selected;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idcategoria',
    'id_campo' => 'idcategoria',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_categoria',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="subcategorias(this.value);"; '.$disabled,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
