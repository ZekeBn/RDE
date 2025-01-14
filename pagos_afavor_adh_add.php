 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "430";
require_once("includes/rsusuario.php");


$consulta = "
select fecha_en_recibo from preferencias_caja limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$usa_adherente = $rsco->fields['usa_adherente'];

//Comprobar apertura de caja en fecha establecida
$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}

require_once("includes/funciones_cobros.php");


$idevento = intval($_GET['idevento']);
if ($idevento > 0) {
    $consulta = "
    Select estado_pedido_int,cliente.razon_social,cliente.fantasia,cliente.nombre,cliente.apellido,regid,idpresupuesto,evento_para,hora_entrega,dire_entrega,
    ubicacion_comp as ubicacion,idtransaccion,monto_evento,cliente.idcliente,
    cliente.email,
    cliente.telefono,
    (select usuario from usuarios where idusu=pedidos_eventos.ultimo_cambio_por ) as quiencambio,
    ultimo_cambio,
    razon_social  as  solicitado_por,
    cliente.idcliente as idclientereal,
    (select pasado from produccion_orden_new where idunico=pedidos_eventos.regid order by idunico asc limit 1) as pasado,
    pedidos_eventos.registrado_el,confirmado_pedido_el as confirmadoel,
    (select usuario from usuarios where idusu=pedidos_eventos.registrado_por) as quien,
    pedidos_eventos.nombre_evento, pedidos_eventos.saldo_evento
    from pedidos_eventos 
    inner join cliente on cliente.idcliente=pedidos_eventos.id_cliente_solicita
    where 
    pedidos_eventos.estado<>6
    and regid = $idevento
    limit 1
    ";
    $rsevento = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idcliente = intval($rsevento->fields['idcliente']);
    $idevento = intval($rsevento->fields['regid']);
    if (intval($rsevento->fields['saldo_evento']) <= 0) {
        echo "Evento ya no tiene saldo.";
        exit;
    }

    //echo $idcliente;exit;
} else {
    $idcliente = intval($_GET['id']);
}
// consulta a la tabla
$consulta = "
select * 
from cliente 
where 
idcliente = $idcliente
and estado = 1
limit 1
";
$rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rscli->fields['idcliente']);

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


    $parametros_array = [
        'monto' => $_POST['monto'],
        'idcliente' => $_POST['idcliente'],
        'idadherente' => $_POST['idadherente'],
        'idserviciocom' => $_POST['idserviciocom'],
        'idusu' => $idusu,
        'idcaja' => $idcaja,
        'idsucursal' => $idsucursal,
        'recibo' => $_POST['recibo'],
        'fecha_recibo' => $_POST['fecha_recibo'],
        'idevento' => $idevento,
        'idpago_precargado' => '' // si ya hay un registro de caja realizado sino dejar en blanco y generara
    ];
    //print_r($parametros_array);exit;
    $res = validar_anticipo($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = $res['valido'];
        $errores .= $res['errores'];
    }

    // si todo es correcto inserta
    if ($valido == "S") {


        $res = registrar_anticipo($parametros_array);
        $idpago_afavor = intval($res['idpago_afavor']);

        header("location: pagos_afavor_adh_det.php?id=".$idpago_afavor);
        exit;

    }
}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



