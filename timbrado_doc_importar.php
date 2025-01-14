<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");

// para mostrar errores por ejemplo el de falta de memoria en vez de mostarr un error 500 y no saber el problema
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
set_time_limit(240); // 4 minutos

// para evitar el error 500 por falta de memoria
ini_set('memory_limit', '-1');

// archivos
require_once("includes/upload.php");
require_once("includes/funcion_upload.php");

$idtimbrado = intval($_GET['id']);
$consulta = "
select * from timbrado where idtimbrado = $idtimbrado and estado = 1
";
$rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtimbrado = intval($rstimb->fields['idtimbrado']);
$timbrado_cab = solonumeros($rstimb->fields['timbrado']);
$valido_desde_cab = $rstimb->fields['inicio_vigencia'];
$valido_hasta_cab = $rstimb->fields['fin_vigencia'];
if ($idtimbrado == 0) {
    header("location: timbrados.php");
    exit;
}


// tipos de documento: PRE IMPRESO, AUTO IMPRESOR, ELECTRONICO
$consulta = "select * from timbrado_tipo where estado = 1";
$rstip = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipo_impreso_timb = "";
while (!$rstip->EOF) {
    $tipo_impreso_timb .= strtoupper(trim($rstip->fields['timbrado_tipo'])).',';
    $rstip->MoveNext();
}
$tipo_impreso_timb = substr($tipo_impreso_timb, 0, -1);
$tipo_impreso_ar = explode(',', $tipo_impreso_timb);

// factura, nota de credito
$consulta = "select * from timbrado_tipodocu where estado = 1";
$rstipdoc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipo_doc_timb = "";
while (!$rstipdoc->EOF) {
    $tipo_doc_timb .= strtoupper(trim($rstipdoc->fields['tipo_documento'])).',';
    $rstipdoc->MoveNext();
}
$tipo_doc_timb = substr($tipo_doc_timb, 0, -1);
$tipo_doc_ar = explode(',', $tipo_doc_timb);

function limpia_csv_externo($texto)
{
    $texto = utf8_encode($texto);
    $texto = limpiar_texto($texto);
    $texto = preg_replace('/[[:^print:]]/', "", $texto); // elimina caracteres no imprimibles
    return $texto;
}
function limpiar_texto($texto)
{
    //eliminando etiquetas html
    $texto = strip_tags($texto);
    //compruebo que los caracteres sean los permitidos
    $permitidos = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789áéíóúÁÉÍÓÚñÑ _-().:,/;&@#|
	";
    for ($i = 0; $i < strlen($texto); $i++) {
        if (strpos($permitidos, substr($texto, $i, 1)) === false) {
            //echo substr($texto,$i,1);
        } else {
            $result = $result.substr($texto, $i, 1);
        }
    }
    return $result;
}




