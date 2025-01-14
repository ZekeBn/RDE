<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

$valido = "S";
$error = "";
// preferencias caja
$consulta = "
	SELECT 
	valida_ruc, permite_ruc_duplicado
	FROM preferencias_caja 
	WHERE  
	idempresa = $idempresa 
	";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$valida_ruc = trim($rsprefcaj->fields['valida_ruc']);
$permite_ruc_duplicado = trim($rsprefcaj->fields['permite_ruc_duplicado']);

$buscar = "SELECT idvendedor, tipovendedor,idtipodoc,nrodoc,nomape,nombres,apellidos,estado,idempresa
FROM vendedor
";

$resultados_vendedores = null;
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
while (!$rsd->EOF) {
    $idvendedor = trim(antixss($rsd->fields['idvendedor']));
    $tipovendedor = trim(antixss($rsd->fields['tipovendedor']));
    $idtipodoc = trim(antixss($rsd->fields['idtipodoc']));
    $nrodoc = trim(antixss($rsd->fields['nrodoc']));
    $nomape = trim(antixss($rsd->fields['nomape']));
    $nombres = trim(antixss($rsd->fields['nombres']));
    $apellidos = trim(antixss($rsd->fields['apellidos']));
    $estado = trim(antixss($rsd->fields['estado']));
    $idempresa = trim(antixss($rsd->fields['idempresa']));
    $resultados_vendedores .= "
	<a class='a_link_vendedores'  href='javascript:void(0);' data-hidden-value='$nrodoc' onclick=\"cambia_vendedor($idvendedor, $tipovendedor, $idtipodoc, $nrodoc,'$nomape','$nombres', '$apellidos','$estado',$idempresa);\">[$idvendedor]-$nomape</a>
	";

    $rsd->MoveNext();
}
// cliente generico
$consulta = "
select ruc, razon_social
from cliente 
where 
borrable = 'N'
order by idcliente asc
limit 1
";
$rscligen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ruc_pred = $rscligen->fields['ruc'];
$razon_social_pred = $rscligen->fields['razon_social'];

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



    $parametros_array = [
        'idusu' => $idusu,
        'idvendedor' => $_POST['idvendedor'],
        'sexo' => '',
        'nombre' => $_POST['nombre'],
        'apellido' => $_POST['apellido'],
        'nombre_corto' => $_POST['nombre_corto'],
        'idtipdoc' => $_POST['idtipdoc'],
        'documento' => $_POST['documento'],
        'ruc' => $_POST['ruc'],
        'telefono' => $_POST['telefono'],
        'celular' => $_POST['celular'],
        'email' => $_POST['email'],
        'direccion' => $_POST['direccion'],
        'comentario' => $_POST['comentario'],
        'fechanac' => $_POST['fechanac'],
        'idclientetipo' => $_POST['idclientetipo'],
        'razon_social' => $_POST['razon_social'],
        'fantasia' => $_POST['fantasia'],
        'ruc_especial' => $_POST['ruc_especial'],
        'idsucursal' => $idsucursal,
        'idclientecat' => $_POST['idclientecat'],
        'numero_casa' => $_POST['numero_casa'],
        'departamento' => $_POST['departamento'],
        'id_distrito' => $_POST['iddistrito'],
        'idciudad' => $_POST['idciudad'],
        'idtiporeceptor_set' => $_POST['idtiporeceptor_set'],
        'idtipooperacionset' => $_POST['idtipooperacionset'],
        'codigoEDI' => $_POST['codigoEDI'],
    ];
    //print_r($parametros_array);exit;
    $res = validar_cliente($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = $res['valido'];
        $errores = nl2br($res['errores']);
    }
    //print_r($res);exit;
    // si todo es correcto inserta

    if ($valido == "S") {

        $res = registrar_cliente($parametros_array);
        $idcliente = $res['idcliente'];

        header("location: cliente.php");
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
function cambia_vendedor(idvendedor,tipovendedor,idtipodoc,nrodoc,nomape,nombres,apellidos,estado,idempresa){
	// alerta_modal("contenido",idtipo_origen+ " "+idmoneda);
	$('#idvendedor').html($('<option>', {
		value: idvendedor,
		text: nomape
	}));	
	//console.log("Valor de idvendedor:", idvendedor);
	var myInput = $('#myInput2');
	var myDropdown = $('#myDropdown2');
	myInput.removeClass('show');
	myDropdown.removeClass('show');
			
	}

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
		var div = $("#lista_vendedores");
		var button = $("#idvendedor");
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
	var input, filter, ul, li, a, i;
	input = document.getElementById("myInput2");
	filter = input.value.toUpperCase();
	div = document.getElementById("myDropdown2");
	a = div.getElementsByTagName("a");
	for (i = 0; i < a.length; i++) {
		txtValue = a[i].textContent || a[i].innerText;
		rucValue = a[i].getAttribute('data-hidden-value');
			

		if(txtValue.toUpperCase().indexOf(filter) > -1 || rucValue.indexOf(filter) > -1 ) {
			a[i].style.display = "block";
		} else {
			a[i].style.display = "none";
		}
				
				
	}
}
function obtenerVendedorSeleccionado() {
        var select = document.getElementById("idvendedor");
        var selectedOption = select.options[select.selectedIndex];
        var selectedValue = selectedOption.value;
        var selectedText = selectedOption.textContent;

        //Haz lo que necesites con el valor y el texto seleccionados
        console.log("Valor seleccionado: " + selectedValue);
        console.log("Texto seleccionado: " + selectedText);
    }

