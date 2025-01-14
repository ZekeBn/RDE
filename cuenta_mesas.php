 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "196";
require_once("includes/rsusuario.php");

// actualiza mozo abrio
$consulta = "
update mesas set
idmozo_abrio = (select idmozo from mesas_atc where idmesa = mesas.idmesa order by idatc desc limit 1)
where
idmozo_abrio is null
";
//$conexion->Execute($consulta) or die (errorpg($conexion,$consulta));

$img = "images/media_rest.jpg";
/*
$consulta="
select * , (select usuario from usuarios where idusu = mesas.idmozo_abrio) as mozo_abrio,
(select mesas_atc.pin from mesas_atc where mesas_atc.estado = 1 and mesas_atc.idmesa = mesas.idmesa order by mesas_atc.idatc desc limit 1) as pin
from mesas
inner join salon on salon.idsalon = mesas.idsalon
where
mesas.estadoex = 1
and salon.estado_salon = 1
and idmesa in (
            select idmesa
            from tmp_ventares_cab
            where
            tmp_ventares_cab.idsucursal = $idsucursal
            and tmp_ventares_cab.finalizado = 'S'
            and tmp_ventares_cab.registrado = 'N'
            and idmesa > 0
            and tmp_ventares_cab.estado = 1
            )
order by numero_mesa asc
";
$rs=$conexion->Execute($consulta) or die (errorpg($conexion,$consulta));
*/
$consulta = "
select * , (select usuario from usuarios where idusu = mesas.idmozo_abrio) as mozo_abrio,
mesas_atc.pin  as pin
from mesas 
inner join salon on salon.idsalon = mesas.idsalon
inner join mesas_atc on mesas_atc.idmesa = mesas.idmesa
where 
mesas.estadoex = 1
and salon.estado_salon = 1
and mesas_atc.idsucursal = $idsucursal
and mesas_atc.estado = 1
order by numero_mesa asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



// buscar impresora remota para app
$consulta = "
SELECT * 
FROM impresoratk 
where 
borrado = 'N' 
and tipo_impresora='REM' 
and idsucursal = $idsucursal
order by idimpresoratk  asc 
limit 1
";
$rsimprem = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// si no existe impresora remota para la sucursal actual
if (intval($rsimprem->fields['idimpresoratk']) == 0) {
    // trae de cualquier sucursal
    $consulta = "
    SELECT * 
    FROM impresoratk 
    where 
    borrado = 'N' 
    and tipo_impresora='REM' 
    order by idimpresoratk  asc 
    limit 1
    ";
    $rsimprem = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}

// si existe reemplaza, caso contrario usa la de caja que trae arriba
if (trim($rsimprem->fields['script']) != '') {
    $script_impresora_app = trim($rsimprem->fields['script']);
    $rsimp = $rsimprem;
}
$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora_rem = trim($rsimp->fields['script']);
$metodo_app = $rsimprem->fields['metodo_app'];
$version_app = $rsimp->fields['version_app'];
$version_app_orig = $rsimp->fields['version_app'];
$tipo_saltolinea_app = $rsimp->fields['tipo_saltolinea_app'];
//echo $version_app;exit;
if (trim($script_impresora_rem) == '') {
    $script_impresora_rem = $defaultprnt;
}
if (intval($version_app) == 0) {
    $version_app = 1;
}

/*
$texto="hola
mundo";*/

// auto impresor para app
// ticket para app
if ($tipo_saltolinea_app != '') {
    $factura_auto_app = str_replace($saltolinea, $tipo_saltolinea_app, $texto); // \\r
} else {
    $factura_auto_app = $texto;
}
$factura_auto_app = str_replace("'", "", $factura_auto_app);
$factura_auto_app = str_replace('"', '', $factura_auto_app);
/*$factura_auto_app=str_replace('[','(',$factura_auto_app);
$factura_auto_app=str_replace(']',')',$factura_auto_app);*/
$texto_app = $factura_auto_app;
//$url1="reimprimir_facturas_retro.php";

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



?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php
    $title_personaliza = "Mesas Activas";
require_once("includes/head_gen.php"); ?>
<script>

