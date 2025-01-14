<?php 
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo="1";
$submodulo="30";
$pag="carrito";//para sacar despues el tema de submodulo de aca
$dirsup_sec="S";
require_once("../../includes/rsusuario.php");

function facturas_quedan($parametros_array){
	global 	$conexion;
	
	
	$factura_suc=intval($parametros_array['factura_suc']);
	$factura_pexp=intval($parametros_array['factura_pexp']);
	
	// busca si existe algun registro
	$consulta="
	select 
		(
		Select numfac as mayor 
		from lastcomprobantes 
		where 
		idsuc=$factura_suc 
		and pe=$factura_pexp 
		order by ano desc 
		limit 1
		) as ultfactura,
		facturas.fin,
		(select timbrado from timbrado where idtimbrado = facturas.idtimbrado) as timbrado,
		(select fin_vigencia from timbrado where idtimbrado = facturas.idtimbrado) as fin_vigencia
	from facturas 
	where 
	 estado = 'A' 
	 and idtipodocutimbrado = 1
	 and sucursal = $factura_suc
	 and punto_expedicion = $factura_pexp
	 order by (select fin_vigencia from timbrado where idtimbrado = facturas.idtimbrado) desc, facturas.fin desc
	limit 1
	";
	$rsfactura=$conexion->Execute($consulta) or die(errorpg( $conexion,$consulta));
	$ultimo_usado=$rsfactura->fields['ultfactura'];
	$proxima_factura=intval($rsfactura->fields['ultfactura'])+1;
	$hab_hasta=intval($rsfactura->fields['fin']);
	$restantes=$hab_hasta-$ultimo_usado;
	$fin_vigencia=$rsfactura->fields['fin_vigencia'];
	$timbrado=$rsfactura->fields['timbrado'];
	
	/*
	echo "Ult Factura: ".$ultimo_usado;
	echo "<br />";
	echo "Prox Factura: ".$proxima_factura;
	echo "<br />";
	echo "Habilitado hasta: ".$hab_hasta;
	echo "<br />";
	echo "Quedan: ".$quedan;
	echo "<br />";
	*/
	$res=array(
		'ult_factura' => $ultimo_usado,
		'prox_factura' => $proxima_factura,
		'factura_hasta' => $hab_hasta,
		'facturas_restan' => $restantes,
		'fin_vigencia' => $fin_vigencia,
		'timbrado' => $timbrado,
	);
	
	return $res;
}

$parametros_array=array(
	'factura_suc' => $factura_suc,
	'factura_pexp' => $factura_pexp,

);
$res=facturas_quedan($parametros_array);



if($tipocarrito==0){
	//comprobamos que no venga de un post
	$tipocarrito=intval($_REQUEST['tipocarrito']);
	
}
//Traemos las preferencias para la empresa
$buscar="
Select 
carry_out,alerta_ventas,forzar_agrupacion ,max_items_factura,descuentoxsucu,delivery_guarda,
factura_obliga
 from preferencias 
 where 
 idempresa=$idempresa
  ";
$rspref=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
$carry_out=trim($rspref->fields['carry_out']);
$alerta_ventas=trim($rspref->fields['alerta_ventas']);
$forzar_agrupacion=trim($rspref->fields['forzar_agrupacion']);
$max_items_factura=intval($rspref->fields['max_items_factura']);
$descuentoxsucu=trim($rspref->fields['descuentoxsucu']);
$delivery_guarda=trim($rspref->fields['delivery_guarda']);
$factura_obliga=trim($rspref->fields['factura_obliga']);
// preferencias caja
$consulta="
SELECT 
usa_canalventa, usa_clienteprevio, avisar_quedanfac, usa_ventarapida, usa_ventarapidacred,
permite_desc_productos
FROM preferencias_caja 
WHERE  
idempresa = $idempresa 
";
$rsprefcaj=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$avisar_quedanfac=trim($rsprefcaj->fields['avisar_quedanfac']);
$usa_canalventa=trim($rsprefcaj->fields['usa_canalventa']);
$usa_clienteprevio=trim($rsprefcaj->fields['usa_clienteprevio']);
$usa_ventarapidacred=trim($rsprefcaj->fields['usa_ventarapidacred']);
$usa_ventarapida=trim($rsprefcaj->fields['usa_ventarapida']);
$permite_desc_productos=trim($rsprefcaj->fields['permite_desc_productos']); 

if ($descuentoxsucu=='S'){
	
	$buscar="Select * from sucursal_parametros where idsucursal=$idsucursal and idusu=$idusu";
	
	$rsbb=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
	
	$habilitadescprod=trim($rsbb->fields['desc_producto']);

}

//Cliente x defecto
$buscar="Select * from cliente where borrable='N' and idempresa=$idempresa";
$rsoclci=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

$domicilio=intval($_COOKIE['dom_deliv']);
if ($domicilio> 0){
	$buscar="Select * from cliente_delivery inner join cliente_delivery_dom
	on cliente_delivery.idclientedel=cliente_delivery_dom.idclientedel
	where iddomicilio=$domicilio and cliente_delivery.idempresa=$idempresa limit 1
	";
	$rscasa=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
	$direccion=trim($rscasa->fields['direccion']);
	$telefono=trim($rscasa->fields['telefono']);
	$nombreclidel=trim($rscasa->fields['nombres']);
	
	
}