function cliente_tipo(tipo){
	// persona fisica
	if(tipo == 1){
		$("#nombre_box").show();
		$("#apellido_box").show();
		//$("#fantasia_box").hide();
	// persona juridica
	}else{
		$("#nombre_box").hide();
		$("#apellido_box").hide();
		//$("#fantasia_box").show();
	}
	
}

function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
function carga_ruc_h(){
	var vruc = $("#ruc").val();
	var txtbusca="Buscando...";
	if(txtbusca != vruc){
		var parametros = {
				"ruc" : vruc
		};
		$.ajax({
				data:  parametros,
				url:   'ruc_extrae_deliv.php',
				type:  'post',
				beforeSend: function () {
					$("#ruc").val(txtbusca);
				},
				success:  function (response) {
					if(IsJsonString(response)){
						var obj = jQuery.parseJSON(response);
						//alert(response);
						if(obj.error == ''){
							var new_ruc = obj.ruc;
							var new_rz = obj.razon_social;
							var new_nom = obj.nombre_ruc;
							var new_ape = obj.apellido_ruc;
							var idcli = obj.idcliente;
							$("#ruc").val(new_ruc);
							$("#razon_social").val(new_rz);
							$("#nombre").val(new_nom);
							$("#apellido").val(new_ape);
							//if(parseInt(idcli)>0){
								//nclie(tipocobro,idpedido);
								//selecciona_cliente(idcli,tipocobro,idpedido);
							//}
						}else{
							$("#ruc").val(vruc);
							$("#razon_social").val('');
		
						}
					}else{
		
						alert(response);
				
					}
					
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
}

window.onload = function() {
    // Obtener los checkboxes y el elemento de motivo
    var checkbox_act = document.getElementById('mycheck_act');
    var checkbox_inac = document.getElementById('mycheck_inac');
    var motivoInput = document.getElementById('motivo');   
    var motivoLabel = document.getElementById('motivo_label');

    // Función para actualizar el estado
    function actualizarEstadoYFecha() {
        if (checkbox_act.checked) {
            // Si se marca checkbox_act, desmarcar checkbox_inac y ocultar motivo
            checkbox_inac.checked = false;
            motivoInput.style.display = 'none';                
            motivoLabel.style.display = 'none';
            motivoInput.value = '';
        } 
		if (checkbox_inac.checked) {
            // Si se marca checkbox_inac, desmarcar checkbox_act y mostrar motivo
            checkbox_act.checked = false; 
            motivoInput.style.display = 'inline';               
            motivoLabel.style.display = 'inline';
        } else {
            // Si ninguno está marcado, ocultar motivo
            motivoInput.style.display = 'none';                
            motivoLabel.style.display = 'none';
            motivoInput.value = '';
        }
    }

    // Llamar a la función de actualización del estado
    actualizarEstadoYFecha();

    // Agregar listeners para el evento change de los checkboxes
    checkbox_act.addEventListener('change', actualizarEstadoYFecha);
    checkbox_inac.addEventListener('change', actualizarEstadoYFecha);
};

</script>
<style type="text/css">
        #lista_vendedores {
            width: 100%;
        }
       
        .a_link_vendedores{
            display: block;
            padding: 0.8rem;
        }	
        .a_link_vendedores:hover{
            color:white;
            background: #73879C;
        }
        .dropdown_vendedores{
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
        .dropdown_vendedores_input{ 
            position: absolute;
            top: 37px;
            left: 0;
            z-index: 99999;
            display:none;
            width: 100% !important;
            padding: 5px !important;
        }
        .btn_vendedores_select{
            border: #c2c2c2 solid 1px;
            color: #73879C;
            width: 100%;
        }
		#fechaCampo {
      	margin-left: 10px; /* Ajusta el margen izquierdo según sea necesario */
    	}
		.fecha-label {
      	margin-left: 40px; /* Ajusta el margen izquierdo del label */
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
                    <h2>Datos Adicionales</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
				  <p>

				  <a href="cliente_add.php" class="btn btn-sm btn-default"><span class="fa fa-undo"></span> Volver</a>
<br>
<br>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Estado del Cliente</label>
    <div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
        <input type="radio" class="mycheck_act" id="mycheck_act" name="estado_cliente" value="activo" checked> 
        <label for="mycheck_act">
            <span id="estadoSpan_act">Activo</span>
        </label>
        <input type="radio" class="mycheck_inac" id="mycheck_inac" name="estado_cliente" value="inactivo"> 
        <label for="mycheck_inac">
            <span id="estadoSpan_inac">Inactivo</span>
        </label>
    </div>
</div>

    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12" id="motivo_label" style="display: none;">Motivo</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="motivo" id="motivo" placeholder="Motivo" class="form-control" style="display: none;">
        </div>
    </div>

<div class="clearfix"></div>	

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
			<div class="dropdown " id="lista_vendedores">
			<select onclick="myFunction2(event)"  class="form-control" id="idvendedor" name="idvendedor">
			<option value="" disabled ></option>
			<?php if (intval($idvendedor_res) > 0) { ?>
				<option value="<?php echo $idvendedor_res; ?>"   selected><?php echo $vendedor_res; ?></option>
				<?php } ?>
				</select>
				<input class="dropdown_vendedores_input col-md-9 col-sm-9 col-xs-12"type="text" placeholder="Nombre/Id Vendedor" id="myInput2" onkeyup="filterFunction2(event)" >
				<div id="myDropdown2" class="dropdown-content hide dropdown_vendedores links-wrapper col-md-9 col-sm-9 col-xs-12" style="max-height: 200px;overflow: auto;">
				<?php echo $resultados_vendedores ?>
				</div>
				</div>

			</div>
    </div>

	<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cobrador *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <?php
        // consulta
        $consulta = "
        SELECT idcobrador, cobrador
        FROM cobrador
        WHERE estado = 'A'
         ";

// valor seleccionado
if (isset($_POST['idcobrador'])) {
    $value_selected = htmlentities($_POST['idcobrador']);
} else {
    $value_selected = htmlentities($rs->fields['idcobrador']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idcobrador',
    'id_campo' => 'idcobrador',

    'nombre_campo_bd' => 'cobrador',
    'id_campo_bd' => 'cobrador',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '0',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="cobrador(this.value)" ',
    'autosel_1registro' => 'N'
];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo cliente *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // consulta
    $consulta = "
    SELECT idclientetipo, clientetipo
    FROM cliente_tipo
    where
    estado = 1
    order by clientetipo asc
     ";

// valor seleccionado
if (isset($_POST['idclientetipo'])) {
    $value_selected = htmlentities($_POST['idclientetipo']);
} else {
    $value_selected = htmlentities($rs->fields['idclientetipo']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idclientetipo',
    'id_campo' => 'idclientetipo',

    'nombre_campo_bd' => 'clientetipo',
    'id_campo_bd' => 'idclientetipo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="cliente_tipo(this.value)" ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>
	

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Naturaleza Persona *</label> <!-- Combobox Naturaleza Persona -->
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
$tipocliente = $rs->fields['tipocliente'];
// consulta
$consulta = "
    SELECT idnaturalezapersona, naturaleza_persona
    FROM naturaleza_persona
    order by naturaleza_persona asc
     ";

// valor seleccionado
if (isset($_POST['idtiporeceptor_set'])) {
    $value_selected = htmlentities($_POST['idtiporeceptor_set']);
} else {
    $value_selected = 1;
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtiporeceptor_set',
    'id_campo' => 'idtiporeceptor_set',

    'nombre_campo_bd' => 'naturaleza_persona',
    'id_campo_bd' => 'idnaturaleza_persona',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="naturaleza_persona(this.value)" ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Código EDI </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="codigoEDI" id="codigoEDI" value="<?php  if (isset($_POST['codigoEDI'])) {
	    echo htmlentities($_POST['codigoEDI']);
	} else {
	    echo htmlentities($rs->fields['codigoEDI']);
	}?>" placeholder="Código EDI" class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Email </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="email" id="email" value="<?php  if (isset($_POST['email'])) {
	    echo htmlentities($_POST['email']);
	} else {
	    echo htmlentities($rs->fields['email']);
	}?>" placeholder="Email" class="form-control"  />                    
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Celular </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="celular" id="celular" value="<?php  if (isset($_POST['celular'])) {
	    echo floatval($_POST['celular']);
	} else {
	    echo floatval($rs->fields['celular']);
	}?>" placeholder="Celular" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="comentario" id="comentario" value="<?php  if (isset($_POST['comentario'])) {
	    echo htmlentities($_POST['comentario']);
	} else {
	    echo htmlentities($rs->fields['comentario']);
	}?>" placeholder="Comentario" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Categoria Cliente </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idclientecat, cliente_categoria
FROM cliente_categoria
where
estado = 1
order by cliente_categoria asc
 ";

// valor seleccionado
if (isset($_POST['idclientecat'])) {
    $value_selected = htmlentities($_POST['idclientecat']);
} else {
    $value_selected = htmlentities($rs->fields['idclientecat']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idclientecat',
    'id_campo' => 'idclientecat',

    'nombre_campo_bd' => 'cliente_categoria',
    'id_campo_bd' => 'idclientecat',

    'value_selected' => $value_selected,

    'pricampo_name' => 'NO APLICA',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>

	<div class="clearfix"></div>
<br /><br />
					  
<div class="alert alert-warning alert-dismissible fade in" role="alert">
<strong>AVISO:</strong><br />
Si usted es facturador electronico y desea que se informe la direccion debe completar obligatoriamente: direccion, departamento, distrito, ciudad y numero de casa, en caso que alguno de los 5 no este cargado, se registrara en su sistema pero no se informara en la factura electronica.
</div>
					  
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
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
