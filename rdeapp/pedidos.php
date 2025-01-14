<?php
//if (!isset($_SESSION)) {
//    session_start();
//}
//require_once("includes/conexion.php");
//require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$ahora = date("Y-m-d H:i:s");
$save_tempocar = $_POST['save_tempocar'];

//if (!isset($_SESSION['idvendedor']) and !isset($_SESSION['nombres']) and !isset($_SESSION['zona']) and !isset($_SESSION['idzona'])) {

//    header("Location: index.php");


// Desactivar las advertencias de retorno temporalmente
error_reporting(E_ALL & ~E_DEPRECATED);

function enviardatoscurl($url, $postdata)
{
    if (is_string($postdata)) {
        $postdata = json_decode($postdata, true);
    }
    http_build_query($postdata);
    $parts = parse_url($url);
    $host = $parts['host'];
    $ch = curl_init();
    $header = array(
        'Content-Type: application/json',
    );
    //print_r($postdata);exit;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // Convertir $postdata a una cadena JSON si es un array
    if (is_array($postdata)) {
        $postdata = json_encode($postdata);
    }

    // Ahora puedes pasar $postdata a curl_setopt()
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    $resultadonav = curl_exec($ch);
    $getinfo = curl_getinfo($ch);

    if ($resultadonav  === false) {
        $resultadonav = "Error in sending";
        if (curl_error($ch)) {
            $resultadonav .= "\n" . curl_error($ch);
        }
    } else if ($getinfo['http_code'] != 200) {
        $resultadonav = "No data returned. Error: " . $getinfo['http_code'];
        if (curl_error($ch)) {
            $resultadonav .= "\n" . curl_error($ch);
        }
    }
    curl_close($ch);
    return $resultadonav;
}

$idcliente = isset($_REQUEST['idc']) ? intval($_REQUEST['idc']) : 0;
$idcodLista = isset($_REQUEST['codLista']) ? intval($_REQUEST['codLista']) : 0;
// $idcliente=intval($__POST['idc']); // era para el get de la funion anterior ahora probar post
// echo json_encode($__REQUEST['idc']);
if ($idcliente == 0 && $save_tempocar != 1) {
    header("Location: seleccion.php");
    exit;
}
if ($idcliente != 0 && $save_tempocar != 1) {

    $opciones = array(
        'http' => array(
            'timeout' => 30, // TODO: timeout de carga de pedidos Tiempo de espera en segundos (ajusta según tus necesidades)
        ),
    );

    $contexto = stream_context_create($opciones);
    $url1 = "http://181.94.221.41:1704/wsrde/busquedaClientes?busqueda=";
    $url1 = $url1 . "$idcliente&tipoCampo=CODIGO_CLIENTE";
    $url2 = "http://181.94.221.41:1704/wsrde/busquedaSaldoLimite?busqueda=";
    $url2 = $url2 . "$idcliente&tipoCampo=CODIGO_CLIENTE";
    $metodo = 1;
    $urlconsulta = $url1;
    //$respuesta=file_get_contents("$urlconsulta", false, $contexto);
    $respuesta = abrir_url_simple("$urlconsulta", false, $contexto);
    $datos = json_decode($respuesta, true);

    foreach ($datos as $dato) {
        $rz = isset($dato['razonSocial']) ? $dato['razonSocial'] : ''; // Verifica si la clave 'razonSocial' existe
        $ruc = isset($dato['ruc']) ? $dato['ruc'] : ''; // Verifica si la clave 'ruc' existe
        $codi = isset($dato['codCliente']) ? $dato['codCliente'] : ''; // Verifica si la clave 'codCliente' existe
        $lc = isset($dato['lineaCredito']) ? $dato['lineaCredito'] : ''; // Verifica si la clave 'lineaCredito' existe
        $sdispo = isset($dato['saldoLineaCredito']) ? $dato['saldoLineaCredito'] : ''; // Verifica si la clave 'saldoLineaCredito' existe
        $codlistap = isset($dato['codListaPrecio']) ? intval($dato['codListaPrecio']) : 0; // Lee el dato como numérico
        $descuento_valor = isset($dato['descuento']) ? $dato['descuento'] : ''; // Verifica si la clave 'descuento' existe
        $codMoneda = isset($dato['codMoneda']) ? $dato['codMoneda'] : ''; // Verifica si la clave 'codMoneda' existe
        $sumatoriaFacturasVencidas = isset($dato['sumatoriaFacturasVencidas']) ? $dato['sumatoriaFacturasVencidas'] : ''; // Verifica si la clave 'sumatoriaFacturasVencidas' existe
    }

    //$respuesta2=file_get_contents("$url2");
    $respuesta2 = abrir_url_simple($url2);
    $datos = json_decode($respuesta2, true);

    foreach ($datos as $dato) {
        $ls = $dato['saldoLimite'];
        $facturasVencidas = intval($dato['facturaVencida']);
    }
    // TODO: agregar modal si es que posee facturas vencidas listo
}

// if (isset($_POST['idtemporal'])){
//     //confirmacion del pedido
//     $idpedidotempo=intval($_POST['idtemporal']);
//     if ($idpedidotempo > 0){
//         $update="update tempocar set estado=3,confirmado_el='$ahora' where idtempo=$idpedidotempo";
//         $conexion->Execute($update) or die(errorpg($conexion,$update));
//         header("Location: seleccion.php");exit;
//     }
// }

