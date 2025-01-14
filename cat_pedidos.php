 <?php
/*----------------------------------------------------------
24/06/2021
-----------------------------------------------------------
*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");

//print_r($_POST);exit;
//Seccion de finalizacion de un pedido en curso
if (isset($_POST['ocregev'])) {
    $idreg = intval($_POST['ocregev']);
    if ($idreg > 0) {
        //almacenamos los items y cerramos el pedido activo
        $update = "update pedidos_eventos set estado=3,confirmado_pedido_el=current_timestamp where regid=$idreg";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //Marcamos los componentes en tmpventares del usuario
        $update = "update tmp_ventares set idpedidocat=$idreg,finalizado='S',impreso_coc='S' where usuario=$idusu and finalizado='N'";
        $conexion->Execute($update) or die(errorpg($conexion, $update));






        header("location: cat_pedidos.php");
    }
}


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


//$errores='';


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
            
            <form id="form1" name="form1" method="post" action="cat_pedidos_cuerpo.php">
            
            <div class="row" >
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agregar / Registrar Pedidos </h2>
                    <ul class="nav navbar-right panel_toolbox collapsed">
                      <li><a class="collapse-link " id="cabeceraid" ><i class="fa fa-chevron-up"></i></a>
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
                    
                          
                        
                          
                          <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha Evento  </label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                    <input class="form-control" type="date" name="fechaevento" id="fechaevento" required="required"   />                    
                                    </div>
                        </div>
                            <div class="col-md-3 col-sm-3 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Hora Evento  </label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                        <input class="form-control" type="time" name="hora" id="hora" required="required"  />
                                        
                                        
                                    </div>
                            </div>
                          <div class="col-md-3 col-sm-3 form-group">
                                      <label class="control-label col-md-3 col-sm-3 col-xs-12">Cliente</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                            <input type="text" name="cliente" id="cliente" class="form-control" style="width: 95%;" required="required" readonly="readonly" >
                                                <span class="input-group-btn">
                                                <button type="button" class="btn btn-primary go-class" onClick="seleccionarcliente();"><span class="fa fa-search"></span></button>
                                                </span> 
                                                <input type="hidden" name="ocidc" id="ocidc" value="" />
                                                <input type="hidden" name="ocidpedi" id="ocidpedi" value="" />
                                        
                                        
                                        
                                    </div>
                            </div>
                          
                         
                        





                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            <!-------------------------------------------------------DESDE ACA NUEVA FORMA DE CARGA---------------------------------------->
            <div class="row" id="cuerpopedido" style="display:none;">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   <h2><span class="fa fa-money"></span></span>&nbsp;Datos de Cliente y Facturaci&oacute;n</h2>    
                                
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
                                    <!------------------------------------------------------------------------------------------------------>
                                
                                    <div class="col-md-4 col-sm-4 form-group">
                                    
                                        <div class="col-md-9 col-sm-9 col-xs-12" id="seleccioncliente">
                                            <label>Cliente Pedido *</label>
                                            <input type="text" name="clientel" id="clientel"   style="width: 85%; height: 40px;" value="<?php echo $raz1?>" />
                                            <?php if ($ggg == 1) { ?><button type="button"  class="btn btn-primary go-class" ><span class="fa fa-search"></span> </button> <?php } ?>  
                                            <input type="hidden" name="ocidcsoli" id="ocidcsoli" value="<?php echo $idclientepedido ?>" />
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-4 form-group">
                                        
                                        <div class="col-md-9 col-sm-9 col-xs-12" id="seleccioncliente">
                                            <label>Facturar *</label>
                                            <input type="text" name="clientelfac" id="clientelfac" readonly  style="width: 85%; height: 40px;" value="<?php echo $raz?>" />
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
                                    
                                    
                                    
                                    

                  </div>
                </div>
              </div>
            </div>
            <?php
                //Lista organizadores
$buscar = "Select nombre,idorganizador from organizador_eventos where estado <> 6 order by nombre asc";
$rslo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Lista decoradores
$buscar = "Select nombre,decorador_eventos.iddecorador from decorador_eventos where estado <> 6 order by nombre asc";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
            <!------------------------------------------------------------>
            <div class="row" id="cuerpoeventos" style="display:none;">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   <h2><span class="fa fa-dashboard"></span></span>&nbsp; Datos del Evento</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link" onclick="desocultar();"><i class="fa fa-chevron-down"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" id="" style="">
                    
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
                        <input type="time" name="hora_entrega" id="hora_entrega" value="<?php  if (isset($_POST['hora_entrega'])) {
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
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Evento</label>
                        <div class="col-md-9 col-sm-9 col-xs-12" id="tiposeventosdiv">
                            <?php require_once("cat_mini_eventos_tipos.php"); ?>

                        </div>
                    </div>
                    <div class="clearfix"></div>
                    

                    <div class="col-md-3 col-sm-3 form-group">
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Lugar Entrega  </label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <select name="suculista" id="suculista" style="height: 40px; width: 90%;" required="required" onClick="seledire(this.value);">
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
    $rsuc->MoveFirst();
}

?>
                            </select>
                            
                            <?php while (!$rsuc->EOF) { ?>
                            <input type="hidden" name="direh_<?php echo $rsuc->fields['idsucu']; ?>" id="direh_<?php echo $rsuc->fields['idsucu']; ?>" value="<?php echo $rsuc->fields['direccion'];?>" />
                            <?php $rsuc->MoveNext();
                            } ?>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 form-group">
                        
                        <label class="control-label col-md-3 col-sm-3 col-xs-12">Dir. Entrega <?php echo $aste;?></label>
                        <div class="col-md-9 col-sm-9 col-xs-12">
                            <input type="text" name="domicilio" id="domicilio" value="<?php  if (isset($_POST['domicilio'])) {
                                echo trim($_POST['domicilio']);
                            }?>" placeholder="Direccion de entrega / Domicilio"  style="height: 40px;width:100%;"  />    <?php if ($gg == 1) { ?> <button type="button"  class="btn btn-dark go-class" onClick="registradireccion();"><span class="fa fa-plus"></span> </button>  <?php } ?>
                            <input type="hidden" name="iddomicilio" id="iddomicilio" value="" />
                        </div>
                    </div>        
                    <div class="col-md-3 col-sm-3 form-group">
                                            <label class="control-label col-md-3 col-sm-3 col-xs-12">Ubicacion / Gmaps  </label>
                                            <div class="col-md-9 col-sm-9 col-xs-12">
                                                    <input type="text" name="ubicacion" id="ubicacion" class="form-control" style="width: 100%;">
                                            </div>
                                        </div>    




                  </div>
                </div>
              </div>
            </div>
            
            
            
            <div class="row" id="cuerpocolores" style="display:none;">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                   <h2><span class="fa fa-edit"></span></span>&nbsp; Comentarios Pedido</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link" onclick="desocultar();"><i class="fa fa-chevron-down"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content" id="" style="display:none">
                    
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
                                    <textarea name="detalles" id="detalles" rows="4" style="width: 90%;"></textarea>                
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 form-group">
                                    <label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario Interno</label>
                                    <div class="col-md-9 col-sm-9 col-xs-12">
                                    <textarea name="interno" id="interno" rows="4" style="width: 90%;"></textarea>                    
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                    





                  </div>
                </div>
              </div>
            </div>
        
            <div class="row" id="enviocuerpo" style="display:none"; >
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2></h2>
                    <ul class="nav navbar-right panel_toolbox collapsed">
                      <li><a class="collapse-link "  ><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                    <div class="form-group">
                        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
                            <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Continuar</button>
                        </div>
                    </div>
                    
                    <input type="hidden" name="ocidclientefactural" id="ocidclientefactural" value="" />
                    <input type="hidden" name="ocidclientepedidol" id="ocidclientepedidol" value="" />
                                      <input type="hidden" name="mapz" id="mapz" value="<?php echo trim($_REQUEST['ubicacion']);?>" />
                                    <input type="hidden" name="tipoenviooc" id="tipoenviooc" value="<?php echo $metodoentrega?>" />
                    <input type="hidden" name="MM_insert" value="form1" />
                    <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
                    <br />

                  </div>
                </div>
              </div>
            </div>
            
                
        </form>
            
            
            <!-------------------------------------------------------------------------------------------------------------------------------->
            <?php
             //cargamos los valores basicos del pedido
            $buscar = "Select regid,idpresupuesto,evento_para,hora_entrega,dire_entrega,
            (select telefono from cliente_pedido where idclienteped=pedidos_eventos.id_cliente_solicita) as telefono,
            (select nomape 
            from cliente_pedido where idclienteped=pedidos_eventos.id_cliente_solicita) 
            as solicitado_por,registrado_el,confirmado_pedido_el as confirmadoel,
            (select usuario 
            from usuarios where idusu=pedidos_eventos.registrado_por) as quien
            from pedidos_eventos where estado=3 order by regid desc ";
$rga = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tpedidos = $rga->RecordCount();


?>
            <!-- SECCION -->
            <hr />
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Ultimos Pedidos confirmados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                      <?php if ($tpedidos > 0) {?>
                                  <div class="table-responsive">
                                    <table class="table table-striped jambo_table bulk_action">
                                    <thead>
                                        <tr class="headings">
                                            <th>&nbsp;</th>
                                            <th class="column-title">Id Pedido</th>
                                            <th class="column-title">Cliente </th>
                                            <th class="column-title">Fecha Evento </th>
                                            <th class="column-title">Hora Evento </th>
                                            <th class="column-title">Direccion </th>
                                            <th class="column-title">Telefono </th>
                                            <th class="column-title">Registrado el</th>
                                            <th class="column-title">Registrado por</th>
                                            <th class="bulk-actions" colspan="7"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while (!$rga->EOF) {?>
                                    <tr class="even pointer">
                                    <td class="a-center ">
                                        <a href="cat_adm_pedidos.php?idr=<?php echo $rga->fields['regid']?>" ><span class="fa fa-gear"></span>[Administrar]</a>&nbsp;&nbsp;
                                        
                                        
                                        </td>
                                    <td class=" "><?php echo "C - ".$rga->fields['regid']?></td>
                                      <td class=" "><?php echo $rga->fields['solicitado_por']; ?></td>
                                      <td class=" "><?php echo date("d/m/Y", strtotime($rga->fields['evento_para'])); ?></td>
                                        <td class=" "><?php echo $rga->fields['hora_entrega']; ?></td>
                                        <td class=" "><?php echo $rga->fields['dire_entrega']; ?></td>
                                        <td class=" "><?php echo $rga->fields['telefono']; ?></td>
                                        <td class=" "><?php echo date("d/m/Y H:i:s", strtotime($rga->fields['registrado_el'])); ?></td>
                                        <td class="a-right a-right "><?php echo $rga->fields['quien']; ?></td>
                                      </tr>
                                    <?php $rga->MoveNext();
                                        }?>
                                    </tbody>
                                    </table>
                                    </div>
                      
                      <?php } else { ?>
                            <span class="fa fa-warning"></span>&nbsp;&nbsp;<h2>No existen pedidos confirmados</h2>

                    <?php }?>




                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo"  style="height: auto;" >
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
        <!-- /page content -->
        <script>
        function desocultar(){
            $("#cuerpocolores").show();
            $("#enviocuerpo").show();
            
        }
        //-------------------------DIRECCIONES---------------------//
        function seledire(valor){
            var direccion="";
            if (valor!=''){
                //alert(direccion);
                if (valor==99){
                    //required
                    direccion="";
                     $('#domicilio').prop("required", true);
                }  else {
                    direccion=$("#direh_"+valor).val();
                    //alert(direccion);
                     $('#domicilio').removeAttr("required");
                }
                $("#domicilio").val(direccion);
            }    
        }
        //------------------------ Clientes------------------------->    
        function seleccionarcliente(){    
            $("#myModalLabel").html("<span class='fa fa-search'></span>&nbsp;&nbsp;Buscar cliente");
            var parametros = {
               
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_mini_eventos_clientes.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                                    
                },
                success:  function (response) {
                    $("#modal_cuerpo").html(response);    
                }
            });
          
            $("#dialogobox").modal("show");
            setTimeout( enfocar(),300 );
            
         }
        function enfocar(){
            if (document.getElementById("dcu")){
                document.getElementById("dcu").focus();
            }
            
        }
        function bcliente(valorbusca,metodobusca){
            if (metodobusca==1){
                
                $("#ncc").val("");
            }
            if (metodobusca==2){
                //$("#ncc").val("");
                $("#dcu").val("");
            }
            var parametros = {
                      "valorbusca" : valorbusca,
                    "metodobusca" :metodobusca
            };
            if (valorbusca!=''){
                $.ajax({          
                    data:  parametros,
                    url:   'cat_miniadd_clie.php',
                    type:  'post',
                    cache: false,
                    timeout: 3000,  // I chose 3 secs for kicks: 3000
                    crossDomain: true,
                    beforeSend: function () {    
                                
                    },
                    success:  function (response) {
                        $("#cuerpoclientebusca").html(response);
                        var respu=$("#occlietot").val();
                        if (parseInt(respu)==0){
                                
                                $("#primero").hide();
                                $("#segundo").show();
                                
                                
                        } else {
                            if (parseInt(respu)>1){
                                $("#segundo").hide();
                                $("#primero").hide();
                            } else {
                                $("#primero").hide();
                                $("#segundo").show();
                            }
                        }
                    }
                });
            } else {
                $("#segundo").hide();
                $("#primero").hide();
                $("#cuerpoclientebusca").html("");
            }
        }
        function actualizarcliente(idcliente){
            var dc=$("#dcu").val();
            var rz=$("#rz").val();
            var ruc=$("#ruc").val();
            var cel=$("#cel").val();
            var ema=$("#em").val();
            
                var parametros = {
                      "dc" : dc,
                    "rz" : rz,
                    "ruc" : ruc,
                    "cel" : cel,
                    "ema" : ema,
                    "add"    :1,
                    "idc"    :idcliente
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_miniadd_clie.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                            
                },
                success:  function (response) {
                    $("#cuerpoclientebusca").html(response);
                    if (response!=''){
                        var nm=response.split("|");
                        $("#ocidc").val(nm[0]);
                        $("#cliente").val(nm[1]);
                        $("#dialogobox").modal("hide");
                    }
                }
            });
        }
        function registrarcliente(){
            
            var dc=$("#docu").val();
            var rz=$("#rz").val();
            var ape=$("#apellidos").val();
            var nom=$("#nombres").val();
            var ruc=$("#ruc").val();
            var cel=$("#celu").val();
            var ema=$("#em").val();
            var obs=$("#obclie").val();
            
                var parametros = {
                      "dc" : dc,
                    "rz" : rz,
                    "nombres" : nom,
                    "apellidos" : ape,
                    "ruc" : ruc,
                    "cel" : cel,
                    "ema" : ema,
                    "obs"  : obs,
                    "add"    :1
                    
            };
            $.ajax({          
                data:  parametros,
                url:   'cat_miniadd_clie.php',
                type:  'post',
                cache: false,
                timeout: 3000,  // I chose 3 secs for kicks: 3000
                crossDomain: true,
                beforeSend: function () {    
                            
                },
                success:  function (response) {
                    //alert(response);
                    $("#cuerpoclientebusca").html(response);
                    
                    if (response!='error'){
                        if ($("#erroresvv").length > 0){
                            //alert('llego1');
                            var comprobar=$("#erroresvv").val();
                            //alert(comprobar);
                            if (comprobar==''){
                                
                                var nm=response.split("|");
                                //$("#clientel").val(nm[3]);
                              // $("#clientelfac").val(nm[1]);
                                $("#ocidc").val(nm[0]);
                                $("#cliente").val(nm[3]);
                                $("#ocidpedi").val(nm[2]);
                            
                                var idcliente=$("#ocidc").val();
                                var idclientepedido=$("#ocidpedi").val();
                                seleccionar(idcliente,idclientepedido,nm[3],nm[1]);
                                $("#dialogobox").modal("hide");
                            }
                        } else {
                                var nm=response.split("|");
                                alert(nm[3]);
                              // $("#clientel").val(nm[3]);
                               //$("#clientelfac").val(nm[1]);
                                $("#ocidc").val(nm[0]);
                                $("#cliente").val(nm[3]);
                                $("#ocidpedi").val(nm[2]);
                                var idcliente=$("#ocidc").val();
                                var idclientepedido=$("#ocidpedi").val();
                                //alert(idcliente);alert(idclientepedido);
                                seleccionar(idcliente,idclientepedido,nm[3],nm[1]);
                                $("#dialogobox").modal("hide");
                            
                        }
                    } else {
                        //alert('llego3');
                        $("#cuerpoclientebusca").html(response);
                    }
                }
            });
        }
        //Seleccionar el cliente al buscar
        function seleccionar(idcliente,idclientepedido,qp,fc){
            
        
            var dc=$("#dcu").val();
            var rz=$("#rz").val();
            var ruc=$("#ruc").val();
            var cel=$("#cel").val();
            var ema=$("#em").val();
            var errores='';
            //verificamos si selecciono la fecha y hora del evento_para
            var pchar=$("#clientepedidochar").val();
            var fchar=$("#clientefactuchar").val();
            var fechaev=$("#fechaevento").val();
            var horaev=$("#hora").val();
            //alert(horaev);
            if (fechaev===""){
                errores=errores+'-> Debe indicar la fecha del evento. \n';
            }
            if (horaev===""){
                errores=errores+'-> Debe indicar horario para evento. \n';
            }
            
            if (errores===''){
                $("#clientel").val(pchar);
                $("#clientelfac").val(fchar);
                $("#evento_para").val(fechaev);
                $("#hora_entrega").val(horaev);
                $("#ocidclientefactural").val(idcliente);
                $("#ocidclientepedidol").val(idclientepedido);
                $("#cliente").val(rz);
                $("#dialogobox").modal("hide");
                $("#cuerpopedido").show();
                $("#cuerpoeventos").show();
                $("#cuerpocolores").show();
                $("#enviocuerpo").show();
                $("#cabeceraid").click();
                
                //$("#clientel").val();
            } else {
                //alert(errores);
                $("#errordetalle1").html(errores);
                $("#errorcontinua").show();
            }
                    
        }
        </script>
        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
