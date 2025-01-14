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
$editapedido = trim($rspref->fields['edita_pedido']);
$mosrz = trim($rspref->fields['muestra_rz_ventas']);
//Eliminar
$chau = intval($_POST['chau']);
if ($chau > 0) {
    if ($borrar_ped == 'S') {
        $update = "Update tmp_ventares_cab 
        set 
        estado=6,
        anulado_el='$ahora',
        anulado_por=$idusu,
        anulado_idcaja = $idcaja
        where 
        idtmpventares_cab=$chau 
        and idempresa = $idempresa 
        and idsucursal = $idsucursal"    ;
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    } else {
        echo "<br /><br />Acceso Denegado! tu usuario no tiene permisos para borrar pedidos.";
        exit;
    }
}





$buscar = "Select *, (select usuarios.usuario from usuarios where usuarios.idusu = tmp_ventares_cab.idusu) as operador
from tmp_ventares_cab 
where 
finalizado='S' 
and registrado='N' 
and estado=1 
and (idmesa=0 or idmesa is null) 
and idcanal <> 3 
and idempresa = $idempresa 
and idsucursal = $idsucursal
 order by fechahora asc";
$rspedicu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpedidos = $rspedicu->RecordCount();


if ($rspref->fields['campo4_pedidos'] == 'C') {
    $ncamp4 = "Chapa / Otros";
}
if ($rspref->fields['campo4_pedidos'] == 'O') {
    $ncamp4 = "Operador";
}

?>
<?php if ($tpedidos > 0) {?>
<table width="100%" class="tablaconborde">
<tr>
    <td width="8%" height="38" align="center" bgcolor="#E1E1E1"><strong>Canal</strong></td>
    <td width="13%" align="center" bgcolor="#E1E1E1"><strong>Pedido</strong></td>
    <td width="50" align="center" bgcolor="#E1E1E1"><strong>Tomado</strong></td>
    <td width="38%" align="center" bgcolor="#E1E1E1"><strong><?php echo $ncamp4; ?></strong></td>
    <td width="28%" align="center" bgcolor="#E1E1E1"><strong>Monto</strong></td>
    <?php if ($editapedido == 'S') { ?>
    <td align="center" bgcolor="#E1E1E1"><strong>Editar</strong></td>
    <?php } ?>
    <td width="10" align="center" bgcolor="#E1E1E1"><img src="img/buscar16.png" width="16" height="16" alt="Detalle del Pedido" title="Detalle del Pedido" /></td>
    <td width="10" align="center" bgcolor="#E1E1E1"><img src="img/error1.png" width="20" height="20" alt="Eliminar" title="Eliminar" /></td>

</tr>
<?php

while (!$rspedicu->EOF) {
    $data = '';
    $rz = trim($rspedicu->fields['razon_social']);
    // chapa
    /*if($rspref->fields['campo4_pedidos'] == 'C'){
        $valcamp4=$rspedicu->fields['chapa'];

    }
    // operador
    if($rspref->fields['campo4_pedidos'] == 'O'){
        $valcamp4=$rspedicu->fields['operador'];
    }*/
    $valcamp4 = $rspedicu->fields['chapa'].'<br />OPER: '.$rspedicu->fields['operador'];

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
    $ipp = intval($rspedicu->fields['idtmpventares_cab']);
    ?>
<tr>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Pedido')" style="cursor:pointer"><img src="<?php echo  $img ?>" width="32px" height="32px" alt="<?php echo $canalnombre; ?>" title="<?php echo $canalnombre; ?>" /></td>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Pedido')" style="cursor:pointer"><?php echo $rspedicu->fields['idtmpventares_cab'] ?></td>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Pedido')" style="cursor:pointer"><?php echo date("d/m/Y H:i:s", strtotime($rspedicu->fields['fechahora'])); ?></td>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Pedido')" style="cursor:pointer"><?php echo $valcamp4 ?>  <?php if ($mosrz == 'S') {
        echo ' | '.$rz;
    }?></td>
    <td align="right" onClick="marca(<?php echo $rspedicu->fields['idtmpventares_cab'] ?>,'Pedido')" style="cursor:pointer"><?php echo formatomoneda($montoglobal) ?>
   </td>
   <?php if ($editapedido == 'S') { ?>
    <td align="center" ><a href="edicionpedido/index.php?pedido=<?php echo $ipp ?>">Editar</a></td>
    <?php } ?>
    <td align="center"><a href="#pop1" onMouseUp="asignardt(<?php echo $rspedicu->fields['idtmpventares_cab']?>);"><img src="img/buscar16.png" width="16" height="16" title="Detalle del Pedido" /></a></td>
    <td align="center"><a href="javascript:void(0)" onClick="chau(<?php echo $rspedicu->fields['idtmpventares_cab']?>)"><img src="img/error1.png" width="20" height="20"  /></a></td>


</tr>

<?php $rspedicu->MoveNext();
} ?>
</table>
<?php } else {?><br /><br />
<p align="center"><span  style="color:#00A737; font-weight:bold;">No existen pedidos pendientes de cobro.</span></p>

<?php } ?>
