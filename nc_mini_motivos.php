<?php

/*------------------------
Script para select
21/08/2023

---------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
require_once("includes/rsusuario.php");
$ahora = date("Y-m-d H:i:s");
$agregar = intval($_POST['agregar']);
if ($agregar > 0) {
    $nombre = antisqlinyeccion($_POST['nombre'], 'text');
    $buscar = "Select * from nota_cred_motivos_cli where descripcion=$nombre and estado=1";
    $rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rsbb->fields['descripcion'] == '') {
        $insertar = "insert into nota_cred_motivos_cli (descripcion,estado,registrado_por,registrado_el) values ($nombre,1,$idusu,'$ahora')";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        echo "OK";
        exit;
    }
}
$eliminar = intval($_POST['eliminar']);
if ($eliminar > 0) {
    $update = "Update nota_cred_motivos_cli set estado=6,anulado_por=$idusu,anulado_el='$ahora' where idmotivo=$eliminar";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    echo "OK";
    exit;
}

// consulta
$consulta = "
SELECT idmotivo, descripcion
FROM nota_cred_motivos_cli
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idmotivo'])) {
    $value_selected = htmlentities($_POST['idmotivo']);
} else {
    $value_selected = htmlentities($rs->fields['idmotivo']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmotivo',
    'id_campo' => 'idmotivo',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idmotivo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
