<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../categorias/preferencias_categorias.php");
$onchangeAdd = "";
if ($preferencias_usa_iva_variable = "S") {
    $onchangeAdd = ' onchange="verificar_concepto()" ';
}

// consulta
$consulta = "
SELECT idconcepto, descripcion
FROM cn_conceptos
where
estado = 1
and borrable = 'S'
order by descripcion asc
";

// valor seleccionado
if (isset($_POST['idconcepto'])) {
    $value_selected = htmlentities($_POST['idconcepto']);
} else {
    $value_selected = htmlentities($rs->fields['idconcepto']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idconcepto',
    'id_campo' => 'idconcepto',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idconcepto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' '.$onchangeAdd.' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
