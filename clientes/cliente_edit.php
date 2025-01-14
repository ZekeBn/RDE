<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");


$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from cliente 
where 
idcliente = $idcliente
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rs->fields['idcliente']);
$permite_credito = intval($rs->fields['permite_credito']);
$estado = intval($rs->fields['estado']);

//echo $permite_credito; exit;

if ($permite_credito == 1) {
    $mostrar_tipocredito = true; // Mostrar el combobox tipocredito
    $mostrar_limite_credito = true; // Mostrar el textbox limite_credito
} else {
    $mostrar_tipocredito = false; // No mostrar el combobox tipocredito
    $mostrar_limite_credito = false; // No mostrar el textbox limite_credito
}

if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}
// preferencias caja
$consulta = "
SELECT 
valida_ruc
FROM preferencias_caja 
WHERE  
idempresa = $idempresa 
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$valida_ruc = trim($rsprefcaj->fields['valida_ruc']);

// cliente generico
$consulta = "
select ruc, razon_social
from cliente 
where 
borrable='N'
limit 1
";
$rscligen = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$ruc_pred = $rscligen->fields['ruc'];
$razon_social_pred = $rscligen->fields['razon_social'];

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

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
    $codigo_cliente = antisqlinyeccion($_POST['codigo_cliente'], "int");
    $codigo_persona = antisqlinyeccion($_POST['codigo_persona'], "int");
    $idsexo = antisqlinyeccion($_POST['idsexo'], "int");
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $apellido = antisqlinyeccion($_POST['apellido'], "text");
    $nombre_corto = antisqlinyeccion('', "text");
    $idtipdoc = antisqlinyeccion(1, "int");
    $documento = antisqlinyeccion($_POST['documento'], "text");
    $ruc = antisqlinyeccion($_POST['ruc'], "text");
    $telefono = antisqlinyeccion($_POST['telefono'], "text");
    $celular = antisqlinyeccion($_POST['celular'], "text");
    $email = antisqlinyeccion($_POST['email'], "text");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $comentario = antisqlinyeccion($_POST['comentario'], "text");
    $fechanac = antisqlinyeccion($_POST['fechanac'], "text");
    $tipocliente = antisqlinyeccion($_POST['idclientetipo'], "int");
    $razon_social = antisqlinyeccion($_POST['razon_social'], "text");
    $fantasia = antisqlinyeccion($_POST['fantasia'], "text");
    $estado = 1;
    $registrado_el = antisqlinyeccion($ahora, "text");
    $registrado_por = $idusu;
    $ruc_especial = antisqlinyeccion($_POST['ruc_especial'], "text");
    if ($_POST['ruc_especial'] == 'S') {
        $carnet_diplomatico = $ruc;
    } else {
        $carnet_diplomatico = antisqlinyeccion('', "text");
    }
    $idvendedor = antisqlinyeccion($_POST['idvendedor'], "text");
    $idcanalventacli = antisqlinyeccion($_POST['idcanalventa'], "int");
    $porce_desc = antisqlinyeccion($_POST['porce_desc'], "float");
    $sucursal = antisqlinyeccion($_POST['idsucu'], "int");
    $idclientecat = antisqlinyeccion($_POST['idclientecat'], "int");
    $numero_casa = antisqlinyeccion($_POST['numero_casa'], "int");
    $idnaturalezapersona = antisqlinyeccion($_POST['idnaturalezapersona'], "int");

    $numero_casa = antisqlinyeccion($_POST['numero_casa'], "int");
    $departamento = antisqlinyeccion($_POST['departamento'], "int");
    $iddistrito = antisqlinyeccion($_POST['iddistrito'], "int");
    $idciudad = antisqlinyeccion($_POST['idciudad'], "int");
    $idtipooperacionset = antisqlinyeccion($_POST['idtipooperacionset'], "int");
    $idcobrador = antisqlinyeccion($_POST['idcobrador'], "int");
    $idcredito = antisqlinyeccion($_POST['idcredito'], "int");
    $limite_credito = antisqlinyeccion($_POST['limite_credito'], "int");
    $idmoneda = antisqlinyeccion($_POST['idtipo'], "int");
    $idcadena = antisqlinyeccion($_POST['idcadena'], "int");
    $idlistaprecio = antisqlinyeccion($_POST['idlistaprecio'], "int");
    $dia_visita = antisqlinyeccion($_POST['iddia'], "int");


    if (trim($_POST['ruc']) == '') {
        $valido = "N";
        $errores .= " - El campo ruc no puede estar vacio.<br />";
    }
    if (intval($_POST['idclientetipo']) == 0) {
        $valido = "N";
        $errores .= " - El campo tipo de cliente no puede estar vacio.<br />";
    }

    if (trim($_POST['razon_social']) == '') {
        $valido = "N";
        $errores .= " - El campo razon_social no puede estar vacio.<br />";
    }
    // si es una persona
    if (intval($_POST['idclientetipo']) == 1) {
        if (trim($_POST['nombre']) == '') {
            $valido = "N";
            $errores .= " - El campo nombre no puede estar vacio.<br />";
        }
        if (trim($_POST['apellido']) == '') {
            $valido = "N";
            $errores .= " - El campo apellido no puede estar vacio.<br />";
        }
    }
    // si es una empresa
    if (intval($_POST['idclientetipo']) == 2) {
        if (trim($_POST['fantasia']) == '') {
            $valido = "N";
            $errores .= " - El campo fantasia no puede estar vacio cuando es una persona juridica.<br />";
        }
        $nombre = "NULL";
        $apellido = "NULL";
    }
    // conversiones
    if (intval($_POST['idtiporeceptor_set']) == 0) {
        $idtiporeceptor_set = 1;
    }
    if ($ruc_pred == trim($_POST['ruc'])) {
        $idtiporeceptor_set = 2;
    }
    if ($_POST['ruc_especial'] == 'S') {
        $idtiporeceptor_set = 2;
    }
    // validar que el ruc no exista excepto si es el ruc generico
    $consulta = "
	select * 
	from cliente 
	where 
	ruc = $ruc 
	and estado = 1
	and borrable = 'S'
	and idcliente <> $idcliente
	and ruc <> '$ruc_pred'
	limit 1
	";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsex->fields['idcliente'] > 0) {
        $valido = "N";
        $errores .= " - Ya existe un cliente con el ruc ingresado, editelo para evitar duplicidad.<br />";
    }
    if ($_POST['documento'] > 0) {
        // validar documento
        $consulta = "
		select * 
		from cliente 
		where 
		documento = $documento
		and estado = 1
		and borrable = 'S'
		and idcliente <> $idcliente
		limit 1
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rsex->fields['idcliente'] > 0) {
            $valido = "N";
            $errores .= " - Ya existe un cliente con el documento ingresado, editelo para evitar duplicidad.<br />";
        }
    }
    if (intval($_POST['idcanalventa']) > 0) {
        if (intval($_POST['idvendedor']) == 0) {
            $valido = "N";
            $errores .= " - Debe indicar el vendedor cuando se registra un canal de venta.<br />";
        }
    }
    if (intval($_POST['porce_desc']) > 100) {
        $valido = "N";
        $errores .= " - El porcentaje de descuento automatico no puede ser mayor a 100.<br />";
    }
    if (intval($_POST['porce_desc']) < 0) {
        $valido = "N";
        $errores .= " - El porcentaje de descuento automatico no puede ser menor a 0.<br />";
    }


    // conversiones
    if (intval($_POST['porce_desc']) == 0) {
        $porce_desc = antisqlinyeccion('', "float");
    }

    // validaciones facturador electronico
    if ($facturador_electronico == 'S') {
        if (trim($_POST['direccion']) != '') {
            if (intval($_POST['numero_casa']) == 0) {
                $valido = "N";
                $errores .= " - El campo numero casa no puede estar vacio cuando se completa el domicilio.<br />";
            }
            if (intval($_POST['numero_casa']) < 100) {
                $valido = "N";
                $errores .= " - El campo numero casa debe ser mayor o igual a 100 cuando se completa el domicilio.<br />";
            }
        }

    }



    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		update cliente
		set
			tipocliente=$tipocliente,
			codigo_cliente=$codigo_cliente,
			codigo_persona=$codigo_persona,
			nombre=$nombre,
			apellido=$apellido,
			documento=$documento,
			ruc=$ruc,
			telefono=$telefono,
			celular=$celular,
			email=$email,			
			comentario=$comentario,
			fechanac=$fechanac,
			razon_social=$razon_social,
			fantasia=$fantasia,
			actualizado_el='$ahora',
			actualizado_por=$idusu,
			diplomatico = $ruc_especial,
			carnet_diplomatico=$carnet_diplomatico,
			idvendedor=$idvendedor,
			sucursal=1,
			idclientecat=$idclientecat,
			direccion=$direccion,
			numero_casa=$numero_casa,
			departamento=$departamento,
			id_distrito=$iddistrito,
			idciudad=$idciudad,
			direccion2=$direccion,
			numero_casa2=$numero_casa,
			departamento2=$departamento,
			iddistrito2=$iddistrito,
			idciudad2=$idciudad,
			sexo=$idsexo,
			idcobrador=$idcobrador,
			idnaturalezapersona=$idnaturalezapersona,
			permite_credito=$permite_credito,
			idcredito=$idcredito,
			limite_credito=$limite_credito,
			idmoneda=$idmoneda,
			idcadena=$idcadena,
			idlistaprecio=$idlistaprecio,
			dia_visita=$dia_visita
			
		where
			idcliente = $idcliente
			and estado = 1
			and borrable = 'S'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //actualizar los datos SOLO de laa primera sucursal disponible (MATRIZ)

        $buscar = "Select * from sucursal_cliente where idcliente=$idcliente and estado=1 order by idsucursal_clie ASC LIMIT 1";
        $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idsucursal_clie = intval($rs->fields['idsucursal_clie']);

        if ($tipocliente == 1) {
            $nf = trim($_POST['nombre'].' '.$_POST['apellido']);
        } else {

            $nf = trim($_POST['razon_social']);
        }

        if ($idsucursal_clie > 0) {
            $update = "update sucursal_cliente set sucursal='$nf',direccion=$direccion,mail=$email,telefono=$celular where idcliente=$idcliente and idsucursal_clie=$idsucursal_clie";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

        }

        // para la set
        $consulta = "
		update cliente 
		set 
		idtiporeceptor_set = 2 
		where 
		idcliente = $idcliente
		and (ruc = 'X' or borrable = 'N')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


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

	function cliente_tipo(tipo){
	// persona fisica
	if(tipo == 1){
		$("#nombre").show();
		$("#apellido").show();
		$("#documento").show();
		$("#sexo").show();
		//$("#fantasia_box").hide();
	// persona juridica
	}else{
		$("#nombre").hide();
		$("#apellido").hide();
		$("#documento").hide();
		$("#sexo").hide();
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

function mostrarOcultarCampoTexto() {
    var motivoContainer = document.getElementById("motivo_container");
    var motivoInput = document.getElementById("motivo");
    var opcionActiva = document.querySelector('input[name="opcion"]:checked');
    var estadoCliente = null;

    if (opcionActiva) {
        estadoCliente = opcionActiva.value;

        if (estadoCliente === "1") {
            motivoContainer.style.display = "none"; // Ocultar el contenedor del campo de motivo
            motivoInput.style.display = "none"; // Ocultar el campo de motivo
        } else {
            motivoContainer.style.display = "block"; // Mostrar el contenedor del campo de motivo
            motivoInput.style.display = "block"; // Mostrar el campo de motivo
        }
    }
}

// Llamar a la función para asegurarse de que el campo de motivo esté correctamente configurado al cargar la página
window.addEventListener("DOMContentLoaded", function() {
    mostrarOcultarCampoTexto();
});

 // Definir la función mostrarCampos
 function mostrarCampos() {
        var permite_credito = document.getElementById("checkbox1").checked ? 1 : 0;
        var mensajePermiteCredito = document.getElementById("mensajePermiteCredito");
        var tipocredito = document.getElementById("tipocredito");
        var limiteCreditoInput = document.getElementById("limite_credito");

        if (permite_credito == 0) {
            mensajePermiteCredito.textContent = "No permite crédito, marque para autorizar";
            tipocredito.style.display = "none"; // Ocultar el combobox tipocredito
            limiteCreditoInput.style.display = "none"; // Ocultar el textbox limite_credito
        } else {
            mensajePermiteCredito.textContent = "Permite Crédito";
            tipocredito.style.display = "block"; // Mostrar el combobox tipocredito
            limiteCreditoInput.style.display = "block"; // Mostrar el textbox limite_credito
        }
    }

    // Ejecutar la función mostrarCampos() después de cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        mostrarCampos(); // Llama a la función mostrarCampos al cargar la página
    });

    // Ejecutar la función mostrarCampos cada vez que cambie el estado del checkbox
    document.getElementById("checkbox1").addEventListener("change", mostrarCampos);

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

  <body class="nav-md" onLoad="cliente_tipo('<?php echo $rs->fields['tipocliente'] ?>');">
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
                    <h2>Editar Cliente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
				<div class="clearfix"></div>
