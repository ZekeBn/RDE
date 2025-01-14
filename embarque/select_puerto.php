<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "598";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");

// consulta

$consulta = "
				SELECT idpuerto, descripcion
				FROM puertos
				where
				estado = 1
				order by descripcion asc
				";

// valor seleccionado
if (isset($_POST['idpuerto'])) {
    $value_selected = htmlentities($_POST['idpuerto']);
} else {
    $value_selected = $rs->fields['idpuerto'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idpuerto',
    'id_campo' => 'idpuerto',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idpuerto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
      'data_hidden' => 'idvias_embarque',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="cambiar_vias_embarque(this)" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
