<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

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
        'sexo' => $_POST['sexo'],
        'codclie' => $_POST['codigo_cliente'],
        'nombre' => $_POST['nombre'],
        'apellido' => $_POST['apellido'],
        'nombre_corto' => '',
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
        'departamento' => $_POST['iddepartamento'],
        'id_distrito' => $_POST['iddistrito'],
        'idciudad' => $_POST['idciudad'],
        'idtiporeceptor_set' => $_POST['idtiporeceptor_set'],
        'idtipooperacionset' => $_POST['idtipooperacionset'],
        'codigoedi' => $_POST['codigoedi'],
        'codigo_persona' => $_POST['codigo_persona'],
        'motivo' => $_POST['motivo'],
        'limite_credito' => $_POST['limite_credito'],
        'idcredito' => $_POST['idcredito'],
        'dia_visita' => $_POST['dia_visita'],
        'idcobrador' => $_POST['idcobrador'],
        'idnaturalezapersona' => $_POST['idnaturalezapersona'],
        'idmoneda' => $_POST['idtipo'],
        'idlistaprecio' => $_POST['idlistaprecio'],
        'direccion2' => $_POST['direccion2'],
        'numero_casa2' => $_POST['numero_casa2'],
        'departamento2' => $_POST['iddepartamento2'],
        'iddistrito2' => $_POST['iddistrito2'],
        'idciudad2' => $_POST['idciudad2'],
        'idcadena' => $_POST['idcadena'],
        'estado' => $_POST['opcion'],
        'permite_credito' => $_POST['credito'],
        'registrado_el' => $registrado_el,
        'registrado_por' => $registrado_por,


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


?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  <script>

function cliente_tipo(tipo){
	// persona fisica
	if(tipo == 1){
		$("#nombre").show();
		$("#apellido").show();
		//$("#fantasia_box").hide();
	// persona juridica
	}else{
		$("#nombre").hide();
		$("#apellido").hide();
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

document.addEventListener("DOMContentLoaded", function() {
        var clienteTipoSelect = document.getElementById('idclientetipo'); // Obtener el select del tipo de cliente
        var sexoCombobox = document.getElementById('sexo'); // Obtener el combobox de sexo
        var cedulaField = document.getElementById('cedula'); // Obtener el campo de cédula
        
        clienteTipoSelect.addEventListener('change', function() {
            var valorSeleccionado = this.value; // Obtener el valor seleccionado
            
            // Mostrar u ocultar el combobox de sexo, el campo de cédula y el campo de RUC según la selección
            if (valorSeleccionado === "1" || valorSeleccionado === "PERSONA FISICA") {
                sexoCombobox.style.display = "block"; // Mostrar el combobox "Sexo"
                cedulaField.style.display = "block"; // Mostrar el campo de cédula
            } else {
                sexoCombobox.style.display = "none"; // Ocultar el combobox "Sexo"
                cedulaField.style.display = "none"; // Ocultar el campo de cédula
            }
        });
    });

    function mostrarOcultarCampoTexto() {
    var motivoLabel = document.getElementById("motivo_label");
    var motivoInput = document.getElementById("motivo");
    var opcionActiva = document.querySelector('input[name="opcion"]:checked');
    var estadoCliente = null;

    if (opcionActiva) {
        estadoCliente = opcionActiva.value;

        if (estadoCliente === "1") {
            motivoLabel.style.display = "none";
            motivoInput.style.display = "none";
        } else {
            motivoLabel.style.display = "block";
            motivoInput.style.display = "block";
        }
    }
}


function mostrarCampos() {
        var checkbox = document.getElementById("checkbox");
        var tipoCredito = document.getElementById("tipoCredito");
		var limitecreditolabel = document.getElementById("limitecreditolabel");
        var limitecreditoInput = document.getElementById("limite_credito");
		if (checkbox.checked) {
            tipoCredito.style.display = "block";
			limitecreditolabel.style.display= "block";
			limitecreditoInput.style.display= "block";
			document.querySelector('label[for="checkbox"]').innerText = "SI";
        } else {
            tipoCredito.style.display = "none";
			limitecreditolabel.style.display= "none";
			limitecreditoInput.style.display= "none";
			document.querySelector('label[for="checkbox"]').innerText = "No";
        }
    }
	

var rutaArchivoSeleccionado = ''; // Definir la variable global al inicio

function previsualizarFoto(input) {
    var preview = document.getElementById('imagen_previsualizada');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        const fileName = input.files[0].name;
        console.log('File name:', fileName);

        reader.onload = function(e) {
            // Almacenar la ruta del archivo en la variable global
            rutaArchivoSeleccionado = e.target.result;
            preview.src = rutaArchivoSeleccionado;

            // También puedes actualizar el campo oculto
            var rutaFotoInput = document.getElementById('ruta_foto');
            rutaFotoInput.value = rutaArchivoSeleccionado;
        }

        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '../gfx/productos/prod_0.jpg';
    }
}
function cargarImagen(input) {
    var file = input.files[0];
    var formData = new FormData();
    formData.append('foto', file);

    // Envía la imagen al servidor a través de AJAX
    fetch('funciones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log(data); // Aquí puedes manejar la lista de archivos renombrados
    })
    .catch(error => console.error(error));
}

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
                    <h2>Datos Generales</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <!-- ::::::::::::::::::::::::::::::::::::AQUI SE COLOCA EL HTML::::::::::::::::::::::::::::::::::::::::::: -->
                  <?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">	

