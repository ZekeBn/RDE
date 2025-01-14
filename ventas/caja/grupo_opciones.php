<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");
//print_r($_POST);
/*
si el grupo tiene 1 solo producto cargado
debe hacer un insert directo y no figurar en la lista
si la cantidad es 6 y solo hay 1 producto cargado hace 6 insert
*/

$prodprinc = intval($_POST['id']);

// busca producto principal para ver si pertenece a la empresa
$consulta = "
select * 
from productos 
where 
idtipoproducto = 13
and idprod_serial = $prodprinc
and productos.borrado = 'N'
order by 
descripcion asc
limit 1
";
$rsprod2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$cat_princ = $rsprod2->fields['idcategoria'];
if (intval($rsprod2->fields['idprod_serial']) == 0) {
    echo "Producto principal inexistente!";
    exit;
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
$seladd_lp = " productos_sucursales.precio ";
if ($idlistaprecio > 0) {
    $joinadd_lp = " inner join productos_listaprecios on productos_listaprecios.idproducto = productos.idprod_serial ";
    $whereadd_lp = "
	and productos_listaprecios.idsucursal = $idsucursal 
	and productos_listaprecios.idlistaprecio = $idlistaprecio 
	and productos_listaprecios.estado = 1
	";
    $seladd_lp = " productos_listaprecios.precio ";
}

$consulta = "
SELECT agregado.idproducto as idprod_serial, agregado.idingrediente, agregado.alias, 
	COALESCE(
	(
		select $seladd_lp as precio
		from productos 
		inner join productos_sucursales on productos_sucursales.idproducto = productos.idprod_serial
		$joinadd_lp
		where
		productos.idprod_serial is not null
		and productos.borrado = 'N'
		and productos_sucursales.idsucursal = $idsucursal
		and productos.idprod_serial = agregado.idprod_intercambio
		$whereadd_lp
		order by productos.descripcion asc
	),0) as precio_adicional,
insumos_lista.descripcion, agregado.cantidad, medidas.nombre, productos.idprod_serial, agregado.idprod_intercambio
FROM agregado 
inner join ingredientes on ingredientes.idingrediente = agregado.idingrediente
inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
inner join medidas on insumos_lista.idmedida=medidas.id_medida
inner join productos on productos.idprod_serial = insumos_lista.idproducto
WHERE
agregado.idproducto = $prodprinc
and insumos_lista.estado = 'A'
";
//echo  $consulta;
$rsprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<div style="height:15px; font-size:15px; margin:5px;"><strong>Seleccione la Opcion: </strong></div><br />
<div id="grupo_<?php echo $idprod_princ; ?>">
<?php
//$col=intval(ceil($totalprod/$totalcol));
$x = 1;

while (!$rsprod->EOF) {

    $id_campo = $rsprod->fields['idprod_serial'];

    $img = "../../gfx/productos/prod_".$rsprod->fields['idprod_serial'].".jpg";
    //echo $img;
    if (!file_exists($img)) {
        $img = "../../gfx/productos/prod_0.jpg";
    }

    //apretar('".$rsprod->fields['idprod_serial']."',0,0);

    if ($_POST['metodo'] == 1) {
        $accionbtn = 'carro_add_grupo('.$rsprod->fields['idprod_intercambio'].')';
    } else {
        $accionbtn = 'carro_add_grupo_lista('.$rsprod->fields['idprod_intercambio'].','.intval($_POST['id_linea']).')';
    }



    ?><div id="prod_<?php echo $id_campo; ?>" class="producto col-md-4 col-sm-4 col-xs-12" style="cursor: pointer;" onMouseUp="<?php echo $accionbtn; ?>">
    <?php if (trim($rsprod->fields['descripcion']) != '') { ?>
    <img src="<?php echo $img ?>" height="81" width="163"  border="0" alt="<?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>" title="<?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?>" />
    <br /><?php echo Capitalizar(trim($rsprod->fields['descripcion'])); ?> [<?php echo $rsprod->fields['idprod_intercambio'] ?>]
	
    <br />
	<?php echo formatomoneda(trim($rsprod->fields['precio_adicional'])); ?>
	<br /><br />
	<?php } ?>
</div>
<?php $x++;
    $rsprod->MoveNext();
}
?>

<div class="clearfix"></div><br />
</div>
</div>