<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
$dirsup = "S";
require_once("../includes/rsusuario.php");
// consulta
$consulta = "select * from marca where idempresa = $idempresa and  idestado = 1";

// valor seleccionado
if (isset($_POST['idmarca'])) {
    $value_selected = htmlentities($_POST['idmarca']);
} else {
    $value_selected = htmlentities($rs->fields['idmarca']);
}

// parametros
$parametros_array = [
'nombre_campo' => 'idmarca',
'id_campo' => 'idmarca',

'nombre_campo_bd' => 'marca',
'id_campo_bd' => 'idmarca',

'value_selected' => $value_selected,

'pricampo_name' => 'Seleccionar...',
'pricampo_value' => '',
'acciones' => ' class="form-control" '
];

// construye campo
echo campo_select($consulta, $parametros_array);

?>          