$link_btn_cancel = 'pagos_afavor_adh.php';
if ($idevento > 0) {
    $link_btn_cancel = 'cat_adm_pedidos_new.php';
}


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function alerta_modal(titulo,mensaje){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html(titulo);
    $("#modal_cuerpo").html(mensaje);

    
}
function busca_cliente(){
    var direccionurl='busqueda_cliente_anticipo.php';        
    var parametros = {
      "m" : '1'          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $('#modal_ventana').modal('show');
            $("#modal_titulo").html('Busqueda de Cliente');
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        }
    });
    
}
function busca_cliente_res(tipo){
    var ruc = $("#ruc").val();
    var razon_social = $("#razon_social").val();
    if(tipo == 'ruc'){
        razon_social = '';
        $("#razon_social").val('');
    }
    if(tipo == 'razon_social'){
        ruc = '';
        $("#ruc").val('');
    }
    var direccionurl='busqueda_cliente_anticipo_res.php';        
    var parametros = {
      "ruc"            : ruc,
      "razon_social"   : razon_social
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#busqueda_cli").html('Cargando...');                
        },
        success:  function (response) {
            $("#busqueda_cli").html(response);
        }
    });
}
function seleccionar_item(idcliente,descricion){
    $("#idcliente").val(idcliente+' - '+descricion);
    $('#modal_ventana').modal('hide');
    document.location.href='pagos_afavor_adh_add.php?id='+idcliente;
}
function agrega_fpag(){
    var idformapago = $('#idforma').val();
    var monto_pago = $('#monto_pago').val();    
    var idbanco = $('#idbanco').val();
    var idbanco_propio = $('#idbanco_propio').val();
    var cheque_nro = $('#cheque_nro').val();
    var transfer_nro = $('#transfer_nro').val();
    var boleta_nro = $('#boleta_nro').val();
    var idcuentacon = $('#idcuentacon').val();
    var fecha_emision = $('#fecha_emision').val();
    var fecha_vencimiento = $('#fecha_vencimiento').val();
    var tarjeta_boleta = $('#tarjeta_boleta').val();
    var retencion_numero = $('#retencion_numero').val();
    
    
    var direccionurl='pagos_afavor_adh_car_fpag_add.php';
    var parametros = {
          "idformapago" : idformapago,
          "idcliente" :  '<?php echo $idcliente ?>',
          "idevento" :  '<?php echo $idevento ?>',
          "monto_pago" : monto_pago,
          "idbanco" : idbanco,
          "idbanco_propio" : idbanco_propio,
          "cheque_numero" : cheque_nro,
          "transfer_numero" : transfer_nro,
          "boleta_deposito" : boleta_nro,
          "tarjeta_boleta" : tarjeta_boleta,
          "retencion_numero" : retencion_numero,
          "idcuentacon" : idcuentacon,
          "fecha_emision" : fecha_emision,
          "fecha_vencimiento" : fecha_vencimiento
   };
   $.ajax({
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            //$("#cuentabox").hide();
            $("#idcuentacon").val('Cargando...');
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        actualiza_carrito_fpag(<?php echo $idcliente ?>);
                    }else{
                        var titulo = 'Errores';
                        var mensaje = obj.errores;
                        alerta_modal(titulo,mensaje);
                        actualiza_carrito_fpag(<?php echo $idcliente ?>);    
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
function actualiza_carrito_fpag(idcliente){
    var direccionurl='pagos_afavor_adh_car_fpag.php';        
    var parametros = {
      "idcliente" : idcliente,
      "idevento"  : '<?php echo $idevento; ?>'
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#carrito_fpag_cuentas").html('Actualizando...');                
        },
        success:  function (response) {
            $("#carrito_fpag_cuentas").html(response);    
        }
    });
}
<?php
$consulta = "
SELECT *
FROM formas_pago
WHERE 
estado = 1
order by idforma asc
";
$rsfp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//$formas_parametros=array();
while (!$rsfp->EOF) {
    $idforma = $rsfp->fields['idforma'];
    $formas_parametros[$idforma] =
        [
            'tarjeta_boleta' => $rsfp->fields['tarjeta_boleta'],
            'cheque_numero' => $rsfp->fields['cheque_numero'],
            'boleta_deposito' => $rsfp->fields['boleta_deposito'],
            'retencion_numero' => $rsfp->fields['retencion_numero'],
            'transfer_numero' => $rsfp->fields['transferencia'],
            'usa_banco' => $rsfp->fields['usa_banco'],
            'usa_banco_propio' => $rsfp->fields['usa_banco_propio'],
            'anticipo' => $rsfp->fields['anticipo']
        ];
    $rsfp->MoveNext();
}
//print_r($formas_parametros);exit;
// convierte a formato json
$formas_parametros_json = json_encode($formas_parametros, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

?>
function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i] == needle) return true;
    }
    return false;
}
function campos_toggle(campos_todos,campos_activos){
    var nuevo_valor = '';            
    campos_todos.forEach(
        function(currentValue){
            var html = $("#res").html();
            if(inArray(currentValue, campos_activos)){
                //nuevo_valor = currentValue+' SI esta activo<br />';
                //$("#res").html(html+nuevo_valor);
                //alert(currentValue+' esta activo');
                activar_campo(currentValue)
            }else{
                //alert(currentValue+' esta inactivo');
                //nuevo_valor = currentValue+' NO esta activo<br />';
                //$("#res").html(html+nuevo_valor);
                desactivar_campo(currentValue)
            }
        }
    );
    
}
function activar_campo(campo){
    // poner obligatorio
    $('#'+campo).attr('required', true); 
    // mostrar campo
    $('#'+campo+'_box').show();
    
}
function desactivar_campo(campo){
    // quitar obligatorio
    $('#'+campo).attr('required', false); 
    // borrar balor
    $('#'+campo).val('');
    // ocultar campo
    $('#'+campo+'_box').hide();
    //alert(campo);
}
function forma_pago_datos(idforma){
    
    var campos_todos = ["idbanco", "idbanco_propio", "cheque_nro", "transfer_nro", "boleta_nro", "idcuentacon", "fecha_emision", "fecha_vencimiento", "tarjeta_boleta"];
    
    if(idforma > 0){
        var parametros_formas = '<?php echo $formas_parametros_json; ?>';
        //alert(idforma);
        if(IsJsonString(parametros_formas)){
            var obj = jQuery.parseJSON(parametros_formas);
            //alert(obj[idforma]['tarjeta_boleta']);
            var tarjeta_boleta = obj[idforma]['tarjeta_boleta'];
            var cheque_numero = obj[idforma]['cheque_numero'];
            var boleta_deposito = obj[idforma]['boleta_deposito'];
            var retencion_numero = obj[idforma]['retencion_numero'];
            var transfer_numero = obj[idforma]['transfer_numero'];
            var usa_banco = obj[idforma]['usa_banco'];
            var usa_banco_propio = obj[idforma]['usa_banco_propio'];
            var anticipo = obj[idforma]['anticipo'];
            


            // ocultar, desocultar, requerir campos
            if(tarjeta_boleta == 'S'){
                var campos_activos = ["idbanco", "idcuentacon", "tarjeta_boleta"];
                campos_toggle(campos_todos,campos_activos);
                
            }else
            if(cheque_numero == 'S'){
                var campos_activos = ["idbanco", "cheque_nro", "idcuentacon"];
                campos_toggle(campos_todos,campos_activos);
            }else
            if(boleta_deposito == 'S'){
                var campos_activos = ["idbanco_propio", "boleta_nro", "idcuentacon"];
                campos_toggle(campos_todos,campos_activos);
            }else
            if(transfer_numero == 'S'){
                var campos_activos = ["idbanco", "idbanco_propio", "transfer_nro", "idcuentacon"];
                campos_toggle(campos_todos,campos_activos);

            }else
            if(usa_banco == 'S'){
                var campos_activos = ["idbanco", "idcuentacon"];
                campos_toggle(campos_todos,campos_activos);
                
            }else
            if(usa_banco_propio == 'S'){
                var campos_activos = ["idbanco_propio", "idcuentacon"];
                campos_toggle(campos_todos,campos_activos);

            }else
            if(retencion_numero == 'S'){
                var campos_activos = ["idcuentacon"];
                campos_toggle(campos_todos,campos_activos);

            }else
            if(anticipo == 'S'){
                var campos_activos = [''];
                campos_toggle(campos_todos,campos_activos);

            }else{
                var campos_activos = [''];
                campos_toggle(campos_todos,campos_activos);
            }




        }else{
            alert('No se encontraron formas de pago.');    
        }

    }else{
        
        var campos_activos = [''];
        campos_toggle(campos_todos,campos_activos);

    }
        
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
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
                    <h2>Anticipos</h2>
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
<form id="form1" name="form1" method="post" action="">

<p><a href="<?php echo $link_btn_cancel; ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<?php if (intval($idcliente) > 0) {?>
<strong>Estado de Cuenta Titular:</strong>
<?php
$consulta = "
SELECT idcliente, razon_social, nombre, apellido, saldo_sobregiro-linea_sobregiro as saldo, linea_sobregiro, saldo_sobregiro,
CASE 
WHEN  (saldo_sobregiro-linea_sobregiro) > 0 THEN 'SALDO A FAVOR'
WHEN  (saldo_sobregiro-linea_sobregiro) = 0 THEN 'SALDO CERO'
ELSE  'DEUDA' END AS tipo
FROM cliente
WHERE
estado <> 6
and idcliente = $idcliente
";
    $rslinea = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    ?><br />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th align="center">Cliente</th>
            <th align="center">Saldo</th>
            <th align="center">Tipo Saldo</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rslinea->EOF) { ?>
        <tr>


            <td align="center"><?php echo antixss($rslinea->fields['razon_social']); ?> [<?php echo antixss($rslinea->fields['idcliente']); ?>]</td>
</td>
            <td align="center"><?php echo formatomoneda($rslinea->fields['saldo']); ?></td>
            <td align="center"><?php echo antixss($rslinea->fields['tipo']); ?> </td>
        </tr>
<?php

    $rslinea->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>

    </table>
</div>

<?php } ?>
<?php
$consulta = "
SELECT 
cliente.razon_social,
adherentes_servicioscom.idcliente,  
adherentes_servicioscom.idadherente, 
adherentes_servicioscom.idserviciocom, 
CONCAT(adherentes.nombres,' ',adherentes.apellidos) AS adherente, 
servicio_comida.nombre_servicio, 
disponibleserv-linea_credito as saldo,
CASE 
WHEN  (disponibleserv-linea_credito) > 0 THEN 'SALDO A FAVOR'
WHEN  (disponibleserv-linea_credito) = 0 THEN 'SALDO CERO'
ELSE  'DEUDA' END AS tipo, adherentes_servicioscom.linea_credito, adherentes_servicioscom.disponibleserv
FROM  `adherentes_servicioscom`
inner join adherentes on adherentes.idadherente = adherentes_servicioscom.idadherente
inner join servicio_comida on servicio_comida.idserviciocom = adherentes_servicioscom.idserviciocom
inner join cliente on cliente.idcliente = adherentes_servicioscom.idcliente
where
adherentes_servicioscom.idcliente = $idcliente
order by adherentes_servicioscom.idadherente asc, adherentes_servicioscom.idserviciocom asc
";
$rslinea = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

