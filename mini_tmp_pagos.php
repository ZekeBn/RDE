 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "259";
require_once("includes/rsusuario.php");


$formapago = floatval($_POST['forma']);
$monto = floatval($_POST['monto']);
$eliminar = intval($_POST['eliminar']);
if ($eliminar > 0) {
    $idcobser = intval($_POST['idcobser']);
    if ($idcobser > 0) {
        $delete = "delete from tarjetas_cobros_deta where idcobser=$idcobser"    ;
        $conexion->Execute($delete) or die(errorpg($conexion, $delete));


    }



}




$buscar = "Select * from tarjetas_transacciones where cajero=$idusu and estado=1";
$rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtransaccion = $rst->fields['idtrans'];


//Deuda global
$buscar = "Select sum(monto) as totald from carrito_tarjeta where idusu=$idusu  ";
$rsdeuda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tdeuda = floatval($rsdeuda->fields['totald']);
//Suma de pagos parciales
$buscar = "Select  sum(montoabonado) as mabo from tarjetas_cobros_deta where registrado_por=$idusu and estadopago=1";
$rspp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tparcial = floatval($rspp->fields['mabo']);

//saldo activo

$saldo = $tdeuda - $tparcial;

if ($monto > 0) {
    //echo $deudamesa;
    //ver que el monto no supere la deuda
    if (($montopago <= $saldo)) {
        //Insertamos y recalculamos para mostrar
        $insertar = "Insert into tarjetas_cobros_deta
        (idformapago,montoabonado,estadopago,idcliente,idtarjeta,registrado_el,registrado_por,idtrans)
        values
        ($formapago,$monto,1,0,0,'$ahora',$idusu,$idtransaccion)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $respuesta = 1;
    } else {
        //Mostrar error de pago
        echo 'Monto ingresado supera la deuda';

    }



}

//Suma de pagos parciales
$buscar = "Select  sum(montoabonado) as mabo from tarjetas_cobros_deta where registrado_por=$idusu and estadopago=1";
$rspp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tparcial = floatval($rspp->fields['mabo']);

//saldo activo

$saldo = $tdeuda - $tparcial;






//Traemos los registros de pagos

$buscar = "Select idventa,idcobser,descripcion,montoabonado,estadopago,usuario,tarjetas_cobros_deta.registrado_el
from tarjetas_cobros_deta
inner join usuarios on usuarios.idusu=tarjetas_cobros_deta.registrado_por
inner join formas_pago on formas_pago.idforma=tarjetas_cobros_deta.idformapago
where estadopago=1  order by tarjetas_cobros_deta.registrado_el desc";

$resupag = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpagos = $resupag->RecordCount();
?>





<table class="tablalinda2">
    <tr>
        <td colspan="2" bgcolor="#DFDFDF"><h2><strong>Neto deuda: </strong></h2></td>
      <td><?php echo formatomoneda($tdeuda, 4, 'N');?></td>
    </tr>
    <tr>
        <td colspan="2" bgcolor="#DFDFDF"><h2><strong>Pagos Parciales:</strong></h2></td>
      <td><?php echo formatomoneda($tparcial, 4, 'N');?></td>
    </tr>
    <tr>
        <td colspan="2" bgcolor="#DFDFDF"><h2><strong>Saldo activo</strong></h2></td>
      <td><?php echo formatomoneda($saldo, 4, 'N');?></td>
    </tr>
    
    <tr>
      <td height="33" align="center" bgcolor="#DFDFDF"><strong>Monto Abonado</strong></td>
      <td align="center" bgcolor="#DFDFDF"><strong>Medio Pago</strong></td>
      <td align="center" bgcolor="#DFDFDF"><strong>Registrado por</strong></td>
      <td align="center" bgcolor="#DFDFDF"><strong>Registrado el</strong></td>
      <td align="center" bgcolor="#DFDFDF"></td>
  </tr>
         
              <?php while (!$resupag->EOF) {
                  $idc = intval($resupag->fields['idcliente']);
                  if ($idc > 0) {
                      $buscar = "Select razon_social from cliente where idcliente=$idc";
                      $rsz = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                      $rz = $rsz->fields['razon_social'];
                  }

                  ?>
              <tr>
                 
                  <td ><?php echo formatomoneda($resupag->fields['montoabonado'])?></td>
                  <td ><?php echo ($resupag->fields['descripcion'])?></td>
                  <td ><?php echo ($resupag->fields['usuario'])?></td>
                  <td ><?php echo date("d/m/Y H:i:s", strtotime($resupag->fields['registrado_el']));?></td>
                  <td><?php if (intval($resupag->fields['idventa']) == 0) {?>
                  <a href="javascript:void(0)" onClick="chaupago(<?php echo $resupag->fields['idcobser']?>)">[X]</a>
                  <?php } else {
                      echo 'COB';
                  }?></td>
            </tr>
              <?php $resupag->MoveNext();
              }?>
              
              <?php if ($saldo == 0) {?>
      <td colspan="5" align="center"><button type="button" id="cierremesa" name="cierremesa" class="btn-btn-round btn-primary btn-sm" onClick="cerrar_venta();"><span class="fa fa-close"></span>&nbsp;&nbsp;Generar Venta</button></td>
              
        <?php } ?>
        
</table>
