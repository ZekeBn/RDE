<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "212";
require_once("includes/rsusuario.php");

require_once("app_rider/funciones_riders.php");


//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>" 	;
    exit;
}


$idventa = intval($_GET['id']);


// preferencias caja
$consulta = "
SELECT 
usa_motorista, obliga_motorista, valida_duplic_tipo,
 obliga_cod_transfer
FROM preferencias_caja 
WHERE  
idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usa_motorista = trim($rsprefcaj->fields['usa_motorista']);
$obliga_motorista = trim($rsprefcaj->fields['obliga_motorista']);
$valida_duplic_tipo = trim($rsprefcaj->fields['valida_duplic_tipo']);
$obliga_cod_transfer = trim($rsprefcaj->fields['obliga_cod_transfer']);


$consulta = "
select *, 
(select nombres from cliente_delivery where idclientedel = ventas.idclientedel) as nombres,
(select apellidos from cliente_delivery where idclientedel = ventas.idclientedel) as apellidos,
(select telefono from cliente_delivery where idclientedel = ventas.idclientedel) as telefono,
(select direccion from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as direccion,
(select idestadodelivery from tmp_ventares_cab where tmp_ventares_cab.idventa = ventas.idventa) as idestadodelivery
from ventas 
where 
idcanal = 3 
and idcaja = $idcaja
and idventa = $idventa
order by fecha desc
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idestadodelivery = $rs->fields['idestadodelivery'];
$idmotorista = $rs->fields['idmotorista'];
$idtmpventares_cab = $rs->fields['idpedido'];
if ($rs->fields['idventa'] == 0) {
    header("location: delivery_micaja.php");
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


    // recibe parametros
    $idestadodelivery = antisqlinyeccion($_POST['idestadodelivery'], "int");
    $idmotorista = antisqlinyeccion($_POST['idmotorista'], "int");



    if (intval($_POST['idestadodelivery']) == 0) {
        $valido = "N";
        $errores .= " - El campo estado delivery no puede ser cero o nulo.<br />";
    }
    if ($obliga_motorista == 'S') {
        if (intval($_POST['idestadodelivery']) == 0) {
            $valido = "N";
            $errores .= " - Debes seleccionar un motorista.<br />";
        }
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update tmp_ventares_cab
		set
			idestadodelivery=$idestadodelivery
		where
			idventa = $idventa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        /// log para los tiempos entre estados ///
        $parametros_array = [
            'idtmpventares_cab' => $idtmpventares_cab,
            'idestadodelivery' => $idestadodelivery,
            'fechahora_estado' => $ahora,
            'registrado_por' => $idusu,
            'registrado_el' => $ahora
        ];
        $res = estado_pedidos_tiempos_registra_log($parametros_array);
        /// log para los tiempos entre estados ///

        $consulta = "
		update ventas 
		set
		idmotorista = $idmotorista
		where 
		idventa = $idventa
		and estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: delivery_micaja.php?estado=".intval($_GET['estado']));
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());





// APPS DE DELIVERY
// delorean
$consulta = "
select app_rider.id_app_rider
from  app_rider_sucursales
inner join app_rider on app_rider.id_app_rider  =  app_rider_sucursales.id_app_rider
where 
app_rider_sucursales.idsucursal = $idsucursal
and app_rider.estado = 1
and app_rider_sucursales.estado = 1
and app_rider.id_app_rider = 1
limit 1
";
$rsdelo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsdelo->fields['id_app_rider']) > 0) {
    // valores por defecto
    $mostrar_delorean = "S";
    $texto_delorean = "Enviar a Delorean";
    $link_delorean = "delivery_micaja_delorean.php?idventa=".$idventa;
    $icon_boton_delorean = "fa fa-paper-plane";
    $tipo_boton_delorean = "btn btn-sm btn-default";
    // busca si ya se envio y llego correctamente
    $consulta = "
	select id_app_rider_log, idpedidoexterno, envio_correcto
	from app_rider_log
	where
	idventa = $idventa
	and id_app_rider = 1
	order by id_app_rider_log desc
	limit 1
	";
    $rsdelolog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpedidoexterno = intval($rsdelolog->fields['idpedidoexterno']);
    //si ya se envio este pedido
    if (intval($rsdelolog->fields['id_app_rider_log']) > 0) {
        // si el envio fue correcto
        if (trim($rsdelolog->fields['envio_correcto']) == 'S') {
            $mostrar_delorean = "S";
            $texto_delorean = "Enviado a Delorean! [$idpedidoexterno]";
            $link_delorean = "#";
            $icon_boton_delorean = "fa fa-check-square-o";
            $tipo_boton_delorean = "btn btn-sm btn-success";
        }
        // si el envio no fue correcto
        if (trim($rsdelolog->fields['envio_correcto']) == 'N') {
            $mostrar_delorean = "S";
            $texto_delorean = "Volver a enviar a Delorean";
            $link_delorean = "delivery_micaja_delorean.php?idventa=".$idventa;
            $icon_boton_delorean = "fa fa-paper-plane";
            $tipo_boton_delorean = "btn btn-sm btn-default";
        }
    }
}

