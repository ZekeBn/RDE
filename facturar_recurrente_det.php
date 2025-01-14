 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "26";
$submodulo = "310";
require_once("includes/rsusuario.php");

require_once("includes/funciones_stock.php");
require_once("includes/funciones_cobros.php");
require_once("includes/funciones_ventas.php");

$idproducto_servicio = intval($rsco->fields['idproducto_recurrente']); // traer de preferencias tipo producto debe ser 7  y en ventas_detalles almacera concepto en pchar


//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$idcaja_old = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja_old == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>";
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>";
    exit;
}


// si la caja no esta abierta direcciona
$parametros_array = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res = caja_abierta($parametros_array);
$idcaja_new = $res['idcaja'];
//print_r($res);
//exit;
if ($res['valido'] != 'S') {
    header("location: gest_administrar_caja.php");
    exit;
}

$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    header("location: facturar_recurrente.php");
    exit;
}

// consulta a la tabla
$consulta = "
select *
from cliente 
where 
idcliente = $idcliente
and estado <> 6
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rs->fields['idcliente']);
$razon_social = $rs->fields['razon_social'];
$ruc = $rs->fields['ruc'];
if ($idcliente == 0) {
    header("location: facturar_recurrente.php");
    exit;
}



//genera cabecera si no existe
$parametros_array_gencab = [
    'idusu' => $idusu,
    'idatc' => '', // opcional
    'idcanal' => 1,
];
$idtmpventares_cab = genera_cabecera_pedido($parametros_array_gencab);
//echo $idtmpventares_cab;exit;

$consulta = "
select iddeposito from gest_depositos where tiposala = 2 and idsucursal = $idsucursal limit 1
";
$rsdepo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rsdepo->fields['iddeposito']);

