 <?php
/*-----------------------------------------------
anula movimientos de caja en la nueva forma
UC: 10/09/2021



------------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

$ub = intval($_REQUEST['ub']);
$tipo = intval($_REQUEST['tipo']);
$regunico = intval($_REQUEST['regunico']);
$idautoriza = intval($_REQUEST['autoriza']);//Encaso que este activa, traera el id de quien autorizo
if ($idautoriza == 0) {
    $anuladopor = $idusu;
} else {
    $anuladopor = $idautoriza;

}
//Comprobar que posea los permisos de seguridad para efectuar anulaciones



//segun el tipo proceder



if ($tipo == 1) {
    //retiro de efectivo
    $idunico = intval($_REQUEST['regunico']);

    $buscar = "Select * from caja_retiros where regserialretira=$idunico and estado=1";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $idpago = intval($rs->fields['idpago']);
    //actualizamos el pago
    $update = "update gest_pagos set anulado_por=$anuladopor,anulado_el=current_timestamp,estado=6 where idpago=$idpago and idtipocajamov=8 ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    //por ultimo desactivamos
    $update = "update caja_retiros set borrado_por=$anuladopor,borrado_el=current_timestamp,estado=6 where regserialretira=$idunico and estado=1 ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    if ($ub == 0) {
        header("Location: gest_administrar_caja_new.php#ultcajamov");
    }
    if ($ub == 1) {
        header("Location: teso_abrir_caja.php");
    }
    exit;
}
if ($tipo == 2) {
    //reposicion de efectivo

    $idunico = intval($_REQUEST['regunico']);

    $buscar = "Select * from caja_reposiciones where regserialentrega=$idunico and estado=1";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $idpago = intval($rs->fields['idpago']);
    //actualizamos el pago
    $update = "update gest_pagos set anulado_por=$anuladopor,anulado_el=current_timestamp,estado=6 where idpago=$idpago and idtipocajamov=7 ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    //por ultimo desactivamos
    $update = "update caja_reposiciones set borrado_por=$anuladopor,borrado_el=current_timestamp,estado=6 where regserialentrega=$idunico and estado=1 ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    if ($ub == 0) {
        header("Location: gest_administrar_caja_new.php#ultcajamov");
    }
    if ($ub == 1) {
        header("Location: teso_abrir_caja.php");
    }
    exit;




}
if ($tipo == 3) {
    //pago x caja
    $idunico = intval($_REQUEST['regunico']);

    $consulta = "
    select * 
    from pagos_extra 
    where 
    unis = $idunico
    and estado = 1
    limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $el = intval($rs->fields['unis']);
    $idcaja = intval($rs->fields['idcaja']);
    $idpago = intval($rs->fields['idpago']);

    if ($idpago > 0) {

        $consulta = "
        update gest_pagos set estado = 6 where idpago = $idpago
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $update = "Update pagos_extra set estado=6,anulado_el='$ahora',anulado_por=$idusu where unis=$el";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

    }

    if ($ub == 0) {
        header("Location: gest_administrar_caja_new.php#ultcajamov");
    }
    if ($ub == 1) {
        header("Location: teso_abrir_caja.php");
    }
    exit;

}
?>