$idvendedor = isset($_SESSION['idvendedor']) ? intval($_SESSION['idvendedor']) : 0;
$nombres = isset($_SESSION['nombres']) ? trim($_SESSION['nombres']) : '';
$apellidos = isset($_SESSION['apellidos']) ? trim($_SESSION['apellidos']) : '';
//print_r($_SESSION);
//Traemos por defecto todas las categorias para array

$url1 = "http://181.94.221.41:1704/wsrde/busquedaCategorias?busqueda=&tipoCampo=ALL";
$urlconsulta = $url1;
//$respuesta=file_get_contents("$urlconsulta");
$respuesta = abrir_url_simple("$urlconsulta");
//echo $respuesta;
$datos = json_decode($respuesta, true);
//print_r($datos);
$ahora = date("Y-m-d");

//cantidad de pedidos confirmados en fecha
$buscar = "select count(idtempo) as cantidad from tempocar where date(confirmado_el)='$ahora' and estado=3";
$rfg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tconf = $rfg->fields['cantidad'];

//procesar_ped_movil_rde

////////////////////////////////////////////////////////////////////////
////////////////////Crear Cabecera ////////////////////////////////////
$ahora = date("Y-m-d H:i:s");
$buscar = "Select * from tempocar where idcliente=$idcliente and estado=1";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idt = 0; // Valor predeterminado

if ($rs !== false && isset($rs->fields['idtempo'])) {
    $idt = intval($rs->fields['idtempo']);
}
if ($idt == 0) {
    //insertamos cabecera
    $insertar = "insert into tempocar (idcliente,idvendedor,regpor,estado,fecha) values ($idcliente,$idvendedor,1,1,'$ahora')";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    $buscar = "Select * from tempocar where idcliente=$idcliente and estado=1";
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idt = intval($rs->fields['idtempo']);
}
// if ($codlistap=='' && $codlistap== NULL){
// echo "Error";exit;
// }

// Recibimos la longitud y la latitud real del vendedor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the JSON data from the request
    $data = json_decode(file_get_contents('php://input'), true);

    // Accedemos a la longitud y latitud
    $latitud_real = $data['latitud'];
    $longitud_real = $data['longitud'];
}

