<?php
//if (!isset($_SESSION)) {
//    session_start();
//}
//require_once('includes/conexion.php');
//require_once('includes/funciones.php');
//require_once('includes/funciones_busqueda.php');

//http://181.94.221.41:1704/wsrde/busquedaVendedores?busqueda=6&tipoCampo=CODIGO_VENDEDOR

//echo "Id zona: ".$_SESSION['idzona'];
//print_r($_SESSION['idzona']);

//if (!isset($_SESSION['idvendedor']) and !isset($_SESSION['nombres']) and !isset($_SESSION['zona']) and !isset($_SESSION['idzona'])) {

  //  header("Location: index.php");
//}
$nombreDias = array(
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
    7 => 'Domingo'
);
$diaDeLaSemana = date("N");
//Listamos las zonas asignadas al vendedor para mostrarlas como una lista 
$idvendedor = intval( 21); //$_SESSION['idvendedor']);
//echo $idvendedor; exit;
//$nombres = trim($_SESSION['nombres']);
//$apellidos = trim($_SESSION['apellidos']);

$url1 = "http://181.94.221.41:1704/wsrde/busquedaVendedores?busqueda=$idvendedor&tipoCampo=CODIGO_VENDEDOR";
$urlconsulta = $url1;
//$respuesta=file_get_contents("$urlconsulta");
//$respuesta = abrir_url_simple("$urlconsulta");
//$datos = json_decode($respuesta, true);
$idzona = 0;
$ahora = date("Y-m-d");
//cantidad de pedidos confirmados en fecha
$buscar = "select count(idtempo) as cantidad from tempocar where date(confirmado_el)='$ahora' and estado=3 and idvendedor=$idvendedor";
//$rfg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//$tconf = $rfg->fields['cantidad'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <style type="text/css">
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

        /***************************** */
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
            <?php //require_once("includes/menu_gen.php"); 
            ?>
            <!-- page content -->
            <div class="right_col" role="main">
                <div class="">
                    <div class="clearfix"></div>
                    <!-- SECCION -->
                    <div class="row" id="main_menu">

                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div style="display: flex;justify-content: space-between;">
                                    <p>MIS ZONAS ASIGNADAS</p>
                                    <a href="logout.php" class="btn btn-sm btn-default"><span class="fa fa-sign-out"></span> Salir</a>
                                </div>
                                <div class="col-md-12" style="text-align:center;">
    <table class="table table-bordered hover_table">
        <thead>
            <tr>
            </tr>
            <tr>

            </tr>
            <tr>
                <th>INICIAR PEDIDO</th>
                <th>No Compra</th>
                <th>Ruc</th>
                <th>Razon Social</th>
                <th>Direccion</th>
                <th>Codigo Cliente</th>
                
            </tr>
        </thead>
        <tbody>
            <tr>    
               <td> <a href="javascript:void(0);" class="btn btn-sm btn-default" onclick=""><span class="fa fa-shopping-cart  fa-1x" style="color:#5A738E;"></span> </a></td>
          </tr>

        <?php 

            ?>
            </tbody>
        </table>
                                    <!--<span class="fa fa-user"></span>&nbsp;Hola,&nbsp;<?php echo $nombres; ?>&nbsp;&nbsp;<?php echo $apellidos; ?>, tu zona asignada es: <?php //echo ($_SESSION['zona']); ?> | Hoy confirmaste: <?php echo $tconf ?> pedido(s).<?php if ($tconf > 0) { ?><a onclick="ver_pedidos_confirmados(<?php echo $idvendedor; ?>)" href="javascript:void(0);"> [Ver]</a> <?php } ?>
    -->
                                </div>
                                <?php //foreach ($datos as $dato) {

                                    //$idzona = intval($dato['codZona']);

                                    //$describezona = trim($dato['nombreZona']);

                                ?>
                                    <hr />
                                    <div class="row" style="margin:0px;"><a href="javascript:void(0);" onclick="abrir_zonas(<?php echo $idzona ?>)"><span class="fa fa-arrow-right"></span>&nbsp; Ruta del D&iacute;a: <?php echo $nombreDias[$diaDeLaSemana] ?></a></div>
                                <?php //} ?>

                                <div id="zonas" class="hide" style="height:200px; overflow-y:scroll;"></div>
                                <hr>
                                <div class="row" style="margin:0px;"><a href="javascript:void(0);" onclick="buscar_visitas_activas()"><span class="fa fa-arrow-right"></span>&nbsp; Clientes Visitados</a></div>
                                </br>
                                <div id="zonas_visitadas" style="height:200px; overflow-y:scroll;"></div>


                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">

                                <div class="col-md-6 col-sm-6">
                                    CLIENTES DEL VENDEDOR
                                    <input type="hidden" name="ocidcliente" id="ocidcliente" value="" />
                                    <div class="col-md-1 col-sm-6  col-xs-12 ">
                                        <button name="dclie" onclick="buscarcliente();"><span class="fa fa-search fa-2x"></span></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- SECCION -->
                    <div id="preloader-overlay">
                        <div class="lds-facebook">
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Consultar Producto</h2>
                                    <ul class="nav navbar-right panel_toolbox">
                                        <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                        </li>
                                    </ul>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">



                                    <div class="table-responsive" style="border:none;">
                                        <div class="col-md-12 col-sm-12 col-xs-12" style="display:flex;">

                                            <input type="text" name="filtrarprod" id="filtrarprod" placeholder="Buscá tu producto" style="height: 40px; width: 100%;" />
                                            <a class="btn btn-default fa fa-search right_input " href="javascript:void(0);" onclick="buscarproducto()"></a>


                                        </div>
                                        <br>
                                        <div class="clearfix"></div>
                                        <div id="cargaproductos"></div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- SECCION -->



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
    </div>
    <!-- /page content -->


    </div>

    </div>
    <?php // require_once("includes/footer_gen.php"); ?>

    <div style="height:70px;">


    </div>
    <script>
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
                url: 'buscar_producto_ws.php',
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

        function abrir_zonas(numero) {
            var idvende = <?php echo $idvendedor ?>;
            var parametros = {
                "idzona": numero,
                "idvende": idvende
            };
            $("#zonas").removeClass("hide");
            $("#zonas").addClass("show");
           
            $.ajax({
                data: parametros,
                url: 'buscar_cliente_ws_zonas.php',
                type: 'post',
                beforeSend: function() {
                    $("#zonas").html("Buscando clientes por zonas...aguarde");
                },
                success: function(response) {
                    //alert(response);
                    $("#zonas").html(response);

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

        function buscarcliente() {
            $("#myModalLabel").html("Busqueda de clientes");
            $("#modal_cuerpo").html("");
            $("#modal_resultado").html("");

            var parametros = "";
            $.ajax({
                data: parametros,
                url: 'buscarclientev.php',
                type: 'post',
                beforeSend: function() {
                    $("#modal_cuerpo").html("");
                },
                success: function(response) {
                    $("#modal_cuerpo").html(response);
                    //setTimeout(function(){$('#textocatesub').html(nombresub)},50);
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
            codclie = $("#codiclie").val();s
            ruc = $("#ruc").val();
            var idv = <?php echo $idvendedor ?>;

            var parametros = {
                "rz": rz,
                "codclie": codclie,
                "ruc": ruc,
                "codZona": <?php //echo $_SESSION['idzona'] ?>,
                "idvendedor": idv
            };
            // alert(parametros);
            // mensaje_modal("hpa","asd");
            $.ajax({
                data: parametros,
                url: 'buscar_cliente_ws.php',
                type: 'post',
                beforeSend: function() {
                    $("#modal_resultado").html("Buscando clientes por zonas en ws...aguarde");
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

        function limpiar(cual) {
            if (cual == 1) {

                $("#razo").val("");
                $("#ruc").val("");
                //$("#codiclie").val("");
            }
            if (cual == 2) {
                //$("#razo").val("");
                $("#ruc").val("");
                $("#codiclie").val("");
            }
            if (cual == 3) {
                $("#razo").val("");
                //$("#ruc").val("");
                $("#codiclie").val("");
            }
        }

        function ver_pedidos_confirmados(idvendedor) {
            document.getElementById('preloader-overlay').style.display = 'flex';
            window.location = ('mis_pedidos_confirmados.php?idv=' + idvendedor);
        }

        function seleccionarcliente(idcliente, codLista) {
            document.getElementById('preloader-overlay').style.display = 'flex';

            window.location = ('pedidos.php?idc=' + idcliente + '&codLista=' + codLista);

        }
        document.addEventListener("DOMContentLoaded", function() {

            // buscar_visitas_activas();
        });

        function mensaje_modal(titulo = "", mensaje = "") {
            $("#myModalLabel").html(titulo);
            $("#modal_cuerpo").html(mensaje);
            $("#modal_resultado").html("");
            $("#dialogobox").modal("show");
        }

        function buscar_visitas_activas() {
            var parametros = {
                "idvendedor": <?php echo isset($idvendedor) ? $idvendedor : "" ?>,
            };
            $.ajax({
                data: parametros,
                url: 'buscar_clientes_visitados_ws.php',
                type: 'post',
                beforeSend: function() {
                    $("#zonas_visitadas").html("Buscando clientes ya visitados en este dia... aguarde");
                },
                success: function(response) {
                    if (response.length == 2) {
                        $("#zonas_visitadas").addClass("hide");
                    } else {

                        $("#zonas_visitadas").removeClass("hide");
                        $("#zonas_visitadas").addClass("show");
                        $("#zonas_visitadas").html(response);
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
        }
    </script>
    <script>
function guarda_gps(coordenadas){
	if(typeof coordenadas != 'undefined' && typeof coordenadas != null && coordenadas != ''){
		var coord = coordenadas.split(";");
		var latitud = coord[0];
		var longitud = coord[1];
		$("#lt").val(latitud);
		$("#lg").val(longitud);
		alert('Lt: '+latitud+' Lg: '+longitud);
	}else{
		alert('No se enviaron parametros. '+coordenadas);	
	}

}
function getLocation(){
	// por APP
	if(!(typeof ApiChannel === 'undefined')){
		ApiChannel.postMessage('<?php

        $parametros_array_tk = [
            'gps_obtener' => 'S', // S / N
        ];
echo texto_para_app($parametros_array_tk);

?>');
	// por NAVEGADOR
	}else{
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(showPosition);
		} else { 
		   // x.innerHTML = "Geolocation is not supported by this browser.";
		   document.getElementById('lt').value='0';
		   document.getElementById('lg').value='0';
		}
	}
}
function showPosition(position) {
	document.getElementById('lt').value=position.coords.latitude;
   	document.getElementById('lg').value=position.coords.longitude;
}
function envia_form(){
	document.getElementById('lof').submit();
}
</script>
</body>

</html>