if (intval($rslinea->fields['idadherente']) > 0) {
    ?>
<strong>Estado de Cuenta por Servicio:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>


            <th align="center">Adherente</th>
            <th align="center">Servicio</th>
            <th align="center">Saldo</th>
            <th align="center">Tipo Saldo</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rslinea->EOF) { ?>
        <tr>


            <td align="center"><?php echo antixss($rslinea->fields['adherente']); ?> [<?php echo antixss($rslinea->fields['idadherente']); ?>]</td>
            <td align="center"><?php echo antixss($rslinea->fields['nombre_servicio']); ?> [<?php echo antixss($rslinea->fields['idserviciocom']); ?>]</td>
            <td align="center"><?php echo formatomoneda($rslinea->fields['saldo']); ?></td>
            <td align="center"><?php echo antixss($rslinea->fields['tipo']); ?> </td>
        </tr>
<?php

    $rslinea->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>

    </table>
</div>
<br />
<?php } ?>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idcliente" id="idcliente" value="<?php  if (intval($idcliente) > 0) {
        echo antixss($rscli->fields['idcliente'].' - '.$rscli->fields['razon_social']);
    } ?>" placeholder="Click para buscar..." class="form-control" onMouseUp="busca_cliente()"  required readonly />                    
    </div>
</div>
<?php if ($idevento > 0) { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Evento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idevento" id="idevento" value="<?php echo $rsevento->fields['nombre_evento']; ?>" placeholder="" class="form-control"  required readonly />                    
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Saldo Evento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="saldo_evento" id="saldo_evento" value="<?php echo formatomoneda($rsevento->fields['saldo_evento'], 4, 'N'); ?>" placeholder="" class="form-control"  required readonly />                    
    </div>
</div>
<?php } ?>
<?php if ($idcliente > 0) {?>
    
<?php if ($usa_adherente == 'S') {  ?>
<div class="col-md-6 col-sm-6 form-group"  >
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Adherente *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta = "
SELECT idadherente, nomape
  FROM adherentes
  where 
  estado = 1
  and idcliente = $idcliente
ORDER BY nomape;

 ";


    // valor seleccionado
    if (isset($_POST['idadherente'])) {
        $value_selected = htmlentities($_POST['idadherente']);
    } else {
        $value_selected = htmlentities($rs->fields['idadherente']);
    }

    // parametros
    $parametros_array = [
            'nombre_campo' => 'idadherente',
            'id_campo' => 'idadherente',

            'nombre_campo_bd' => 'nomape',
            'id_campo_bd' => 'idadherente',

            'value_selected' => $value_selected,

            'pricampo_name' => 'NINGUNO (APLICAR AL TITULAR)',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => ' ',
            'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);


    ?>
    </div>
</div>
    
   
<div class="col-md-6 col-sm-6 form-group"  >
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Servicio Comida *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
SELECT idserviciocom, nombre_servicio
  FROM servicio_comida
  where 
  estado = 'A'
ORDER BY nombre_servicio;

 ";


    // valor seleccionado
    if (isset($_POST['idserviciocom'])) {
        $value_selected = htmlentities($_POST['idserviciocom']);
    } else {
        $value_selected = htmlentities($rs->fields['idserviciocom']);
    }

    // parametros
    $parametros_array = [
            'nombre_campo' => 'idserviciocom',
            'id_campo' => 'idserviciocom',

            'nombre_campo_bd' => 'nombre_servicio',
            'id_campo_bd' => 'idserviciocom',

            'value_selected' => $value_selected,

            'pricampo_name' => 'seleccionar...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => ' ',
            'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);


    ?>
    </div>
</div>
<?php } //  if($usa_adherente == 'S'){?>

    <div class="clearfix"></div>
<br />
    
<div class="col-md-6 col-sm-6 form-group" id="formapagobox" >
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Forma de Pago *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
    // no quitar and idforma_old por que se usa para la caja vieja
    // consulta
    $consulta = "
SELECT idforma, descripcion
  FROM formas_pago
  where 
  estado = 1
  AND anticipo = 'N'
ORDER BY descripcion;

 ";


    // valor seleccionado
    if (isset($_POST['idforma'])) {
        $value_selected = htmlentities($_POST['idforma']);
    } else {
        $value_selected = htmlentities($rs->fields['idforma']);
    }

    // parametros
    $parametros_array = [
            'nombre_campo' => 'idforma',
            'id_campo' => 'idforma',

            'nombre_campo_bd' => 'descripcion',
            'id_campo_bd' => 'idforma',

            'value_selected' => $value_selected,

            'pricampo_name' => 'seleccionar...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => ' onchange="forma_pago_datos(this.value);" ',
            'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);


    ?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto pago *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="monto_pago" id="monto_pago" value="<?php  if (isset($_POST['monto_pago'])) {
        echo intval($_POST['monto_pago']);
    } else {
        echo intval($total_abonar_acum);
    }?>" placeholder="Monto pago" class="form-control" required />                    
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group" id="idbanco_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Banco Cliente *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta = "
select 
 idbanco, nombre as banco, estado
from bancos
  where 
  estado = 1
  and caja = 'N'
ORDER BY nombre asc;

 ";


    // valor seleccionado
    if (isset($_POST['idbanco'])) {
        $value_selected = htmlentities($_POST['idbanco']);
    } else {
        $value_selected = htmlentities($rs->fields['idbanco']);
    }

    // parametros
    $parametros_array = [
            'nombre_campo' => 'idbanco',
            'id_campo' => 'idbanco',

            'nombre_campo_bd' => 'banco',
            'id_campo_bd' => 'idbanco',

            'value_selected' => $value_selected,

            'pricampo_name' => 'seleccionar...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => '  ',


    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);


    ?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group" id="idbanco_propio_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Banco Destino *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
select 
 idbanco, nombre as banco, estado
from bancos
  where 
  estado = 1
  and caja = 'N'
ORDER BY nombre asc;

 ";


    // valor seleccionado
    if (isset($_POST['idbanco_propio'])) {
        $value_selected = htmlentities($_POST['idbanco_propio']);
    } else {
        $value_selected = htmlentities($rs->fields['idbanco_propio']);
    }

    // parametros
    $parametros_array = [
            'nombre_campo' => 'idbanco_propio',
            'id_campo' => 'idbanco_propio',

            'nombre_campo_bd' => 'banco',
            'id_campo_bd' => 'idbanco',

            'value_selected' => $value_selected,

            'pricampo_name' => 'seleccionar...',
            'pricampo_value' => '',
            'style_input' => 'class="form-control"',
            'acciones' => '  ',


    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);


    ?>
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group" id="cheque_nro_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cheque Nro. </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cheque_nro" id="cheque_nro" value="<?php  if (isset($_POST['cheque_nro'])) {
        echo htmlentities($_POST['cheque_nro']);
    } else {
        echo htmlentities($rs->fields['cheque_nro']);
    }?>" placeholder="Cheque Nro" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="fecha_emision_box" <?php if ($_POST['idforma'] != 2 && $_POST['idforma'] != 10) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Emision *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
     <input type="date" name="fecha_emision" id="fecha_emision" value="<?php  if (isset($_POST['fecha_emision'])) {
         echo htmlentities($_POST['fecha_emision']);
     } else {
         echo htmlentities($rs->fields['fecha_emision']);
     }?>" placeholder="Fecha Emision" class="form-control"  />                   
    </div>
</div> 

<div class="col-md-6 col-sm-6 form-group" id="fecha_vencimiento_box" <?php if ($_POST['idforma'] != 2 && $_POST['idforma'] != 10) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Vencimiento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
     <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="<?php  if (isset($_POST['fecha_vencimiento'])) {
         echo htmlentities($_POST['fecha_vencimiento']);
     } else {
         echo htmlentities($rs->fields['fecha_vencimiento']);
     }?>" placeholder="Fecha Vencimiento" class="form-control"  />                   
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="transfer_nro_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Transfer Nro. </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="transfer_nro" id="transfer_nro" value="<?php  if (isset($_POST['transfer_nro'])) {
        echo htmlentities($_POST['transfer_nro']);
    } else {
        echo htmlentities($rs->fields['transfer_nro']);
    }?>" placeholder="Transfer Nro" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="boleta_nro_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Boleta de Deposito Nro. </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="boleta_nro" id="boleta_nro" value="<?php  if (isset($_POST['boleta_nro'])) {
        echo htmlentities($_POST['boleta_nro']);
    } else {
        echo htmlentities($rs->fields['boleta_nro']);
    }?>" placeholder="Boleta Nro" class="form-control"  />                    
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group" id="tarjeta_boleta_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Voucher Tarjeta Nro. </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="tarjeta_boleta" id="tarjeta_boleta" value="<?php  if (isset($_POST['tarjeta_boleta'])) {
        echo htmlentities($_POST['tarjeta_boleta']);
    } else {
        echo htmlentities($rs->fields['tarjeta_boleta']);
    }?>" placeholder="Voucher tarjeta Nro" class="form-control"  />                    
    </div>
