 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "278";
require_once("includes/rsusuario.php");

require_once("includes/funciones_cobros.php");

// mudar estas funciones a funciones_cobros
function validar_aplicacion_anticipo($parametros_array)
{
    global $conexion;
    global $ahora;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (intval($parametros_array['idpago_afavor']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpago_afavor no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idcta']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpago_afavor no puede ser cero o nulo.<br />";
    }
    if (floatval($parametros_array['monto_aplicar']) <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_aplicar no puede ser cero o negativo.<br />";
    }
    // validar que el anticipo tenga saldo

    // que el monto a aplicar no supere el saldo del anticipo

    // que el monto a aplicar no supere el saldo de la cuenta


    $res = [
        'valido' => 'S',
        'errores' => $errores
    ];
    return $res;
}
function registrar_aplicacion_anticipo($parametros_array)
{
    global $conexion;
    global $ahora;

    // recibe parametros
    $idpago_afavor = antisqlinyeccion($parametros_array['idpago_afavor'], "int"); // anticipo que se aplicara
    $idcta = antisqlinyeccion($parametros_array['idcta'], "int"); // cuenta a la que ese va a aplicar el anticipo
    $monto_aplicar = antisqlinyeccion($parametros_array['monto_aplicar'], "float"); // monto a aplicar que puede aplicarse parcialmente

    $consulta = "
    select * 
    from cuentas_clientes_pagos_cab 
    where
    estado <> 6 
    and idpago_afavor = $idpago_afavor
    limit 1
    ";


    // genera el detalle cuentas_clientes_pagos

    // genera el detalle cuentas_clientes_pagos_det

    // actualiza los saldos de factura en cuentas clientes (con la funcion, ver si hay)

    // actualiza los saldos de pagos a favor (con la funcion, ver si hay)

    // actualiza la linea de credito del cliente (con la funcion, ver si hay)


    return $res;
}


//Comprobar apertura de caja
$parametros_caja_new = [
    'idcajero' => $idusu,
    'idsucursal' => $idsucursal,
    'idtipocaja' => 1
];
$res_caja = caja_abierta_new($parametros_caja_new);
$idcaja = intval($res_caja['idcaja']);
$idcaja_old = intval($idcaja);
if ($idcaja_old == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}


$consulta = "
select fecha_en_recibo from preferencias_caja limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




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
    header("location: cobro_cuentas.php");
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
if ($idcliente == 0) {
    header("location: cobro_cuentas.php");
    exit;
}

// actualizar estados en cuentas_clientes para este cliente
$consulta = "
update cuentas_clientes 
set 
estado = 1 
where 
idcliente = $idcliente 
and saldo_activo > 0 
and estado <> 6
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// actualizar linea del cliente
actualiza_saldos_clientes($idcliente, $idadherente, $idserviciocom);
/*$consulta="
update cliente
set
saldo_sobregiro = COALESCE(linea_sobregiro,0)-COALESCE((select sum(saldo_activo) from cuentas_clientes where idcliente = cliente.idcliente and estado = 1),0)
where
idcliente = $idcliente
";
$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/

// rellena cuotas por si tiene version antigua en registrar venta
$consulta = "
INSERT INTO cuentas_clientes_det
(idcta, nro_cuota, vencimiento, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota, fch_ult_pago, fch_cancela, dias_atraso, dias_pago, dias_comb, estado) 
select cuentas_clientes.idcta, 1, date(cuentas_clientes.registrado_el), cuentas_clientes.deuda_global, 0, 0, cuentas_clientes.saldo_activo, NULL, NULL, 0, 0, 0, 1
from cuentas_clientes
where 
estado <> 6
and idcta not in (select idcta from cuentas_clientes_det)
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



$ano = date("Y");

// busca si existe algun registro, no usar count xq sino devuelve 1 el recordcount
$buscar = "
Select *
from lastcomprobantes 
where 
idsuc=$factura_suc 
and pe=$factura_pexp 
and idempresa=$idempresa 
order by ano desc 
limit 1";
$rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$total_reg = intval($rsfactura->recordCount());
// si no hay registros inserta
if ($total_reg == 0) {
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

    $comentario = trim($_POST['obs']);

    $recibo_completo = trim($_POST['recibo']);

    $fecha_recibo = trim($_POST['fecha_recibo']);
    if ($fecha_recibo != '') {
        $fecha_pago = $fecha_recibo;
    } else {
        $fecha_pago = date('Y-m-d');
    }




    $datos_ar = [
        'fecha_pago' => $fecha_pago,
        'registrado_por' => $idusu,
        'idcliente' => $idcliente,
        'idcaja_new' => $idcaja_new,
        'idcaja_old' => $idcaja_old,
        'registrado_el' => $ahora,
        'recibo' => $recibo_completo,  // buscar en last comprobantes
        'sucursal' => $idsucursal
    ];

    // valida pago
    $res = valida_pago_cuentacliente($datos_ar);
    if ($res['valido'] == 'N') {
        $valido = $res['valido'];
        $errores .= nl2br($res['errores']);
    }


    $consulta = "
    select sum(tmp_carrito_cobros_fpag.monto_pago) as total_cobrado
    from tmp_carrito_cobros_fpag 
    where 
    idcliente = $idcliente
    and registrado_por = $idusu
    ";
    $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // genera cabecera de caja
    $parametros_caja['idcaja'] = $idcaja_new;
    $parametros_caja['idtipocajamov'] = 3; // Cobro de Facturas a Credito
    $parametros_caja['tipomovdinero'] = 'E'; // E: entrada S: salida
    $parametros_caja['monto_movimiento'] = $rspag->fields['total_cobrado'];
    $parametros_caja['idmoneda'] = 1; // guaranies
    $parametros_caja['fechahora_mov'] = date("Y-m-d H:i:s");
    $parametros_caja['registrado_por'] = $idusu;

    // parametros para movimiento de caja gestion
    $consulta = "
    select tmp_carrito_cobros_fpag.*, formas_pago2.idforma_old
    from tmp_carrito_cobros_fpag 
    inner join formas_pago2 on formas_pago2.idforma = tmp_carrito_cobros_fpag.idformapago 
    inner join formas_pago on formas_pago.idforma = formas_pago2.idforma_old
    where 
    idcliente = $idcliente
    and registrado_por = $idusu
    ";
    $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recorre e inserta en caja gestion
    $i = 1;
    while (!$rspag->EOF) {

        $idformapago = $rspag->fields['idformapago'];
        $monto_pago = $rspag->fields['monto_pago'];
        $fecha_emision = $rspag->fields['fecha_emision'];
        $fecha_vencimiento = $rspag->fields['fecha_vencimiento'];
        $idbanco = $rspag->fields['idbanco'];
        $nrochq = $rspag->fields['nrochq'];
        // cheque vista
        if ($idformapago == 2) {
            $fecha_vencimiento = $fecha_emision;
        }

        // campos segun forma de cobro
        $parametros_caja['detalles'][$i]['monto_movimiento'] = $monto_pago;
        $parametros_caja['detalles'][$i]['idformapago'] = $idformapago;
        $parametros_caja['detalles'][$i]['fecha_vencimiento'] = $fecha_vencimiento;
        $parametros_caja['detalles'][$i]['fecha_emision'] = $fecha_emision;
        $parametros_caja['detalles'][$i]['idbanco'] = $idbanco;
        $parametros_caja['detalles'][$i]['idtipopersona_titular'] = '';
        $parametros_caja['detalles'][$i]['idpaisdoc_titular'] = '';
        $parametros_caja['detalles'][$i]['idtipodoc_titular'] = '';
        $parametros_caja['detalles'][$i]['idtipodoc_titular'] = '';
        $parametros_caja['detalles'][$i]['nrodoc_titular'] = '';
        $parametros_caja['detalles'][$i]['nomape_titular'] = '';
        $parametros_caja['detalles'][$i]['nrochq'] = $nrochq;


        $consulta = "
        SELECT * FROM cuentas where caja = 'S' and estado = 1 order by idcuenta asc limit 1
        ";
        $rsccon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $parametros_caja['detalles'][$i]['idcuentacon'] = intval($rsccon->fields['idcuenta']);


        $i++;
        $rspag->MoveNext();
    }
    $rspag->MoveFirst();



    //print_r($parametros_caja);exit;

    // validar movimiento de caja
    $res = caja_movimiento_valida($parametros_caja);
    if ($res['valido'] == 'N') {
        $valido = $res['valido'];
        $errores .= nl2br($res['errores']);
    }

    //echo $valido;exit;
    // si todo es correcto inserta
    if ($valido == "S") {


        // registra movimiento de caja gestion
        $idcajamov = caja_movimiento_registra($parametros_caja);
        if ($res['valido'] == 'N') {
            $valido = $res['valido'];
            $errores .= nl2br($res['errores']);
        }

        if ($idcajamov == 0) {
            echo "No se envio el movimiento de caja de gestion.";
            exit;
        }


        // registra pago
        $datos_ar['idcajamov'] = $idcajamov;
        $res = pagar_cuentacliente($datos_ar);
        $idcuentaclientepagcab = $res['idcuentaclientepagcab'];

        // recorre e inserta en movimiento de caja old
        while (!$rspag->EOF) {

            $fecha = $ahora;
            $medio_pago = $rspag->fields['idforma_old'];
            $total_cobrado = floatval($rspag->fields['monto_pago']);
            $chequenum = antisqlinyeccion($rspag->fields['nrochq'], "text");
            $banco = antisqlinyeccion($idbanco, "int");
            $factura = antisqlinyeccion('', "text");
            $recibo = antisqlinyeccion($recibo_completo, "text");
            $tickete = antisqlinyeccion('', "text");
            $estado = antisqlinyeccion(1, "text");
            $ruc = antisqlinyeccion('', "text");
            $tipo_pago = 1;
            $idempresa = 1;
            $sucursal = $idsucursal;
            $tipotarjeta = 'NULL';
            $codtransfer = antisqlinyeccion($rspag->fields['codtransfer'], "text");
            $montotarjeta = 0;
            $montocheque = 0;
            $montotransfer = 0;
            $efectivo = 0;
            if ($medio_pago == 1) { // efectivo
                $efectivo = $total_cobrado;
            }
            if ($medio_pago == 2) { // tcredito
                $montotarjeta = $total_cobrado;
                $tipotarjeta = 1;
            }
            if ($medio_pago == 4) { // tdebito
                $montotarjeta = $total_cobrado;
                $tipotarjeta = 2;
            }
            if ($medio_pago == 5) { // cheque
                $montocheque = $total_cobrado;
            }
            if ($medio_pago == 6) { // transfer
                $montotransfer = $total_cobrado;
            }
            $cajero = $idusu;
            $fechareal = $ahora;
            $idventa = 0;
            $anulado_el = "NULL";
            $anulado_por = "NULL";
            $montovale = 0;
            $idpedido = 0;
            $idmesa = 0;
            $numtarjeta = 0;
            $vueltogs = 0;
            $delivery = 0;
            $idcaja = $idcaja_old;
            $rendido = "'S'";
            $fec_rendido = "NULL";
            $obs = "NULL";
            $reimpresofc = "NULL";

            $consulta = "
            insert into gest_pagos
            (fecha, medio_pago, total_cobrado, chequenum, banco, factura, recibo, tickete, estado, ruc, tipo_pago, idempresa, sucursal, efectivo, codtransfer, montotransfer, montocheque, cajero, fechareal, idventa, anulado_el, anulado_por, montovale, idpedido, idmesa, montotarjeta, numtarjeta, tipotarjeta, vueltogs, delivery, idcaja, rendido, fec_rendido, obs, reimpresofc, idcuentaclientepagcab,
            idtipocajamov,tipomovdinero)
            values
            ('$fecha', $medio_pago, $total_cobrado, $chequenum, $banco, $factura, $recibo, $tickete, $estado, $ruc, $tipo_pago, $idempresa, $sucursal, $efectivo, $codtransfer, $montotransfer, $montocheque, $cajero, '$fechareal', $idventa, $anulado_el, $anulado_por, $montovale, $idpedido, $idmesa, $montotarjeta, $numtarjeta, $tipotarjeta, $vueltogs, $delivery, $idcaja_old, $rendido, $fec_rendido, $obs, $reimpresofc, $idcuentaclientepagcab,
            3,'E'
            )
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
            select max(idpago) as idpago from gest_pagos where idcuentaclientepagcab = $idcuentaclientepagcab limit 1
            ";
            $rsmaxpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idpago_gest = $rsmaxpag->fields['idpago'];

            $consulta = "
            INSERT INTO gest_pagos_det
            (idpago, monto_pago_det, idformapago) 
            VALUES 
            ($idpago_gest, $total_cobrado, $medio_pago)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
            select max(idpagodet) as idpagodet from gest_pagos_det where idpago = $idpago_gest 
            ";
            $rsmax = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idpagodet = intval($rsmax->fields['idpagodet']);

            $consulta = "
            INSERT INTO gest_pagos_det_datos
            (idpagodet, idbanco, idbanco_propio, transfer_numero, tarjeta_boleta, cheque_numero, boleta_deposito, retencion_numero)
            VALUES 
            ($idpagodet, $banco, NULL, $codtransfer, NULL, $chequenum, NULL, NULL)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



            $rspag->MoveNext();
        }


        // recorre las facturas agregadas
        $consulta = "
        select *, 
        (select idsucursal_clie from cuentas_clientes where idcta = tmp_carrito_cobros.idcta) as idsucucliente,
        (select idventa from cuentas_clientes where idcta = tmp_carrito_cobros.idcta) as idventa
        from tmp_carrito_cobros 
        where 
        estado = 1 
        and idcliente = $idcliente
        and registrado_por = $idusu
        ";
        $rscarcob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // por cada factura
        while (!$rscarcob->EOF) {
            $idventa_recu = $rscarcob->fields['idventa'];
            $idsucrsalcliente = intval($rscarcob->fields['idsucucliente']);
            // busca si es una facturacion recurrente
            $consulta = "
            select * 
            from factura_recurrente 
            where 
            idventa = $idventa_recu 
            and estado <> 6 
            and saldo_cobrar_recu > 0
            ";
            $rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //echo $consulta;
            // si es recurrente
            if ($rsrec->fields['idventa'] > 0) {
                while (!$rsrec->EOF) {
                    $parametros_cobros = [];
                    $monto_facturado = $rsrec->fields['monto_facturado'];
                    $idoperacion = $rsrec->fields['idoperacion'];
                    $nro_cuota = $rsrec->fields['nro_cuota'];
                    $parametros_cobros = [
                        'idcuentaclientepagcab' => $idcuentaclientepagcab,
                        'idfacturarecurrente' => $idfacturarecurrente,
                        'idoperacion' => $idoperacion,
                        'nro_cuota' => $nro_cuota,
                        'monto_facturado' => $monto_facturado,
                        'fecha_pago' => $fecha_pago,
                        'idcliente' => $idcliente,
                    ];
                    // afecta saldos
                    paga_recurrente($parametros_cobros);
                    $rsrec->MoveNext();
                }
            }

            $rscarcob->MoveNext();
        }

        // borrar carritos de factura
        $consulta = "
        delete
        from tmp_carrito_cobros 
        where 
        estado = 1 
        and idcliente = $idcliente
        and registrado_por = $idusu
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // borrar carritos de cobros
        $consulta = "
        delete
        from tmp_carrito_cobros_fpag 
        where 
        idcliente = $idcliente
        and registrado_por = $idusu
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        if ($idcuentaclientepagcab > 0 && $comentario != '') {
            //Update de comentario
            $comentario = antisqlinyeccion($comentario, 'text');
            $update = "Update cuentas_clientes_pagos_cab set comentarios=$comentario where idcuentaclientepagcab=$idcuentaclientepagcab";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

        }


        header("location: cobro_cuentas_imp.php?id=".$idcuentaclientepagcab);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

/*
rellenar cuentas_clientes_det
INSERT INTO `cuentas_clientes_det`(`idcta`, `nro_cuota`, `vencimiento`, `monto_cuota`, `cobra_cuota`, `quita_cuota`, `saldo_cuota`, `fch_ult_pago`, `fch_cancela`, `dias_atraso`, `dias_pago`, `dias_comb`, `estado`)
select cuentas_clientes.idcta, 1, date(cuentas_clientes.registrado_el), cuentas_clientes.deuda_global, 0, 0, cuentas_clientes.saldo_activo, NULL, NULL, 0, 0, 0, 1
from cuentas_clientes
where
idcta not in (select idcta from cuentas_clientes_det)
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
///////////////////////////////// FACTURAS /////////////////////////////////
function agregar_cta_todas(){
    var direccionurl='cobro_cuentas_car_add.php';    
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
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
}
function agregar_cta(idcta){
    var monto_abonar = $("#idcta_"+idcta).val();
    var direccionurl='cobro_cuentas_car_add.php';    
    var parametros = {
      "MM_insert"    : "form1",
      "idcta"        : idcta,
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
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function agregar_cta_marcados(){
    var totitems = $("#totitems").val();
    var idcta = '';
    var cuentas = '';
    for(i=1;i<=totitems;i++){
        idcta=$("#ctaid_"+i).val();
        if ($("#ctaid_"+i).is(':checked')) {
            cuentas+=idcta+',';
        }
        
    }
    var direccionurl='cobro_cuentas_car_add.php';    
    var parametros = {
      "MM_insert"    : "MARCADOS",
      "idcliente"    : <?php echo $idcliente; ?>,
      "cuentas_csv"  : cuentas
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
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function actualiza_facturas_pend(idcliente){
    var direccionurl='cobro_cuentas_fact_pend.php';        
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
    var direccionurl='cobro_cuentas_car.php';        
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
        }
    });
}
function detalle_venta(idventa){
    var direccionurl='cobro_cuentas_deta_venta.php';    
    var parametros = {
      "idventa"        : idventa
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Detalle de la Factura');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $('#dialogobox').modal('show');
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
///////////////////////////////// FACTURAS /////////////////////////////////
    
function errores_ajax_jq(jqXHR, textStatus, errorThrown, tipo){
    // error
    if(tipo == 'error'){
        if(jqXHR.status == 404){
            alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
        }else if(jqXHR.status == 0){
            alert('Se ha rechazado la conexión.');
        }else{
            alert(jqXHR.status+' '+errorThrown);
        }
    // fail
    }else{
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
    }
}
    
///////////////////////////////// ANTICIPOS /////////////////////////////////
function agregar_ant_todas(){
    var direccionurl='cobro_cuentas_car_add_anticip.php';    
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
            $("#carrito_anticipo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        actualiza_carrito_anticipo(<?php echo $idcliente; ?>);
                    }else{
                        var titulo = 'Errores';
                        var mensaje = obj.errores;
                        alerta_modal(titulo,mensaje);
                        actualiza_carrito_anticipo(<?php echo $idcliente; ?>);    
                    }
                }else{
                    alert(response);
                    $("#carrito_anticipo").html(response);        
                }

            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
}
function agregar_ant(idanticipo){
    var monto_abonar = $("#idpago_afavor_"+idanticipo).val();
    var direccionurl='cobro_cuentas_car_add_anticip.php';    
    var parametros = {
      "MM_insert"    : "form1",
      "idanticipo"   : idanticipo,
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
            $("#carrito_anticipos").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        actualiza_carrito_anticipo(<?php echo $idcliente; ?>);
                    }else{
                        var titulo = 'Errores';
                        var mensaje = obj.errores;
                        alerta_modal(titulo,mensaje);
                        actualiza_carrito_anticipo(<?php echo $idcliente; ?>);    
                    }
                }else{
                    alert(response);
                    $("#carrito_anticipos").html(response);        
                }

            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function agregar_ant_marcados(){
    var totitems = $("#totitems").val();
    var idcta = '';
    var cuentas = '';
    for(i=1;i<=totitems;i++){
        idcta=$("#antid_"+i).val();
        if ($("#antid_"+i).is(':checked')) {
            cuentas+=idcta+',';
        }
        
    }
    var direccionurl='cobro_cuentas_car_add_anticip.php';    
    var parametros = {
      "MM_insert"    : "MARCADOS",
      "idcliente"    : <?php echo $idcliente; ?>,
      "anticipos_csv"  : cuentas
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#carrito_anticipos").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        actualiza_carrito_anticipo(<?php echo $idcliente; ?>);
                    }else{
                        var titulo = 'Errores';
                        var mensaje = obj.errores;
                        alerta_modal(titulo,mensaje);
                        actualiza_carrito_anticipo(<?php echo $idcliente; ?>);    
                    }
                }else{
                    alert(response);
                    $("#carrito_anticipos").html(response);        
                }

            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function actualiza_anticipos_pend(idcliente){
    var direccionurl='cobro_cuentas_ant_pend.php';        
    var parametros = {
      "idcliente" : idcliente          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#anticipos_pend").html('Actualizando...');                
        },
        success:  function (response) {
            $("#anticipos_pend").html(response);    
        }
    });
}
function actualiza_carrito_anticipo(idcliente){
    var direccionurl='cobro_cuentas_car_ant.php';        
    var parametros = {
      "idcliente" : idcliente          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#carrito_anticipos").html('Actualizando...');                
        },
        success:  function (response) {
            $("#carrito_anticipos").html(response);
            actualiza_anticipos_pend(idcliente);
        }
    });
}
function detalle_anticipo(idanticipo){
    var direccionurl='cobro_cuentas_deta_anticipo.php';    
    var parametros = {
      "idanticipo"        : idanticipo
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Detalle del Anticipo');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $('#dialogobox').modal('show');
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
///////////////////////////////// ANTICIPOS /////////////////////////////////
    
///////////////////////////////// FORMA PAGO /////////////////////////////////
function actualiza_carrito_fpag(idcliente){
    var direccionurl='cobro_cuentas_car_fpag.php';        
    var parametros = {
      "idcliente" : idcliente          
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
function alerta_modal(titulo,mensaje){
    $('#dialogobox').modal('show');
    $("#myModalLabel").html(titulo);
    $("#modal_cuerpo").html(mensaje);

    
}


    
    /*
1;"EFECTIVO            "
2;"CHEQUE              "
3;"A CONFIRMAR         "
4;"CREDITO / AJUSTE    "
5;"TRANSFERENCIA       "
6;"TARJETA DE CREDITO  "
7;"TARJETA DE DEBITO   "
8;"DEPOSITO EN CUENTA  "
9;"MIXTO               "


    */

function forma_pago(idformapago){
    // EFECTIVO
    if(idformapago == 1){

        //$('#solicampo').hide();
        $('#banco').attr('required', false);
        $('#cheque_nro').attr('required', false);
        $('#transfer_nro').attr('required', false);
        $('#boleta_nro').attr('required', false);
        $('#idcuentacon').attr('required', false);
        $('#fecha_emision').attr('required', false);
        $('#fecha_vencimiento').attr('required', false);
        
        $('#banco_box').hide();
        $('#cheque_nro_box').hide();
        $('#transfer_nro_box').hide();
        $('#boleta_nro_box').hide();
        $('#idcuentacon_box').hide();
        $('#fecha_emision_box').hide();
        $('#fecha_vencimiento_box').hide();
    }
        
    // CHEQUE  VISTA 
    if(idformapago == 2){
        $('#banco').attr('required', true);
        $('#cheque_nro').attr('required', true);
        $('#transfer_nro').attr('required', false);
        $('#boleta_nro').attr('required', false);
        $('#idcuentacon').attr('required', true);
        $('#fecha_emision').attr('required', true);
        $('#fecha_vencimiento').attr('required', false);
        
        $('#banco_box').show();
        $('#cheque_nro_box').show();
        $('#transfer_nro_box').hide();
        $('#boleta_nro_box').hide();
        $('#idcuentacon_box').show();
        $('#fecha_emision_box').show();
        $('#fecha_vencimiento_box').hide();
        
    }
    // CHEQUE DIFERIDO
    if(idformapago == 10){
        $('#banco').attr('required', true);
        $('#cheque_nro').attr('required', true);
        $('#transfer_nro').attr('required', false);
        $('#boleta_nro').attr('required', false);
        $('#idcuentacon').attr('required', true);
        $('#fecha_emision').attr('required', true);
        $('#fecha_vencimiento').attr('required', true);
        
        $('#banco_box').show();
        $('#cheque_nro_box').show();
        $('#transfer_nro_box').hide();
        $('#boleta_nro_box').hide();
        $('#idcuentacon_box').show();
        $('#fecha_emision_box').show();
        $('#fecha_vencimiento_box').show();
        
    }
    // TRANSFERENCIA
    if(idformapago == 5){
        $('#banco').attr('required', true);
        $('#cheque_nro').attr('required', false);
        $('#transfer_nro').attr('required', true);
        $('#boleta_nro').attr('required', false);
        $('#idcuentacon').attr('required', true);
        $('#fecha_emision').attr('required', true);
        $('#fecha_vencimiento').attr('required', false);
        
        $('#banco_box').show();
        $('#cheque_nro_box').hide();
        $('#transfer_nro_box').show();
        $('#boleta_nro_box').hide();
        $('#idcuentacon_box').show();
        $('#fecha_emision_box').show();
        $('#fecha_vencimiento_box').hide();
    }
    
    // DEPOSITO EN CUENTA
    if(idformapago == 8){
        $('#banco').attr('required', true);
        $('#cheque_nro').attr('required', false);
        $('#transfer_nro').attr('required', false);
        $('#boleta_nro').attr('required', true);
        $('#idcuentacon').attr('required', true);
        $('#fecha_emision').attr('required', false);
        $('#fecha_vencimiento').attr('required', false);
        
        $('#banco_box').show();
        $('#cheque_nro_box').hide();
        $('#transfer_nro_box').hide();
        $('#boleta_nro_box').show();
        $('#idcuentacon_box').show();
        $('#fecha_emision_box').hide();
        $('#fecha_vencimiento_box').hide();
    }
    
    // TARJETA DE CREDITO o TARJETA DE DEBITO
    if(idformapago == 6 || idformapago == 7){

        //$('#solicampo').hide();
        $('#banco').attr('required', true);
        $('#cheque_nro').attr('required', false);
        $('#transfer_nro').attr('required', false);
        $('#boleta_nro').attr('required', false);
        $('#idcuentacon').attr('required', true);
        $('#fecha_emision').attr('required', false);
        $('#fecha_vencimiento').attr('required', false);
        
        $('#banco_box').show();
        $('#cheque_nro_box').hide();
        $('#transfer_nro_box').hide();
        $('#boleta_nro_box').hide();
        $('#idcuentacon_box').show();
        $('#fecha_emision_box').hide();
        $('#fecha_vencimiento_box').hide();
        
    }
    
}

function carga_cuentas(idbanco){
    var direccionurl='cobro_cuentas_cuentasban.php';
    var parametros = {
          "idbanco" : idbanco
   };
   $.ajax({
            data:  parametros,
            url:   direccionurl,
            type:  'post',
            beforeSend: function () {
                //$("#cuentabox").hide();
                $("#idcuentacon").val('Cargando...');
            },
            success:  function (response) {
                //$("#cuentabox").show();
                $("#idcuentacon_box_camp").html(response);
            }
    });    
}
function agrega_fpag(){
    var idformapago = $('#idforma').val();
    var monto_pago = $('#monto_pago').val();    
    var idbanco = $('#idbanco').val();
    var cheque_nro = $('#cheque_nro').val();
    var transfer_nro = $('#transfer_nro').val();
    var boleta_nro = $('#boleta_nro').val();
    var idcuentacon = $('#idcuentacon').val();
    var fecha_emision = $('#fecha_emision').val();
    var fecha_vencimiento = $('#fecha_vencimiento').val();
    
    
    var direccionurl='cobro_cuentas_car_fpag_add.php';
    var parametros = {
          "idformapago" : idformapago,
          "idcliente" :  <?php echo $idcliente ?>,
          "monto_pago" : monto_pago,
          "idbanco" : idbanco,
          "nrochq" : cheque_nro,
          "codtransfer" : transfer_nro,
          "boleta_nro" : boleta_nro,
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
                        actualiza_carrito_fpag(<?php echo $idcliente; ?>);
                    }else{
                        var titulo = 'Errores';
                        var mensaje = obj.errores;
                        alerta_modal(titulo,mensaje);
                        actualiza_carrito_fpag(<?php echo $idcliente; ?>);    
                    }
                }else{
                    alert(response);
                    $("#carrito_cuentas").html(response);        
                }

            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_jq(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_jq(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
///////////////////////////////// FORMA PAGO /////////////////////////////////

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
                    <h2>Cobrar cuentas a credito</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<p><a href="cobro_cuentas.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
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
            <th align="center">Nombre</th>
            <th align="center">Apellido</th>
            <th align="center">Documento</th>
            <th align="center">Nombre Fantasia</th>

            <th align="center">Linea Credito</th>
            <th align="center">Saldo Linea</th>
            <th align="center">Linea mensual</th>
            <th align="center">Saldo mensual</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) { ?>
        <tr>

            <td align="center"><?php echo intval($rs->fields['idcliente']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['nombre']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['apellido']); ?></td>
            <td align="right"><?php echo formatomoneda($rs->fields['documento']);  ?></td>
            <td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>

            <td align="center"><?php echo formatomoneda($rs->fields['linea_sobregiro']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['saldo_sobregiro']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['max_mensual']); ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['saldo_mensual']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />

<hr />
<strong>Facturas Pendientes:</strong>
<div id="facturas_pend"><?php require_once("cobro_cuentas_fact_pend.php"); ?></div>

<strong>Facturas Seleccionadas:</strong>
<div id="carrito_cuentas"><?php require_once("cobro_cuentas_car.php"); ?></div>
<?php /*?>
<strong>Anticipos (Sin aplicar):</strong>
<div id="anticipos_pend"><?php require_once("cobro_cuentas_ant_pend.php"); ?></div>

<strong>Anticipos Seleccionados:</strong>
<div id="carrito_anticipos"><?php require_once("cobro_cuentas_car_ant.php"); ?></div>
    <?php /*?>
<strong>Notas de Credito (Sin aplicar):</strong>
<div id="notascredito_pend"><?php //require_once("cobro_cuentas_fact_pend.php"); ?></div>
  <?php */ ?>
<div class="clearfix"></div>
<br />

<div class="form-group" id="formapagobox" >
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Forma de Pago *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// no quitar and idforma_old por que se usa para la caja vieja
// consulta
$consulta = "
SELECT formas_pago2.idforma, formas_pago2.descripcion, formas_pago2.estado
FROM formas_pago2
inner join formas_pago on formas_pago.idforma = formas_pago2.idforma_old
where 
formas_pago2.estado = 1
and formas_pago2.hab_cliente = 'S'
and formas_pago2.idforma_old is not null
and formas_pago.anticipo = 'N'
ORDER BY formas_pago2.descripcion;
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
        'acciones' => ' onchange="forma_pago(this.value);"; ',
        'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);


?>
    </div>
</div>

<div class="form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Monto pago *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="monto_pago" id="monto_pago" value="<?php  if (isset($_POST['monto_pago'])) {
        echo intval($_POST['monto_pago']);
    } else {
        echo intval($total_abonar_acum);
    }?>" placeholder="Monto pago" class="form-control" required />                    
    </div>
</div>



<div class="form-group" id="banco_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Banco *</label>
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





<div class="form-group" id="cheque_nro_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cheque Nro. </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cheque_nro" id="cheque_nro" value="<?php  if (isset($_POST['cheque_nro'])) {
        echo htmlentities($_POST['cheque_nro']);
    } else {
        echo htmlentities($rs->fields['cheque_nro']);
    }?>" placeholder="Cheque Nro" class="form-control"  />                    
    </div>
</div>

<div class="form-group" id="fecha_emision_box" <?php if ($_POST['idforma'] != 2 && $_POST['idforma'] != 10) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Emision *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
     <input type="date" name="fecha_emision" id="fecha_emision" value="<?php  if (isset($_POST['fecha_emision'])) {
         echo htmlentities($_POST['fecha_emision']);
     } else {
         echo htmlentities($rs->fields['fecha_emision']);
     }?>" placeholder="Fecha Emision" class="form-control"  />                   
    </div>
</div> 

<div class="form-group" id="fecha_vencimiento_box" <?php if ($_POST['idforma'] != 2 && $_POST['idforma'] != 10) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Vencimiento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
     <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="<?php  if (isset($_POST['fecha_vencimiento'])) {
         echo htmlentities($_POST['fecha_vencimiento']);
     } else {
         echo htmlentities($rs->fields['fecha_vencimiento']);
     }?>" placeholder="Fecha Vencimiento" class="form-control"  />                   
    </div>
