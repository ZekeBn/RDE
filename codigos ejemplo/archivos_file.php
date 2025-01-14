<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
$idproveedor = 1;
if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {
    // echo json_encode($_FILES['archivo']);
    // echo json_encode($_POST);exit;
    if (isset($_FILES['archivo'])) {
        if (is_dir("../gfx/proveedores/acuerdos_comercial")) {

        } else {
            //creamos
            mkdir("../gfx/proveedores", "0777");
            mkdir("../gfx/proveedores/acuerdos_comercial", "0777");

        }
        $tamanoMaximo = 900 * 1024;
        $source_file = $_FILES['archivo']['tmp_name'];
        $extension_archivo = end(explode('.', $_FILES['archivo']['name']));
        $nombre_archivo = 'prv_'.$clasedocu.'_'.date("YmdHis").'.'.$extension_archivo;
        $dest_file = "../gfx/proveedores/acuerdos_comercial/$idproveedor/".$nombre_archivo;
        $directorio = "../gfx/proveedores/acuerdos_comercial/$idproveedor";

        if (is_dir($directorio)) {

        } else {
            //creamos
            mkdir($directorio, "0777");

        }
        $nombre_archivo_ant = antisqlinyeccion($_FILES['archivo']['name'], "text");
        $tipo = $_FILES['archivo']['type'];
        //$extension = substr($_FILES['archivo']['name'], -3);
        $extension = end(explode('.', $_FILES['archivo']['name']));
        if ($_FILES['archivo']['size'] <= $tamanoMaximo) {
            $valido = "N";
            $errores .= "El archivo .pdf,.jpg o .jpeg no puede pesa mas de 900KB";
        }

        $documento = $_FILES['archivo']['name'];
        if ($_FILES['archivo']['type'] == "application/pdf" or $extension == "jpg" or $extension == "jpeg") {
            if (file_exists($dest_file)) {
                echo "El archivo ya existe";
                exit;
            } else {
                //echo 'MOVER';exit;
                // echo json_encode($source_file);
                // echo json_encode($dest_file);exit;
                move_uploaded_file($source_file, $dest_file) or die("Error!!");
                //echo "paso";exit;
                if ($_FILES['archivo']['error'] == 0) {
                    $logmostrar .= "Cargado correctamente - Detalles : </u></b><br/>";
                    $logmostrar .= "Nombre: ".$nombre_archivo."<br.>"."<br/>";
                    $logmostrar .= "Tamanho : ".htmlentities($_FILES['archivo']['size'])." bytes"."<br/>";
                    $logmostrar .= "Ubicacion : ".$dest_file."<br/>";
                    // SUBSTRING_INDEX(archivo, '/', -1) explode mysql
                    // $insertar="insert into cliente_legajo
                    // (idcliente,idtipodocumento,archivo,estado,registrado_por,registrado_el,
                    // nombre_antiguo_arch,comentario,idsoportegastopla
                    // )
                    //     values
                    // ($idcliente,$clasedocu,'$dest_file',1,$idusu,'$ahora',
                    // $nombre_archivo_ant,$comentario,$idsoportegastopla)";
                    // $conexion->Execute($insertar) or die(errorpg($conexion,$insertar));

                    // header("location: cliente_legajo.php?id=".$idcliente);
                    // exit;
                }
            }
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
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
                    <h2>archivo file ejemplo</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	
                  <!-- <?php //echo $logmostrar;?> -->
                  <form id="form1" name="form1" method="post" action="" enctype="multipart/form-data">
    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Acuerdo Comercial pdf</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="file" name="archivo" id="archivo" class="form-control" />
        </div>
    </div>

    <input type="hidden" name="MM_insert" value="form1" />
    <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
	<br />
    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
        <a href="../gfx/proveedores/acuerdos_comercial/1/leg__20230628130742.pdf" download>Descargar archivo</a>
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_proveedores.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>
</form>





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
