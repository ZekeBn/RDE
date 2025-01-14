 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "342";
require_once("includes/rsusuario.php");




$idsucu = intval($_GET['id']);
if ($idsucu == 0) {
    header("location: sucursales.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from sucursales 
where 
idsucu = $idsucu
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucu = intval($rs->fields['idsucu']);
if ($idsucu == 0) {
    header("location: sucursales.php");
    exit;
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

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


    // recibe parametros
    //$idsucu=antisqlinyeccion($_POST['idsucu'],"float");
    $idempresa = antisqlinyeccion(1, "float");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $punto_expedicion = antisqlinyeccion($_POST['punto_expedicion'], "float");
    $estado = antisqlinyeccion(1, "float");
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $telefono = antisqlinyeccion($_POST['telefono'], "text");
    $responsable = antisqlinyeccion($_POST['responsable'], "text");
    $wifi = antisqlinyeccion($_POST['wifi'], "text");
    $matriz = antisqlinyeccion($_POST['matriz'], "text");
    $punto_equilibrio = antisqlinyeccion($_POST['punto_equilibrio'], "float");
    $latitud = antisqlinyeccion($_POST['latitud'], "text");
    $longitud = antisqlinyeccion($_POST['longitud'], "text");
    $fantasia_sucursal = antisqlinyeccion($_POST['fantasia_sucursal'], "text");
    $razon_social_sucursal = antisqlinyeccion($_POST['razon_social_sucursal'], "text");
    $ruc_sucursal = antisqlinyeccion($_POST['ruc_sucursal'], "text");
    $actividad_sucursal = antisqlinyeccion($_POST['actividad_sucursal'], "text");
    $arrastre_caja_suc = antisqlinyeccion($_POST['arrastre_caja_suc'], "text");
    $imprime_directo_suc = antisqlinyeccion($_POST['imprime_directo_suc'], "text");
    $idtipodesc_depo = antisqlinyeccion($_POST['idtipodesc_depo'], "int");
    $modo_kg = antisqlinyeccion($_POST['modo_kg'], "text");
    $idprod_kg = antisqlinyeccion($_POST['idprod_kg'], "int");
    $integra_pos_suc = antisqlinyeccion($_POST['integra_pos_suc'], "text");
    $muestra_tomapedido = antisqlinyeccion($_POST['muestra_tomapedido'], "text");

    if (trim($_POST['nombre']) == '') {
        $valido = "N";
        $errores .= " - El campo nombre sucursal no puede estar vacio.<br />";
    }
    if (intval($_POST['idtipodesc_depo']) == 0) {
        $valido = "N";
        $errores .= " - No indico el Comportamiento del Deposito en Ventas.<br />";
    }
    // si se activa el modo kg
    if (trim($_POST['modo_kg']) == 'S') {
        if (intval($_POST['idprod_kg']) <= 0) {
            $valido = "N";
            $errores .= " - Debe indicar el producto cuando se activa el modo venta por KG.<br />";
        }
    }


    $consulta = "
    select * from sucursales where nombre = $nombre and idsucu <> $idsucu and estado = 1 limit 1
    ";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idsucu'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe otra sucursal con el mismo nombre.<br />";
    }

    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        update sucursales
        set
            direccion=$direccion,
            nombre=$nombre,
            telefono=$telefono,
            wifi=$wifi,
            lat_defecto=$latitud,
            lng_defecto=$longitud,
            fantasia_sucursal=$fantasia_sucursal,
            razon_social_sucursal=$razon_social_sucursal,
            ruc_sucursal=$ruc_sucursal,
            actividad_sucursal=$actividad_sucursal,
            arrastre_caja_suc=$arrastre_caja_suc,
            imprime_directo_suc=$imprime_directo_suc,
            idtipodesc_depo=$idtipodesc_depo,
            modo_kg=$modo_kg,
            idprod_kg=$idprod_kg,
            integra_pos_suc=$integra_pos_suc,
            muestra_tomapedido=$muestra_tomapedido
        where
            idsucu = $idsucu
            and estado = 1
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: sucursales.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




$consulta = "select usa_lista_zonas from preferencias";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usarzonadelivery = trim($rspref->fields['usa_lista_zonas']);

// AIzaSyCpPoaeqAUHAJ0X8OXb4vey2wWy2bTSCQU ekaru
// AIzaSyDCRfkUJQw3bE0t5u9iMrMghtTl7nuBdO4 miguel
$consulta = "
select gmaps_apikey, lat_defecto, lng_defecto, zoom, ultactu_maps_js, usa_maps, multi_razonsocial_suc
from preferencias_caja 
limit 1;
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$lng_defecto = $rsprefcaj->fields['lng_defecto'];
$lat_defecto = $rsprefcaj->fields['lat_defecto'];
$gmaps_apikey = $rsprefcaj->fields['gmaps_apikey'];
$zoom = $rsprefcaj->fields['zoom'];
$multi_razonsocial_suc = $rsprefcaj->fields['multi_razonsocial_suc'];
$ultactu_maps_js = date("YmdHis", strtotime($rsprefcaj->fields['ultactu_maps_js']));
$usa_maps = trim($rsprefcaj->fields['usa_maps']);
if ($usarzonadelivery != 'S') {
    $usa_maps = "N";
}

?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<?php if ($usa_maps == "S") { ?>
        <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <link rel="stylesheet" type="text/css" href="css/style.css?nc=<?php echo $ultactu_maps_js;  ?>" />
<script>
function coordenadas() {
    fetch('maps.php')
        .then(res => {
            if (res.ok) {
                return res.json();
            } else {
                console.log('error');
            }
        })
        .then(data => {
            if (data.status == 'success') {
                initMap(true, data);
            } else {
                initMap(false);
            }
        });
}
function marcar(valor){
    //$('#zonad option[value="'+valor+'"]').attr("selected", "selected");
    //$('#zonad').val(valor);
}
</script>
<?php } ?>
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
                    <h2>Editar Sucursal</h2>
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
<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre Sucursal *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
        echo htmlentities($_POST['nombre']);
    } else {
        echo htmlentities($rs->fields['nombre']);
    }?>" placeholder="Nombre" class="form-control" required  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fantasia Factura </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="fantasia_sucursal" id="fantasia_sucursal" value="<?php  if (isset($_POST['fantasia_sucursal'])) {
        echo htmlentities($_POST['fantasia_sucursal']);
    } else {
        echo htmlentities($rs->fields['fantasia_sucursal']);
    }?>" placeholder="Fantasia en Factura (debe activarse en preferencias)" class="form-control"  />                    
    </div>
</div>
<?php if ($multi_razonsocial_suc == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social sucursal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="razon_social_sucursal" id="razon_social_sucursal" value="<?php  if (isset($_POST['razon_social_sucursal'])) {
        echo htmlentities($_POST['razon_social_sucursal']);
    } else {
        echo htmlentities($rs->fields['razon_social_sucursal']);
    }?>" placeholder="Razon social sucursal" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc sucursal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="ruc_sucursal" id="ruc_sucursal" value="<?php  if (isset($_POST['ruc_sucursal'])) {
        echo htmlentities($_POST['ruc_sucursal']);
    } else {
        echo htmlentities($rs->fields['ruc_sucursal']);
    }?>" placeholder="Ruc sucursal" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Actividad sucursal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="actividad_sucursal" id="actividad_sucursal" value="<?php  if (isset($_POST['actividad_sucursal'])) {
        echo htmlentities($_POST['actividad_sucursal']);
    } else {
        echo htmlentities($rs->fields['actividad_sucursal']);
    }?>" placeholder="Actividad sucursal" class="form-control"  />                    
    </div>
</div>
<?php } ?>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
        echo htmlentities($_POST['direccion']);
    } else {
        echo htmlentities($rs->fields['direccion']);
    }?>" placeholder="Direccion" class="form-control"  />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
        echo htmlentities($_POST['telefono']);
    } else {
        echo htmlentities($rs->fields['telefono']);
    }?>" placeholder="Telefono" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Wifi </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="wifi" id="wifi" value="<?php  if (isset($_POST['wifi'])) {
        echo htmlentities($_POST['wifi']);
    } else {
        echo htmlentities($rs->fields['wifi']);
    }?>" placeholder="Wifi" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Comportamiento Deposito Ventas *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
       
        