// jolex
$consulta = "
select app_rider.id_app_rider, app_rider.token_api
from  app_rider_sucursales
inner join app_rider on app_rider.id_app_rider  =  app_rider_sucursales.id_app_rider
where 
app_rider_sucursales.idsucursal = $idsucursal
and app_rider.estado = 1
and app_rider_sucursales.estado = 1
and app_rider.id_app_rider = 2
limit 1
";
$rsdelo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsdelo->fields['id_app_rider']) > 0) {
    // valores por defecto
    $mostrar_jolex = "S";
    $texto_jolex = "Enviar a Jolex";
    $link_jolex = "delivery_micaja_jolex.php?idventa=".$idventa;
    $icon_boton_jolex = "fa fa-paper-plane";
    $tipo_boton_jolex = "btn btn-sm btn-default";
    // busca si ya se envio y llego correctamente
    $consulta = "
	select id_app_rider_log, idpedidoexterno, envio_correcto
	from app_rider_log
	where
	idventa = $idventa
	and id_app_rider = 2
	order by id_app_rider_log desc
	limit 1
	";
    $rsdelolog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpedidoexterno = intval($rsdelolog->fields['idpedidoexterno']);
    //si ya se envio este pedido
    if (intval($rsdelolog->fields['id_app_rider_log']) > 0) {
        // si el envio fue correcto
        if (trim($rsdelolog->fields['envio_correcto']) == 'S') {
            $mostrar_jolex = "S";
            $texto_jolex = "Enviado a Jolex! [$idpedidoexterno]";
            $link_jolex = "#";
            $icon_boton_jolex = "fa fa-check-square-o";
            $tipo_boton_jolex = "btn btn-sm btn-success";
        }
        // si el envio no fue correcto
        if (trim($rsdelolog->fields['envio_correcto']) == 'N') {
            $mostrar_jolex = "S";
            $texto_jolex = "Volver a enviar a Jolex";
            $link_jolex = "delivery_micaja_jolex.php?idventa=".$idventa;
            $icon_boton_jolex = "fa fa-paper-plane";
            $tipo_boton_jolex = "btn btn-sm btn-default";
        }
    }
}
// obtener riders de jolex
if ($mostrar_jolex == 'S') {

    $url_jolex = "http://jolex.tech-precision.com/api/external/getDrivers";
    $token_api = $rsdelo->fields['token_api'];
    $res = drivers_jolex($url_jolex, $token_api);
    $respuesta = json_decode($res, true);
    $riders = $respuesta['Items'];
    // desactiva los demas motoristas de la sucursal actual
    $consulta = "
	update app_rider_motorista
	set
	estado = 3
	where
	id_app_rider=2
	and idsucursal_presente = $idsucursal
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    foreach ($riders as $rider) {
        $idmotorista_app = intval($rider['Id']);
        $nombre_motorista = antisqlinyeccion($rider['UserName'], "text");
        $documento = antisqlinyeccion($rider['UserDocumentNumber'], "text");
        $idsucursal_app_jolex_actu = antisqlinyeccion($rider['CurrentBranchId'], "int");
        // busca a que sucursal nuestra corresponde el nuevo
        $consulta = "
		select idsucursal from app_rider_sucursales where idsucursal_app =$idsucursal_app_jolex_actu
		";
        $rssucnew = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idsucursal_nuestra_new_jolex = intval($rssucnew->fields['idsucursal']);
        // busca si existe en la tabla
        $consulta = "
		select id_app_rider_motorista
		from app_rider_motorista
		where
		idmotorista_app = $idmotorista_app
		and id_app_rider = 2
		";
        $rsexrider = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $id_app_rider_motorista_ex = intval($rsexrider->fields['id_app_rider_motorista']);

        // si no existe
        if ($id_app_rider_motorista_ex == 0) {
            // inserta
            $consulta = "
			INSERT INTO app_rider_motorista
			(id_app_rider, idmotorista, idmotorista_app, nombre_motorista, documento_motorista, estado, idsucursal_presente) 
			VALUES
			(2,NULL,$idmotorista_app,$nombre_motorista,$documento,1,$idsucursal_nuestra_new_jolex)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // si existe
        } else {
            // actualiza
            $consulta = "
			update app_rider_motorista
			set
			nombre_motorista=$nombre_motorista,
			documento_motorista=$documento,
			estado=1,
			idsucursal_presente=$idsucursal_nuestra_new_jolex
			where
			id_app_rider=2
			and idmotorista_app = $idmotorista_app
			and id_app_rider_motorista = $id_app_rider_motorista_ex
			";
            //echo $consulta;exit;
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

    }
    //print_r($riders);exit;
}


// vm logistica
$consulta = "
select app_rider.id_app_rider, app_rider.token_api
from  app_rider_sucursales
inner join app_rider on app_rider.id_app_rider  =  app_rider_sucursales.id_app_rider
where 
app_rider_sucursales.idsucursal = $idsucursal
and app_rider.estado = 1
and app_rider_sucursales.estado = 1
and app_rider.id_app_rider = 3
limit 1
";
$rsdelo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsdelo->fields['id_app_rider']) > 0) {
    // valores por defecto
    $mostrar_vm = "S";
    $texto_vm = "Enviar a VM Logistica";
    $link_vm = "delivery_micaja_vm.php?idventa=".$idventa;
    $icon_boton_vm = "fa fa-paper-plane";
    $tipo_boton_vm = "btn btn-sm btn-default";
    // busca si ya se envio y llego correctamente
    $consulta = "
	select id_app_rider_log, idpedidoexterno, envio_correcto
	from app_rider_log
	where
	idventa = $idventa
	and id_app_rider = 3
	order by id_app_rider_log desc
	limit 1
	";
    $rsdelolog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpedidoexterno = intval($rsdelolog->fields['idpedidoexterno']);
    //si ya se envio este pedido
    if (intval($rsdelolog->fields['id_app_rider_log']) > 0) {
        // si el envio fue correcto
        if (trim($rsdelolog->fields['envio_correcto']) == 'S') {
            $mostrar_vm = "S";
            $texto_vm = "Enviado a VM Logistica! [$idpedidoexterno]";
            $link_vm = "#";
            $icon_boton_vm = "fa fa-check-square-o";
            $tipo_boton_vm = "btn btn-sm btn-success";
        }
        // si el envio no fue correcto
        if (trim($rsdelolog->fields['envio_correcto']) == 'N') {
            $mostrar_vm = "S";
            $texto_vm = "Volver a enviar a VM Logistica";
            $link_vm = "delivery_micaja_vm.php?idventa=".$idventa;
            $icon_boton_vm = "fa fa-paper-plane";
            $tipo_boton_vm = "btn btn-sm btn-default";
        }
    }
}
// obtener riders de VM LOGISTICA
if ($mostrar_vm == 'S') {

    $url_vm = "http://vmlogistica.tech-precision.com/api/external/getDrivers";
    $token_api = $rsdelo->fields['token_api'];
    $res = drivers_vm($url_vm, $token_api);
    $respuesta = json_decode($res, true);
    $riders = $respuesta['Items'];
    // desactiva los demas motoristas de la sucursal actual
    $consulta = "
	update app_rider_motorista
	set
	estado = 3
	where
	id_app_rider=3
	and idsucursal_presente = $idsucursal
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    foreach ($riders as $rider) {
        $idmotorista_app = intval($rider['Id']);
        $nombre_motorista = antisqlinyeccion($rider['UserName'], "text");
        $documento = antisqlinyeccion($rider['UserDocumentNumber'], "text");
        $idsucursal_app_vm_actu = antisqlinyeccion($rider['CurrentBranchId'], "int");
        // busca a que sucursal nuestra corresponde el nuevo
        $consulta = "
		select idsucursal from app_rider_sucursales where idsucursal_app =$idsucursal_app_vm_actu
		";
        $rssucnew = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idsucursal_nuestra_new_vm = intval($rssucnew->fields['idsucursal']);
        // busca si existe en la tabla
        $consulta = "
		select id_app_rider_motorista
		from app_rider_motorista
		where
		idmotorista_app = $idmotorista_app
		and id_app_rider = 3
		";
        $rsexrider = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $id_app_rider_motorista_ex = intval($rsexrider->fields['id_app_rider_motorista']);

        // si no existe
        if ($id_app_rider_motorista_ex == 0) {
            // inserta
            $consulta = "
			INSERT INTO app_rider_motorista
			(id_app_rider, idmotorista, idmotorista_app, nombre_motorista, documento_motorista, estado, idsucursal_presente) 
			VALUES
			(3,NULL,$idmotorista_app,$nombre_motorista,$documento,1,$idsucursal_nuestra_new_vm)
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // si existe
        } else {
            // actualiza
            $consulta = "
			update app_rider_motorista
			set
			nombre_motorista=$nombre_motorista,
			documento_motorista=$documento,
			estado=1,
			idsucursal_presente=$idsucursal_nuestra_new_vm
			where
			id_app_rider=3
			and idmotorista_app = $idmotorista_app
			and id_app_rider_motorista = $id_app_rider_motorista_ex
			";
            //echo $consulta;exit;
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

    }
    //print_r($riders);exit;
}
// Pedi boss
$consulta = "
select app_rider.id_app_rider
from  app_rider_sucursales
inner join app_rider on app_rider.id_app_rider  =  app_rider_sucursales.id_app_rider
where 
app_rider_sucursales.idsucursal = $idsucursal
and app_rider.estado = 1
and app_rider_sucursales.estado = 1
and app_rider.id_app_rider = 4
limit 1
";
//echo $consulta;exit;
$rsdelo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rsdelo->fields['id_app_rider']) > 0) {
    // valores por defecto
    $mostrar_pediboss = "S";
    $texto_pediboss = "Enviar a Pediboss";
    $link_pediboss = "delivery_micaja_pediboss.php?idventa=".$idventa;
    $icon_boton_pediboss = "fa fa-paper-plane";
    $tipo_boton_pediboss = "btn btn-sm btn-default";
    // busca si ya se envio y llego correctamente
    $consulta = "
	select id_app_rider_log, idpedidoexterno, envio_correcto
	from app_rider_log
	where
	idventa = $idventa
	and id_app_rider = 4
	order by id_app_rider_log desc
	limit 1
	";
    $rsdelolog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idpedidoexterno = intval($rsdelolog->fields['idpedidoexterno']);
    //si ya se envio este pedido
    if (intval($rsdelolog->fields['id_app_rider_log']) > 0) {
        // si el envio fue correcto
        if (trim($rsdelolog->fields['envio_correcto']) == 'S') {
            $mostrar_pediboss = "S";
            $texto_pediboss = "Enviado a Pediboss! [$idpedidoexterno]";
            $link_pediboss = "#";
            $icon_boton_pediboss = "fa fa-check-square-o";
            $tipo_boton_pediboss = "btn btn-sm btn-success";
        }
        // si el envio no fue correcto
        if (trim($rsdelolog->fields['envio_correcto']) == 'N') {
            $mostrar_pediboss = "S";
            $texto_pediboss = "Volver a enviar a Pediboss";
            $link_pediboss = "delivery_micaja_pediboss.php?idventa=".$idventa;
            $icon_boton_pediboss = "fa fa-paper-plane";
            $tipo_boton_pediboss = "btn btn-sm btn-default";
        }
    }
}


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<?php
if ($mostrar_jolex == 'S') {
    ?>
	  
<script>
function enviar_jolex(){
	var id_app_rider_motorista = $("#id_app_rider_motorista").val();
	if(parseInt(id_app_rider_motorista) > 0){
		document.location.href='delivery_micaja_jolex.php?idventa=<?php echo $idventa; ?>&id_app_rider_motorista='+id_app_rider_motorista;
	}else{
		alert('Debe seleccionar el motorista de jolex.');
	}
}	
</script>
<?php 	}  ?>
<?php
if ($mostrar_vm == 'S') {
    ?>
	  
<script>
function enviar_vm(){
	var id_app_rider_motorista = $("#id_app_rider_motorista").val();
	if(parseInt(id_app_rider_motorista) > 0){
		document.location.href='delivery_micaja_vm.php?idventa=<?php echo $idventa; ?>&id_app_rider_motorista='+id_app_rider_motorista;
	}else{
		alert('Debe seleccionar el motorista de VM Logistica.');
	}
}	
</script>
<?php 	}  ?>
<?php
if ($mostrar_pediboss == 'S') {
    ?>
	  
<script>
function enviar_pediboss(){
	var id_app_rider_motorista = $("#id_app_rider_motorista").val();
	if(parseInt(id_app_rider_motorista) > 0){
		document.location.href='delivery_micaja_pediboss.php?idventa=<?php echo $idventa; ?>&id_app_rider_motorista='+id_app_rider_motorista;
	}else{
		alert('Debe seleccionar el motorista de pediboss.');
	}
}	
</script>
<?php 	}  ?>
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Cambiar estado del Delivery</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>

