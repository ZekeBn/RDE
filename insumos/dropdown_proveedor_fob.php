<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");
// consulta
$idfob_elegido = null;
$codigo_origen_elegido = null;
$idproveedor = intval($_POST['idproveedor']);
$agregar_cod_fob = $_POST['agregar_cod_fob'];
$oculta_add = intval($_POST['oculta_add']);
$idfob_select = $_POST['idfob'];

if ($agregar_cod_fob == 1) {
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $codigo_articulo = antisqlinyeccion($_POST['codigo_articulo'], "text");
    $precio = antisqlinyeccion($_POST['precio'], "float");
    $fecha = antisqlinyeccion($_POST['fecha'], "text");
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;
    $estado = 1;
    $valido = "S";
    if (intval($_POST['idproveedor']) == 0) {
        $valido = "N";
        $errores .= " - El campo idproveedor no puede ser cero o nulo.<br />";
    }
    if (trim($_POST['codigo_articulo']) == '') {
        $valido = "N";
        $errores .= " - El campo codigo_articulo no puede estar vacio.<br />";
    }
    if (floatval($_POST['precio']) < 0) {
        $valido = "N";
        $errores .= " - El campo precio no puede ser cero o negativo.<br />";
    }
    if (trim($_POST['fecha']) == '') {
        $valido = "N";
        $errores .= " - El campo fecha no puede estar vacio.<br />";
    }

    if ($valido == "S") {
        $idfob = select_max_id_suma_uno("proveedores_fob", "idfob")["idfob"];
        $consulta = "
		insert into proveedores_fob
		(idfob, idproveedor, codigo_articulo, precio, fecha, registrado_el, registrado_por, estado)
		values
		($idfob, $idproveedor, $codigo_articulo, $precio, $fecha, $registrado_el, $registrado_por, $estado)
		";
        $idfob_elegido = $idfob;
        $codigo_origen_elegido = $codigo_articulo;
        $codigo_origen_elegido = str_replace("'", "", $codigo_origen_elegido);
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


    }



}


$buscar = "SELECT idfob, codigo_articulo
    FROM proveedores_fob
where
    idproveedor = $idproveedor
    and estado = 1
    order by codigo_articulo desc
";
$resultados_articulos = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$script = null;
while (!$rsd->EOF) {
    $idfob = trim(antixss($rsd->fields['idfob']));
    $nombre = trim(antixss($rsd->fields['codigo_articulo']));
    if ($idfob_select == $idfob) {
        $codigo_origen_elegido = $nombre;
        $idfob_elegido = $idfob;
    }
    $resultados_articulos .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' onclick=\"cambia_cod_fob($idfob, '$nombre');\">[$idfob]-$nombre</a>
	";
    $rsd->MoveNext();
}

?>

<script>
    function cambia_cod_fob(idfob,nombre){
		$('#cod_fob').html($('<option>', {
            value: idfob,
            text: nombre
        }));
        
        $('#cod_fob').val(idfob);
       
        var myInput = $('#myInput');
        var myDropdown = $('#myDropdown');
        myInput.removeClass('show');
        myDropdown.removeClass('show');	
        
	}
    function myFunction(event) {
            event.preventDefault();
            document.getElementById("myInput").classList.toggle("show");
            document.getElementById("myDropdown").classList.toggle("show");
            div = document.getElementById("myDropdown");
            $("#myInput").focus();


			
		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput');
			var myDropdown = $('#myDropdown');
			var div = $("#lista_articulos");
			var button = $("#cod_fob");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			
		});
	}
    function filterFunction(event) {
		event.preventDefault();
		var input, filter, ul, li, a, i;
		input = document.getElementById("myInput");
		filter = input.value.toUpperCase();
		div = document.getElementById("myDropdown");
		a = div.getElementsByTagName("a");
		for (i = 0; i < a.length; i++) {
			txtValue = a[i].textContent || a[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                a[i].style.display = "block";
            } else {
                a[i].style.display = "none";
            }
		}
	}

    window.onload = function() {
        $('#cod_fob').on('mousedown', function(event) {
            event.preventDefault();
        });
       
    };
</script>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">
		<?php if ($oculta_add != 1) { ?>
			<a href="javascript:void(0);" onClick="ventana_codigo_origen();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a>
		<?php } ?>
		Codigo de origen</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
            <div class="" style="display:flex;">
                <div class="dropdown " id="lista_articulos">
                    <select  aria-describedby="codOrigenHelp" onclick="myFunction(event)"  class="form-control" id="cod_fob" name="cod_fob">
                    <?php if (isset($idfob_elegido)) { ?>
						<option value="<?php echo $idfob_elegido ?>" selected><?php echo $codigo_origen_elegido?></option>
					<?php } else { ?>
						<option value="" disabled selected></option>
					<?php } ?>
                </select>
                <small id="codOrigenHelp"   class="form-text text-muted">Referencte al codigo del Proveedor FOB.</small>
                    <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Codigo FOB" id="myInput" onkeyup="filterFunction(event)" >
                    <div id="myDropdown" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                        <?php echo $resultados_articulos ?>
                    </div>
                </div>
                    
            </div>


    </div>


		