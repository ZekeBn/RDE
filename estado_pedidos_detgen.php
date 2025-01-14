<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "461";
require_once("includes/rsusuario.php");

require_once("includes/funciones_carrito.php");


$idtmpventares_cab = intval($_GET['id']);


$parametros_array = ["estado_pedido" => 'R', "idpedido" => $idtmpventares_cab];//idatc
$carrito_detalles = carrito_muestra_mesa($parametros_array);

$consulta = "
select *,
	(
	select ventas.idmotorista 
	from ventas
	where 
	ventas.idventa = tmp_ventares_cab.idventa
	) as idmotorista,
	(
	select motorista 
	from ventas
	inner join motoristas on motoristas.idmotorista = ventas.idmotorista 
	where 
	ventas.idventa = tmp_ventares_cab.idventa
	) as motorista,
	(
	select color 
	from delivery_estado
	where 
	delivery_estado.idestadodelivery = tmp_ventares_cab.idestadodelivery
	) as color,
	(
	select estado_delivery 
	from delivery_estado
	where 
	delivery_estado.idestadodelivery = tmp_ventares_cab.idestadodelivery
	) as estado_delivery,
	(
	select nombre
	from sucursales
	where
	idsucu = tmp_ventares_cab.idsucursal
	) as sucursal,
    (
    Select cliente_delivery_dom.referencia
    from cliente_delivery_dom
    where  
    cliente_delivery_dom.iddomicilio=tmp_ventares_cab.iddomicilio
    limit 1	
    ) as referencia
from tmp_ventares_cab
where
estado <> 6
and idtmpventares_cab = $idtmpventares_cab
limit 1
";
//echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idventa = $rs->fields['idventa'];

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
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p><a href="estado_pedidos_general.php?estado=<?php echo intval($_GET['estado']); ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

            <th align="center">Pedido #</th>
			<th align="center">Venta #</th>
            <th align="center">Fecha/Hora</th>
            <th align="center">Sucursal</th>
			<th align="center">Nombre y Apellido</th>
            <th align="center">Razon Social</th>
            <th align="center">RUC</th>
            <th align="center">Direccion</th>
            <th align="center">Telefono</th>
<?php if ($monto_endeliverycaja == 'S') { ?>
            <th align="center">Monto</th>
<?php } ?>
            <th align="center">Motorista</th>
            <th align="center">Estado</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idtmpventares_cab']); ?></td>
            <td align="center"><?php if (intval($rs->fields['idventa']) > 0) {
                echo intval($rs->fields['idventa']);
            } else { ?><span class="fa fa-clock-o"></span><?php } ?></td>
			<td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora'])); ?></td>
            <td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombre_deliv']." ".$rs->fields['apellido_deliv']); ?><?php if (trim($rs->fields['chapa']) != '') {
			    echo " | ".antixss($rs->fields['chapa']);
			} ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
            <td align="center">0<?php echo antixss($rs->fields['telefono']); ?></td>
<?php if ($monto_endeliverycaja == 'S') { ?>
            <td align="center"><?php echo formatomoneda($rs->fields['monto']); ?></td>
<?php } ?>
            <td align="center"><?php echo antixss($rs->fields['motorista']); ?></td>
            <td align="center" style="font-weight:bold;color:#FFF; background-color:<?php echo antixss($rs->fields['color']); ?>;"><?php echo antixss($rs->fields['estado_delivery']); ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<?php //if(intval($idventa) == 0){?>
<div class="clearfix"></div>
<Hr />
/////////////////////////////////////////////

<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action" border="1">
    <thead>
        <tr>
            <th  >Producto</th>
            <th  >Cantidad</th>
            <th  >P.U.</th>
            <th  >Subtotal</th>


        </tr>
    </thead>
    <tbody>
