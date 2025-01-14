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




$categoria_fallados = [];
$familia_fallados = [];
$sub_familia_fallados = [];

$status = "";
$msg = urlencode("Archivo Cargado Exitosamente!");
if (isset($_POST["MM_upload"]) && ($_POST["MM_upload"] == "form1")) {

    $categorias_array = [];
    $familia_array = [];
    $subfamilia_array = [];
    $archivo = $_FILES['archivo'];
    if ($archivo['name'] != "") {

        $archivoExcel = $archivo['tmp_name'];
        // Crear un objeto PHPExcel
        $excel = PHPExcel_IOFactory::load($archivoExcel);

        // Seleccionar la primera hoja del archivo
        $hoja = $excel->getSheet(0);// categoria
        $hoja2 = $excel -> getSheet(1);// familia
        $hoja3 = $excel -> getSheet(2);// subfamilia
        //la function es columnas de 0 a n, filas de 1 a n
        // Obtener el número de filas y columnas
        $numFilas_categorias = $hoja->getHighestRow();
        $numColumnas_categorias = PHPExcel_Cell::columnIndexFromString($hoja->getHighestColumn());

        $numFilas_familias = $hoja2->getHighestRow();
        $numColumnas_familias = PHPExcel_Cell::columnIndexFromString($hoja2->getHighestColumn());

        $numFilas_sub_familias = $hoja3->getHighestRow();
        $numColumnas_sub_familias = PHPExcel_Cell::columnIndexFromString($hoja3->getHighestColumn());

        // Recorrer las filas y acceder a los valores
        $valido = "S";
        $errores = "";
        for ($fila = 2; $fila <= $numFilas_categorias; $fila++) {

            $idcategoria = trim($hoja->getCellByColumnAndRow(0, $fila)->getValue());
            $nombre = trim($hoja->getCellByColumnAndRow(1, $fila)->getValue());
            $estado = trim($hoja->getCellByColumnAndRow(3, $fila)->getValue());

            if ($estado != "") {
                if ($estado == "A") {
                    $estado = 1;
                } else {
                    $estado = 6;
                };
            }
            if ($nombre != "" && $estado != "") {
                $nombre = antisqlinyeccion($nombre, "text");

                $consulta = "SELECT id_categoria 
					from categorias
					where
					UPPER(nombre) = UPPER($nombre)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $id_categoria = intval($rs->fields["id_categoria"]);

                if ($id_categoria == 0) {
                    $id_categoria = select_max_id_suma_uno("categorias", "id_categoria")["id_categoria"];
                    $consulta = "
						insert into categorias 
						(id_categoria,nombre,ab,orden,textobanner,imgbanner,estado,borrable,especial,idsucursal,idempresa,margen_seguridad)
						values
						($id_categoria,$nombre,NULL,0,'','',$estado,'S','N',$idsucursal,$idempresa,0)
						";
                    // echo $consulta;
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $categorias_array[$idcategoria] = [
                        "nombre" => $nombre,
                        "estado" => $estado,
                        "id_categoria" => $id_categoria
                    ];
                } else {
                    $categoria_fallados[] = ["fila" => $fila, "nombre" => $nombre, "error" => "elemento ya existente"];
                }
            }

        }
        for ($fila = 2; $fila <= $numFilas_familias; $fila++) {

            $idfamilia = trim($hoja2->getCellByColumnAndRow(0, $fila)->getValue());
            $idcategoria = trim($hoja2->getCellByColumnAndRow(1, $fila)->getValue());
            $nombre = trim($hoja2->getCellByColumnAndRow(2, $fila)->getValue());
            $estado = trim($hoja2->getCellByColumnAndRow(4, $fila)->getValue());
            if ($estado != "") {
                if ($estado == "A") {
                    $estado = 1;
                } else {
                    $estado = 6;
                };
            }
            if ($nombre != "" && $estado != "") {

                $nombre = antisqlinyeccion($nombre, "text");

                $consulta = "SELECT idsubcate 
					from sub_categorias
					where
					UPPER(descripcion) = UPPER($nombre)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idsubcate = intval($rs->fields["idsubcate"]);

                if ($idsubcate == 0) {
                    $idsubcate = select_max_id_suma_uno("sub_categorias", "idsubcate")["idsubcate"];
                    $idcat_select = $categorias_array[$idcategoria]["id_categoria"];
                    $consulta = "
						INSERT INTO sub_categorias
						(idsubcate,idcategoria, descripcion, idempresa, estado, describebanner, orden, muestrafiltro, borrable, margen_seguridad)
						VALUES
						($idsubcate,$idcat_select, $nombre, $idempresa, 1, '', 0, 'S', 'S', 0)
						";
                    // echo $consulta;
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $familia_array[$idfamilia] = [
                        "nombre" => $nombre,
                        "estado" => $estado,
                        "idsubcate" => $idsubcate
                    ];
                } else {
                    $familia_fallados[] = ["fila" => $fila, "nombre" => $nombre, "error" => "elemento ya existente"];

                }
            }

        }

        for ($fila = 2; $fila <= $numFilas_sub_familias; $fila++) {

            $idsubfamilia = trim($hoja3->getCellByColumnAndRow(0, $fila)->getValue());
            $idfamilia = trim($hoja3->getCellByColumnAndRow(1, $fila)->getValue());
            $idcategoria = trim($hoja3->getCellByColumnAndRow(2, $fila)->getValue());
            $nombre = trim($hoja3->getCellByColumnAndRow(3, $fila)->getValue());
            $estado = trim($hoja3->getCellByColumnAndRow(5, $fila)->getValue());
            if ($estado != "") {
                if ($estado == "A") {
                    $estado = 1;
                } else {
                    $estado = 6;
                };
            }
            if ($nombre != "" && $estado != "") {

                $nombre = antisqlinyeccion($nombre, "text");


                $subfamilia_array[$idsubfamilia] = [
                    "nombre" => $nombre,
                    "idcategoria" => $idcategoria,
                    "idfamilia" => $idfamilia,
                    "estado" => $estado
                ];


                $consulta = "SELECT idsubcate_sec
					from sub_categorias_secundaria
					where
					UPPER(descripcion) = UPPER($nombre)
					";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idsubcate_sec = intval($rs->fields["idsubcate_sec"]);



                if ($idsubcate_sec == 0) {
                    $idsubcate = $familia_array[$idfamilia]["idsubcate"];
                    $idsubcate_sec = select_max_id_suma_uno("sub_categorias_secundaria", "idsubcate_sec")["idsubcate_sec"];
                    $idcat_select = $categorias_array[$idcategoria]["id_categoria"];
                    $consulta = "
						insert into sub_categorias_secundaria
						(idsubcate_sec,idsubcate, descripcion, idempresa, estado, registrado_por, registrado_el, margen_seguridad)
						values
						($idsubcate_sec, $idsubcate, $nombre, $idempresa, $estado, $idusu, '$ahora', 0)
						";
                    // echo $consulta;
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                } else {
                    $sub_familia_fallados[] = ["fila" => $fila, "nombre" => $nombre, "error" => "elemento ya existente"];

                }


            }

        }


        // header("location: c.php");
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
                    <h2>Importar Categorias/ Familias y Sub Familias</h2>
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
<?php if (trim($errores) != "" || count($categoria_fallados) > 0 || count($familia_fallados) > 0 || count($sub_familia_fallados) > 0) { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
<strong>Errores:</strong><br /><?php echo nl2br($errores); ?>

<div>
	<ul class="list-group">
		<?php if (count($categoria_fallados) > 0) { ?>
			<li class="list-group-item list-group-item-danger">
				<strong>Total Errores en Categoria:</strong> <?php  echo count($categoria_fallados); ?><br>
				<?php foreach ($categoria_fallados as $key => $value) {?>
					<strong>Fila:</strong> <?php  echo $value["fila"]; ?>&nbsp;&nbsp;&nbsp; <strong>Marca:</strong> <?php  echo $value["nombre"]; ?> <strong>Error:</strong> <?php  echo $value["error"]; ?><br>
				<?php } ?>
			</li>
		<?php } ?>
		<?php if (count($familia_fallados) > 0) { ?>
			<li class="list-group-item list-group-item-danger">
				<strong>Total Errores en Familias:</strong> <?php  echo count($familia_fallados); ?><br>
				<?php foreach ($familia_fallados as $key => $value) {?>
					<strong>Fila:</strong> <?php  echo $value["fila"]; ?>&nbsp;&nbsp;&nbsp; <strong>Marca:</strong> <?php  echo $value["nombre"]; ?> <strong>Error:</strong> <?php  echo $value["error"]; ?><br>
				<?php } ?>
			</li>
		<?php } ?>
		<?php if (count($sub_familia_fallados) > 0) { ?>
			<li class="list-group-item list-group-item-danger">
				<strong>Total Errores en Sub Familias:</strong> <?php  echo count($sub_familia_fallados); ?><br>
				<?php foreach ($sub_familia_fallados as $key => $value) {?>
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
href='../gfx/formatos_arch/categorias.xlsx' download><span class="fa fa-download" ></span> Descargar Formato XLSX Ejemplo</a>
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
