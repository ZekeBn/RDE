<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "618";

$dirsup = "S";
require_once("../includes/rsusuario.php");



$buscar = "
Select * 
from productos 
where 
idempresa=$idempresa
and borrado = 'N' 
and idprod  in (select idproducto from insumos_lista where   idproducto is not null and idempresa=$idempresa and estado='A')
order by descripcion asc";



$resultados_insumos_lista = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idinsumo = trim(antixss($rsd->fields['idprod_serial']));
    $nombre = trim(antixss($rsd->fields['descripcion']));

    $resultados_insumos_lista .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$idinsumo' onclick=\"cambia_cod_alt($idinsumo, '$nombre');\" >[$idinsumo]-$nombre</a>
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
    $idproducto = antisqlinyeccion($_POST['idproducto'], "int");

    $buscar = "SELECT idinsumo FROM insumos_lista WHERE idproducto = $idproducto";
    $rs_insumo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


    $idinsumo = $rs_insumo->fields['idinsumo'];
    $venta = antisqlinyeccion($_POST['venta'], "int");
    // $venta_sin_stock=antisqlinyeccion($_POST['venta_sin_stock'],"int");
    $venta_sin_stock = antisqlinyeccion("2", "int");
    $estado = 1;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;




    if (intval($_POST['idproducto']) == 0) {
        $valido = "N";
        $errores .= " - El campo idproducto no puede ser cero o nulo.<br />";
    }
    if ($idinsumo == 0) {
        $valido = "N";
        $errores .= " - El campo idinsumo no puede ser cero o nulo.<br />";
    }
    if (intval($_POST['venta']) == 0) {
        $valido = "N";
        $errores .= " - El campo venta no puede ser cero o nulo.<br />";
    }
    if (intval($venta_sin_stock) == 0) {
        $valido = "N";
        $errores .= " - El campo venta_sin_stock no puede ser cero o nulo.<br />";
    }


    // si todo es correcto inserta
    if ($valido == "S") {
        $idexcepcion = select_max_id_suma_uno("excepciones_producto", "idexcepcion")["idexcepcion"];
        $consulta = "
		insert into excepciones_producto
		(idexcepcion, idproducto, idinsumo, venta, venta_sin_stock, estado, registrado_el, registrado_por)
		values
		($idexcepcion, $idproducto, $idinsumo, $venta, $venta_sin_stock, $estado, $registrado_el, $registrado_por)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: excepciones_producto.php");
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
			var div = $("#lista_productos");
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

  function cambia_cod_alt(idinsumo,nombre){
		$('#idproducto').html($('<option>', {
            value: idinsumo,
            text: nombre
        }));
        
        $('#idproducto').val(idinsumo);
       
        var myInput = $('#myInput2');
        var myDropdown = $('#myDropdown2');
        myInput.removeClass('show');
        myDropdown.removeClass('show');	
        
	}

  window.onload = function() {
		
        $('#idproducto').on('mousedown', function(event) {
            // Evitar que el select se abra
            event.preventDefault();
        });
    };

  </script>
  <style type="text/css">
		.have_cod_alt{
			background: #6CAD3BC4;
			color:white;
		}
		.have_cod_alt:hover{
			background: #A7D9A5 !important;
			color:white !important;
		}
		
        #lista_productos {
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
                    <h2>Excepciones Producto</h2>
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

<div class="col-md-6 col-sm-6 form-group"  >
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Productos</label>
			<div class="col-md-9 col-sm-9 col-xs-12">
				<div class="" style="display:flex;">
					<div class="dropdown " id="lista_productos">
						<select onclick="myFunction2(event)"  class="form-control " id="idproducto" name="idproducto">
						<?php if (intval($rs->fields['idproducto']) > 0) { ?>
							<option value="<?php echo intval($rs->fields['idproducto']) ?>" selected><?php echo $rs->fields['cod_alt_nombre'] ?></option>
						<?php } ?>
					</select>
						<input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Articulo" id="myInput2" onkeyup="filterFunction2(event)" >
						<div id="myDropdown2" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
							<?php echo $resultados_insumos_lista ?>
						</div>
					</div>
				</div>
			</div>
		</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Venta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <?php


      // valor seleccionado
      if (isset($_POST['venta'])) {
          $value_selected = htmlentities($_POST['venta']);
      } else {
          $value_selected = '1';
      }
// opciones
$opciones = [
  'SI' => '1',
  'NO' => '2'
];
// parametros
$parametros_array = [
  'nombre_campo' => 'venta',
  'id_campo' => 'venta',

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

<!-- <div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Venta sin stock *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
    <?php


    // valor seleccionado
    if (isset($_POST['venta_sin_stock'])) {
        $value_selected = htmlentities($_POST['venta_sin_stock']);
    } else {
        $value_selected = '2';
    }
// opciones
$opciones = [
  'SI' => '1',
  'NO' => '2'
];
// parametros
$parametros_array = [
  'nombre_campo' => 'venta_sin_stock',
  'id_campo' => 'venta_sin_stock',

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
</div> -->

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='excepciones_producto.php'"><span class="fa fa-ban"></span> Cancelar</button>
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
