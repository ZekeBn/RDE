 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");

require_once("includes/funciones_canales.php");

$idcanal_script = 1; // carry
$idcanal_nuevo = 3; // delivery

// Obtener el ID de redirección de la URL, si no envio asigna 1
$idredir = isset($_GET['redir']) ? intval($_GET['redir']) : 1;

// Lista de scripts de redirección
$lista_scripts_redir = [
    1 => 'central_pedidos.php', // por defecto
    2 => 'gest_ventas_resto_caja.php'
];
// Comprobar si el ID de redirección está en la lista, si no, asignar 1 por defecto
$script_redir = isset($lista_scripts_redir[$idredir]) ? $lista_scripts_redir[$idredir] : $lista_scripts_redir[1];


$idpedido = intval($_REQUEST['id']);
if ($idpedido == 0) {
    echo "Pedido inexistente o anulado.";
    exit;
}

$consulta = "
select permite_cambiar_canal from preferencias_caja limit 1
";
$rspcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$permite_cambiar_canal = $rspcaj->fields['permite_cambiar_canal'];

if ($permite_cambiar_canal != 'S') {
    echo "Acceso Denegado!<br />La administración desactivó el permiso para realizar esta acción.";
    exit;
}


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
idtmpventares_cab = $idpedido
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
if ($idcanal_actual != $idcanal_script) {
    header("location: central_pedidos.php");
    exit;
}

http://localhost/ekaru/cambiar_canal_pedido_delivery_to_carry.php?id=2606

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






    $parametros_array = [
        "idpedido" => $idpedido,
        "idcanal_nuevo" => $idcanal_nuevo, // 1 carry out 3 delivery
        "registrado_por" => $idusu,
        "idclientedel" => $_POST['idclientedel'],
        "iddomicilio" => $_POST['iddomicilio'],
        "llevapos" => $_POST['llevapos'],
        "cambio" => $_POST['cambio'],
        "observacion_delivery" => $_POST['observacion_delivery'],
    ];

    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = validar_cambio_canal($parametros_array);
        //print_r($res);exit;
        if ($res["valido"] == "S") {

            registrar_cambio_canal($parametros_array);

            header("location: $script_redir");
            exit;
        } else {
            $errores .= $res["errores"];
            $valido = "N";
        }

    } else {
        $errores .= $res["errores"];
        $valido = "N";
    }


}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function llevapos_cambio(v_llevapos){
    if(v_llevapos == 'S'){
        $("#cambio").val('');
        $("#cambio_box").hide();
    }else{
        $("#cambio_box").show();
            
    }
}
function cliente_delivery_sel(){
    $('#modal_ventana').modal('show');
    $("#modal_titulo").html('Cliente Delivery');
    $("#modal_cuerpo").html('');
    
    var direccionurl='delivery_canal/delivery_pedidos.php';

    var parametros = {
      "carga"   : '' 
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);    
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });

    
}
function buscar_deliv(){
    var direccionurl='delivery_canal/delivery_pedidos.php';
    
    var telefono = $("#telefono").val();    
    var nombre  = $("#nombre").val();    
    var ruc = $("#ruc").val();    

    var parametros = {
      "telefono"   : telefono,
      "nombre"   : nombre,
      "ruc"   : ruc,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);    
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });

    
}
function solouncampo(campo,e){
    if(campo == 'telefono'){
        $("#ruc").val('');    
        $("#nombre").val('');
    }
    if(campo == 'ruc'){
        $("#telefono").val('');    
        $("#nombre").val('');
    }
    if(campo == 'nombre'){
        $("#ruc").val('');    
        $("#telefono").val('');
    }
    // si apreto enter
    if(e.keyCode == 13){
        buscar_deliv();
    }
}
function asigna_domicilio(iddomicilio,idzona,idclientedel){
    var cli_sel_nombre = $("#cli_sel_nombre_"+idclientedel).val();
    var domi_sel_nombre = $("#domi_sel_nombre_"+iddomicilio).val();
    $("#idclientedel").val(idclientedel+'-'+cli_sel_nombre);
    $("#iddomicilio").val(iddomicilio+'-'+domi_sel_nombre);
    $('#modal_ventana').modal('hide');
    /*if(idzona > 0){
        agrega_carrito_zona(idzona);
        
    }else{
        $('#modal_ventana').modal('hide');
        //document.location.href='gest_ventas_resto_caja.php';
    }*/
}
/*function agrega_carrito_zona(idzona){
    var direccionurl='delivery_carrito_zona.php';    
    var parametros = {
      "idzona" : idzona
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            //$("#busqueda_prod").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        //document.location.href='gest_ventas_resto_caja.php';
                        $('#modal_ventana').modal('hide');
                    }else{
                        alert(obj.errores);        
                    }
                }else{
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
    
}*/
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
                    <h2>Cambiar Canal del Pedido</h2>
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
<h3>Pedido: <?php echo $idpedido; ?></h3>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
  <tbody>

    <tr>
      <td><strong>Canal Actual:</strong></td>
      <td><?php echo $rscab->fields['canal']; ?> </td>
    </tr>
    <tr>
      <td><strong>Canal Nuevo:</strong></td>
      <td>Delivery </td>
    </tr>
    <tr>
      <td><strong>Operador:</strong></td>
      <td><?php echo $rscab->fields['operador']; ?></td>
    </tr>
  </tbody>
