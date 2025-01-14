 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");

$idpedido = intval($_GET['idpedido']);

$consulta = "SELECT *  FROM preferencias_caja WHERE  idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$usar_oc = trim($rsprefcaj->fields['usa_orden_compra']);
$obligar_oc = trim($rsprefcaj->fields['obliga_orden_compra']);

$consulta = "
select idforma, idformapago_set 
from formas_pago 
where 
estado <> 6
";
$rsfpagset = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
while (!$rsfpagset->EOF) {
    $idforma = $rsfpagset->fields['idforma'];
    $idformapago_set = $rsfpagset->fields['idformapago_set'];
    $formapag[$idforma] = $idformapago_set;
    $rsfpagset->MoveNext();
}

$json_fpag = json_encode($formapag, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
//print_r($json_fpag);exit;

?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function auto_refresco_pedidos(){
    setInterval(function(){ actualizar_pedidos(); }, 60000); // 60000 = 60 segundos
}
function rendido(id,tipo){
    var idcanal = $("#idcanal").val();    
    var idsucursal = $("#idsucu").val();    
    var direccionurl='central_pedidos_ajx.php';    
    
    var parametros = {
      "idcanal"       : idcanal,
      "idsucursal"    : idsucursal,
      "rendir"        : id,
      "tipo"        : tipo
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 15000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#pedidos_div").html('Actualizando...');    
            $(".audio")[0].pause();            
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#pedidos_div").html(response);
                // si hay pedidos sin notificar
                if($("#notificado").val() == 'N'){
                    $(".audio")[0].play();
                }
                
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
    
}
function actualizar_pedidos(){
    var idcanal = $("#idcanal").val();    
    var idsucursal = $("#idsucu").val();    
    var direccionurl='central_pedidos_ajx.php';    
    var parametros = {
      "idcanal"       : idcanal,
      "idsucursal"    : idsucursal,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 15000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#pedidos_div").html('Actualizando...');    
            $(".audio")[0].pause();            
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#pedidos_div").html(response);
                // si hay pedidos sin notificar
                if($("#notificado").val() == 'N'){
                    $(".audio")[0].play();
                }
                
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}
function cobrar_pedido(idpedido){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html('Cobrar Pedido #'+idpedido);
    var direccionurl='central_pedidos_ped.php';    
    var parametros = {
      "idpedido"   : idpedido
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');            
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#modal_cuerpo").html(response);
                $(".audio")[0].pause();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}
function cobrar_pedido2(idpedido,obj){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html('Cobrar Pedido #'+idpedido);
    var direccionurl='central_pedidos_ped.php';    
    var parametros = {
      "idpedido"   : idpedido
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');            
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#modal_cuerpo").html(response);
                $(".audio")[0].pause();
                $("#idcliente").val(obj.idcliente);
                $("#idsucursal_clie").val(obj.idsucursal_clie);
                $("#ruc").val(obj.ruc);
                $("#cliente").val(obj.razon_social);
                $("#nombres").val(obj.nombre_ruc);
                $("#apellidos").val(obj.apellido_ruc);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}
function condicion_factura(condicion){
    if(condicion == 1){
        $("#venfact_box").hide(); 
    }else{
        $("#venfact_box").show(); 
    }
}
function registrar_venta(idpedido,tipo){
    // recibe parametros
    var errores='';
    var obligar='<?php echo $obliga_adicional?>';
    var adicional='';
    var cual=0;
    var tipodocu=tipo; // ticket o factura
    var mpago=parseInt($("#mediopago").val());
    var observacion=$("#observacion").val();
    //var idpedido = $("#idpedido").val();
    var idvendedor = $("#idvendedor").val();
    var chapa = $("#chapa").val();
    var idadherente=0;
    var idservcom = 0;
    var iddeposito = $("#iddeposito").val();
    var idcanal= $("#canal").val();
    var idmotorista = $("#idmotorista").val();
    var domicilio = $("#domicilio").val();
    var vencimiento_factura = $("#vencimiento_factura").val();
    
    
    if (isNaN(mpago)){
        cual='';
    } else {
        cual=(mpago);
    }
    if (cual==''){
        cual=9;
    }
    //alert(cual);
    var totalventa= $("#totalventa_real").val();
    if (isNaN(totalventa)){
        totalventa=0;
    }
    var montorecibido = parseFloat($("#montorecibido").val());
    var efectivo=0;
    var tarjeta=0;
    var tipotarjeta=0;
    var montocheque=0;
    var numcheque='';
    var vuelto=parseInt($("#vuelto").val());//solo referencial
    if (isNaN(vuelto)){
        vuelto=0;
    }
    var idzona = $("#idzona").val();
    var pref1 = $("#fac_suc").val();
    var pref2 = $("#fac_pexp").val();
    var fact = $("#fac_nro").val();
    var mesa = $("#mesa").val();
    var cliente = 0;
    cliente=$("#idcliente").val();
    if (cliente==''){
        cliente = $("#occliedefe").val();
    }
    var idsucursal_clie=$("#idsucursal_clie").val();

    var delivery=$("#delioc").val();
    if (isNaN(delivery)){
        delivery=0;
    }
    var cheque_numero = $("#cheque_numero").val();
    if (isNaN(cheque_numero)){
        cheque_numero=0;
    }
    var adicional=cheque_numero;
    var banco=$("#banco").val();
    if (isNaN(banco)){
        banco=0;
    }
    /*var adicional=$("#adicional1").val();
    if (isNaN(adicional)){
        adicional=0;
    }*/

    var llevapos=$("#llevapos").val();
    var cambiopara=$("#cambiopara").val();
    var obsdel=$("#observa").val();
    var condi=$("#condventa").val();
    var orden_numero="";
    var motivodesc = $("#motivo_descuento").val();
    var descuento = $("#descuento").val();
    if (isNaN(descuento)){
        var descuento = '0';
        var motivodesc ='';
    } else {
        if (parseFloat(descuento) > 0 && motivodesc==''){
            errores=errores+'Debe indicar motivo del descuento.\n';
            
        }
    }
    <?php if ($usar_oc == 'S') { ?>
        var orden_numero=$("#orden_compra_num").val();
    <?php } ?>
    //Comprobar disponible de productos  total de venta
    /*if (totalventa==0){
        errores=errores+'Debe agregar al menos un producto para vender. \n';
    }*/
    if(tipo == 8){
        $("#rghbtn").hide();
    }
    
    var idcanalventa = $("#idcanalventa").val();
    var iddenominaciontarjeta = $("#iddenominaciontarjeta").val();

    
    if (errores==''){
        
        $("#terminar").hide();
        //alert('enviar');
        if(tipo == 2){
            fact='';
        }
        
        // INICIO REGISTRAR VENTAS //
        //alert(cual);
        var parametros = {
            "pedido"           : idpedido,
            "idzona"           : idzona, // zona costo delivery
            "idadherente"      : idadherente,
            "idservcom"        : idservcom, // servicio comida
            "banco"            : banco,
            "adicional"        : adicional, // numero de cheque, tarjeta, etc
            "condventa"        : condi, // credito o contado
            "mediopago"        : cual, // forma de pago
            "fac_suc"          : pref1,
            "fac_pexp"         : pref2,
            "fac_nro"          : fact,
            "domicilio"        : domicilio, // codigo domicilio
            "llevapos"         : llevapos,
            "cambiode"         : cambiopara,
            "observadelivery"  : obsdel,
            "observacion"      : observacion,
            "mesa"             : 0,
            "canal"            : idcanal,
            "fin"              : 3,
            "idcliente"        : cliente,
            "idsucursal_clie"  : idsucursal_clie,
            "monto_recibido"   : montorecibido,
            "descuento"        : descuento,
            "motivo_descuento" : motivodesc,
            "chapa"            : chapa,
            "montocheque"      : montocheque,
            "idvendedor"       : idvendedor,
            "iddeposito"       : iddeposito,
            "idmotorista"      : idmotorista,
            "primervto"        : vencimiento_factura,
            "idcanalventa"     : idcanalventa,
            "ocnumero"           : orden_numero,
            "iddenominaciontarjeta"           : iddenominaciontarjeta,
            "json"             : 'S' 
            
            
        };
        
       $.ajax({
                data:  parametros,
                url:   'registrar_venta.php',
                type:  'post',
                beforeSend: function () {
                    $("#botones_venta_msg").html("<br /><br />Registrando, favor aguarde...<br /><br />");
                    $("#botones_venta").hide();
                    $("#botones_venta_msg").show();
                },
                success:  function (response) {
                    //alert(response);
                    //$("#modal_cuerpo").html(response);
                    //$("#carrito").html(response);
                    $("#botones_venta").show();
                    $("#botones_venta_msg").hide();
                    if(IsJsonString(response)){
                        var obj = jQuery.parseJSON(response);
                         //borra_carrito();
                         <?php $script = "script_central_impresion.php";?>
                        if(obj.error == ''){
                                document.body.innerHTML='<meta http-equiv="refresh" content="0; url=<?php echo $script?>?tk='+tipo+'&clase=1&v='+obj.idventa+'<?php echo $redirbus2; ?>&modventa=7">';
                        }else{
                            //alertar_redir('NO SE REGISTRO LA VENTA',obj.error,'error','ACEPTAR','central_pedidos.php');
                            alerta_modal('No se registro la venta',nl2br(obj.error));
                        }
                    }else{
                        alert(response);
                        $("#modal_cuerpo").html(response);
                    }
                }
        });
        
        // FIN REGISTRAR VENTAS //
        
        
        
        
    } else {
        $("#terminar").show();
        //document.getElementById('montorecibido').focus();
        //alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');    
        alerta_modal('No se registro la venta',errores);
    }

}
function detalle_pedido(idpedido){
    var direccionurl='central_pedidos_deta_venta.php';    
    var parametros = {
      "idpedido"  : idpedido
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_titulo").html('Detalle del Pedido');    
            $("#modal_cuerpo").html('Cargando...');            
            $('#modal_ventana').modal('show');    
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $(".audio")[0].pause();
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function transfer_fran(idpedido){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html('Transferir Pedido #'+idpedido);
    var direccionurl='central_pedidos_fran.php';    
    var parametros = {
      "idpedido"   : idpedido
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');            
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#modal_cuerpo").html(response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function transfer_franquicia(idpedido){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html('Transferir Pedido #'+idpedido);
    var idfranquicia = $("#idfranquicia").val();
    var direccionurl='central_pedidos_fran_transf.php';    
    var parametros = {
      "idpedido"       : idpedido,
      "idfranquicia"   : idfranquicia,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');            
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        // hacer algo
                        //$("#monto_abonar").val(obj.saldo_factura);
                        $("#modal_cuerpo").html('ENVIO EXITOSO!!!<BR /> PEDIDO NRO.: '+obj.idpedidoexterno);
                        //$('#modal_ventana').modal('show');
                        
                    }else{
                        alert('Errores: '+obj.errores);    
                        $("#modal_cuerpo").html(nl2br(obj.errores));
                        //$("#error_box_msg").html(nl2br(obj.errores));
                        //$("#error_box").show();
                    }
                }else{
                    alert(response);
                    $("#modal_cuerpo").html(response);
                }
                
                
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function transfer_suc(idpedido){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html('Transferir Pedido #'+idpedido);
    var direccionurl='central_pedidos_suc.php';    
    var parametros = {
      "idpedido"   : idpedido
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');            
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#modal_cuerpo").html(response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function transfer_sucursal(idpedido){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html('Transferir Pedido #'+idpedido);
    var idsucursal = $("#idsucursal_ped").val();
    var direccionurl='central_pedidos_suc_transf.php';    
    var parametros = {
      "idpedido"       : idpedido,
      "idsucursal"   : idsucursal,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');            
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        // hacer algo
                        //$("#monto_abonar").val(obj.saldo_factura);
                        $("#modal_cuerpo").html('TRANSFERIDO A SUCURSAL EXITOSAMENTE!!!<BR />');
                        actualizar_pedidos();
                        //$('#modal_ventana').modal('show');
                        
                    }else{
                        alert('Errores: '+obj.errores);    
                        $("#modal_cuerpo").html(nl2br(obj.errores));
                        //$("#error_box_msg").html(nl2br(obj.errores));
                        //$("#error_box").show();
                    }
                }else{
                    alert(response);
                    $("#modal_cuerpo").html(response);
                }
                
                
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function alerta_modal(titulo,mensaje){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html(titulo);
    $("#modal_cuerpo").html(mensaje);    
}
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}
function borrar(idprod,idventacab,txt){
    var codigo_borra = $("#codigo_borra_"+idventacab).val();
    var parametros = {
        "idprod" : idprod,
        "idtmpventares_cab" : idventacab,
        "cod" : codigo_borra
    };
    if(window.confirm("Esta seguro que desea borrar '"+txt+"'?")){    
        $.ajax({
            data:  parametros,
            url:   'borra_prod_ped_new.php',
            type:  'post',
            beforeSend: function () {
                $("#modal_cuerpo").html('Cargando...');    
            },
            success:  function (response) {
                if(response == 'OK'){
                    detalle_pedido(idventacab);
                }else{
                    $("#modal_cuerpo").html(response);    
            
                }    
            }
        });
    }
}
function borrar_item(idventatmp,idventacab,txt){
    var codigo_borra = $("#codigo_borra_"+idventacab).val();
    var parametros = {
        "idventatmp" : idventatmp,
        "idtmpventares_cab" : idventacab,
        "cod" : codigo_borra
    };
    if(window.confirm("Esta seguro que desea borrar '"+txt+"'?")){    
        $.ajax({
            data:  parametros,
            url:   'borra_prod_ped_new.php',
            type:  'post',
            beforeSend: function () {
                $("#modal_cuerpo").html('Cargando...');    
            },
            success:  function (response) {
                if(response == 'OK'){
                    detalle_pedido(idventacab);
                }else{
                    $("#modal_cuerpo").html(response);    
            
                }
            }
        });
    }
}
function agrega_carrito_pag(idpedido){
    var idforma_mixto_monto = $("#idforma_mixto_monto").val();
    var idforma_mixto = $("#idforma_mixto").val();
    var iddenominaciontarjeta_mixsel = $("#iddenominaciontarjeta_mixsel").val();
    var banco_mixsel = $("#banco_mixsel").val();
    var cheque_numero_mixsel = $("#cheque_numero_mixsel").val();
    
    var parametros = {
            "idformapago"               : idforma_mixto,
            "monto_forma"               : idforma_mixto_monto,
            "idpedido"                  : idpedido,
            "iddenominaciontarjeta"    : iddenominaciontarjeta_mixsel,
            "banco"                     : banco_mixsel,
            "cheque_numero"             : cheque_numero_mixsel,
            "accion"                    : 'add',
    };
    $.ajax({
            data:  parametros,
            url:   'carrito_cobros_venta_cent.php',
            type:  'post',
            beforeSend: function () {
                $("#carrito_pagos_box").html("Cargando...");
            },
            success:  function (response) {
                $("#carrito_pagos_box").html(response);
                // ocultar el boton ticket segun corresponda
                if($("#obliga_facturar").val() == 'S'){
                    $("#ticket_btn").hide();
                }else{
                    $("#ticket_btn").show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
            }
        }).fail( function( jqXHR, textStatus, errorThrown ) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
        });
}
function borra_carrito_pag(idcarritocobrosventas,idpedido){
    var parametros = {
            "idcarritocobrosventas" : idcarritocobrosventas,
            "idpedido" : idpedido,
            "accion"      : 'del',
    };
    $.ajax({
            data:  parametros,
            url:   'carrito_cobros_venta_cent.php',
            type:  'post',
            beforeSend: function () {
                $("#carrito_pagos_box").html("Cargando...");
            },
            success:  function (response) {
                $("#carrito_pagos_box").html(response);
                // ocultar el boton ticket segun corresponda
                if($("#obliga_facturar").val() == 'S'){
                    $("#ticket_btn").hide();
                }else{
                    $("#ticket_btn").show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
            }
        }).fail( function( jqXHR, textStatus, errorThrown ) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
        });
}
function forma_pago(forma){
    //alert(forma);
    // pago mixto
    if(forma == 3){
        // mostrar carrito de pagos
        $("#pago_mixto").show();
        // ocultar monto global
        $("#monto_box").hide();
    
    }else{
        // ocultar carrito de pagos
        $("#pago_mixto").hide();
        // mostrar monto global
        $("#monto_box").show();
            
    }
    var json_fpag = '<?php echo $json_fpag; ?>';
    var obj = jQuery.parseJSON(json_fpag);
    var forma_str=forma.toString();
    var forma_pago_set = obj[forma];
    //alert(obj[forma]);
    // cheque
    if(forma_pago_set == 2){
        $("#iddenominaciontarjeta_box").hide();
        $("#banco_box").show();
        $("#cheque_numero_box").show();
    // tarjeta credito y debito
    }else if(forma_pago_set == 3 || forma_pago_set == 4){
        $("#iddenominaciontarjeta_box").show();
        $("#banco_box").hide();
        $("#cheque_numero_box").hide();
    }else{
        $("#iddenominaciontarjeta_box").hide();
        $("#banco_box").hide();
        $("#cheque_numero_box").hide();
    }
    
}
function forma_pago_mixsel(forma){
    var json_fpag = '<?php echo $json_fpag; ?>';
    var obj = jQuery.parseJSON(json_fpag);
    var forma_str=forma.toString();
    var forma_pago_set = obj[forma];
    //alert(obj[forma]);
    // cheque
    if(forma_pago_set == 2){
        $("#iddenominaciontarjeta_mixsel_box").hide();
        $("#banco_mixsel_box").show();
        $("#cheque_numero_mixsel_box").show();
    // tarjeta credito y debito
    }else if(forma_pago_set == 3 || forma_pago_set == 4){
        $("#iddenominaciontarjeta_mixsel_box").show();
        $("#banco_mixsel_box").hide();
        $("#cheque_numero_mixsel_box").hide();
    }else{
        $("#iddenominaciontarjeta_mixsel_box").hide();
        $("#banco_mixsel_box").hide();
        $("#cheque_numero_mixsel_box").hide();
    }
    
}
function json2array(json){
    var result = [];
    var keys = Object.keys(json);
    keys.forEach(function(key){
        result.push(json[key]);
    });
    return result;
}
function busca_cliente(idpedido){
    var direccionurl='busqueda_cliente_cent.php';        
    var parametros = {
      "m" : '1',      
      "idpedido" : idpedido    
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
function busca_cliente_res(tipo,idpedido){
    //alert(tipo);
    var ruc = $("#ruc_cent").val();
    var razon_social = $("#razon_social_cent").val();
    var fantasia = $("#fantasia_cent").val();
    var documento = $("#documento_cent").val();
    if(tipo == 'ruc'){
        razon_social = '';
        fantasia = '';
        documento = '';
        $("#razon_social_cent").val('');
        $("#fantasia_cent").val('');
        $("#documento_cent").val('');
    }
    if(tipo == 'razon_social'){
        ruc = '';
        fantasia = '';
        documento = '';
        $("#ruc_cent").val('');
        $("#fantasia_cent").val('');
        $("#documento_cent").val('');
    }
    if(tipo == 'fantasia'){
        ruc = '';
        razon_social = '';
        documento = '';
        $("#ruc_cent").val('');
        $("#razon_social_cent").val('');
        $("#documento_cent").val('');
    }
    if(tipo == 'documento'){
        ruc = '';
        razon_social = '';
        fantasia = '';
        $("#ruc_cent").val('');
        $("#razon_social_cent").val('');
        $("#fantasia_cent").val('');
    }
    var direccionurl='busqueda_cliente_res_cent.php';        
    var parametros = {
      "ruc"            : ruc,
      "razon_social"   : razon_social,
      "fantasia"          : fantasia,
      "documento"      : documento,
      "idpedido"       : idpedido
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
function seleccionar_item(id){
    var valor = $("#idsucursal_clie_"+id).val();
    if(IsJsonString(valor)){
        var obj = jQuery.parseJSON(valor);
        var idcliente = obj.idcliente;
        var idsucursal_clie = obj.idsucursal_clie;
        var idpedido = obj.idpedido;
        var ruc = obj.ruc;
        var razon_social = obj.razon_social;
        var nombres = obj.nombres;
        var apellidos = obj.apellidos;
        //$('#modal_ventana').modal('show');
        $("#modal_titulo").html('Cobrar Pedido #'+idpedido);
        var direccionurl='central_pedidos_ped.php';    
        var parametros = {
          "idpedido"   : idpedido
        };
        $.ajax({          
            data:  parametros,
            url:   direccionurl,
            type:  'post',
            cache: false,
            timeout: 3000,  // I chose 3 secs for kicks: 3000
            crossDomain: true,
            beforeSend: function () {
                $("#modal_cuerpo").html('Cargando...');            
            },
            success:  function (response, textStatus, xhr) {
                if(xhr.status === 200){
                    $("#modal_cuerpo").html(response);
                    $(".audio")[0].pause();
                    $("#idcliente").val(idcliente);
                    $("#idsucursal_clie").val(idsucursal_clie);
                    $("#ruc").val(ruc);
                    $("#cliente").val(razon_social);
                    $("#nombres").val(nombre);
                    $("#apellidos").val(apellido);
                    //$('#modal_ventana').modal('hide');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
            }
        }).fail( function( jqXHR, textStatus, errorThrown ) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
        });
        
        $("#idcliente").val(idcliente);
        $("#idsucursal_clie").val(idsucursal_clie);
    }else{
        alert(valor);    
    }
    
    // idcliente,razon_social,ruc,nombre,apellido
    /*$("#idcliente").val(idcliente);
    $("#ruc").val(ruc);
    $("#razon_social").val(razon_social);
    $("#nombres").val(nombre);
    $("#apellidos").val(apellido);
    $('#modal_ventana').modal('hide');*/
    
}
function agrega_cliente(idpedido){
        var direccionurl='cliente_agrega_cent.php';
        var parametros = {
              "new" : 'S',
              "idpedido" : idpedido,
       };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html(response);
                    if (document.getElementById('ruccliente')){
                        document.getElementById('ruccliente').focus();
                    }
                    $("#idpedido").html(idpedido);
                }
        });    
}

function registrar_cliente(idpedido){
    var ruc_add = $("#ruccliente").val();
    var nombres_add = $("#nombreclie").val();
    var apellidos_add = $("#apellidosclie").val();
    var razon_social_add = $("#rz1").val();
    var documento_add = $("#cedulaclie").val();
    var tipo_cliente_add = $("#ruccliente").val();
    
    if($('#r1').is(':checked')) { var idclientetipo=1; }
    if($('#r2').is(':checked')) { var idclientetipo=2; }
    
    if(idclientetipo == 1){
       var razon_social_add = nombres_add+' '+apellidos_add;
    }
    
    
        var direccionurl='cliente_agrega_cent.php';
        var parametros = {
            "new" : 'S',
            "MM_insert" : 'form1',
            "ruc" : ruc_add,
            "nombre" : nombres_add,
            "apellido" : apellidos_add,
            "documento" : documento_add,
            "razon_social" : razon_social_add,
            "idclientetipo" : idclientetipo,
            
            
       };
       $.ajax({
                data:  parametros,
                url:   direccionurl,
                type:  'post',
                beforeSend: function () {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html('Cargando...');
                },
                success:  function (response) {
                    $('#modal_ventana').modal('show');
                    $("#modal_titulo").html('Alta de Cliente');
                    $("#modal_cuerpo").html(response);
                    //{"ruc":"X","razon_social":"JUAN PEREZ","nombre_ruc":"JUAN","apellido_ruc":"PEREZ","idcliente":"341","valido":"S"}
                    if(IsJsonString(response)){
                        var obj = jQuery.parseJSON(response);
                        //alert(obj.error);
                        if(obj.valido == 'S'){
                            cobrar_pedido2(idpedido,obj);



                            /*var new_ruc = obj.ruc;
                            var new_rz = obj.razon_social;
                            var new_nom = obj.nombre_ruc;
                            var new_ape = obj.apellido_ruc;
                            var idcli = obj.idcliente;
                            $("#ruc").val(new_ruc);
                            $("#razon_social").val(new_rz);
                            $("#nombres").val(new_nom);
                            $("#apellidos").val(new_ape);
                            $("#idcliente").val(idcli);*/
                            //if(parseInt(idcli)>0){
                                //nclie(tipocobro,idpedido);
                                //selecciona_cliente(idcli,tipocobro,idpedido);
                            //}
                            //$('#modal_ventana').modal('hide');
                            
                        }else{
                            //$("#ruc").val(vruc);
                            //$("#razon_social").val('');
                            //alert(obj.errores);
                            agrega_cliente(idpedido);
                            alert(obj.errores);
                        }
                    }else{
                        alert(response);
                    }
                }
        });    
}
function cambia(valor){
    if (valor==1){
        $("#nombreclie_box").show();
        $("#apellidos_box").show();
        $("#rz1").val("");
        $("#rz1_box").hide();
        $("#cedula_box").show();
    }
    if (valor==2){
        $("#nombreclie").val("");
        $("#apellidosclie").val("");
        $("#nombreclie_box").hide();
        $("#apellidos_box").hide();
        $("#rz1_box").show();
        $("#cedula_box").hide();
    }
    
}
function carga_ruc_h(idpedido){
    var vruc = $("#ruccliente").val();
    var txtbusca="Buscando...";
    var tipocobro=$("#mediopagooc").val();
    if(txtbusca != vruc){
    var parametros = {
            "ruc" : vruc
    };
    $.ajax({
            data:  parametros,
            url:   '../ruc_extrae.php',
            type:  'post',
            beforeSend: function () {
                $("#ruccliente").val('Buscando...');
            },
            success:  function (response) {
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    //alert(obj.error);
                    if(obj.errores == ''){
                        var new_ruc = obj.ruc;
                        var new_rz = obj.razon_social;
                        var new_nom = obj.nombre_ruc;
                        var new_ape = obj.apellido_ruc;
                        var idcli = obj.idcliente;
                        $("#ruccliente").val(new_ruc);
                        $("#nombreclie").val(new_nom);
                        $("#apellidos").val(new_ape);
                        $("#rz1").val(new_rz);
                        if(obj.tipo_persona == 'FISICA'){
                            $("#r1").click();
                        }
                        if(obj.tipo_persona == 'JURIDICA'){
                            $("#r2").click();
                        }
                        var obj_json = '{"idcliente":"'+obj.idcliente+'","idsucursal_clie":"'+obj.idsucursal_clie+'"}';
                        if(parseInt(idcli)>0){
                            //nclie(tipocobro,idpedido);
                            selecciona_cliente(obj_json,tipocobro,idpedido);
                        }
                    }else{
                        $("#ruccliente").val(vruc);
                        $("#nombreclie").val('');
                        $("#apellidos").val('');
                        $("#rz1").val('');
                    }
                }else{
    
                    alert(response);
            
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if(jqXHR.status == 404){
                    alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
                }else if(jqXHR.status == 0){
                    alert('Se ha rechazado la conexiÃ³n.');
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
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function reimpimir_comp(id){
    $("#reimprimebox").html('<iframe src="impresor_ticket_reimp.php?idped='+id+'" style="width:310px; height:500px;"></iframe>');        
}
function acceso_denegado_cambiocanal(){
    alert('Acceso Denegado!\nLa administración desactivó el permiso para realizar esta acción.');
}
function acceso_denegado_editar(){
    alert('Acceso Denegado!\nLa administración desactivó el permiso para realizar esta acción.');
}
</script>
  </head>

  <body class="nav-md" onLoad="auto_refresco_pedidos();">
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
                    <h2>Central de Pedidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Canal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idcanal, canal
FROM canal
where
(idcanal = 1 or idcanal = 3) 
order by canal asc
 ";

// valor seleccionado
if (isset($_GET['idcanal'])) {
    $value_selected = htmlentities($_GET['idcanal']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idcanal',
    'id_campo' => 'idcanal',

    'nombre_campo_bd' => 'canal',
    'id_campo_bd' => 'idcanal',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODOS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_GET['idsucu'])) {
    $value_selected = htmlentities($_GET['idsucu']);
} else {
    $value_selected = $idsucursal;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => 'T',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>
                      
<?php if ($idpedido > 0) { ?>                      
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Pedido *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="app" id="app" value="<?php echo intval($idpedido); ?>" placeholder="Pedido" class="form-control" readonly />                    
    </div>
</div>
<?php } ?>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="button" class="btn btn-default" onMouseUp="actualizar_pedidos();" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

<br />


<hr />
                  
<p><a href="#" class="btn btn-sm btn-default" onMouseUp="actualizar_pedidos();"><span class="fa fa-refresh"></span> Actualizar</a></p>


<br />
<div id="pedidos_div"><?php require_once("central_pedidos_ajx.php"); ?></div>
                <!-- AUDIO --> 
                <audio id="peor" class="audio" src="audio/dingdong.mp3" loop preload>
                </audio>
                <div class="clearfix"></div>
                <!-- AUDIO --> 

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
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="modal_titulo">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
                        ...
                        </div>
                        <div class="modal-footer"  id="modal_pie">
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
<script>
$('#modal_ventana').on('hidden.bs.modal', function (e) {
    //alert('cerrado');
    var parametros = {
            "all"         : 'S',
            "accion"      : 'del',
    };
    $.ajax({
            data:  parametros,
            url:   'carrito_cobros_venta_cent.php',
            type:  'post',
            beforeSend: function () {
                $("#carrito_pagos_box").html("Cargando...");
            },
            success:  function (response) {
                $("#carrito_pagos_box").html(response);
                //alert(response);
                // ocultar el boton ticket segun corresponda
                if($("#obliga_facturar").val() == 'S'){
                    $("#ticket_btn").hide();
                }else{
                    $("#ticket_btn").show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if(jqXHR.status == 404){
                    alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
                }else if(jqXHR.status == 0){
                    alert('Se ha rechazado la conexiÃ³n.');
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
});
</script>
  </body>
</html>