<?php
// consulta
$consulta = "
SELECT idtipodesc_depo, tipo_descuento_deposito
FROM tipo_descuento_deposito
where
estado = 1
order by tipo_descuento_deposito asc
 ";

// valor seleccionado
if (isset($_POST['idtipodesc_depo'])) {
    $value_selected = htmlentities($_POST['idtipodesc_depo']);
} else {
    $value_selected = htmlentities($rs->fields['idtipodesc_depo']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipodesc_depo',
    'id_campo' => 'idtipodesc_depo',

    'nombre_campo_bd' => 'tipo_descuento_deposito',
    'id_campo_bd' => 'idtipodesc_depo',

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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Arrastre saldo caja *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">         
<?php

// valor seleccionado
if (isset($_POST['arrastre_caja_suc'])) {
    $value_selected = htmlentities($_POST['arrastre_caja_suc']);
} else {
    $value_selected = $rs->fields['arrastre_caja_suc'];
}
// opciones
$opciones = [
    'Predeterminado' => 'DEF',
    'Activado' => 'ACT',
    'Desactivado' => 'INA'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'arrastre_caja_suc',
    'id_campo' => 'arrastre_caja_suc',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);

?>
        
    </div> 
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Imprime directo en mesa *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['imprime_directo_suc'])) {
    $value_selected = htmlentities($_POST['imprime_directo_suc']);
} else {
    $value_selected = $rs->fields['imprime_directo_suc'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'imprime_directo_suc',
    'id_campo' => 'imprime_directo_suc',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>    
    </div>
</div>
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Modo Venta por KG Rapida *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['modo_kg'])) {
    $value_selected = htmlentities($_POST['modo_kg']);
} else {
    $value_selected = $rs->fields['modo_kg'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'modo_kg',
    'id_campo' => 'modo_kg',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>    
    </div>
</div>
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Producto Venta por KG Rapida </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idprod_serial, descripcion
FROM productos
where
borrado = 'N'
and idmedida = 2
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idprod_kg'])) {
    $value_selected = htmlentities($_POST['idprod_kg']);
} else {
    $value_selected = htmlentities($rs->fields['idprod_kg']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idprod_kg',
    'id_campo' => 'idprod_kg',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idprod_serial',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);


?>    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Integra POS *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['integra_pos_suc'])) {
    $value_selected = htmlentities($_POST['integra_pos_suc']);
} else {
    $value_selected = $rs->fields['integra_pos_suc'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'integra_pos_suc',
    'id_campo' => 'integra_pos_suc',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>    
    </div>
</div>
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar en Toma de Pedidos *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['muestra_tomapedido'])) {
    $value_selected = htmlentities($_POST['muestra_tomapedido']);
} else {
    $value_selected = $rs->fields['muestra_tomapedido'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'muestra_tomapedido',
    'id_campo' => 'muestra_tomapedido',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>    
    </div>
</div>
    



<?php if ($usarzonadelivery == 'S') { ?>
<div class="clearfix"></div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Latitud (GPS) </label>
    <div class="col-md-9 col-sm-9 col-xs-12">    
        <input type="text" name="latitud" id="latitud" value="<?php  if (isset($_POST['latitud'])) {
            echo htmlentities($_POST['latitud']);
        } else {
            echo htmlentities($rs->fields['lat_defecto']);
        }?>" placeholder="latitud"  class="form-control" readonly />
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Longitud (GPS) </label>
    <div class="col-md-9 col-sm-9 col-xs-12">   
        <input type="text" name="longitud" id="longitud" value="<?php  if (isset($_POST['longitud'])) {
            echo htmlentities($_POST['longitud']);
        } else {
            echo htmlentities($rs->fields['lng_defecto']);
        }?>" placeholder="longitud"  class="form-control" readonly />
    </div>
</div>




    
    <?php }?>




<textarea name="coordinates" id="coordinates" cols="70" rows="3" style="width:100%; display:none;"><?php  if (isset($_POST['coordinates'])) {
    echo htmlentities($_POST['coordinates']);
} else {
    echo htmlentities($rs->fields['coordenadas']);
}?></textarea>



<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='sucursales.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>

<div class="clearfix"></div>
<br /><br />
<?php if ($usarzonadelivery == "S") { ?>
<strong>Indicar Coordenadas: </strong><br />
<br />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"></label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input
      id="pac-input"
      class="form-control"
      type="text"
      placeholder="Buscar calle..."
    />
    </div>
</div>


    <div id="map-container">
        <div id="map"></div>
    </div>
<?php } ?>

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
<?php if ($usa_maps == "S") { ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $gmaps_apikey; ?>&libraries=places,drawing,geometry,places&v=weekly"></script>
<script>
<?php
$lat_defecto = $rsprefcaj->fields['lat_defecto'];
    $lng_defecto = $rsprefcaj->fields['lng_defecto'];
    $zoom = $rsprefcaj->fields['zoom'];
    if (trim($rs->fields['lat_defecto']) == '') {
        $lat_cliente = $lat_defecto;
        $lng_cliente = $lng_defecto;
    } else {
        // de la sucursal
        $lat_cliente = $rs->fields['lat_defecto'];
        $lng_cliente = $rs->fields['lng_defecto'];
    }
    ?>
var latCliente = <?php echo $lat_cliente; ?>;
var lngCliente = <?php echo $lng_cliente; ?>;
latLng = new google.maps.LatLng(latCliente, lngCliente);
const map = new google.maps.Map(document.getElementById("map"), {
    zoom: <?php echo $zoom;  ?>,
    center: { lat: latCliente, lng: lngCliente },
    mapTypeId: "terrain",
});
//latLng = new google.maps.LatLng(-25.282197, -57.635099999999966);
</script>
<script src="js/asignar.js?nc=<?php echo $ultactu_maps_js;  ?>"></script>
<?php } ?>
  </body>
</html>
