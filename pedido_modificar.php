 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");
$idsucursal_login = $idsucursal;

$idpedido_viejo = intval($_GET['idpedido']);
if ($idpedido_viejo == 0) {
    echo "No se recibio el codigo de pedido.";
    exit;
}

$consulta = "
select permite_cambiar_canal, permite_editar_pedido from preferencias_caja limit 1
";
$rspcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$permite_editar_pedido = $rspcaj->fields['permite_editar_pedido'];
if ($permite_editar_pedido != 'S') {
    echo "Acceso Denegado!<br />La administración desactivó el permiso para realizar esta acción.";
    exit;
}


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots

    // verifica que se pueda modificar (solo carry out y delivery)
    $consulta = "
    select *
    from tmp_ventares_cab
    where
    idtmpventares_cab = $idpedido_viejo
    ";
    $rsval = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpedido_val = intval($rsval->fields['idtmpventares_cab']);
    $idventa_val = intval($rsval->fields['idventa']);
    $idcanal_val = intval($rsval->fields['idcanal']);
    $estado_val = intval($rsval->fields['estado']);
    $fechahora = trim($rsval->fields['fechahora']);
    // valida si existe
    if ($idpedido_val == 0) {
        echo "El pedido que intentas modificar no existe.";
        exit;
    }
    // valida que no se haya facturado
    if ($idventa_val > 0) {
        echo "El pedido que intentas modificar ya fue facturado.";
        exit;
    }
    // valida que su canal sea delivery o carry out
    if ($idcanal_val != 1 && $idcanal_val != 3) {
        echo "El canal del pedido [$idcanal_val] no admite modificaciones.";
        exit;
    }
    // valida que no este anulado
    if ($estado_val != 1) {
        echo "El pedido que intentas modificar se encuentra anulado.";
        exit;
    }
    // Fecha almacenada en $fechahora (por ejemplo, '2022-10-01 10:00:00')
    $fechaAlmacenada = new DateTime($fechahora);
    // Fecha y hora actual
    $fechaActual = new DateTime();
    // Calcula la diferencia entre las dos fechas
    $intervalo = $fechaActual->diff($fechaAlmacenada);
    // Comprueba si han pasado 24 horas (86400 segundos)
    if ($intervalo->s >= 86400) {
        echo "Han pasado más de 24 horas ya no puedes modificar este pedido, anulalo y genera uno nuevo";
        exit;
    }
    // valida si existe en la tabla de carrito o  ya paso a la tabla bak
    $consulta = "
    SELECT idtmpventares_cab FROM `tmp_ventares` where idtmpventares_cab = $idpedido_viejo limit 1
    ";
    $rscar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpedido_car = intval($rscar->fields['idtmpventares_cab']);
    if ($idpedido_car == 0) {
        echo "Pedido muy antiguo, ya no puede editarse";
        exit;
    }

    // genera cabecera para pedido nuevo en base al viejo
    $consulta = "
    INSERT INTO `tmp_ventares_cab`
    (
    idpedido_ant, `razon_social`, `ruc`, `idtarjeta`, `delivery`, `idclientedel`, `iddomicilio`, `nombre_deliv`, `apellido_deliv`, 
    `direccion`, `telefono`, `llevapos`, `cambio`, `observacion_delivery`, `delivery_zona`, `delivery_costo`, `chapa`, `observacion`, `monto`, 
    `idusu`, `fechahora`, `finalizado`, `cocinado`, `retirado`, `registrado`, `fechahora_coc`, `fechahora_reg`, `idsucursal`, 
    `idempresa`, `idventa`, `idcanal`, `estado`, `anulado_por`, `anulado_el`, `anulado_idcaja`, `idmesa`, `idmesa_tmp`, `impreso`, `ultima_impresion`, 
    `tipoventa`, `clase`, `idmozo`, `iddelivery`, `url_maps`, `latitud`, `longitud`, `idatc`, `idtrans`, `idsesion`, `idestadodelivery`,
    `idmotoristaped`, `idcanalventa`, `idsolicitudcab`, `ped_registrado_el`, `idclienteped`, `idsucursal_clie_ped`, `notificado`, `idapp`,
    `idwebpedido`, `idpedidoexterno`, `idatc_anter`, `idmesa_anter`, `ideventoped`, `idterminal`, `iddarkkitchen`, `descuento_monto`, `idtarjetadelivery`
    ) 
    select 
    idtmpventares_cab, `razon_social`, `ruc`, `idtarjeta`, `delivery`, `idclientedel`, `iddomicilio`, `nombre_deliv`, `apellido_deliv`, 
    `direccion`, `telefono`, `llevapos`, `cambio`, `observacion_delivery`, `delivery_zona`, `delivery_costo`, `chapa`, `observacion`, `monto`, 
    $idusu, '$ahora' as fechahora, 'N' as `finalizado`, 'N' as `cocinado`, 'N' as `retirado`, `registrado`, `fechahora_coc`, `fechahora_reg`, `idsucursal`,  
    `idempresa`, `idventa`, `idcanal`, `estado`, `anulado_por`, `anulado_el`, `anulado_idcaja`, `idmesa`, `idmesa_tmp`, `impreso`, `ultima_impresion`,
    `tipoventa`, `clase`, `idmozo`, `iddelivery`, `url_maps`, `latitud`, `longitud`, `idatc`, `idtrans`, `idsesion`, `idestadodelivery`,
    `idmotoristaped`, `idcanalventa`, `idsolicitudcab`, `ped_registrado_el`, `idclienteped`, `idsucursal_clie_ped`, 'N' as notificado, `idapp`,
    `idwebpedido`, `idpedidoexterno`, `idatc_anter`, `idmesa_anter`, `ideventoped`, `idterminal`, `iddarkkitchen`, `descuento_monto`, `idtarjetadelivery`
    from tmp_ventares_cab
    where
    idtmpventares_cab = $idpedido_viejo
    and idventa is null
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // obtiene el id del nuevo pedido
    $consulta = "
    select idtmpventares_cab 
    from tmp_ventares_cab 
    where 
    idpedido_ant = $idpedido_viejo 
    order by idtmpventares_cab desc 
    limit 1
    ";
    $rsnew = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpedido_nuevo = $rsnew->fields['idtmpventares_cab'];



    // trae datos del pedido nuevo
    $consulta = "
    select *
    from tmp_ventares_cab
    where
    idtmpventares_cab = $idpedido_nuevo
    and idventa is null
    ";
    $rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // busca si hay algo en el carrito y si hay borra
    $consulta = "
    select * 
    from tmp_ventares 
    where
    usuario = $idusu
    and finalizado = 'N'
    and registrado = 'N'
    and idsucursal = $idsucursal_login
    and borrado = 'N'
    and idmesa = 0
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // recorre borra
    while (!$rs->EOF) {

        $idventatmp = $rs->fields['idventatmp'];
        $idtmpventaresagregado = intval($rs->fields['idtmpventaresagregado']);

        // borra los detalles que contienen ese producto
        $consulta = "
        update tmp_ventares
        set borrado = 'S'
        where
        idventatmp = $idventatmp
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // borra los agregados relacionados al idventatmp principal
        $consulta = "
        update tmp_ventares
        set 
        borrado = 'S'
        where
        idventatmp_princ_delagregado = $idventatmp
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // borra los agregados de la tabla de agregados
        $consulta = "
        delete from tmp_ventares_agregado
        where
        idventatmp = $idventatmp
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        if ($idtmpventaresagregado > 0) {
            $consulta = "
            delete from tmp_ventares_agregado
            where
            idtmpventaresagregado = $idtmpventaresagregado
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }


        $rs->MoveNext();
    }


    // carga el carrito en base al pedido viejo
    $consulta = "
    INSERT INTO `tmp_ventares`
    (
    idtmpventares_cab, `idsesion`, `idpedidocat`, `idtmpventares_tan`, `idproducto`, `idtipoproducto`,
    `cantidad`, `precio`, `precio_sindiplo`, `fechahora`, `usuario`, `idtarjeta`, `finalizado`,
    `cocinado`, `retirado`, `registrado`, `impreso_coc`, `idventa`,
    `idsucursal`, `idempresa`, `observacion`, `chapa`, `receta_cambiada`, `combinado`,
    `combo`, `idprod_mitad1`, `idprod_mitad2`, `borrado`, `borrado_mozo`, `borrado_mozo_el`,
    `borrado_mozo_por`, `borrado_mozo_idcaja`, `subtotal`, `subtotal_orig`, `subtotal_sindiplo`,
    `descuento`, `idmesa`, `cortesia`, `rechazo`, `idmotivorecha`, `tipo_plato`, `diplo`, `iva`,
    `iva_sindiplo`, `monto_iva`, `monto_iva_sindiplo`, `monto_iva_unit`,
    `monto_iva_unit_sindiplo`, `idventatmp_princ_delagregado`, `idtmpventaresagregado`,
    `idtrans`, `idlistaprecio`, `desconsolida_forzar`, `idmotivodescuento`,
    `idmotivoborra`, `idatcdet`
     )
    select 
    $idpedido_nuevo, `idsesion`, `idpedidocat`, `idtmpventares_tan`, `idproducto`, `idtipoproducto`,
    `cantidad`, `precio`, `precio_sindiplo`, `fechahora`, $idusu, `idtarjeta`, 'N' as `finalizado`,
    `cocinado`, `retirado`, 'N' as registrado, `impreso_coc`, `idventa`, 
    $idsucursal_login, `idempresa`, `observacion`, `chapa`, `receta_cambiada`, `combinado`,
    `combo`, `idprod_mitad1`, `idprod_mitad2`, `borrado`, `borrado_mozo`, `borrado_mozo_el`,
    `borrado_mozo_por`, `borrado_mozo_idcaja`, `subtotal`, `subtotal_orig`, `subtotal_sindiplo`,
    `descuento`, `idmesa`, `cortesia`, `rechazo`, `idmotivorecha`, `tipo_plato`, `diplo`, `iva`,
    `iva_sindiplo`, `monto_iva`, `monto_iva_sindiplo`, `monto_iva_unit`,
    `monto_iva_unit_sindiplo`, `idventatmp_princ_delagregado`, `idtmpventaresagregado`,
    `idtrans`, `idlistaprecio`, `desconsolida_forzar`, `idmotivodescuento`,
    `idmotivoborra`, `idatcdet`
    from tmp_ventares
    where
    idtmpventares_cab = $idpedido_viejo
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // anula pedido viejo (se anula recien al registrarse el nuevo)
    /*$consulta="
    update tmp_ventares_cab
    set
    estado = 6,
    anulado_por = $idusu,
    anulado_el = '$ahora'
    where
    idtmpventares_cab = $idpedido_viejo
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/


    // si es delivery
    if (intval($rscab->fields['idcanal']) == 3) {

        $iddomicilio = intval($rscab->fields['iddomicilio']);

        // trae datos de delivery si aplica
        $buscar = "
        Select * 
        from cliente_delivery 
        inner join cliente_delivery_dom on cliente_delivery.idclientedel=cliente_delivery_dom.idclientedel
        where 
        iddomicilio=$iddomicilio 
        limit 1
        ";
        $rscasa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $direccion = antixss($rscasa->fields['direccion']);
        $telefono = antixss($rscasa->fields['telefono']);
        $nombreclidel = antixss($rscasa->fields['nombres']).''.trim($rscasa->fields['apellidos']);
        $idzonadel = intval($rscasa->fields['idzonadel']);
        $nombre_domicilio = antixss($rscasa->fields['nombre_domicilio']);

        $consulta = "
        Select gest_zonas.idzona, descripcion, costoentrega
        from gest_zonas
        where 
        gest_zonas.estado=1
        and gest_zonas.idsucursal = $idsucursal_login
        order by descripcion asc
        limit 1
        ";
        $rszonold = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idzona_old = intval($rszonold->fields['idzona']);


    }


    // crear sesionres si aplica

    $_SESSION['canal'] = $rscab->fields['idcanal'];
    $_SESSION['idcanalventa'] = $rscab->fields['idcanalventa'];
    $_SESSION['idpedido_edita'] = $idpedido_viejo;

    // crea cookies
    setcookie("chapa_cookie", $rscab->fields['chapa'], time() + 3600, '/');
    setcookie("ruc_cookie", $rscab->fields['ruc'], time() + 3600, '/');
    setcookie("razon_social_cookie", $rscab->fields['razon_social'], time() + 3600, '/');
    setcookie("delivery_cookie", $rscab->fields['delivery'], time() + 3600, '/');
    setcookie("telefono_cookie", $rscab->fields['telefono'], time() + 3600, '/');
    setcookie("direccion_cookie", $rscab->fields['direccion'], time() + 3600, '/');
    setcookie("cambio_cookie", $rscab->fields['cambio'], time() + 3600, '/');
    setcookie("llevapos_cookie", $rscab->fields['llevapos'], time() + 3600, '/');
    setcookie("observacion_delivery_cookie", $rscab->fields['observacion_delivery'], time() + 3600, '/');
    setcookie("mesa_cookie", $rscab->fields['idmesa'], time() + 3600, '/');
    setcookie("observacion_cookie", $rscab->fields['observacion'], time() + 3600, '/');
    setcookie("mozo_cookie", $rscab->fields['mozo'], time() + 3600, '/');
    setcookie("nombres_cookie", $rscab->fields['nombre_deliv'], time() + 3600, '/');
    setcookie("apellidos_cookie", $rscab->fields['apellido_deliv'], time() + 3600, '/');
    setcookie("idclientedel_cookie", $rscab->fields['idclientedel'], time() + 3600, '/');
    setcookie("iddomicilio_cookie", $rscab->fields['iddomicilio'], time() + 3600, '/');
    setcookie("sucursaldir_cookie", $rscab->fields['idsucursal'], time() + 3600, '/');
    setcookie("idzonadel_cookie", $idzonadel, time() + 3600, '/');
    setcookie("zona_cookie", $idzona_old, time() + 3600, '/');
    setcookie("lugar_cookie", $nombre_domicilio, time() + 3600, '/');
    setcookie("dom_deliv", $rscab->fields['iddomicilio'], time() + 3600, '/');




    header("location: tablet/index.php");
    exit;

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

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
idtmpventares_cab = $idpedido_viejo
and finalizado = 'S'
and registrado = 'N'
";
//echo $consulta;
$rscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$id = intval($rscab->fields['idtmpventares_cab']);
$idventa = intval($rscab->fields['idventa']);
$idcanal_actual = intval($rscab->fields['idcanal']);
if ($idventa > 0) {
    echo "No se puede cambiar el canal de un pedido que ya fue facturado, ya que podria o deberia contener costos asociados como el delivery.";
    exit;
}

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
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Reemplazar Pedido #<?php echo $idpedido_viejo; ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

              
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>

<h3>Pedido: <?php echo $idpedido_viejo; ?></h3>
Se eliminara el pedido anterior y se reemplazara por uno nuevo para que puedas editarlo.
<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <tbody>
    <tr>
      <td><strong>Razon Social:</strong></td>
      <td><?php echo $rscab->fields['razon_social']; ?> </td>
    </tr>
    <tr>
      <td><strong>RUC:</strong></td>
      <td><?php echo $rscab->fields['ruc']; ?> </td>
    </tr>
    <tr>
      <td><strong>Nombre:</strong></td>
      <td><?php
            $idcanal = $rscab->fields['idcanal'];
if ($idcanal == 1) { // carry out
    echo antixss($rscab->fields['chapa']);

} elseif ($idcanal == 3) { // delivery
    echo antixss($rscab->fields['nombre_deliv']).' '.antixss($rscab->fields['apellido_deliv']);
} else {
    echo antixss($rscab->fields['chapa']);
}
?></td>
    </tr>
    <tr>
      <td><strong>Operador:</strong></td>
      <td><?php echo $rscab->fields['operador']; ?></td>
    </tr>
  </tbody>
</table>
</div>
<hr />
<p align="center"><strong>Esta seguro que deseas editar este pedido ?</strong></p>
<form id="form1" name="form1" method="post" action="">
<div class="clearfix"></div>
<br />
<div class="alert alert-warning alert-dismissible fade in" role="alert" style="color:#000;">
<strong>CUIDADO! </strong><br /> 
- Esta accion no se puede deshacer. <br />
- Se <strong>Reemplazara</strong> por un nuevo pedido.<br />
- Debes avisar a cocina que no prepare los productos del pedido anterior.
</div>






<div class="clearfix"></div>
<br /><br />


    <div class="form-group">
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> SI, CAMBIAR</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='central_pedidos.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<br /><br />




<br /><br />

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
