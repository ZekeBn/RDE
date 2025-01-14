<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "302";
require_once("includes/rsusuario.php");

$idventa = intval($_GET['id']);
if ($idventa == 0) {
    header("location: informe_venta_graf_busq.php");
    exit;
}

$consulta = "
select *,
(select sucursales.nombre from sucursales where sucursales.idsucu = ventas.sucursal) as sucursal,
CASE WHEN tipo_venta = 1 then 'CONTADO' ELSE 'CREDITO' END as condicion,
(select canal from canal where canal.idcanal = ventas.idcanal) as canal,
(select usuario from usuarios where ventas.registrado_por = usuarios.idusu) as registrado_por,
venta_registrada_el as registrado_el,
anulado_el,
(select usuario from usuarios where ventas.anulado_por = usuarios.idusu) as anulado_por
from ventas 
where 
 idventa = $idventa
order by fecha asc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idventa = intval($rs->fields['idventa']);
$tipo_venta = intval($rs->fields['tipo_venta']);
$anulado_por = $rs->fields['anulado_por'];
$anulado_el = date("d/m/Y H:i:s", strtotime($rs->fields['anulado_el']));
$idpedido = intval($rs->fields['idpedido']);
if ($idventa == 0) {
    header("location: informe_venta_graf_busq.php");
    exit;
}

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>

            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Busqueda de Ventas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  
<?php if ($rs->fields['estado'] == 6) { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>ATENCION!</strong><br />Esta venta fue anulada por <?php echo $anulado_por; ?> el <?php echo $anulado_el; ?>.
</div>
<?php } ?>
<?php if ($rs->fields['finalizo_correcto'] != 'S') { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>ATENCION!</strong><br />Esta venta esta pendiente de anulacion, la factura de esta venta no se imprimio.
</div>
<?php } ?>					  
					  
