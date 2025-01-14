 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "313";
require_once("includes/rsusuario.php");

// si el usuario no es de una master franquicia
$consulta = "
select * from usuarios where idusu = $idusu  
";
$rsusfranq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$franq_m = $rsusfranq->fields['franq_m'];
// si el usuario actual no es master franq ni super filtra
if ($franq_m != 'S' && $superus != 'S') {
    $whereadd = "
    and usuarios.franq_m = 'N'
    ";
}

// si no es un super usuario debe filtrar usuarios por este campo
if ($superus != 'S') {
    $whereadd .= "
    and usuarios.super = 'N'
    ";
}

$idusuario = intval($_GET['id']);
if ($idusuario == 0) {
    header("location: traslados_permisos.php");
    exit;
}

$consulta = "
select usuarios.idusu as idusuario, usuarios.usuario as usuario,
(select count(*) as total from traslados_permisos where traslados_permisos.idusuario = usuarios.idusu and traslados_permisos.estado = 1) as total
from usuarios 
where 
 usuarios.estado = 1 
 $whereadd
 and idusu = $idusuario
 group by usuarios.usuario
order by usuarios.usuario asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$idusuario = intval($rs->fields['idusuario']);
if ($idusuario == 0) {
    header("location: traslados_permisos.php");
    exit;
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
function registra_permiso(direccion,iddeposito){
    var direccionurl='traslados_permisos_registra.php';    
    //alert(direccion);
    var parametros = {
      "direccion"   : direccion,
      "iddeposito"  : iddeposito,
      "idusuario"  : <?php echo $idusuario; ?>,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            if(direccion == 'E'){
                $("#entrante_td_"+iddeposito).html('Cargando...');    
            }
            if(direccion == 'S'){
                $("#saliente_td_"+iddeposito).html('Cargando...');    
            }
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    if(direccion == 'E'){
                        $("#entrante_td_"+iddeposito).html(obj.html_checkbox);
                        var permitido = obj.permitido;
                        var elem = $("#entrante_"+iddeposito);
                        //alert(elem);
                        var idbox = "entrante";
                        switchery_reactivar_uno(iddeposito,idbox);
                        //var switchery = new Switchery(elem);
                        /*if(permitido == 'S'){
                            switchery.disable();
                        }else{
                            switchery.enable();    
                        }*/
                    }
                    if(direccion == 'S'){
                        $("#saliente_td_"+iddeposito).html(obj.html_checkbox);
                        var permitido = obj.permitido;
                        var elem = $("#saliente_"+iddeposito);
                        var idbox = "saliente";
                        switchery_reactivar_uno(iddeposito,idbox);
                        //var switchery = new Switchery(elem);
                        /*if(permitido == 'S'){
                            switchery.disable();
                        }else{
                            switchery.enable();    
                        }*/
                    }
                }else{
                    alert(obj.errores);
                }
            }else{
                alert(response);    
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function registra_permiso_edit(){
    var direccionurl='traslados_permisos_registra_usu.php';    
    //alert(direccion);
    var parametros = {
      "idusuario"  : <?php echo $idusuario; ?>,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#edit_box").html('Cargando...');    

        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    $("#edit_box").html(obj.html_checkbox);
                    var permitido = obj.permitido;
                    var elem = $("#edit_box");
                    //alert(elem);
                    var idbox = "editar";
                    switchery_reactivar_dos(idbox);
                }else{
                    alert(obj.errores);
                }
            }else{
                alert(response);    
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function registra_permiso_edit_recep(){
    var direccionurl='traslados_permisos_registra_usu_recep.php';    
    //alert(direccion);
    var parametros = {
      "idusuario"  : <?php echo $idusuario; ?>,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#edit_recep_box").html('Cargando...');    

        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    $("#edit_recep_box").html(obj.html_checkbox);
                    var permitido = obj.permitido;
                    var elem = $("#edit_recep_box");
                    //alert(elem);
                    var idbox = "editar_traslado_recep";
                    switchery_reactivar_dos(idbox);
                }else{
                    alert(obj.errores);
                }
            }else{
                alert(response);    
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function registra_permiso_ver(){
    var direccionurl='traslados_permisos_registra_usu_vs.php';    
    //alert(direccion);
    var parametros = {
      "idusuario"  : <?php echo $idusuario; ?>,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#verstock_box").html('Cargando...');    

        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    $("#verstock_box").html(obj.html_checkbox);
                    var permitido = obj.permitido;
                    var elem = $("#verstock_box");
                    //alert(elem);
                    var idbox = "verstock";
                    switchery_reactivar_dos(idbox);
                }else{
                    alert(obj.errores);
                }
            }else{
                alert(response);    
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function registra_permiso_ver_recep(){
    var direccionurl='traslados_permisos_registra_ver_recep.php';    
    //alert(direccion);
    var parametros = {
      "idusuario"  : <?php echo $idusuario; ?>,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#ver_pendrecep_box").html('Cargando...');    

        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    $("#ver_pendrecep_box").html(obj.html_checkbox);
                    var permitido = obj.permitido;
                    var elem = $("#ver_pendrecep_box");
                    //alert(elem);
                    var idbox = "ver_pendrecep";
                    switchery_reactivar_dos(idbox);
                }else{
                    alert(obj.errores);
                }
            }else{
                alert(response);    
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
    
}
function switchery_reactivar(){
    var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
    elems.forEach(function(html) {
        var switchery = new Switchery(html);
    });
}
function switchery_reactivar_uno(iddeposito,idbox){
        var elems = document.querySelector('#'+idbox+'_'+iddeposito);
        var switchery = new Switchery(elems);

}
function switchery_reactivar_dos(idbox){
        var elems = document.querySelector('#'+idbox);
        var switchery = new Switchery(elems);

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
                    <h2>Permisos para traslados de stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="traslados_permisos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="traslados_permisos_usu_det.php?id=<?php echo intval($_GET['id']); ?>" class="btn btn-sm btn-primary"><span class="fa fa-search"></span> Traslado</a>      
<a href="traslados_permisos_usu_det_rec.php?id=<?php echo intval($_GET['id']); ?>" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Recepcion</a>
</p>
<hr />

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>

            <th align="center">Idusuario</th>
            <th align="center">Usuario</th>
            <th align="center">Total Depositos</th>
        </tr>
      </thead>
      <tbody>
        <tr>

            <td align="center"><?php echo intval($rs->fields['idusuario']); ?></td>
            <td align="center"><?php echo antixss($rs->fields['usuario']); ?></td>
            <td align="center"><?php echo intval($rs->fields['total']); ?></td>
        </tr>
      </tbody>
    </table>
</div>
<br />
<?php

$idusuario = $rs->fields['idusuario'];
$consulta = "
select editar_traslado, verstock_traslado, editar_traslado_recep, muestra_traslado_pendientesrecep
from usuarios 
where 
idusu = $idusuario
";
$rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>

                      
<strong>Permite Editar Traslado:</strong>
<span id="edit_box">
<input name="editar" id="editar" type="checkbox" value="S" class="js-switch" onChange="registra_permiso_edit();" <?php if ($rsus->fields['editar_traslado'] == 'S') {
    echo "checked";
} ?>  >
</span>
<div class="clearfix"></div>
<br />
                      
<strong>Permite Editar Cantidad Recibida:</strong>
<span id="edit_recep_box">
<input name="editar_traslado_recep" id="editar_traslado_recep" type="checkbox" value="S" class="js-switch" onChange="registra_permiso_edit_recep();" <?php if ($rsus->fields['editar_traslado_recep'] == 'S') {
    echo "checked";
} ?>  >
</span>
<div class="clearfix"></div>
<br />    
                      
<strong>Permite Ver Stock:</strong>
<span id="verstock_box">
<input name="verstock" id="verstock" type="checkbox" value="S" class="js-switch" onChange="registra_permiso_ver();" <?php if ($rsus->fields['verstock_traslado'] == 'S') {
    echo "checked";
} ?>  >
</span>
<div class="clearfix"></div>
<br />
                                
<strong>Permite Ver Traslados Pendientes al enviar:</strong>
<span id="ver_pendrecep_box">
<input name="verstock_pendrecep" id="verstock_pendrecep" type="checkbox" value="S" class="js-switch" onChange="registra_permiso_ver_recep();" <?php if ($rsus->fields['muestra_traslado_pendientesrecep'] == 'S') {
    echo "checked";
} ?>  >
</span>
<div class="clearfix"></div>
<br />
<?php

$consulta = "
select *, 
(select entrante from traslados_permisos where iddeposito = gest_depositos.iddeposito and idusuario = $idusuario) as entrante,
(select saliente from traslados_permisos where iddeposito = gest_depositos.iddeposito and idusuario = $idusuario) as saliente 
from gest_depositos
where
estado = 1
and tiposala <> 3
order by descripcion asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<strong>Permisos para envios:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
      <thead>
        <tr>
            <th>Entrante (Destino)</th>
            <th>Saliente (Origen)</th>
            <th align="center">Deposito</th>
        </tr>
      </thead>
      <tbody>
<?php while (!$rs->EOF) {

    $iddeposito = intval($rs->fields['iddeposito']);
    $entrante = $rs->fields['entrante'];
    $saliente = $rs->fields['saliente'];

    ?>
        <tr>
            <td id="entrante_td_<?php echo $iddeposito; ?>">
            <input name="entrante" id="entrante_<?php echo $iddeposito; ?>" type="checkbox" value="S" class="js-switch" onChange="registra_permiso('E',<?php echo $iddeposito; ?>);" <?php if ($entrante == 'S') {
                echo "checked";
            } ?>   >
            </td>
            <td id="saliente_td_<?php echo $iddeposito; ?>">
            <input name="saliente" id="saliente" type="checkbox" value="S" class="js-switch" onChange="registra_permiso('S',<?php echo $iddeposito; ?>);" <?php if ($saliente == 'S') {
                echo "checked";
            } ?>>
            </td>

            <td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
        </tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    </table>
</div>
<br />


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
<link href="vendors/switchery/dist/switchery.min.css" rel="stylesheet">
<script src="vendors/switchery/dist/switchery.min.js" type="text/javascript"></script>
  </body>
</html>