// recalcula el monto pendiente de facturar
$consulta = "
update detalle 
set 
facturado_cuota_saldo = monto_cuota-quita_cuota-facturado_cuota
where 
idoperacion in (select idoperacion from operacion where idcliente = $idcliente)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

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

    $observacion = antisqlinyeccion(substr(trim($_POST['concepto']), 0, 150), "like");
    $tipo_venta = antisqlinyeccion($_POST['condicion_factura'], "int");
    $factura = antisqlinyeccion($_POST['factura'], "like");
    $fecha_factura = antisqlinyeccion($_POST['fecha_factura'], "like");
    $vencimiento_factura = antisqlinyeccion($_POST['vencimiento_factura'], "like");

    // conversiones
    $factura_completa = trim($_POST['factura']);
    $factura_suc = substr($factura_completa, 0, 3);
    $factura_pexp = substr($factura_completa, 3, 3);
    $factura_nro = substr($factura_completa, 6, 7);

    // para permitir al contado hay que programar un mecanismo diferente que ya acredite al instante de facturar
    // en las tablas detalles, operaciones y factura_recurrente
    // tambien hay que modificar anular venta y anular cobro si se va a permitir factura contado
    if ($tipo_venta != 2) {
        $errores .= "- No se puede facturar recurrente con factura al contado.<br />";
        $valido = "N";
    }
    if ($idproducto_servicio == 0) {
        $errores .= "- No se indico el articulo recurrente.<br />";
        $valido = "N";
    }


    // monto para agregar al carrito
    $consulta = "
    select sum(monto_abonar) as monto_facturar
    from tmp_carrito_factu
     where 
     estado = 1 
     and registrado_por = $idusu 
     and idcliente = $idcliente
     ";
    $rscardat = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $monto_facturar = $rscardat->fields['monto_facturar'];

    // busca si ya hay en el carrito
    $consulta = "
    select * 
    from tmp_ventares 
    where 
    usuario = $idusu 
    and idtmpventares_cab = $idtmpventares_cab 
    and borrado = 'N' 
    and finalizado = 'N'
    limit 1
    ";
    $rscar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idventatmp = $rscar->fields['idventatmp'];
    // SI EXISTE Actualiza
    if ($idventatmp > 0) {
        $consulta = "
        update tmp_ventares 
        set 
        precio = $monto_facturar,
        subtotal = $monto_facturar,
        observacion = '$observacion'
        where 
        idventatmp = $idventatmp
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si no existe agrega
    } else {
        // recibe parametros
        $parametros_array_car = [
            'idtmpventares_cab' => $idtmpventares_cab,
            'idproducto' => $idproducto_servicio,
            'cantidad' => 1,
            'precio' => $monto_facturar,
            'idmesa' => '',
            'idusu' => $idusu,
            'idsucursal' => $idsucursal,
            'observacion' => $observacion

        ];
        // agrega al carrito
        $res = agregar_carrito($parametros_array_car);
        if ($res['valido'] == 'N') {
            $errores .= nl2br($res['errores']);
            $valido = "N";
        }
    }

    //exit;

    $parametros_array_ped = [
        'idtmpventares_cab' => $idtmpventares_cab,
        'idtmpventares_cab_old' => '', // solo tablet
        'idmesa' => '', //opcional
        'idusu' => $idusu,
        'idsucursal' => $idsucursal,
        'idempresa' => $idempresa,
        'razon_social' => $razon_social,
        'ruc' => $ruc,
        'chapa' => '',
        'observacion' => $observacion,
        'iddomicilio' => '',
        'idmozo' => '',
        'idcanal' => 1,
        'idzona' => '',
        'fechahora' => $ahora,
    ];
    $res = validar_pedido($parametros_array_ped);
    if ($res['valido'] == 'N') {
        $errores .= nl2br($res['errores']);
        $valido = "N";
    }

    // validar venta
    $parametros_array_vta = [
        'idcaja' => $idcaja_old,
        'afecta_caja' => 'N',
        'iddeposito' => $iddeposito,
        'idtmpventares_cab' => $idtmpventares_cab,
        'idsucursal' => $idsucursal,
        'idempresa' => $idempresa,
        'factura_suc' => $factura_suc,
        'factura_pexp' => $factura_pexp,
        'factura_nro' => $factura_nro,
        'registrado_por' => $idusu,
        'idcliente' => $idcliente,
        'idmoneda' => 1,
        'idvendedor' => '',
        'tipo_venta' => $tipo_venta,
        'fecha' => $fecha_factura,
        'vencimiento_factura' => $vencimiento_factura, // nuevo agregar a la funcion
        'detalle_agrupado' => 'N',

    ];
    //print_r($parametros_array_vta);exit;
    $res = validar_venta($parametros_array_vta);
    if ($res['valido'] == 'N') {
        $errores .= nl2br($res['errores']);
        $valido = "N";
    }

    if ($valido == 'S') {

        // registra pedido
        registrar_pedido($parametros_array_ped);

        // registra venta
        $idventa = registrar_venta($parametros_array_vta);

        // registra observacion larga
        if (trim($_POST['concepto']) != '') {
            $observacion = antisqlinyeccion(substr(trim($_POST['concepto']), 0, 10000), "like");
            $consulta = "
            INSERT INTO ventas_observacion_larga
            (idventa, observacion_larga) 
            VALUES 
            ($idventa, '$observacion') 
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        // recorrer el carrito
        $consulta = "
        select * 
        from tmp_carrito_factu
        inner join detalle on detalle.iddetalle =  tmp_carrito_factu.iddetalle
        where 
         tmp_carrito_factu.estado = 1 
         and tmp_carrito_factu.registrado_por = $idusu 
         and tmp_carrito_factu.idcliente = $idcliente
        ";
        $rscar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rscar->EOF) {
            $idoperacion = $rscar->fields['idoperacion'];
            $nro_cuota = $rscar->fields['nro_cuota'];
            $monto_abonar = $rscar->fields['monto_abonar'];
            // registrar en la tabla factura_recurrente
            $consulta = "
            INSERT INTO factura_recurrente
            (idoperacion, nro_cuota, idventa, monto_facturado, saldo_cobrar_recu, estado)
             VALUES
             ($idoperacion,$nro_cuota,$idventa,$monto_abonar, $monto_abonar, 1)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // actualiza detalles
            $consulta = "
            update detalle set 
            facturado_cuota = COALESCE((select sum(monto_facturado) from factura_recurrente where factura_recurrente.idoperacion = detalle.idoperacion and factura_recurrente.nro_cuota = detalle.nro_cuota ),0)
            where
            idoperacion = $idoperacion
            and nro_cuota = $nro_cuota
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            //facturado_cuota_saldo se actualiza al cobrar y al anular
            $consulta = "
            update detalle set 
            facturado_cuota_saldo = monto_cuota-quita_cuota-facturado_cuota
            where
            idoperacion = $idoperacion
            and nro_cuota = $nro_cuota
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            $rscar->MoveNext();
        }


        $consulta = "
        update tmp_carrito_factu
        set estado = 6
         where 
         estado = 1 
         and registrado_por = $idusu 
         and idcliente = $idcliente
         ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // tocar cobro de cuentas para que acredite los pagos
        // tocar anulacion ventas para que anule la recurrente



        header('location: script_central_impresion.php?vta='.$idventa.'&tk=1&modventa=6');
        exit;

    }





}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

