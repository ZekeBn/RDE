<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "16";
$submodulo = "76";
require_once("includes/rsusuario.php");

$idventa = intval($_GET['v']);

// venta
$consulta = "
select * 
from ventas 
where
idventa = $idventa
and idempresa = $idempresa
";
$rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rsv->fields['idventa']) == 0) {
    header("location: adherentes_estadocuenta.php");
    exit;
}

$consulta = "
select sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal, productos.descripcion as producto, productos.idprod_serial
from ventas_detalles 
inner join productos on productos.idprod_serial = ventas_detalles.idprod 
where
idventa = $idventa
and idempresa = $idempresa
group by productos.idprod_serial
order by productos.descripcion
";
$rsvd = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
      <br /><br />

      <div class="divstd">
    		<span class="resaltaditomenor">
    			Detalle de la Venta<br />
    		</span>
 		</div>
<br /><hr />
<p><br /> 
</p>
<table width="900" border="1">
  <thead>
    <tr>
      <th align="center" bgcolor="#F8FFCC"><strong>Fecha</strong></th>
      <th align="center" bgcolor="#F8FFCC"><strong>Cod</strong></th>
      <th align="center" bgcolor="#F8FFCC"><strong>Total Venta</strong></th>
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
      <td align="center"><?php echo $rsv->fields['idventa']; ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['total_venta']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['otrosgs']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['descneto']); ?></td>
      <td align="right"><?php echo formatomoneda($rsv->fields['total_cobrado']); ?></td>
      </tr>
<?php $rsv->MoveNext();
} ?>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Totales:</strong></td>
      <td align="right" bgcolor="#F8FFCC">&nbsp;</td>
      <td align="right" bgcolor="#F8FFCC"><strong><?php echo formatomoneda($tventaacum); ?></strong></td>
      <td align="right" bgcolor="#F8FFCC"><strong><?php echo formatomoneda($tdeliveryacum); ?></strong></td>
      <td align="right" bgcolor="#F8FFCC"><strong><?php echo formatomoneda($tdescuentoacum); ?></strong></td>
      <td align="right" bgcolor="#F8FFCC"><strong style="color:#1A8400;"><?php echo formatomoneda($tcobradoacum); ?></strong></td>
      </tr>
  </tbody>
</table>
      <p>&nbsp;</p>
      <p>&nbsp;</p>
      <p><br />     
        
        
       <p align="center"> Detalle:</p>
      <p align="center">&nbsp;</p>
       <table width="900" border="1">
         <tbody>
           <tr>
             <td align="center" bgcolor="#F8FFCC"><strong>Producto</strong></td>
             <td align="center" bgcolor="#F8FFCC"><strong>Cantidad</strong></td>
             <td align="center" bgcolor="#F8FFCC"><strong>Monto</strong></td>
           </tr>
<?php while (!$rsvd->EOF) {


    ?>
           <tr>
             <td align="center"><?php echo $rsvd->fields['producto']; ?></td>
             <td align="center"><?php echo formatomoneda($rsvd->fields['cantidad'], 4, 'N'); ?></td>
             <td align="center"><?php echo formatomoneda($rsvd->fields['subtotal']); ?></td>
           </tr>
<?php $rsvd->MoveNext();
} ?>
         </tbody>
      </table>
       <p align="center">&nbsp;</p>
       <p align="center">&nbsp;</p>
       <br />      
       <br />
      </p>


          </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>