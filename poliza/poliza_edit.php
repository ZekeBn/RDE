<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "42";
$submodulo = "614";

$dirsup = "S";
require_once("../includes/rsusuario.php");



$idpoliza = intval($_GET['id']);
if ($idpoliza == 0) {
    header("location: poliza.php");
    exit;
}

// consulta a la tabla
$consulta = "
select poliza.* , proveedores.nombre
from poliza 
INNER JOIN proveedores on proveedores.idproveedor = poliza.idproveedor
where 
poliza.idpoliza = $idpoliza
and poliza.estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idpoliza = intval($rs->fields['idpoliza']);
$nombre_proveedor = $rs->fields['nombre'];
$idproveedor = $rs->fields['idproveedor'];
if ($idpoliza == 0) {
    header("location: poliza.php");
    exit;
}




//SELECT proveedores datos

$buscar = "SELECT idproveedor, idtipo_origen,nombre ,idmoneda, ruc,tipocompra,idtipo_servicio,dias_entrega
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
    $ruc = trim(antixss($rsd->fields['ruc']));
    $resultados_proveedores .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$ruc' data-hidden-servicio='$idtipo_servicio' data-hidden-entrega='$dias_entrega' onclick=\"cambia_proveedor($idtipo_origen, $idmoneda, $idproveedor, '$nombre',$tipocompra, $dias_entrega);\">[$idproveedor]-$nombre</a>
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
    $archivo = $_FILES['archivo'];
    $fecha_inicio = antisqlinyeccion($_POST['fecha_inicio'], "text");
    $fecha_fin = antisqlinyeccion($_POST['fecha_fin'], "text");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;
    $estado = 1;
    $anulado_por = antisqlinyeccion($_POST['anulado_por'], "int");
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $anulado_el = antisqlinyeccion($_POST['anulado_el'], "text");




    if ($idproveedor == "NULL" || $idproveedor == 0) {
        $valido = "N";
        $errores .= " - El campo proveedor no puede estar vacio.<br />";
    }
    if (trim($_POST['fecha_inicio']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_inicio no puede estar vacio.<br />";
    }
    if (trim($_POST['fecha_fin']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha_fin no puede estar vacio.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {
        $nombre_archivo = "";
        if ($archivo['name'] != "") {
            if (is_dir("../gfx/proveedores/poliza")) {

            } else {
                //creamos
                mkdir("../gfx/proveedores/", "0777");
                mkdir("../gfx/proveedores/poliza", "0777");

            }
            $date_now = date("YmdHis");
            $extension_archivo = end(explode('.', $archivo['name']));
            $nombre_archivo = 'poliza_'.$date_now.'.'.$extension_archivo;
            $dest_file = "../gfx/proveedores/poliza/$idproveedor/".$nombre_archivo;
            $directorio = "../gfx/proveedores/poliza/$idproveedor";
            mkdir($directorio, 0777);
            if (!file_exists($dest_file)) {
                move_uploaded_file($archivo['tmp_name'], $dest_file) or die("Error!!");
            }
        }
        $consulta = "";
        if ($nombre_archivo != null && $nombre_archivo != "") {

            $consulta = "
      update poliza
      set
        archivo='$nombre_archivo',
        fecha_inicio=$fecha_inicio,
        fecha_fin=$fecha_fin,
        idproveedor=$idproveedor
      where
        idpoliza = $idpoliza
        and estado = 1
      ";
        } else {
            $consulta = "
      update poliza
      set
        fecha_inicio=$fecha_inicio,
        fecha_fin=$fecha_fin,
        idproveedor=$idproveedor
      where
        idpoliza = $idpoliza
        and estado = 1
      ";
        }

        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        header("location: poliza.php");
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


      function cambia_proveedor(idtipo_origen, idmoneda, idproveedor, nombre,idtipo_compra, dias_entrega){
		// alerta_modal("contenido",idtipo_origen+ " "+idmoneda);
      $('#idproveedor').html($('<option>', {
          value: idproveedor,
          text: nombre
        }));
	
			var myInput = $('#myInput2');
			var myDropdown = $('#myDropdown2');
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			
		
	}

      window.onload = function() {
        $('#idproveedor').on('mousedown', function(event) {
            // Evitar que el select se abra
            event.preventDefault();
        });
      };
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
                    <h2>Datos Polizas</h2>
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
<form id="form1" name="form1" method="post" enctype="multipart/form-data" action="">



<div class="col-md-6 col-sm-6 form-group form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor *</label>
					<div class="col-md-9 col-sm-9 col-xs-12">
						<div class="" style="display:flex;">
							<div class="dropdown " id="lista_proveedores">
								<select onclick="myFunction2(event)"  class="form-control" id="idproveedor" name="idproveedor">
								<option value="" disabled selected></option>
								<?php if ($nombre_proveedor) { ?>
									<option value="<?php echo $idproveedor ?>" selected><?php echo $nombre_proveedor ?></option>
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



<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Archivo </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="file" name="archivo" id="archivo" value="<?php  if (isset($_POST['archivo'])) {
	    echo htmlentities($_POST['archivo']);
	} else {
	    echo htmlentities($rs->fields['archivo']);
	}?>" placeholder="Archivo" class="form-control"  />                    
	<?php if (isset($rs->fields["archivo"])) { ?>
					
					<small id="archivoProveedorHelp" class="form-text text-muted">
						Si se carga otro archivo, el archivo actual se almacenará y se convertirá en el archivo 
						vigente para la poliza de este proveedor. Sin embargo, aún será posible descargar los archivos 
						anteriores desde el detalle de la poliza.
					</small>
  <?php } ?>
  </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha inicio *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_inicio" id="fecha_inicio" value="<?php  if (isset($_POST['fecha_inicio'])) {
	    echo htmlentities($_POST['fecha_inicio']);
	} else {
	    echo $rs->fields['fecha_inicio'] != "" ? htmlentities(date("Y-m-d", strtotime($rs->fields['fecha_inicio']))) : "";
	}?>" placeholder="Fecha inicio" class="form-control" required="required" />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha fin *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fecha_fin" id="fecha_fin" value="<?php  if (isset($_POST['fecha_fin'])) {
	    echo htmlentities($_POST['fecha_fin']);
	} else {
	    echo $rs->fields['fecha_fin'] != "" ? htmlentities(date("Y-m-d", strtotime($rs->fields['fecha_fin']))) : "";
	}?>" placeholder="Fecha fin" class="form-control" required="required" />                    
	</div>
</div>




<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='poliza.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
