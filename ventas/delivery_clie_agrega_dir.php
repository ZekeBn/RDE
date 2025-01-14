<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "129";
require_once("includes/rsusuario.php");

$idclientedel = intval($_GET['id']);
if ($idclientedel == 0) {
    header("location: delivery_pedidos.php");
    exit;
}
$consulta = "select usa_lista_zonas from preferencias";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$usarzonadelivery = trim($rspref->fields['usa_lista_zonas']);



$consulta = "
select gmaps_apikey, lat_defecto, lng_defecto, zoom , ultactu_maps_js, usa_maps
from preferencias_caja 
limit 1;
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$lng_defecto = $rsprefcaj->fields['lng_defecto'];
$lat_defecto = $rsprefcaj->fields['lat_defecto'];
$gmaps_apikey = $rsprefcaj->fields['gmaps_apikey'];
$zoom = $rsprefcaj->fields['zoom'];
$ultactu_maps_js = date("YmdHis", strtotime($rsprefcaj->fields['ultactu_maps_js']));
$usa_maps = trim($rsprefcaj->fields['usa_maps']);
if ($usarzonadelivery != 'S') {
    $usa_maps = "N";
}

// busca clientes
$consulta = "
select * 
from cliente_delivery
where
idclientedel = $idclientedel
limit 1
";
$rscab_old = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idclientedel = intval($rscab_old->fields['idclientedel']);
if ($idclientedel == 0) {
    header("location: delivery_pedidos.php");
    exit;
}



if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {


    // recibe parametros dom
    $iddomicilio = antisqlinyeccion($_POST['iddomicilio'], "int");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $referencia = antisqlinyeccion($_POST['referencia'], "text");
    $nombre_domicilio = antisqlinyeccion($_POST['nombre_domicilio'], "text");
    $ruc = antisqlinyeccion($_POST['ruc'], "text");
    $razon_social = antisqlinyeccion($_POST['razon_social'], "text");
    $idzonadel = intval($_POST['zonad']);
    $idsucursal_sugiere = antisqlinyeccion($_POST['idsucursal_sugiere'], "int");
    $url_maps = antisqlinyeccion($_POST['url_maps'], "textbox");

    // validaciones basicas
    $valido = "S";
    $errores = "";

    // validaciones dom
    if (trim($_POST['direccion']) == '') {
        $valido = "N";
        $errores .= " - El campo direccion no puede estar vacio.<br />";
    }
    if (trim($_POST['nombre_domicilio']) == '') {
        $valido = "N";
        $errores .= " - El campo nombre_domicilio no puede estar vacio.<br />";
    }

    // si todo es correcto inserta
    if ($valido == "S") {
        //cliente delivery domicilio
        $consulta = "
		insert into cliente_delivery_dom
		(direccion, referencia, nombre_domicilio, idclientedel, creado_el, creado_por, ultactu_el, ultactu_por, idempresa,idzonadel, idsucursal_sugiere, url_maps)
		values
		($direccion, $referencia, $nombre_domicilio, $idclientedel, '$ahora', $idusu, '$ahora', $idusu, $idempresa,$idzonadel, $idsucursal_sugiere, $url_maps)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: delivery_pedidos_dir.php?id=$idclientedel");
        exit;

    }

}

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
	<?php require_once("includes/head_gen.php"); ?>
<?php if ($usa_maps == 'S') {    ?>
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
	$('#zonad').val(valor);
}
function asigna_latlon(valor){
	var latlon =valor.split(',');
	var latitud = latlon[0].trim();
	var longitud = latlon[1].trim();
	$("#latitud").val(latitud);
	$("#longitud").val(longitud);
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
                    <h2>Agregar Direccion</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
              <thead>
                <tr>

                  <th >Nombre y Apellido</th>
                  <th >Telefono</th>
                </tr>
              </thead>
              <tbody>
                <?php while (!$rscab_old->EOF) {
                    $idclientedel = $rscab_old->fields['idclientedel'];

                    ?>
                <tr>

                  <td align="left"><?php echo $rscab_old->fields['nombres']; ?> <?php echo $rscab_old->fields['apellidos']; ?></td>
                   <td align="center">0<?php echo $rscab_old->fields['telefono']; ?></td>
                </tr>
                <?php $rscab_old->MoveNext();
                } ?>
              </tbody>
            </table>
</div>


<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Lugar *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre_domicilio" id="nombre_domicilio" value="<?php  if (isset($_POST['nombre_domicilio'])) {
	    echo htmlentities($_POST['nombre_domicilio']);
	} else {
	    echo htmlentities($rs->fields['nombre_domicilio']);
	}?>" placeholder="Casa, Trabajo, etc" class="form-control" required />                    
	</div>
</div>



<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
	    echo htmlentities($_POST['direccion']);
	} else {
	    echo htmlentities($rs->fields['direccion']);
	}?>" placeholder="Direccion" class="form-control" required />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Referencia </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="referencia" id="referencia" value="<?php  if (isset($_POST['referencia'])) {
	    echo htmlentities($_POST['referencia']);
	} else {
	    echo htmlentities($rs->fields['referencia']);
	}?>" placeholder="Referencia" class="form-control"  />                    
	</div>