<div class="col-md-6 col-sm-6 form-group" id="tipoCliente">
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
    'acciones' => ' required="required"  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
  </div>
</div>

<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod. Cliente *</label> <!-- Campo Codigo de Cliente -->
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="codigo_cliente" id="codigo_cliente" value="<?php  if (isset($_POST['codigo_cliente'])) {
			    echo htmlentities($_POST['codigo_cliente']);
			} else {
			    echo htmlentities($rs->fields['codigo_cliente']);
			}?>" placeholder="Código del Cliente" class="form-control" required  />                    
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group"> 
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Persona</label>
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="codigo_persona" id="codigo_persona" value="<?php  if (isset($_POST['codigo_persona'])) {
			    echo htmlentities($_POST['codigo_persona']);
			} else {
			    echo htmlentities($rs->fields['codigo_persona']);
			}?>" placeholder="Código de Persona" class="form-control"   />                    
		</div>
	</div>

	<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Razón social *</label> <!-- Campo de Razon SocialL -->
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
			    echo htmlentities($_POST['razon_social']);
			} else {
			    echo htmlentities($rs->fields['razon_social']);
			}?>" placeholder="Razon social" class="form-control" required  />                    
		</div>
	</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre de Fantasia *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_POST['fantasia'])) {
	    echo htmlentities($_POST['fantasia']);
	} else {
	    echo htmlentities($rs->fields['fantasia']);
	}?>" placeholder="Fantasia" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group"> 
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control"   />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellido </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellido" id="apellido" value="<?php  if (isset($_POST['apellido'])) {
	    echo htmlentities($_POST['apellido']);
	} else {
	    echo htmlentities($rs->fields['apellido']);
	}?>" placeholder="Apellido" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="cedula" style="display: none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cédula</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="documento" id="documento" value="<?php if (isset($_POST['documento'])) {
            echo htmlentities($_POST['documento']);
        } else {
            echo htmlentities($rs->fields['documento']);
        }?>" placeholder="Cédula" class="form-control" />
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="rucespecial">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC Especial *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['ruc_especial'])) {
    $value_selected = htmlentities($_POST['ruc_especial']);
} else {
    $value_selected = 'N';
}
// opciones
$opciones = [
    'NO' => 'N',
    'SI (DIPLOMATICOS, ONG, ETC)' => 'S',
];
// parametros
$parametros_array = [
    'nombre_campo' => 'ruc_especial',
    'id_campo' => 'ruc_especial',

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
<div class="col-md-6 col-sm-6 form-group" id="ruc"> <!-- Campo RUC y validacion de RUC -->
	<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="carga_ruc_h();" class="btn btn-sm btn-default" title="Buscar" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span></a> RUC * </label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="ruc"value="<?php  if (isset($_POST['ruc'])) {
        echo htmlentities($_POST['ruc']);
    } else {
        echo htmlentities($ruc);
    }?>" placeholder="RUC" required class="form-control"  />	    
    <input type="hidden" name="idcliente" id="idcliente" value="<?php  if (isset($_POST['idcliente'])) {
        echo htmlentities($_POST['idcliente']);
    } else {
        echo htmlentities($rs->fields['idcliente']);
    }?>" placeholder="idcliente" required />
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Código EDI </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="codigoedi" id="codigoedi" value="<?php  if (isset($_POST['codigoedi'])) {
	    echo htmlentities($_POST['codigoedi']);
	} else {
	    echo htmlentities($rs->fields['codigoedi']);
	}?>" placeholder="Código EDI" class="form-control"  />                    
	</div>
