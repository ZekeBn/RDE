<?php 
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo="1";
$submodulo="232";
$dirsup = 'S';
require_once("../includes/rsusuario.php"); 


$factura=antisqlinyeccion($_POST['factura'],"text");
$idventa=antisqlinyeccion($_POST['idventa'],"int");
$clase=intval($_POST['clase']); // 1 articulo 2 monto global
// echo $clase;exit;
// 2 monto global
//3 por articulo
$idnotacred=intval($_POST['idnotacred']);
$idorden_retiro = intval($_POST['idorden_retiro']);
$pventa="";
// ///////////////////////////////////////////////
// if($idorden_retiro != 0) {
//     $consulta="
//     SELECT 
// 	devolucion_det.*, 
// 	devolucion.registrado_el,
// 	ventas_detalles.pventa, 
// 	(
// 		select 
// 		medidas.nombre 
// 		from 
// 		medidas 
// 		where 
// 		medidas.id_medida = devolucion_det.idmedida
// 	) as medida, 
// 	(
// 		select 
// 		insumos_lista.descripcion 
// 		from 
// 		insumos_lista 
// 		where 
// 		insumos_lista.idproducto = devolucion_det.idproducto
// 	) as insumo, 
// 	(
// 		select 
// 		gest_depositos.descripcion 
// 		from 
// 		gest_depositos 
// 		WHERE 
// 		gest_depositos.iddeposito = devolucion_det.iddeposito
// 	) as deposito 
// 	FROM 
// 		devolucion_det 
// 		INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
// 		INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion_det.iddevolucion
// 		INNER JOIN ventas_detalles on ventas_detalles.idprod = devolucion_det.idproducto 
// 	WHERE 
// 		retiros_ordenes.idorden_retiro = $idorden_retiro
      
//     ";
    
//     $rs_devolucion=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
// 	$pventa = $rs_devolucion->fields['pventa']*$rs_devolucion->fields['cantidad'];
    
// }

// ///////////////////////////////////////////////////////////////////////////////////////

$consulta="
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
where 
 nota_credito_cabeza.estado = 1 
 and nota_credito_cabeza.idnotacred = $idnotacred
limit 1
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$idnotacred=intval($rs->fields['idnotacred']);
$motivo=antixss($rs->fields['motivo']);
if($idnotacred == 0){
	echo "- Nota de credito inexistente.<br />";
	exit;
}
if(trim($_POST['factura']) != ''){
	$whereadd=" and factura = $factura ";	
}
if(intval($_POST['idventa']) > 0){
	$whereadd=" and idventa = $idventa ";	
}
if(intval($_POST['idventa']) == 0  && trim($_POST['factura']) == ''){
	echo "- No se indico la factura ni el idventa, debe especificar al menos 1 de los 2.<br />";
	exit;
}
if(intval($_POST['clase']) == 0){
	echo "- No especifico la forma de aplicar.<br />";
	exit;	
}
//print_r($_POST);

$consulta="
select * ,
(select saldo_activo from cuentas_clientes where idventa = ventas.idventa) as saldo_factura,
(select sucursales.nombre from sucursales where sucursales.idsucu = ventas.sucursal) as sucursal
from ventas 
where 
estado <> 6
$whereadd
";
// echo $consulta;
$rs=$conexion->Execute($consulta) or die (errorpg($conexion,$consulta));
$recordCount = $rs->recordCount();
	
if(intval($rs->fields['idventa']) == 0){
	echo "- Factura/Venta no encontrada.<br />";
	exit;
}



