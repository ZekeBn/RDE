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
$idmesa = intval($_POST['mesa']);
if ($idmesa > 0) {
    if ($borrar_ped == 'S') {
        $update = "
        Update tmp_ventares_cab 
         set 
         estado=6,
         anulado_el='$ahora',
         anulado_por=$idusu,
         anulado_idcaja = $idcaja
        where 
        finalizado='S' 
        and registrado='N' 
        and estado=1 
        and idmesa=$idmesa
        and idempresa=$idempresa
        and idsucursal=$idsucursal
        "    ;
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    } else {
        echo "<br /><br />Acceso Denegado! tu usuario no tiene permisos para borrar pedidos.";
        exit;
    }
}





$buscar = "
Select sum(monto) as total, mesas.numero_mesa, salon.nombre, mesas.idmesa, salon.color
from tmp_ventares_cab 
inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa 
INNER JOIN salon on mesas.idsalon = salon.idsalon 
where 
finalizado='S' 
and registrado='N' 
and estado=1 
and tmp_ventares_cab.idsucursal = $idsucursal
and tmp_ventares_cab.idempresa = $idempresa
GROUP by mesas.idmesa 
order by salon.nombre asc, mesas.numero_mesa asc
";
$rspedicu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpedidos = $rspedicu->RecordCount();


?>
<?php if ($tpedidos > 0) {?>
<table width="100%" class="tablaconborde">
<tr>
    <td width="13%" height="38" align="center" bgcolor="#E1E1E1"><strong>Mesa</strong></td>
    <td width="50" align="center" bgcolor="#E1E1E1"><strong>Salon</strong></td>
    <td width="28%" align="center" bgcolor="#E1E1E1"><strong>Monto</strong></td>
    <td width="10" align="center" bgcolor="#E1E1E1"><img src="img/buscar16.png" width="16" height="16" alt="Detalle del Pedido" title="Detalle del Pedido" /></td>
        <td width="10" align="center" bgcolor="#E1E1E1"><img src="img/error1.png" width="20" height="20" alt="Eliminar" title="Eliminar" /></td>

</tr>
<?php while (!$rspedicu->EOF) {
    $data = '';
    $data = $rspedicu->fields['observacion'];
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

    $totalmesa = intval($rspedicu->fields['total']);
    ?>
<tr bgcolor="<?php echo $rspedicu->fields['color'] ?>">
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idmesa'] ?>,'Mesa')" style="cursor:pointer"><?php echo $rspedicu->fields['numero_mesa'] ?></td>
    <td align="center" onClick="marca(<?php echo $rspedicu->fields['idmesa'] ?>,'Mesa')" style="cursor:pointer"><?php echo $rspedicu->fields['nombre']; ?></td>
    <td align="right" onClick="marca(<?php echo $rspedicu->fields['idmesa'] ?>,'Mesa')" style="cursor:pointer"><?php echo formatomoneda($totalmesa) ?>
    </td>
    <td align="center"><a href="#pop1" onMouseUp="asignardt_mesa(<?php echo $rspedicu->fields['idmesa']?>);"><img src="img/buscar16.png" width="16" height="16" title="Detalle del Pedido" /></a></td>
        <td align="center"><a href="javascript:void(0)" onClick="chau_mesa(<?php echo $rspedicu->fields['idmesa']?>,<?php echo $rspedicu->fields['numero_mesa'] ?>)"><img src="img/error1.png" width="20" height="20"  /></a></td>

</tr>

<?php $rspedicu->MoveNext();
} ?>
</table>
<?php } else {?><br /><br />
<p align="center"><span  style="color:#00A737; font-weight:bold;">No quedan mesas pendientes de cobro.</span></p>

<?php } ?>
