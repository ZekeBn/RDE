<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "212";
require_once("includes/rsusuario.php");

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}


$idventa = intval($_GET['id']);



$consulta = "
select *, 
(select nombres from cliente_delivery where idclientedel = ventas.idclientedel) as nombres,
(select apellidos from cliente_delivery where idclientedel = ventas.idclientedel) as apellidos,
(select telefono from cliente_delivery where idclientedel = ventas.idclientedel) as telefono,
(select direccion from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as direccion
from ventas 
where 
idcanal = 3 
and idcaja = $idcaja
and idventa = $idventa
order by fecha desc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalpagar = $rs->fields['totalcobrar'];

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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Deliverys Enviados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p><a href="delivery_micaja.php?estado=<?php echo intval($_GET['estado']); ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Venta #</th>
            <th align="center">Fecha/Hora</th>
			<th align="center">Nombre y Apellido</th>
            <th align="center">Razon Social</th>
            <th align="center">RUC</th>
            <th align="center">Direccion</th>
            <th align="center">Telefono</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
			<td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha'])); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombres']." ".$rs->fields['apellidos']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php
$consulta = "
select *
from tmp_ventares_cab
where
idventa = $idventa
limit 1
";
$rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtmpventares_cab = $rscab->fields['idtmpventares_cab'];

// datos cabecera
$direccion = $rscab->fields['direccion'];
$telefono = $rscab->fields['telefono'];
$nombre_deliv = texto_tk($rscab->fields['nombre_deliv'], 26);
$apellido_deliv = texto_tk($rscab->fields['apellido_deliv'], 26);
$llevapos = siono($rscab->fields['llevapos']);
$cambio = formatomoneda($rscab->fields['cambio']);
$observacion_delivery = $rscab->fields['observacion_delivery'];
$delivery_costo = formatomoneda($rscab->fields['delivery_costo']);
$monto = formatomoneda($rscab->fields['monto']);
$totalpagar_txt = formatomoneda($totalpagar);
$vuelto = $rscab->fields['cambio'] - ($totalpagar);
if ($vuelto < 0) {
    $vuelto = 0;
}
$vuelto = formatomoneda($vuelto);
?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr <?php if ($llevapos == 'SI') { ?>style="color:#F00; font-weight:bold;"<?php } ?>>
			<td align="center">LLEVAR POS</th>
			<td align="center"><?php echo $llevapos; ?></td>
		</tr>
		<tr>
			<td align="center">TOTAL GLOBAL</th>
			<td align="center"><?php echo $totalpagar_txt; ?></td>
		</tr>
<?php if ($llevapos != 'SI') { ?>
		<tr>
			<td align="center">PAGA CON</th>
			<td align="center"><?php echo $cambio; ?></td>
		</tr>
		<tr>
			<td align="center">VUELTO</th>
			<td align="center"><?php echo $vuelto; ?></td>
		</tr>
<?php } ?>
		<tr>
			<td align="center">DIRECCION</th>
			<td align="center"><?php echo $direccion; ?></td>
		</tr>
		<tr>
			<td align="center">OBS. DEL.</th>
			<td align="center"><?php echo $observacion_delivery; ?></td>
		</tr>


</table>
 </div>
<br />


<?php


$consulta = "
select tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado,
productos.idtipoproducto, tmp_ventares.idprod_mitad2, tmp_ventares.idprod_mitad1
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
tmp_ventares.borrado = 'N'
and tmp_ventares.registrado = 'S'
and idtmpventares_cab = $idtmpventares_cab
group by descripcion, receta_cambiada
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <thead>
    <tr>
      <th height="29" ><strong>Producto</strong></th>
      <th align="center" ><strong>Cant.</strong></th>
      <th align="center"><strong>Total</strong></th>
     
    </tr>
    </thead>
    <tbody>
<?php
    $cc = 0;
while (!$rs->EOF) {
    $cc = $cc + 1;
    $total = $rs->fields['subtotal'];
    $totalacum += $total;
    $des = str_replace("'", "", $rs->fields['descripcion']);

    // 1 producto 2 combo 3 combinado 4 combinado extendido
    $idtipoproducto = $rs->fields['idtipoproducto'];
    $idprod_mitad1 = $rs->fields['idprod_mitad1'];
    $idprod_mitad2 = $rs->fields['idprod_mitad2'];
    $idventatmp = $rs->fields['idventatmp'];
    if ($idtipoproducto == 3) {
        $consulta = "
		select * 
		from productos 
		where 
		(idprod_serial = $idprod_mitad1 or idprod_serial = $idprod_mitad2)
		limit 2
		";
        $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    if ($idtipoproducto == 4) {
        $consulta = "
		select * 
		from productos 
		inner join tmp_combinado_listas on tmp_combinado_listas.idproducto_partes = productos.idprod_serial
		where 
		tmp_combinado_listas.idventatmp = $idventatmp
		limit 20
		";
        $rsmit = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }


    ?>
    <tr id="tr_<?php echo $cc;?>">
      <td height="30"  id="td_<?php echo $cc;?>"><?php echo Capitalizar($rs->fields['descripcion']); ?><?php
    if (($idtipoproducto == 3 or $idtipoproducto == 4) && $rs->fields['total'] == 1) {
        $i = 0;
        while (!$rsmit->EOF) {
            $i++;
            echo "<br />&nbsp;&nbsp;> Parte $i: ".Capitalizar($rsmit->fields['descripcion']);
            $rsmit->MoveNext();
        }

    }


    ?><input type="hidden" name="onp_<?php echo $cc;?>" id="onp_<?php echo $cc;?>"  value="<?php echo $rs->fields['idproducto']; ?>"/></td>
      <td align="center"><?php echo formatomoneda($rs->fields['total'], 3, 'N'); ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['subtotal'], 0, 'N'); ?></td>

    </tr>
<?php $rs->MoveNext();
} ?>
<?php

// buscar si hay agregados y mostrar el total global
$consulta = "
SELECT sum(precio_adicional) as montototalagregados , count(idventatmp) as totalagregados
FROM 
tmp_ventares_agregado
where
idventatmp in (
select tmp_ventares.idventatmp
from tmp_ventares 
where 
registrado = 'S'
and tmp_ventares.borrado = 'N'
and idtmpventares_cab = $idtmpventares_cab
)
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$montototalagregado = $rs->fields['montototalagregados'];
$totalagregado = $rs->fields['totalagregados'];
$totalacum += $montototalagregado;

if ($totalagregado > 0) {
    ?>
    <tr>
      <td height="30">Agregados</td>
      <td align="center"><?php echo formatomoneda($totalagregado, 0); ?></td>
      <td align="center"><?php echo formatomoneda($montototalagregado, 0); ?></td>

    </tr>
<?php } ?>

  </tbody>
</table>
</div>
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
