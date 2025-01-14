<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require("../../clases/PHPExcel.php");
require_once('../../clases/PHPExcel/IOFactory.php');

// archivos 9
require_once("../includes/upload.php");
require_once("../includes/funcion_upload.php");
set_time_limit(0);

function insertexcel_add($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";

    $iddepartamento_set = antisqlinyeccion($parametros_array['iddepartamento_set'], "text");
    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $estado = antisqlinyeccion($parametros_array['estado'], "int");
    $idpais = antisqlinyeccion($parametros_array['idpais'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "text");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");
    $anulado_por = antisqlinyeccion($parametros_array['anulado_por'], "text");
    $anulado_el = antisqlinyeccion($parametros_array['anulado_el'], "text");

    if (trim($parametros_array['iddepartamento_set']) == '') {
        $valido = "N";
        $errores .= " - El campo iddepartamento_set no puede estar vacio.<br />";
    }
    if (trim($parametros_array['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }
    if (intval($parametros_array['idpais']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpais no puede ser cero o nulo.<br />";
    }
    if (trim($parametros_array['registrado_por']) == '') {
        $valido = "N";
        $errores .= " - El campo registrado_por no puede estar vacio.<br />";
    }
    if (trim($parametros_array['anulado_por']) == '') {
        $valido = "N";
        $errores .= " - El campo anulado_por no puede estar vacio.<br />";
    }
    if (trim($parametros_array['anulado_el']) == '') {
        $valido = "N";
        $errores .= " - El campo anulado_el no puede estar vacio.<br />";
    }

    if ($valido == "S") {

        $consulta = "
		insert into insertexcel
		(iddepartamento_set, descripcion, estado, idpais, registrado_por, registrado_el, anulado_por, anulado_el)
		values
		($iddepartamento_set, $descripcion, $estado, $idpais, $registrado_por, $registrado_el, $anulado_por, $anulado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["errores" => $errores,"valido" => $valido];
    exit;
}

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
        $hoja = $excel->getSheet(0);// categoria
        $numFilas_categorias = $hoja->getHighestRow();
        $numColumnas_categorias = PHPExcel_Cell::columnIndexFromString($hoja->getHighestColumn());
        $valido = "S";
        $errores = "";
        $filas = [];

        for ($fila = 2; $fila <= $numFilas_categorias; $fila++) {
            $iddepartamento_set = intval($hoja->getCellByColumnAndRow(0, $fila)->getValue());
            $descripcion = trim($hoja->getCellByColumnAndRow(1, $fila)->getValue());
            $estado = trim($hoja->getCellByColumnAndRow(2, $fila)->getValue());
            $idpais = trim($hoja->getCellByColumnAndRow(3, $fila)->getValue());
            $registrado_por = trim($hoja->getCellByColumnAndRow(4, $fila)->getValue());
            $registrado_el_str = trim($hoja->getCellByColumnAndRow(5, $fila)->getValue());
            $registrado_el = PHPExcel_Style_NumberFormat::toFormattedString($registrado_el_str, 'YYYY-mm-dd');
            $anulado_por = trim($hoja->getCellByColumnAndRow(6, $fila)->getValue()); // Corregido el índice
            $anulado_el_str = trim($hoja->getCellByColumnAndRow(7, $fila)->getValue()); // Corregido el índice
            $anulado_el = PHPExcel_Style_NumberFormat::toFormattedString($anulado_el_str, 'YYYY-mm-dd');
            $datos_fila = [
                'iddepartamento_set' => $iddepartamento_set,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'idpais' => $idpais,
                'registrado_por' => $registrado_por,
                'registrado_el' => $registrado_el,
                'anulado_por' => $anulado_por,
                'anulado_el' => $anulado_el
            ];
            insertexcel_add($datos_fila);

        }


        /////////////////
        /////////////////////

    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////
}

if (isset($_GET['status']) && ($_GET['status'] != '')) {
    $status = substr(htmlentities($_GET['status']), 0, 200);
}
if ($_GET['cargado'] == 'n') {
    $errores = htmlentities($_GET['status']);
}

function exportarCSV()
{
    global $conexion;
    global $saltolinea;

    $consulta = "SELECT * FROM insertexcel";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $impreso = date("YmdHis");

    $datos = "";

    $columnasPersonalizadas = [
        'iddepartamento' => 'ID Departamento',
        'iddepartamento_set' => 'ID Departamento Set',
        'descripcion' => 'Descripcion',
        'estado' => 'Estado',
        'idpais' => 'ID Pais',
        'registrado_por' => 'Registrado por',
        'registrado_el' => 'Registrado el',
        'anulado_por' => 'Anulado por',
        'anulado_el' => 'Anulado el'
    ];

    foreach ($columnasPersonalizadas as $key => $value) {
        $datos .= limpia_csv_externo($value) . ';';
    }
    $datos .= $saltolinea;

    $impreso = date("YmdHis");

    header('Content-Description: File Transfer');
    header('Content-Type: application/force-download');
    header('Content-Disposition: attachment; filename=ExportaCSV_' . $impreso . '.csv');

    echo $datos;

    // CONSTRUYE CUERPO
    $fila = 1;
    while (!$rs->EOF) {
        $fila++;
        $array = $rs->fields;
        $i = 0;
        foreach ($array as $key => $value) {
            $i++;
            echo limpia_csv_externo($value) . ';';
        }
        echo $saltolinea;
        $rs->MoveNext();
    }
}

if (isset($_POST["descargar_csv"])) {
    exportarCSV();
    exit;
}
function exportarExcel()
{
    global $conexion;

    $objPHPExcel = new PHPExcel();

    $objPHPExcel->setActiveSheetIndex(0);
    $hoja = $objPHPExcel->getActiveSheet();

    $columnasPersonalizadas = [
        'iddepartamento' => 'ID Departamento',
        'iddepartamento_set' => 'ID Departamento Set',
        'descripcion' => 'Descripción',
        'estado' => 'Estado',
        'idpais' => 'ID País',
        'registrado_por' => 'Registrado Por',
        'registrado_el' => 'Registrado El',
        'anulado_por' => 'Anulado Por',
        'anulado_el' => 'Anulado El'
    ];

    $columna = 'A';
    foreach ($columnasPersonalizadas as $nombreColumna) {
        $hoja->setCellValue($columna . '1', $nombreColumna);
        $columna++;
    }

    $consulta = "SELECT * FROM insertexcel";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $fila = 2;
    while (!$rs->EOF) {
        $array = $rs->fields;
        $columna = 'A';
        foreach ($columnasPersonalizadas as $key => $value) {
            $hoja->setCellValue($columna . $fila, $array[$key]);
            $columna++;
        }
        $fila++;
        $rs->MoveNext();
    }

    $impreso = date("YmdHis");
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename=insertexcel_' . $impreso . '.xlsx');
    header('Cache-Control: max-age=0');

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
}

if (isset($_POST["descargar_excel"])) {
    exportarExcel();
    exit;
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
                    <h2>Importar articulos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="insumos_lista.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />

<hr />
<?php if (count($array_fallados) > 0) { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
<strong>Errores:</strong><br />
	<div>
		<ul class="list-group">
			<?php if (count($array_fallados) > 0) { ?>
				<li class="list-group-item list-group-item-danger">
					<strong>Total Errores:</strong> <?php  echo count($array_fallados); ?><br>
					<?php foreach ($array_fallados as $key => $value) {?>
						<strong>Fila:</strong> <?php  echo $value["fila"]; ?>&nbsp;&nbsp;&nbsp; <strong>Articulo:</strong> <?php  echo $value["nombre"]; ?> <strong><br>Error:</strong> <?php  echo $value["error"]; ?><br>
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
        </div>
		<input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
    </div>
	<div class="form-group2">
		<br />
		<br />
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
            <button type="submit" class="btn btn-success" name="descargar_csv"><span class="fa fa-download"></span> Descargar Archivo CSV</button>
        </div>
    </div>
	<div class="form-group3">
    	<input type="hidden" name="descargar_csv" value="2" />
		<br />
		<br />
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
		<button type="submit" class="btn btn-success" name="descargar_excel"><span class="fa fa-download"></span> Descargar Archivo Excel</button>
        </div>
    </div>
    <input type="hidden" name="descargar_excel" value="3" />
 </form>

<p>&nbsp;</p>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />
<a class="btn btn-sm btn-default" type="button" 
href='../gfx/formatos_arch/articulos.xlsx' download><span class="fa fa-download" ></span> Descargar Formato XLSX Ejemplo</a>
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