$buscar="
Select gest_zonas.idzona,descripcion,costoentrega
from gest_zonas
where 
estado=1 
and gest_zonas.idempresa = $idempresa 
and gest_zonas.idsucursal = $idsucursal
order by descripcion asc
";
$rszonas=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

$buscar="
select * from cliente where borrable = 'N' and idempresa = $idempresa limit 1
";
$rsclipred=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

if(intval($_SESSION['idlistaprecio']) > 1){
	$idlistaprecio=intval($_SESSION['idlistaprecio']);
	$consulta="
	select * from lista_precios_venta where idlistaprecio = $idlistaprecio and estado = 1 limit 1;
	";	
	$rslistapre=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	$lista_precio=$rslistapre->fields['lista_precio'];
}
if(intval($_SESSION['idcanalventa']) > 0){
	$idcanalventa=intval($_SESSION['idcanalventa']);
	$consulta="
	select * from canal_venta where idcanalventa = $idcanalventa and estado = 1 limit 1;
	";	
	$rscanalv=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	$canal_venta=$rscanalv->fields['canal_venta'];
}
if(intval($_SESSION['idclienteprevio']) > 0){
	$idclienteprevio=intval($_SESSION['idclienteprevio']);
	$consulta="
	select razon_social from cliente where idcliente = $idclienteprevio and estado = 1 limit 1;
	";	
	$rscliprev=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	$cliente_previo=$rscliprev->fields['razon_social'];
}
if(intval($_SESSION['idfacturaregalia']) > 0){
	$idfacturaregalia=intval($_SESSION['idfacturaregalia']);
	$consulta="
	select nombre_franquicia 
	from facturas_regalia
	inner join franquicia on franquicia.idfranquicia = facturas_regalia.idfranquicia
	where 
	facturas_regalia.idfacturaregalia = $idfacturaregalia 
	and facturas_regalia.estado = 1 
	limit 1;
	";	
	$rscliprev=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	$nombre_franquicia=$rscliprev->fields['nombre_franquicia'];
}


?>
<?php if ($tipocarrito==0){?>
<?php if(intval($_SESSION['idpulsera']) > 0){?>
<div style="border:1px solid #000000; text-align:center; background-color:#F8FFCC; font-weight:bold;">
Pulsera: 
</div>
<div style="border:1px solid #000000; text-align:center;">
<a href="pulseras/pulsera_asigna.php">[Cambiar]</a> | <a href="pulseras/pulsera_asigna_reg.php?id=0">[Borrar]</a>
</div>
<?php } ?>
<?php if($_COOKIE['dom_deliv'] > 0){?>
<div style="border:1px solid #000000; text-align:center; background-color:#F8FFCC; font-weight:bold;">
DELIVERY: <?php echo $nombreclidel?>
</div>
<div style="border:1px solid #000000; text-align:center;">
<a href="delivery_clie_edita_dir.php?id=<?php echo intval($_COOKIE['dom_deliv']); ?>">[Ver]</a> <a href="delivery_pedidos.php">[Cambiar]</a> | <a href="#" onMouseUp="borra_delivery();">[Borrar]</a>
</div>
<?php } ?>
<?php if($_SESSION['idwebpedido'] > 0){ ?>
<div style="border:1px solid #000000; text-align:center; background-color:#F8FFCC; font-weight:bold;">
PEDIDO WEB N&ordm; <?php echo intval($_SESSION['idwebpedido']); ?>
</div>
<div style="border:1px solid #000000; text-align:center;">
<a href="web_pedidos_rec.php">[Cambiar]</a> | <a href="#" onMouseUp="borra_pedidoweb();">[Borrar]</a>
</div>
<?php } ?>
<?php 
if($_SESSION['idlistaprecio'] > 1){ ?>
<div style="border:1px solid #000000; text-align:center; background-color:#F8FFCC; font-weight:bold;">
Lista Precio: <?php echo antixss($lista_precio); ?>
</div>
<div style="border:1px solid #000000; text-align:center;">
<a href="gest_ventas_resto_carrito_listaprecio.php">[Cambiar]</a> | <a href="#" onMouseUp="document.location.href='gest_ventas_resto_carrito_listaprecio_aplica.php?id=0'">[Borrar]</a>
</div>
<?php } ?>
<?php 
if($_SESSION['idcanalventa'] > 0){ ?>
<div style="border:1px solid #000000; text-align:center; background-color:#F8FFCC; font-weight:bold;">
Canal Venta: <?php echo antixss($canal_venta); ?>
</div>
<div style="border:1px solid #000000; text-align:center;">
<a href="gest_ventas_resto_carrito_canalventa.php">[Cambiar]</a> | <a href="#" onMouseUp="document.location.href='gest_ventas_resto_carrito_canalventa_aplica.php?id=0'">[Borrar]</a>
</div>
<?php } ?>
<?php 
//print_r($_SESSION);
if($_SESSION['idclienteprevio'] > 0){ ?>
<div style="border:1px solid #000000; text-align:center; background-color:#F8FFCC; font-weight:bold;">
Cliente Prev: <?php echo antixss($cliente_previo); ?>
</div>
<div style="border:1px solid #000000; text-align:center;">
<a href="gest_ventas_resto_carrito_clienteprevio.php">[Cambiar]</a> | <a href="#" onMouseUp="document.location.href='gest_ventas_resto_carrito_clienteprevio_aplica.php?id=0'">[Borrar]</a>
</div>
<?php } ?>
<?php 
//print_r($_SESSION);
if(intval($_SESSION['idfacturaregalia']) > 0){ ?>
<div style="border:1px solid #000000; text-align:center; background-color:#F8FFCC; font-weight:bold;">
Franquiciado: <?php echo antixss($nombre_franquicia); ?>
</div>
<div style="border:1px solid #000000; text-align:center;">
<a href="#" onMouseUp="document.location.href='gest_ventas_resto_carrito_facturaregalia_del.php?id=<?php echo intval($_SESSION['idfacturaregalia']); ?>'">[Borrar]</a>
</div>
<?php } ?>
<?php }?>
<div style="border-bottom:1px solid #CCCCCC; height:500px; overflow-y:scroll; font-size:10px;">
<table width="98%" border="1" class="tablalinda" id="tablacarrito" style="border-color:#CCCCCC;">
  <tbody>
    <tr>
      <td height="29" bgcolor="#F1F1F1"><strong>Producto</strong></td>
      <td align="center" bgcolor="#F1F1F1"><strong>Cant.</strong></td>
      <td align="center" bgcolor="#F1F1F1"><strong>lote</strong></td>
      <td align="center" bgcolor="#F1F1F1"><strong>vto.</strong></td>
      <td align="center" bgcolor="#F1F1F1"><strong>Total</strong></td>
      <td width="50" align="center" bgcolor="#F1F1F1">&nbsp;</td>
    </tr>