function detallar(idmesa){
    var direccionurl='detalle_mesa.php';        
    var parametros = {
      "idmesa" : idmesa          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $('#estadocuentadet').modal('show');
            $(".modal-content").html('Cargando...');                
        },
        success:  function (response) {
            $(".modal-content").html(response);
        }
    });
    
}
function detallar_det(idmesa){
    var direccionurl='detalle_mesa_tanda.php';        
    var parametros = {
      "idmesa" : idmesa          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $('#estadocuentadet').modal('show');
            $(".modal-content").html('Cargando...');                
        },
        success:  function (response) {
            $(".modal-content").html(response);
        }
    });
    
}
function reimprimir_mesa(id){
    $("#preticket_imp").hide();
    //$("#reimprimebox").html('<iframe src="../impresor_ticket_mesa.php?idmesa='+id+'" style="width:310px; height:500px;"></iframe>');
    // si es la app
    <?php echo trim($js_app['if_es_app_inicio']).$saltolinea; ?>
        document.location.href='impresor_ticket_mesa.php?idmesa='+id+'&modredir=1';
    <?php echo trim($js_app['if_es_app_fin']).$saltolinea; ?>
    // si no es la app
    <?php echo trim($js_app['if_no_app_inicio']).$saltolinea; ?>
        $("#reimprimebox").html('<iframe src="../impresor_ticket_mesa.php?idmesa='+id+'" style="width:310px; height:500px;"></iframe>');
    <?php echo trim($js_app['if_no_app_fin']).$saltolinea; ?>
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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
            
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Mesas Activas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  
                  
   <p><a href="tablet/" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Tomar Pedido</a></p>
<hr />               
          
  
                <div class="row">

                     

<?php while (!$rs->EOF) {
    $idmesa = intval($rs->fields['idmesa']);
    $pin = trim($rs->fields['pin']);
    $idatc = intval($rs->fields['idatc']);
    if ($rs->fields['nombre_mesa'] != '') {
        $nombre_mesa = "&nbsp;->Mesa de ".trim($rs->fields['nombre_mesa']);
    } else {
        $nombre_mesa = "";
    }

    //Buscamos el nombre por el idmesa
    /*$buscar="Select idatc from mesas_atc where idmesa=$idmesa order by idatc desc limit 1";
    $rsatc=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $idatc=intval($rsatc->fields['idatc']);
    $buscar="select * from mesas_atc where idatc=$idatc ";
    $rfnm=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    if ($rfnm->fields['nombre_mesa']!=''){
        $nombre_mesa="&nbsp;->Mesa de ".trim($rfnm->fields['nombre_mesa']);
    } else {
        $nombre_mesa="";
    }*/



    ?>
                      <!--  MESA -->
                      <div class="col-md-55" href="javascript:void(0);" onMouseUp="detallar(<?php echo $rs->fields['idmesa'] ?>);" style="cursor:pointer;">
                        <div class="thumbnail">
                          <div class="image view view-first">
                             <img style="width: 100%; display: block;" src="<?php echo $img; ?>" alt="image"> 
                            <div class="mask no-caption">
                              <div class="tools tools-bottom">
                                <!--<a href="#"><i class="fa fa-link"></i></a>
                                <a href="#"><i class="fa fa-pencil"></i></a>
                                <a href="#"><i class="fa fa-times"></i></a>-->
                              </div>
                            </div>
                          </div>
                          <div class="caption">
                            <p><strong>Mesa <?php echo $rs->fields['numero_mesa']; ?><?php if ($nombre_mesa != '') {
                                echo $nombre_mesa;
                            }?></strong><br />
                            </p>
                            <p><?php echo $rs->fields['nombre']; ?><?php if (trim($rs->fields['mozo_abrio']) != '') { ?> | <?php echo $rs->fields['mozo_abrio'];
                            } ?><?php if ($pin != '') { ?> | PIN: <?php echo formatomoneda($pin);
                            } ?></p>
                          </div>
                        </div>
                      </div>
                      <!--  MESA -->
<?php $rs->MoveNext();
}?>     

<div id="reimprimebox" style="display:none;"></div>
                      
                      

                      
                    </div>
                            
                      
                      
                      
                      
                      
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->
        
        
        <!-- POPUP DE MODAL OCULTO -->
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="estadocuentadet">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Detalle de la Cuenta</h4>
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
