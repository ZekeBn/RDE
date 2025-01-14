 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

require_once("includes/funciones_cocina.php");

//Preferencias
$buscar = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$contado_txt = trim($rspref->fields['contado_txt']);
$credito_txt = trim($rspref->fields['credito_txt']);
$factura_pred = trim($rspref->fields['factura_pred']);
$autoimpresor = trim($rspref->fields['autoimpresor']);
$forzar_agrupacion = trim($rspref->fields['forzar_agrupacion']);
$anteponer_moneda_fact = trim($rspref->fields['anteponer_moneda_fact']);
$ticket_fox = trim($rspref->fields['ticket_fox']);
$comanda_o_tk = trim($rspref->fields['comanda_o_tk']);
if ($forzar_agrupacion == '') {
    $forzar_agrupacion = "N";
}
if ($factura_pred == '') {
    $factura_pred = "N";
}
if ($autoimpresor == '') {
    $autoimpresor = "N";
}
if ($anteponer_moneda_fact == '') {
    $anteponer_moneda_fact = "N";
}
// sucursales preimpresas
$consulta = "
select preimpreso_forzar from sucursales where idsucu = $idsucursal limit 1
";
$rssucauto = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rssucauto->fields['preimpreso_forzar'] == 'S') {
    $autoimpresor = 'N';
}
$moduloventa = intval($_REQUEST['modventa']);
//Nuevos campos para impresion de factura agrupada o no, imprime idpedido en factura,limite de items de la factura del cliente
$agrupar_articulos = trim($rspref->fields['agrupar_items_factura']);//Indica si se imprimen las cantidades y descripciones de la venta en la factura
$describe_factura = trim(strtoupper($rspref->fields['describe_factura']))    ;//Indica el texto x defecto que se va usar si esta en uso agrupar facturas
$maximo_items = intval($rspref->fields['max_items_factura']); //Idica cuantos articulos caben en la factura. Por mas que agrupar facturas sea si, se debe completar con la cantidad excta para rellenar los vacios (lineas)
$max_items_factura = intval($rspref->fields['max_items_factura']);
$imprime_idvta = trim($rspref->fields['imprimir_idvta']);    //Imprime ek id de la vta en factura
$imprime_idped = trim($rspref->fields['imprimir_idped']);    //Imprime ek id pedido en factura

//echo $agrupar_articulos;exit;

$moduloventa = intval($_REQUEST['modventa']);
$tk = intval($_REQUEST['tk']);
$script_factura_cliente = trim($rspref->fields['script_factura_cliente']);
if ($script_factura_cliente == '') {
    //usamos x defecto
    $script_factura_cliente = "http://localhost/impresorweb/ladoclientefactura.php";
}
if (intval($_GET['v']) > 0) {
    $venta = intval($_GET['v']);
}
if (intval($_GET['vta']) > 0) {
    $venta = intval($_GET['vta']);
}
$idventa = $venta;


//cabecera
$consulta = "
    Select factura,ventas.idventa,recibo,ventas.razon_social,ruchacienda,dv,idpedido,ventas.idcliente as idunicocli,
    (select telefono from cliente where idcliente = ventas.idcliente) as telefono,
    (select direccion from cliente where idcliente = ventas.idcliente) as direccion,
    total_cobrado,total_venta,otrosgs,fecha,tipo_venta,descneto,totaliva10,totaliva5,texe,idmesa, ventas.sucursal
    from ventas
    inner join cliente on cliente.idcliente=ventas.idcliente
    where 
    cliente.idempresa=$idempresa 
    and ventas.idempresa=$idempresa 
    and idventa=$venta
    and ventas.estado <> 6
    ";