if ($save_tempocar == 1) {

    $idtemporal = $_POST['idtemporal'];
    $idcliente = $_POST['idcliente'];
    $motivo_no_compra = $_POST['idmotivo_no_compra'];


    if (isset($_POST['idtemporal'])) {
        //confirmacion del pedido
        $idpedidotempo = intval($_POST['idtemporal']);
        if ($idpedidotempo > 0) {
            ////////////////////////////////////////////////////////////////
            $idcliente = intval($_POST['idcliente']);

            //armamos el json para pasar al evento de guardar el carrito
            $buscar = "Select * from tempocar where idcliente=$idcliente and estado=1";
            $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            $idt = intval($rs->fields['idtempo']);
            $consulta = "Select count(*) as datos from tempocarrdeta where idtempocarr=$idt";
            $rscount = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            if (intval($rscount->fields['datos']) > 0) {

                $buscar = "Select * from tempocarrdeta where idtempocarr=$idt ";
                $rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $i = 0;

                while (!$rsdet->EOF) {

                    $i++;
                    $detalle[] = array(
                        // "idUnico" => $idubnico,//ejemplo
                        "codEmpresa" => 1,
                        "codSucursal" => "01",
                        "tipComprobante" => "PED",
                        "serComprobante" => "C",
                        "cantidadReferencia" => $rsdet->fields['cantidad'],
                        "cantidad" => $rsdet->fields['cantidad'],
                        "descArticulo" => trim($rsdet->fields['pchar']),
                        "precioUnitario" => 0,
                        "precioLista" => $rsdet->fields['precio'],
                        "subtotal" => $rsdet->fields['subtotal'], //
                        "codArticulo" => $rsdet->fields['idproducto'],
                        "descuento" => 0,
                        "montoTotal" => 0,
                        "totalIva" => 0,
                        "montoTotalCIva" => 0,
                        "orden" => $i
                    );
                    $rsdet->MoveNext();
                }

                //*------------------------------------------------------------------//
                $idvendedor = intval($_SESSION['idvendedor']);
                $hoy = date("d-m-Y");
                // Inicializa $codlistap con un valor predeterminado
                $codlistap = 0; // O cualquier otro valor predeterminado que desees

                // Verifica si la clave 'codListaPrecio' está presente en $dato y si es numérico
                if (isset($dato['codListaPrecio']) && is_numeric($dato['codListaPrecio'])) {
                    $codlistap = intval($dato['codListaPrecio']);
                }

                // Ahora puedes usar $codlistap de manera segura en el array $cabecerareal
                $cabecerareal = array(
                    "codEmpresa" => 1,
                    "tipComprobante" => "PED",
                    "serComprobante" => "C",
                    "codSucursal" => "01",
                    "fecComprobante" => "$hoy",
                    "fecAlta" => "$hoy",
                    "codCliente" => $idcliente,
                    "codVendedor" => $idvendedor,
                    "codCondicionVenta" => 2,
                    "codListaPrecio" => $codlistap,
                    "codMoneda" => 1,
                    "tipCambio" => 1,
                    "estado" => "P",
                    "codUsuario" => "RDE",
                    "cambioMonedaPrecio" => 1,
                    "tipCambioCompra" => 1,
                    "totGravadas" => 0,
                    "totComprobante" => 0,
                    "totExentas" => null,
                    //"codCondicionVenta" => null,
                    "codPersona" => null,
                    "totIvaSinRedondeo" => 0,
                    "descuentoDet" => 0,
                    "descuento" => 0,
                    "descuentoConiva" => 0,
                    "totIva" => 0,
                    "codZona" => $_SESSION['idzona'],
                    "rdeWebPedidosMovilDet" => $detalle,
                    "latitud" => $latitud_real,
                    "longitud" => $longitud_real,
                    "motivoNoCompra" => $motivo_no_compra
                );

                $enviadatos = json_encode($cabecerareal, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                //print_r($enviadatos);
                $urlconsulta = "http://181.94.221.41:1704/wsrde/carritoWeb/guardar/";
                $resp = enviardatoscurl($urlconsulta, $enviadatos);
                //echo $resp;exit;
            }

            ////////////////////
            $update = "update tempocar set estado=3,confirmado_el='$ahora' where idtempo=$idpedidotempo";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            if ($conexion->Execute($update)) {
                echo "<script type='text/javascript'>
                    alert('Pedido Cargado con Exito');
                    window.location.href = window.location.href;
                </script>";
            } else {
                echo "Error al actualizar el estado del pedido.";
            }
            //pasar pedido a rde
            //header("Location: pedidos.php");exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once("includes/head_gen.php"); ?>
    <style type="text/css">
        /* /////////////////////////// */
        #preloader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .lds-facebook {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-facebook div {
            display: inline-block;
            position: absolute;
            left: 8px;
            width: 16px;
            background: #fff;
            animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
        }

        .lds-facebook div:nth-child(1) {
            left: 8px;
            animation-delay: -0.24s;
        }

        .lds-facebook div:nth-child(2) {
            left: 32px;
            animation-delay: -0.12s;
        }

        .lds-facebook div:nth-child(3) {
            left: 56px;
            animation-delay: 0;
        }

        @keyframes lds-facebook {
            0% {
                top: 8px;
                height: 64px;
            }

            50%,
            100% {
                top: 24px;
                height: 32px;
            }
        }

        /* /////////////////////// */
        body,
        html {
            height: 100%;
            /*Siempre es necesario cuando trabajamos con alturas*/
        }

        #inferior {
            position: fixed;
            bottom: 0;
            width: 100%;
            height: 70px;
            padding-bottom: 16px;
            padding-top: 4px;
            background: #FFF;
            /*background: rgba(0,0,0,.90);*/
            /*border-top: 2px solid #E21B35;*/
            border-top: 2px solid #000;
            z-index: 2000;
            text-align: center;

        }

        .right_input {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            padding: 13px;
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php //require_once("includes/menu_gen.php;
            ?>

            <!-- page content -->
            <div class="right_col" role="main">
                <div class="">
                    <div class="clearfix"></div>

                    <!-- SECCION -->
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12"></div>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div class="x_content">
                                    <div style="display:flex;justify-content: space-between;">
                                        <a href="pedidos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
                                        <a href="javascript:void(0);" onclick="anular_cabecera(<?php echo $idcliente; ?>)"><button type="button" class="btn btn-dark"><span class="fa fa-trash-o">&nbsp;Anular</span></button></a>
                                    </div>

                                    <div class="col-md-12">
                                        <input type="hidden" name="ocidcliente" id="ocidcliente" value="<?php echo $idcliente ?>" />
                                        <div class="col-md-12 col-xs-12  form-group has-feedback" style="display:flex;justify-content:center;text-align:center;">
                                            <span id="datosc1">
                                                <h2> &nbsp; <?php echo $rz ?> &nbsp; RUC: &nbsp;<?php echo $ruc ?>
                                            </span>
                                        </div>

                                        <div class="col-md-12 col-xs-12  form-group has-feedback" style="display:flex;justify-content:center;text-align:center;">
                                            <span id="datoscodi"> &nbsp; Codigo Cliente: &nbsp;<?php echo $codi ?></span>
                                        </div>

                                        <div id="detalles_box" class="row hide">
                                            <div class="col-md-4  col-xs-12  form-group has-feedback" style="display:flex;justify-content:center;text-align:center;">
                                                <span id="datosc2">
                                                    <h2>Linea de cr&eacute;dito: <?php echo formatomoneda($lc, 0, 'N'); ?></h2>
                                                </span>
                                            </div>

                                            <div class="col-md-4  col-xs-12  form-group has-feedback" style="display:flex;justify-content:center;text-align:center;">
                                                <span id="facturas_vencidas">
                                                    <?php if ($facturasVencidas == 0) { ?>
                                                        <h2>Facturas vencidas: Sin Facturas Pendientes. </h2>
                                                    <?php } else { ?>
                                                        <h2><?php echo $facturasVencidas > 0 ? "Factura vencida: " : "Nota de Credito: "; ?> <?php echo formatomoneda($facturasVencidas, 0, 'N'); ?> </h2>
                                                    <?php } ?>
                                                </span>
                                            </div>

                                            <div class="col-md-4  col-xs-12  form-group has-feedback" style="display:flex;justify-content:center;text-align:center;">
                                                <span id="datosc3">
                                                    <h2>Saldo disponible: <?php echo formatomoneda($ls, 0, 'N'); ?></h2>
                                                </span>
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>
                                        <div class="row" style="margin:0px;"><a href="javascript:void(0);" onclick="detalles()"><span id="detalles_button" class="fa fa-arrow-down"></span>&nbsp;<div id="texto_detalle" style="display: inherit;">Mostrar Detalles.-</div></a></div>
                                    </div>
                                    <!-----------
                                    <div class="col-md-12" style="text-align:center;">
                                        <span class="fa fa-user"></span>&nbsp;Hola,&nbsp;<?php echo $nombres; ?>&nbsp;&nbsp;<?php echo $apellidos; ?>, tu zona asignada es: <?php echo ($_SESSION['zona']); ?> | Hoy confirmaste: <?php echo $tconf ?> pedido(s).<?php if ($tconf > 0) { ?><a href="mis_pedidos_confirmados.php?idv=<?php echo $idvendedor; ?>"> [Ver]</a> <?php } ?>
                                    </div>
                                    -------->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- SECCION -->
                    <div id="respuesta"></div>

                    <!-- SECCION -->
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div class="x_title">
                                    <div class="col-md-6 col-sm-6 col-xs-12" style="display:flex;">
                                        <input type="text" name="filtrarprod" id="filtrarprod" placeholder="Buscá tu producto" style="height: 40px; width: 100%;" />
                                        <a class="btn btn-default fa fa-search right_input " href="javascript:void(0);" onclick="buscarproducto()"></a>
                                    </div>

                                    <div class="col-md-6 col-sm-6 col-xs-12" style="display:flex;"></div>
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <select name="Categorias" id="Categorias" style="height:40px; width: 100%;" onchange="abrirlista(this);">
                                            <option value="" selected="selected">Filtrar Categorias</option>
                                            <?php foreach ($datos as $value) {
                                                $idp = $value['codRubro'];
                                                $descripcion = $value['descripcion'];
                                            ?>
                                                <option value="<?php echo $idp; ?>" onclick="//filtrar_categoria()"><?php echo $descripcion; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div id="cargaproductos">
                                        <?php require_once("productos.php") ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- POPUP DE MODAL OCULTO -->
                    <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                                    </button>
                                    <h4 class="modal-title" id="myModalLabel"></h4>
                                </div>
                                <div class="modal-body" id="modal_cuerpo">
                                </div>
                                <div id="modal_resultado">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- POPUP DE MODAL OCULTO -->
    </div>
    <!-- /page content -->
    <script>
        //////////////////categoria
        function detalles() {
            $("#detalles_box").toggleClass('hide');
            $("#detalles_box").toggleClass("show");
            $("#detalles_button").toggleClass('fa-arrow-down');
            $("#detalles_button").toggleClass("fa-arrow-up");
            var texto = $("#texto_detalle");
            console.log(texto.html());
            if (texto.html() == "Mostrar Detalles") {
                texto.html('Ocultar Detalles');
            } else {
                texto.html('Mostrar Detalles');

            }
        }

        ////////////////////////////////////////    
        function mensaje_modal(titulo = "", mensaje = "") {
            $("#myModalLabel").html(titulo);
            $("#modal_cuerpo").html(mensaje);
            $("#modal_resultado").html("");
            $("#dialogobox").modal("show");
        }

        function buscarcliente() {
            $("#myModalLabel").html("Busqueda de clientes");
            $("#modal_cuerpo").html("");
            $("#modal_resultado").html("");

            var parametros = "";
            $.ajax({
                data: parametros,
                url: 'buscarclientev.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    $("#modal_cuerpo").html("");
                },
                success: function(response) {
                    $("#modal_cuerpo").html(response);
                    //setTimeout(function(){$('#textocatesub').html(nombresub)},50);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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
            $("#dialogobox").modal("show");
        }

        function buscar_datos() {
            //alert('llega');
            var rz = "";
            var codclie = "";
            var ruc = "";
            rz = $("#razo").val();
            codclie = $("#codiclie").val();
            ruc = $("#ruc").val();
            var parametros = {
                "rz": rz,
                "codclie": codclie,
                "ruc": ruc,
                "codZona": <?php echo $_SESSION['idzona'] ?>,
                "idvendedor": <?php echo $_SESSION['idvendedor'] ?>
            };
            $.ajax({
                data: parametros,
                url: 'buscar_cliente_ws.php',
                type: 'post',
                timeout: 9000, // TODO: cambiando a 9 segundos I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    //$("#modal_resultado").html("");
                },
                success: function(response) {
                    //alert(response);
                    $("#modal_resultado").html(response);
                    //setTimeout(function(){$('#textocatesub').html(nombresub)},50);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

        function abrirlista(option_selected, nombresub) {
            //alert('llega');
            var textoSeleccionado = option_selected.options[option_selected.selectedIndex].textContent;
            var valorSeleccionado = option_selected.value;
            var desc = <?php echo $descuento_valor ? "\"$descuento_valor\"" : "'N'"; ?>;
            desc = desc.toString().replace(/'/g, '');
            var texto = document.getElementById('filtrarprod').value;
            var parametros = {
                "idcategoria": valorSeleccionado,
                "categoria": textoSeleccionado,
                "palabra": texto,
                "descuento": desc,
                "categoria_busqueda": 1
            };
            $.ajax({
                data: parametros,
                url: 'productos.php',
                type: 'post',
                timeout: 11000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    $("#cargaproductos").html("Esperando respuesta del servidor...");
                },
                success: function(response) {
                    $("#cargaproductos").html(response);
                    //setTimeout(function(){$('#textocatesub').html(nombresub)},50);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

        function buscarproducto() {
            var texto = document.getElementById('filtrarprod').value;
            texto = texto.toUpperCase();
            var desc = <?php echo $descuento_valor ? "\"$descuento_valor\"" : "'N'"; ?>;
            console.log(texto);
            //alert(texto);
            var parametros = {
                "buscar": texto,
                "descuento": desc,
                "bus": 1
            };
            $.ajax({
                data: parametros,
                url: 'productos.php',
                type: 'post',
                timeout: 11000, // TODO: Cambiando timeout de 3 segundos a 11
                beforeSend: function() {
                    $("#cargaproductos").html("<div style='border:1px solid #000; background-color:#EEE;'>Esperando respuesta del servidor...</div>");
                },
                success: function(response) {
                    $("#cargaproductos").html(response);
                    //setTimeout(function(){$('#textocatesub').html(nombresub)},50);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

        function seleccionar_producto(idproducto, stock) {
            document.getElementById('preloader-overlay').style.display = 'flex';
            $("#bt_" + idproducto).prop('disabled', true);
            var idvendedor = <?php echo $idvendedor ?>;
            var cantidad = $("#cant_" + idproducto).val();
            var precio = $("#ocprodu_" + idproducto).val();
            var ds = $("#dsprodu_" + idproducto).val();
            var idcliente = $("#ocidcliente").val();
            if (parseInt(cantidad) > parseInt(stock)) {
                $("#bt_" + idproducto).prop('disabled', false);
                // TODO: hacer con modal listo
                // alert("- Stock no disponible");
                mensaje_modal("¡Atención!", "<h2> No se posee en stock la cantidad deseada.</h2>");
                return;
            }
            if (idcliente == '') {
                alert('debe seleccionar un cliente');
            } else {
                var parametros = {
                    "producto": idproducto,
                    "cantidad": cantidad,
                    "vendedor": idvendedor,
                    "precio": precio,
                    "idcliente": idcliente,
                    "descripcion": ds
                };
                $.ajax({
                    data: parametros,
                    url: 'carrito_ws.php',
                    type: 'post',
                    timeout: 5000, // I chose 3 secs for kicks: 3000
                    beforeSend: function() {
                        $("#carritodiv").html("");
                    },
                    success: function(response) {
                        console.log((response)["error"]);
                        //$("#respuesta").html(response);
                        if (JSON.parse(response)["error"]) {
                            // TODO: cambar a modal
                            mensaje_modal("ERROR", JSON.parse(response)["error"]);
                        }
                        // $("#datosc2").html();
                        $("#carritodiv").html(response);
                        if (response == 'LISTO') {
                            $("#bts_" + idproducto).prop('enabled', true);
                            $("#cant_" + idproducto).val("");
                            //$("#bts_"+idproducto).prop('disabled', false);


                        }
                        setTimeout(function() {
                            recargarpie(idcliente);
                            document.getElementById('preloader-overlay').style.display = 'none';
                        }, 3000);
                        //setTimeout(function(){$('#textocatesub').html(nombresub)},50);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status == 404) {
                            alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                        } else if (jqXHR.status == 0) {
                            alert('Se ha rechazado la conexión.');
                        } else {
                            alert(jqXHR.status + ' ' + errorThrown);
                        }
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {

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

        ////////////////////////////////////////////////////////////////////////
        function alerta_carrito(clase, error, titulo) {
            var alertaClase = 'alert-' + clase;
            if (clase == "info") {
                $('#boxErroresCarrito').removeClass('alert-danger');
            } else {
                $('#boxErroresCarrito').removeClass('alert-info');
            }
            $('#tituloErroresCarrito').html(titulo);
            $('#boxErroresCarrito').addClass(alertaClase);
            $('#boxErroresCarrito').removeClass('hide');
            $("#erroresCarrito").html(error);
            $('#boxErroresCarrito').addClass('show');
        }

        function producto_descuento_carrito() {
            var idproducto = $("#form_carrito #codigo_producto").val();
            var cantidad = $("#form_carrito #cantidad").val();
            var precio = $("#form_carrito #precio").val();
            var parametros = {
                "idproducto": idproducto,
                "cantidad": cantidad,
                "precio": precio,
            };
            console.log(parametros);
            $.ajax({
                data: parametros,
                url: 'buscar_descuentos_productos_ws.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    // $("#carritodiv").html("");
                },  
                success: function(response) {
                    console.log(response);


                    if (JSON.parse(response)["porcentaje"] != 0) {
                        var mensaje = "Se ha confirmado el descuento del " + JSON.parse(response)["porcentaje"] +
                            "% para la cantidad de productos solicitados. A partir de ahora, el valor unitario es de " + JSON.parse(response)["precio"].toLocaleString('es') + "Gs, el cual se aplicará al agregarlo en el pedido.";
                        alerta_carrito("info", mensaje, "Atención");
                    } else {
                        alerta_carrito("info", JSON.parse(response)["mensaje"], "Atención");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

        function producto_descuento(idproducto, stock) {
            // var cantidad=$("#cant_"+idproducto).val();
            var cantidad = 0;
            var precio = $("#ocprodu_" + idproducto).val();

            var parametros = {
                "idproducto": idproducto,
                "cantidad": cantidad,
                "precio": precio,
            };

            $.ajax({
                data: parametros,
                url: 'buscar_descuentos_productos_ws.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    // $("#carritodiv").html("");
                },
                success: function(response) {

                    if (JSON.parse(response)["porcentaje"] != 0) {
                        var mensaje = "Se ha confirmado el descuento del " + JSON.parse(response)["porcentaje"] +
                            "% para la cantidad de productos solicitados. A partir de ahora, el valor unitario es de " + JSON.parse(response)["precio"].toLocaleString('es') + "Gs, el cual se aplicará al agregarlo en el pedido.";
                        mensaje_modal("Descuento", mensaje);
                    } else {
                        mensaje_modal("Descuento", JSON.parse(response)["mensaje"]);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

        ////////////////////////////////////////////////////////////////////////    
        function seleccionarcliente(idcliente) {

            if (idcliente != '') {
                var datos = $("#ocdclie_" + idcliente).val();
                var exp = datos.split("|");
                var html1 = "<strong>Cliente ID: " + exp[1] + " " + exp[0] + " - R.U.C: " + exp[2] + "</strong>";
                var html2 = "<span style='color:red'>Linea Cred: " + exp[3] + " - Disponible: " + exp[4] + " - Pendientes F: " + exp[5] + "</span>";
                $("#ocidcliente").val(exp[1]);
                $("#datosc1").html(html1);
                $("#datosc2").html(html2);
                $("#dialogobox").modal("hide");
                recargarpie(exp[1]);
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            var idcliente = $("#ocidcliente").val();
            recargarpie(idcliente);
        });

        function recargarpie(idcliente) {
            var parametros = {
                "idcliente": idcliente,
                "ls": <?php echo intval($ls); ?>
            };
            $.ajax({
                data: parametros,
                url: 'calculin.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    $("#inferior").html("");
                },
                success: function(response) {
                    // alert(response);
                    $("#inferior").html(response);

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

        function mostrarpedidos(idcliente) {
            $("#myModalLabel").html("Productos seleccionados");
            $("#modal_cuerpo").html("");
            $("#modal_resultado").html("");
            var desc = <?php echo $descuento_valor ? "\"$descuento_valor\"" : "'N'"; ?>;
            var parametros = {
                "idcliente": idcliente,
                "descuento": desc
            };
            $.ajax({
                data: parametros,
                url: 'mostrar_carrito.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    //$("#modal_cuerpo").html("");
                },
                success: function(response) {
                    //alert(response);
                    $("#modal_cuerpo").html(response);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

            $("#dialogobox").modal("show");
        }

        function anular_cabecera(idcliente) {

            var parametros = {
                "idcliente": idcliente
            };
            $.ajax({
                data: parametros,
                url: 'anular_cabecera.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    //$("#modal_cuerpo").html("");
                },
                success: function(response) {
                    if (JSON.parse(response)["success"] == true) {
                        location.href = "pedidos.php";
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            });
        }

        function terminar(idcliente) {
            $("#myModalLabel").html("Confirmar productos");
            $("#modal_cuerpo").html("");
            $("#modal_resultado").html("");
            var parametros = {
                "idcliente": idcliente
            };
            $.ajax({
                data: parametros,
                url: 'mostrar_carrito_final.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    //$("#modal_cuerpo").html("");
                },
                success: function(response) {
                    console.log(response);
                    $("#modal_cuerpo").html(response);
                    getLocation();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

            $("#dialogobox").modal("show");
        }

        function chaumerca(idunico, idcliente, idproducto) {
            var parametros = {
                "idserial": idunico
            };
            $.ajax({
                data: parametros,
                url: 'chaumerca.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {
                    $("#modal_resultado").html("");
                },
                success: function(response) {
                    console.log(response);
                    $("#modal_resultado").html(response);
                    if (response == "LISTO") {
                        $("#bt_" + idproducto).prop('disabled', false);
                        $("#cant_" + idproducto).val("");
                        setTimeout(function() {
                            recargarpie(idcliente);
                        }, 3000);
                    }
                    //setTimeout(function(){$('#textocatesub').html(nombresub)},50);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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
            $("#dialogobox").modal("hide");
        }

        function restar_cantidad_update(id, idcliente, idserialunico, precio, stock) {
            document.getElementById('preloader-overlay').style.display = 'flex';
            var input = $("#carrito_cant_" + id);
            var inputValue = input.val(); // Obtener el valor del input
            var desc = <?php echo $descuento_valor ? "\"$descuento_valor\"" : "'N'"; ?>;

            // Si el valor es una cadena vacía o nulo, asignar 0; de lo contrario, convertir a entero
            var intValue = inputValue !== "" ? parseInt(inputValue, 10) : 0;

            // Restar 1 del valor actual hasta que sea cero
            if (intValue > 0) {
                intValue--; // Restar 1 al valor actual
                input.val(intValue);
                event.preventDefault();
                idunico = idserialunico;
                producto = id;
                cantidad = intValue;
                precio = precio;
                stock = stock;
                idcliente = idcliente;
                if (parseInt(cantidad) > parseInt(stock)) {
                    alerta_carrito("danger", "<h2> No se posee en stock la cantidad deseada.</h2>", "¡Atención!");
                    return;
                }
                var parametros = {
                    "idunico": idunico,
                    "producto": producto,
                    "cantidad": cantidad,
                    "precio": precio,
                    "stock": stock
                };
                $.ajax({
                    data: parametros,
                    url: 'editar_carrito.php',
                    type: 'post',
                    timeout: 5000, // I chose 3 secs for kicks: 3000
                    beforeSend: function() {

                    },
                    success: function(response) {
                        // alert(response);
                        var parametros = {
                            "idcliente": idcliente,
                            "descuento": desc
                        };
                        $.ajax({
                            data: parametros,
                            url: 'mostrar_carrito.php',
                            type: 'post',
                            timeout: 5000, // I chose 3 secs for kicks: 3000
                            beforeSend: function() {
                                //$("#modal_cuerpo").html("");
                            },
                            success: function(response) {
                                //alert(response);
                                $("#modal_cuerpo").html(response);

                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                if (jqXHR.status == 404) {
                                    alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                                } else if (jqXHR.status == 0) {
                                    alert('Se ha rechazado la conexión.');
                                } else {
                                    alert(jqXHR.status + ' ' + errorThrown);
                                }
                            }
                        })
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status == 404) {
                            alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                        } else if (jqXHR.status == 0) {
                            alert('Se ha rechazado la conexión.');
                        } else {
                            alert(jqXHR.status + ' ' + errorThrown);
                        }
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {

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
                // cerrar_editar_carrito_form();
                setTimeout(function() {
                    recargarpie(idcliente);
                    document.getElementById('preloader-overlay').style.display = 'none';
                }, 3000);
            }
        }

        function sumar_cantidad_update(id, idcliente, idserialunico, precio, stock) {
            document.getElementById('preloader-overlay').style.display = 'flex';
            var input = $("#carrito_cant_" + id);
            var inputValue = input.val(); // Obtener el valor del input
            var desc = <?php echo $descuento_valor ? "\"$descuento_valor\"" : "'N'"; ?>;

            // Si el valor es una cadena vacía o nulo, asignar 0; de lo contrario, convertir a entero
            var intValue = inputValue !== "" ? parseInt(inputValue, 10) : 0;

            // Restar 1 del valor actual hasta que sea cero

            intValue++; // Restar 1 al valor actual
            input.val(intValue);
            /////////////////////////////////////////////////////
            event.preventDefault();
            idunico = idserialunico;
            producto = id;
            cantidad = intValue;
            precio = precio;
            stock = stock;
            idcliente = idcliente;
            if (parseInt(cantidad) > parseInt(stock)) {
                alerta_carrito("danger", "<h2> No se posee en stock la cantidad deseada.</h2>", "¡Atención!");
                return;
            }
            var parametros = {
                "idunico": idunico,
                "producto": producto,
                "cantidad": cantidad,
                "precio": precio,
                "stock": stock
            };
            $.ajax({
                data: parametros,
                url: 'editar_carrito.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {

                },
                success: function(response) {
                    // alert(response);
                    var parametros = {
                        "idcliente": idcliente,
                        "descuento": desc
                    };
                    $.ajax({
                        data: parametros,
                        url: 'mostrar_carrito.php',
                        type: 'post',
                        timeout: 5000, // I chose 3 secs for kicks: 3000
                        beforeSend: function() {
                            //$("#modal_cuerpo").html("");
                        },
                        success: function(response) {
                            //alert(response);
                            $("#modal_cuerpo").html(response);

                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            if (jqXHR.status == 404) {
                                alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                            } else if (jqXHR.status == 0) {
                                alert('Se ha rechazado la conexión.');
                            } else {
                                alert(jqXHR.status + ' ' + errorThrown);
                            }
                        }
                    })
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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
            // cerrar_editar_carrito_form();
            setTimeout(function() {
                recargarpie(idcliente);
                document.getElementById('preloader-overlay').style.display = 'none';
            }, 3000);

            // mostrarpedidos(idcliente);
            // var closeButton = document.querySelector('button.close');
            // closeButton.click();
        }

        function update_cantidad(value, id, idcliente, idserialunico, precio, stock) {
            console.log(stock);
            if (isNaN(value)) {
                alerta_carrito("danger", "<h2> No se debe ingresar letras en la cantidad.</h2>", "¡Atención!");
                document.getElementById('preloader-overlay').style.display = 'none';
                return;
            }
            if (parseInt(value) < 0) {
                return;
            }
            if ((value) == "") {
                return;
            }
            document.getElementById('preloader-overlay').style.display = 'flex';

            idunico = idserialunico;
            producto = id;
            cantidad = value;
            precio = precio;
            stock = stock;
            idcliente = idcliente;
            var desc = <?php echo $descuento_valor ? "\"$descuento_valor\"" : "'N'"; ?>;
            if (parseInt(cantidad) > parseInt(stock)) {
                alerta_carrito("danger", "<h2> No se posee en stock la cantidad deseada.</h2>", "¡Atención!");
                document.getElementById('preloader-overlay').style.display = 'none';
                return;
            }
            var parametros = {
                "idunico": idunico,
                "producto": producto,
                "cantidad": cantidad,
                "precio": precio,
                "stock": stock
            };
            console.log(parametros);
            $.ajax({
                data: parametros,
                url: 'editar_carrito.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {

                },
                success: function(response) {
                    // alert(response);
                    var parametros = {
                        "idcliente": idcliente,
                        "descuento": desc
                    };
                    $.ajax({
                        data: parametros,
                        url: 'mostrar_carrito.php',
                        type: 'post',
                        timeout: 5000, // I chose 3 secs for kicks: 3000
                        beforeSend: function() {
                            //$("#modal_cuerpo").html("");
                        },
                        success: function(response) {
                            //alert(response);
                            $("#modal_cuerpo").html(response);

                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            if (jqXHR.status == 404) {
                                alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                            } else if (jqXHR.status == 0) {
                                alert('Se ha rechazado la conexión.');
                            } else {
                                alert(jqXHR.status + ' ' + errorThrown);
                            }
                        }


                    })

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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
            // cerrar_editar_carrito_form();
            setTimeout(function() {
                recargarpie(idcliente);
                document.getElementById('preloader-overlay').style.display = 'none';
            }, 3000);
        }

        function abrir_editar_carrito_form(idunico, idcliente, idproducto, producto, cantidad, precio) {
            $("#form_carrito").removeClass("hide");
            $("#form_carrito").addClass("show");
            $("#tabla_carrito").addClass("hide");
            $("#tabla_carrito").removeClass("show");
            $("#form_carrito #id_producto").val(idunico);
            $("#form_carrito #codigo_producto").val(idproducto);
            $("#form_carrito #idcliente").val(idcliente);
            $("#form_carrito #producto").val(producto);
            $("#form_carrito #cantidad").val(cantidad);
            $("#form_carrito #precio").val(precio);
            var desc = <?php echo $descuento_valor ? "\"$descuento_valor\"" : "'N'"; ?>;
            var parametros = {
                "codigo_producto": idproducto,
                "descuento": desc
            };
            $.ajax({
                data: parametros,
                url: 'verificar_stock_ws.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {

                },
                success: function(response) {
                    // alert(response);
                    console.log(JSON.parse(response)["stock"]);
                    $("#stock").val(JSON.parse(response)["stock"]);

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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

        function cerrar_editar_carrito_form() {
            $("#form_carrito").removeClass("show");
            $("#form_carrito").addClass("hide");
            $("#tabla_carrito").addClass("show");
            $("#tabla_carrito").removeClass("hide");
        }

        function editar_producto_carrito() {
            event.preventDefault();
            idunico = $("#id_producto").val();
            producto = $("#codigo_producto").val();
            cantidad = $("#cantidad").val();
            precio = $("#precio").val();
            stock = $("#stock").val();
            idcliente = $("#idcliente").val();
            if (parseInt(cantidad) > parseInt(stock)) {
                alerta_carrito("danger", "<h2> No se posee en stock la cantidad deseada.</h2>", "¡Atención!");
                return;
            }
            var parametros = {
                "idunico": idunico,
                "producto": producto,
                "cantidad": cantidad,
                "precio": precio,
                "stock": stock
            };
            $.ajax({
                data: parametros,
                url: 'editar_carrito.php',
                type: 'post',
                timeout: 5000, // I chose 3 secs for kicks: 3000
                beforeSend: function() {

                },
                success: function(response) {
                    // alert(response);
                    console.log(JSON.parse(response));
                    $("#cant_" + idunico).html(JSON.parse(response)['cantidad']);
                    $("#precio_" + idunico).html(JSON.parse(response)['precio']);
                    $("#subtotal_" + idunico).html(JSON.parse(response)['subtotal']);

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (jqXHR.status == 404) {
                        alert('Pagina no encontrada. ' + jqXHR.status + ' ' + errorThrown);
                    } else if (jqXHR.status == 0) {
                        alert('Se ha rechazado la conexión.');
                    } else {
                        alert(jqXHR.status + ' ' + errorThrown);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {

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
            // cerrar_editar_carrito_form();
            setTimeout(function() {
                recargarpie(idcliente);
            }, 3000);

            // mostrarpedidos(idcliente);
            var closeButton = document.querySelector('button.close');
            closeButton.click();
        }

        // Obtenemos la latitud y longitud real del vendedor
        function getLocation() {

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(sendPosition);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        // Tomamos la latitud y logitud y lo mandamos a buscar_clientes_visitados_ws.php para comparacion
        function sendPosition(position) {

            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            // Mandamos la longitud y latitud a la url
            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        latitud: lat,
                        longitud: lon
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Respuesta:", data);
                })
                .catch((error) => {
                    console.error("Error:", error);
                });
        }

        window.onload = function() {
            document.getElementById("filtrarprod").focus();

            <?php if ($facturasVencidas < 0) { ?>
                mensaje_modal("¡Atención!", "Posees Notas de Credito a favor!");
            <?php } ?>
            <?php if ($facturasVencidas > 0) { ?>
                mensaje_modal("¡Atención!", "Posees Facturas Pendientes!");
            <?php } ?>
        };
    </script>

    <div>
        <div id="pedidos_conenedor"></div>
        <div id="carritodiv"></div>
    </div>

    <?php require_once("includes/footer_gen.php"); ?>

    <div style="height:70px;">
        <div id="inferior">
            <?php require_once('calculin.php'); ?>
        </div>
    </div>

    <div id="preloader-overlay">
        <div class="lds-facebook">
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</body>

</html>