</div>

<div class="form-group" id="transfer_nro_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Transfer Nro. </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="transfer_nro" id="transfer_nro" value="<?php  if (isset($_POST['transfer_nro'])) {
        echo htmlentities($_POST['transfer_nro']);
    } else {
        echo htmlentities($rs->fields['transfer_nro']);
    }?>" placeholder="Transfer Nro" class="form-control"  />                    
    </div>
</div>

<div class="form-group" id="boleta_nro_box" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Boleta de Deposito Nro. </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="boleta_nro" id="transfer_nro" value="<?php  if (isset($_POST['boleta_nro'])) {
        echo htmlentities($_POST['boleta_nro']);
    } else {
        echo htmlentities($rs->fields['boleta_nro']);
    }?>" placeholder="Boleta Nro" class="form-control"  />                    
    </div>
</div>

 

<div class="clearfix"></div>
<BR />
    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
       <button type="button" class="btn btn-default" onMouseUp="agrega_fpag();" ><span class="fa fa-plus"></span> Agregar</button>
        </div>
    </div>    
    
  
    
 
         
           
        
<br /><hr /><br />

<strong>Formas de Pago:</strong>
<div id="carrito_fpag_cuentas"><?php require_once("cobro_cuentas_car_fpag.php"); ?></div>



<hr />

<form id="form1" name="form1" method="post" action="">

    
    
<?php
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
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Observaciones </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="obs" id="obs" value="" placeholder="" class="form-control"   />                    
    </div>
</div>
<div class="clearfix"></div>
<br /><br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Abonar</button>

        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
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