<?php foreach ($carrito_detalles as $carrito_detalle) {

    if ($carrito_detalle['idtipoproducto'] != 5) {
        ?>
        <tr>
            <td align="left"><?php echo  $carrito_detalle['descripcion'];
        if (trim($carrito_detalle['observacion']) != '') {
            echo  "<br />&nbsp;&nbsp;( ! ) OBS: ".$carrito_detalle['observacion'];
        }
        //print_r($carrito_detalle['agregados']);


        // combinado extendido
        $combinados = $carrito_detalle['combinado'];
        $ic = 1;
        foreach ($combinados as $combinado) {
            echo "<br />&nbsp;&nbsp;> Parte $ic: ".Capitalizar($combinado['descripcion']);
            $ic++;
        }
        // combo
        $combos = $carrito_detalle['combo'];
        $ic = 1;
        foreach ($combos as $combo) {
            echo "<br />&nbsp;&nbsp;> ".formatomoneda($combo['cantidad'])." x ".Capitalizar($combo['descripcion']);
            $ic++;
        }
        // combinado viejo
        $combinado_vs = $carrito_detalle['combinado_v'];
        $ic = 1;
        foreach ($combinado_vs as $combinado_v) {
            echo "<br />&nbsp;&nbsp;> Parte $ic: ".Capitalizar($combinado_v['descripcion']);
            $ic++;
        }



        // agregados
        $carrito_agregados = $carrito_detalle['agregados'];
        $iag = 1;
        foreach ($carrito_agregados as $carrito_agregado) {
            echo "<br />&nbsp;&nbsp;&nbsp;(+) ".formatomoneda($carrito_agregado['cantidad']).' x '.trim($carrito_agregado['alias'], 36)."<br />";
            echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Gs. ".formatomoneda($carrito_agregado['precio_adicional'])."";
            $iag++;
        }
        // sacados
        $carrito_sacados = $carrito_detalle['sacados'];
        $iag = 1;
        foreach ($carrito_sacados as $carrito_sacado) {
            echo "<br />&nbsp;&nbsp;&nbsp;(-) SIN ".trim($carrito_sacado['alias'], 36)."";
            $iag++;
        }



        $totalacum += $carrito_detalle['subtotal_con_extras'];

        $estilo_entrada = "";
        $estilo_fondo = "";
        if ($carrito_detalle['tipo_plato'] == 'E') {
            $estilo_entrada = ' style="background-color:#82E9FF;" ';
        }
        if ($carrito_detalle['tipo_plato'] == 'F') {
            $estilo_fondo = ' style="background-color:#82E9FF;" ';
        }


        if ($carrito_detalle['idventatmp'] > 0) {
            $tipo_borra = 'onClick="borrar_item('.$carrito_detalle['idventatmp'].','.$carrito_detalle['idproducto'].',\''.Capitalizar(str_replace("'", "", $carrito_detalle['descripcion'])).'\');"';
            $tipo_personaliza = 'editareceta.php?idvt='.$carrito_detalle['idventatmp'];
            $accion_entrada = 'onclick="marcarplato_item('.$carrito_detalle['idventatmp'].',\'E\');"';
            $accion_fondo = 'onclick="marcarplato_item('.$carrito_detalle['idventatmp'].',\'F\');"';
        } else {
            $tipo_borra = 'onClick="borrar('.$carrito_detalle['idproducto'].',\''.Capitalizar(str_replace("'", "", $des)).'\');"';
            $tipo_personaliza = 'editareceta.php?id='.$carrito_detalle['idproducto'];
            $accion_entrada = 'onclick="marcarplato('.$carrito_detalle['idproducto'].',\'E\');"';
            $accion_fondo = 'onclick="marcarplato('.$carrito_detalle['idproducto'].',\'F\');"';
        }


        ?></td>
            <td align="center"><?php   if ($carrito_detalle['idmedida'] != 4 && $carrito_detalle['idtipoproducto'] == 1) {?><a href="cantidad_cambia.php?id=<?php echo $carrito_detalle['idproducto']; ?>" title="Editar Cantidad"><?php } ?><?php echo  formatomoneda($carrito_detalle['cantidad'], 4, 'N'); ?><?php  if ($carrito_detalle['idmedida'] != 4 && $carrito_detalle['idtipoproducto'] == 1) {?></a><?php } ?></td>
            <td align="right"><?php echo  formatomoneda($carrito_detalle['precio_unitario_con_extras'], 2, 'N'); ?></td>
            <td align="right"><?php echo  formatomoneda($carrito_detalle['subtotal_con_extras'], 2, 'N'); ?></td>


        </tr>
<?php } // if($carrito_detalle['idtipoproducto'] != 5){?>
<?php } ?>
    <tr>
      <td height="50" colspan="5"><strong>Total: <?php echo formatomoneda($totalacum, 0); ?></strong></td>
    </tr>
    </tbody>
