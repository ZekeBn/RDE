<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "1";
$submodulo = "2";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$iddistrito = intval($_GET['id']);
if ($iddistrito == 0) {
    header("location: distrito.php");
    exit;
}


// consulta a la tabla
$consulta = "
select *,
departamentos_propio.descripcion as departamento, departamentos_propio.idpais as idpais, paises_propio.nombre as pais
from distrito_propio 
left join departamentos_propio on distrito_propio.iddepartamento = departamentos_propio.iddepartamento
left join paises_propio on paises_propio.idpais = departamentos_propio.idpais
where 
iddistrito = $iddistrito
and distrito_propio.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$iddistrito = intval($rs->fields['iddistrito']);
$iddepartamento = intval($rs->fields['iddepartamento']);
$idpais = intval($rs->fields['idpais']);
$pais = $rs->fields['pais'];
$departamento = $rs->fields['departamento'];
if ($iddistrito == 0) {
    header("location: distrito.php");
    exit;
}



$buscar = "SELECT iddepartamento,descripcion ,idpais 
FROM departamentos_propio
";

$resultados_departamentos = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idpais = trim(antixss($rsd->fields['idpais']));
    $nombre = trim(antixss($rsd->fields['descripcion']));
    $iddepartamento = trim(antixss($rsd->fields['iddepartamento']));
    $resultados_departamentos .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$idpais' onclick=\"cambia_departamento_pais($iddepartamento, '$nombre', $idpais);\">[$iddepartamento]-$nombre</a>
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
    $distrito = antisqlinyeccion($_POST['distrito'], "text");
    $iddepartamento = antisqlinyeccion($_POST['iddepartamento'], "int");
    $estado = 1;
    $registrado_por = $idusu;
    $registrado_el = antisqlinyeccion($ahora, "text");




    if (trim($_POST['distrito']) == '') {
        $valido = "N";
        $errores .= " - El campo distrito no puede estar vacio.<br />";
    }
    if (intval($_POST['iddepartamento']) == 0) {
        $valido = "N";
        $errores .= " - El campo iddepartamento no puede ser cero o nulo.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update distrito_propio
		set
			distrito=$distrito,
			iddepartamento=$iddepartamento,
			registrado_por=$registrado_por,
			registrado_el=$registrado_el


		where
			iddistrito = $iddistrito
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: distrito.php");
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
	<script>
        function filtrar_pais(h){
            console.log(h);
        }
       

		function cambia_departamento_pais(iddepartamento,nombre,idpais){
			$('#iddepartamento').html($('<option>', {
				value: iddepartamento,
				text: nombre
			}));
	
			$("#idpais").val(idpais)
			// Seleccionar opción
			$('#iddepartamento').val(iddepartamento);
			$('#idciudad').html("");
			var myInput = $('#myInput2');
			var myDropdown = $('#myDropdown2');
			myInput.removeClass('show');
			myDropdown.removeClass('show');	
			
		}
	
	
		function limpiar_datos(){
			$("#iddepartamento").html("");
		}

		function myFunction2(event) {
				event.preventDefault();
				var idpais = $("#idpais").val();
				if (!idpais) {
					document.getElementById("myInput2").classList.toggle("show");
					document.getElementById("myDropdown2").classList.toggle("show");
					div = document.getElementById("myDropdown2");
					$("#myInput2").focus();
				} else {
					var div,ul, li, a, i;
				
					div = document.getElementById("myDropdown2");
					a = div.getElementsByTagName("a");
					for (i = 0; i < a.length; i++) {
						txtValue = a[i].textContent || a[i].innerText;
						id_pais = a[i].getAttribute('data-hidden-value');
						if ( id_pais==idpais ) {
							a[i].style.display = "block";
						} else {
							a[i].style.display = "none";
						}
					}

					document.getElementById("myInput2").classList.toggle("show");
					document.getElementById("myDropdown2").classList.toggle("show");
					div = document.getElementById("myDropdown2");
					$("#myInput2").focus();
				}

				
				$(document).mousedown(function(event) {
					var target = $(event.target);
					var myInput = $('#myInput2');
					var myDropdown = $('#myDropdown2');
					var div = $("#lista_departamentos");
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
			var pais = $("#idpais").val();
			var input, filter, ul, li, a, i;
			input = document.getElementById("myInput2");
			filter = input.value.toUpperCase();
			div = document.getElementById("myDropdown2");
			a = div.getElementsByTagName("a");
			for (i = 0; i < a.length; i++) {
				txtValue = a[i].textContent || a[i].innerText;
				id_pais = a[i].getAttribute('data-hidden-value');
				if(pais ){
					if ((pais == id_pais && txtValue.toUpperCase().indexOf(filter) > -1 )){
						a[i].style.display = "block";
					}else{
						a[i].style.display = "none";
					}
				}else{

					if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
						a[i].style.display = "block";
					} else {
						a[i].style.display = "none";
					}
				}
				
			}
		}
		
		window.onload = function() {
		
			$('#iddepartamento').on('mousedown', function(event) {
				// Evitar que el select se abra
				event.preventDefault();
			});
		};

    </script>
	<style type="text/css">
			#lista_ciudades,#lista_departamentos {
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
                    <h2>Editar Distrito</h2>
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

<div class="col-md-6 col-sm-12 col-xs-12 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Distrito *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="distrito" id="distrito" value="<?php  if (isset($_POST['distrito'])) {
	    echo htmlentities($_POST['distrito']);
	} else {
	    echo htmlentities($rs->fields['distrito']);
	}?>" placeholder="Distrito" class="form-control" required="required" />                    
	</div>
</div>


<div class="col-md-6 col-xs-12 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Pais</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<?php

                // consulta

                $consulta = "
				SELECT idpais, nombre
				FROM paises_propio
				where
				estado = 1
				order by nombre asc
				";

// valor seleccionado
if (isset($_POST['idpais'])) {
    $value_selected = htmlentities($_POST['idpais']);
} else {
    $value_selected = $idpais;
}

if ($_GET['idpais'] > 0) {
    $add = "disabled";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idpais',
    'id_campo' => 'idpais',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idpais',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="limpiar_datos()" "'.$add,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
			</div>
</div>

<div class="col-md-6 col-xs-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Departamentos</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <div class="" style="display:flex;">
            <div class="dropdown " id="lista_departamentos">
                <select onclick="myFunction2(event)"  class="form-control" id="iddepartamento" name="iddepartamento">
                <option value="" disabled selected></option>
				<?php if ($iddepartamento) { ?>
                    <option value="<?php echo $iddepartamento ?>" selected><?php echo $departamento?></option>
                <?php } ?>
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



<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='distrito.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