</div>
	
	<!--<div class="clearfix"></div>-->

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
	}?>" placeholder="Celular" class="form-control"  />                    
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha de alta </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fechanac" id="fechanac" value="<?php  if (isset($_POST['fechanac'])) {
	    echo htmlentities($_POST['fechanac']);
	} else {
	    echo htmlentities($rs->fields['fechanac']);
	}?>" placeholder="Fechanac" class="form-control"  />                    
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
<hr>	
<h2>Datos Adicionales</h2>
<hr>	
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Edo. Cliente</label>
    <div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
        <input type="radio" name="opcion" value="1" onchange="mostrarOcultarCampoTexto()" <?php if (isset($_POST["opcion"]) && $_POST["opcion"] == "1") {
            echo "checked";
        } ?> checked> Activo<br>
        <input type="radio" name="opcion" value="0" onchange="mostrarOcultarCampoTexto()" <?php if (isset($_POST["opcion"]) && $_POST["opcion"] == "0") {
            echo "checked";
        } ?>> Inactivo <br>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12" id="motivo_label" style="display:none;">Motivo</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="motivo" id="motivo" value="<?php  if (isset($_POST['motivo'])) {
            echo htmlentities($_POST['motivo']);
        } else {
            echo htmlentities($rs->fields['motivo']);
        }?>" placeholder="Motivo" class="form-control" style="display:none;">                    
    </div>
</div>

<div class="clearfix"></div>	

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Vendedor</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // consulta
    $consulta = "
    SELECT idvendedor, nomape
    FROM vendedor
    where
    estado = 'A'
    order by nomape asc
     ";

