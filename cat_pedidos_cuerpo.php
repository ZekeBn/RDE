 <?php
/*--------------------------------------------------------
Inserta y genera cabecera de pedidos desde formulario
en cat_pedidos_add.php
UR: 12/05/2021

---------------------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");




if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {
    //print_r($_POST);exit;


    //Identificador del presupuesto: si es que hay
    $idpresupuesto = intval($_POST['idpresupuesto']);//so lo si ya se genero previamnete un presu
    $idtipoevento = intval($_POST['idtipoev']);
    $fechaevento = date("Y-m-d", strtotime($_POST['evento_para']));
    $horaevento = date("H:i:s", strtotime($_POST['hora_entrega']));
    $cantpersona = intval($_POST['cantidad_personas']);
    $fechavalidez = antisqlinyeccion($_POST['valido_hasta'], 'date');
    $clienterz = antisqlinyeccion($_POST['clientel'], 'text');
    $idclientepedido = intval($_POST['ocidclientepedidol']);//CLiente que generó el pedido   ocidcsoli
    $idclientefactura = intval($_POST['ocidclientefactural']);//ocidc Cliente de facturacion
    $idclienterecibe = intval($_POST['ocidclientefactural']); //ocidcrec quien va recibir el pedido actual


    if ($idclienterecibe == 0) {
        $idclienterecibe = $idclientepedido;//si no vino x post, le asignamos al que genero el pedido
    }

    $direccion = antisqlinyeccion($_POST['domicilio'], 'text');

    $iddireccion = intval($_POST['iddomicilio']);
    $listaprecio = intval($_POST['listapre']);

    $idorganizador = intval($_POST['listaorg']);
    $iddecorador = intval($_POST['listadeco']);

    $comentariovisible = antisqlinyeccion($_POST['detalles'], 'text');

    $comentariointerno = antisqlinyeccion($_POST['interno'], 'text');
    $tipoenvio = intval($_POST['tipoenviooc']);

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





    if ($tipoenvio == 99 && $direccion == '') {
        $valido = "N";
        $errores .= " - Al ser delivery debe indicar la direcci&oacote;n de env&iacute;o.<br />";
    }

    $ubicacion = trim($_POST['mapz']);

    // si todo es correcto inserta
    if ($valido == "S") {

        //Verificamos la direccion ingresada si es que ya no existe en la base, si la misma ya esxiste
        if ($direccion != '') {
            $ub = trim($_REQUEST['ubicacion']);
            $ex = explode(",", $ub);
            $lat = trim($ex[0]);
            $lon = trim($ex[1]);

            if ($iddomicilio > 0) {

                $buscar = "Select * from clientes_direcciones where iddireccion=$iddomicilio  ";
            } else {

                $buscar = "Select * from clientes_direcciones where descripcion=$direccion  ";

            }
            $rdir = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //echo "DIRECCION: ".$buscar;exit;
            $iddireccion = intval($rdir->fields['iddireccion']);
            if ($iddireccion == 0) {
                //Damos de alta la direccion
                $insertar = "Insert into clientes_direcciones 
                (descripcion,latitud,longitud,compuesta,observacion,idcliente,estado,registrado_por,registrado_el)
                values 
                ($direccion,'$lat','$lon','$ub',NULL,$idclientefactura,1,$idusu,current_timestamp)
                
                ";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                //traemos el id de la direccion
                $buscar = "Select iddireccion from clientes_direcciones where registrado_por=$idusu and idcliente=$idclientefactura limit 1";
                $rdir = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $iddireccion = intval($rdir->fields['iddireccion']);
            }

        }






        //print_r($_POST);exit;



        $tipoenvio = intval($_POST['tipoenviooc']);


        $consulta = "
        insert into pedidos_eventos
        (idpresupuesto, idtipoev, id_cliente_solicita, fecha_solicitud, evento_para, hora_entrega, iddomicilio, cantidad_personas, adultos, ninhos, comentarios, idlistaprecio, estado, registrado_el, registrado_por, valido_hasta,
        idorganizador,iddecorador,comentario_interno,idcliente_recibe,idcliente_factura,tipoenvio,dire_entrega)
        values
        ($idpresupuesto, $idtipoevento, $idclientepedido,current_timestamp, '$fechaevento', '$horaevento', $iddireccion, $cantpersona, 0,
        0, $comentariovisible, $listaprecio, 1, current_timestamp, $idusu,$fechavalidez,
        $idorganizador,$iddecorador,$comentariointerno,$idclienterecibe,$idclientefactura,$tipoenvio,$direccion)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //traemos el id de la cabecera
        $buscar = "Select regid from pedidos_eventos where id_cliente_solicita=$idclientepedido and registrado_por=$idusu order by regid desc limit 1";
        $rg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $idreg = intval($rg->fields['regid']);
        header("location: cat_pedidos_cuerpo.php?evreg=$idreg");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

$idreg = intval($_REQUEST['evreg']);
if ($idreg == 0) {
    header("location: cat_pedidos_add.php");

    exit;
} else {
    //cargamos los valores basicos del pedido
    $buscar = "Select idpresupuesto,evento_para,hora_entrega,dire_entrega,
    (select telefono from cliente_pedido where idclienteped=pedidos_eventos.id_cliente_solicita) as telefono,
    (select email from cliente_pedido where idclienteped=pedidos_eventos.id_cliente_solicita) as email,
    (select nomape from cliente_pedido where idclienteped=pedidos_eventos.id_cliente_solicita) as solicitado_por,
    (select razon_social from cliente where idcliente=pedidos_eventos.idcliente_factura) as facturar,
    (select usuario from usuarios where idusu=pedidos_eventos.registrado_por) as usuarioreg
    from pedidos_eventos where regid=$idreg ";
    $rga = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

}



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
function generar_plantilla(){
    var cantidad_plantilla = $("#cantidad_plantilla").val();
    var idplantillaart = $("#idplantillaart").val();
    var parametros = {
      "idpedido"            : <?php echo $idreg; ?>,
      "idplantillaart"      : idplantillaart,
      "cantidad_plantilla"  : cantidad_plantilla
    };
    
    var direccionurl = 'cat_pedidos_cuerpo_gen.php';
    
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#carrito").html('Cargando...');                
        },
        success:  function (response) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    actualiza_carrito();
                }else{
                   $("#carrito").html(nl2br(obj.errores));
                }
                
            }else{
                alert(response);    
                $("#carrito").html(response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexi��n.');
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
function nl2br (str, is_xhtml) {
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';
  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
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
                    <h2><span class="fa fa-edit"></span></span>&nbsp;Pedido</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                             <input type="hidden" name="occantidad" id="occantidad" value="0" />
                            <input type="hidden" name="ocserial" id="ocserial" value="0" />
                            <input type="hidden" name="ocregunico" id="ocregunico" value="<?php echo $idreg ?>" />
                             <?php if (trim($errores) != "") { ?>
                                    <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
                                    </button>
                                    <strong>Errores:</strong><br /><?php echo $errores; ?>
                                    </div>
                                <?php } ?>
                                

                                <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente </label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                           <?php echo htmlentities($rga->fields['solicitado_por']); ?> 
                                            
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Facturacion </label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                           <?php echo htmlentities($rga->fields['facturar']); ?> 
                                            
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tel&eacute;fono</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <?php echo htmlentities($rga->fields['telefono']);?>                 
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Email</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <?php echo htmlentities($rga->fields['email']);?>                 
                                    </div>
                                </div>
                                
                                <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Evento</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                    
                                    
                                    <?php echo htmlentities(date("d/m/Y", strtotime($rga->fields['evento_para']))); ?>
                                                           
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hora entrega</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <?php echo htmlentities($rga->fields['hora_entrega']);?>                 
                                    </div>
                                </div>    
                                <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <?php echo htmlentities($rga->fields['dire_entrega']);?>                 
                                    </div>
                                </div>
                        
                                <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12"><?php echo $rga->fields['usuarioreg']?></div>
                                </div>

<div class="clearfix"></div>
<br />
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Plantilla *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
// consulta
$consulta = "
SELECT idplantillaart, nombre_plantilla
FROM plantilla_articulos
where
estado = 1
order by nombre_plantilla asc
 ";

// valor seleccionado
if (isset($_POST['idplantillaart'])) {
    $value_selected = htmlentities($_POST['idplantillaart']);
} else {
    $value_selected = htmlentities($rs->fields['idplantillaart']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idplantillaart',
    'id_campo' => 'idplantillaart',

    'nombre_campo_bd' => 'nombre_plantilla',
    'id_campo_bd' => 'idplantillaart',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cantidad_plantilla" id="cantidad_plantilla" value="<?php  if (isset($_POST['cantidad_plantilla'])) {
        echo htmlentities($_POST['cantidad_plantilla']);
    } else {
        echo htmlentities($rs->fields['cantidad_plantilla']);
    }?>" placeholder="Cantidad" class="form-control" required="required" />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="button" class="btn btn-success" onmouseup="generar_plantilla();"><span class="fa fa-check-square-o"></span> Generar</button>
        </div>
    </div>


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Datos del Pedido</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="col-md-6 col-sm-6 col-xs-12" style="width: 60%;" id="carrito">
                            <?php require_once('carrito_central.php'); ?>
                                
                      </div>
                            <div class="col-md-6 col-sm-6 col-xs-12" style="width: 40%;" >
                                <div class="x_panel">
                                      <div class="x_title">
                                        <h2><i class="fa fa-bars"></i> Items  <small>Productos disponibles para venta</small></h2>
                                        <div class="clearfix"></div>
                                      </div>
                                      <div class="x_content">
                                         <div class="form-group">
                                                <input type="text" autocomplete="off" name="bprodu" id="bprodu" onKeyup="filtrar(this.value,<?php echo $idmesa?>);"  style="width: 99%; height: 40px;" placeholder="Indique el producto a buscar" />
                                                <div id="listaproductos">
                                                    <?php require_once('cat_mini_productos_lista.php'); ?>

                                                </div>
                                          </div>
                                      </div>
                                </div>
                            </div>





                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
          </div>
        </div>
        <!-- /page content -->
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo"  style="min-height: 80px;" >
                        ...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
                  </div>
        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
    <script src="js/shortcut.js"></script>
      <script>
      function env(){
          var ocunico= $("#ocregunico").val();
          $("#ocregev").val(ocunico);
          $("#cp1").submit();
          
      }
      function agregar(cual){
          
          $("#myModalLabel").html("Agregar tipos eventos");
          var parametros = {
              
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_mini_eventos_tipos_add.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                    //$("#modal_cuerpo").html('Cargando...');                
                },
                success:  function (response) {
                    $("#modal_cuerpo").html(response);    
                }
            });
          
          
          
         
          $("#dialogobox").modal("show");
      }
      
      function registrar(cual){
          var describe=$("#describetipoev").val();
          if (describe!=""){
               var parametros = {
                      "describe" : describe,
                    "agregar" : 1
                };
                $.ajax({          
                    data:  parametros,
                    url:   'cat_mini_eventos_tipos.php',
                    type:  'post',
                    cache: false,
                    timeout: 3000,  // I chose 3 secs for kicks: 3000
                    crossDomain: true,
                    beforeSend: function () {    
                                        
                    },
                    success:  function (response) {
                        $("#tiposeventosdiv").html(response);
                        
                    }
                });

              
          }
         
         
          
          
         
          $("#dialogobox").modal("hide");
      }
function actualiza_carrito(){
    //alert('llega');
        var parametros = {
                "act" : 'S'
        };
        $.ajax({
                data:  parametros,
                url:   'carrito_central.php',
                type:  'post',
                beforeSend: function () {
                       //$("#carrito").html("Actualizando Carrito...");
                },
                success:  function (response) {
                        $("#carrito").html(response);
                }
        });
}
          
function borrar_todo(quien){
        
    var parametros = {
        "todo" : 'S'
    };
    if(window.confirm("Esta seguro que desea borrar TODO?")){    
            $.ajax({
                    data:  parametros,
                    url:   'carrito_borra.php',
                    type:  'post',
                    beforeSend: function () {
                            //$("#carrito").html("Borrando...");
                    },
                    success:  function (response) {
                            actualiza_carrito();
                            enfocarbusqueda();
                            
                        }
                    
            });
    }
}
          
function borrar(idprod,txt){
            
    var parametros = {
         "prod" : idprod
    };
    
    if(window.confirm("Esta seguro que desea borrar '"+txt+"'?")){    
        $.ajax({
                    data:  parametros,
                    url:   'carrito_borra.php',
                    type:  'post',
                    beforeSend: function () {
                            //$("#carrito").html("Actualizando Carrito...");
                    },
                    success:  function (response) {
                        actualiza_carrito();
                        
                    }
        });
    }
}
//------------------------------------Funciones a cambiar a un archivo cuando se finalice ------------------------------//
function enfocarbusqueda(){
    setTimeout(function(e){ $("#bprodu").focus(); }, 2000);    
}
function seleccionar(idprdser,valor){
    //Se usa previo al control de teclas de acciones para efectual los cambios deseados
    //colocamos los inp
    //alert(idprdser);alert(valor);
    $("#ocserial").val(idprdser);
    $("#occantidad").val(valor);         
}
          
function filtrar(texto){
    var parametros = {
            "texto" : texto
    };
    $.ajax({
            data:  parametros,
            url:   'cat_mini_productos_lista.php',
            type:  'post',
            beforeSend: function () {
                    
            },
            success:  function (response) {
                $("#listaproductos").html(response);
            }
    });
}
          
function apretarauto(id,prod1,prod2,quien){
    var id=$("#ocserial").val();
        var cantidad=$("#occantidad").val();
        var prod1=0;
        var prod2=0;
        
        //alert(idmesa);
        if(cantidad ==''){
            cantidad=1;
        }
        if(prod1 > 0){
            var precio = 0;
        }else{
            //Lista de Productos
            //var html = document.getElementById("produlis_"+id).innerHTML;
            var precio = document.getElementById("preciolis_"+id).value;            
        }
        var parametros = {
                "prod" : id,
                "cant" : cantidad,
                "precio" : precio,
                "prod_1" : prod1,
                "prod_2" : prod2
        };
       $.ajax({
                data:  parametros,
                url:   'carrito.php',
                type:  'post',
                beforeSend: function () {
                        if(prod1 > 0){
                            
                        }else{
                            
                            $("#carrito").html("Actualizando Carrito...");
                        }
                },
                success:  function (response) {
                    //alert(response);
                        if(prod1 > 0 && parseInt(response) > 0){
                            
                            //$("#carrito").html("Actualizando Carrito...");
                            $("#can_"+id).val("");
                            $("#bprodu").val("");
                            actualiza_carrito();
                            $("#modpop").modal("hide");
                            $("#bprodu").focus();
                        }else{
                            $("#can_"+id).val("");
                            $("#bprodu").val("");
                            //Cerramos popup
                            $("#modpop").modal("hide");
                            actualiza_carrito();
                            enfocarbusqueda();
                        }
                }
        });
        
    
}


function apretar_combo(idprodser){
    var canti=$("#can_"+idprodser).val();
    if (canti==0){
        canti=1;
    }    
    var idmesa=0;
    //para permitir agregar, debemos poner en un temporal y controlar accion de combos
     var parametros = {
        "id" : idprodser,
        "cantidad" : canti,
        "idmesa" : idmesa
     }
     $.ajax({
        data:  parametros,
        url:   'combo_ventas.php',
        type:  'post',
        beforeSend: function () {
        
            
            
        },
        success:  function (response) {
            $("#modal_titulo").html("Seleccionando opciones de combo");
            $("#modal_cuerpo").html(response);
            $("#dialogobox").modal("show");
            setTimeout(function(e){ enfocar("filtrarprod",1000); }, 5000);
            
        }
     });                     
}
function agrega_prod_grupo(idprod,idlista){
    //alert(idlista);
    var html = $("#prod_"+idprod+'_'+idlista).html();
    var idmesa = 0;
    //var cant = $('cant_'+idprod+'_'+idlista).val();
    //alert(cant);
    var parametros = {
        "idlista" : idlista,
        "idprod" : idprod,
        "idmesa" : idmesa
    };
    $.ajax({
        data:  parametros,
        url:   'combo_ventas_add.php',
        type:  'post',
        beforeSend: function () {
            //$("#prod_"+idprod+'_'+idlista).html("Cargando Opciones...");
        },
        success:  function (response) {
            //alert(response);
            if(response == 'MAX'){
                $("#grupo_"+idlista).html('Cantidad Maxima Alcanzada');
            }else if(response == 'LISTO'){
                $("#grupo_"+idlista).html('Listo!');
            }else{
                $("#prod_"+idprod+'_'+idlista).html(html);
                $("#contador_"+idprod+'_'+idlista).html(response);
            }
        }
    });
}
function reinicia_grupo(id,prod_princ){
        var idmesa = 0;
        var parametros = {
                "idlista" : id
                
        };
       $.ajax({
                data:  parametros,
                url:   'combo_ventas_del.php',
                type:  'post',
                beforeSend: function () {
                    //$("#lista_prod").html("Cargando Opciones...");
                },
                success:  function (response) {
                    if(response == 'OK'){
                        apretar_combo(prod_princ);
                    }else{
                        $("#lista_prod").html(response);
                    }
                }
        });
}
function terminar_combo(idprod_princ,cat){
        var html = $("#lista_prod").html();
        var idmesa = $("#idmesa").val();
        //alert(idmesa);
        //alert(idatc);
        var parametros = {
                "idprod_princ" : idprod_princ,
                "idmesa" : idmesa
        };
       $.ajax({
                data:  parametros,
                url:   'combo_ventas_termina.php',
                type:  'post',
                beforeSend: function () {
                    $("#lista_prod").html("Registrando...");
                },
                success:  function (response) {
                    if(response == 'OK'){
                        //document.location.href='?cat='+cat;
                        $("#busqueda_prod").html('');
                        $("#codbar").val('');
                        $("#cant_cb").val('1');
                        actualiza_carrito(idmesa);
                        $("#dialogobox").modal("hide");
                    }else if(response == 'NOVALIDO'){
                        $("#lista_prod").html(html);
                        alert("Favor seleccione todos los productos antes de terminar.");
                    }else{
                        $("#lista_prod").html(response);
                    }
                }
        });    
}
    
    

function updatecomentario(idtmp,idprod,cadena){
    
    var comentario=cadena;
    //alert(comentario);
    var parametros = {
        "prod"       : idprod,
        "comentario" : comentario
     };
       $.ajax({
                data:  parametros,
                url:   'cat_actualiza_comentario.php',
                type:  'post',
                beforeSend: function () {
                        
                },
                success:  function (response) {
                    $("#updcoment").html(response);
                    
                }
        });
}
                    
function init() {
    //Enter
    shortcut.add("enter", function() {
        tecla_acciones('enter');
    });
}    
          function enfocar(eventito,tiempo){
    
    if (eventito==''){
        //evento x defecto
        eventito="codaccede";
    }
    if (tiempo==''){
        tiempo=1000;
    }
    //alert("eventito"+eventito);
    setTimeout(function(e){ $("#"+eventito).focus(); }, tiempo);
}
function tecla_acciones(tecla){
    if(tecla == 'enter'){
        
        var valor='';
        var prim=$("#codaccede").is(":visible");
        var seg=$("#occantidad").val();
        var ter=$("#mpago").is(":visible");
        var cuar=$("#filtrarprod").is(":focus");
        var cin=$("#listaproduselect").is(":focus");
        //alert('Primero:'+prim);alert('Segundo:'+seg);alert('Tercero:'+ter);alert('Cuarto:'+cuar);
        //segun el enfoque haremos las opciones
        if (prim==true && seg==false && ter==false && cuar==false){
             controlar_codigo();
             ocultarerrores(valor);
        } 
        if (seg!='' && prim==false && ter==false && cuar==false){
            
            //abrir filtro de lista prod
            var ss=$("#ocserial").val();
            
            $("#enla_"+ss).click();
            
            enfocar("filtrarprod",1000);
        }
        
        if (cuar==true && seg!='' && ter==false && prim==false){
            //alert('entra');
            //Por seguridad vemos si se apreto enter, es porque termino de escribir
            if ($("#filtrarprod").is(":focus")){
                $("#listaproduselect").focus();
                var valorse=$("#listaproduselect").val();
                    
            } else {
                //Puede que el enfoque ya se encuentre en la lista
                if ($("#listaproduselect").is(":focus")){
                    var valorse=$("#listaproduselect").val();
                }
            }
        }
        if (cin==true && seg!='' && ter==false && prim==false && cuar==false){
            //esta seleccionado ya el select 
            
            var idatc=$("#ocidatcmini").val();
            var idmesa=$("#ocidmesamini").val();
            //combinado seleccionado porcion
            var produ=$("#valordelprimero").val();
            if (produ==''){
                var produ=$("#listaproduselect").val();
            }
            var prodppal=$("#ocidprodppal").val();

            //al obtener el id del producto, debemos marcar las porciones seleccionadas
            var parametros = {
                 "idmesa" : idmesa,
                 "idatc" : idatc,
                 "prodppal"    : prodppal,
                 "porcion"    : produ,
                 "agregar"    :1
                };
                $.ajax({
                            data:  parametros,
                            url:   'mini_mesas_combinado_seleccionados.php',
                            type:  'post',
                            beforeSend: function () {
                                    
                            },
                            success:  function (response) {
                                $("#seleccioncombinado").html(response);
                                //mostrar();
                            }     
                                
                 });

        }
    }

}    

          
          
          
          
          
          
window.onload=init;          
     //-----------------------------------------------------------------------------------------------------------//
    </script>      
      
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