$status = "";
$msg = urlencode("Archivo Cargado Exitosamente!");
if (isset($_POST["MM_upload"]) && ($_POST["MM_upload"] == "form1")) {

    $directorio = "gfx";

    $fupload = new Upload();
    $fupload->setPath($directorio);
    $fupload->setMinSize(0);
    $exten = ['csv'];
    $extension = strtolower(substr($_FILES['archivo']['name'], strrpos($_FILES['archivo']['name'], '.') + 1));

    $tiempo = date("YmdHis");
    $nombrearchivo = 'timbrado_doc_'.$tiempo.'.'.$extension;
    $nombrearchivo2 = 'timbrado_doc_'.$tiempo;

    $ip_real = htmlentities(ip_real());

    if ($extension == 'csv') {
        $fupload->setFile("archivo", $nombrearchivo2, 'S');
        $fupload->isImage(false);
        // IMAGEN
    } else {
        //$fupload->setFile("archivo",$nombrearchivo2,'N',$extension);
        //$fupload->isImage(true);
    }
    //$fupload->isImage(true);
    $fupload->save();

    $cargado = $fupload->isupload;
    $status = $fupload->message;

    // si se cargo
    if ($cargado) {

        // lee el archivo
        $archivo_csv = file_get_contents($directorio.'/'.$nombrearchivo);
        $array_res = csv_to_array($archivo_csv, ";");
        //print_r($array_res);exit;

        // borra el archivo
        if (file_exists($directorio.'/'.$nombrearchivo)) {
            if (trim($nombrearchivo) != '') {
                unlink($directorio.'/'.$nombrearchivo);
            }
        }

        // validaciones basicas
        $valido = "S";
        $errores = "";
        $i = 1;
        //timbrado	inicio_vigencia	fin_vigencia	documento	tipo_documento	sucursal	punto_expedicion	numero_desde	numero_hasta	comentario


        // recorre el archio y valida
        foreach ($array_res as $fila) {
            // la cabecera se salta
            if ($i > 1) {
                //echo $fila[0];exit;
                $timbrado = substr(solonumeros($fila[1]), 0, 20);
                $documento = strtoupper(substr(trim($fila[4]), 0, 250));
                $tipo_documento = strtoupper(substr(trim($fila[5]), 0, 250));
                $sucursal = substr(intval($fila[6]), 0, 3);
                $punto_expedicion = substr(intval($fila[7]), 0, 3);
                $numero_desde = substr(intval($fila[8]), 0, 20);
                $numero_hasta = substr(solonumeros($fila[9]), 0, 20);
                $comentario = substr(trim($fila[10]), 0, 250);
                $inicio_vigencia_orig = trim($fila[2]);
                $fin_vigencia_orig = trim($fila[3]);

                // convertir fecha
                $inicio_vigencia_ar = explode('/', $inicio_vigencia_orig);
                $inicio_vigencia = $inicio_vigencia_ar[2].'-'.$inicio_vigencia_ar[1].'-'.$inicio_vigencia_ar[0];
                $fin_vigencia_ar = explode('/', $fin_vigencia_orig);
                $fin_vigencia = $fin_vigencia_ar[2].'-'.$fin_vigencia_ar[1].'-'.$fin_vigencia_ar[0];
                $inicio_vigencia = date("Y-m-d", strtotime($inicio_vigencia));
                $fin_vigencia = date("Y-m-d", strtotime($fin_vigencia));

                if (trim($timbrado) == '') {
                    $valido = "N";
                    $errores .= " - El campo timbrado no puede estar vacio, en la fila: [".$i.'] '.$saltolinea;
                }
                if (trim($inicio_vigencia) == '') {
                    $valido = "N";
                    $errores .= " - El campo inicio_vigencia no puede estar vacio, en la fila: [".$i.'] '.$saltolinea;
                }
                if (trim($fin_vigencia) == '') {
                    $valido = "N";
                    $errores .= " - El campo fin_vigencia no puede estar vacio, en la fila: [".$i.'] '.$saltolinea;
                }
                if (trim($documento) == '') {
                    $valido = "N";
                    $errores .= " - El campo documento no puede estar vacio, en la fila: [".$i.'] '.$saltolinea;
                }
                if (trim($tipo_documento) == '') {
                    $valido = "N";
                    $errores .= " - El campo tipo_documento no puede estar vacio, en la fila: [".$i.'] '.$saltolinea;
                }
                if (intval($sucursal) == 0) {
                    $valido = "N";
                    $errores .= " - El campo sucursal no puede ser cero o nulo, en la fila: [".$i.'] '.$saltolinea;
                }
                if (intval($punto_expedicion) == 0) {
                    $valido = "N";
                    $errores .= " - El campo punto_expedicion no puede ser cero o nulo, en la fila: [".$i.'] '.$saltolinea;
                }
                if (intval($numero_desde) == 0) {
                    $valido = "N";
                    $errores .= " - El campo numero_desde no puede ser cero o nulo, en la fila: [".$i.'] '.$saltolinea;
                }
                if (intval($numero_hasta) == 0) {
                    $valido = "N";
                    $errores .= " - El campo numero_hasta no puede ser cero o nulo, en la fila: [".$i.'] '.$saltolinea;
                }
                // fin no puede ser superior a inicio
                if (intval($numero_desde) >= intval($numero_hasta)) {
                    $valido = "N";
                    $errores .= " - El campo inicio no puede ser mayor o igual a fin, en la fila: [".$i.'] '.$saltolinea;
                }
                if ($timbrado != $timbrado_cab) {
                    $valido = "N";
                    $errores .= " - El timbrado enviado [".antixss($timbrado)."] no coincide con el timbrado seleccionado [".antixss($timbrado_cab)."] para carga masiva, en la fila: [".$i.'] '.$saltolinea;
                }
                if ($timbrado != $timbrado_cab) {
                    $valido = "N";
                    $errores .= " - El timbrado enviado no coincide con el timbrado seleccionado para carga masiva, en la fila: [".$i.'] '.$saltolinea;
                }
                if (trim($inicio_vigencia) != $valido_desde_cab) {
                    $valido = "N";
                    $errores .= " - El campo inicio_vigencia [".antixss($inicio_vigencia)."] no coincide con el inicio del timbrado  seleccionado [".antixss($valido_desde_cab)."] para carga masiva, en la fila: [".$i.'] '.$saltolinea;
                }
                if (trim($fin_vigencia) != $valido_hasta_cab) {
                    $valido = "N";
                    $errores .= " - El campo fin_vigencia [".antixss($fin_vigencia)."] no coincide con el final del timbrado seleccionado  [".antixss($valido_hasta_cab)."] para carga masiva, en la fila: [".$i.'] '.$saltolinea;
                }
                if (!in_array($documento, $tipo_doc_ar)) {
                    $valido = "N";
                    $errores .= " - El campo documento contiene valores [".antixss($documento)."] no permitidos, los permitidos son: [".$tipo_doc_timb."], en la fila: [".$i.'] '.$saltolinea;
                }
                if (!in_array($tipo_documento, $tipo_impreso_ar)) {
                    $valido = "N";
                    $errores .= " - El campo tipo_documento contiene valores [".antixss($tipo_documento)."] no permitidos, los permitidos son: [".$tipo_impreso_timb."], en la fila: [".$i.'] '.$saltolinea;
                }





            } // if($i > 1){
            $i++;

        }
        // reset del array
        reset($array_res);

        //echo $errores;
        //exit;
        // si todo es valido inserta
        if ($valido == 'S') {

            // crea cabecera de importacion
            $consulta = "
			INSERT INTO timbrado_import_cab
			(registrado_por,registrado_el) 
			VALUES 
			($idusu,'$ahora')
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // busca el id creado
            $consulta = "
			select idtimbradoimpcab from timbrado_import_cab where registrado_por = $idusu order by registrado_el desc limit 1 
			";
            $rsprpodcab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idtimbradoimpcab = $rsprpodcab->fields['idtimbradoimpcab'];
            // recorre e inserta los detalles
            $i = 1;
            foreach ($array_res as $fila) {

                // la cabecera se salta
                if ($i > 1) {
                    //print_r($fila);exit;

                    // reemplazar popr el de arriba
                    $timbrado = antisqlinyeccion(limpia_csv_externo(substr(solonumeros($fila[1]), 0, 20)), "text");
                    $documento = antisqlinyeccion(limpia_csv_externo(strtoupper(substr(trim($fila[4]), 0, 250))), "text");
                    $tipo_documento = antisqlinyeccion(limpia_csv_externo(strtoupper(substr(trim($fila[5]), 0, 250))), "text");
                    $sucursal = antisqlinyeccion(limpia_csv_externo(substr(intval($fila[6]), 0, 3)), "text");
                    $punto_expedicion = antisqlinyeccion(limpia_csv_externo(substr(intval($fila[7]), 0, 3)), "text");
                    $numero_desde = antisqlinyeccion(limpia_csv_externo(substr(intval($fila[8]), 0, 20)), "text");
                    $numero_hasta = antisqlinyeccion(limpia_csv_externo(substr(solonumeros($fila[9]), 0, 20)), "text");
                    $comentario = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[10]), 0, 250)), "text");
                    $inicio_vigencia_orig = trim($fila[2]);
                    $fin_vigencia_orig = trim($fila[3]);

                    // convertir fecha
                    $inicio_vigencia_ar = explode('/', $inicio_vigencia_orig);
                    $inicio_vigencia = $inicio_vigencia_ar[2].'-'.$inicio_vigencia_ar[1].'-'.$inicio_vigencia_ar[0];
                    $fin_vigencia_ar = explode('/', $fin_vigencia_orig);
                    $fin_vigencia = $fin_vigencia_ar[2].'-'.$fin_vigencia_ar[1].'-'.$fin_vigencia_ar[0];
                    $inicio_vigencia = date("Y-m-d", strtotime($inicio_vigencia));
                    $fin_vigencia = date("Y-m-d", strtotime($fin_vigencia));


                    $inicio_vigencia = antisqlinyeccion($inicio_vigencia, "text");
                    $fin_vigencia = antisqlinyeccion($fin_vigencia, "text");


                    $consulta = "
					insert into timbrado_import
					(idtimbradoimpcab, timbrado, inicio_vigencia, fin_vigencia, documento, tipo_documento, sucursal, punto_expedicion, numero_desde, numero_hasta, comentario)
					values
					($idtimbradoimpcab, $timbrado, $inicio_vigencia, $fin_vigencia, $documento, $tipo_documento, $sucursal, $punto_expedicion, $numero_desde, $numero_hasta, $comentario)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                } // if($i > 1){
                $i++;
            }


            // redireccionar
            header("location: timbrado_doc_importar_control.php?id=$idtimbradoimpcab");
            exit;

        }
    } else {
        header("location: timbrado_doc_importar.php?cargado=n&status=".$status);
        exit;

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
                    <h2>Carga Masiva de Documentos del Timbrado</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="timbrados_det.php?id=<?php echo $idtimbrado; ?>" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
</p>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo nl2br($errores); ?>
</div>
<?php } ?>



<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">



<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo CSV *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <input type="file" name="archivo" id="archivo"  class="form-control" accept=".csv"  />
	</div>
</div>


<div class="clearfix"></div>
<br />





<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-upload"></span> Cargar Archivo</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='timbrados_det.php?id=<?php echo $idtimbrado; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

        <input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
 </form>

<p>&nbsp;</p>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='gfx/formatos_arch/timbrado_doc.csv?nc=<?php echo date("Ymdhis"); ?>'"><span class="fa fa-download"></span> Descargar Formato CSV Ejemplo</button>
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
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