</table>
</div>
<hr />
<p align="center"><strong>Esta seguro que desea Cambiar a Delivery ?</strong></p>
<form id="form1" name="form1" method="post" action="">
<div class="clearfix"></div>
<br />
<div class="alert alert-warning alert-dismissible fade in" role="alert" style="color:#000;">
<strong>CUIDADO! </strong><br /> 
- Esta accion no se puede deshacer. <br />
- Se <strong>BORRARA</strong> todos los datos de ruc y razon social y se reemplazaran por el del cliente delivery seleccionado.<br />
- Debes avisar a la cocina que empaquete el pedido para delivery ya que pudieron haber preparado de otra forma.
</div>
<br /><br />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente Delivery *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idclientedel" id="idclientedel" onclick="cliente_delivery_sel();" value="<?php  if (isset($_POST['idclientedel'])) {
        echo htmlentities($_POST['idclientedel']);
    } elseif (trim($rs->fields['idclientedel']) != '') {
        echo htmlentities($rs->fields['idclientedel']);
    } ?>" placeholder="Cliente Delivery" class="form-control" required="required" readonly />
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion Envio *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="iddomicilio" id="iddomicilio" value="<?php  if (isset($_POST['iddomicilio'])) {
        echo htmlentities($_POST['iddomicilio']);
    } elseif (trim($rs->fields['iddomicilio']) != '') {
        echo htmlentities($rs->fields['iddomicilio']);
    } ?>" placeholder="Domicilio" class="form-control" required="required" readonly />
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Lleva POS *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// valor seleccionado
if (isset($_POST['llevapos'])) {
    $value_selected = htmlentities($_POST['llevapos']);
} else {
    $value_selected = '';
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'llevapos',
    'id_campo' => 'llevapos',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="llevapos_cambio(this.value);" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group"  id='cambio_box'>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cambio de *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="numeric" name="cambio" id="cambio" value="<?php  if (isset($_POST['cambio'])) {
        echo htmlentities($_POST['cambio']);
    } elseif (trim($rs->fields['cambio']) != '') {
        echo htmlentities($rs->fields['cambio']);
    } ?>" placeholder="cambio de" class="form-control"   />
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Observacion Delivery *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="observacion_delivery" id="observacion_delivery" value="<?php  if (isset($_POST['observacion_delivery'])) {
        echo htmlentities($_POST['observacion_delivery']);
    } elseif (trim($rs->fields['observacion_delivery']) != '') {
        echo htmlentities($rs->fields['observacion_delivery']);
    } ?>" placeholder="Observacion delivery" class="form-control"   />
    </div>
</div>





<div class="clearfix"></div>
<br /><br /><br />


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
