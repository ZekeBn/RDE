<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");
$usar_lote = "S";
$order_vencimiento = "";

$describe = antisqlinyeccion($_POST['bb'], 'text');
$mini = str_replace("'", "", $describe);


$seladd_lp = " productos_sucursales.precio as p1 ";


$idclienteprevio = intval($_SESSION['idclienteprevio']);
if ($idclienteprevio > 0) {
    $consulta = "
	select idcliente, idvendedor, idcanalventacli
	from cliente 
	where 
	idcliente = $idclienteprevio
	and estado <> 6 
	limit 1
	";
    $rscprev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idclienteprevio = $rscprev->fields['idcliente'];
    if ($idclienteprevio > 0) {
        $idcliente = $idclienteprevio;
        $idvendedor = intval($rscprev->fields['idvendedor']);
        $idcanalventa = intval($rscprev->fields['idcanalventacli']);
    }
}

$idlistaprecio = intval($_SESSION['idlistaprecio']);
$idcanalventa = intval($_SESSION['idcanalventa']);
$idclienteprevio = intval($_SESSION['idclienteprevio']);
if ($idclienteprevio > 0) {
    $consulta = "
	select idcliente, idvendedor, idcanalventacli
	from cliente 
	where 
	idcliente = $idclienteprevio
	and estado <> 6 
	limit 1
	";
    $rscprev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idclienteprevio = $rscprev->fields['idcliente'];
    if ($idclienteprevio > 0) {
        $idcliente = $idclienteprevio;
        $idvendedor = intval($rscprev->fields['idvendedor']);
        $idcanalventa = intval($rscprev->fields['idcanalventacli']);
    }
}
if ($idcanalventa > 0) {
    $consulta = "
	select idlistaprecio, idcanalventa, canal_venta 
	from canal_venta 
	where 
	idcanalventa = $idcanalventa 
	and estado = 1
	limit 1
	";
    $rscv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idlistaprecio = intval($rscv->fields['idlistaprecio']);
}
//echo $idlistaprecio;
if ($idlistaprecio > 0) {
    $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
    $whereadd_lp = "
	and productos_listaprecios.idsucursal = $idsucursal 
	and productos_listaprecios.idlistaprecio = $idlistaprecio 
	and productos_listaprecios.estado = 1
	";
    $seladd_lp = " productos_listaprecios.precio as p1";
}
if ($usar_lote == "S") {
    $total_tmp = "(
		select 
		sum(cantidad) as total
		from tmp_ventares 
		where 
		tmp_ventares.registrado = 'N'
		and tmp_ventares.usuario = $idusu
		and tmp_ventares.borrado='N'
		and tmp_ventares.finalizado = 'N'
		and tmp_ventares.idproducto = productos.idprod_serial
		and tmp_ventares.lote = gest_depositos_stock.lote
		and DATE_FORMAT(tmp_ventares.vencimiento, '%Y-%m-%d') = gest_depositos_stock.vencimiento
		and tmp_ventares.idempresa = $idempresa
		and tmp_ventares.idsucursal = $idsucursal
		) as total";
    $from_join_add = "gest_depositos_stock
	INNER JOIN insumos_lista on insumos_lista.idinsumo = gest_depositos_stock.idproducto
	INNER JOIN productos on productos.idprod = insumos_lista.idproducto ";
    $disponible_valor_select = "gest_depositos_stock.disponible, lote, vencimiento, idregseriedptostk";
    $order_vencimiento = " ,  gest_depositos_stock.vencimiento";
} else {
    $total_tmp = "(
		select 
		sum(cantidad) as total
		from tmp_ventares 
		where 
		tmp_ventares.registrado = 'N'
		and tmp_ventares.usuario = $idusu
		and tmp_ventares.borrado='N'
		and tmp_ventares.finalizado = 'N'
		and tmp_ventares.idproducto = productos.idprod_serial
		and tmp_ventares.idempresa = $idempresa
		and tmp_ventares.idsucursal = $idsucursal
		) as total";
    $from_join_add = "productos ";
    $disponible_valor_select = " (
		select sum(gest_depositos_stock_gral.disponible)
		from insumos_lista
		inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
		where 
		productos.idprod_serial = insumos_lista.idproducto
		and gest_depositos_stock_gral.iddeposito = (
													select gest_depositos.iddeposito 
													from gest_depositos 
													where 
													tiposala = 2 
													and idsucursal = $idsucursal
													)
		) as disponible ";
}
$buscar = "Select *, $seladd_lp,
			$total_tmp
				,
				(
					SELECT excepciones_producto.venta_sin_stock 
					FROM excepciones_producto 
					WHERE excepciones_producto.idproducto = productos.idprod_serial
				) as venta_sin_stock
				,
				$disponible_valor_select
