<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");


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

    $directorio = "gfx";

    $fupload = new Upload();
    $fupload->setPath($directorio);
    $fupload->setMinSize(0);
    $exten = ['csv'];
    $extension = strtolower(substr($_FILES['archivo']['name'], strrpos($_FILES['archivo']['name'], '.') + 1));

    $tiempo = date("YmdHis");
    $nombrearchivo = 'insu_'.$tiempo.'.'.$extension;
    $nombrearchivo2 = 'insu_'.$tiempo;

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
        //DESCRIPCION;CATEGORIA;SUBCATEGORIA;IDMEDIDA;COSTO UNITARIO;IVA COMPRA;GRUPO STOCK;HABILITA COMPRA;HABILITA INVENTARIO
        // recorre el archio y valida
        foreach ($array_res as $fila) {
            // la cabecera se salta
            if ($i > 1) {
                $descripcion = trim($fila[1]);
                $categoria = trim($fila[2]);
                $subcategoria = trim($fila[3]);
                $medida = trim($fila[4]);
                $costo = floatval(str_replace(',', '.', $fila[5]));
                $iva_compra = str_replace(',', '.', trim($fila[6]));
                $grupo_stock = trim($fila[7]);
                $hab_compra = trim($fila[8]);
                $hab_invent = trim($fila[9]);
                $idconcepto = trim($fila[10]);
                $idcentroprod = trim($fila[11]);
                $idagrupacionprod = trim($fila[12]);
                $idplancuentadet = trim($fila[13]);
                $rendimiento_porc = floatval($fila[14]);

                if ($descripcion == '') {
                    $valido = "N";
                    $errores .= "- El campo descripcion no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($categoria == '') {
                    $valido = "N";
                    $errores .= "- El campo categoria no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($subcategoria == '') {
                    $valido = "N";
                    $errores .= "- El campo subcategoria no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($medida == '') {
                    $valido = "N";
                    $errores .= "- El campo subcategoria no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($costo < 0) {
                    $valido = "N";
                    $errores .= "- El campo costo no puede ser menor a cero en la fila: [".$i.'] '.$saltolinea;
                }
                if ($iva_compra == '') {
                    $valido = "N";
                    $errores .= "- El campo iva no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($iva_compra < 0) {
                    $valido = "N";
                    $errores .= "- El campo iva no puede ser menor a cero en la fila: [".$i.'] '.$saltolinea;
                }
                if ($grupo_stock == '') {
                    $valido = "N";
                    $errores .= "- El campo grupo stock no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($hab_compra != 'SI' && $hab_compra != 'NO') {
                    $valido = "N";
                    $errores .= "- El campo habilita compra debe tener los valores aceptados SI/NO en la fila: [".$i.'] '.$saltolinea;
                }
                if ($hab_invent != 'SI' && $hab_invent != 'NO') {
                    $valido = "N";
                    $errores .= "- El campo habilita inventario debe tener los valores aceptados SI/NO en la fila: [".$i.'] '.$saltolinea;
                }
                if ($idconcepto <= 0) {
                    $valido = "N";
                    $errores .= "- El campo cod concepto es obligatorio en la fila: [".$i.'] '.$saltolinea;
                }


                // medida si existe en la bd
                if (!in_array($medida, $medidas_array)) {
                    $medida_txt = htmlentities($medida);
                    $valido = "N";
                    $errores .= "- El campo medida [$medida_txt] no corresponde a ningun valor aceptado en la fila: [".$i.'] '.$saltolinea;
                }


                // iva si existe en la bd
                if (!in_array($iva_compra, $ivas_array)) {
                    $iva_compra_txt = htmlentities($iva_compra);
                    $valido = "N";
                    $errores .= "- El campo valor del campo IVA [$iva_compra_txt] no corresponde a ningun valor aceptado en la fila: [".$i.'] '.$saltolinea;
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
			INSERT INTO insumos_lista_import_cab
			(registrado_por,registrado_el) 
			VALUES 
			($idusu,'$ahora')
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // busca el id creado
            $consulta = "
			select idinsumoimpcab from insumos_lista_import_cab where registrado_por = $idusu order by registrado_el desc limit 1 
			";
            $rsinscab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idinsumoimpcab = $rsinscab->fields['idinsumoimpcab'];
            // recorre e inserta los detalles
            $i = 1;
            foreach ($array_res as $fila) {
                // la cabecera se salta
                if ($i > 1) {
                    $descripcion = antisqlinyeccion(limpia_csv_externo(trim($fila[1])), "text");
                    $categoria = antisqlinyeccion(limpia_csv_externo(trim($fila[2])), "text");
                    $subcategoria = antisqlinyeccion(limpia_csv_externo(trim($fila[3])), "text");
                    $medida = antisqlinyeccion(limpia_csv_externo(trim($fila[4])), "text");
                    $costo = antisqlinyeccion(floatval(str_replace(',', '.', $fila[5])), "float");
                    $iva_compra = antisqlinyeccion(floatval(str_replace(',', '.', $fila[6])), "int");
                    $grupo_stock = antisqlinyeccion(limpia_csv_externo(trim($fila[7])), "text");
                    $hab_compra = antisqlinyeccion(substr(trim($fila[8]), 0, 2), "text");
                    $hab_invent = antisqlinyeccion(substr(trim($fila[9]), 0, 2), "text");
                    $idconcepto = antisqlinyeccion(intval($fila[10]), "text");
                    $idcentroprod = antisqlinyeccion(intval($fila[11]), "int");
                    $idagrupacionprod = antisqlinyeccion(intval($fila[12]), "int");
                    $idplancuentadet = antisqlinyeccion(intval($fila[13]), "int");
                    $rendimiento_porc = antisqlinyeccion(floatval($fila[14]), "float");

                    // CONVERSIONES

                    // si el rendimiento no es mayor a 0 entonces fijar a 100%
                    if (floatval($fila[14]) <= 0) {
                        $rendimiento_porc = 100;
                    }


                    $consulta = "
					INSERT INTO insumos_lista_import
					(
					idinsumoimpcab, descripcion, categoria, subcategoria, medida, costo_unitario, iva_compra_porc, grupo_stock, hab_compra, hab_invent, idconcepto, 
					idcentroprod, idagrupacionprod, idplancuentadet, rendimiento_porc
					)
					values
					(
					$idinsumoimpcab, $descripcion, $categoria, $subcategoria, $medida, $costo, $iva_compra, $grupo_stock, $hab_compra, $hab_invent, $idconcepto,
					$idcentroprod, $idagrupacionprod, $idplancuentadet, $rendimiento_porc
					)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                } // if($i > 1){
                $i++;
            }


            // redireccionar
            header("location: insumos_lista_importar_control.php?id=$idinsumoimpcab");
            exit;

        }
    } else {
        header("location: insumos_lista_importar.php?cargado=n&status=".$status);
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
                    <h2>Importar Articulos</h2>
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
Esta seccion es para crear articulos que <strong style="color:#F00">NO SE VENDERAN</strong>, si desea crear un producto para vender hacerlo en:  <a href="gest_listado_productos.php"  class="btn btn-sm btn-default"><span class="fa fa-external-link"></span> productos</a>.

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
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='insumos_lista.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
onmouseup="document.location.href='gfx/formatos_arch/insumos.csv?nc=<?php echo date("Ymdhis"); ?>'"><span class="fa fa-download"></span> Descargar Formato CSV Ejemplo</button>
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