</table>
</div>
//////////////////
<br />
<?php //}?>
<?php
/*if(intval($idventa) > 0){

$consulta="
select *,
(select barcode from productos where idprod_serial = ventas_detalles.idprod) as barcode,
    (
    select idinsumo
    from productos
    inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
    where
    idprod_serial = ventas_detalles.idprod
    ) as idinsumo,
(select descripcion from productos where idprod_serial = ventas_detalles.idprod) as producto,
(select idtipoproducto from productos where idprod_serial = ventas_detalles.idprod) as idtipoproducto,
    idventatmp
from ventas_detalles
where
 idventa = $idventa
order by pventa asc
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));



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
<?php

$combinado="";
while(!$rs->EOF){
        //echo $rs->fields['idtipoproducto'] ;
        $combinado="";
        $idventatmp=intval($rs->fields['idventatmp']);
        if($rs->fields['idtipoproducto'] == 4){
            if($idventatmp > 0){
                $consulta="
                SELECT productos.descripcion
                FROM tmp_combinado_listas
                inner join tmp_ventares on tmp_ventares.idventatmp = tmp_combinado_listas.idventatmp
                inner join tmp_ventares_cab on tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab
                inner join productos on productos.idprod_serial = tmp_combinado_listas.idproducto_partes
                where
                tmp_ventares.idventatmp = $idventatmp
                ";
                if($idtmpventares_cab == 21465){
                //echo $consulta;
                }
                $rscomb=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
                $i=1;
                while(!$rscomb->EOF){
                    if($i == 1){
                        $combinado.='<br />';
                    }
                    $combinado.='&nbsp;&nbsp;» Parte '.$i.': '.$rscomb->fields['descripcion']."<br />";
                    $i++;
                $rscomb->MoveNext(); }
            }
        }

?>
        <tr>
            <td align="center"><?php echo antixss($rs->fields['idinsumo']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['barcode']); ?></td>
            <td align="left"><?php echo antixss($rs->fields['producto']); echo $combinado; ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['cantidad'],4,'N');  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['pventa']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['subtotal']);  ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['iva']);  ?>%</td>
        </tr>
<?php $rs->MoveNext(); } //$rs->MoveFirst(); ?>
      </tbody>
    </table>
</div>
<br />
<?php }*/ ?>
<?php
$consulta = "
select *
from tmp_ventares_cab
where
idtmpventares_cab = $idtmpventares_cab
limit 1
";
$rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtmpventares_cab = $rscab->fields['idtmpventares_cab'];

// datos cabecera
$direccion = $rscab->fields['direccion'];
$referencia = $rscab->fields['referencia'];
$telefono = $rscab->fields['telefono'];
$nombre_deliv = texto_tk($rscab->fields['nombre_deliv'], 26);
$apellido_deliv = texto_tk($rscab->fields['apellido_deliv'], 26);
$llevapos = siono($rscab->fields['llevapos']);
$cambio = formatomoneda($rscab->fields['cambio']);
$observacion_delivery = $rscab->fields['observacion_delivery'];
$observacion = $rscab->fields['observacion'];
$delivery_costo = formatomoneda($rscab->fields['delivery_costo']);
$monto = formatomoneda($rscab->fields['monto']);
$totalpagar = formatomoneda(intval($rscab->fields['monto']) + intval($rscab->fields['delivery_costo']) - intval($descuento));
$vuelto = $rscab->fields['cambio'] - ($rscab->fields['monto'] + $rscab->fields['delivery_costo'] - intval($descuento));
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
			<td align="center"><?php echo $totalpagar; ?></td>
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
			<td align="center">REFERENCIA</th>
			<td align="center"><?php echo $referencia; ?></td>
		</tr>
		<tr>
			<td align="center">OBS. OPER.</th>
			<td align="center"><?php echo $observacion; ?></td>
		</tr>
		<tr>
			<td align="center">OBS. DEL.</th>
			<td align="center"><?php echo $observacion_delivery; ?></td>
		</tr>


</table>
 </div>
<br />





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