<?php 
$consulta="
select tmp_ventares.*, productos.descripcion, sum(cantidad) as total, sum(precio) as totalprecio, sum(subtotal) as subtotal,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado,
tmp_ventares.idtipoproducto, tmp_ventares.idprod_mitad2, tmp_ventares.idprod_mitad1,
(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idtipoproducto not in (2,3,4)

and (
	select tmp_ventares_agregado.idventatmp 
	from tmp_ventares_agregado 
	WHERE 
	tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
	limit 1
) is null
and (
	select tmp_ventares_sacado.idventatmp 
	from tmp_ventares_sacado 
	WHERE 
	tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
	limit 1
) is null
and tmp_ventares.observacion is null
and tmp_ventares.desconsolida_forzar is null

group by descripcion, receta_cambiada
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	$cc=0;  
	while (!$rs->EOF){
		$cc=$cc+1;
	$total=$rs->fields['subtotal'];
	$totalacum+=$total;
	$cantacum+=$rs->fields['total'];
	$des=str_replace("'","",$rs->fields['descripcion']);
	$muestra_grupo_combo=$rs->fields['muestra_grupo_combo'];
	
	// 1 producto 2 combo 3 combinado 4 combinado extendido
	$idtipoproducto=$rs->fields['idtipoproducto'];
	$idprod_mitad1=$rs->fields['idprod_mitad1'];
	$idprod_mitad2=$rs->fields['idprod_mitad2'];
	$idventatmp=$rs->fields['idventatmp'];
	if($idtipoproducto == 3){
		$consulta="
		select * 
		from productos 
		where 
		(idprod_serial = $idprod_mitad1 or idprod_serial = $idprod_mitad2)
		limit 2
		";	
		$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	}
		// combinado extendido
	if($idtipoproducto == 4){
		$consulta="
		select * 
		from productos 
		inner join tmp_combinado_listas on tmp_combinado_listas.idproducto_partes = productos.idprod_serial
		where 
		tmp_combinado_listas.idventatmp = $idventatmp
		limit 20
		";	
		$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	}
		// combo
	if($idtipoproducto == 2){
		if($muestra_grupo_combo == 'S'){
			$consulta="
			select combos_listas.nombre, productos.descripcion, count(*) as total
			from productos 
			inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
			inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
			where 
			tmp_combos_listas.idventatmp = $idventatmp
			group by combos_listas.nombre, productos.descripcion
			order by combos_listas.idlistacombo asc
			limit 20
			";
			$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		}else{
			$consulta="
			select productos.descripcion , count(*) as total
			from productos 
			inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
			where 
			tmp_combos_listas.idventatmp = $idventatmp
			group by productos.descripcion
			limit 20
			";	
			$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		}

	}
	
	
	 ?>
    <tr id="tr_<?php echo $cc;?>" <?php if($idtipoproducto == 5){ ?> style="display:none;"<?php } ?>>
      <td height="30"  id="td_<?php echo $cc;?>"><?php
	  
	 // echo $idtipoproducto;
	  if($idtipoproducto == 5){
		echo "&nbsp;&nbsp;(+) ";  
	  }
	  
	   echo Capitalizar($rs->fields['descripcion']); ?><?php
	   
	// combinado y combinado extendido    
	if($idtipoproducto == 3 or $idtipoproducto == 4){
		$i=0;
		while (!$rsmit->EOF){
			$i++;
			  echo "<br />&nbsp;&nbsp;> Parte $i: ".Capitalizar($rsmit->fields['descripcion']);
		$rsmit->MoveNext(); }

	}
	// combo
	if($idtipoproducto == 2){
		$i=0;
		while (!$rsmit->EOF){
			$total=$rsmit->fields['total'];
			$i++;
			
			if($muestra_grupo_combo == 'S'){
				$nombre_grupo=trim($rsmit->fields['nombre']).": ";
			}
			  echo "<br />&nbsp;&nbsp;> ".$nombre_grupo.$total." x ".Capitalizar($rsmit->fields['descripcion']);
		$rsmit->MoveNext(); }

	} 

?><input type="hidden" name="onp_<?php echo $cc;?>" id="onp_<?php echo $cc;?>"  value="<?php echo $rs->fields['idproducto']; ?>"/><?php 
if(trim($rs->fields['observacion']) != ''){
	echo "<br />&nbsp;&nbsp;<strong>* OBS:</strong> ".antixss($rs->fields['observacion']);
}
?></td>
      <td align="center" style="cursor:pointer;" onclick="edita_cant(<?php echo $rs->fields['idproducto']; ?>);"><?php echo formatomoneda($rs->fields['total'],3,'N'); ?></td>
	  <td align="center"><?php echo antixss($rs->fields['lote']); ?></td>
      <td align="center"><?php echo antixss(date("d/m/Y",strtotime($rs->fields['vencimiento']))); ?></td> 
	  <td align="center"><?php echo formatomoneda($rs->fields['subtotal'],0,'N'); ?></td>
      <td align="center">
      
				<div class="btn-group">
<?php /*if($idtipoproducto != 5 && $idtipoproducto != 6){ if($rs->fields['tienereceta'] > 0 or $rs->fields['tieneagregado'] > 0 or $rs->fields['combinado'] == 'S' or  $rs->fields['idtipoproducto'] == 4){*/ ?>
					<a href="editareceta.php?id=<?php echo $rs->fields['idproducto']; ?>" class="btn btn-sm btn-default" title="Opciones" data-toggle="tooltip" data-placement="right"  data-original-title="Opciones"><span class="fa fa-cogs"></span></a>
<?php /*} }*/ ?>

					<a href="javascript:void(0);" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar" onClick="borrar('<?php echo $rs->fields['idproducto']; ?>','<?php echo Capitalizar($des); ?>');"><span class="fa fa-trash-o"></span></a>
				</div>
      </td>
    </tr>
<?php $rs->MoveNext(); } ?>
    <tr>
<?php 
$consulta="
select tmp_ventares.*, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado,
tmp_ventares.idtipoproducto, tmp_ventares.idprod_mitad2, tmp_ventares.idprod_mitad1
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idtipoproducto not in (2,3,4)
and 
(
	(
		select tmp_ventares_agregado.idventatmp 
		from tmp_ventares_agregado 
		WHERE 
		tmp_ventares_agregado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is not null
	or
	(
		select tmp_ventares_sacado.idventatmp 
		from tmp_ventares_sacado 
		WHERE 
		tmp_ventares_sacado.idventatmp = tmp_ventares.idventatmp
		limit 1
	) is not null
	or 
	(
		tmp_ventares.observacion is not null
	)
	or
	(
		tmp_ventares.desconsolida_forzar = 'S'
	)
)
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	$cc=0;  
	while (!$rs->EOF){
		$cc=$cc+1;
	$total=$rs->fields['subtotal'];
	$totalacum+=$total;
	$cantacum+=$rs->fields['total'];
	$des=str_replace("'","",$rs->fields['descripcion']);
	
	// 1 producto 2 combo 3 combinado 4 combinado extendido
	$idtipoproducto=$rs->fields['idtipoproducto'];
	$idprod_mitad1=$rs->fields['idprod_mitad1'];
	$idprod_mitad2=$rs->fields['idprod_mitad2'];
	$idventatmp=$rs->fields['idventatmp'];
	$muestra_grupo_combo=$rs->fields['muestra_grupo_combo'];
	if($idtipoproducto == 3){
		$consulta="
		select * 
		from productos 
		where 
		(idprod_serial = $idprod_mitad1 or idprod_serial = $idprod_mitad2)
		limit 2
		";	
		$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	}
	if($idtipoproducto == 4){
		$consulta="
		select * 
		from productos 
		inner join tmp_combinado_listas on tmp_combinado_listas.idproducto_partes = productos.idprod_serial
		where 
		tmp_combinado_listas.idventatmp = $idventatmp
		limit 20
		";	
		$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	}
	if($idtipoproducto == 2){
		if($muestra_grupo_combo == 'S'){
			$consulta="
			select combos_listas.nombre, productos.descripcion, count(*) as total
			from productos 
			inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
			inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
			where 
			tmp_combos_listas.idventatmp = $idventatmp
			group by combos_listas.nombre, productos.descripcion
			order by combos_listas.idlistacombo asc
			limit 20
			";
			$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		}else{
			$consulta="
			select productos.descripcion , count(*) as total
			from productos 
			inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
			where 
			tmp_combos_listas.idventatmp = $idventatmp
			group by productos.descripcion
			limit 20
			";	
			$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		}
	}
	
	
	 ?>
    <tr id="tr_<?php echo $cc;?>">
      <td height="30"  id="td_<?php echo $cc;?>"><?php
	  
	  $idventatmp=$rs->fields['idventatmp'];
	  $texto="";
	// busca si tiene agregado
	$idvt=$rs->fields['idventatmp'];
	$consulta="
	select tmp_ventares_agregado.*, 
	tmp_ventares_agregado.precio_adicional*tmp_ventares_agregado.cantidad as precio_adicional
	from tmp_ventares_agregado
	where 
	idventatmp = $idventatmp
	order by alias desc
	";
	$rsag = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	// genera agregados si tiene
	if(trim($rsag->fields['alias']) != ''){
		//$texto.=$saltolinea."   > AGREGADOS:".$saltolinea;
		while (!$rsag->EOF){
			$texto.="&nbsp;&nbsp;&nbsp;(+) ".texto_tk($rsag->fields['alias'],36)."<br />";
			$texto.="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Gs. ".texto_tk(formatomoneda($rsag->fields['precio_adicional']),30)."<br />";
		$rsag->MoveNext(); }
	}
	$texto=trim($texto);
	
	// busca si tiene sacados
	$consulta="
	select tmp_ventares_sacado.*
	from tmp_ventares_sacado
	where 
	idventatmp = $idventatmp
	order by alias desc
	";
	$rssac = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	// genera sacados si tiene
	if(trim($rssac->fields['alias']) != ''){
		//$texto.=$saltolinea."   > EXCLUIDOS:"."<br />";
		while (!$rssac->EOF){
			$texto.="&nbsp;&nbsp;&nbsp;(-) SIN ".texto_tk($rssac->fields['alias'],36)."<br />";
		$rssac->MoveNext(); }
	}
	if(trim($rs->fields['observacion']) != ''){
		$texto.=$saltolinea."&nbsp;&nbsp;&nbsp;*OBS: ".texto_tk($rs->fields['observacion'],35)."<br />";
	}

	 // echo $idtipoproducto;
	  if($idtipoproducto == 5){
		echo "&nbsp;&nbsp;(+) ";  
	  }
	  
	   echo Capitalizar($rs->fields['descripcion']); ?><?php
	   
	// combinado y combinado extendido    
	if($idtipoproducto == 3 or $idtipoproducto == 4){
		$i=0;
		while (!$rsmit->EOF){
			$i++;
			  echo "<br />&nbsp;&nbsp;> Parte $i: ".Capitalizar($rsmit->fields['descripcion']);
		$rsmit->MoveNext(); }

	} 
	// combo
	if($idtipoproducto == 2){
		$i=0;
		while (!$rsmit->EOF){
			$total=$rsmit->fields['total'];
			$i++;
			
			if($muestra_grupo_combo == 'S'){
				$nombre_grupo=trim($rsmit->fields['nombre']).": ";
			}
			  echo "<br />&nbsp;&nbsp;> ".$nombre_grupo.$total." x ".Capitalizar($rsmit->fields['descripcion']);
		$rsmit->MoveNext(); }

	} 
	echo "<br />".$texto;
?><input type="hidden" name="onp_<?php echo $cc;?>" id="onp_<?php echo $cc;?>"  value="<?php echo $rs->fields['idproducto']; ?>"/><?php if(trim($rs->fields['observacion']) != ''){
	echo "<br />&nbsp;&nbsp;<strong>* OBS:</strong> ".antixss($rs->fields['observacion']);
} ?></td>
      <td align="center" ><?php echo formatomoneda($rs->fields['total'],3,'N'); ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['subtotal'],0,'N'); ?></td>
      <td align="center">
	  
      
				<div class="btn-group">
<?php /*if($idtipoproducto != 5 && $idtipoproducto != 6){ if($rs->fields['tienereceta'] > 0 or $rs->fields['tieneagregado'] > 0 or $rs->fields['combinado'] == 'S' or  $rs->fields['idtipoproducto'] == 4){*/ ?>
					<a href="editareceta.php?idvt=<?php echo $rs->fields['idventatmp']; ?>" class="btn btn-sm btn-default" title="Opciones" data-toggle="tooltip" data-placement="right"  data-original-title="Opciones"><span class="fa fa-cogs"></span></a>
<?php /*} }*/ ?>

					<a href="javascript:void(0);" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar" onClick="borrar_item('<?php echo $rs->fields['idventatmp']; ?>','<?php echo $rs->fields['idproducto']; ?>','<?php echo Capitalizar($des); ?>');"><span class="fa fa-trash-o"></span></a>
				</div>
      </td>
    </tr>
<?php $rs->MoveNext(); } ?>
    <tr>
<?php 

$consulta="
select tmp_ventares.*, productos.descripcion, cantidad as total, precio as totalprecio, subtotal as subtotal,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado,
tmp_ventares.idtipoproducto, tmp_ventares.idprod_mitad2, tmp_ventares.idprod_mitad1,
(select muestra_grupo_combo from productos WHERE productos.idprod_serial = tmp_ventares.idproducto limit 1) as muestra_grupo_combo
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
and tmp_ventares.idempresa = $idempresa
and tmp_ventares.idtipoproducto in (2,3,4)
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

	$cc=0;  
	while (!$rs->EOF){
		$cc=$cc+1;
	$total=$rs->fields['subtotal'];
	$totalacum+=$total;
	$cantacum+=$rs->fields['total'];
	$des=str_replace("'","",$rs->fields['descripcion']);
	
	// 1 producto 2 combo 3 combinado 4 combinado extendido
	$idtipoproducto=$rs->fields['idtipoproducto'];
	$idprod_mitad1=$rs->fields['idprod_mitad1'];
	$idprod_mitad2=$rs->fields['idprod_mitad2'];
	$idventatmp=$rs->fields['idventatmp'];
	$muestra_grupo_combo=$rs->fields['muestra_grupo_combo'];
	if($idtipoproducto == 3){
		$consulta="
		select * 
		from productos 
		where 
		(idprod_serial = $idprod_mitad1 or idprod_serial = $idprod_mitad2)
		limit 2
		";	
		$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	}
	if($idtipoproducto == 4){
		$consulta="
		select * 
		from productos 
		inner join tmp_combinado_listas on tmp_combinado_listas.idproducto_partes = productos.idprod_serial
		where 
		tmp_combinado_listas.idventatmp = $idventatmp
		limit 20
		";	
		$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	}
	if($idtipoproducto == 2){
		if($muestra_grupo_combo == 'S'){
			$consulta="
			select combos_listas.nombre, productos.descripcion, count(*) as total
			from productos 
			inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
			inner join combos_listas on combos_listas.idlistacombo = tmp_combos_listas.idlistacombo
			where 
			tmp_combos_listas.idventatmp = $idventatmp
			group by combos_listas.nombre, productos.descripcion
			order by combos_listas.idlistacombo asc
			limit 20
			";
			$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		}else{
			$consulta="
			select productos.descripcion , count(*) as total
			from productos 
			inner join tmp_combos_listas on tmp_combos_listas.idproducto = productos.idprod_serial
			where 
			tmp_combos_listas.idventatmp = $idventatmp
			group by productos.descripcion
			limit 20
			";	
			$rsmit = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
		}
	}
	
	
	 ?>
    <tr id="tr_<?php echo $cc;?>">
      <td height="30"  id="td_<?php echo $cc;?>"><?php
	  
	 // echo $idtipoproducto;
	  if($idtipoproducto == 5){
		echo "&nbsp;&nbsp;(+) ";  
	  }
	  
	   echo Capitalizar($rs->fields['descripcion']); ?><?php
	   
	// combinado y combinado extendido    
	if($idtipoproducto == 3 or $idtipoproducto == 4){
		$i=0;
		while (!$rsmit->EOF){
			$i++;
			  echo "<br />&nbsp;&nbsp;> Parte $i: ".Capitalizar($rsmit->fields['descripcion']);
		$rsmit->MoveNext(); }

	} 
	// combo
	if($idtipoproducto == 2){
		$i=0;
		while (!$rsmit->EOF){
			$total=$rsmit->fields['total'];
			$i++;
			
			if($muestra_grupo_combo == 'S'){
				$nombre_grupo=trim($rsmit->fields['nombre']).": ";
			}
			  echo "<br />&nbsp;&nbsp;> ".$nombre_grupo.$total." x ".Capitalizar($rsmit->fields['descripcion']);
		$rsmit->MoveNext(); }

	} 
	
		  $idventatmp=$rs->fields['idventatmp'];
	  $texto="";
	// busca si tiene agregado
	$idvt=$rs->fields['idventatmp'];
	$consulta="
	select tmp_ventares_agregado.*
	from tmp_ventares_agregado
	where 
	idventatmp = $idventatmp
	order by alias desc
	";
	$rsag = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
	// genera agregados si tiene
	if(trim($rsag->fields['alias']) != ''){
		//$texto.=$saltolinea."   > AGREGADOS:".$saltolinea;
		while (!$rsag->EOF){
			$texto.="&nbsp;&nbsp;&nbsp;(+) ".texto_tk($rsag->fields['alias'],36)."<br />";
			$texto.="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Gs. ".texto_tk(formatomoneda($rsag->fields['precio_adicional']),30)."<br />";
		$rsag->MoveNext(); }
	}
	$texto=trim($texto);
	if($texto != ''){
		echo "<br />".$texto;
	}

?><input type="hidden" name="onp_<?php echo $cc;?>" id="onp_<?php echo $cc;?>"  value="<?php echo $rs->fields['idproducto']; ?>"/><?php if(trim($rs->fields['observacion']) != ''){
	echo "<br />&nbsp;&nbsp;<strong>* OBS:</strong> ".antixss($rs->fields['observacion']);
} ?></td>
      <td align="center" ><?php echo formatomoneda($rs->fields['total'],3,'N'); ?></td>
      <td align="center"><?php echo formatomoneda($rs->fields['subtotal'],0,'N'); ?></td>
      <td align="center">
				<div class="btn-group">
<?php /*if($idtipoproducto != 5 && $idtipoproducto != 6){ if($rs->fields['tienereceta'] > 0 or $rs->fields['tieneagregado'] > 0 or $rs->fields['combinado'] == 'S' or  $rs->fields['idtipoproducto'] == 4){*/ ?>
					<a href="editareceta.php?idvt=<?php echo $rs->fields['idventatmp']; ?>" class="btn btn-sm btn-default" title="Opciones" data-toggle="tooltip" data-placement="right"  data-original-title="Opciones"><span class="fa fa-cogs"></span></a>
                    
<?php /*} }*/ ?>
<?php if($rs->fields['idtipoproducto'] == 2){ // combo ?>
					<a href="duplica_combo.php?id=<?php echo $rs->fields['idventatmp']; ?>" class="btn btn-sm btn-default" title="Duplicar Combo" data-toggle="tooltip" data-placement="right"  data-original-title="Duplicar Combo"><span class="fa fa-files-o"></span></a>
<?php } ?>

					<a href="javascript:void(0);" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar" onClick="borrar_item('<?php echo $rs->fields['idventatmp']; ?>','<?php echo $rs->fields['idproducto']; ?>','<?php echo Capitalizar($des); ?>');"><span class="fa fa-trash-o"></span></a>
                    
				</div>

   </td>
    </tr>
<?php $rs->MoveNext(); } ?>
    <tr>
		<td height="39" colspan="4" align="center"><strong>Cantidad: <?php echo formatomoneda($cantacum,2,'N'); ?></strong><br /><strong><span style="font-size: 16px;color: #DB171A">Total Venta: <?php echo formatomoneda($totalacum,0); ?><input type="hidden" name="totalventa" id="totalventa" value="<?php echo $totalacum; ?>"><input type="hidden" name="lastelemento" id="lastelemento" value=""><input type="hidden" name="totalelementos" id="totalelementos" value="<?php echo $cc;?>">
		<input type="hidden" name="totalventa_actu" id="totalventa_actu" value="<?php echo $totalacum; ?>">	
    <input type="hidden" name="totalventa_real" id="totalventa_real" value="<?php echo $totalacum; ?>"></span></strong> <?php if($permite_desc_productos == 'S'){ ?><a href="javascript:void(0);" onClick="document.location.href='carrito_descuento.php?id=<?php echo $rs->fields['idventatmp']; ?>'"><img src="../../img/desc_mini.png" width="20" height="20" alt="Descuento" /></a><?php } ?></td>
    </tr>
  </tbody>
</table>
<p align="center"><a href="javascript:void(0);" onClick="borrar_todo();"><img src="../../img/trashico.png" width="20" height="20" alt="Borrar Todo" name="Borrar Todo" /></a><br />
<img src="<?php echo $rsco->fields['logo_sys_carrito']; ?>" width="200" height="73" alt="" style="margin:10px; "/></p><br />
<?php if(date('d/m') == '25/08'){ ?>
<p align="center"><strong>¡¡¡ FELIZ DIA DEL IDIOMA GUARANI  !!!</strong>  <a href="25_agosto.php" target="_blank"> [+ Info]</a></p>
<?php } ?>
	<div align="center">
		<h3>Cajero: <?php echo $cajero; ?></h3>
        <strong>Sucursal: <?php echo $nombresucursal ?></strong><br /><br />
		<?php if ($habilitadescprod=='S'){?>
			<a href="gest_ventas_carrito_descontar.php?o=1" class="button grey micro radius">&nbsp;
            	<img src="img/percentforecast-512.png" width="40px" height="40px;"/>
                
				<span class="icon-percent" style=" font-size: 40px; "></span>
                </a>
		<?php }?>
	
	</div>
<?php if($alerta_ventas != ''){ ?>
<div class="alerta_recuerda"><strong><?php echo $alerta_ventas; ?></strong></div>
<?php } ?>
<p align="center"><strong>Suc/Exp: <?php echo agregacero($factura_suc,3).'-'.agregacero($factura_pexp,3); ?></strong></p>
<?php 
if($avisar_quedanfac > 0){
	if($res['facturas_restan'] <= $avisar_quedanfac){
		$facturas_restan=formatomoneda($res['facturas_restan'],0,'N');
		echo '<div class="mensaje" style="border:1px solid #FF0000; background-color:#F8FFCC; width:90%; margin:0px auto; text-align:center;"><strong>Atencion! quedan solo ('.$facturas_restan.') facturas para el punto de expedicion de esta pc ('.agregacero($factura_suc,3).'-'.agregacero($factura_pexp,3).'), avisar a la administracion.</strong></div>';
	}
	// avisar vencimiento 10 dias antes
	if(strtotime($res['fin_vigencia']."- 10 days") < strtotime(date("Y-m-d"))){
		if(strtotime($res['fin_vigencia']) < strtotime(date("Y-m-d"))){
			$vence_txt="vencio";
		}else{
			$vence_txt="vencera";
		}
		$fin_vigencia=date("d/m/Y",strtotime($res['fin_vigencia']));
		echo '<div class="mensaje" style="border:1px solid #FF0000; background-color:#F8FFCC; width:90%; margin:0px auto; text-align:center;"><strong>Atencion! Su timbrado ['.$timbrado.'] '.$vence_txt.' en fecha '.$fin_vigencia.', avisar a la administracion.</strong></div>';
	}
}
?>


</div>
<br />
<?php if($valido == "N"){ ?>
<div class="mensaje">
<strong>Errores:</strong><br />
<?php echo $errores; ?>
</div><br />
<?php } ?>
<?php if ($tipocarrito==0){?>


<div  style="width:100%; margin-left:0px; height:240px; margin-top: 5px;border:0px solid #000;" >


<?php if($usa_ventarapida=='S'){ ?>
<?php // if($factura_obliga != 'S'){?>
<div style="margin:0px auto; width:100%; text-align:center;">
<!--<input name="" type="button" value="&check; Venta Rapida" onmouseup="venta_rapida();" class="boton_vrapida" /> -->
<a href="javascript:void(0);" class="btn btn-sm btn-default" onmouseup="venta_rapida();"><span class="fa fa-bolt"></span> Venta Rapida</a>
<br /><br />
</div>
<?php //} ?>
<?php } ?>
	  <input type="hidden" name="occliedefe" id="occliedefe" value="<?php echo $rsoclci->fields['idcliente']?>" />
		<table width="300">
			<tr>
			  <td width="150" height="38" ><a id="occ" href="#pop5" style="visibility:hidden"  >ABRETE</a>
			  <a id="ocb" href="#pop3" onClick="abrecodbarra();" style="visibility:hidden">abrir</a></td>
			  <td width="78" >
				  <?php if (intval($totalacum)>0){?>
                <span id="btn-cobrar" class="btn btn-app" onClick="cobranza('','',1)"><i class="fa fa-money"></i> Cobrar</span>
				<?php }?>
			</td>
			  <td width="65" >  
              <a id="btn-caja" href="gest_administrar_caja.php?lo=1" class="btn btn-app" target="_blank" ><i class="fa fa-calculator"></i> Caja</a></td>
			  <td width="187" > 
              <a id="btn-reimpresion" href="gest_impresiones.php" class="btn btn-app" target="_blank" ><i class="fa fa-print"></i> Reimpresiones</a>

		 	  </td>
			</tr>
        </table><br />
        <p align="center">

        <a href="../delivery/delivery_pedidos.php" class="btn btn-sm btn-default"><span class="fa fa-motorcycle"></span> Tomar Delivery</a>
<?php if ($carry_out == 'S'){ ?>        &nbsp;
          <a href="javascript:void(0);"  onMouseUp="carry_out();" class="btn btn-sm btn-default"><span class="fa fa-car"></span> Pasa a Buscar</a>
          
<?php } ?>
<?php if ($delivery_guarda == 'S'){ ?>
        &nbsp;
          <a href="#pop6"  onmouseover="popupasigna6();" onMouseUp="delivery_pedidos();"><input type="button" name="button" id="button" value="Delivery Guardar" class="btn btn-sm btn-default" ></a>
<?php } ?>
<?php if($rsco->fields['usa_listaprecio'] == 'S'){ ?>&nbsp;
        <!--<a href="#"   onMouseUp="document.location.href='gest_ventas_resto_carrito_listaprecio.php'"><input type="button" name="button" id="button" value="Lista Precios" class="btn btn-sm btn-default"  ></a>-->
        <a href="gest_ventas_resto_carrito_listaprecio.php" class="btn btn-sm btn-default"><span class="fa fa-tags"></span> Lista Precios</a>
<?php } ?>
<?php if($usa_canalventa == 'S'){ ?>&nbsp;
        <a href="#"   onMouseUp="document.location.href='gest_ventas_resto_carrito_canalventa.php'"><input type="button" name="button" id="button" value="Canal Venta" class="btn btn-sm btn-default"  ></a>
<?php } ?>
<?php if($usa_clienteprevio == 'S'){ ?>&nbsp;
        <a href="#"   onMouseUp="document.location.href='gest_ventas_resto_carrito_clienteprevio.php'"><input type="button" name="button" id="button" value="Cliente Previo" class="btn btn-sm btn-default"  ></a>
<?php } ?>
        </p>
        <a id="adhbtn" href="#pop7"  onClick="abreadherente();" style="display:none;" class="btn btn-sm btn-default">Adherente</a>
</div>

<?php }?>