/*
// recargar con registros viejos que no tienen idventa
INSERT INTO factura_recurrente(idoperacion, nro_cuota, idventa, monto_facturado, estado)

select idoperacion, nro_cuota, 0, detalle.monto_cuota, 1
from detalle
WHERE
detalle.saldo_cuota = 0;
update detalle
set
cobra_cuota = COALESCE
            (
                 (select sum(monto)
                 from pagos_det
                 where
                 pagos_det.detalle_idoperacion = detalle.idoperacion
                 and pagos_det.detalle_nro_cuota=detalle.nro_cuota
                 and pagos_det.estado = 1
                 ),
             0),
quita_cuota = COALESCE
            (
                 (select sum(monto_quita)
                 from detalle_quita
                 where
                 detalle_quita.idoperacion = detalle.idoperacion
                 and detalle_quita.nro_cuota=detalle.nro_cuota
                 and detalle_quita.estado = 1
                 ),
             0),
facturado_cuota = COALESCE
            (
                 (select sum(monto_facturado)
                 from factura_recurrente
                 where
                 factura_recurrente.idoperacion = detalle.idoperacion
                 and factura_recurrente.nro_cuota=detalle.nro_cuota
                 and factura_recurrente.estado = 1
                 ),
             0)
;
update detalle
set
saldo_cuota = monto_cuota-quita_cuota-cobra_cuota,
facturado_cuota_saldo = monto_cuota-quita_cuota-facturado_cuota

;

*/





?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function agregar_cta_todas(){
    var direccionurl='facturar_recurrente_car_add.php';    
    var parametros = {
      "MM_insert"    : "TODAS",
      "idcliente"    : <?php echo $idcliente; ?>
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#carrito_cuentas").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        actualiza_carrito(<?php echo $idcliente; ?>);
                    }else{
                        var titulo = 'Errores';
                        var mensaje = obj.errores;
                        alerta_modal(titulo,mensaje);
                        actualiza_carrito(<?php echo $idcliente; ?>);    
                    }
                }else{
                    alert(response);
                    $("#carrito_cuentas").html(response);        
                }

            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
}
function agregar_cta(iddetalle){
    var monto_abonar = $("#iddetalle_"+iddetalle).val();
    var direccionurl='facturar_recurrente_car_add.php';    
    var parametros = {
      "MM_insert"    : "form1",
      "iddetalle"        : iddetalle,
      "monto_abonar" : monto_abonar
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#carrito_cuentas").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        actualiza_carrito(<?php echo $idcliente; ?>);
                    }else{
                        var titulo = 'Errores';
                        var mensaje = obj.errores;
                        alerta_modal(titulo,mensaje);
                        actualiza_carrito(<?php echo $idcliente; ?>);    
                    }
                }else{
                    alert(response);
                    $("#carrito_cuentas").html(response);        
                }

            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
    
}
function actualiza_facturas_pend(idcliente){
    var direccionurl='facturar_recurrente_obliga_pend.php';        
    var parametros = {
      "idcliente" : idcliente          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#facturas_pend").html('Actualizando...');                
        },
        success:  function (response) {
            $("#facturas_pend").html(response);    
        }
    });
}
function actualiza_carrito(idcliente){
    var direccionurl='facturar_recurrente_car.php';        
    var parametros = {
      "idcliente" : idcliente          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#carrito_cuentas").html('Actualizando...');                
        },
        success:  function (response) {
            $("#carrito_cuentas").html(response);
            actualiza_facturas_pend(idcliente);
            var fechas_txt = $("#fechas_txt").val();
            $("#concepto").val(fechas_txt);
        }
    });
}
function alerta_modal(titulo,mensaje){
    $('#dialogobox').modal('show');
    $("#myModalLabel").html(titulo);
    $("#modal_cuerpo").html(mensaje);

    
}

    </script>
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
                    <h2>Facturar Recurrente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<p><a href="facturar_recurrente.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Idcliente</th>
            <th align="center">Ruc</th>
            <th align="center">Razon social</th>
            <th align="center">Nombre de Fantasia</th>


        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>

        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />

<hr />
<strong>Obligaciones Pendientes de facturar:</strong>
<div id="facturas_pend"><?php require_once("facturar_recurrente_obliga_pend.php"); ?></div>

<strong>Obligaciones Seleccionadas:</strong>
<div id="carrito_cuentas"><?php require_once("facturar_recurrente_car.php"); ?></div>


  
<div class="clearfix"></div>
<hr /><br />


<?php
$consulta = "
select * from lastcomprobantes where pe = $factura_pexp and idsuc = $factura_suc order by ano desc limit 1;
";
$rsproxfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$proxfactura = intval($rsproxfac->fields['numfac']) + 1;

$factura_completa = agregacero($factura_suc, 3).agregacero($factura_pexp, 3).agregacero($proxfactura, 7);

?>

<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="factura" id="factura" value="<?php  if (isset($_POST['factura'])) {
        echo htmlentities($_POST['factura']);
    } else {
        echo $factura_completa;
    } ?>" placeholder="Factura" class="form-control"   />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Condicion *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <select name="condicion_factura" class="form-control">
        <option value="2" <?php if ($_POST['condicion_factura'] == 2) {
            echo "selected";
        } ?>>CREDITO</option>

    </select>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="fecha_factura" id="fecha_factura" value="<?php if (isset($_POST['fecha_factura'])) {
        echo htmlentities($_POST['fecha_factura']);
    } else {
        echo date("Y-m-d");
    } ?>" placeholder="Fecha factura" class="form-control"   />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Vencimiento Factura *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="vencimiento_factura" id="vencimiento_factura" value="<?php  if (isset($_POST['vencimiento_factura'])) {
        echo htmlentities($_POST['vencimiento_factura']);
    } else {
        echo date("Y-m-d");
    } ?>" placeholder="Vencimiento factura" class="form-control"  />                    
    </div>
</div>

<div class="col-md-12 col-sm-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Concepto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php /*?><input type="text" name="concepto" id="concepto" value="<?php  if(isset($_POST['concepto'])){ echo htmlentities($_POST['concepto']); }else{ echo $fechas_txt; } ?>" placeholder="Servicio de xxxx" class="form-control" maxlength="150"  /><?php */ ?>
    <textarea name="concepto" id="concepto" cols="50" rows="8" placeholder="Servicio de xxxx" class="form-control" maxlength="10000"><?php  if (isset($_POST['concepto'])) {
        echo htmlentities($_POST['concepto']);
    } else {
        echo $fechas_txt;
    } ?></textarea>
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Facturar</button>

        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
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
        
        <!-- POPUP DE MODAL OCULTO -->
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
                        ...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>

                      
                  </div>
                </div>
              </div>
              
              
              
        <!-- POPUP DE MODAL OCULTO -->
        

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