$rsvv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rsvv->fields['idunicocli']);
$razon_social = limpia_puso_factura(substr($rsvv->fields['razon_social'], 0, 40));
$ruc = $rsvv->fields['ruchacienda'].'-'.$rsvv->fields['dv'];
$direccion = limpia_puso_factura(substr($rsvv->fields['direccion'], 0, 40));
$telefono = limpia_puso_factura(substr('0'.$rsvv->fields['telefono'], 0, 10));
$fecha = date("d-m-Y", strtotime($rsvv->fields['fecha']));
$tipoventa = intval($rsvv->fields['tipo_venta']);
$factura = trim($rsvv->fields['factura']);
$totalventa = intval($rsvv->fields['total_cobrado']);
$totaldescuento = intval($rsvv->fields['descneto']);
$totaliva10 = intval($rsvv->fields['totaliva10']);
$totaliva5 = intval($rsvv->fields['totaliva5']);
$totalex = intval($rsvv->fields['texe']);
$idpedido = intval($rsvv->fields['idpedido']);
$idped = $idpedido;//para motor de impresion
$idventa = intval($rsvv->fields['idventa']);
$idmesa = intval($rsvv->fields['idmesa']);
$idsucursalventa = intval($rsvv->fields['sucursal']);
//echo $idsucursalventa;exit;
if ($idventa == 0) {
    echo "La venta fue anulada.";
    exit;
}

