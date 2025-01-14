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
    // Obtener el checkbox
    var checkbox = document.getElementById('mycheck');

    // Obtener el span donde se mostrará el estado
    var estadoSpan = document.getElementById('estadoSpan');

    // Función para actualizar el estado
	function actualizarEstadoYFecha() {
            var checkbox = document.getElementById('mycheck');
            var estadoSpan = document.getElementById('estadoSpan');
            var limiteCreditoInput = document.getElementById('limite_credito');
            var diasCreditoInput = document.getElementById('dias_credito');
            var limiteCreditoLabel = document.getElementById('limite_credito_label');
            var diasCreditoLabel = document.getElementById('dias_credito_label');
			var toleranciaInput = document.getElementById('tolerancia');
			var toleranciaLabel = document.getElementById('tolerancia_label');


            if (checkbox.checked) {
                estadoSpan.innerText = 'SI';
                limiteCreditoInput.style.display = 'inline';
                diasCreditoInput.style.display = 'inline';
                limiteCreditoLabel.style.display = 'inline';
                diasCreditoLabel.style.display = 'inline';
				toleranciaInput.style.display = 'inline';
				toleranciaLabel.style.display = 'inline';
            } else {
                estadoSpan.innerText = 'No';
                limiteCreditoInput.style.display = 'none';
                diasCreditoInput.style.display = 'none';
                limiteCreditoLabel.style.display = 'none';
                diasCreditoLabel.style.display = 'none';
				toleranciaInput.style.display = 'none';
				toleranciaLabel.style.display = 'none';
            }
        }

        // Llamar a la función de actualización del estado y la fecha
        actualizarEstadoYFecha();

        // Agregar un listener para el evento change del checkbox
        var checkbox = document.getElementById('mycheck');
        checkbox.addEventListener('change', actualizarEstadoYFecha);
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
                    <h2>Datos Comerciales</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
				  <p>

	<div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Crédito?</label>
        <div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
            <input type="checkbox" class="mycheck" id="mycheck" name="permite_credito"> 
            <label for="mycheck">
                <span id="estadoSpan"></span>
            </label>
        </div>
    </div>

    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12" id="limite_credito_label" style="display: none;">Límite de Crédito</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="limite_credito" id="limite_credito" placeholder="Límite Crédito" class="form-control" style="display: none;">
        </div>
    </div>

	<div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12" id="tolerancia_label" style="display: none;">Dias de Tolerancia</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="tolerancia" id="tolerancia" placeholder="Dias Tolerancia" class="form-control" style="display: none;">                    
        </div>
    </div>

    <div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12" id="dias_credito_label" style="display: none;">Días Crédito</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="dias_credito" id="dias_credito" placeholder="Días Crédito" class="form-control" style="display: none;">                    
        </div>
    </div>

	<div class="clearfix"></div>

	<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Moneda</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <?php
        // consulta
        $consulta = "
        SELECT idtipo, descripcion 
        FROM tipo_moneda
        WHERE estado = '1'
         ";

// valor seleccionado
if (isset($_POST['idtipo'])) {
    $value_selected = htmlentities($_POST['idtipo']);
} else {
    $value_selected = htmlentities($rs->fields['idtipo']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipo',
    'id_campo' => 'idtipo',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'descripcion',

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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio Asignado</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <?php
// consulta
$consulta = "
        SELECT idlistaprecio, lista_precio
        FROM lista_precios_venta
        WHERE estado = 1
         ";

// valor seleccionado
if (isset($_POST['idlistaprecio'])) {
    $value_selected = htmlentities($_POST['idlistaprecio']);
} else {
    $value_selected = htmlentities($rs->fields['idlistaprecio']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idlistaprecio',
    'id_campo' => 'idlistaprecio',

    'nombre_campo_bd' => 'lista_precio',
    'id_campo_bd' => 'lista_precio',

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
