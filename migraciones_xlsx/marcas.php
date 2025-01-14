<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
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




$array_fallados = [];
$status = "";
$msg = urlencode("Archivo Cargado Exitosamente!");
if (isset($_POST["MM_upload"]) && ($_POST["MM_upload"] == "form1")) {


    $archivo = $_FILES['archivo'];
    if ($archivo['name'] != "") {

        $archivoExcel = $archivo['tmp_name'];
        $excel = PHPExcel_IOFactory::load($archivoExcel);

        // Seleccionar la primera hoja del archivo
        $hoja = $excel->getActiveSheet();// marcas
        $numFilas_marcas = $hoja->getHighestRow();
        $numColumnas_marcas = PHPExcel_Cell::columnIndexFromString($hoja->getHighestColumn());


        // Recorrer las filas y acceder a los valores
        $valido = "S";
        $errores = "";
        for ($fila = 2; $fila <= $numFilas_marcas; $fila++) {

            $nombre = trim($hoja->getCellByColumnAndRow(1, $fila)->getValue());
            $estado = trim($hoja->getCellByColumnAndRow(2, $fila)->getValue());

            if ($estado != "") {
                if ($estado == "A") {
                    $estado = 1;
                } else {
                    $estado = 6;
                };
            }
            if ($nombre != "" && $estado != "") {


                $nombre = antisqlinyeccion($nombre, "text");
                // $idmarca = select_max_id_suma_uno("marca","idmarca")["idmarca"];

                $consulta = "SELECT idmarca 
					from marca
					where
					UPPER(marca) = UPPER($nombre)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idmarca = intval($rs->fields["idmarca"]);




                if ($idmarca == 0) {
                    $consulta = "
						insert into marca
						(marca, idempresa, creado_por, creado_el,idestado)
						values
						($nombre, $idempresa, $idusu, '$ahora',$estado)
						";
                    // echo $consulta;
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                } else {
                    $array_fallados[] = ["fila" => $fila, "nombre" => $nombre, "estado" => $estado, "error" => "elemento ya existente"];
                }
            }

        }




        // exit;
        ///////////////////////////////////////////////////////////////////////////////////////////////////
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
                    <h2>Importar Marcas</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="#" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />

<hr />

<?php if (trim($errores) != "" || count($array_fallados) > 0) { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
<strong>Errores:</strong><br /><?php echo nl2br($errores); ?>
	<div>
		<ul class="list-group">
			<?php if (count($array_fallados) > 0) { ?>
				<li class="list-group-item list-group-item-danger">
					<strong>Total Errores:</strong> <?php  echo count($array_fallados); ?><br>
					<?php foreach ($array_fallados as $key => $value) {?>
						<strong>Fila:</strong> <?php  echo $value["fila"]; ?>&nbsp;&nbsp;&nbsp; <strong>Marca:</strong> <?php  echo $value["nombre"]; ?> <strong>Error:</strong> <?php  echo $value["error"]; ?><br>
					<?php } ?>
				</li>
			<?php } ?>
		</ul>
	</div>
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
href='../gfx/formatos_arch/marcas.xlsx' download><span class="fa fa-download" ></span> Descargar Formato XLSX Ejemplo</a>
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
