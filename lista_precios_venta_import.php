 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "322";
require_once("includes/rsusuario.php");


// archivos
require_once("includes/upload.php");
require_once("includes/funcion_upload.php");
//set_time_limit(120);


// automentar el timpo de ejecucion del script
set_time_limit(0);

// para mostrar errores por ejemplo el de falta de memoria en vez de mostarr un error 500 y no saber el problema
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);



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
    $nombrearchivo = 'precio_'.$tiempo.'.'.$extension;
    $nombrearchivo2 = 'precio_'.$tiempo;

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
                $idproductolista = intval($fila[1]);
                $idproducto = intval($fila[2]);
                $idsucursal = intval($fila[7]);
                $idlistaprecio = intval($fila[9]);
                $precio_nuevo = floatval($fila[13]);



                //$producto=htmlentities($fila[3]);


                //echo $cantidad;
                //1 idproductolista    2codigo_producto    3codigo_barras    4producto    5categoria    6subcategoria 7idssucursal    8sucursal    9idlistaprecio    10lista_precio    11precio_actual    12precio_nuevo
                //echo $idproductolista;exit;
                if (trim($fila[13]) == '') {
                    $valido = "N";
                    $errores .= " - El campo precio nuevo no puede estar vacio. Linea: $i, Id: $idproductolista.<br />";
                }
                if (floatval($precio_nuevo) < 0) {
                    $valido = "N";
                    $errores .= " - El campo precio nuevo no puede ser negativo. Linea: $i, Id: $idproductolista.<br />";
                }
                if (intval($idproductolista) <= 0) {
                    $valido = "N";
                    $errores .= " - El campo idproductolista no puede estar vacio. Linea: $i, Id: $idproductolista.<br />";
                }
                if (intval($idproducto) == 0) {
                    $valido = "N";
                    $errores .= " - El campo idproducto no puede estar vacio. Linea: $i, Id: $idproductolista.<br />";
                }
                if (intval($idlistaprecio) == 0) {
                    $valido = "N";
                    $errores .= " - El campo idlistaprecio no puede estar vacio. Linea: $i, Id: $idproductolista.<br />";
                }
                if (intval($idsucursal) == 0) {
                    $valido = "N";
                    $errores .= " - El campo idsucursal no puede estar vacio. Linea: $i, Id: $idproductolista.<br />";
                }



                $consulta = "
                select idproductolista, idproducto, idlistaprecio, idsucursal
                from productos_listaprecios 
                where 
                idproductolista = $idproductolista 
                and estado = 1
                limit 1
                ";
                $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idproducto_bd = intval($rs->fields['idproducto']);
                $idlistaprecio_bd = intval($rs->fields['idlistaprecio']);
                $idsucursal_bd = intval($rs->fields['idsucursal']);
                if (intval($rs->fields['idproductolista']) == 0) {
                    $valido = "N";
                    $errores = "- La lista de precios que intentas actualizar no existe. Linea: $i, Id: $idproductolista.<br />";
                }
                if ($idproducto_bd <> $idproducto) {
                    $valido = "N";
                    $errores = "- El producto [$idproducto] no corresponde al idproductolista. Linea: $i, Id: $idproductolista.<br />";
                }
                if ($idlistaprecio_bd <> $idlistaprecio) {
                    $valido = "N";
                    $errores = "- La Lista de precio [$idlistaprecio] no corresponde al idproductolista. Linea: $i, Id: $idproductolista.<br />";
                }
                if ($idsucursal_bd <> $idsucursal) {
                    $valido = "N";
                    $errores = "- La Sucursal [$idsucursal] no corresponde al idproductolista, debia ser sucusal [$idsucursal_bd]. Linea: $i, Id: $idproductolista.<br />";
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
            INSERT INTO productos_listaprecios_import_cab
            (registrado_por,registrado_el) 
            VALUES 
            ($idusu,'$ahora')
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // busca el id creado
            $consulta = "
            select idproductoimpcab from productos_listaprecios_import_cab where registrado_por = $idusu order by registrado_el desc limit 1 
            ";
            $rsprpodcab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idproductoimpcab = $rsprpodcab->fields['idproductoimpcab'];
            $i = 1;
            foreach ($array_res as $fila) {
                // la cabecera se salta
                if ($i > 1) {
                    $idproductolista = intval($fila[1]);
                    $idproducto = intval($fila[2]);
                    $idsucursal = intval($fila[7]);
                    $idlistaprecio = intval($fila[9]);
                    $precio_nuevo = floatval($fila[13]);


                    $consulta = "
                    insert into productos_listaprecios_import
                    (idproductoimpcab, idproductolista, idlistaprecio, idproducto, idsucursal, estado, precio_anterior, precio, reg_por, reg_el)
                    values
                    ($idproductoimpcab, $idproductolista, $idlistaprecio, $idproducto, $idsucursal, 1, 0, '$precio_nuevo', $idusu, '$ahora')
                    ";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                } // if($i > 1){

                $i++;
            }

            // actualiza precio viejo
            $consulta = "
            update productos_listaprecios_import
            set
            precio_anterior = COALESCE((
                                SELECT precio 
                                from productos_listaprecios 
                                where 
                                productos_listaprecios.idproductolista = productos_listaprecios_import.idproductolista
                            ),0)
            WHERE
            idproductoimpcab = $idproductoimpcab
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // redireccionar
            header("location: lista_precios_venta_import_control.php?id=$idproductoimpcab");
            exit;

        } // if($valido == 'S'){

    } else { // if($cargado){
        header("location: lista_precios_venta_import_control.php?cargado=n&status=".$status);
        exit;
    } // if($cargado){


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
<script>
//lista_precios_venta_csv.php    lista_precios_venta
function descargar_lista(){
    var idlistaprecio = $("#idlistaprecio").val();
    var idsucu = $("#idsucu").val();
    document.location.href='lista_precios_venta_csv.php?id='+idlistaprecio+'&idsucu='+idsucu;
}
</script>
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
            
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Importar precios nuevos de Excel</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  
<div class="btn-group">
<button class="btn btn-sm btn-default" type="button" 
onmouseup="document.location.href='lista_precios_venta.php'"><span class="fa fa-reply"></span> Volver</button>

