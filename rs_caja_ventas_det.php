 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

$id = intval($_GET['id']);

$consulta = "
select ventas.*
from ventas 
inner join caja_super on caja_super.idcaja = ventas.idcaja
where
ventas.idventa = $id
and ventas.sucursal = $idsucursal
and (select cajero from caja_super where idcaja = ventas.idcaja limit 1) = $idusu
and ventas.estado <> 6
and caja_super.sucursal = $idsucursal
and caja_super.estado_caja = 1
";
//echo  $consulta;
$rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idventa = intval($rsv->fields['idventa']);

if ($idventa == 0) {
    echo "La venta que intenta ver no existe o pertenece a una caja cerrada.";
    exit;
}
if ($idventa > 0) {
    $buscar = "
        SELECT * FROM 
        tmp_ventares_cab
        where 
        idventa = $idventa
        ";
    $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<script src="js/sweetalert.min.js"></script>
 <link rel="stylesheet" type="text/css" href="css/sweetalert.css">
 <script src="js/jquery-1.9.1.js"></script>
 <!--  Esto modificar para que el logo de cabecera no se deforme -->
<!--  <link rel="stylesheet" href="css/main.css"> -->
<!-- <link rel="stylesheet" href="css/docs.css"> -->
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF" onLoad="<?php if (intval($imprimir) == 1) {?> imprimir();<?php } ?><?php if ($cerrado == 1) {?>cerrado();<?php }?>">
<?php require("includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">

    
  <div class="colcompleto" id="contenedor" style="min-height:1100px;">
    <div align="center">
     <a href="gest_administrar_caja.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar" /></a>

    </div>
<hr />

<table width="900" border="1">
  <thead>
    <tr>
      <th align="center" bgcolor="#F8FFCC"><strong>Fecha</strong></th>
      <th align="center" bgcolor="#F8FFCC">Total Venta</th>
      <th align="center" bgcolor="#F8FFCC"><strong>+ Delivery</strong></th>
      <th align="center" bgcolor="#F8FFCC"><strong>- Descuentos</strong></th>
      <th align="center" bgcolor="#F8FFCC"><strong>= Total Cobrado</strong></th>
      </tr>
  </thead>
  <tbody>
<?php while (!$rsv->EOF) {
    $tventaacum += $rsv->fields['total_venta'];
    $tdeliveryacum += $rsv->fields['otrosgs'];
    $tdescuentoacum += $rsv->fields['descneto'];
    $tcobradoacum += $rsv->fields['total_cobrado'];

    ?>
    <tr>
      <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsv->fields['fecha'])); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['total_venta']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['otrosgs']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['descneto']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['total_cobrado']); ?></td>
      </tr>
<?php $rsv->MoveNext();
} ?>
  </tbody>
</table>
<p>&nbsp;</p>
<p align="center"><strong>Pedidos:</strong></p>
<p align="center">&nbsp;</p>
<table width="900" border="1">
  <tbody>
    <tr>
      <td bgcolor="#F8FFCC"><strong>Operador</strong></td>
      <td bgcolor="#F8FFCC"><strong>Chapa</strong></td>
      <td bgcolor="#F8FFCC"><strong>Observacion</strong></td>
      <td bgcolor="#F8FFCC"><strong>Salon</strong></td>
      <td bgcolor="#F8FFCC"><strong>Mesa</strong></td>
      </tr>
<?php
$delivery = $rscab->fields['delivery'];
while (!$rscab->EOF) {
    $operador = intval($rscab->fields['idusu']);
    $consulta = "
        select usuario from usuarios where idusu = $operador
        ";
    $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $operador = $rsop->fields['usuario'];
    $idmesa = intval($rscab->fields['idmesa']);
    if ($idmesa > 0) {
        $consulta = "
            SELECT mesas.idmesa, mesas.numero_mesa, salon.nombre
            FROM mesas
            inner join salon on mesas.idsalon = salon.idsalon
            WHERE
            mesas.idmesa = $idmesa
            and salon.idsucursal = $idsucursal
            order by salon.nombre asc, mesas.numero_mesa asc
            ";
        $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    ?>
    <tr>
      <td><?php echo $operador?></td>
      <td><?php echo $rscab->fields['chapa']; ?></td>
      <td><?php echo $rscab->fields['observacion']; ?></td>
      <td><?php echo $rsmesa->fields['nombre']; ?></td>
      <td><?php echo $rsmesa->fields['numero_mesa']; ?></td>
      </tr>
<?php $rscab->MoveNext();
} ?>
  </tbody>
</table>
<p align="center">&nbsp;</p>
<?php if ($delivery == 'S') {
    $buscar = "
        SELECT * FROM 
        tmp_ventares_cab
        where 
        idventa = $idventa
        ";
    $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    ?>
<h1 align="center">Delivery</h1>
<table width="350" border="1" class="tablaconborde">
    <tbody>
      <tr>
        <td><strong>Telefono:</strong></td>
        <td>0<?php echo $rscab->fields['telefono']; ?></td>
      </tr>
      <tr>
        <td><strong>Llevar POS</strong></td>
        <td><?php echo siono($rscab->fields['llevapos']); ?></td>
      </tr>
      <tr>
        <td><strong>Razon Social</strong></td>
        <td><?php echo $rscab->fields['razon_social']; ?></td>
      </tr>
      <tr>
        <td><strong>Ruc:</strong></td>
        <td><?php echo $rscab->fields['ruc']; ?></td>
      </tr>
      <tr>
        <td><strong>Direccion:</strong></td>
        <td><textarea name="textarea2" cols="30" rows="3" id="textarea4"><?php echo $rscab->fields['direccion']; ?></textarea></td>
      </tr>
      <tr>
        <td><strong>Observacion Delivery:</strong></td>
        <td><textarea name="textarea" cols="30" rows="3" id="textarea3"><?php echo $rscab->fields['observacion_delivery']; ?></textarea></td>
      </tr>
      <tr>
        <td><strong>Operador:</strong></td>
        <td><?php echo $operador; ?></td>
      </tr>
      <tr>
        <td><strong>Observacion Operador:</strong></td>
        <td><textarea name="textarea" cols="30" rows="3" id="textarea3"><?php echo $rscab->fields['observacion']; ?></textarea></td>
      </tr>
    </tbody>
  </table>
  <br />
  <table width="350" border="1" class="tablaconborde">
    <tbody>
      <tr>
        <td><strong>Total Compra:</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['monto']); ?></td>
      </tr>
      <tr>
        <td><strong>Costo Delivery:</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['delivery_costo']); ?></td>
      </tr>
      <tr>
        <td><strong>Total con Delivery:</strong></td>
        <td align="right"><?php echo formatomoneda(intval($rscab->fields['monto']) + intval($rscab->fields['delivery_costo'])); ?></td>
      </tr>
    </tbody>
  </table>
  <br />
  <table width="350" border="1" class="tablaconborde">
    <tbody>
      <tr>
        <td><strong>Paga con (Cambio):</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['cambio']); ?></td>
      </tr>
      <tr>
        <td><strong>Vuelto:</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['cambio'] - ($rscab->fields['monto'] + $rscab->fields['delivery_costo'])); ?></td>
      </tr>
    </tbody>
  </table>
  <?php } ?>
<p align="center">&nbsp;</p>
                
              </div><!-- /CONTAINER -->
            </div><!-- /MAIN -->
     </div>
    
      <div class="clear"></div><!-- clear1 -->
     </div> <!-- contenedor -->
     <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>
