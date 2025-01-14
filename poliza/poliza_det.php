<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "42";
$submodulo = "614";

$dirsup = "S";
require_once("../includes/rsusuario.php");



$idpoliza = intval($_GET['id']);
if ($idpoliza == 0) {
    header("location: poliza.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from poliza 
where 
idpoliza = $idpoliza
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpoliza = intval($rs->fields['idpoliza']);
if ($idpoliza == 0) {
    header("location: poliza.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <style>
		.custom-table {
			width: 100%;
			table-layout: fixed;
		}

		.custom-table th:first-child,
		.custom-table td:first-child {
			width: 70%;
		}

		.custom-table th:last-child,
		.custom-table td:last-child {
			min-width: auto;

		}

	</style>
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
                    <h2>Datos Polizas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	

   

                  <p><a href="poliza.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">

		<tr>
			<th align="center">Idpoliza</th>
			<td align="center"><?php echo intval($rs->fields['idpoliza']); ?></td>
		</tr>
		<tr>
			<th align="center">Archivo</th>
			<td align="center">
        <?php if ($rs->fields['archivo'] != null and $rs->fields['archivo'] != "" and isset($rs->fields['archivo'])) {?>
          <a class="btn btn-sm btn-default " href="../gfx/proveedores/poliza/<?php echo $rs->fields['idproveedor']; ?>/<?php echo($rs->fields['archivo']); ?>" download>Descargar archivo</a>
        <?php } else { ?>
          Sin Archivos
        <?php }?>  
      </td>
		</tr>
		<tr>
			<th align="center">Fecha inicio</th>
			<td align="center"><?php if ($rs->fields['fecha_inicio'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_inicio']));
			}  ?></td>
		</tr>
		<tr>
			<th align="center">Fecha fin</th>
			<td align="center"><?php if ($rs->fields['fecha_fin'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha_fin']));
			}  ?></td>
		</tr>
		<tr>
			<th align="center">Registrado el</th>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
		</tr>
		<tr>
			<th align="center">Registrado por</th>
			<td align="center"><?php echo intval($rs->fields['registrado_por']); ?></td>
		</tr>
		
		
		<tr>
			<th align="center">Idproveedor</th>
			<td align="center"><?php echo intval($rs->fields['idproveedor']); ?></td>
		</tr>


</table>
 </div>
 <br />
<?php
if (true) {
    $idproveedor = $rs->fields['idproveedor'];
    $path = "../gfx/proveedores/poliza/$idproveedor";
    // $directorio = __DIR__ .$path;
    // $directorio=str_replace("/","\\",$directorio);

    // Leer los nombres de los directorios
    // echo $path;exit;
    $archivos = scandir($path);

    // echo json_encode($archivos);exit;
    $actual = $rs->fields['archivo'];
    $actual = str_replace("\\", "/", $actual);
    // echo ($actual);exit;
    $pattern = "/\/([^\/]+)$/"; // Expresión regular para obtener el nombre del archivo

    if (preg_match($pattern, $actual, $matches)) {
        $nombreArchivo = $matches[1];

    }
    // echo $nombreArchivo;
    $archivos = array_slice($archivos, 2);
    // echo json_encode($archivos);
    if (count($archivos) > 1) {
        ?>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action ">
		<h2>Acuerdos Comerciales Historico</h2>
		<?php

                    // echo json_encode(scandir("../gfx/proveedores/acuerdos_comercial/$idproveedor"));
                    // echo json_encode($archivos);exit;

                    // Recorrer los archivos y excluir los directorios "." y ".."
                    foreach ($archivos as $archivo) {
                        if ($archivo != "." && $archivo != "..") {
                            // Verificar si no es un directorio
                            // echo ($path . '/' . $archivo);
                            // echo "<br>";
                            // echo $rs->fields['ac_archivo'];
                            // exit;
                            if (!is_dir($path . '/' . $archivo) && ($archivo) != $actual) {
                                $tiempo = filemtime($path . '/' . $archivo);
                                $tiempo = date("d/m/Y H:i", $tiempo);
                                ?>
			<tr>
				<th align="center"> <?php echo  $tiempo; ?> </th>
				<td align="center"><a class="btn btn-sm btn-default " href="<?php echo $path."/".$archivo ; ?>" download>Descargar archivo</a></td>
			</tr>
		<?php
                            }
                        }
                    }

        ?>
		
	<table>
		<?php }?>
</div>
<?php } ?>











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