if ($tk == 0) {

    // toma prioridad de la tabla de timbrado no de preferencias
    $consulta = "
    SELECT idventa, tipoimpreso, idtimbrado 
    FROM facturas
    inner join ventas on ventas.idtandatimbrado = facturas.idtanda 
    where
    idventa = $idventa
    limit 1
    ";
    $rstim = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (trim($rstim->fields['tipoimpreso']) == 'AUT') {
        $autoimpresor = "S";
    } else {
        $autoimpresor = "N";
    }

    if ($autoimpresor == 'N') {








        //detalle
        $consulta = "
        Select  
        idprod, 
        sum(cantidad) as cantidad, 
        sum(subtotal) as subtotal,
        pventa, iva,
        (select descripcion from productos where idprod_serial = ventas_detalles.idprod) as producto
        from ventas_detalles 
        where 
        idventa=$venta 
        and idemp=$idempresa
        group by idprod
        order by (select descripcion from productos where idprod_serial = ventas_detalles.idprod) asc
        ";
        $rscuerpo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tdcuerpo = $rscuerpo->RecordCount();
        $totitems = $tdcuerpo;
        $descuento_item = intval($rscuerpo->fields['descuento']);
        // agregados
        $consulta = "
        select sum(precio_adicional) as totalagregado, count(*) as cantagregado
        from ventas_agregados
        where idventadet in
        (
        Select idventadet
        from ventas_detalles 
        where 
        idventa=$venta 
        and idemp=$idempresa
        )
        ";
        $rsag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $totag = intval($rsag->RecordCount());
        $cantagregado = $rsag->fields['cantagregado'];
        if (intval($rsag->fields['totalagregado']) == 0) {
            $totag = 0;
        }
        $delivery_costo = $rsvv->fields['otrosgs'];
        if (intval($delivery_costo) > 0) {
            $totdel = 1;
        } else {
            $totdel = 0;
        }

        if (intval($descuento) > 0) {
            $totdesc = 1;
        } else {
            $totdesc = 0;
        }

        // Contado o Credito
        if ($rsvv->fields['tipo_venta'] == 1) {
            $contado = "X";
            $credito = "";
        } else {
            $contado = "";
            $credito = "X";
        }


        //con todos los datos insertamos en el localhost
        //armar cuerpo
        if ($agrupar_articulos == 'N') {
            $arraycuerpo = '';
            while (!$rscuerpo->EOF) {
                $cantidad = floatval($rscuerpo->fields['cantidad']);
                $descripcion = limpia_puso_factura(trim($rscuerpo->fields['producto']));
                $precioventa = floatval($rscuerpo->fields['pventa']);
                $subiva5 = 0;
                $subexenta = 0;
                $subiva10 = floatval($rscuerpo->fields['subtotal']);
                $descuento = 0;



                $concat = $cantidad.'}'.$descripcion.'}'.$precioventa.'}'.$subiva5.'}'.$subiva10.'}'.$descuento_item;
                $arraycuerpo = $arraycuerpo.$concat.'}';


                $rscuerpo->MoveNext();
            }
        } else {
            $cantidad = 0;
            $descripcion = trim($describe_factura);
            $precioventa = floatval($totalventa);
            $subiva5 = 0;
            $subexenta = 0;
            $subiva10 = floatval($totalventa);
            $descuento = 0;



            $concat = $cantidad.'}'.$descripcion.'}'.$precioventa.'}'.$subiva5.'}'.$subiva10.'}'.$descuento_item;
            $arraycuerpo = $arraycuerpo.$concat.'}';


        }
        // agregados
        if (intval($rsag->fields['totalagregado']) > 0) {
            $subiva5 = 0;
            $subiva10 = intval($rsag->fields['totalagregado']);
            $preciounit = round(intval($rsag->fields['totalagregado'] / $cantagregado), 0);
            $descuento_item = 0;
            $arraycuerpo .= $cantagregado.'}'.'AGREGADOS'.'}'.$preciounit.'}'.$subiva5.'}'.$subiva10.'}'.$descuento_item.'}';
        }

        // delivery
        if (intval($delivery_costo) > 0) {
            $subiva5 = 0;
            $subiva10 = intval($delivery_costo);
            $descuento_item = 0;
            $arraycuerpo .= '1}'.'DELIVERY'.'}'.intval($delivery_costo).'}'.$subiva5.'}'.$subiva10.'}'.$descuento_item.'}';
        }


        $razon_social = trim($razon_social);

        $redirbus = "";
        if ($_GET['bus'] == 1) {
            $redirbus = "?bus=1";
        }




        ///////////////////////////// IMPRESOR NUEVO /////////////////////////////////////////
        $factura_json = factura_preimpresa($idventa);


        //print_r(json_decode($factura_json,true));
        //exit;
        ///////////////////////////// IMPRESOR NUEVO /////////////////////////////////////////

    } else { // if($autoimpresor == 'N'){

        // auto impresor
        $factura_auto = factura_autoimpresor($idventa);

    } // if($autoimpresor == 'N'){


} else {

    //Tickete
    // trae la primera impresora
    $consulta = "SELECT * FROM impresoratk where  idsucursal = $idsucursalventa and borrado = 'N' and tipo_impresora = 'CAJ' order by idimpresoratk asc limit 1";
    $rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //echo $consulta;exit;
    $pie_pagina = $rsimp->fields['pie_pagina'];
    $defaultprnt = "http://localhost/impresorweb/ladocliente.php";
    $script_impresora = trim($rsimp->fields['script']);
    if (trim($script_impresora) == '') {
        $script_impresora = $defaultprnt;
    }

    // tipo de impresor
    /*$impresor_tip="REI";
    $redir_impr="impresor_ticket_reimp.php";

    // parametros
    $consolida='S';
    $leyenda_credito=$rsimp->fields['leyenda_credito'];
    $datos_fiscal=$rsimp->fields['datos_fiscal'];
    $muestra_nombre=$rsimp->fields['muestra_nombre'];
    $usa_chapa=$rsimp->fields['usa_chapa'];
    $usa_obs=$rsimp->fields['usa_obs'];
    $usa_precio=$rsimp->fields['usa_precio'];
    $usa_total=$rsimp->fields['usa_total'];
    $usa_nombreemp=$rsimp->fields['usa_nombreemp'];
    $usa_totaldiscreto=$rsimp->fields['usa_totaldiscreto'];
    $txt_codvta=$rsimp->fields['txt_codvta'];
    $cabecera_pagina=$rsimp->fields['cabecera_pagina'];
    $pie_pagina=$rsimp->fields['pie_pagina'];

    require_once("impresor_motor.php");*/


    if ($ticket_fox == 'S') {
        $texto_json = ticket_venta_json($idventa);
        $texto = "";
    } else {
        $texto_json = "";
        if ($comanda_o_tk == 'T') {
            $texto = ticket_venta($idventa);
        } else {
            // ticket de cocina para caja
            $impresor_tip = "CAJ";
            if ($idmesa > 0) {
                $impresor_tip = "MES";
            }
            $parametros_array = [
                'idimpresoratk' => $rsimp->fields['idimpresoratk'],
                'idpedido' => $idpedido,
                'idmesa' => $idmesa,
                'impresor_tip' => $impresor_tip,
                'v' => $idventa
            ];
            //print_r($parametros_array);exit;
            $res = comanda_cocina_consolidado($parametros_array);
            $texto = $res['ticket'];
            if (trim($texto) == '') {
                $texto = ticket_venta($idventa);
            }

        }
    }


    /*    if($ticket_fox == 'S'){
            $texto_json=ticket_venta_json($idventa);
            $texto="";
        }else{
            $texto_json="";
            $texto=ticket_venta($idventa);
        }
        */
}



