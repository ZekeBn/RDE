<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "183";
$dirsup = 'S';
require_once("../includes/rsusuario.php");
?>
<div class="col-md-6 col-sm-12 col-xs-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">*Moneda Extranjera <a href="javascript:void(0)" onclick="agregar_moneda_modal()" class="btn btn-sm btn-default "><span class="fa fa-plus"></span></a> :</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <?php
            // consulta
            $consulta = "select * from tipo_moneda  where estado = 1 and idempresa = $idempresa and borrable = 'S' and nacional = 'N' ";

// valor seleccionado
if (isset($_POST['idtipo'])) {
    $value_selected = htmlentities($_POST['tipo_moneda']);
} else {
    $value_selected = htmlentities($rs->fields['idtipo']);
}

// parametros
$parametros_array = [
'nombre_campo' => 'tipo_moneda',
'id_campo' => 'tipo_moneda',

'nombre_campo_bd' => 'descripcion',
'id_campo_bd' => 'idtipo',

'value_selected' => $value_selected,

'pricampo_name' => 'Seleccionar...',
'pricampo_value' => '',
'style_input' => 'class="form-control"',
'autosel_1registro' => 'N'
];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>