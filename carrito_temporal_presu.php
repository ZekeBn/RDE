 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

//print_r($_POST);

$idprod_serial = intval($_POST['idprodserial']);
$cantidad = floatval($_POST['cantidad']);
if ($cantidad == 0) {
    $cantidad = 1;
}
//Traemos el precio para la sucursal en curso
$buscar = "Select * from productos_sucursales where idproducto=$idprod_serial and idsucursal=$idsucursal and activo_suc=1";
$gr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$precio = floatval($gr->fields['precio']);
//echo $precio;exit;
$subtotal = $precio * $cantidad;

$buscar = "Select * from tmp_carrito_presu where idproducto=$idprod_serial and estado=1 and registrado_por=$idusu ";
$tgh = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

if ($tgh->fields['idproducto'] > 0) {
    $update = "update tmp_carrito_presu set cantidad=cantidad+$cantidad,subtotal=subtotal+$subtotal where idproducto=$idprod_serial
    and estado=1 and registrado_por=$idusu";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

} else {
    $insertar = "Insert into tmp_carrito_presu
    (idproducto,cantidad,subtotal,registrado_por,estado)
    values
    ($idprod_serial,$cantidad,$subtotal,$idusu,1)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

}

$buscar = "Select descripcion,cantidad,subtotal,pkl,productos.idprod_serial,(select precio from productos_sucursales where idproducto=tmp_carrito_presu.idproducto and idsucursal=$idsucursal) as precio
 from productos inner join 
tmp_carrito_presu on tmp_carrito_presu.idproducto=productos.idprod_serial
where tmp_carrito_presu.registrado_por=$idusu order by descripcion asc ";
$th1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tr = $th1->RecordCount();

if ($tr > 0) {
    ?>

<table class="table table-hover">
    <thead>
        <tr>
            <th>Id producto</th>
            <th>Descripcion</th>
            <th>Precio</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
            <th>Observaciones</th>
         </tr>
        </thead>
        <tbody>
        <?php while (!$th1->EOF) { ?>
        <tr>
            <th scope="row"><?php echo $th1->fields['idprod_serial'] ?></th>
            <td><?php echo $th1->fields['descripcion'] ?></td>
            <td align="center"><?php echo formatomoneda($th1->fields['precio'], 4, 'N'); ?></td>
            <td align="center"><?php echo formatomoneda($th1->fields['cantidad'], 4, 'N'); ?></td>
            <td align="right"><?php echo formatomoneda($th1->fields['subtotal'], 4, 'N'); ?></td>
            <td></td>
        </tr>
        <?php $th1->MoveNext();
        } ?>
        <tr>
            <td align="center" colspan="6"><button type="button" class="btn btn-primary" onclick="terminar();">Finalizar</button></td>
        </tr>
    </tbody>
</table>
<?php } ?>








