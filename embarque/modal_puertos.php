<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo

$modulo = "42";
$submodulo = "598";
// $modulo="1";
// $submodulo="2";
$dirsup = "S";
require_once("../includes/rsusuario.php");


$buscar = "SELECT idciudad, nombre, distrito_propio.iddepartamento, departamentos_propio.descripcion, departamentos_propio.idpais 
FROM ciudades_propio
INNER JOIN distrito_propio ON distrito_propio.iddistrito =ciudades_propio.iddistrito 
INNER JOIN departamentos_propio ON departamentos_propio.iddepartamento= distrito_propio.iddepartamento 
WHERE ciudades_propio.estado =1
";
$resultados_ciudad = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idciudad = trim(antixss($rsd->fields['idciudad']));
    $nombre = trim(antixss($rsd->fields['nombre']));
    $iddepartamento = trim(antixss($rsd->fields['iddepartamento']));
    $nombre_departamento = trim(antixss($rsd->fields['descripcion']));
    $idpais = trim(antixss($rsd->fields['idpais']));
    $resultados_ciudad .= "
	<a class='a_link_proveedores'  href='javascript:void(0);' data-hidden-value='$iddepartamento' data-hidden-name='$nombre_departamento' data-hidden-pais='$idpais'  onclick=\"cambia_departamento_ciudad($idciudad, '$nombre', $iddepartamento,'$nombre_departamento',$idpais);\">[$idciudad]-$nombre</a>
	";

    $rsd->MoveNext();
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



