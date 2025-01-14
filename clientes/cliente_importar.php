<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = "S";
$modulo = "1";
$submodulo = "180";
require_once("../includes/rsusuario.php");

// para mostrar errores por ejemplo el de falta de memoria en vez de mostarr un error 500 y no saber el problema
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED);
set_time_limit(240); // 4 minutos

// para evitar el error 500 por falta de memoria
ini_set('memory_limit', '-1');

// archivos
require_once("../includes/upload.php");
require_once("../includes/funcion_upload.php");



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

$consulta = "
SELECT sucursales.estado, GROUP_CONCAT(sucursales.nombre) as sucursales 
FROM sucursales  
where 
estado = 1 
GROUP BY estado
";
$rssucuact = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$sucursales_array = explode(",", $rssucuact->fields['sucursales']);

$consulta = "
select * from cliente where borrable = 'N' and idempresa = $idempresa order by idcliente asc limit 1
";
$rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_pred = strtoupper(trim($rscli->fields['razon_social']));
$ruc_pred = $rscli->fields['ruc'];

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
    $nombrearchivo = 'prod_'.$tiempo.'.'.$extension;
    $nombrearchivo2 = 'prod_'.$tiempo;

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
        //RAZON SOCIAL	RUC	DOCUMENTO	SUCURSAL	TELEFONO	DIRECCION	COMENTARIO	LINEA CREDITO
        // recorre el archio y valida
        foreach ($array_res as $fila) {
            // la cabecera se salta
            if ($i > 1) {
                $razon_social = permitidos(substr(trim($fila[1]), 0, 200));
                $ruc = substr(trim($fila[2]), 0, 45);
                $documento = trim($fila[3]);
                $sucursal = trim($fila[4]);
                $telefono = substr(trim($fila[5]), 0, 45);
                $direccion = substr(trim($fila[6]), 0, 150);
                $comentario = substr(trim($fila[7]), 0, 500);
                $linea_credito = floatval(str_replace(',', '.', $fila[8]));
                $nombre = substr(trim($fila[9]), 0, 45);
                $apellido = substr(trim($fila[10]), 0, 45);
                $tipo_cliente = substr(trim($fila[11]), 0, 45);
                $latitud = floatval(trim($fila[12]), 0, 45);
                $longitud = floatval(trim($fila[13]), 0, 45);
                $email = substr(trim($fila[14]), 0, 45);

                if ($razon_social == '') {
                    $valido = "N";
                    $errores .= "- El campo razon_social no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($ruc == '') {
                    $valido = "N";
                    $errores .= "- El campo ruc no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($ruc_pred == $ruc) {
                    if ($documento == '') {
                        $valido = "N";
                        $errores .= "- El campo documento no puede estar vacio cuando el ruc es '$ruc_pred' en la fila: [".$i.'] '.$saltolinea;
                    }
                }
                if ($sucursal == '') {
                    $valido = "N";
                    $errores .= "- El campo sucursal no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($tipo_cliente == '') {
                    $valido = "N";
                    $errores .= "- El campo tipo cliente no puede estar vacio en la fila: [".$i.'] '.$saltolinea;
                }
                if ($tipo_cliente != 'FISICO' && $tipo_cliente != 'JURIDICO') {
                    $valido = "N";
                    $errores .= "- El campo tipo cliente debe ser 'FISICO' o 'JURIDICO' en la fila: [".$i.'] '.$saltolinea;
                }




                // medida si existe en la bd
                if (!in_array($sucursal, $sucursales_array)) {
                    $sucursal_txt = htmlentities($sucursal);
                    $valido = "N";
                    $errores .= "- El campo sucursal [$sucursal_txt] no corresponde a ningun valor aceptado en la fila: [".$i.'] '.$saltolinea;
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
			INSERT INTO cliente_import_cab
			(registrado_por,registrado_el) 
			VALUES 
			($idusu,'$ahora')
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // busca el id creado
            $consulta = "
			select idclienteimpcab from cliente_import_cab where registrado_por = $idusu order by registrado_el desc limit 1 
			";
            $rsprpodcab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idclienteimpcab = $rsprpodcab->fields['idclienteimpcab'];
            // recorre e inserta los detalles
            $i = 1;
            foreach ($array_res as $fila) {

                // la cabecera se salta
                if ($i > 1) {
                    //print_r($fila);exit;
                    // reemplazar popr el de arriba
                    $codigo_cliente = antisqlinyeccion(limpia_csv_externo(trim($fila[1])), "int");
                    $codigo_persona = antisqlinyeccion(limpia_csv_externo(trim($fila[2])), "int");
                    $razon_social = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[3]), 0, 200)), "text");
                    $ruc = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[4]), 0, 45)), "text");
                    $documento = antisqlinyeccion(limpia_csv_externo(trim($fila[5])), "int");
                    $sucursal = antisqlinyeccion(limpia_csv_externo(trim($fila[6])), "text");
                    $telefono = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[7]), 0, 45)), "text");
                    $celular = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[8]), 0, 45)), "text");
                    $codigoedi = antisqlinyeccion(limpia_csv_externo(trim($fila[9])), "int");
                    $comentario = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[10]), 0, 500)), "text");
                    $nombre = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[11]), 0, 45)), "text");
                    $apellido = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[12]), 0, 45)), "text");
                    $tipo_cliente = antisqlinyeccion(substr(trim($fila[13]), 0, 45), "text");
                    $email = antisqlinyeccion(substr(trim($fila[14]), 0, 45), "text");
                    $idnaturalezapersona = antisqlinyeccion(limpia_csv_externo(trim($fila[15])), "int");
                    $idlistaprecio = antisqlinyeccion(limpia_csv_externo(trim($fila[16])), "int");
                    $idcadena = antisqlinyeccion(limpia_csv_externo(trim($fila[17])), "int");
                    $tipo_moneda = antisqlinyeccion(limpia_csv_externo(trim($fila[18])), "int");
                    $direccion = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[19]), 0, 150)), "text");
                    $numero_casa = antisqlinyeccion(limpia_csv_externo(trim($fila[20])), "int");
                    $direccion2 = antisqlinyeccion(limpia_csv_externo(substr(trim($fila[21]), 0, 150)), "text");
                    $numero_casa2 = antisqlinyeccion(limpia_csv_externo(trim($fila[22])), "int");
                    $dia_visita = antisqlinyeccion(limpia_csv_externo(trim($fila[23])), "int");





                    $consulta = "
					insert into cliente
					(codigo_cliente,codigo_persona,razon_social,ruc,documento,sucursal,telefono,celular,codigoedi,comentario,nombre,apellido,tipocliente,email,idnaturalezapersona,
					idlistaprecio,idcadena,idmoneda,direccion,numero_casa,direccion2,numero_casa2,dia_visita)
					values
					($codigo_cliente, $codigo_persona, $razon_social, $ruc, $documento, $sucursal, $telefono, $celular, $codigoedi, $comentario, 
					$nombre, $apellido, $tipo_cliente, $email, $idnaturalezapersona, $idlistaprecio, $idcadena, $tipo_moneda, $direccion, 
					$numero_casa, $direccion2, $numero_casa2, $dia_visita)
					";
                    //echo $consulta;exit;
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


                } // if($i > 1){
                $i++;
            }


            // redireccionar
            header("location: cliente_importar_control.php?id=$idclienteimpcab");
            exit;

        }
    } else {
        header("location: cliente_importar.php?cargado=n&status=".$status);
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
                    <h2>Importar Clientes</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<p>
<a href="cliente.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
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
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
onmouseup="document.location.href='gfx/formatos_arch/clientes.csv?nc=<?php echo date("Ymdhis"); ?>'"><span class="fa fa-download"></span> Descargar Formato CSV Ejemplo</button>
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