if($rs->fields['idventa'] > 0){

	if(intval($recordCount) > 1){
	

	
?>
<hr />
<strong>Facturas Encontradas:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center"></th>
			<th align="center">Idventa</th>
            <th align="center">Condicion</th>
			<th align="center">Factura</th>
			<th align="center">Fecha</th>
			<th align="center">RUC</th>
			<th align="center">Razon Social</th>
			<th align="center">Sucursal</th>
			<th align="center">Monto Factura</th>
			<th align="center">Saldo Factura</th>
		</tr>
	  </thead>
	  <tbody>
<?php while(!$rs->EOF){
	
$saldo_factura=$rs->fields['saldo_factura'];
$total_factura=$rs->fields['totalcobrar'];
if($rs->fields['tipo_venta'] == 2){
	$sugiere_nota=$saldo_factura;
}else{
	$sugiere_nota=$total_factura;
}
$idventa=$rs->fields['idventa'];
	 ?>
		<tr>
			<td align="center"><a href="#" class="btn btn-sm btn-default" onClick="buscar_factura_varias(<?php echo intval($rs->fields['idventa']); ?>);"><span class="fa fa-check-square-o"></span></a></td>
			<td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
            <td align="center"><?php if($rs->fields['tipo_venta'] == 1){ echo "Contado"; }else{ echo "Credito"; } ?></td>
			<td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
			<td align="center"><?php if($rs->fields['fecha'] != ""){ echo date("d/m/Y H:i:s",strtotime($rs->fields['fecha'])); }  ?></td>
			<td align="center"><?php echo $rs->fields['ruc']; ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['totalcobrar']); ?></td>
            <td align="center"><?php if($rs->fields['tipo_venta'] == 1){ echo "No Aplica"; }else{ echo formatomoneda($rs->fields['saldo_factura']); } ?></td>

		</tr>
<?php $rs->MoveNext(); } //$rs->MoveFirst(); ?>
	  </tbody>
    </table>
</div>
<br />
<?php 
	}else{ // if(intval($recordCount) > 1){
?>


<hr />
<strong>Factura Encontrada:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idventa</th>
            <th align="center">Condicion</th>
			<th align="center">Factura</th>
			<th align="center">Fecha</th>
			<th align="center">RUC</th>
			<th align="center">Razon Social</th>
			<th align="center">Sucursal</th>
			<th align="center">Monto Factura</th>
			<th align="center">Saldo Factura</th>
		</tr>
	  </thead>
	  <tbody>
<?php //while(!$rs->EOF){
	
$saldo_factura=$rs->fields['saldo_factura'];
$total_factura=$rs->fields['totalcobrar'];
if($rs->fields['tipo_venta'] == 2){
	$sugiere_nota=$saldo_factura;
}else{
	$sugiere_nota=$total_factura;
}
$idventa=$rs->fields['idventa'];
	 ?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
            <td align="center"><?php if($rs->fields['tipo_venta'] == 1){ echo "Contado"; }else{ echo "Credito"; } ?></td>
			<td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
			<td align="center"><?php if($rs->fields['fecha'] != ""){ echo date("d/m/Y H:i:s",strtotime($rs->fields['fecha'])); }  ?></td>
			<td align="center"><?php echo $rs->fields['ruc']; ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['totalcobrar']); ?></td>
            <td align="center"><?php if($rs->fields['tipo_venta'] == 1){ echo "No Aplica"; }else{ echo formatomoneda($rs->fields['saldo_factura']); } ?></td>

		</tr>
<?php //$rs->MoveNext(); } //$rs->MoveFirst(); ?>
	  </tbody>
    </table>
</div>
<br />

<?php 
// articulo
if($clase == 1){
//////////////////////////////////////////////agregar join

$consulta="
select ventas_detalles.idprod, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal,
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo,
productos.descripcion as producto
from ventas_detalles 
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where 
 ventas_detalles.idventa = $idventa
 and ventas_detalles.idprod not in (select codproducto from nota_credito_cuerpo where idnotacred = $idnotacred)
group by ventas_detalles.idprod
";

$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	
?>
<strong>Detalle de la Factura:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center"><?php if($_POST['clase'] == 1){?>Cantidad<?php }else{ ?>Monto<?php } ?> Aplicar</th>
			<?php if($_POST['clase'] == 1){?><th align="center">Deposito</th><?php } ?>
			<th align="center">Codigo</th>
			<th align="center">Codigo Barras</th>
			<th align="center">Articulo</th>
			<th align="center">Cantidad</th>
			<th align="center">Precio</th>

			
		</tr>
	  </thead>
	  <tbody>
<?php while(!$rs->EOF){ ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="javascript:void(0);" onMouseUp="cargar_articulo_factura(<?php echo $idventa ?>,<?php echo $rs->fields['idprod']; ?>,<?php echo $clase; ?>);" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a>
				</div>

			</td>
			<td align="center"><input name="idprod_<?php echo intval($rs->fields['idprod']); ?>" id="idprod_<?php echo intval($rs->fields['idprod']); ?>" type="text" value="" class="form-control"></td>
			<?php if($_POST['clase'] == 1){?>
            <td align="center"><?php
			
$idprod=intval($rs->fields['idprod']);
			 
// consulta
$consulta="
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if(isset($_POST['iddeposito_'.$idprod])){ 
	$value_selected=htmlentities($_POST['iddeposito_'.$idprod]); 
}else{
	$value_selected=htmlentities($rs->fields['iddeposito']);
}

// parametros
$parametros_array=array(
	'nombre_campo' => 'iddeposito_'.$idprod,
	'id_campo' => 'iddeposito_'.$idprod,
	
	'nombre_campo_bd' => 'descripcion',
	'id_campo_bd' => 'iddeposito',
	
	'value_selected' => $value_selected,
	
	'pricampo_name' => 'Seleccionar...',
	'pricampo_value' => '',
	'style_input' => 'class="form-control"', 
	'acciones' => ' required="required" ',
	'autosel_1registro' => 'S'

);

// construye campo
echo campo_select($consulta,$parametros_array);

?></td>
<?php } ?>
			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['barcode']);  ?></td>
            <td align="left"><?php echo antixss($rs->fields['producto']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['subtotal']/$rs->fields['cantidad']);  ?></td>
		</tr>
<?php $rs->MoveNext(); } //$rs->MoveFirst(); ?>
	  </tbody>
    </table>
