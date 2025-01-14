<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");

// archivos
require_once("../includes/upload.php");
require_once("../includes/funcion_upload.php");
set_time_limit(120);


$idconteo = intval($_GET['id']);
if (intval($idconteo) == 0) {
    header("location: conteo_stock.php");
    exit;
}

$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo,
(select usuario from usuarios where idusu = conteo.iniciado_por) as usuario
from conteo
where
estado <> 6
and (estado = 1 or estado = 2)
and idconteo = $idconteo
and afecta_stock = 'N'
and fecha_final is null
and idempresa = $idempresa
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddeposito = intval($rs->fields['iddeposito']);
if (intval($rs->fields['idconteo']) == 0) {
    header("location: conteo_stock.php");
    exit;
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
    $nombrearchivo = 'invent_'.$tiempo.'.'.$extension;
    $nombrearchivo2 = 'invent_'.$tiempo;

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
        // recorre el archio y valida
        foreach ($array_res as $fila) {
            // la cabecera se salta
            if ($i > 1) {
                $cantidad = trim($fila[1]);
                $idinsumo = intval($fila[5]);

                $consulta = "
				select idinsumo from insumos_lista where idinsumo = $idinsumo and estado = 'A'
				";
                $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idinsumoex = intval($rsins->fields['idinsumo']);

                if (intval($idinsumoex) <= 0) {
                    $valido = "N";
                    $errores .= " - El articulo con codigo [$idinsumo] no existe o fue borrado. Linea: $i.<br />";
                }
                /*if(trim($cantidad) == ''){
                    $valido="N";
                    $errores.=" - El campo cantidad no puede estar vacio. Linea: $i, Codigo: [$idinsumo].<br />";
                }*/


            } // if($i > 1){
            $i++;

        }
        // reset del array
        reset($array_res);

        //echo $errores;
        //exit;
        // si todo es valido inserta
        if ($valido == 'S') {
            // borra todo para reemplazar por el excel
            $consulta = "
			delete from conteo_detalles where idconteo  = $idconteo
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $i = 1;
            foreach ($array_res as $fila) {
                // la cabecera se salta
                if ($i > 1) {
                    $cantidad = floatval(str_replace(',', '.', $fila[1]));
                    $idinsumo = intval($fila[5]);
                    // SALTAR VALORES VACIOS
                    if (trim($fila[1]) != '') {

                        $consulta = "
						select descripcion from insumos_lista where idinsumo = $idinsumo
						";
                        $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $descripcion = antisqlinyeccion($rsins->fields['descripcion'], "text");

                        // stock disponible
                        $consulta = "
						select sum(disponible) as total_stock,
						(
						select productos_sucursales.precio 
						from productos_sucursales
						where 
						productos_sucursales.idproducto = $idinsumo
						and productos_sucursales.idsucursal = $idsucursal
						) as pventa
						from gest_depositos_stock_gral 
						where 
						gest_depositos_stock_gral.idproducto = $idinsumo
						and gest_depositos_stock_gral.iddeposito = $iddeposito
						";
                        $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                        $disponible = floatval($rsdisp->fields['total_stock']);
                        $pventa = floatval($rsdisp->fields['pventa']);
                        $pcosto = 0;
                        $cantidad_sistema = $disponible;
                        //calculos
                        $venta = floatval($rsdisp->fields['venta']);
                        $cantidad_contada = $cantidad;
                        $cantidad_teorica = floatval($disponible);
                        $cantidad_teorica_cv = $cantidad_teorica + $venta;
                        $diferencia = $cantidad_contada - $cantidad_teorica;
                        $diferencia_cv = $cantidad_contada - $cantidad_teorica_cv;
                        $cantidad_venta = "0";
                        if ($sumavent == 'S') {
                            $diferencia = $diferencia_cv;
                            $cantidad_venta = $venta;
                        }
                        $precio_venta = $pventa;
                        $precio_costo = $pcosto;
                        $diferencia_pv = $diferencia * $precio_venta;
                        $diferencia_pc = $diferencia * $precio_costo;

                        $consulta = "
						insert into conteo_detalles
						(idconteo, idinsumo,  cantidad_contada,  cantidad_sistema, cantidad_venta, 
						precio_venta, precio_costo, diferencia, diferencia_pv, diferencia_pc, 
						descripcion, idusu, ubicacion)
						values
						($idconteo, $idinsumo,  $cantidad_contada, $cantidad_sistema, $cantidad_venta, 
						$precio_venta, $precio_costo, $diferencia, $diferencia_pv, $diferencia_pc, 
						$descripcion, $idusu, $iddeposito)
						";
                        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    } // if(trim($fila[1]) != ''){
                } // if($i > 1){
                $i++;
            } // foreach($array_res as $fila){


            // redireccionar
            header("location: conteo_stock_contar.php?id=$idconteo");
            exit;

        }
    } else {
        header("location: conteo_importar.php?id=$idconteo&cargado=n&status=".$status);
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
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Importar Conteo de Stock</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  
<div class="btn-group">
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='conteo_stock_contar.php?id=<?php echo $idconteo ?>'"><span class="fa fa-reply"></span> Volver</button>

<br /> 
</div>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>



<form action="conteo_importar.php?id=<?php echo $idconteo; ?>" method="post" enctype="multipart/form-data" name="form1" id="form1">



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
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='conteo_stock_contar.php?id=<?php echo $idconteo ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

        <input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
 </form>

<p>&nbsp;</p>
<div class="alert alert-warning alert-dismissible fade in" role="alert">

<strong>Aviso:</strong><br />Si ya tiene cantidades cargadas en el conteo se borraran todas al cargar el archivo.
</div>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='conteo_importar_csv.php?id=<?php echo $idconteo; ?>'"><span class="fa fa-download"></span> Descargar Formato CSV Ejemplo</button><br />
<br />
<strong>Paso 2:</strong><br />
Completar el excel sin agregar ni sacar columnas.
<strong style="color:#F00">NO MODIFICAR</strong> las columnas de Codigo y/o Descripcion, <br />
<strong style="color:#060;">SOLAMENTE MODIFICAR</strong> "Cantidad Pedido" ninguna otra columna.<br />
En caso que modifique otra columna podria ocasionar errores en el pedido de otros productos y el cambio sera irreversible (No se podra solucionar).<br />
<br />
<strong>Paso 3:</strong><br />
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
        
        <!-- POPUP DE MODAL OCULTO -->
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
						...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
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