<br /> 
</div>
<hr />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">

<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>



<form action="lista_precios_venta_import.php" method="post" enctype="multipart/form-data" name="form1" id="form1">



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
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='lista_precios_venta.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

        <input type="hidden" name="MM_upload" id="MM_upload" value="form1" /></td>
 </form>

<p>&nbsp;</p>
<hr />
<h2>Instrucciones:</h2><br />
<br />
<strong>Paso 1:</strong><br />
            <br />        
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Lista Precio *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">

<?php
// consulta
$consulta = "
SELECT idlistaprecio, lista_precio
FROM lista_precios_venta
where
estado = 1
and recargo_porc = 0
and idlistaprecio > 1
order by lista_precio asc
 ";

// valor seleccionado
if (isset($_POST['idlistaprecio'])) {
    $value_selected = htmlentities($_POST['idlistaprecio']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idlistaprecio',
    'id_campo' => 'idlistaprecio',

    'nombre_campo_bd' => 'lista_precio',
    'id_campo_bd' => 'idlistaprecio',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>                   
    </div>
</div>
                    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">

<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idsucu'])) {
    $value_selected = htmlentities($_POST['idsucu']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>                   
    </div>
</div>
<div class="clearfix"></div>
<br />
                    
<button class="btn btn-sm btn-default" type="button" 
onmouseup="descargar_lista();"><span class="fa fa-download"></span> Descargar Formato CSV Ejemplo</button><br />
<br />
<strong>Paso 2:</strong><br />
Completar el excel sin agregar ni sacar columnas.
<strong style="color:#F00">NO MODIFICAR</strong> las columnas de Codigo y/o Descripcion, <br />
<strong style="color:#060;">SOLAMENTE MODIFICAR</strong> "precio nuevo" ninguna otra columna.<br />
En caso que modifique otra columna podria ocasionar que cambien los precios de otros productos y el cambio sera irreversible (No se podra solucionar).<br />
<br />
<strong>Paso 3:</strong><br />
Cargar aqui el archivo excel con los nuevos precios.
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
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