// valor seleccionado
if (isset($_POST['idvendedor'])) {
    $value_selected = htmlentities($_POST['idvendedor']);
} else {
    $value_selected = htmlentities($rs->fields['idvendedor']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idvendedor',
    'id_campo' => 'idvendedor',

    'nombre_campo_bd' => 'nomape',
    'id_campo_bd' => 'idvendedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
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
    'id_campo_bd' => 'idcobrador',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  ',
    'autosel_1registro' => 'N'
];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Naturaleza Cliente</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    // consulta
    $consulta = "
    SELECT idnaturalezapersona, naturaleza_persona
    FROM naturaleza_persona
    order by naturaleza_persona asc
     ";

// valor seleccionado
if (isset($_POST['idnaturalezapersona'])) {
    $value_selected = htmlentities($_POST['idnaturalezapersona']);
} else {
    $value_selected = htmlentities($rs->fields['idnaturalezapersona']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idnaturalezapersona',
    'id_campo' => 'idnaturalezapersona',

    'nombre_campo_bd' => 'naturaleza_persona',
    'id_campo_bd' => 'idnaturalezapersona',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required"  ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="sexo" style="display: none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Sexo</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <select name="sexo" class="form-control">
            <option value="">Seleccionar...</option>
            <option value="1">Femenino</option>
            <option value="2">Masculino</option>
            <option value="3">Otro</option>
        </select>
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

<div class="clearfix"></div>
<hr>	
<h2>Datos Comerciales</h2>
<hr>	

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Crédito?</label>
    <div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
        <input type="checkbox" id="checkbox" name="credito" value="0" onchange="mostrarCampos()" <?php if (isset($_POST["credito"]) && $_POST["credito"] == "1") {
            echo "checked";
        } ?>>
        <label for="checkbox">No</label>
        <input type="hidden" name="credito" value="1"> <!-- Hidden field with value 1 -->
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group" id="tipoCredito" style="display: none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de Crédito</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
      <?php
      // consulta
      $consulta = "
      SELECT idcredito, descripcion
      FROM tipo_credito
      WHERE estado = '1'
      ";

// valor seleccionado
if (isset($_POST['idcredito'])) {
    $value_selected = htmlentities($_POST['idcredito']);
} else {
    $value_selected = htmlentities($rs->fields['idcredito']);
}

// parametros
$parametros_array = [
  'nombre_campo' => 'idcredito',
  'id_campo' => 'idcredito',

  'nombre_campo_bd' => 'descripcion',
  'id_campo_bd' => 'idcredito',

  'value_selected' => $value_selected,

  'pricampo_name' => 'Seleccionar...',
  'pricampo_value' => '',
  'style_input' => 'class="form-control"',
  'acciones' => '   ',
  'autosel_1registro' => 'S'
];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
  </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-24" id="limitecreditolabel" style="display: none;">Limite de Crédito</label>
        <div class="col-md-9 col-sm-9 col-xs-24">
            <input type="text" name="limite_credito" id="limite_credito" value="<?php  if (isset($_POST['limite_credito'])) {
                echo htmlentities($_POST['limite_credito']);
            } else {
                echo htmlentities($rs->fields['limite_credito']);
            }?>" placeholder="Limite de Credito" class="form-control" style="display: none;" />
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
    'id_campo_bd' => 'idtipo',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'
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
    'id_campo_bd' => 'idlistaprecio',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'
];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div> 

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cadena</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <?php
// consulta
$consulta = "
        SELECT idcadena, cadena
        FROM cadena
        WHERE estado = 'A'
         ";

// valor seleccionado
if (isset($_POST['idcadena'])) {
    $value_selected = htmlentities($_POST['idcadena']);
} else {
    $value_selected = htmlentities($rs->fields['idcadena']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idcadena',
    'id_campo' => 'idcadena',

    'nombre_campo_bd' => 'cadena',
    'id_campo_bd' => 'idcadena',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'
];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div> 


<div class="clearfix"></div>
<hr>	
<h2>Datos de Despacho</h2>
<hr>	
<br />
<div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-24">Dirección 1</label>
        <div class="col-md-9 col-sm-9 col-xs-24">
            <input type="text" name="direccion" id="direccion1" value="<?php  if (isset($_POST['direccion'])) {
                echo htmlentities($_POST['direccion']);
            } else {
                echo htmlentities($rs->fields['direccion']);
            }?>" placeholder="Direccion 1" class="form-control" />
        </div>
    </div>

	<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Casa </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="numero_casa" id="numero_casa" value="<?php  if (isset($_POST['numero_casa'])) {
	    echo htmlentities($_POST['numero_casa']);
	} else {
	    echo htmlentities($rs->fields['numero_casa']);
	}?>" placeholder="Numero de Casa 1" class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Departamento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
    SELECT iddepartamento, iddepartamento_set, descripcion as departamento
    FROM departamentos_propio
    order by descripcion asc
     ";

