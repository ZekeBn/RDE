 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");

$idatc = intval($_POST['idatc']); // atc actual
$asignar_mozo = trim($_POST['asignar_mozo']);
$idmozo = intval($_POST['idmozo']);


// asignar_mozo
if ($asignar_mozo == 'S') {
    if ($idmozo == 0) {
        echo "No se envio el idmozo";
        exit;
    }
    $consulta = "
    update mesas_atc set idmozo = $idmozo where idatc = $idatc
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}


// validar que la mesa y el atc coincidan
$buscar = "Select * from mesas_atc where idatc = $idatc and estado<>3  and estado<>6 and idsucursal= $idsucursal limit 1";
$rsval = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idmozo = intval($rsval->fields['idmozo']);
if (intval($rsval->fields['idatc']) == 0) {
    echo "El atc no coincide con la mesa.";
    exit;
}

$buscar = "Select usuario as mozo from usuarios where idusu = $idmozo ";
$rsmoz = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$mozo = $rsmoz->fields['mozo'];






if ($_POST['edit'] != 'S') {
    ?>Mozo: <?php if (trim($mozo) != '') {
        echo antixss($mozo);
    } else {
        echo "No Asignado";
    } ?> <a href="javascript:asignar_mozo_edit(<?php echo $idatc; ?>);void(0);" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Cambiar</a>
<?php } else {  ?>
<div class="col-md-12 col-sm-12 form-group">
    <label class="control-label col-md-4 col-sm-4 col-xs-12">Mozo *</label>
    <div class="col-md-8 col-sm-8 col-xs-12">
    <?php

        // consulta
        $consulta = "
    SELECT idusu as idmozo_asignado, usuario
    FROM usuarios
    where
    estado = 1
    order by usuario asc
    ";


    // valor seleccionado
    if (isset($_POST['idmozo_asignado'])) {
        $value_selected = htmlentities($_POST['idmozo_asignado']);
    } else {
        $value_selected = htmlentities($mozo);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idmozo_asignado',
        'id_campo' => 'idmozo_asignado',

        'nombre_campo_bd' => 'usuario',
        'id_campo_bd' => 'idmozo_asignado',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S',
        //'opciones_extra' => $opciones_extra,

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);
    ?>
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="button" class="btn btn-success" onclick="asignar_mozo(<?php echo $idatc; ?>);" ><span class="fa fa-check-square-o"></span> Asignar</button>
        </div>
    </div>
<div class="clearfix"></div>
<?php } ?>