<a href="delivery_micaja.php?estado=<?php echo intval($_GET['estado']); ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<?php
if ($mostrar_delorean == 'S') {
    ?>
<a href="<?php echo $link_delorean; ?>" class="<?php echo $tipo_boton_delorean; ?>"><span class="<?php echo $icon_boton_delorean; ?>"></span> <?php echo $texto_delorean ?></a>
<?php } ?>
	

<?php
if ($mostrar_jolex == 'S') {
    ?>
<a href="#" onClick="enviar_jolex();" class="<?php echo $tipo_boton_jolex; ?>"><span class="<?php echo $icon_boton_jolex; ?>"></span> <?php echo $texto_jolex ?></a>
<?php } ?>
<?php
if ($mostrar_vm == 'S') {
    ?>
<a href="#" onClick="enviar_vm();" class="<?php echo $tipo_boton_vm; ?>"><span class="<?php echo $icon_boton_vm; ?>"></span> <?php echo $texto_vm ?></a>
<?php } ?>	
<?php
if ($mostrar_pediboss == 'S') {
    ?>
<a href="<?php echo $link_pediboss; ?>" class="<?php echo $tipo_boton_pediboss; ?>"><span class="<?php echo $icon_boton_pediboss; ?>"></span> <?php echo $texto_pediboss ?></a>
<?php } ?>
</p>

