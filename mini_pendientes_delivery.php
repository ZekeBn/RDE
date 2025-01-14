 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
if ($idcaja == 0) {
    echo "<br /><br />-Debes tener una caja abierta.<br /><br />";
    exit;
}

//Traemos las preferencias para la empresa
$buscar = "Select * from preferencias where idempresa=$idempresa ";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$borrar_ped = trim($rspref->fields['borrar_ped']);

//Eliminar
$chau = intval($_POST['chau']);
if ($chau > 0) {
    if ($borrar_ped == 'S') {
        $update = "
        Update tmp_ventares_cab 
        set 
        estado=6,
        anulado_el='$ahora',
        anulado_por=$idusu,
        anulado_idcaja = $idcaja
        where 
        idtmpventares_cab=$chau 
        and idempresa = $idempresa 
        and idsucursal = $idsucursal
        ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    } else {
        echo "<br /><br />Acceso Denegado! tu usuario no tiene permisos para borrar pedidos.";
        exit;
    }
}

// caja abierta
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = $rscaja->fields['idcaja'];


$buscar = "Select * from tmp_ventares_cab where finalizado='S' and registrado='N' and estado=1 and (idmesa=0 or idmesa is null) and idcanal = 3
 and idempresa = $idempresa and idsucursal = $idsucursal  
 order by fechahora asc";
$rspedicu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpedidos = $rspedicu->RecordCount();


$consulta = "
    Select *, total_cobrado as totalpend  
    from gest_pagos 
        where 
        cajero=$idusu  
        and estado=1 
        and idcaja=$idcaja 
        and rendido ='N'
        and idempresa = $idempresa
        order by fecha desc
    ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<?php if ($tpedidos > 0) {?>
<table width="100%" class="tablaconborde">
<tr>
    <td width="8%" height="38" align="center" bgcolor="#E1E1E1"><strong>Canal</strong></td>
    <td width="13%" align="center" bgcolor="#E1E1E1"><strong>Pedido</strong></td>
    <td width="50" align="center" bgcolor="#E1E1E1"><strong>Tomado</strong></td>
    <td width="38%" align="center" bgcolor="#E1E1E1"><strong>Direcccion</strong></td>
    <td width="28%" align="center" bgcolor="#E1E1E1"><strong>Monto</strong></td>
    <td width="10" align="center" bgcolor="#E1E1E1"><img src="img/buscar16.png" width="16" height="16" alt="Detalle del Pedido" title="Detalle del Pedido" /></td>
    <td width="10" align="center" bgcolor="#E1E1E1"><img src="img/error1.png" width="20" height="20" alt="Eliminar" title="Eliminar" /></td>

</tr>
<?php while (!$rspedicu->EOF) {
    $data = '';
    $data = $rspedicu->fields['direccion'];
    /*if ($rspedicu->fields['chapa']!=''){
        $data=$rspedicu->fields['chapa'];
    }
    if ($data!=''){
        if($rspedicu->fields['observacion']!=''){
            $data=$data.'/'.$rspedicu->fields['observacion'];
        }
    } else {
        $data=$rspedicu->fields['observacion'];

    }*/

    $canal = intval($rspedicu->fields['idcanal']);
    if ($canal == 1) {
        //tab
        $img = "img/tb1.png";
        $canalnombre = "Calle";
    }
    if ($canal == 2) {
        //caja
        $img = "img/cashier.png";
        $canalnombre = "Local";
    }
    if ($canal == 3) {
        //delivery
        $img = "img/caarritus.png" ;
        $canalnombre = "Delivery";

    }
    $totalventa = intval($rspedicu->fields['monto']);
    $delivery = intval($rspedicu->fields['delivery_costo']);
    $montoglobal = $totalventa + $delivery;
    ?>
<tr>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Delivery')" style="cursor:pointer"><img src="<?php echo  $img ?>" width="32px" height="32px" alt="<?php echo $canalnombre; ?>" title="<?php echo $canalnombre; ?>" /></td>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Delivery')" style="cursor:pointer"><?php echo $rspedicu->fields['idtmpventares_cab'] ?></td>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Delivery')" style="cursor:pointer"><?php echo date("d/m/Y H:i:s", strtotime($rspedicu->fields['fechahora'])); ?></td>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Delivery')" style="cursor:pointer;"><?php echo wordwrap($data, 15, " ", true); ?></td>
    <td align="right" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Delivery')" style="cursor:pointer"><?php echo formatomoneda($montoglobal) ?>
   </td>
    <td align="center"><a href="#pop1" onMouseUp="asignardt(<?php echo $rspedicu->fields['idtmpventares_cab']?>);"><img src="img/buscar16.png" width="16" height="16" title="Detalle del Pedido" /></a></td>
    <td align="center"><a href="javascript:void(0)" onClick="chau(<?php echo $rspedicu->fields['idtmpventares_cab']?>)"><img src="img/error1.png" width="20" height="20"  /></a></td>


</tr>

<?php $rspedicu->MoveNext();
} ?>
</table>
<?php } else {?><br /><br />
<p align="center"><span style="color:#00A737; font-weight:bold;">No existen deliverys pendientes de cobro.</span></p>
<?php } ?>
<?php if ($rs->fields['idpago'] > 0) { ?>
<br /><hr /><br />
<p align="center"><strong>Delivery's no Rendidos:</strong></p>
<table width="100%" class="tablaconborde">
      <tbody>
        <tr align="center" bgcolor="#F8FFCC">
          <td>Fecha/Hora</td>
          <td>Cod Venta</td>
          <td>Monto</td>
          <td>[Rendido]</td>
        </tr>
<?php while (!$rs->EOF) {    ?>
        <tr>
          <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha'])); ?></td>
          <td align="center"><?php echo $rs->fields['idventa']; ?></td>
          <td align="center"><?php echo formatomoneda($rs->fields['totalpend']); ?></td>
          <td align="center"><a href="delivery_norendidos_rendir.php?id=<?php echo $rs->fields['idpago']; ?>&m=gvr">[Rendido]</a></td>
        </tr>
<?php $rs->MoveNext();
} ?>
      </tbody>
    </table>
<?php } ?>