<br /><br />
<div class="alert alert-warning alert-dismissible fade in" role="alert">
<strong>AVISO:</strong><br />
Si usted es facturador electronico y desea que se informe la direccion debe completar obligatoriamente: direccion, departamento, distrito, ciudad y numero de casa, en caso que alguno de los 5 no este cargado, se registrara en su sistema pero no se informara en la factura electronica.
</div>
<br /><br />
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo cliente *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
    $tipocliente = $rs->fields['tipocliente'];
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
    $value_selected = htmlentities($rs->fields['tipocliente']);
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
    'autosel_1registro' => 'S'

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

<div class="col-md-6 col-sm-6 form-group" id="nombre"> 
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control"   />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="apellido">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellido </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellido" value="<?php  if (isset($_POST['apellido'])) {
	    echo htmlentities($_POST['apellido']);
	} else {
	    echo htmlentities($rs->fields['apellido']);
	}?>" placeholder="Apellido" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="documento">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cédula</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="documento" value="<?php if (isset($_POST['documento'])) {
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
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Ruc *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
	    echo htmlentities($_POST['ruc']);
	} else {
	    echo htmlentities($rs->fields['ruc']);
	}?>" placeholder="Ruc" class="form-control"  required  />                    
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
	    echo  htmlentities($_POST['celular']);
	} else {
	    echo  htmlentities($rs->fields['celular']);
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
	}?>" placeholder="Fecha de Alta" class="form-control" />                    
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
        <input type="radio" name="opcion" value="1" onchange="mostrarOcultarCampoTexto()" <?php if ((isset($_POST["opcion"]) && $_POST["opcion"] == "1") || $estado == 1) {
            echo "checked";
        } ?>> Activo<br>
        <input type="radio" name="opcion" value="2" onchange="mostrarOcultarCampoTexto()" <?php if ((isset($_POST["opcion"]) && $_POST["opcion"] != "1") || ($estado != 1 && $estado != null)) {
            echo "checked";
        } ?>> Inactivo <br>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="motivo_container" style="display:none;">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Motivo</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="motivo" id="motivo" value="<?php  if (isset($_POST['motivo'])) {
            echo htmlentities($_POST['motivo']);
        } else {
            echo htmlentities($rs->fields['motivo']);
        }?>" placeholder="Motivo" class="form-control">                    
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

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sexo</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<?php
// consulta
$consulta = "
    SELECT idsexo, sexo
    FROM sexo
    order by sexo asc
     ";