<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Venta #</th>
            <th align="center">Fecha/Hora</th>
			<th align="center">Nombre y Apellido</th>
            <th align="center">Razon Social</th>
            <th align="center">RUC</th>
            <th align="center">Direccion</th>
            <th align="center">Telefono</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
			<td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha'])); ?></td>
			<td align="center"><?php echo antixss($rs->fields['nombres']." ".$rs->fields['apellidos']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['direccion']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['telefono']); ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>

<div class="clearfix"></div>
<Hr />

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<?php
if ($_GET['ok'] == 's') {
    $disabled = 'disabled';
    $bnt = "N";
} else {
    $disabled = '';
    $bnt = "S";
}
?>
<form id="form1" name="form1" method="post" action="">
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Estado del Delivery </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
SELECT idestadodelivery, estado_delivery, orden
FROM delivery_estado
where
estado = 1
order by orden asc
 ";

// valor seleccionado
if (isset($_POST['idestadodelivery'])) {
    $value_selected = htmlentities($_POST['idestadodelivery']);
} else {
    $value_selected = htmlentities($idestadodelivery);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idestadodelivery',
    'id_campo' => 'idestadodelivery',

    'nombre_campo_bd' => 'estado_delivery',
    'id_campo_bd' => 'idestadodelivery',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  '.$disabled,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<?php
$required = "";
$obcampo = "";
if ($obliga_motorista == 'S') {
    $required = ' required="required" ';
    $obcampo = "*";
}
?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Motorista <?php echo $obcampo; ?></label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php



// consulta
$consulta = "
SELECT idmotorista, motorista
FROM motoristas
where
estado = 1
order by motorista asc
 ";

// valor seleccionado
if (isset($_POST['idmotorista'])) {
    $value_selected = htmlentities($_POST['idmotorista']);
} else {
    $value_selected = htmlentities($idmotorista);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmotorista',
    'id_campo' => 'idmotorista',

    'nombre_campo_bd' => 'motorista',
    'id_campo_bd' => 'idmotorista',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  '.$required.' '.$disabled,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

	
<?php
if ($mostrar_jolex == 'S') {
    ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Motorista Jolex: </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // busca si ya se envio y llego correctamente
    $consulta = "
select id_app_rider_log, idpedidoexterno, envio_correcto
from app_rider_log
where
idventa = $idventa
and id_app_rider = 2
and envio_correcto = 'S'
order by id_app_rider_log desc
limit 1
";
    $rsdelolog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $envio_correcto = $rsdelolog->fields['envio_correcto'];
    $disabled_jolex = "";
    if ($envio_correcto == 'S') {
        // BUSCA EL MOTORISTA ASIGNADO SI HUBIERA
        $consulta = "
	select id_app_rider_motorista from ventas_datosextra where idventa = $idventa and id_app_rider = 2
	";
        $rsriderven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $id_app_rider_motorista = intval($rsriderven->fields['id_app_rider_motorista']);
        $disabled_jolex = "disabled";
        $disabled = '';
    }
    // consulta
    $consulta = "
SELECT id_app_rider_motorista, nombre_motorista
FROM app_rider_motorista
where
estado = 1
and id_app_rider = 2
and idsucursal_presente = $idsucursal
order by nombre_motorista asc
 ";

    // valor seleccionado
    if (isset($_POST['id_app_rider_motorista'])) {
        $value_selected = htmlentities($_POST['id_app_rider_motorista']);
    } else {
        $value_selected = htmlentities($id_app_rider_motorista);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'id_app_rider_motorista',
        'id_campo' => 'id_app_rider_motorista',

        'nombre_campo_bd' => 'nombre_motorista',
        'id_campo_bd' => 'id_app_rider_motorista',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => '  '.$required.' '.$disabled.' '.$disabled_jolex,
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</div>
</div>
<?php } ?>
	
	
<?php
if ($mostrar_vm == 'S') {
    ?>
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Motorista VM Logistica: </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // busca si ya se envio y llego correctamente
    $consulta = "
select id_app_rider_log, idpedidoexterno, envio_correcto
from app_rider_log
where
idventa = $idventa
and id_app_rider = 2
and envio_correcto = 'S'
order by id_app_rider_log desc
limit 1
";
    $rsdelolog = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $envio_correcto = $rsdelolog->fields['envio_correcto'];
    $disabled_vm = "";
    if ($envio_correcto == 'S') {
        // BUSCA EL MOTORISTA ASIGNADO SI HUBIERA
        $consulta = "
	select id_app_rider_motorista from ventas_datosextra where idventa = $idventa and id_app_rider = 2
	";
        $rsriderven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $id_app_rider_motorista = intval($rsriderven->fields['id_app_rider_motorista']);
        $disabled_vm = "disabled";
        $disabled = '';
    }
    // consulta
    $consulta = "
SELECT id_app_rider_motorista, nombre_motorista
FROM app_rider_motorista
where
estado = 1
and id_app_rider = 2
and idsucursal_presente = $idsucursal
order by nombre_motorista asc
 ";

    // valor seleccionado
    if (isset($_POST['id_app_rider_motorista'])) {
        $value_selected = htmlentities($_POST['id_app_rider_motorista']);
    } else {
        $value_selected = htmlentities($id_app_rider_motorista);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'id_app_rider_motorista',
        'id_campo' => 'id_app_rider_motorista',

        'nombre_campo_bd' => 'nombre_motorista',
        'id_campo_bd' => 'id_app_rider_motorista',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => '  '.$required.' '.$disabled.' '.$disabled_vm,
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
	</div>
</div>
<?php } ?>
	
<div class="clearfix"></div>
<br />
<?php if ($bnt == "S") { ?>
    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='delivery_micaja.php?estado=<?php echo intval($_GET['estado']); ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
<?php } ?>
</form>
<div class="clearfix"></div>
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