//print_r(json_decode($texto_json,true));
//exit;


/*

Problemas Ñ en clientes
En Mysql:
ALTER TABLE `cabeza` CHANGE `factura` `factura` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `razon_social` `razon_social` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE `ruc` `ruc` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE `direccion` `direccion` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE `telefono` `telefono` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
CHANGE `credito` `credito` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `contado` `contado` CHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
CHANGE `terminado` `terminado` VARCHAR(1) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'N';


En el Script Local:
$razon=utf8_decode($razon);
$direccion=utf8_decode($direccion);
*/
// buscar impresora remota
$consulta = "
SELECT * FROM 
impresoratk 
where 
idsucursal = $idsucursalventa 
and borrado = 'N' 
and tipo_impresora='REM' 
order by idimpresoratk  asc
limit 1
";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora_rem = trim($rsimp->fields['script']);
$version_app = $rsimp->fields['version_app'];
$tipo_saltolinea_app = $rsimp->fields['tipo_saltolinea_app'];
if (trim($script_impresora_rem) == '') {
    $script_impresora_rem = $defaultprnt;
}
if (intval($version_app) == 0) {
    $version_app = 1;
}


// auto impresor para app
if ($tipo_saltolinea_app != '') {
    $factura_auto_app = str_replace($saltolinea, $tipo_saltolinea_app, $factura_auto); // \\r
} else {
    $factura_auto_app = $factura_auto;
}
$factura_auto_app = str_replace("'", "", $factura_auto_app);
$factura_auto_app = str_replace('"', '', $factura_auto_app);
$texto_app = $factura_auto_app;
$url1 = "reimprimir_facturas_retro.php";

// lista de post a enviar
if ($metodo_app == 'POST_URL') {
    $lista_post = [
        'tk' => $texto_app,
        'tk_json' => $ticket_json
    ];
}
//parametros para la funcion
$parametros_array_tk = [
    'texto_imprime' => $texto_app, // texto a imprimir
    'url_redir' => $url1, // redireccion luego de imprimir
    'lista_post' => $lista_post, // se usa solo con metodo POST_URL
    'imp_url' => $script_impresora_rem, // se usa solo con metodo POST_URL
    'metodo' => $metodo_app // POST_URL, SUNMI, ''
];
$parametros_app = [
    'parametros_tk' => $parametros_array_tk,
    'div_msg' => 'impresion_box',
    'version_app' => $version_app
];


$js_app = javascript_app_webview($parametros_app);

// ticket para app
if ($tipo_saltolinea_app != '') {
    $factura_auto_app = str_replace($saltolinea, $tipo_saltolinea_app, $texto); // \\r
} else {
    $factura_auto_app = $texto;
}
$factura_auto_app = str_replace("'", "", $factura_auto_app);
$factura_auto_app = str_replace('"', '', $factura_auto_app);
$texto_app = $factura_auto_app;
$url1 = "reimprimir_facturas_retro.php";

// lista de post a enviar
if ($metodo_app == 'POST_URL') {
    $lista_post = [
        'tk' => $texto_app,
        'tk_json' => $ticket_json
    ];
}
//parametros para la funcion
$parametros_array_tk = [
    'texto_imprime' => $texto_app, // texto a imprimir
    'url_redir' => $url1, // redireccion luego de imprimir
    'lista_post' => $lista_post, // se usa solo con metodo POST_URL
    'imp_url' => $script_impresora_rem, // se usa solo con metodo POST_URL
    'metodo' => $metodo_app // POST_URL, SUNMI, ''
];
$parametros_app = [
    'parametros_tk' => $parametros_array_tk,
    'div_msg' => 'impresion_box',
    'version_app' => $version_app
];

$js_app_tk = javascript_app_webview($parametros_app);

