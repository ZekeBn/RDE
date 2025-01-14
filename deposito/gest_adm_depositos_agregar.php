<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "55";

require_once("../includes/rsusuario.php");
require_once("./preferencias_deposito.php");

//usuarios
$buscar = "Select * from usuarios where idempresa=$idempresa order by usuario asc";
$rslusu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


if (isset($_POST['descripcion']) && ($_POST['descripcion'] != '')) {
    $buscar = "select  max(iddeposito) as mayor from gest_depositos";
    $rsmay = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $mayor = intval($rsmay->fields['mayor']) + 1;
    $errores = '';
    /*--------------------------------------------------------*/
    $describe = antisqlinyeccion($_POST['descripcion'], 'text');
    if ($describe == 'NULL') {
        $errores = '* Debe indicar nombre descriptivo<br />';
    }
    $dire = antisqlinyeccion($_POST['dire'], 'text');
    $sucuelige = intval($_POST['suc']);
    $encargado = intval($_POST['enca']);
    $autosel_compras = antisqlinyeccion($_POST['autosel_compras'], "text");
    if ($preferencia_autosel_compras == "N") {
        $autosel_compras = "'N'";
    }
    if ($encargado == 0) {
        $errores = '* Debe indicar encargado del deposito<br />';

    }
    $color = antisqlinyeccion($_POST['colorse'], 'text');
    /*$tipo=intval($_POST['tiposala']);
    if ($tipo==0){
        $errores='* Debe indicar encargado del deposito<br />';

    }*/
    $tipo = 1;
    $estado = intval($_POST['estado']);

    /*------------------------------------------------------*/

    if ($errores == '') {
        $insertar = "insert into gest_depositos 
		(iddeposito,direccion,idencargado,estado,descripcion,color,tiposala,idempresa,idsucursal,autosel_compras) 
		values
		($mayor,$dire,$encargado,$estado,$describe,$color,$tipo,$idempresa,$sucuelige,$autosel_compras)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    }

}
$buscar = "Select color,descripcion,usuario from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado
where gest_depositos.idempresa=$idempresa and usuarios.idempresa=$idempresa
order by descripcion ASC";
$rsdpto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tdpto = $rsdpto->RecordCount();

//Sucursales
$buscar = "Select * from sucursales where idempresa=$idempresa order by nombre asc";
$rssucu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
;
?>


<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
	function ver(){
		var errores='';
		var des=document.getElementById('descripcion').value;
		if (des==''){
			errores=errores+'* Debe indicar nombre descriptivo. \n'	;
			
		}
		var dire=document.getElementById('dire').value;
		var sucursal=parseInt(document.getElementById('suc').value);
		if (sucursal==''){
			errores=errores+'* Debe indicar sucursal. \n'	;
			
		}
		var encargado=parseInt(document.getElementById('enca').value);
		if (encargado==0){
			errores=errores+'* Debe indicar encargado para deposito. \n'	;
			
		}
		/*var tipo=parseInt(document.getElementById('tiposala').value);
		if (tipo==0){
			errores=errores+'* Debe indicar si es deposito o salon \n'	;
		}*/
		var estado=parseInt(document.getElementById('estado').value);
		if (estado==0){
			errores=errores+'* Debe indicar si estra activo \n'	;
		}
		if (errores!=''){
			alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
		
		} else {
			document.getElementById('regdepto').submit();	
			
		}
	}
	
