 <?php
/*-------------------------------------------------------
Formulario de relleno datos para generar
cuerpo y cabecera de pedido nuevo.
Hace Post de insert en cat_pedidos_cuerpo.php
UR: 07/05/2021 ->SE VA ANULAR ESTE SCRIPT
--------------------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");





if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {
    // print_r($_POST);
    /*//   [listapre] => [listaorg] => 1 [listadeco] => 1 [detalles] => q11111 [interno] => ggggg [mapz] => https://www.google.com/maps/@-25.1916427,-57.4966736,15z [tipoenviooc] => 99

    //Identificador del presupuesto: si es que hay
    $idpresupuesto=intval($_REQUEST['idpresupuesto']);
    $idtipoevento=intval($_REQUEST['idtipoev']);
    $fechaevento=date("Y-m-d",strtotime($_REQUEST['evento_para']));
    $horaevento=date("H:i:s",strtotime($_REQUEST['hora_entrega']));
    $cantpersona=intval($_REQUEST['cantidad_personas']);
    $fechavalidez=antisqlinyeccion($_REQUEST['valido_hasta'],'date');
    $clienterz=antisqlinyeccion($_POST['clientel'],'text');
    $idcliente=intval($_REQUEST['ocidc']);
    $direccion=antisqlinyeccion($_REQUEST['domicilio'],'text');
    $iddomicilio=intval($_REQUEST['iddomicilio']);
    $listaprecio=intval($_REQUEST['listapre']);
    $idorganizador=intval($_REQUEST['listaorg']);
    $iddecorador=intval($_REQUEST['listadeco']);
    $comentariovisible=antisqlinyeccion($_REQUEST['detalles'],'text');
    $comentariointerno=antisqlinyeccion($_REQUEST['interno'],'text');
    $tipoenvio=intval($_REQUEST['tipoenviooc']);
    $maps=antisqlinyeccion($_REQUEST['mapz'],'clave');
    // validaciones basicas
    $valido="S";
    $errores="";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if($_SESSION['form_control'] != $_POST['form_control']){
        //$errores.="- Se detecto un intento de envio doble, recargue la pagina.<br />";
        //$valido="N";
    }
    if(trim($_POST['form_control']) == ''){
        $errores.="- Control del formularios no activado.<br />";
        $valido="N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots





    if($tipoenvio == 99 && $direccion==''){
        $valido="N";
        $errores.=" - Al ser delivery debe indicar la direcci&oacote;n de env&iacute;o.<br />";
    }


    // si todo es correcto inserta
    if($valido == "S"){






        $consulta="
        insert into pedidos_eventos
        (idpresupuesto, idtipoev, id_cliente_solicita, fecha_solicitud, evento_para, hora_entrega, iddomicilio, cantidad_personas, adultos, ninhos, comentarios,comentario_interno, idlistaprecio, estado, registrado_el, registrado_por, valido_hasta)
        values
        ($idpresupuesto, $idtipoevento, $idcliente, current_timestamp, '$fechaevento', '$horaevento', $iddomicilio, $cantpersona, 0, 0,
        $comentariovisible,$comentariointerno, $listaprecio, $estado, current_timestamp, $idusu,'$fechavalidez')
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

        header("location: cat_pedidos_cuerpo.php?");
        exit; */

    //}
    //SE HACE EN EL CUERPO
}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




//Recibimos datos

$idc = intval($_REQUEST['ocidc']);
$idclientepedido = intval($_REQUEST['ocidpedi']);
$metodoentrega = intval($_REQUEST['suculista']);

if ($idc > 0) {
    //para la facturacion

    $buscar = "Select razon_social,email,celular,ruc,documento from cliente where idcliente=$idc";
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $raz = trim($rscli->fields['razon_social']);
}
//para el pedido

if ($idclientepedido > 0) {
    //para la facturacion

    $buscar = "Select * from cliente_pedido where idclienteped=$idclientepedido";
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $raz1 = trim($rscli->fields['nomape']);
}

if ($metodoentrega == 99) {
    $aste = "*";
    $req = " required='required' ";

}


//Lista organizadores
$buscar = "Select nombre,idorganizador from organizador_eventos where estado <> 6 order by nombre asc";
$rslo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Lista decoradores
$buscar = "Select nombre,decorador_eventos.iddecorador from decorador_eventos where estado <> 6 order by nombre asc";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
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
                    <h2><span class="fa fa-dashboard"></span></span>&nbsp; Datos Pedido</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                            
                             <?php if (trim($errores) != "") { ?>
                                    <div class="alert alert-danger alert-dismissible fade in" role="alert">
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
                                    </button>
                                    <strong>Errores:</strong><br /><?php echo $errores; ?>
                                    </div>
                                <?php } ?>
                                <form id="form1" name="form1" method="post" action="cat_pedidos_cuerpo.php">

                                    <div class="col-md-3 col-sm-3 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Presupuesto Num </label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                            <input type="text" name="idpresupuesto" id="idpresupuesto" value="<?php  if (isset($_POST['idpresupuesto'])) {
                                                echo intval($_POST['idpresupuesto']);
                                            } else {
                                                echo intval($rs->fields['idpresupuesto']);
                                            }?>" placeholder="Idpresupuesto" class="form-control"  />                    
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-3 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Evento</label>
                                        <div class="col-md-9 col-sm-9 col-xs-12" id="tiposeventosdiv">
                                            <?php require_once("cat_mini_eventos_tipos.php"); ?>

                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-3 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Evento *</label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="date" name="evento_para" id="evento_para" value="<?php  if (isset($_POST['evento_para'])) {
                                            echo htmlentities($_POST['evento_para']);
                                        } else {
                                            if ($_REQUEST['fechaevento']) {
                                                echo htmlentities($_REQUEST['fechaevento']);
                                            } else {
                                                echo htmlentities($rs->fields['evento_para']);
                                            }
                                        }?>"
                                               placeholder="Evento para" class="form-control" required="required" />                    
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-sm-3 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Hora entrega *</label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="text" name="hora_entrega" id="hora_entrega" value="<?php  if (isset($_POST['hora_entrega'])) {
                                            echo htmlentities($_POST['hora_entrega']);
                                        } else {

                                            if ($_REQUEST['fechaevento']) {
                                                echo htmlentities($_REQUEST['hora']);
                                            } else {
                                                echo htmlentities($rs->fields['hora_entrega']);
                                            }
                                        }?>" placeholder="Hora entrega" class="form-control" required="required" />                    
                                        </div>
                                    </div>    
                                    <div class="clearfix"></div>
                                    <div class="col-md-3 col-sm-3 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad personas </label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input type="text" name="cantidad_personas" id="cantidad_personas" value="<?php  if (isset($_POST['cantidad_personas'])) {
                                            echo intval($_POST['cantidad_personas']);
                                        } else {
                                            echo intval($rs->fields['cantidad_personas']);
                                        }?>" placeholder="Cantidad personas" class="form-control" />                    
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-3 form-group">
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Forma Entrega  </label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                            <select name="suculista" id="suculista" style="height: 40px; width: 90%;" required="required" onClick="bsele();">
                                                <option value="" selected="selected">Seleccionar</option>
                                                <option value="99" >Delivery</option>
                                                <?php
                                                    $buscar = "Select * from sucursales where estado <> 6 
                                                    and idsucu in(select idsucursal from sucursal_parametros where idsucursal=sucursales.idsucu)
                                                    order by nombre asc";
