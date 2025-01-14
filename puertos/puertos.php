<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
// TODO:PREGUNTAR MODULO SI AGREGAR NOMAS
$modulo = "42";
$submodulo = "579";
$dirsup = "S";
require_once("../includes/rsusuario.php");

$iddepartamento = antisqlinyeccion($_GET['iddepartamento'], "int");
$idpais = antisqlinyeccion($_GET['idpais'], "int");
$idciudad = antisqlinyeccion($_GET['idciudad'], "int");
$whereadd = null;
if (trim($_GET['iddepartamento']) != '') {
    $whereadd .= " and departamentos_propio.iddepartamento = $iddepartamento ";
}
if (trim($_GET['idpais']) != '') {
    $whereadd .= " and paises_propio.idpais = $idpais ";
}
if (trim($_GET['idciudad']) != '') {
    $whereadd .= " and ciudades_propio.idciudad = $idciudad ";
}

$consulta = "
select puertos.idpuerto, puertos.descripcion, puertos.idpais, puertos.idpto, puertos.idciudad, paises_propio.nombre as pais, departamentos_propio.descripcion as departamento, ciudades_propio.nombre as ciudad,
puertos.registrado_el,(select usuario from usuarios where puertos.registrado_por = usuarios.idusu) as registrado_por,
(select usuario from usuarios where puertos.borrado_por = usuarios.idusu) as borrado_por
from puertos 
LEFT JOIN paises_propio on paises_propio.idpais = puertos.idpais
LEFT JOIN departamentos_propio on departamentos_propio.iddepartamento = puertos.idpto
LEFT JOIN ciudades_propio on ciudades_propio.idciudad = puertos.idciudad 
where 
 puertos.estado = 1 
 $whereadd
order by idpuerto asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



// $buscar="SELECT idproveedor, nombre, ruc
// FROM proveedores
// where
// estado = 1
// order by nombre asc";
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
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>
        function filtrar_pais(h){
            console.log(h);
        }
        function myFunction(event) {
            event.preventDefault();
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
        // TODO:cambiar por lo de arriba
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
        // TODO:cambiar por lo de arriba
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
                    <h2>Puertos</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

	



                  <p><a href="puertos_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar</a></p>
<hr />

<form id="form1" name="form1" method="get" action="">



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
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Buscar</button>
        </div>
    </div>

<br />
</form>
<div class="clearfix"></div>
<br /><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Idpuerto</th>
			<th align="center">Nombre</th>
			<th align="center">Pais</th>
			<th align="center">Dpto</th>
			<th align="center">Ciudad</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="puertos_det.php?id=<?php echo $rs->fields['idpuerto']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>
					<a href="puertos_edit.php?id=<?php echo $rs->fields['idpuerto']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span></a>
					<a href="puertos_del.php?id=<?php echo $rs->fields['idpuerto']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
			<td align="center"><?php echo intval($rs->fields['idpuerto']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['pais']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['departamento']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ciudad']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
		</tr>
<?php
$estado_acum += $rs->fields['estado'];

    $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
	 
    </table>
</div>
<br />





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
