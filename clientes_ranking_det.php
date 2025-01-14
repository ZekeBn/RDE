<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "11";
$submodulo = "290";
require_once("includes/rsusuario.php");


if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
/*if(intval($_GET['idsucu']) > 0){
    $idsucu=intval($_GET['idsucu']);
    $whereadd.=" and ventas.sucursal = $idsucu ";
    $consulta="
    select idsucu, nombre from sucursales where idsucu = $idsucu
    ";
    $rssucven=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $sucursal_ven=$rssucven->fields['nombre'];
}else{
    $sucursal_ven="TODAS";
}*/


$idcliente = intval($_GET['idcliente']);

$consulta = "
select cliente.razon_social, ventas.idcliente, sum(totalcobrar) as total_venta, count(*) as cantidad_venta
from ventas 
inner join cliente on cliente.idcliente = ventas.idcliente
where 
 ventas.estado <> 6 
 $whereadd
 and date(ventas.fecha) >= '$desde'
 and date(ventas.fecha) <= '$hasta'
 and cliente.idcliente = $idcliente
 group by cliente.razon_social, ventas.idcliente
order by sum(totalcobrar)  desc
limit 1000
";
//echo $consulta;
//exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// debe ser igual al de arriba
$consulta = "
select sum(totalcobrar) as total_venta, count(*) as total_cantidad
from ventas 
inner join cliente on cliente.idcliente = ventas.idcliente
where 
 ventas.estado <> 6 
 $whereadd
 and date(ventas.fecha) >= '$desde'
 and date(ventas.fecha) <= '$hasta'
 and cliente.idcliente = $idcliente
";
$rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$total_venta = $rstot->fields['total_venta'];




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
                    <h2>Cliente <?php echo antixss($rs->fields['razon_social']); ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php //require_once("includes/menu_venta_cli.php");?>

<p><a href="clientes_ranking.php?desde=<?php echo antixss($desde); ?>&hasta=<?php echo antixss($hasta); ?>&idsucu=<?php echo intval($_GET['idsucu']); ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Cliente</th>
			<th align="center">Ticket Promedio</th>
            <th align="center">Cantidad Ventas</th>
			<th align="center">Monto Ventas</th>
		</tr>
	  </thead>
	  <tbody>
<?php //while(!$rs->EOF){?>
		<tr>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['total_venta'] / $rs->fields['cantidad_venta']);  ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['cantidad_venta']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['total_venta']);  ?></td>
		</tr>
<?php //$rs->MoveNext(); } //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<hr />
<?php
$consulta = "
select productos.descripcion as producto, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal
from ventas 
inner join ventas_detalles on ventas.idventa = ventas_detalles.idventa
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where
date(ventas.fecha) >= '$desde' 
and date(ventas.fecha) <= '$hasta' 
and ventas.estado <> 6
and ventas.excluye_repven = 0
and ventas.idcliente = $idcliente
$whereadd
group by productos.descripcion
order by sum(ventas_detalles.subtotal) desc
limit 50
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>

<strong>Ventas Entre:</strong>  <?php echo date("d/m/Y", strtotime($desde)); ?> y <?php echo date("d/m/Y", strtotime($hasta)); ?><Br />
<?php /*?><strong>Sucursal: </strong><?php echo $sucursal_ven; ?><Br /><Br /><?php */?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Producto</th>
			<th align="center">Cantidad</th>
			<th align="center">Total</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="left"><?php echo antixss($rs->fields['producto']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['cantidad'], '4', 'N'); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['subtotal'], '4', 'N'); ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<hr />
<strong>Ultimas 10 Ventas:</strong>
<?php

$consulta = "
select *,
(select sucursales.nombre from sucursales where sucursales.idsucu = ventas.sucursal) as sucursal,
CASE WHEN tipo_venta = 1 then 'CONTADO' ELSE 'CREDITO' END as condicion,
(select canal from canal where canal.idcanal = ventas.idcanal) as canal
from ventas 
where 
 estado <> 6
 and date(ventas.fecha) >= '$desde'
 and date(ventas.fecha) <= '$hasta'
 and ventas.excluye_repven = 0
