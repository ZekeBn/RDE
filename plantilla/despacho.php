<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

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


    // recibe parametros
    $descripcion = $_POST['descripcion'];
    $idpais = $_POST['idpais'];
    $idempresa = $_POST['idempresa'];
    $idpto = $_POST['idpto'];
    $idciudad = $_POST['idciudad'];
    $registrado_por = $idusu;

    $registrado_el = $ahora;

    $estado = $_POST['estado'];
    $estado = 1;



    $parametros_array = [
        "descripcion" => $descripcion,
        "idpais" => $idpais,
        "idempresa" => $idempresa,
        "idpto" => $idpto,
        "idciudad" => $idciudad,
        "registrado_por" => $registrado_por,
        "registrado_el" => $registrado_el,
        "borrado_por" => $borrado_por,
        "borrado_el" => $borrado_el,
        "estado" => $estado
    ];

    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = aduana_add($parametros_array);
        if ($res["valido"] == "S") {
            header("location: aduana.php");
            exit;
        } else {
            $errores .= $res["errores"];
        }

    } else {
        $errores .= $res["errores"];
    }


}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

// se puede mover esta funcion al archivo funciones_aduana.php y realizar un require_once
function aduana_add($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    $descripcion = antisqlinyeccion($parametros_array['descripcion'], "text");
    $idpais = antisqlinyeccion($parametros_array['idpais'], "int");
    $idempresa = antisqlinyeccion($parametros_array['idempresa'], "int");
    $idpto = antisqlinyeccion($parametros_array['idpto'], "int");
    $idciudad = antisqlinyeccion($parametros_array['idciudad'], "int");
    $registrado_por = antisqlinyeccion($parametros_array['registrado_por'], "int");
    $registrado_el = antisqlinyeccion($parametros_array['registrado_el'], "text");
    $estado = antisqlinyeccion($parametros_array['estado'], "float");
    $estado = antisqlinyeccion($parametros_array['estado'], "int");

    if (trim($parametros_array['descripcion']) == '') {
        $valido = "N";
        $errores .= " - El campo descripcion no puede estar vacio.<br />";
    }
    if (intval($parametros_array['idpais']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpais no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idempresa']) == 0) {
        $valido = "N";
        $errores .= " - El campo idempresa no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idpto']) == 0) {
        $valido = "N";
        $errores .= " - El campo idpto no puede ser cero o nulo.<br />";
    }
    if (intval($parametros_array['idciudad']) == 0) {
        $valido = "N";
        $errores .= " - El campo idciudad no puede ser cero o nulo.<br />";
    }
    /*
    registrado_por
    */
    /*
    registrado_el
    */
    /*
    borrado_por
    */
    /*
    borrado_el
    */
    /*
    estado
        if(floatval($parametros_array['estado']) <= 0){
            $valido="N";
            $errores.=" - El campo estado no puede ser cero o negativo.<br />";
        }
    */


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into aduana
		(descripcion, idpais, idempresa, idpto, idciudad, registrado_por, registrado_el, estado)
		values
		($descripcion, $idpais, $idempresa, $idpto, $idciudad, $registrado_por, $registrado_el, $estado)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["errores" => $errores,"valido" => $valido];
}




$buscar = "SELECT diasvence,idproveedor, idtipo_origen,nombre ,idmoneda, ruc,tipocompra,idtipo_servicio,dias_entrega
FROM proveedores
where estado = 1
";

$resultados_proveedores = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idproveedor = intval(trim(antixss($rsd->fields['idproveedor'])));
    $dias_entrega = intval(trim(antixss($rsd->fields['dias_entrega'])));
    $idmoneda = intval(trim(antixss($rsd->fields['idmoneda'])));
    $idtipo_origen = intval(trim(antixss($rsd->fields['idtipo_origen'])));
    $idtipo_servicio = intval(trim(antixss($rsd->fields['idtipo_servicio'])));
    $nombre = trim(antixss($rsd->fields['nombre']));
    $tipocompra = intval(trim(antixss($rsd->fields['tipocompra'])));
    $diasvence = floatval($rsd->fields['diasvence']);
    $ruc = trim(antixss($rsd->fields['ruc']));
    $resultados_proveedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-diasvence='$diasvence' data-hidden-value='$ruc' data-hidden-servicio='$idtipo_servicio' data-hidden-entrega='$dias_entrega' onclick=\"cambia_proveedor($idtipo_origen, $idmoneda, $idproveedor, '$nombre',$tipocompra, $dias_entrega,$diasvence);\">[$idproveedor]-$nombre</a>
	";

    $rsd->MoveNext();
}



