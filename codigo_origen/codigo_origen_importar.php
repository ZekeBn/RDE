<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
// $modulo="42";
// $submodulo="617";
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require("../../clases/PHPExcel.php");

// archivos
require_once("../includes/upload.php");
require_once("../includes/funcion_upload.php");
set_time_limit(120);

function limpia_csv_externo($texto)
{
    $texto = utf8_encode($texto);
    return $texto;
}


$consulta = "
SELECT estado, GROUP_CONCAT(iva_porc) as ivas
FROM tipo_iva
GROUP BY estado
";
$rsivas = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ivas_array = explode(",", $rsivas->fields['ivas']);

$consulta = "
SELECT medidas.estado, GROUP_CONCAT(medidas.nombre) as medidas 
FROM medidas 
where 
estado = 1 
GROUP BY estado
";
$rsmed = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$medidas_array = explode(",", $rsmed->fields['medidas']);


$status = "";
$msg = urlencode("Archivo Cargado Exitosamente!");
if (isset($_POST["MM_upload"]) && ($_POST["MM_upload"] == "form1")) {
    $archivo = $_FILES['archivo'];
    if ($archivo['name'] != "") {
        if (is_dir("../gfx/proveedores/codigo_origen")) {

        } else {
            //creamos
            mkdir("../gfx/proveedores", "0777");
            mkdir("../gfx/proveedores/codigo_origen", "0777");

        }
        $date_now = date("YmdHis");
        $extension_archivo = end(explode('.', $archivo['name']));
        $nombre_archivo = 'pfob_'.$date_now.'.'.$extension_archivo;
        $dest_file = "../gfx/proveedores/codigo_origen/".$nombre_archivo;
        if (!file_exists($dest_file)) {
            // move_uploaded_file( $archivo['tmp_name'], $dest_file ) or die ("Error!!");
            // $archivo_cargado = 1;
            // $archivoExcel = $dest_file;
            $archivoExcel = $archivo['tmp_name'];
            ///////////////////////////////////////////////////////////////////////////

            // Crear un objeto PHPExcel
            $excel = PHPExcel_IOFactory::load($archivoExcel);

            // Seleccionar la primera hoja del archivo
            $hoja = $excel->getActiveSheet();

            // Obtener el número de filas y columnas
            $numFilas = $hoja->getHighestRow();
            $numColumnas = PHPExcel_Cell::columnIndexFromString($hoja->getHighestColumn());
            $idproveedor = $hoja->getCellByColumnAndRow(1, 1)->getValue();
            // Recorrer las filas y acceder a los valores
            $valido = "S";
            $errores = "";
            for ($fila = 3; $fila <= $numFilas; $fila++) {

                $codigo = trim($hoja->getCellByColumnAndRow(0, $fila)->getValue());
                $precio = trim($hoja->getCellByColumnAndRow(1, $fila)->getValue());
                $fechaCell = $hoja->getCellByColumnAndRow(2, $fila);
                $fechaValue = trim($fechaCell->getValue());

                $fechaFormatted = PHPExcel_Style_NumberFormat::toFormattedString($fechaValue, 'YYYY-DD-MM');
                if ($codigo == "" && $precio == "" && $fechaValue == "") {
                } else {

                    if ($codigo == "") {
                        $valido = "N";
                        $errores .= "Fila $fila el codigo no puede ser nulo.<br>";
                    }
                    if ($precio == "") {
                        $valido = "N";
                        $errores .= "Fila $fila el precio no puede ser nulo.<br>";
                    }
                    if ($fechaValue == "") {
                        $valido = "N";
                        $errores .= "Fila $fila la fecha no puede ser nula.<br>";
                    }
                }
            }
            if ($valido == "S") {

                for ($fila = 3; $fila <= $numFilas; $fila++) {

                    $codigo_articulo = trim($hoja->getCellByColumnAndRow(0, $fila)->getValue());
                    $precio = trim($hoja->getCellByColumnAndRow(1, $fila)->getValue());
                    $fechaCell = $hoja->getCellByColumnAndRow(2, $fila);
                    $fecha = trim($fechaCell->getValue());
                    $fecha = PHPExcel_Style_NumberFormat::toFormattedString($fecha, 'DD-MM-YYYY');
                    if ($codigo_articulo != "" && $precio != "" && $fecha != "") {
                        $registrado_por = $idusu;
                        $registrado_el = antisqlinyeccion($ahora, "text");
                        $fecha = date("Y-m-d", strtotime($fecha));

                        /////busca si no existe
                        $consulta = "SELECT idfob from proveedores_fob
						where idproveedor = $idproveedor and codigo_articulo = '$codigo_articulo' and estado = 1
						";
                        $rs_fob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        ////////inserta
                        if (intval($rs_fob->fields['idfob']) > 0) {
                            $idfob = intval($rs_fob->fields['idfob']);
                            $consulta = "
							update proveedores_fob 
							set
							precio=$precio,
							fecha='$fecha',
							registrado_el=$registrado_el,
							registrado_por=$registrado_por
							where
							idfob=$idfob
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                        } else {
                            $idfob = select_max_id_suma_uno("proveedores_fob", "idfob")["idfob"];
                            $consulta = "
							insert into proveedores_fob
							(idfob, idproveedor, codigo_articulo,precio,fecha,registrado_el, registrado_por, estado)
							values
							($idfob, $idproveedor, '$codigo_articulo', $precio, '$fecha', $registrado_el, $registrado_por, 1
							)
							";
                            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        }
                    }
                }

            }
            unlink($dest_file);
            header("location: codigo_origen.php");
            exit;
            ///////////////////////////////////////////////////////////////////////////////////////////////////
        }
    }

}

if (isset($_GET['status']) && ($_GET['status'] != '')) {
    $status = substr(htmlentities($_GET['status']), 0, 200);
}
if ($_GET['cargado'] == 'n') {
    $errores = htmlentities($_GET['status']);
}



?><!DOCTYPE html>
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
                    <h2>Importar Codigo Origen</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="codigo_origen.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />

<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo nl2br($errores); ?>
</div>
<?php } ?>



<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">



<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo xlsx *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <input type="file" name="archivo" id="archivo"  class="form-control" accept=".xlsx"  />
	</div>
</div>


<div class="clearfix"></div>
<br />





<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-upload"></span> Cargar Archivo</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='codigo_origen.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

        <input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
 </form>

<p>&nbsp;</p>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />
<a class="btn btn-sm btn-default" type="button" 
href='../gfx/formatos_arch/codigo_origen.xlsx' download><span class="fa fa-download" ></span> Descargar Formato XLSX Ejemplo</a>
<br />
<br />
<strong>Paso 2:</strong><br />
Cargar aqui el archivo excel con las nuevas cantidades.
<br />
 </form>

<p>&nbsp;</p>
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
