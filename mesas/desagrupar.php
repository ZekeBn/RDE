 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("../includes/rsusuario.php");

$idmesa = intval($_POST['idmesa']);
$consulta = "
select * from mesas where idmesa = $idmesa limit 1
";
$rsme = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
/*
$consulta="
select * from mesas_atc where idmesa = $idmesa order by idatc desc limit 1
";
$rsmeatc=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$idatc=intval($rsmeatc->fields['idatc']);
if(intval($idatc) == 0){
    echo "ATC Inexistente!";
    exit;
}
*/
$consulta = "
select * from mesas_atc_grupo_deta where idmesa = $idmesa
";
$rsmeatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idatc = intval($rsmeatc->fields['idatc']);
if (intval($idatc) == 0) {
    echo "ATC Inexistente!";
    exit;
}


if ($_POST['conf'] == 'S') {



    $consulta = "
    delete from tmp_mesitasele where idmesa = $idmesa
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // elimina la mesa del detalle de la agrupacion
    $consulta = "
    delete FROM `mesas_atc_grupo_deta` where idmesa = $idmesa
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
    delete from mesas_atc_grupos_cab 
    where 
    idatc not in (select idatc from mesas_atc_grupo_deta where idatc is not null)
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
    update mesas set 
    agrupado_con = NULL, 
    estado_mesa = 1
    where
    idmesa = $idmesa
    and agrupado_con is not null
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    echo "OK";
    exit;
}

?>
Esta seguro que desea liberar la mesa Agrupada?<br />
Mesa Numero: <?php echo $rsme->fields['numero_mesa']; ?>

<br /><br />
<div class="col-md-12 col-sm-12 col-xs-12 form-group has-feedback">
        <button type="button" class="btn-btn-round btn-success btn-sm" onclick="desagrupar_confirma(<?php echo $idmesa; ?>);"><span class="fa fa-add">
        </span>&nbsp;&nbsp;Liberar Mesa</button>
        <button type="button" class="btn-btn-round btn-primary btn-sm" onclick="$('#modpop').modal('hide');"><span class="fa fa-add">
        </span>&nbsp;&nbsp;Cancelar</button>
    </div>