?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
    function myFunction2(event) {
		event.preventDefault();
		var idtipo_servicio = $("#idtipo_servicio").val();
		if(idtipo_servicio) {
			var div,ul, li, a, i;
			div = document.getElementById("myDropdown2");
			a = div.getElementsByTagName("a");
			for (i = 0; i < a.length; i++) {
				txtValue = a[i].textContent || a[i].innerText;
				idtipo_servicio_hidden = a[i].getAttribute('data-hidden-servicio');
				if ( idtipo_servicio_hidden==idtipo_servicio ) {
					a[i].style.display = "block";
				} else {
					a[i].style.display = "none";
				}
			}
		}else{
			var div,ul, li, a, i;
                    div = document.getElementById("myDropdown2");
                    a = div.getElementsByTagName("a");
                    for (i = 0; i < a.length; i++) {
						a[i].style.display = "block";
                    }
		}
		document.getElementById("myInput2").classList.toggle("show");
		document.getElementById("myDropdown2").classList.toggle("show");
		div = document.getElementById("myDropdown2");
		$("#myInput2").focus();
		

			
		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput2');
			var myDropdown = $('#myDropdown2');
			var div = $("#lista_proveedores");
			var button = $("#iddepartameto");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			
		});
	}

  function filterFunction2(event) {
		event.preventDefault();
		var idtipo_servicio = $("#idtipo_servicio").val();
		var input, filter, ul, li, a, i;
		input = document.getElementById("myInput2");
		filter = input.value.toUpperCase();
		div = document.getElementById("myDropdown2");
		a = div.getElementsByTagName("a");
		for (i = 0; i < a.length; i++) {
			txtValue = a[i].textContent || a[i].innerText;
			rucValue = a[i].getAttribute('data-hidden-value');
			idtipo_servicio_hidden = a[i].getAttribute('data-hidden-servicio');
			if(parseInt(idtipo_servicio) > 0){
				if( idtipo_servicio_hidden  == idtipo_servicio && (txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1  || filter =="")) {
					a[i].style.display = "block";
				} else {
					a[i].style.display = "none";
				}
			}else{
				if(txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1 ) {
					a[i].style.display = "block";
				} else {
					a[i].style.display = "none";
				}
			}

            
            
		}
	}
  </script>
  <style type="text/css">
        #lista_proveedores {
            width: 100%;
        }
       
        .a_link_proveedores{
            display: block;
            padding: 0.8rem;
        }	
        .a_link_proveedores:hover{
            color:white;
            background: #73879C;
        }
        .dropdown_proveedores{
            position: absolute;
            top: 70px;
            left: 0;
            z-index: 99999;
            width: 100% !important;
            overflow: auto;
            white-space: nowrap;
            background: #fff !important;
            border: #c2c2c2 solid 1px;
        }
        .dropdown_proveedores_input{ 
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display:none;
            width: 100% !important;
            padding: 5px !important;
        }
        .btn_proveedor_select{
            border: #c2c2c2 solid 1px;
            color: #73879C;
            width: 100%;
        }
	
	</style>
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
                    <h2>Datos Plantilla</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	


                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            <?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
	    echo htmlentities($_POST['descripcion']);
	} else {
	    echo htmlentities($rs->fields['descripcion']);
	}?>" placeholder="Descripcion" class="form-control" required="required" />                    
	</div>
</div>


<div class=" form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<div class="" style="display:flex;">
							<div class="dropdown " id="lista_proveedores">
								<select onclick="myFunction2(event)"  class="form-control" id="idproveedor" name="idproveedor">
								<option value="" disabled selected></option>
								<?php if ($proveedor_nombre) { ?>
									<option value="<?php echo $idproveedor ?>" data-hidden-diasvence="<?php echo $diasvence?>" selected><?php echo $proveedor_nombre ?></option>
								<?php } ?>
							</select>
								<input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre/ruc Proveedor" id="myInput2" onkeyup="filterFunction2(event)" >
								<div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
									<?php echo $resultados_proveedores ?>
								</div>
							</div>
								<!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
									<span  class="fa fa-plus"></span> Agregar
								</a> -->
						</div>
					</div>
				</div>

				
				<div class="clearfix"></div>

        
<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Pais</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

                // valor seleccionado
        if (isset($_POST['idmedida'])) {
            $value_selected = htmlentities($_POST['idmedida']);
        } else {
            $value_selected = 'S';
        }
// opciones
$opciones = [
  'SI' => '0',
  'NO' => 'N'
];
// parametros
$parametros_array = [
  'nombre_campo' => 'idmedida',
  'id_campo' => 'idmedida',

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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idempresa *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idempresa" id="idempresa" value="<?php  if (isset($_POST['idempresa'])) {
	    echo intval($_POST['idempresa']);
	} else {
	    echo intval($rs->fields['idempresa']);
	}?>" placeholder="Idempresa" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idpto *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idpto" id="idpto" value="<?php  if (isset($_POST['idpto'])) {
	    echo intval($_POST['idpto']);
	} else {
	    echo intval($rs->fields['idpto']);
	}?>" placeholder="Idpto" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idciudad *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idciudad" id="idciudad" value="<?php  if (isset($_POST['idciudad'])) {
	    echo intval($_POST['idciudad']);
	} else {
	    echo intval($rs->fields['idciudad']);
	}?>" placeholder="Idciudad" class="form-control" required="required" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='aduana.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />

            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
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