from  $from_join_add 
inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
$joinadd_lp
where 
productos.descripcion like '%$mini%'
and productos.borrado = 'N'
and productos.idempresa = $idempresa
and idprod_serial not in (SELECT excepciones_producto.idproducto FROM excepciones_producto WHERE excepciones_producto.venta = 2)

/*and (productos.idtipoproducto = 1 or productos.idtipoproducto = 6)*/

and productos_sucursales.idsucursal = $idsucursal 
and productos_sucursales.idempresa = $idempresa
and productos_sucursales.activo_suc = 1

$whereadd_lp

order by productos.descripcion asc  $order_vencimiento
limit 50";
$rsbusq = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tr = $rsbusq->RecordCount();

$buscar = "select usa_balanza, muestra_stock_ven from preferencias where idempresa=$idempresa ";
$rsfh = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$balanza = trim($rsfh->fields['usa_balanza']);
$muestra_stock_ven = trim($rsfh->fields['muestra_stock_ven']);
?><div align="center">
<?php if ($tr > 0) {?>
  <table width="90%" border="1" class="tablas_ventacaj">
    <tr>
    	<td width="15" align="center" bgcolor="#F1F1F1"><strong>Cod</strong></td>
        <td width="218" align="center" bgcolor="#F1F1F1"><strong>Producto</strong></td>
        <td width="69"  align="center" bgcolor="#F1F1F1"><strong>Precio Venta Gs</strong></td>
		<?php if ($usar_lote == "S") { ?>
			<td width="50"  align="center" bgcolor="#F1F1F1"><strong>lote</strong></td>
			<td width="50"  align="center" bgcolor="#F1F1F1"><strong>vencimiento</strong></td>
		<?php } ?>
        <?php if ($muestra_stock_ven == 'S') { ?>
        <td width="50"  align="center" bgcolor="#F1F1F1"><strong>Stock</strong></td>
        <?php } ?>
		<?php if ($balanza == 'S') {?>
        <td width="50"  align="center" bgcolor="#F1F1F1"><strong>Pesar (solo x kilo)</strong></td>
		<?php }?>
        <td width="50"  align="center" bgcolor="#F1F1F1"><strong>Cantidad Vender</strong></td>
        <td width="63"  bgcolor="#F1F1F1"></td>
    </tr>
    <?php
    $c = 0;
    while (!$rsbusq->EOF) {
        $c++;
        $serial = intval($rsbusq->fields['idprod_serial']);
        $idregseriedptostk = intval($rsbusq->fields['idregseriedptostk']);
        $des = trim($rsbusq->fields['descripcion']);
        $precio = floatval($rsbusq->fields['p1']);
        $idtipoproducto = $rsbusq->fields['idtipoproducto'];

        if ($idtipoproducto == 1) { // producto
            $accion_btn = 'onClick="agregon('.$c.','.$serial.','.$precio.',\''.$usar_lote.'\','.$idregseriedptostk.');"';
            $accion_keyup = 'onkeyup="agregon_enter('.$c.','.$serial.','.$precio.',event,\''.$usar_lote.'\','.$idregseriedptostk.');"';
        } elseif ($idtipoproducto == 2) {  // combo
            $accion_btn = "onClick=\"apretar_combo('".$rsbusq->fields['idprod_serial']."');\" ";
            $accion_keyup = "onClick=\"apretar_combo('".$rsbusq->fields['idprod_serial']."');\" ";
        } elseif ($idtipoproducto == 3) {  // combinado simple
            $accion_btn = "onClick=\"apretar_pizza('".$rsbusq->fields['idprod_serial']."');\" ";
            $accion_keyup = "onClick=\"apretar_pizza('".$rsbusq->fields['idprod_serial']."');\" ";
        } elseif ($idtipoproducto == 4) {  // combinado extendido
            $accion_btn = "onClick=\"apretar_combinado('".$rsbusq->fields['idprod_serial']."');\" ";
            $accion_keyup = "onClick=\"apretar_combinado('".$rsbusq->fields['idprod_serial']."');\" ";
        } else { // por defecto producto
            $accion_btn = 'onClick="agregon('.$c.','.$serial.','.$precio.',\''.$usar_lote.'\','.$idregseriedptostk.');"';
            $accion_keyup = 'onkeyup="agregon_enter('.$c.','.$serial.','.$precio.',event,\''.$usar_lote.'\','.$idregseriedptostk.');"';
        }
        $cantidad_restante = floatval($rsbusq->fields['disponible']) - floatval($rsbusq->fields['total']);
        ?>
    <tr class="<?php if ($cantidad_restante == 0 && ($rsbusq->fields['venta_sin_stock'] != 1)) {?>hide<?php } ?>">
    <td align="center"><?php echo capitalizar($rsbusq->fields['idprod_serial'])?></td>
   	  <td><?php echo capitalizar($rsbusq->fields['descripcion'])?></td>
        <td align="center"><?php echo formatomoneda($rsbusq->fields['p1'])?></td>
		<?php if ($usar_lote == "S") { ?>
			<td align="center"><div id="lote_<?php echo $idregseriedptostk;?>" ><?php echo antixss($rsbusq->fields['lote']);?></div></td>
			<td align="center"><div id="vencimiento_<?php echo $idregseriedptostk;?>"><?php echo antixss(date("Y-m-d", strtotime($rsbusq->fields['vencimiento'])));?></div></td>
		<?php } ?>
        <?php if ($muestra_stock_ven == 'S') { ?>
        <td align="center">
			<div id="stock_cantidad_<?php
                if ($usar_lote == "S") {
                    echo $rsbusq->fields['idregseriedptostk'];
                } else {
                    echo $rsbusq->fields['idprod_serial'];
                }
            ?>" >
				<?php echo formatomoneda($rsbusq->fields['disponible'] - $rsbusq->fields['total'], 4, 'N')?>
			</div>
			<div <?php if ($rsbusq->fields['venta_sin_stock'] != 1) {?> data-hidden-value="false" <?php } else { ?> data-hidden-value="true" <?php } ?> id="hidden_stock_cantidad_<?php if ($usar_lote == "S") {
			    echo $rsbusq->fields['idregseriedptostk'];
			} else {
			    echo $rsbusq->fields['idprod_serial'];
			}  ?>" class="hide"><?php echo $rsbusq->fields['disponible'] - $rsbusq->fields['total']; ?></div></td>
        <?php } ?>
		<?php if ($balanza == 'S') {?>
		<td align="center"><?php if ($rsbusq->fields['idmedida'] == 2) {?><a href="javascript:void(0);" onClick="obtener_peso(<?php echo $c?>)" title="Pesar" id="obtp_<?php echo $c; ?>"><img src="img/89956.png" width="40" height="40" alt=""/></a><?php }?></td>
		<?php }?>
        <td align="center"><input type="text" name="cvender_<?php echo $c?>"  id="cvender_<?php echo $c?>" size="7" style="height:40px;" <?php echo $accion_keyup; ?> /></td>
      <td align="center"><input type="button" name="cv_<?php echo $c?>" id="cv_<?php echo $c?>" value="Agregar" <?php echo $accion_btn; ?> style="height:40px;"/></td>
    
    </tr>
    <?php $rsbusq->MoveNext();
    }?>
    
    </table>
<?php } else { ?>
<img src="img/alerta1.png" width="64" height="64" /><br />
<strong>ATENCION</strong>: el producto buscado no ha sido encontrado.

<?php } ?>
</div>