?>
<html>
<head>
<script src="js/jquery-1.10.2.min.js"></script>
<meta charset="utf-8">
<title>Medidor de Factura</title>
<script>
<?php echo $js_app['funcion_externa']; ?>
function llamaimprime(){
    setTimeout("imprimir_factura()",100);    
}
<?php if ($tk == 1) {     ?>    
    
function imprime_cliente(){
    
    // impresor app
<?php echo $js_app_tk['inicio']; ?>
        var texto = document.getElementById("texto").value;
        var parametros = {
                "tk"      : texto,
                'tk_json' : '<?php echo $texto_json; ?>'
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora; ?>',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
                crossDomain: true,
                success:  function (response) {
                        //$("#impresion_box").html(response);    
                        //si impresion es correcta marcar
                        var str = response;
                        var res = str.substr(0, 18);
                        //alert(res);
                        if(res == 'Impresion Correcta'){
                            //marca_impreso('<?php echo $id; ?>');
                            //document.body.innerHTML = "Impresion Enviada!";
                            //$('#reimprimebox',window.parent.document).html('');
                            document.location.href='<?php echo $url1; ?>';
                        }else{
                            $("#impresion_box").html(response);    
                            document.location.href='<?php echo $url1; ?>';
                        }
                        
                        // si no es correcta avisar para entrar al modulo de reimpresiones donde se pone la ultima impresion correcta y desde ahi se marca como no impreso todas las que le siguen
                        
                }
        });
    // impresor app final
<?php echo $js_app_tk['final']; ?>
    
}    


<?php }?>

function imprimir_factura_auto(){
    
    // impresor app
<?php echo $js_app['inicio']; ?>
    var texto = $("#texto_fac").val();
        var parametros = {
                "tk" : texto,
                'fac': 'S'
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_factura_cliente; ?>',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
                crossDomain: true,
                success:  function (response) {
                        //$("#impresion_box").html(response);    
                        //si impresion es correcta marcar
                        //var str = response;
                        //var res = str.substr(0, 18);
                        //alert(res);
                            $("#impresion_box").html(response);    
                            document.location.href='<?php echo $url1; ?>';


                        // si no es correcta avisar para entrar al modulo de reimpresiones donde se pone la ultima impresion correcta y desde ahi se marca como no impreso todas las que le siguen

                }
        });
    // impresor app final
