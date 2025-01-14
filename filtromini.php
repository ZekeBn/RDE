 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$agregawhere = '';
$describe = antisqlinyeccion($_POST['bb'], 'text');
$mini = str_replace("'", "", $describe);
$palabra = $mini;
// separar palabras
$nomape = trim($palabra);
$nombre = $nomape;
if (trim($nombre) != '') {
    $nombres = explode(' ', $nombre);
    foreach ($nombres as $nombre) {
        $agregawhere .= " and  ( productos.descripcion LIKE _utf8 '%$nombre%' COLLATE utf8_general_ci )
";
    }
}


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
$iddarkkitchen = intval($_SESSION['iddarkkitchen']);
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
if ($iddarkkitchen > 0) {
    $consulta = "
    select iddarkkitchen, nombre_marca, idlistaprecio
    from dark_kitchen 
    where 
    iddarkkitchen = $iddarkkitchen 
    and estado = 1
    limit 1
    ";
    $rsdk = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idlistaprecio = intval($rsdk->fields['idlistaprecio']);
    $whereadd_dk = "
    and productos_sucursales.idproducto in (select idproducto from productos_darkkitchen where iddarkkitchen = $iddarkkitchen)
    ";
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

$len = strlen($mini);
$buscar = "
Select *, $seladd_lp,
                (
                select sum(gest_depositos_stock_gral.disponible)
                from insumos_lista
                inner join gest_depositos_stock_gral on insumos_lista.idinsumo = gest_depositos_stock_gral.idproducto
                where 
                productos.idprod_serial = insumos_lista.idproducto
                and insumos_lista.hab_invent = 1
                and gest_depositos_stock_gral.iddeposito = (
                                                            select gest_depositos.iddeposito 
                                                            from gest_depositos 
                                                            where 
                                                            tiposala = 2 
                                                            and idsucursal = $idsucursal
                                                            )
                ) as disponible
from productos 
inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
$joinadd_lp
where 
productos.borrado = 'N'
$agregawhere

and productos_sucursales.idsucursal = $idsucursal 
and productos_sucursales.activo_suc = 1

$whereadd_lp
$whereadd_dk

order by 
CASE WHEN
    substring(productos.descripcion from 1 for $len) = '$mini'
THEN
    0
ELSE
    1
END asc, 
productos.descripcion asc 
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
        $des = trim($rsbusq->fields['descripcion']);
        $precio = floatval($rsbusq->fields['p1']);
        $idtipoproducto = $rsbusq->fields['idtipoproducto'];

        if ($idtipoproducto == 1) { // producto
            $accion_btn = 'onClick="agregon('.$c.','.$serial.','.$precio.');"';
            $accion_keyup = 'onkeyup="agregon_enter('.$c.','.$serial.','.$precio.',event);"';
        } elseif ($idtipoproducto == 2) {  // combo
            $accion_btn = "onClick=\"apretar_combo('".$rsbusq->fields['idprod_serial']."');\" ";
            $accion_keyup = "onClick=\"apretar_combo('".$rsbusq->fields['idprod_serial']."');\" ";
        } elseif ($idtipoproducto == 3) {  // combinado simple
            $accion_btn = "onClick=\"apretar_pizza('".$rsbusq->fields['idprod_serial']."');\" ";
            $accion_keyup = "onClick=\"apretar_pizza('".$rsbusq->fields['idprod_serial']."');\" ";
        } elseif ($idtipoproducto == 4) {  // combinado extendido
            $accion_btn = "onClick=\"apretar_combinado('".$rsbusq->fields['idprod_serial']."');\" ";
            $accion_keyup = "onClick=\"apretar_combinado('".$rsbusq->fields['idprod_serial']."');\" ";
        } elseif ($idtipoproducto == 13) {  // grupo opciones
            $accion_btn = "onClick=\"grupo_opciones('".$rsbusq->fields['idprod_serial']."',2,".$c.");\" ";
            $accion_keyup = "onClick=\"grupo_opciones('".$rsbusq->fields['idprod_serial']."',2,".$c.");\" ";
        } else { // por defecto producto
            $accion_btn = 'onClick="agregon('.$c.','.$serial.','.$precio.');"';
            $accion_keyup = 'onkeyup="agregon_enter('.$c.','.$serial.','.$precio.',event);"';
        }

        ?>
    <tr>
    <td align="center"><?php echo capitalizar($rsbusq->fields['idprod_serial'])?></td>
         <td><?php echo capitalizar($rsbusq->fields['descripcion'])?></td>
        <td align="center"><?php echo formatomoneda($rsbusq->fields['p1'])?></td>
        <?php if ($muestra_stock_ven == 'S') { ?>
        <td align="center"><?php echo formatomoneda($rsbusq->fields['disponible'], 4, 'N')?></td>
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