// valor seleccionado
if (isset($_POST['iddepartamento'])) {
    $value_selected = htmlentities($_POST['iddepartamento']);
} else {
    $value_selected = htmlentities($rs->fields['iddepartamento_set']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddepartamento',
    'id_campo' => 'iddepartamento',

    'nombre_campo_bd' => 'departamento',
    'id_campo_bd' => 'iddepartamento',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>          
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Distrito </label>
	<div class="col-md-9 col-sm-9 col-xs-12" id="distrito_box">
<?php
$consulta = "
SELECT iddistrito, distrito
FROM distrito
order by distrito asc
 ";

// valor seleccionado
if (isset($_POST['iddistrito'])) {
    $value_selected = htmlentities($_POST['iddistrito']);
} else {
    $value_selected = htmlentities($rs->fields['id_distrito']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddistrito',
    'id_campo' => 'iddistrito',

    'nombre_campo_bd' => 'distrito',
    'id_campo_bd' => 'iddistrito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>         
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ciudad </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta = "
    SELECT idciudad, nombre
    FROM ciudades
    order by nombre asc
     ";

// valor seleccionado
if (isset($_POST['idciudad'])) {
    $value_selected = htmlentities($_POST['idciudad']);
} else {
    $value_selected = htmlentities($rs->fields['idciudad']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idciudad',
    'id_campo' => 'idciudad',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idciudad',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?> 
	</div>
</div>

	<div class="clearfix"></div>
	<hr>

	<div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-24">Dirección 2</label>
        <div class="col-md-9 col-sm-9 col-xs-24">
            <input type="text" name="direccion2" id="direccion2" value="<?php  if (isset($_POST['direccion2'])) {
                echo htmlentities($_POST['direccion2']);
            } else {
                echo htmlentities($rs->fields['direccion2']);
            }?>" placeholder="Direccion 2" class="form-control" />
        </div>
    </div>

	<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Casa 2 </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="numero_casa2" id="numero_casa2" value="<?php  if (isset($_POST['numero_casa2'])) {
	    echo htmlentities($_POST['numero_casa2']);
	} else {
	    echo htmlentities($rs->fields['numero_casa2']);
	}?>" placeholder="Numero de Casa 2" class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Departamento 2</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
    SELECT iddepartamento, iddepartamento_set, descripcion as departamento
    FROM departamentos_propio
    order by descripcion asc
     ";

// valor seleccionado
if (isset($_POST['iddepartamento2'])) {
    $value_selected = htmlentities($_POST['iddepartamento2']);
} else {
    $value_selected = htmlentities($rs->fields['iddepartamento_set2']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddepartamento2',
    'id_campo' => 'iddepartamento2',

    'nombre_campo_bd' => 'departamento',
    'id_campo_bd' => 'iddepartamento',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>          
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Distrito 2</label>
	<div class="col-md-9 col-sm-9 col-xs-12" id="distrito_box2">
<?php

// consulta
$consulta = "
    SELECT iddistrito as iddistrito2, distrito as distrito2
    FROM distrito
    order by distrito asc
     ";

// valor seleccionado
if (isset($_POST['iddistrito2'])) {
    $value_selected = htmlentities($_POST['iddistrito2']);
} else {
    $value_selected = htmlentities($rs->fields['iddistrito2']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddistrito2',
    'id_campo' => 'iddistrito2',

    'nombre_campo_bd' => 'distrito2',
    'id_campo_bd' => 'iddistrito2',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>         
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ciudad </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta = "
    SELECT idciudad as idciudad2, nombre as nombre2
    FROM ciudades
    order by nombre2 asc
     ";

// valor seleccionado
if (isset($_POST['idciudad2'])) {
    $value_selected = htmlentities($_POST['idciudad2']);
} else {
    $value_selected = htmlentities($rs->fields['idciudad2']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idciudad2',
    'id_campo' => 'idciudad2',

    'nombre_campo_bd' => 'nombre2',
    'id_campo_bd' => 'idciudad2',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '   ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?> 
	</div>
</div>

<div class="clearfix"></div>
<hr>

<div class="col-md-6 col-sm-6 form-group">
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Dia de Visita</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <select name="dia_visita" class="form-control">
                <option value="">Seleccionar...</option>
                <option value="01">Lunes</option>
                <option value="02">Martes</option>
                <option value="03">Miércoles</option>
                <option value="04">Jueves</option>
                <option value="05">Viernes</option>
                <option value="06">Sábado</option>
                <option value="07">Domingo</option>
            </select> 
        </div>
    </div>
	
	<div class="clearfix"></div>
	<br>
	<hr>
	<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Fachada del Local</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="file" name="foto" id="foto" class="form-control" accept="image/*" onchange="previsualizarFoto(this)" />
    </div>
</div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Previsualización</label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="previsualizacion_foto" class="previsualizacion-foto">
        <img id="imagen_previsualizada" src="../gfx/productos/prod_0.jpg" class="img-thumbnail" width="350" />
		<input type="hidden" name="ruta_foto" id="ruta_foto" value="" />
	</div>
</div>
    </div>
  </div>
</div>
	  </div>
	  <div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
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