<p><a href="javascript:void(0);" onClick="window.history.back();" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idventa</th>
            <th align="center">Idcaja</th>
			<th align="center">Fecha</th>
			<th align="center">Factura</th>
			<th align="center">Timbrado</th>
			<th align="center">Condicion</th>
			<th align="center">Sucursal</th>
			<th align="center">Total sin Desc</th>
			<th align="center">Descneto</th>
			<th align="center">Total venta</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Canal</th>
			<th align="center">OC</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php echo antixss($rs->fields['idventa']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['idcaja']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['timbrado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['condicion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['total_venta']); ?></td>
			<td align="center"><?php echo intval($rs->fields['descneto']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['totalcobrar']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['canal']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ocnumero']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>


<?php


$consulta = "
select *,
(select barcode from productos where idprod_serial = ventas_detalles.idprod) as barcode,
	(
	select idinsumo 
	from productos 
	inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
	where 
	idprod_serial = ventas_detalles.idprod
	) as idinsumo,
(select descripcion from productos where idprod_serial = ventas_detalles.idprod) as producto

from ventas_detalles 
where 
 idventa = $idventa
order by pventa asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



?>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Codigo</th>
            <th align="center">Codigo Barra</th>
			<th align="center">Producto</th>
			<th align="center">Cantidad</th>
			<th align="center">Precio Unitario</th>
			<th align="center">Subtotal</th>
            <th align="center">IVA %</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {
    $idventadet = $rs->fields['idventadet'];
    $consulta = "
SELECT tipo_iva.iva_describe AS iva_describe, sum(ventas_detalles_impuesto.monto_col) as monto_col
from ventas_detalles_impuesto 
inner join tipo_iva on tipo_iva.idtipoiva = ventas_detalles_impuesto.idtipoiva
where
ventas_detalles_impuesto.idventadet = $idventadet
group by iva_describe
order by tipo_iva.iva_describe asc
";
    $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
		<tr>
			<td align="center"><?php echo antixss($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['barcode']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['producto']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N');  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['pventa']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['subtotal']);  ?></td>
            <td align="right">
				<?php while (!$rsiva->EOF) { ?>
				<?php echo antixss($rsiva->fields['iva_describe']);  ?> <br />
				<?php $rsiva->MoveNext();
				} ?>
			</td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
					  

<strong> Formas de Pago:</strong>
     
<?php
$consulta = "
select *, (select descripcion from formas_pago where idforma = gest_pagos_det.idformapago) as medio_pago
from gest_pagos 
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
where 
 estado = 1 
 and idventa = $idventa
 and gest_pagos.idtipocajamov = 1
order by idcliente asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Medio pago</th>
			<th align="center">Total cobrado</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="left"><?php echo antixss($rs->fields['medio_pago']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_pago_det']);  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />

<?php
$consulta = "
select tmp_ventares_cab_pos.*, tarjetas_bancard.tipo as tipo_tarjeta, tarjetas_bancard.marca
from tmp_ventares_cab_pos 
left join tarjetas_bancard on tarjetas_bancard.issuerId = tmp_ventares_cab_pos.issuerId
where 
tmp_ventares_cab_pos.idtmpventares_cab = $idpedido
order by idtmpventares_cab_pos asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>	
<?php if (intval($rs->fields['idtmpventares_cab_pos']) > 0) {?>
<strong>Reporte POS Bancard:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Metodo</th>
			<th align="center">Codigoautorizacion</th>
			<th align="center">Codigocomercio</th>
			<th align="center">Issuerid</th>
			<th align="center">Tipo Tarjeta</th>
			<th align="center">Marca Tarjeta</th>
			<th align="center">Mensajedisplay</th>
			<th align="center">Montovueltopos</th>
			<th align="center">Nombrecliente</th>
			<th align="center">Nombretarjeta</th>
			<th align="center">Nroboleta</th>
			<th align="center">Pan</th>
			<th align="center">Saldo</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php echo antixss($rs->fields['tipo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codigoAutorizacion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['codigoComercio']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['issuerId']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipo_tarjeta']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['marca']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['mensajeDisplay']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['montoVuelto']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombreCliente']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombreTarjeta']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nroBoleta']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['pan']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['saldo']);  ?></td>
		</tr>
<?php
//$montoVuelto_acum+=$rs->fields['montoVuelto'];
//$saldo_acum+=$rs->fields['saldo'];

$rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>

    </table>
</div>
<br />				  
<?php } ?>
	
					  
					  
<?php if ($tipo_venta == 2) {?>
	<hr />
    
<?php
 $consulta = "
select *,
(select usuario from usuarios where cuentas_clientes.registrado_por = usuarios.idusu) as registrado_por,
(select fecha from ventas where idventa = cuentas_clientes.idventa) as fecha,
(select factura from ventas where idventa = cuentas_clientes.idventa) as factura
from cuentas_clientes 
where 
 estado = 1 
 and cuentas_clientes.idventa = $idventa
order by idcta asc
";
    //echo $consulta;exit;
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?>
<strong>Detalle de la Deuda:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idcta</th>

			<th align="center">Deuda global</th>
            <th align="center">Cobrado</th>
			<th align="center">Saldo activo</th>
			<th align="center">Ultimo pago</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {


    $deuda_global = $rs->fields['deuda_global'];
    $saldo_activo = $rs->fields['saldo_activo'];
    $deuda_global_acum += $deuda_global;
    $saldo_activo_acum += $saldo_activo;

    ?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idcta']); ?></td>

			<td align="right"><?php echo formatomoneda($rs->fields['deuda_global']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['deuda_global'] - $rs->fields['saldo_activo']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['saldo_activo']);  ?></td>

			<td align="center"><?php if ($rs->fields['ultimo_pago'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['ultimo_pago']));
			}  ?></td>

		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
		<tr style="background-color:#CCC; font-weight:bold;">
		  <td>Totales:</td>




		  <td align="right"><?php echo formatomoneda($deuda_global_acum); ?></td>
          <td align="right"><?php echo formatomoneda($deuda_global_acum - $saldo_activo_acum); ?></td>
		  <td align="right"><?php echo formatomoneda($saldo_activo_acum); ?></td>

		  <td align="center">&nbsp;</td>
		  </tr>
	  </tbody>
    </table>
</div>
<?php
$consulta = "
select 
cuentas_clientes_pagos_cab.monto_abonado as monto_recibo,
cuentas_clientes_pagos.monto_abonado as monto_aplicado, 
cuentas_clientes_pagos_cab.fecha_pago,
cuentas_clientes_pagos_cab.recibo
from cuentas_clientes_pagos_cab
inner join cuentas_clientes_pagos on cuentas_clientes_pagos.idcuentaclientepagcab = cuentas_clientes_pagos_cab.idcuentaclientepagcab
inner join cuentas_clientes on cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta
where
cuentas_clientes.idventa = $idventa
and cuentas_clientes_pagos_cab.estado <> 6
and notanum is null
order by cuentas_clientes_pagos_cab.fecha_pago asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>

<strong>Recibos aplicados a la factura:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Recibo</th>
            <th align="center">Fecha</th>
			<th align="center">Monto Recibo</th>
			<th align="center">Monto Aplicado</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {
    $monto_recibo_acum += $rs->fields['monto_recibo'];
    $monto_aplicado_acum += $rs->fields['monto_aplicado'];

    ?>
		<tr>
			<td align="center"><?php echo antixss($rs->fields['recibo']); ?></td>
			<td align="center"><?php echo date("d/m/Y", strtotime($rs->fields['fecha_pago'])); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['monto_recibo'], 4, 'N'); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_aplicado'], 4, 'N');  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
		<tr style="background-color:#CCC; font-weight:bold;">
		  <td>Totales:</td>
			<td align="center"></td>
			<td align="center"><?php echo formatomoneda($monto_recibo_acum, 4, 'N'); ?></td>
			<td align="right"><?php echo formatomoneda($monto_aplicado_acum, 4, 'N');  ?></td>
		  </tr>
	  </tbody>
    </table>
</div>
<br />
<?php
$consulta = "
select 
cuentas_clientes_pagos_cab.monto_abonado as monto_recibo,
cuentas_clientes_pagos.monto_abonado as monto_aplicado, 
cuentas_clientes_pagos_cab.fecha_pago,
cuentas_clientes_pagos_cab.notacredito
from cuentas_clientes_pagos_cab
inner join cuentas_clientes_pagos on cuentas_clientes_pagos.idcuentaclientepagcab = cuentas_clientes_pagos_cab.idcuentaclientepagcab
inner join cuentas_clientes on cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta
where
cuentas_clientes.idventa = $idventa
and cuentas_clientes_pagos_cab.estado <> 6
and notanum is not null
order by cuentas_clientes_pagos_cab.fecha_pago asc
";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    ?>

<strong>Notas de Credito aplicada a la factura a credito:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Nota Credito</th>
            <th align="center">Fecha</th>
			<th align="center">Monto </th>
			<th align="center">Monto Aplicado</th>
		</tr>
	  </thead>
	  <tbody>
<?php
    $monto_recibo_acum = 0;
    $monto_aplicado_acum = 0;
    while (!$rs->EOF) {
        $monto_recibo_acum += $rs->fields['monto_recibo'];
        $monto_aplicado_acum += $rs->fields['monto_aplicado'];

        ?>
		<tr>
			<td align="center"><?php echo antixss($rs->fields['notacredito']); ?></td>
			<td align="center"><?php echo date("d/m/Y", strtotime($rs->fields['fecha_pago'])); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['monto_recibo'], 4, 'N'); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_aplicado'], 4, 'N');  ?></td>
		</tr>
<?php $rs->MoveNext();
    } //$rs->MoveFirst();?>
		<tr style="background-color:#CCC; font-weight:bold;">
		  <td>Totales:</td>
			<td align="center"></td>
			<td align="center"><?php echo formatomoneda($monto_recibo_acum, 4, 'N'); ?></td>
			<td align="right"><?php echo formatomoneda($monto_aplicado_acum, 4, 'N');  ?></td>
		  </tr>
	  </tbody>
    </table>
</div>

<?php } ?>
<?php
$consulta = "
SELECT nota_credito_cabeza.idnotacred, nota_credito_cabeza.numero,  fecha_nota, ventas.idventa, ventas.factura, nota_credito_cabeza.idcliente, nota_credito_cabeza.razon_social, nota_credito_cabeza.ruc, nota_credito_cuerpo.subtotal as monto_aplicado
FROM nota_credito_cabeza 
inner join `nota_credito_cuerpo` on nota_credito_cuerpo.idnotacred = nota_credito_cabeza.idnotacred
inner join ventas on ventas.idventa = nota_credito_cuerpo.idventa
WHERE
nota_credito_cabeza.estado <> 6
and ventas.idventa = $idventa
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rs->fields['idnotacred']) > 0) {
    ?>

<strong>Notas de Credito aplicada a la factura contado:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Nota Credito</th>
            <th align="center">Fecha</th>
			<th align="center">Monto Aplicado</th>
		</tr>
	  </thead>
	  <tbody>
<?php
    $monto_recibo_acum = 0;
    $monto_aplicado_acum = 0;
    while (!$rs->EOF) {
        //$monto_recibo_acum+=$rs->fields['monto_recibo'];
        $monto_aplicado_acum += $rs->fields['monto_aplicado'];

        ?>
		<tr>
			<td align="center"><?php echo antixss($rs->fields['numero']); ?> [<?php echo antixss($rs->fields['idnotacred']); ?>]</td>
			<td align="center"><?php echo date("d/m/Y", strtotime($rs->fields['fecha_nota'])); ?></td>

			<td align="right"><?php echo formatomoneda($rs->fields['monto_aplicado'], 4, 'N');  ?></td>
		</tr>
<?php $rs->MoveNext();
    } //$rs->MoveFirst();?>
		<tr style="background-color:#CCC; font-weight:bold;">
		  <td>Totales:</td>
			<td align="center"></td>

			<td align="right"><?php echo formatomoneda($monto_aplicado_acum, 4, 'N');  ?></td>
		  </tr>
	  </tbody>
    </table>
</div>
<?php } ?>

<br /><br /><br />
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
           
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
