 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "21";
$submodulo = "414";
require_once("includes/rsusuario.php");



function contiene_cadena($texto, $busqueda)
{
    if (strlen(stristr($texto, $busqueda)) > 0) {
        return true;
    } else {
        return false;
    }
}
function dmy_to_ymd($fecha)
{
    $fecha = trim($fecha);
    $fecarpv = explode('/', $fecha);
    $diapv = $fecarpv[0];
    $mespv = $fecarpv[1];
    $anopv = $fecarpv[2];
    $fecha = $anopv.'-'.$mespv.'-'.$diapv;
    return $fecha;
}


if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // control de formularios, seguridad para evitar doble envio y ataques via bots
    if ($_SESSION['form_control'] != $_POST['form_control']) {
        $errores .= "- Se detecto un intento de envio doble, recargue la pagina.<br />";
        $valido = "N";
    }
    if (trim($_POST['form_control']) == '') {
        $errores .= "- Control del formularios no activado.<br />";
        $valido = "N";
    }
    $_SESSION['form_control'] = md5(rand());
    // control de formularios, seguridad para evitar doble envio y ataques via bots

    // permitidos
    $permitidos_mime = ["text/plain","application/vnd.ms-excel"];

    // recibe parametros
    //$archivo=antisqlinyeccion($_POST['archivo'],"text");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $archivo = $_FILES['archivo'];
    $archivo_mime = mime_content_type($archivo['tmp_name']);
    $archivo_ext = strtolower(substr($archivo['name'], strrpos($archivo['name'], '.') + 1));
    $contenido_archivo = file_get_contents($archivo['tmp_name']);
    $nombre_archivo = antisqlinyeccion($archivo['name'], "textbox");
    $idsucursal = antisqlinyeccion($_POST['idsucu'], "int");
    //print_r($_FILES);


    if (trim($archivo['name']) == '') {
        $valido = "N";
        $errores .= " - El campo archivo no puede estar vacio.<br />";
    }

    if (intval($_POST['idsucu']) == 0) {
        $valido = "N";
        $errores .= " - El campo sucursal no puede ser cero o nulo.<br />";
    }
    if (!in_array($archivo_mime, $permitidos_mime)) {
        $archivo_mime_txt = htmlentities($archivo_mime);
        $valido = "N";
        $errores .= " - Tipo de archivo no permitido ($archivo_mime_txt), debe ser CSV.<br />";
    }
    $archivo_mime = mime_content_type($archivo['tmp_name']);
    if ($archivo_ext != 'csv') {
        $archivo_ext_txt = htmlentities($archivo_ext);
        $valido = "N";
        $errores .= " - Extension archivo no permitido ($archivo_ext_txt), debe ser CSV.<br />";
    }
    if (trim($contenido_archivo) == '') {
        $valido = "N";
        $errores .= " - El archivo enviado esta vacio.<br />";
    }
    // si todo es valido, valida mas cosas del archivo
    $separador = "\n".str_repeat("=", 100)."\n";
    $datalog = "valido=$valido";

    if ($valido == "S") {
        // mete todo en un array
        $array_glob = [];
        $filas = explode("<br />", nl2br(trim($contenido_archivo)));
        $ifila = 0;
        //print_r($filas);
        foreach ($filas as $fila) {
            if ($ifila > 0) {
                if (trim($fila) != ';;;;' && trim($fila) != '|;;;;') {
                    $columnas = explode(";", $fila);
                    //print_r($columnas);
                    $fechahora = trim($columnas[2]);
                    // conversiones de fechas
                    if (contiene_cadena($fechahora, "/")) {
                        $fechahora = dmy_to_ymd($fechahora);
                    }
                    //echo '-'.$fechahora."-";

                    $array_glob[$ifila]['ruc'] = str_replace(".", "", trim($columnas[0]));
                    $array_glob[$ifila]['razon_social'] = utf8_encode(trim($columnas[1]));
                    $array_glob[$ifila]['fechahora'] = $fechahora;
                    $array_glob[$ifila]['productos'] = trim($columnas[3]);
                }
            }
            $ifila++;
        }
        //print_r($array_glob);
        // valida datos
        $fila = 1;
        foreach ($array_glob as $pedido_cab) {
            if (trim($pedido_cab['ruc']) == '') {
                $valido = "N";
                $errores .= " - El ruc en la fila ($fila) no puede estar vacio.<br />";
            }
            if (trim($pedido_cab['razon_social']) == '') {
                $valido = "N";
                $errores .= " - La razon social en la fila ($fila) no puede estar vacio.<br />";
            }
            if (trim($pedido_cab['fechahora']) == '') {
                $valido = "N";
                $errores .= " - La fecha hora en la fila ($fila) no puede estar vacio.<br />";
            }
            if (trim($pedido_cab['productos']) == '') {
                $valido = "N";
                $errores .= " - Los productos en la fila ($fila) no se encontraron.<br />";
            }
            // validaciones de BD
            //if($valido == "S"){
            $ruc = antisqlinyeccion(trim($pedido_cab['ruc']), "text");
            $consulta = "
                select idcliente from cliente where ruc = $ruc and estado <> 6 limit 1
                ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $ruc_txt = htmlentities($pedido_cab['ruc']);
            if (intval($rsex->fields['idcliente']) == 0) {
                $valido = "N";
                $errores .= " - Cliente con Ruc ($ruc_txt) no existe, en la fila ($fila).<br />";
            }
            $productos = explode(",", trim($pedido_cab['productos']));
            // print_r($productos);
            // exit;
            foreach ($productos as $producto) {
                $idinsumo = intval($producto);
                $consulta = "
                    select idproducto from insumos_lista where idinsumo = $idinsumo and estado <> 6 limit 1
                    ";
                $datalog .= "consulta=$consulta $separador";
                $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $producto = intval($rsex->fields['idproducto']);
                $idproducto = intval($producto);
                $consulta = "
                    select idprod_serial
                    from productos 
                    where 
                    idprod_serial = $producto 
                    and borrado = 'N' 
                    and idtipoproducto = 1
                    ";
                $datalog .= "consulta=$consulta $separador";
                $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idprod_serial = intval($rsex->fields['idprod_serial']);
                if (intval($idprod_serial) == 0) {
                    $valido = "N";
                    $errores .= " - El producto ($idinsumo) no existe, en la fila ($fila).<br />";
                }

            }

            //}


            $fila++;
        }

    }
    grabar_log($datalog, 'i');

    //echo $errores;

    // si todo es correcto inserta
    if ($valido == "S") {


        $consulta = "
        insert into pedidos_masivo_tanda
        (archivo, estado, registrado_por, registrado_el, idsucursal)
        values
        ($nombre_archivo, $estado, $registrado_por, $registrado_el, $idsucursal)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        select idtandamas from pedidos_masivo_tanda where registrado_por = $idusu order by idtandamas desc limit 1
        ";
        $rsult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idtandamas = intval($rsult->fields['idtandamas']);

        reset($array_glob);
        foreach ($array_glob as $pedido_cab) {
            $fechahora = antisqlinyeccion(trim($pedido_cab['fechahora']), "text");
            $ruc = antisqlinyeccion(trim($pedido_cab['ruc']), "text");
            $razon_social = antisqlinyeccion(trim($pedido_cab['razon_social']), "text");
            $consulta = "
            select idcliente from cliente where ruc = $ruc and estado <> 6 limit 1
            ";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idcliente = intval($rsex->fields['idcliente']);

            // inserta cabecera de pedidos
            $consulta = "
            INSERT INTO pedidos_masivo_cab
            (idtandamas, fechahora, idcliente, estado, ruc, razon_social) 
            VALUES 
            ($idtandamas,$fechahora,$idcliente, 1, $ruc, $razon_social)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
            select idpedidomas  from pedidos_masivo_cab where idtandamas = $idtandamas order by idpedidomas desc limit 1
            ";
            $rsult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idpedidomas = intval($rsult->fields['idpedidomas']);

            // inserta detalles de pedidos
            $productos = explode(",", trim($pedido_cab['productos']));
            //print_r($productos);
            foreach ($productos as $producto) {
                $idinsumo = intval($producto);
                $consulta = "
                select idproducto from insumos_lista where idinsumo = $idinsumo and estado <> 6 limit 1
                ";
                $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $producto = intval($rsex->fields['idproducto']);
                $idproducto = intval($producto);
                $consulta = "
                select idprod_serial from productos where idprod_serial = $producto and borrado = 'N'
                ";
                $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idprod_serial = intval($rsex->fields['idprod_serial']);
                $consulta = "
                insert into pedidos_masivo_det
                (idpedidomas, idproducto, precio_unitario, cantidad, subtotal)
                values
                ($idpedidomas, $idprod_serial, 0, 0, 0)
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            }


        }




        header("location: pedidos_masivo_tanda.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

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
                    <h2>Carga Masiva de Pedidos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action=""  enctype="multipart/form-data">

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal </label>
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
    $value_selected = htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="file" name="archivo" id="archivo" value="<?php  if (isset($_POST['archivo'])) {
        echo htmlentities($_POST['archivo']);
    } else {
        echo htmlentities($rs->fields['archivo']);
    }?>" placeholder="Archivo" class="form-control" required accept=".csv" />                    
    </div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='pedidos_masivo_tanda.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<hr />
<div class="clearfix"></div>
<br /><br />
Tipo de Archivo: .CSV (Valores separados por punto y coma)<br />
Formato de Archivo: <br />
<textarea name="" cols="" rows="" style="width:500px; height:150px;">
Ruc;Razon Social;FechaHora;"idprod,idprod,idprod..."
</textarea>
<!--<button type="button" class="btn btn-default" onMouseUp="document.location.href='pedidos_masivo_tanda_desc.php'"><span class="fa fa-download"></span> Descargar Ejemplo</button>-->


<div class="clearfix"></div>
<br /><br />

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