</div>


<div class="clearfix"></div>
<BR />
    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="button" class="btn btn-default" onMouseUp="agrega_fpag();" ><span class="fa fa-plus"></span> Agregar</button>
        </div>
    </div>    
    
  
    
 
         
           
        
<br /><hr /><br />


<strong>Formas de Pago:</strong>
<div id="carrito_fpag_cuentas"><?php require_once("anticipos_car_fpag.php"); ?></div>

<?php


$ano = date("Y");
    // busca si existe algun registro
    $buscar = "
Select idsuc, numfac as mayor 
from lastcomprobantes 
where 
idsuc=$factura_suc 
and pe=$factura_pexp 
and idempresa=$idempresa 
order by ano desc 
limit 1";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //$maxnfac=intval(($rsfactura->fields['mayor'])+1);
    // si no existe inserta
    if (intval($rsfactura->fields['idsuc']) == 0) {
        $consulta = "
    INSERT INTO lastcomprobantes
    (idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
    numhoja, hojalevante, idempresa) 
    VALUES
    ($factura_suc, 0, 0, NULL, 0, NULL, 0, $ano, $factura_pexp, NULL, 
    NULL, 0, '', $idempresa)
    ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    // busca los proximos numeros de factura y recibo
    $buscar = "
Select max(numfac) as mayor, max(numrec) as mayorrec
from lastcomprobantes 
where 
idsuc=$factura_suc 
and pe=$factura_pexp 
and idempresa=$idempresa 
order by ano desc 
limit 1";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    $maxnrec = intval(($rsfactura->fields['mayorrec']) + 1);
    $recibo = agregacero($factura_suc, 3).'-'.agregacero($factura_pexp, 3).'-'.agregacero($maxnrec, 7);
    $recibo_completo = $recibo;


    ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Recibo *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="recibo" id="recibo" value="<?php  echo $recibo_completo; ?>" placeholder="Recibo" class="form-control"   />                    
    </div>
</div>
<?php if ($rsprefcaj->fields['fecha_en_recibo'] == 'S') {?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="date" name="fecha_recibo" id="fecha_recibo" value="<?php echo date("Y-m-d"); ?>" placeholder="Fecha" class="form-control"    />                    
    </div>
</div>
<?php } ?>

<div class="clearfix"></div>
<br /><br />


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='<?php echo $link_btn_cancel; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
  
 <?php } ?> 
  
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
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                   <h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
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
