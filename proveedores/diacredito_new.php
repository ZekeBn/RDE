<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
require_once("../includes/funciones_proveedor.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "24";

require_once("../includes/rsusuario.php");

//consulta
$consulta = "
    select descripcion, dias_credito from  tipo_credito where estado = 1 order by dias_credito
";

// valor seleccionado
$value_selected = '1';
if (isset($_POST['idcredito'])) {
    $value_selected = htmlentities($_POST['idcredito']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'diasvence',
    'id_campo' => 'diasvence',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'dias_credito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="verifica_diacredito();"'.$disabled,
    'autosel_1registro' => 'N'
];

// contruye campo
echo campo_select($consulta, $parametros_array);