// valor seleccionado
if (isset($_POST['idsexo'])) {
    $value_selected = htmlentities($_POST['idsexo']);
} else {
    $value_selected = htmlentities($rs->fields['sexo']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsexo',
    'id_campo' => 'idsexo',

    'nombre_campo_bd' => 'sexo',
    'id_campo_bd' => 'idsexo',

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
    <label class="control-label col-md-3 col-sm-3 col-xs-12">¿Permite Crédito?</label>
    <div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
        <input type="checkbox" id="checkbox1" name="credito" value="1" onchange="mostrarCampos()" <?php if ($permite_credito == 1) {
            echo "checked";
        } ?>>
        <span id="mensajePermiteCredito"><?php echo ($permite_credito == 1) ? "Permite Crédito" : "No permite crédito, marque para autorizar" ; ?></span>
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group" id="tipocredito">
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

<div class="col-md-6 col-sm-6 form-group" id="limite_credito">
        <label class="control-label col-md-3 col-sm-3 col-xs-24">Limite de Crédito</label>
        <div class="col-md-9 col-sm-9 col-xs-24">
            <input type="text" name="limite_credito" value="<?php  if (isset($_POST['limite_credito'])) {
                echo htmlentities($_POST['limite_credito']);
            } else {
                echo htmlentities($rs->fields['limite_credito']);
            }?>" placeholder="Limite de Credito" class="form-control"/>
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
    order by descripcion asc
     ";

// valor seleccionado
if (isset($_POST['idtipo'])) {
    $value_selected = htmlentities($_POST['idtipo']);
} else {
    $value_selected = htmlentities($rs->fields['idmoneda']);
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
    'acciones' => ' required="required"  ',
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
    order by lista_precio asc
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
    'acciones' => ' required="required"  ',
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
    $value_selected = htmlentities($rs->fields['departamento']);
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
    $value_selected = htmlentities($rs->fields['departamento2']);
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
<?php

// consulta
$consulta = "
    SELECT iddia, descripcion
    FROM dias
    order by descripcion asc
     ";

// valor seleccionado
if (isset($_POST['iddia'])) {
    $value_selected = htmlentities($_POST['iddia']);
} else {
    $value_selected = htmlentities($rs->fields['dia_visita']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddia',
    'id_campo' => 'iddia',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddia',

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
   

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<hr />
					  

<div class="clearfix"></div>
<br /><br />

<div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Sucursales del Cliente</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
				  	<?php

                    $buscar = "Select * from sucursal_cliente where idcliente=$idcliente order by idsucursal_clie ASC";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
$treg = $rs->RecordCount();
if ($treg > 0) {

    ?>
				  
				  
					<div class="table-responsive">
					<table width="100%" class="table table-bordered jambo_table bulk_action">
					  <thead>
						<tr>
							
							<th align="center">Ids</th>
							<th align="center">Descripcion</th>
							<th align="center">Celular</th>
							<th align="center">Email</th>
							<th align="center">Direccion</th>
						</tr>
					  </thead>
					  <tbody>
						<?php
        while (!$rs->EOF) {
            ?>
						<tr>
							<td><?php echo formatomoneda($rs->fields['idsucursal_clie']);  ?></td>
							<td align="center"><?php echo $rs->fields['sucursal']; ?></td>
							<td align="center"><?php echo $rs->fields['telefono']; ?></td>
							<td align="center"><?php echo $rs->fields['mail']; ?></td>
							<td align="center"><?php echo $rs->fields['direccion']; ?></td>
						</tr>
					




						<?php
                $rs->MoveNext();
        }
    ?>
						</tbody>
					</table>
				</div>
						<?php } ?>
                  </div>
                </div>
              </div>
</div>
            <!-- SECCION --> 

			
			
			
                  </div>
                </div>
              </div>
            </div>
            
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