<?php echo $js_app['final']; ?>
}    
function imprimir_factura(){
    
    
<?php echo $js_app['if_es_app_inicio']; ?>
    alert('No se puede imprimir por la app cuando es preimpreso.');
<?php echo $js_app['if_es_app_fin']; ?>
    <?php /* ?>
    var idventa=<?php echo $venta?>;
    var idcliente=<?php echo $idcliente ?>;
    var razon=<?php echo "'$razon_social'" ?>;
    var fact=<?php echo "'$factura'" ?>;
    var fechaventa=<?php echo "'$fecha'"  ?>;
    var tipoventa=<?php echo $tipoventa ?>;
    var idpedido=<?php echo $idpedido ?>;
    var totalventa=<?php echo $totalventa ?>;
    var totaldescuento=<?php echo $totaldescuento ?>;
    var totaliva10=<?php echo $totaliva10?>;
    var totaliva5=<?php echo $totaliva5?>;
    var totalex=<?php echo $totalex ?>;
    var ruc=<?php echo "'$ruc'" ?>;
    var dt=<?php echo "'$arraycuerpo'" ?>;
       var direccion='';
    var telefono='';

    var maxitms=<?php echo $maximo_items?>;
    var impvta='<?php echo $imprime_idvta ?>';
    var imped='<?php echo $imprime_idped ?>';

     var parametros = {
            "idventa"         :idventa,
            "idcliente"       : idcliente,
            "razon"           :razon,
            "fact"            : fact,
            "fechaventa"      : fechaventa,
            "tipoventa"       : tipoventa,
            "totalventa"      : totalventa,
            "totaldescuento"  : totaldescuento,
            "totaliva10"      : totaliva10,
            "totaliva5"       : totaliva5,
            "totalex"         : totalex,
            "dt"              : dt,
             "ruc"             : ruc,
            "idpedido"        : idpedido,
            "direccion"       : direccion,
             "telefono"        : telefono,
             "maximoitem"      : maxitms,
             "imprimirvta"      : impvta,
             "imprimirped"      : imped,
            "factura_json"    : '<?php echo $factura_json; ?>'

        };
        <?php */ ?>
        var url_abrir=<?php echo "'".$script_factura_cliente."'"; ?>;
        var parametros = {
            "factura_json"    : '<?php echo $factura_json; ?>'
         
       };
       $.ajax({
                data:  parametros,
                url:   url_abrir,
                type:  'post',
                beforeSend: function () {
                      //  $("#imprimir").html("<br /><br />Enviando Impresion...<br /><br />");
                },
                success:  function (response) {
                    if(IsJsonString(response)){    
                                            // convierte a objeto
                        var obj = jQuery.parseJSON(response);    
                        // si es valido    
                        if (obj.valido=='S'){
                            // redirecciona
                            document.location.href='<?php echo $url1; ?>';
                        }else{
                            $("#imprimir").html(obj.errores);
                            alert(obj.errores);
                        }
                    
                    }else{
                        if (response=='ok'){
                            document.location.href='<?php echo $url1; ?>';
                        }else{
                            $("#imprimir").html(response);
                            alert(response);
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
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function errores_ajax_manejador(jqXHR, textStatus, errorThrown, tipo){ 
    // error
    if(tipo == 'error'){
        if(jqXHR.status == 404){
            alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown+' | URL: \''+ jqXHR.getResponseHeader('Location')+'\'');
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
            alert('Pagina no encontrada [404]'+' | URL: \''+ jqXHR.getResponseHeader('Location')+'\'');
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
// si es la app
<?php echo $js_app['if_es_app_inicio']; ?>
// si se cargo la pagina 
<?php echo $js_app['document_ready_app_ini']; ?>
            
<?php
// llamadores de funcion javascript
$imprimeticket = "    imprime_cliente();".$saltolinea;
if ($autoimpresor == 'S') {
    $imprimefactura = "    imprimir_factura_auto();".$saltolinea;
} else {
    $imprimefactura = "    imprimir_factura();".$saltolinea;
}
// factura
if ($tk != 1) {
    echo $imprimefactura;
}
// ticket
if ($tk == 1) {
    // si es ventas tablet
    if ($moduloventa == 1) {
        // si las preferencias permiten imprimir al finalizar
        if ($rspref->fields['imprime_alfinalizar'] == 'S') {
            echo $imprimeticket;
        } else {
            echo "    document.location.href='".$url1."';";
        }
        // si no es ventas por caja imprime el ticket siempre
    } else {
        echo $imprimeticket;
    }

}
?>            
            
<?php echo $js_app['document_ready_app_fin']; ?>
<?php echo $js_app['if_es_app_fin']; ?>
// si no es la app
<?php echo $js_app['if_no_app_inicio']; ?>
        // ejecutar al cargar la pagina
        $( document ).ready(function() {
<?php

// llamadores de funcion javascript
$imprimeticket = "    imprime_cliente();".$saltolinea;
if ($autoimpresor == 'S') {
    $imprimefactura = "    imprimir_factura_auto();".$saltolinea;
} else {
    $imprimefactura = "    imprimir_factura();".$saltolinea;
}
// factura
if ($tk != 1) {
    echo $imprimefactura;
}
// ticket
if ($tk == 1) {
    // si es ventas tablet
    if ($moduloventa == 1) {
        // si las preferencias permiten imprimir al finalizar
        if ($rspref->fields['imprime_alfinalizar'] == 'S') {
            echo $imprimeticket;
        } else {
            echo "    document.location.href='".$url1."';";
        }
        // si no es ventas por caja imprime el ticket siempre
    } else {
        echo $imprimeticket;
    }

}

?>

});

<?php echo $js_app['if_no_app_fin']; ?>

</script>
</head>
<body>
<?php echo $js_app['html']; ?>
    <textarea name="texto" id="texto" style="display: none"><?php echo $texto; ?></textarea>
    <textarea name="texto_fac" id="texto_fac" style="display: none"><?php echo $factura_auto; ?></textarea>
    <div id="imprimir">
        
        
    </div>
    <div id="impresion_box">
    
    </div>
    </body>
</html>