$rsuc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tsuc = $rsuc->RecordCount();
if ($tsuc > 0) {
    while (!$rsuc->EOF) {
        ?>
                                                        <option value="<?php echo $rsuc->fields['idsucu'] ?>"><?php echo $rsuc->fields['nombre'] ?></option>
                                                        <?php
        $rsuc->MoveNext();
    }
}

?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-3 form-group">
                                        
                                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Dir. Entrega <?php echo $aste;?></label>
                                        <div class="col-md-9 col-sm-9 col-xs-12">
                                            <input type="text" name="domicilio" id="domicilio" value="<?php  if (isset($_POST['domicilio'])) {
                                                echo trim($_POST['domicilio']);
                                            }?>" placeholder="Direccion de entrega / Domicilio"  style="height: 40px;width:80%;" <?php echo $req?> required="required" />    <?php if ($gg == 1) { ?> <button type="button"  class="btn btn-dark go-class" onClick="registradireccion();"><span class="fa fa-plus"></span> </button>  <?php } ?>
                                            <input type="hidden" name="iddomicilio" id="iddomicilio" value="" />
                                        </div>
                                    </div>        
                                 
                                    

                                        <div class="col-md-3 col-sm-3 form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Ubicacion / Gmaps  </label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <input type="text" name="ubicacion" id="ubicacion" class="form-control" style="width: 100%;">
                                            </div>
                                        </div>    
                                    
                                <div class="clearfix"></div>
                                
                                    
                                <h2><span class="fa fa-money"></span></span>&nbsp;Datos de Cliente y Facturacion</h2>    
                                    <hr />
                                <div class="col-md-4 col-sm-4 form-group">
                                    
                                    <div class="col-md-9 col-sm-9 col-xs-12" id="seleccioncliente">
                                        <label>Cliente  *</label>
                                        <input type="text" name="clientel" id="clientel" readonly  style="width: 85%; height: 40px;" value="<?php echo $raz1?>" />
                                        <?php if ($ggg == 1) { ?><button type="button"  class="btn btn-primary go-class" ><span class="fa fa-search"></span> </button> <?php } ?>  
                                        <input type="hidden" name="ocidcsoli" id="ocidcsoli" value="<?php echo $idclientepedido ?>" />
                                    </div>
                                </div>
                                <div class="col-md-4 col-sm-4 form-group">
                                    
                                    <div class="col-md-9 col-sm-9 col-xs-12" id="seleccioncliente">
                                        <label>Facturar *</label>
                                        <input type="text" name="clientel" id="clientel" readonly  style="width: 85%; height: 40px;" value="<?php echo $raz?>" />
                                        <?php if ($ggg == 1) { ?><button type="button"  class="btn btn-primary go-class" ><span class="fa fa-search"></span> </button>  <?php } ?>
                                        <input type="hidden" name="ocidc" id="ocidc" value="<?php echo $idc ?>" />
                                    </div>
                                </div>
                                
                                <div class="col-md-4 col-sm-4 form-group">
                                    
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <label >Lista Precio</label>
                                    <?php
                                        $consulta = "
                                select *
                                from lista_precios_venta 
                                where 
                                 estado = 1 
                                order by idlistaprecio asc
                                ";
