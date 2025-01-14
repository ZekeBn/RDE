<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$buscar = "SELECT idciudad,nombre ,idpais 
FROM ciudades_propio
";

$resultados_departamentos = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idpais = trim(antixss($rsd->fields['idpais']));
    $idciudad = trim(antixss($rsd->fields['idciudad']));
    $nombre = trim(antixss($rsd->fields['nombre']));
    $resultados_departamentos .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$idpais' onclick=\"cambia_ciudad($idciudad, '$nombre', $idpais);\">[$idciudad]-$nombre</a>
	";

    $rsd->MoveNext();
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


    // recibe parametros

    $idciudad = antisqlinyeccion($_POST['idciudad'], "int");
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $obs = antisqlinyeccion($_POST['obs'], "text");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");






    if (intval($idciudad) == 0) {
        $valido = "N";
        $errores .= " - El campo idciudad no puede ser cero o nulo.<br />";
    }
    if (trim($nombre) == '') {
        $valido = "N";
        $errores .= " - El campo nombre no puede estar vacio.<br />";
    }



    // si todo es correcto inserta
    if ($valido == "S") {
        $idbarrio = select_max_id_suma_uno("barrios", "idbarrio")["idbarrio"];

        $consulta = "
		insert into barrios
		(idbarrio, idciudad, nombre, obs, estado, registrado_por, registrado_el)
		values
		($idbarrio, $idciudad, $nombre, $obs, $estado, $registrado_por, $registrado_el)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: barrios.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());



?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	<style type="text/css">
        #lista_ciudades {
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
	<script>
		function myFunction2(event) {
			event.preventDefault();
			document.getElementById("myInput2").classList.toggle("show");
			document.getElementById("myDropdown2").classList.toggle("show");
			div = document.getElementById("myDropdown2");
			$("#myInput2").focus();

                
            $(document).mousedown(function(event) {
				var target = $(event.target);
				var myInput = $('#myInput2');
				var myDropdown = $('#myDropdown2');
				var div = $("#lista_ciudades");
				var button = $("#idciudad");
				// Verificar si el clic ocurrió fuera del elemento #my_input
				if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown2").length && myInput.hasClass('show')) {
					myInput.removeClass('show');
					myDropdown.removeClass('show');
           		}
            
            });
        }

		function cambia_ciudad(idciudad,nombre,idpais){
			$('#idciudad').html($('<option>', {
					value: idciudad,
					text: nombre
            }));
            
            // Seleccionar opción
            $('#idciudad').val(idciudad);
            // $('#iddistrito').html("");
            var myInput = $('#myInput2');
            var myDropdown = $('#myDropdown2');
            myInput.removeClass('show');
            myDropdown.removeClass('show');	
            
        }
		function filterFunction2(event) {
            event.preventDefault();
			var input, filter, ul, li, a, i;
			input = document.getElementById("myInput2");
			filter = input.value.toUpperCase();
			div = document.getElementById("myDropdown2");
			a = div.getElementsByTagName("a");
			for (i = 0; i < a.length; i++) {
				txtValue = a[i].textContent || a[i].innerText;
				id_pais = a[i].getAttribute('data-hidden-value');

				if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
					a[i].style.display = "block";
				} else {
					a[i].style.display = "none";
				}
			
			}
        }
		 window.onload = function() {
            $('#idciudad').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
           
        };
	</script>
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
                    <h2>Barrios</h2>
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

<div class="col-md-6 col-xs-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ciudad</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <div class="" style="display:flex;">
            <div class="dropdown " id="lista_ciudades">
                <select onclick="myFunction2(event)"  class="form-control" id="idciudad" name="idciudad">
                <option value="" disabled selected></option>
            </select>
                <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Ciudad" id="myInput2" onkeyup="filterFunction2(event)" >
                <div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                    <?php echo $resultados_departamentos ?>
                </div>
            </div>
                <!-- <a  href="javascript:void(0);" onclick="agregar_proveedor(event);" class="btn btn-sm btn-default">
                    <span  class="fa fa-plus"></span> Agregar
                </a> -->
        </div>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Obs </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="obs" id="obs" value="<?php  if (isset($_POST['obs'])) {
	    echo htmlentities($_POST['obs']);
	} else {
	    echo htmlentities($rs->fields['obs']);
	}?>" placeholder="Obs" class="form-control"  />                    
	</div>
</div>



<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='barrios.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
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