</script>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
</head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>*Registrar dep&oacute;sitos o salones de venta pertenecientes a la empresa.</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<div class="colcompleto" id="contenedor">
 	<!-- SECCION DONDE COMIENZA TODO -->
    
    <div class="divstd">
	  
    <div class="resumenmini">
    </div>
   <div class="div-izq300">
   		<?php if ($tdpto > 0) {?>
   			<div class="table-responsive">
          <table width="300" class="table table-bordered jambo_table bulk_action">
            <thead>
              <tr>
                              <td height="22" align="center" ><strong>Descripci&oacute;n</strong></td>
                              <td align="center" ><strong>Encargado</strong></td>
                                <td align="center" ><strong>Color</strong></td>
                          </tr>
            </thead>
            <?php while (!$rsdpto->EOF) {?>
            <tr>
                            <td height="29"><?php echo $rsdpto->fields['descripcion']?></td>
                            <td align="center"><?php echo $rsdpto->fields['usuario']?></td>
                              <td align="center"><?php if (($rsdpto->fields['color']) != '') {?><span style="background-color:<?php echo $rsdpto->fields['color']?>;color:<?php echo $rsdpto->fields['color']?>;border:2px solid #c2c2c2;">C. Asignado</span><?php } else {
                                  echo 'Sin color asignado';
                              }?></td>
                          </tr>
            <?php $rsdpto->MoveNext();
            }?>
          </table>
        </div>
   		<?php } else { ?>
        	<span class="resaltarojomini">No se han definido los dep&oacute;sitos / salones de venta</span>
        	
        
        <?php } ?>
   </div>
   <br /><br/>

    <div class="div-izq300">
       <div align="center">
       <form id="regdepto" class = name="regdepto" action="gest_adm_depositos_agregar.php" method="post">
       <div class="col-md-6 col-sm-6 form-group">
          <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre *</label>
          <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="descripcion" id="descripcion" placeholder="Ingrese nombre/descripcion" class="form-control"  required="required"/>                 
          </div>
        </div>

        <div class="col-md-6 col-sm-6 form-group">
          <label class="control-label col-md-3 col-sm-3 col-xs-12">Direcci&oacute;n</label>
          <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="dire" id="dire" placeholder="Ingrese direcci&oacute;n" class="form-control" />
          </div>
        </div>

        <div class="col-md-6 col-sm-6 form-group">
          <label class="control-label col-md-3 col-sm-3 col-xs-12">* Sucursal</label>
          <div class="col-md-9 col-sm-9 col-xs-12">
          <select name="suc" id="suc"  class="form-control">
                    <option value="0" selected="selected">Seleccionar</option>
                    <?php while (!$rssucu->EOF) {?>
                    <option value="<?php echo $rssucu->fields['idsucu']?>"><?php echo $rssucu->fields['nombre']?></option>
                    <?php $rssucu->MoveNext();
                    } ?>
                  </select>
          </div>
        </div> 

        <div class="col-md-6 col-sm-6 form-group">
          <label class="control-label col-md-3 col-sm-3 col-xs-12">Encargado</label>
          <div class="col-md-9 col-sm-9 col-xs-12">
          <select name="enca" id="enca"  class="form-control">
                    <option value="0" selected="selected">Seleccionar</option>
                  	<?php while (!$rslusu->EOF) {?>
                    <option value="<?php echo $rslusu->fields['idusu']?>"><?php echo $rslusu->fields['usuario']?></option>
                    
                    <?php $rslusu->MoveNext();
                  	} ?>
                  </select>
          </div>
        </div>

        <div class="col-md-6 col-sm-6 form-group">
          <label class="control-label col-md-3 col-sm-3 col-xs-12">Color</label>
          <div class="col-md-9 col-sm-9 col-xs-12">
          <input type="color" name="colorse" id="colorse" class="form-control"/>  
          </div>
        </div>

        <div class="col-md-6 col-sm-6 form-group">
          <label class="control-label col-md-3 col-sm-3 col-xs-12">Activar</label>
          <div class="col-md-9 col-sm-9 col-xs-12">
          <select name="estado" id="estado" class="form-control" >
                    <option value="1" selected="selected">SI</option>
                    <option value="0" >NO</option>
                  </select>
          </div>
        </div>
        
        <?php if ($preferencia_autosel_compras == "S") { ?>
          <div class="col-md-6 col-sm-6 form-group">
            <label class="control-label col-md-3 col-sm-3 col-xs-12">Predeterminado en Compras</label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <select name="autosel_compras" id="autosel_compras" class="form-control" >
                <option value="S" >SI</option>
                <option value="N" selected >NO</option>
            </select>
            </div>
          </div>
			  <?php } ?>


    
            <br />
            <div class="col-md-12 col-sm-12 form-group">
              <button type="submit" onclick="ver()" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
                          <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_adm_depositos.php'"><span class="fa fa-ban"></span> Cancelar</button>
            </div> 
          </form>
           <?php if ($errores != '') {?>
           		<span class="resaltarojomini"><?php echo $errores?></span>
           <?php } ?>
       </div>
      </div> 
</div> 
<!-- contenedor -->





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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
