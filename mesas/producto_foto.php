 <?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "216";
require_once("../includes/rsusuario.php");

$idproducto = intval($_POST['idproducto']);

// consulta a la tabla
$idprefmesa = 1;
$consulta = "
select muestra_foto, tipo_foto, muestra_stock
from mesas_preferencias 
where 
idprefmesa = $idprefmesa
and idestadopref = 1
limit 1
";
$rsprefmes = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$muestra_foto = trim($rsprefmes->fields['muestra_foto']);
$tipo_foto = trim($rsprefmes->fields['tipo_foto']);
$muestra_stock = trim($rsprefmes->fields['muestra_stock']);

$consulta = "
Select productos.*, categorias.nombre as categoria, sub_categorias.descripcion as subcategoria,
(
SELECT idprod
FROM recetas_detalles
inner join ingredientes on recetas_detalles.ingrediente = ingredientes.idingrediente
inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo
inner join medidas on medidas.id_medida = insumos_lista.idmedida
where
recetas_detalles.idprod = productos.idprod_serial
and coalesce(insumos_lista.idproducto,0) <> productos.idprod_serial
limit 1
) as receta,
(select idproducto from agregado where agregado.idproducto = productos.idprod_serial limit 1) as agregado,
(select idproducto from producto_impresora where producto_impresora.idproducto = productos.idprod_serial limit 1) as tieneimpre,
(select tipoproducto from productos_tipo where productos.idtipoproducto = productos_tipo.idtipoproducto) as tipoproducto,
(select productos_sucursales.precio as p1 from productos_sucursales where productos_sucursales.idproducto = productos.idprod_serial and activo_suc = 1 and productos_sucursales.idsucursal = 1 order by productos_sucursales.idsucursal asc limit 1) as p1,
(select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo
from productos 
inner join categorias on productos.idcategoria = categorias.id_categoria 
inner join sub_categorias on productos.idsubcate = sub_categorias.idsubcate 
where 
productos.borrado = 'N'
and productos.idprod_serial = $idproducto
limit 1
"    ;
//echo $consulta;
$prod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idproducto = intval($prod->fields['idprod_serial']);

$img = "../gfx/productos/prod_0.jpg"; //  por defecto
if ($muestra_foto == 'S') {
    // foto cajero
    if ($tipo_foto == 1) {
        // foto productos
        $img = "../gfx/productos/prod_".$idproducto.".jpg";
        if (!file_exists($img)) {
            $img = "../gfx/productos/prod_0.jpg";
        }
    }
    // foto cliente
    if ($tipo_foto == 2) {
        // foto ecom
        $img = "../ecom/gfx/fotosweb/wprod_".$idproducto.".jpg";
        // si no tiene  foto ecom
        if (!file_exists($img)) {
            $img = "../ecom/gfx/fotosweb/wprod_0.jpg";
        }
    }
    // la mas grande (automatica)
    if ($tipo_foto == 3) {
        // foto ecom
        $img = "../ecom/gfx/fotosweb/wprod_".$idproducto.".jpg";
        // si no tiene  foto ecom
        if (!file_exists($img)) {
            // foto productos
            $img = "../gfx/productos/prod_".$idproducto.".jpg";
            if (!file_exists($img)) {
                $img = "../gfx/productos/prod_0.jpg";
            }
        }
    }
}



?>
<div style="text-align:center;">
    <img src="<?php echo $img; ?>" style="max-height:400px;" /><hr />
    <?php echo trim($prod->fields['descripcion']) ?> [P:<?php echo intval($prod->fields['idprod_serial']) ?>] [A:<?php echo intval($prod->fields['idinsumo']) ?>]
<?php if ($muestra_stock == 'S') { ?>
    <hr />
    <?php
    $consulta = "
    select gest_depositos.descripcion, gest_depositos_stock_gral.disponible, sucursales.nombre
    from gest_depositos_stock_gral
    inner join gest_depositos on gest_depositos.iddeposito = gest_depositos_stock_gral.iddeposito
    inner join sucursales on gest_depositos.idsucursal = sucursales.idsucu
    where
    gest_depositos_stock_gral.idproducto = $idproducto
    and gest_depositos.idsucursal = $idsucursal
    order by gest_depositos_stock_gral.disponible desc, sucursales.nombre asc, gest_depositos.descripcion asc
    ";
    $rsprecio = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
    <div class="table-responsive">
        <table width="100%" class="table table-bordered jambo_table bulk_action">
            <thead>
                <tr>
                <th>Stock</th>
                <th>Deposito</th>
                <th>Sucursal</th>
                </tr>
            </thead>
            <tbody>
            <?php while (!$rsprecio->EOF) { ?>
                <tr>
                    <td align="right"><?php echo formatomoneda($rsprecio->fields['disponible'], 4, 'N'); ?></td>
                    <td height="27" align="left"><?php echo antixss($rsprecio->fields['descripcion']); ?></td>
                    <td height="27" align="left"><?php echo antixss($rsprecio->fields['nombre']); ?></td>
                </tr>
            <?php $rsprecio->MoveNext();
            } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } // if($muestra_stock == 'S'){?>
<div class="clearfix"></div>
<br />
