 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");

require_once("includes/funciones_carrito.php");

$idpedido = intval($_REQUEST['idpedido']);
if ($idpedido == 0) {
    echo "Pedido inexistente o anulado.";
    exit;
}

$consulta = "
select permite_cambiar_canal, permite_editar_pedido from preferencias_caja limit 1
";
$rspcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$permite_cambiar_canal = $rspcaj->fields['permite_cambiar_canal'];
$permite_editar_pedido = $rspcaj->fields['permite_editar_pedido'];


//$parametros_array=array("estado_pedido" => 'R', "idpedido" => 1361);
$parametros_array = ["estado_pedido" => 'R', "idpedido" => $idpedido];
$carrito_detalles = carrito_muestra($parametros_array);
//print_r($carrito_detalles); exit;
/*
$parametros_array=array("estado_pedido" => 'C', "idusu" => $idusu, "idsucursal" => $idsucursal);
$carrito_detalles=carrito_muestra($parametros_array);
*/
//print_r($carrito_detalles); exit;

// productos normales sin agregados ni sacados
$buscar = "
Select productos.descripcion, tmp_ventares.combinado, idprod_mitad1,idprod_mitad2, tmp_ventares.precio,
idtmpventares_cab, tmp_ventares.cantidad, idventatmp,idproducto, tmp_ventares.subtotal,
(select tmp_ventares_cab.delivery_costo from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery_costo,
(select tmp_ventares_cab.delivery from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as delivery,
(select tmp_ventares_cab.idusu from tmp_ventares_cab where tmp_ventares_cab.idtmpventares_cab = tmp_ventares.idtmpventares_cab) as idusu
 from tmp_ventares
inner join productos on productos.idprod=tmp_ventares.idproducto
where 
idtmpventares_cab=$idpedido
and tmp_ventares.borrado = 'N'
and tmp_ventares.borrado_mozo = 'N'



order by descripcion asc
";
$rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idpedido = intval($rsbb->fields['idtmpventares_cab']);
if ($idpedido == 0) {
    echo "Pedido inexistente o anulado.";
    exit;
}

// marcar como notificado
$consulta = "
update tmp_ventares_cab set notificado = 'S' where idtmpventares_cab = $idpedido
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// cabecera
$consulta = "
select *,
(select usuario from usuarios  where idusu = tmp_ventares_cab.idusu) as operador,
(select app from app where idapp = tmp_ventares_cab.idapp) as app,
(select canal from canal where idcanal = tmp_ventares_cab.idcanal) as canal,
(
Select cliente_delivery_dom.referencia
from cliente_delivery_dom
where  
cliente_delivery_dom.iddomicilio=tmp_ventares_cab.iddomicilio
limit 1    
) as referencia
from tmp_ventares_cab
where
idtmpventares_cab = $idpedido
and finalizado = 'S'
and registrado = 'N'
";
//echo $consulta;
$rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id = intval($rscab->fields['idtmpventares_cab']);
$idcanal = intval($rscab->fields['idcanal']);


$mostrar_boton_cambio_canal = "N";
if ($permite_cambiar_canal == 'S') {
    // carry out
    if ($idcanal == 1) {
        $href = "cambiar_canal_pedido_carry_to_delivery.php?id=".$idpedido;
        $onclick = '';
        //$href="javascript:void(0);";
        //$onclick='onclick="alert(\'Canal no permite cambios.\');"';
        $mostrar_boton_cambio_canal = "S";
        // delivery a carry out
    } elseif ($idcanal == 3) {
        $href = "cambiar_canal_pedido_delivery_to_carry.php?id=".$idpedido;
        $onclick = '';
        $mostrar_boton_cambio_canal = "S";
    } else {
        $href = "javascript:void(0);";
        $onclick = 'onclick="alert(\'Canal no permite cambios.\');"';
        $mostrar_boton_cambio_canal = "N";
    }
} else {
    $href = "javascript:void(0);";
    $onclick = 'onclick="acceso_denegado_cambiocanal();"';
    $mostrar_boton_cambio_canal = "S";
}
if ($permite_editar_pedido == 'S') {
    $href_edit = "pedido_modificar.php?idpedido=".$idpedido;
    $onclick_edit = '';
} else {
    $href_edit = "javascript:void(0);";
    $onclick_edit = 'onclick="acceso_denegado_editar();"';
}

?>
Pedido: <?php echo $idpedido; ?>

&nbsp;&nbsp;&nbsp;
<a href="javascript:reimpimir_comp(<?php echo $idpedido; ?>);void(0);" class="btn btn-sm btn-default" title="Imprimir Ticket" data-toggle="tooltip" data-placement="right"  data-original-title="Imprimir Ticket"><span class="fa fa-print"></span> Imprimir</a>

<div id="reimprimebox"></div>
<hr />
<?php if (trim($rscab->fields['app']) != '') { ?>
<h3>APP: <span style="color:red;"><?php echo $rscab->fields['app']; ?><span></h3><hr />
<?php } ?>
<div class="col-md-6 col-sm-6 form-group">

    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="password" name="codigo_borra_<?php echo $idpedido; ?>" id="codigo_borra_<?php echo $idpedido; ?>" value="" placeholder="Codigo de Autorizacion" class="form-control" required="required" autofocus="autofocus" />                    
    </div>
</div>
<div class="clearfix"></div>

<div class="table-responsive">
<table width="100%" class="table table-bordered jambo_table bulk_action" border="1">
    <thead>
        <tr>
            <th ></th>
            <th >Producto</th>
            <th >Cantidad</th>
            <th >P.U.</th>
            <th >Subtotal</th>

        </tr>
    </thead>
    <tbody>
<?php foreach ($carrito_detalles as $carrito_detalle) {


    if ($carrito_detalle['idventatmp'] > 0) {
        $tipo_borra = 'onClick="borrar_item('.$carrito_detalle['idventatmp'].','.$idpedido.',\''.Capitalizar(str_replace("'", "", $carrito_detalle['descripcion'])).'\');"';

    } else {
        $tipo_borra = 'onClick="borrar('.$carrito_detalle['idproducto'].','.$idpedido.',\''.Capitalizar(str_replace("'", "", $carrito_detalle['descripcion'])).'\');"';

    }

    if ($carrito_detalle['idtipoproducto'] != 5) {
        ?>
        <tr>
            <td>
                
                <div class="btn-group">
                    <a href="javascript:void(0);" <?php echo $tipo_borra; ?> class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </div>

            </td>
            <td align="left"><?php echo  $carrito_detalle['descripcion'];
        if (trim($carrito_detalle['observacion']) != '') {
            echo  "<br />&nbsp;&nbsp;( ! ) OBS: ".$carrito_detalle['observacion'];
        }


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
            echo "<br />&nbsp;&nbsp;&nbsp;(+) ".trim($carrito_agregado['alias'], 36)."<br />";
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








        ?></td>
            <td align="right"><?php echo  formatomoneda($carrito_detalle['cantidad'], 4, 'N'); ?></td>
            <td align="right"><?php echo  formatomoneda($carrito_detalle['precio_unitario'], 2, 'N'); ?></td>
            <td align="right"><?php echo  formatomoneda($carrito_detalle['subtotal_con_extras'], 2, 'N'); ?></td>

        </tr>
<?php } // if($carrito_detalle['idtipoproducto'] != 5){?>
<?php } ?>
    </tbody>
</table>
</div>
<a href="<?php echo $href_edit; ?>" <?php echo $onclick_edit; ?> class="btn btn-sm btn-default"><span class="fa fa-edit"></span> Modificar Pedido</a>
<?php
//$rsbb->MoveFirst();




if ($rsbb->fields['delivery'] == 'S') {

    ?><hr />
<strong>Delivery:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <tbody>
    <tr>
      <td><strong>Canal:</strong></td>
      <td><?php echo $rscab->fields['canal']; ?> <?php if ($mostrar_boton_cambio_canal == "S") { ?><a href="<?php echo $href; ?>" <?php echo $onclick; ?>  class="btn btn-sm btn-default"><span class="fa fa-edit"></span> Cambiar</a><?php } ?></td>
    </tr>
      <tr>
        <td><strong>Telefono:</strong></td>
        <td>0<?php echo $rscab->fields['telefono']; ?></td>
      </tr>
      <tr>
        <td><strong>Llevar POS</strong></td>
        <td><?php echo siono($rscab->fields['llevapos']); ?></td>
      </tr>
      <tr>
        <td><strong>Razon Social</strong></td>
        <td><?php echo $rscab->fields['razon_social']; ?></td>
      </tr>
      <tr>
        <td><strong>Ruc:</strong></td>
        <td><?php echo $rscab->fields['ruc']; ?></td>
      </tr>
      <tr>
        <td><strong>Direccion:</strong></td>
        <td><?php echo $rscab->fields['direccion']; ?></td>
      </tr>
      <tr>
        <td><strong>Referencia:</strong></td>
        <td><?php echo $rscab->fields['referencia']; ?></td>
      </tr>
      <tr>
        <td><strong>Observacion Delivery:</strong></td>
        <td><?php echo $rscab->fields['observacion_delivery']; ?></td>
      </tr>
      <tr>
        <td><strong>Operador:</strong></td>
        <td><?php echo $rscab->fields['operador']; ?></td>
      </tr>
      <tr>
        <td><strong>Observacion Operador:</strong></td>
        <td><?php echo $rscab->fields['observacion']; ?></td>
      </tr>
    </tbody>
  </table>
</div>
  <br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <tbody>
      <tr>
        <td><strong>Total Venta:</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['monto']); ?></td>
      </tr>

    </tbody>
  </table>
</div>
  <br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <tbody>
      <tr>
        <td><strong>Paga con (Cambio):</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['cambio']); ?></td>
      </tr>
      <tr>
        <td><strong>Vuelto:</strong></td>
        <td align="right"><?php echo formatomoneda($rscab->fields['cambio'] - ($rscab->fields['monto'])); ?></td>
      </tr>
    </tbody>
  </table>
</div>
<?php } else {
    $idmesa = intval($rscab->fields['idmesa']);
    if ($idmesa > 0) {
        $consulta = "
    SELECT mesas.idmesa, mesas.numero_mesa, salon.nombre
    FROM mesas
    inner join salon on mesas.idsalon = salon.idsalon
    WHERE
    mesas.idmesa = $idmesa
    and salon.idsucursal = $idsucursal
    order by salon.nombre asc, mesas.numero_mesa asc
    ";
        $rsmesa = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    ?>
 <br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <tbody>
<?php



    ?> 

    <tr>
      <td><strong>Canal:</strong></td>
      <td><?php echo $rscab->fields['canal']; ?> <?php if ($mostrar_boton_cambio_canal == "S") { ?><a href="<?php echo $href; ?>" <?php echo $onclick; ?>  class="btn btn-sm btn-default"><span class="fa fa-edit"></span> Cambiar</a><?php } ?></td>
    </tr>
    <tr>
      <td><strong>Operador:</strong></td>
      <td><?php echo $rscab->fields['operador']; ?></td>
    </tr>
<?php if ($idmesa > 0) { ?>
    <tr>
      <td><strong>Mesa:</strong></td>
      <td><?php echo $rsmesa->fields['numero_mesa']; ?></td>
    </tr>
    <tr>
      <td><strong>Salon:</strong></td>
      <td><?php echo $rsmesa->fields['nombre']; ?></td>
    </tr>
<?php } ?>
<?php if ($rscab->fields['chapa'] != '') { ?>
    <tr>
      <td><strong>Nombre</strong></td>
      <td><?php echo $rscab->fields['chapa']; ?></td>
    </tr>
<?php } ?>
    <tr>
      <td><strong>Telefono</strong></td>
      <td>0<?php echo $rscab->fields['telefono']; ?></td>
    </tr>
    <tr>
      <td><strong>Observacion Operador:</strong></td>
      <td><?php echo $rscab->fields['observacion']; ?></td>
    </tr>
  </tbody>
</table>
</div>
<?php } ?>
<hr />

<br /><br /><br />

<br />