</div>
<?php if ($usarzonadelivery == 'S') {

    ?>
    <div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Asignar Zona </label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
<?php
// consulta
$consulta = "
Select idzonadel, CONCAT(describezona,' | ',COALESCE(obs,'')) as zona
from zonas_delivery 
where 
estado=1 
order by  describezona asc
 ";

    // valor seleccionado
    if (isset($_POST['zonad'])) {
        $value_selected = htmlentities($_POST['zonad']);
    } else {
        $value_selected = htmlentities($rs->fields['idzonadel']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'zonad',
        'id_campo' => 'zonad',

        'nombre_campo_bd' => 'zona',
        'id_campo_bd' => 'idzonadel',

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


<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal Sugerida </label>
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
    if (isset($_POST['idsucursal_sugiere'])) {
        $value_selected = htmlentities($_POST['idsucursal_sugiere']);
    } else {
        $value_selected = htmlentities($rscab_old->fields['idsucursal_sugiere']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idsucursal_sugiere',
        'id_campo' => 'idsucursal_sugiere',

        'nombre_campo_bd' => 'nombre',
        'id_campo_bd' => 'idsucu',

        'value_selected' => $value_selected,

        'pricampo_name' => 'NINGUNA',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' ',
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);
    ?>
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">URL maps </label>
	<div class="col-md-9 col-sm-9 col-xs-12">    
        <input type="text" name="url_maps" id="url_maps" value="<?php  if (isset($_POST['url_maps'])) {
            echo htmlentities($_POST['url_maps']);
        } else {
            echo htmlentities($rs->fields['url_maps']);
        }?>" placeholder="url maps"  class="form-control"  />
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Latitud,Longitud </label>
	<div class="col-md-9 col-sm-9 col-xs-12">    
        <input type="text" name="latlon" id="latlon" value="" placeholder="Latitud, Longitud"  class="form-control" onChange="asigna_latlon(this.value);"  />
	</div>
</div>
	

	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Latitud (GPS) </label>
	<div class="col-md-9 col-sm-9 col-xs-12">    
        <input type="text" name="latitud" id="latitud" value="<?php  if (isset($_POST['latitud'])) {
            echo htmlentities($_POST['latitud']);
        } else {
            echo htmlentities($rs->fields['latitud']);
        }?>" placeholder="latitud"  class="form-control" readonly />
	</div>
</div>



<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Longitud (GPS) </label>
	<div class="col-md-9 col-sm-9 col-xs-12">   
        <input type="text" name="longitud" id="longitud" value="<?php  if (isset($_POST['longitud'])) {
            echo htmlentities($_POST['longitud']);
        } else {
            echo htmlentities($rs->fields['longitud']);
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
<br /><br /><br />





    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='delivery_pedidos_dir.php?id=<?php echo $idclientedel ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>


<div class="clearfix"></div>
<br /><br />

<?php if ($usa_maps == 'S') {    ?>
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
<?php if ($usa_maps == 'S') {    ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $gmaps_apikey; ?>&libraries=places,drawing,geometry,places&v=weekly"></script>
<script>
<?php
$lat_defecto = $rsprefcaj->fields['lat_defecto'];
    $lng_defecto = $rsprefcaj->fields['lng_defecto'];
    $zoom = $rsprefcaj->fields['zoom'];
    if (trim($rscab_old->fields['latitud']) == '') {
        $lat_cliente = $lat_defecto;
        $lng_cliente = $lng_defecto;
    } else {
        $lat_cliente = $rscab_old->fields['latitud'];
        $lng_cliente = $rscab_old->fields['longitud'];
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