and ventas.idcliente = $idcliente
$whereadd
order by fecha desc
limit 10
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idventa</th>
			<th align="center">Fecha</th>
			<th align="center">Factura</th>
			<th align="center">Condicion</th>
			<th align="center">Sucursal</th>
			<th align="center">Total sin Desc</th>
			<th align="center">Descneto</th>
			<th align="center">Total venta</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Canal</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo antixss($rs->fields['idventa']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['condicion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['total_venta']); ?></td>
			<td align="center"><?php echo intval($rs->fields['descneto']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['totalcobrar']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo intval($rs->fields['ruchacienda']).'-'.intval($rs->fields['dv']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['canal']); ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<hr />
<strong>Datos de Contacto: </strong>
<?php
$consulta = "
Select telefono, celular, direccion, fechanac  
from cliente
where 
idcliente = $idcliente
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Telefono</th>
			<th align="center">Celular</th>
			<th align="center">Direccion</th>
			<th align="center">Fecha Nacimiento</th>
		</tr>
	  </thead>
	  <tbody>
		<tr>

			<td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>

			<td align="center"><?php echo antixss($rs->fields['celular']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
			<td align="center"><?php if ($rs->fields['fechanac'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fechanac']));
			}  ?></td>            

		</tr>
	  </tbody>
    </table>
</div>
<?php
$consulta = "
select cliente_delivery.*, cliente.ruc, cliente.razon_social
from cliente_delivery
inner join cliente on cliente.idcliente = cliente_delivery.idcliente
where
cliente_delivery.idclientedel is not null
and cliente.idcliente = $idcliente
and cliente_delivery.estado <> 6
order by cliente_delivery.nomape asc
limit 50
";
$rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<strong>Clientes Delivery Asociados:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead> 
                <tr>

                  <th>Direcciones</strong></th>
                  <th>Cliente</th>

                </tr>
               </thead>  
               <tbody> 
<?php

$totcli = 0;
while (!$rscab_old->EOF) {
    $idclientedel = $rscab_old->fields['idclientedel'];

    ?>
                <tr>

                  <td align="left">
<?php
$consulta = "
select *
,(select describezona from zonas_delivery where idzonadel=cliente_delivery_dom.idzonadel) as describezona
,(select obs from zonas_delivery where idzonadel=cliente_delivery_dom.idzonadel) as obs
 from cliente_delivery_dom 
where
idclientedel = $idclientedel
and estado = 1
";
    $rsdirec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    if ($rsdirec->fields['idclientedel'] > 0) {
        ?>
<table class="table table-bordered jambo_table bulk_action">
<?php while (!$rsdirec->EOF) {?>
    <tr>
    	<td style="width:80%;"><strong>Lugar:</strong> <?php echo $rsdirec->fields['nombre_domicilio']; ?>
        <br /><strong>DIR:</strong><?php echo $rsdirec->fields['direccion']; ?>
        <br /><strong>REF:</strong><?php echo $rsdirec->fields['referencia']; ?><br />
        <?php if ($usarzonadelivery == 'S') {?>
        <strong>Zona:</strong> <?php echo $rsdirec->fields['describezona'].' | '.$rsdirec->fields['obs']; ?><br />
        <?php }?>
        </td>
    </tr>
<?php $rsdirec->MoveNext();
} ?>
</table>
<?php } else { ?>
SIN DOMICILIO REGISTRADO
<?php }?>
                  </td>
                  <td align="left">
                  <strong>Cliente:</strong><?php echo $rscab_old->fields['nombres']; ?> <?php echo $rscab_old->fields['apellidos']; ?><br />
                  <strong>Tel:</strong> 0<?php echo $rscab_old->fields['telefono']; ?><br />
                  <strong>RUC:</strong> <?php echo $rscab_old->fields['ruc']; ?><br />
                  <strong>Razon Social:</strong> <?php echo $rscab_old->fields['razon_social']; ?><br />
                  
                  </td>

<?php
$totcli++;
    $rscab_old->MoveNext();
} ?>
              </tbody>
            </table>
</div>

<br /><br /><br /><br /><br />
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
