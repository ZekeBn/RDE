 <?php
/*------------------------------------------
Formulario de busqueda para clientes
UR:08/01/2021
URN:29/04/2022->Se agrega la posibilidad de cargar una plantilla de productos
06/05/202: Se corrige update de cantidades y se mejora busqueda de productos

--------------------------------------------
*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");
//print_r($_POST);

if (isset($_POST['cual'])) {
    //print_r($_POST);exit;
    $idtmp = intval($_POST['idprodserial']);
    $cantidad = floatval($_POST['cantidad']);
    $idunicomodificando = intval($_POST['idtemporal']);//id unico en tmpventares
    $cual = intval($_POST['cual']);
    if ($cual == 1) {
        //es una actualizacion, buscamos el mayor id, para borrar los menores
        $buscar = "Select max(idventatmp) as mayor from tmp_ventares where usuario=$idusu and borrado='N' and finalizado='N'
        and idproducto=$idtmp order by idventatmp desc limit 1";
        $rsmay = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idm = intval($rsmay->fields['mayor']);//almacenamos el mayor id encontrado
        if ($idm > 0) {
            $update = "update tmp_ventares set borrado='S',borrado_mozo_el=current_timestamp,observacion='BORRADO AUTOMATICO POR CAMBIO DE PRODUCTO CATERING' where idproducto=$idtmp
            and idventatmp < $idm and  usuario=$idusu and finalizado='N' and borrado='N' ";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
        }
        $update = "update tmp_ventares set cantidad=$cantidad,subtotal=(precio*cantidad) where idproducto=$idtmp and  idventatmp = $idm";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    }
    if ($cual == 2) {
        //Borrar
        $update = "update tmp_ventares set borrado='S',borrado_mozo_el=current_timestamp,observacion='BORRADO EN PEDIDO CATERING' where idproducto=$idtmp and  usuario=$idusu and finalizado='N' and borrado='N' ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));


    }

}


$consulta = "
select  idventatmp,tmp_ventares.idproducto as idprod_serial,productos.descripcion, sum(cantidad) as cantidad, sum(precio) as totalprecio, sum(subtotal) as subtotal,
(select precio from productos_sucursales where idsucursal=$idsucursal and idproducto=tmp_ventares.idproducto)as precioventa,
(select recetas_detalles.idreceta from recetas_detalles where recetas_detalles.idprod = tmp_ventares.idproducto limit 1) as tienereceta, 
(select agregado.idproducto from agregado WHERE agregado.idproducto = tmp_ventares.idproducto limit 1) as tieneagregado
from tmp_ventares 
inner join productos on tmp_ventares.idproducto = productos.idprod_serial
where 
registrado = 'N'
and tmp_ventares.usuario = $idusu
and tmp_ventares.borrado = 'N'
and tmp_ventares.finalizado = 'N'
and tmp_ventares.idsucursal = $idsucursal
and tmp_ventares.idempresa = $idempresa

group by descripcion,  idproducto, receta_cambiada
";
//echo $consulta;exit;
$rsa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$tr = $rsa->RecordCount();


?>

<div class="col-md-12 col-sm-12 col-xs-12">
    <div class="x_panel">
      <div class="x_title">
        <h2>Productos | <a href="javascript:void(0);" onclick="buscarprod()"><span class="fa fa-search"></span>[BUSCAR] </a> </h2>
        <ul class="nav navbar-right panel_toolbox collapsed">
          <li><a class="collapse-link "  ><i class="fa fa-chevron-up"></i></a>
          </li>
        </ul>
        <div class="clearfix"></div>
      </div>
      <div class="x_content">
      <?php if ($tr > 0) { ?>
        <table class="table table-bordered">
        
            <thead>
            <tr>
                <th>Id Producto</th>
                <th>Descripcion</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
            </thead>
            <tbody>
            <?php while (!$rsa->EOF) {
                $ipd = trim($rsa->fields['idprod_serial']);
                $idunicotmp = intval($rsa->fields['idventatmp']);
                ?> 
                <tr>
                    <td align="right"><?php echo formatomoneda($ipd, 4, 'N'); ?></td>
                    <td><?php echo $rsa->fields['descripcion']; ?></td>
                    <td><input type="text" name="cantidad_<?php echo $idunicotmp; ?>" id="cantidad_<?php echo $idunicotmp; ?>" value="<?php echo floatval($rsa->fields['cantidad']); ?>" onkeypress="actualizar(<?php echo $idunicotmp; ?>,<?php echo $ipd; ?>,1,event)";   /></td>
                    <td><input readonly type="text" name="precio_<?php echo $idunicotmp; ?>" id="precio_<?php echo $idunicotmp; ?>" value="<?php echo floatval($rsa->fields['precioventa']); ?>"/></td>
                    <td><input type="text" name="subt_<?php echo $idunicotmp; ?>" id="subt_<?php echo $idunicotmp; ?>" value="<?php echo floatval($rsa->fields['subtotal']); ?>" readonly  /></td>
                    <td><a href="javascript:void(0);" onclick="eliminar(<?php echo $ipd?>,2);"><span class="fa fa-trash"></span>&nbsp;&nbsp; <?php echo $rsa->fields['idproducto']; ?></td>
                </tr>
            <?php $rsa->MoveNext();
            } ?> 
            </tbody>
        </table>
      <?php } else { ?>
      <table class="table table-bordered">
        
            <thead>
            <tr>
                <th>Id Producto</th>
                <th>Descripcion</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                <th><a href="javascript:void(0);" onclick="buscarprod()"><span class="fa fa-search"></span>&nbsp;&nbsp; </a></th>
                <th><input type="text" name="desc" disabled /></th>
                <th><input type="text" name="cantidad"  disabled /></th>
                <th><input type="text" name="subtotal"  disabled /></th>
            </tr>
            
            </tbody>
        </table>
       <?php }  ?>
      </div>
    </div>
</div>