$rsl = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tl = $rsl->RecordCount();
if ($tl == 1) {
    $selected = "selected='selected' ";
}
if ($tl > 0) {

    ?>   
                                        <select name="listapre" id="listapre" style="height:40px; width: 80%;">
                                            <option value="" selected="selected">Seleccionar</option>
                                            <?php while (!$rsl->EOF) {?>
                                            <option value="<?php echo $rsl->fields['idlistaprecio']?>" <?php if ($_POST['listapre'] == $rsl->fields['idlistaprecio']) { ?> selected="selected"<?php } ?><?php echo $selected ?>><?php echo $rsl->fields['lista_precio']?></option>

                                            <?php $rsl->MoveNext();
                                            }?>
                                </select>
                                        <?php }?>
                                    </div>
                                </div>    
                                <div class="clearfix"></div>
                                <div class="row"></div>    
                                    
                                    <h2><span class="fa fa-edit"></span></span>&nbsp;Datos de Organizaci&oacute;n</h2>        
                                <hr />            

                                    
                                    <div class="col-md-6 col-sm-6 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Organizador </label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                            <select name="listaorg" id="listaorg" style="height:40px; width: 80%;">
                                            <option value="" selected="selected">Seleccionar</option>
                                            <?php while (!$rslo->EOF) {?>
                                            <option value="<?php echo $rslo->fields['idorganizador']?>" <?php if ($_POST['listaorg'] == $rslo->fields['idorganizador']) { ?> selected="selected"<?php } ?>><?php echo $rslo->fields['nombre']?></option>

                                            <?php $rslo->MoveNext();
                                            }?>
                                                </select>                 
                                    </div>
                                </div>
                                    <div class="col-md-6 col-sm-6 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Decorador</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                             <select name="listadeco" id="listadeco" style="height:40px; width: 80%;">
                                            <option value="" selected="selected">Seleccionar</option>
                                            <?php while (!$rsd->EOF) {?>
                                            <option value="<?php echo $rsd->fields['iddecorador']?>" <?php if ($_POST['listadeco'] == $rsd->fields['iddecorador']) { ?> selected="selected"<?php } ?>><?php echo $rsd->fields['nombre']?></option>

                                            <?php $rsd->MoveNext();
                                            }?>
                                            </select>        
                                    </div>
                                </div>
                                    <div class="col-md-6 col-sm-6 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Detalles  / Colores</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                    <textarea name="detalles" id="detalles" rows="4" style="width: 90%;">
                                    </textarea>                
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario Interno</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                    <textarea name="interno" id="interno" rows="4" style="width: 90%;">
                                    </textarea>                    
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                                <br />

                                    <div class="form-group">
                                        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
                                       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Continuar</button>
                                      
                                        </div>
                                    </div>
                                      <input type="hidden" name="mapz" id="mapz" value="<?php echo trim($_REQUEST['ubicacion']);?>" />
                                    <input type="hidden" name="tipoenviooc" id="tipoenviooc" value="<?php echo $metodoentrega?>" />
                                  <input type="hidden" name="MM_insert" value="form1" />
                                  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
                                <br />
                                </form>
<div class="clearfix"></div>
<br /><br />





                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            <?php if ($idcabe > 0) {?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Titulo 1</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                            






                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            <?php } ?>
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
      <script>
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
      
      
      </script>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