</div>

<?php } ?>
<?php 
// monto
if($clase == 2){  
	if($idorden_retiro != 0) {
		$consulta="SELECT 
						SUM(t1.cantidad * t1.pventa) as devolucion_costo 
					from 
						(
						SELECT 
							devolucion_det.cantidad, 
							devolucion_det.idproducto, 
							(
							select 
								insumos_lista.descripcion 
							from 
								insumos_lista 
							where 
								insumos_lista.idproducto = devolucion_det.idproducto
							) as insumo, 
							(
							SELECT 
								ventas_detalles.pventa 
							from 
								ventas_detalles 
							WHERE 
								ventas_detalles.idprod = devolucion_det.idproducto 
								and ventas_detalles.idventa = devolucion.idventa 
							limit 
								1
							) as pventa 
						FROM 
							devolucion_det 
							INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion 
							INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion_det.iddevolucion 
						WHERE 
							retiros_ordenes.idorden_retiro = $idorden_retiro
						) as t1
					";
					 $rs_devolucion=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
					 $sugiere_nota=$rs_devolucion->fields['devolucion_costo'];
	}
	?>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Total Factura *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="totalcobrar" id="totalcobrar" value="<?php  if(isset($_POST['totalcobrar'])){ echo intval($_POST['totalcobrar']); }else{ echo intval($sugiere_nota); }?>" placeholder="Totalcobrar" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Concepto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="concepto" id="concepto" value="<?php  if(isset($_POST['concepto'])){ echo intval($_POST['concepto']); }else{ echo $motivo; }?>" placeholder="Ej: descuento concedido por xxx" class="form-control" required="required" />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
	   <button type="button" class="btn btn-default" onMouseUp="cargar_monto_factura(<?php echo $idventa; ?>);" ><span class="fa fa-plus"></span> Agregar</button>
        </div>
    </div>

<?php } ?>
<?php 
// monto articulo
if($clase == 3){

$consulta="
select ventas_detalles.idprod, sum(ventas_detalles.cantidad) as cantidad, sum(ventas_detalles.subtotal) as subtotal,
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo,
productos.descripcion as producto
from ventas_detalles 
inner join productos on productos.idprod_serial = ventas_detalles.idprod
where 
 ventas_detalles.idventa = $idventa
 and ventas_detalles.idprod not in (select codproducto from nota_credito_cuerpo where idnotacred = $idnotacred)
group by ventas_detalles.idprod
";

$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

if($idorden_retiro != 0){
	$consulta = "SELECT (t1.cantidad * t1.pventa) as subtotal, t1.cantidad, t1.idinsumo, t1.idproducto as idprod, t1.insumo as producto from (SELECT 
	devolucion_det.cantidad, devolucion_det.idproducto, insumos_lista.idinsumo,
	(
		select 
		insumos_lista.descripcion 
		from 
		insumos_lista 
		where 
		insumos_lista.idproducto = devolucion_det.idproducto
	) as insumo,
    (
        SELECT ventas_detalles.pventa 
        from ventas_detalles 
        WHERE ventas_detalles.idprod = devolucion_det.idproducto 
        and ventas_detalles.idventa = devolucion.idventa limit 1
    ) as pventa
	FROM 
		devolucion_det 
		INNER JOIN devolucion on devolucion.iddevolucion = devolucion_det.iddevolucion
		INNER JOIN retiros_ordenes on retiros_ordenes.iddevolucion = devolucion_det.iddevolucion
		INNER JOIN insumos_lista on insumos_lista.idproducto = devolucion_det.idproducto
	WHERE 
		retiros_ordenes.idorden_retiro = $idorden_retiro) as t1";
	$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	
}
?>
<strong>Detalle de la Factura:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Monto Aplicar</th>
			<th align="center">Codigo</th>
			<th align="center">Codigo Barras</th>
			<th align="center">Articulo</th>
			<th align="center">Cantidad</th>
			<th align="center">Precio</th>

			
		</tr>
	  </thead>
	  <tbody>
<?php while(!$rs->EOF){ ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="javascript:void(0);" onMouseUp="cargar_monto_articulo_factura(<?php echo $idventa ?>,<?php echo $rs->fields['idprod']; ?>,<?php echo $clase; ?>);" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a>
				</div>

			</td>
			<td align="center"><input name="idprod_<?php echo intval($rs->fields['idprod']); ?>" id="idprod_<?php echo intval($rs->fields['idprod']); ?>" type="text" value="" class="form-control"></td>
			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['barcode']);  ?></td>
            <td align="left"><?php echo antixss($rs->fields['producto']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['subtotal']/$rs->fields['cantidad']);  ?></td>




		</tr>
<?php $rs->MoveNext(); } //$rs->MoveFirst(); ?>
	  </tbody>
    </table>
</div>

<?php } ?>
<div class="clearfix"></div>

<?php 	} // if(intval($recordCount) > 1){ ?>
<?php }  ?>