?>


  <script>
        function guardar_puerto_ajax(){
            var direccionurl='./crear_puerto.php';	
			var parametros = {
                "descripcion"    : $("#form_puertos #descripcion").val(),
                "idpais"        : $("#form_puertos #idpais").val(),
                "iddepartamento"         : $("#form_puertos #iddepartamento").val(),
                "idciudad"   : $("#form_puertos #idciudad").val(),
				"agregar"       : 1
			};
            console.log(parametros);
			$.ajax({		  
				data:  parametros,
				url:   direccionurl,
				type:  'post',
				cache: false,
				timeout: 3000,  // I chose 3 secs for kicks: 3000
				crossDomain: true,
				beforeSend: function () {
							
				},
				success:  function (response, textStatus, xhr) {
					console.log(response);
                    cerrar_pop();
                    recargar_puerto_select();
				},
				error: function(jqXHR, textStatus, errorThrown) {
					if(jqXHR.status == 404){
						alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
					}else if(jqXHR.status == 0){
						alert('Se ha rechazado la conexión.');
					}else{
						alert(jqXHR.status+' '+errorThrown);
					}
				}
				
				
			}).fail( function( jqXHR, textStatus, errorThrown ) {
				
				if (jqXHR.status === 0) {
			
					alert('No conectado: verifique la red.');
				
				} else if (jqXHR.status == 404) {
				
					alert('Pagina no encontrada [404]');
				
				} else if (jqXHR.status == 500) {
				
					alert('Internal Server Error [500].');
				
				} else if (textStatus === 'parsererror') {
				
					alert('Requested JSON parse failed.');
				
				} else if (textStatus === 'timeout') {
				
					alert('Tiempo de espera agotado, time out error.');
				
				} else if (textStatus === 'abort') {
				
					alert('Solicitud ajax abortada.'); // Ajax request aborted.
				
				} else {
				
					alert('Uncaught Error: ' + jqXHR.responseText);
				
				}
				
			});
        }
        function filtrar_pais(h){
            console.log(h);
        }
        function myFunction(event) {
            event.preventDefault();
            $('#iddepartamento').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
            var departamento = $("#iddepartamento").val();
            var pais = $("#idpais").val();
            if(departamento) {
                var div,ul, li, a, i;
               
                div = document.getElementById("myDropdown");
                a = div.getElementsByTagName("a");
                for (i = 0; i < a.length; i++) {
                    txtValue = a[i].textContent || a[i].innerText;
                    id_departamento = a[i].getAttribute('data-hidden-value');
                    if ( id_departamento==departamento ) {
                        a[i].style.display = "block";
                    } else {
                        a[i].style.display = "none";
                    }
                }

            }
            if(!departamento && pais){
                var div,ul, li, a, i;
               
               div = document.getElementById("myDropdown");
               a = div.getElementsByTagName("a");
               for (i = 0; i < a.length; i++) {
                   txtValue = a[i].textContent || a[i].innerText;
                   id_pais = a[i].getAttribute('data-hidden-pais');
                   if ( id_pais==pais ) {
                       a[i].style.display = "block";
                   } else {
                       a[i].style.display = "none";
                   }
               }
            }
            document.getElementById("myInput").classList.toggle("show");
            document.getElementById("myDropdown").classList.toggle("show");
            div = document.getElementById("myDropdown");
            $("#myInput").focus();


			
		$(document).mousedown(function(event) {
			var target = $(event.target);
			var myInput = $('#myInput');
			var myDropdown = $('#myDropdown');
			var div = $("#lista_ciudades");
			var button = $("#idciudad");
			// Verificar si el clic ocurrió fuera del elemento #my_input
			if (!target.is(myInput) && !target.is(button) && !target.closest("#myDropdown").length && myInput.hasClass('show')) {
			// Remover la clase "show" del elemento #my_input
			myInput.removeClass('show');
			myDropdown.removeClass('show');
			}
			
		});
	}
    function myFunction2(event) {
            event.preventDefault();
            $('#idciudad').on('mousedown', function(event) {
                // Evitar que el select se abra
                event.preventDefault();
            });
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
    function cambia_departamento_ciudad(idciudad,nombre,iddepartamento,nombre_departamento,idpais){
		$('#idciudad').html($('<option>', {
            value: idciudad,
            text: nombre
        }));
       
        // $("#iddepartamento").val(iddepartamento)
        $('#iddepartamento').html($('<option>', {
            value: iddepartamento,
            text: nombre_departamento
        }));
        $("#idpais").val(idpais)
        // Seleccionar opción
        $('#idciudad').val(idciudad);
        var myInput = $('#myInput');
        var myDropdown = $('#myDropdown');
        myInput.removeClass('show');
        myDropdown.removeClass('show');	
        
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
    function filterFunction(event) {
		event.preventDefault();
        var departamento = $("#iddepartamento").val();
        var pais = $("#idpais").val();
		var input, filter, ul, li, a, i;
		input = document.getElementById("myInput");
		filter = input.value.toUpperCase();
		div = document.getElementById("myDropdown");
		a = div.getElementsByTagName("a");
		for (i = 0; i < a.length; i++) {
			txtValue = a[i].textContent || a[i].innerText;
			id_departamento = a[i].getAttribute('data-hidden-value');
			id_pais = a[i].getAttribute('data-hidden-pais');
			if(departamento ){
                if ((departamento == id_departamento && txtValue.toUpperCase().indexOf(filter) > -1 )){
                    a[i].style.display = "block";
                }else{
                    a[i].style.display = "none";
                }
            }
            if(pais ){
                if ((pais == id_pais && txtValue.toUpperCase().indexOf(filter) > -1 )){
                    a[i].style.display = "block";
                }else{
                    a[i].style.display = "none";
                }
            }

            if(!departamento && !pais){

                if (txtValue.toUpperCase().indexOf(filter) > -1 ) {
                    a[i].style.display = "block";
                } else {
                    a[i].style.display = "none";
                }
            }
            
		}
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
    function limpiar_datos(){
        $("#idciudad").html("");
        $("#iddepartamento").html("");
    }
    
    window.onload = function() {
        $('#idciudad').on('mousedown', function(event) {
            // Evitar que el select se abra
            event.preventDefault();
        });
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



                  <?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form_puertos" name="form_puertos" method="post" action="">

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
    $value_selected = $id_pais_nacional;
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
    'acciones' => '  onchange="limpiar_datos()" "'.$add,
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





<div class="col-md-6 col-xs-12 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ciudad</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <div class="" style="display:flex;">
            <div class="dropdown " id="lista_ciudades">
                <select onclick="myFunction(event)"  class="form-control" id="idciudad" name="idciudad">
                <option value="" disabled selected></option>
            </select>
                <input class="dropdown_proveedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre Ciudad" id="myInput" onkeyup="filterFunction(event)" >
                <div id="myDropdown" class="dropdown-content hide dropdown_proveedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
                    <?php echo $resultados_ciudad ?>
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
        
	   <a type="submit" class="btn btn-success" href="javascript:void(0)" onclick="guardar_puerto_ajax()" ><span class="fa fa-check-square-o"></span> Registrar</a>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form_puertos" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />
<div id="preloader-overlay">
	<div class="lds-facebook">
		<div></div>
		<div></div>
		<div></div>
	</div>
</div>



