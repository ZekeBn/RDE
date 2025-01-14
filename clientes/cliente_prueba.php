<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// echo "hola";exit;
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";

$dirsup = "S";
require_once("../includes/rsusuario.php");

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


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
  
<script>

document.addEventListener("DOMContentLoaded", function() {
  const openModalBtn = document.getElementById("openModalBtn");
  const modal = document.getElementById("myModal");
  const closeBtn = modal.querySelector(".close");
  const tabs = modal.querySelectorAll(".tabs .tab");
  const tabContent = modal.querySelector("#tabContent");
  const saveBtn = modal.querySelector("#saveBtn");

  openModalBtn.addEventListener("click", function() {
    modal.style.display = "block";
    showTab(0);
  });

  closeBtn.addEventListener("click", function() {
    modal.style.display = "none";
  });

  function showTab(tabIndex) {
    tabContent.querySelectorAll(".tabContent").forEach(function(tab) {
      tab.style.display = "none";
    });
    tabContent.querySelector("#" + tabs[tabIndex].dataset.tab + "Content").style.display = "block";
  }

  tabs.forEach(function(tab, index) {
    tab.addEventListener("click", function() {
      showTab(index);
    });
  });

  saveBtn.addEventListener("click", function() {
    // Obtener el valor seleccionado del combo box "Tipo Moneda" en el tab 3
    const tipoMonedaSelect = document.querySelector("#tabContent #tab3Content .col-md-9 select");
    const idTipoMonedaSeleccionada = tipoMonedaSelect.options[tipoMonedaSelect.selectedIndex].value;
    
    // Obtener el valor de nombre_box en el tab1
    const nombreBoxValue = document.querySelector("#tabContent #tab1Content #nombre_box input[name='nombre']").value;
    
    // Obtener el valor de direccion1 en el tab4
    const direccion1Value = document.querySelector("#tabContent #tab4Content #direccion1").value;
    
    alert("ID del tipo de moneda seleccionada (en el tab 3): " + idTipoMonedaSeleccionada + "\nValor de nombre_box (en el tab 1): " + nombreBoxValue + "\nValor de direccion1 (en el tab 4): " + direccion1Value);
  });
});

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
    var motivoLabel = document.getElementById("motivo_label");
    var motivoInput = document.getElementById("motivo");
    var opcionActiva = document.querySelector('input[name="opcion"]:checked');
	var estadoCliente = null;

    if (opcionActiva) {
        estadoCliente = opcionActiva.value;

        if (estadoCliente === "opcion1") {
            motivoLabel.style.visibility = "hidden";
            motivoInput.style.visibility = "hidden";
			estadoCliente='01';
        } else {
            motivoLabel.style.visibility = "visible";
            motivoInput.style.visibility = "visible";
			estadoCliente='02';
        }
		return estadoCliente;
}}

function mostrarCampos() {
    var checkbox = document.getElementById("checkbox");
    var limiteCreditoLabel = document.getElementById("limite_credito_label");
    var limiteCreditoInput = document.getElementById("limite_credito");
    var toleranciaLabel = document.getElementById("tolerancia_label");
    var toleranciaInput = document.getElementById("tolerancia");
    var diasCreditoLabel = document.getElementById("dias_credito_label");
    var diasCreditoInput = document.getElementById("dias_credito");

    if (checkbox.checked) {
        limiteCreditoLabel.style.visibility = "visible";
        limiteCreditoInput.style.visibility = "visible";
        toleranciaLabel.style.visibility = "visible";
        toleranciaInput.style.visibility = "visible";
        diasCreditoLabel.style.visibility = "visible";
        diasCreditoInput.style.visibility = "visible";
        document.querySelector('label[for="checkbox"]').innerText = "Si";
    } else {
        limiteCreditoLabel.style.visibility = "hidden";
        limiteCreditoInput.style.visibility = "hidden";
        toleranciaLabel.style.visibility = "hidden";
        toleranciaInput.style.visibility = "hidden";
        diasCreditoLabel.style.visibility = "hidden";
        diasCreditoInput.style.visibility = "hidden";
		limiteCreditoInput.value = "";
		toleranciaInput.value ="";
		diasCreditoInput.value ="";
        document.querySelector('label[for="checkbox"]').innerText = "No";
    }
	}

</script>


<style type="text/css">
/* Estilos del modal */

.form-control-small {
    		width: 60%;
 		}
		.modal {
		display: none;
		position: fixed;
		z-index: 1;
		left: 50%;
		top: 50%;
		transform: translate(-50%, -50%);
		width: 80%; /* Ancho máximo del modal */
		max-width: 800px; /* Opcional: define un ancho máximo específico */
		overflow: auto;
		background-color: rgba(0, 0, 0, 0.4);
		}
		.modal-content {
		height: auto !important;
		max-height: 80vh;
		overflow-y: auto;
		}

		.modal-dialog {
		width: 90%; /* Ancho del modal */
		max-width: 800px; /* Ancho máximo del modal */
		margin: 0 auto; /* Centrado horizontal */
		}

		.close {
		color: #aaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
		cursor: pointer;
		}

		.close:hover,
		.close:focus {
		color: black;
		text-decoration: none;
		}

		/* Estilos de las pestañas */
		.tab {
		overflow: hidden;
		border-bottom: 1px solid #ccc;
		}

		.tab button {
		background-color: inherit;
		float: left;
		border: none;
		outline: none;
		cursor: pointer;
		padding: 10px 20px;
		transition: 0.3s;
		}

		.tab button:hover {
		background-color: #ddd;
		}

		.tab button.active {
		background-color: #ccc;
		}

		/* Estilos del contenido de las pestañas */
		.tabcontent {
		display: none;
		padding: 20px;
		border: 1px solid #ccc;
		}

		.show {
		display: block;
		}
</style>

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
                    <h2>Datos Plantilla</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <!-- :::::::::::::::::::::::::::AQUI SE COLOCA EL HTML:::::::::::::::::::::::::::::: -->
                  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
                       <div class="tabs">
                       <button class="tab" data-tab="tab1">Datos Generales</button>
                       <button class="tab" data-tab="tab2">Mas Datos</button>
                       <button class="tab" data-tab="tab3">Datos Comerciales</button>
                       <button class="tab" data-tab="tab4">Datos de Despacho</button>
                      </div>
                   <div id="tabContent">
                   <div class="tabContent" id="tab1Content">
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

	<div class="col-md-6 col-sm-6 form-group" id="codigo_persona_box"> 
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
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social *</label> <!-- Campo de Razon SocialL -->
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
			    echo htmlentities($_POST['razon_social']);
			} else {
			    echo htmlentities($rs->fields['razon_social']);
			}?>" placeholder="Razon social" class="form-control" required  />                    
		</div>
	</div>

<div class="col-md-6 col-sm-6 form-group" id="fantasia_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre de Fantasia *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fantasia" id="fantasia" value="<?php  if (isset($_POST['fantasia'])) {
	    echo htmlentities($_POST['fantasia']);
	} else {
	    echo htmlentities($rs->fields['fantasia']);
	}?>" placeholder="Fantasia" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="nombre_box"> 
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre" id="nombre" value="<?php  if (isset($_POST['nombre'])) {
	    echo htmlentities($_POST['nombre']);
	} else {
	    echo htmlentities($rs->fields['nombre']);
	}?>" placeholder="Nombre" class="form-control"   />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="apellido_box">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Apellido </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="apellido" id="apellido" value="<?php  if (isset($_POST['apellido'])) {
	    echo htmlentities($_POST['apellido']);
	} else {
	    echo htmlentities($rs->fields['apellido']);
	}?>" placeholder="Apellido" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Documento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="documento" id="documento" value="<?php  if (isset($_POST['documento'])) {
	    echo htmlentities($_POST['documento']);
	} else {
	    echo htmlentities($rs->fields['documento']);
	}?>" placeholder="Documento" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
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
    'autosel_1registro' => 'N',
    'opciones' => $opciones
];

// construye campo
echo campo_select_sinbd($parametros_array);

?>
	</div>
</div>
<div class="col-md-6 col-sm-6 form-group"> <!-- Campo RUC y validacion de RUC -->
	<label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="carga_ruc_h();" class="btn btn-sm btn-default" title="Buscar" data-toggle="tooltip" data-placement="right"  data-original-title="Buscar"><span class="fa fa-search"></span></a> RUC * </label>
	<div class="col-md-9 col-sm-9 col-xs-12">                    
    <input type="text" name="ruc" id="ruc" value="<?php  if (isset($_POST['ruc'])) {
        echo htmlentities($_POST['ruc']);
    } else {
        echo htmlentities($ruc_pred);
    }?>" placeholder="ruc" required class="form-control"  />	    
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
	<input type="text" name="codigoEDI" id="codigoEDI" value="<?php  if (isset($_POST['codigoEDI'])) {
	    echo htmlentities($_POST['codigoEDI']);
	} else {
	    echo htmlentities($rs->fields['codigoEDI']);
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
	} else {
	    echo floatval($rs->fields['celular']);
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
                    </div>
                   <div class="tabContent" id="tab2Content">
                   <div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Estado del Cliente</label>
    <div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
        <input type="radio" name="opcion" value="opcion1" onchange="mostrarOcultarCampoTexto()" checked> Activo<br>
        <input type="radio" name="opcion" value="opcion2" onchange="mostrarOcultarCampoTexto()"> Inactivo <br>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12" id="motivo_label" style="visibility:hidden;">Motivo</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="motivo" id="motivo" placeholder="Motivo" class="form-control" style="visibility:hidden;">                    
    </div>
</div>

<div class="clearfix"></div>	

<div class="col-md-6 col-xs-12 form-group" >
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
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

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
    'nombre_campo' => 'cobrador',
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


<div class="clearfix"></div>
<br />
                   </div>
                   <div class="tabContent" id="tab3Content">
                   <div class="col-md-6 col-sm-6 form-group">
    	<label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Crédito?</label>
    	<div class="col-md-9 col-sm-9 col-xs-12 checkbox-container">
        	<input type="checkbox" id="checkbox" onchange="mostrarCampos()">
        	<label for="checkbox">No</label>
    	</div>
	</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12" id="limite_credito_label" style="visibility: hidden;">Límite de Crédito</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="limite_credito" id="limite_credito" placeholder="Límite Crédito" class="form-control" style="visibility: hidden;">
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12" id="tolerancia_label" style="visibility: hidden;">Dias de Tolerancia</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="tolerancia" id="tolerancia" placeholder="Dias Tolerancia" class="form-control" style="visibility: hidden;">                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12" id="dias_credito_label" style="visibility: hidden;">Días Crédito</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="dias_credito" id="dias_credito" placeholder="Días Crédito" class="form-control" style="visibility: hidden;">                    
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
    'id_campo_bd' => 'lista_precio',

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

                    </div>
                    <div class="tabContent" id="tab4Content" id="direccion1">
                    <div class="col-md-6 col-sm-6 form-group">
                      <label class="control-label col-md-3 col-sm-3 col-xs-24">Dirección 1</label>
                         <div class="col-md-9 col-sm-9 col-xs-24">
                            <input type="text" name="direccion1" id="direccion1" value="<?php  if (isset($_POST['direccion1'])) {
                                echo htmlentities($_POST['direccion1']);
                            } else {
                                echo htmlentities($rs->fields['direccion1']);
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
    SELECT id as iddepartamento, descripcion as departamento
    FROM departamentos
    order by descripcion asc
     ";

// valor seleccionado
if (isset($_POST['departamento'])) {
    $value_selected = htmlentities($_POST['departamento']);
} else {
    $value_selected = htmlentities($rs->fields['departamento']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'departamento',
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Numero Casa </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="numero_casa2" id="numero_casa2" value="<?php  if (isset($_POST['numero_casa2'])) {
	    echo htmlentities($_POST['numero_casa2']);
	} else {
	    echo htmlentities($rs->fields['numero_casa2']);
	}?>" placeholder="Numero de Casa 2" class="form-control"  />                    
	</div>
</div>
	
<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Departamento </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
    SELECT id as iddepartamento, descripcion as departamento
    FROM departamentos
    order by descripcion asc
     ";

// valor seleccionado
if (isset($_POST['departamento2'])) {
    $value_selected = htmlentities($_POST['departamento2']);
} else {
    $value_selected = htmlentities($rs->fields['departamento2']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'departamento',
    'id_campo' => 'departamento',

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
	<div class="col-md-9 col-sm-9 col-xs-12" id="distrito_box2">
<?php

// consulta
$consulta = "
    SELECT iddistrito, distrito
    FROM distrito
    order by distrito asc
     ";

// valor seleccionado
if (isset($_POST['iddistrito'])) {
    $value_selected = htmlentities($_POST['iddistrito2']);
} else {
    $value_selected = htmlentities($rs->fields['id_distrito2']);
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
if (isset($_POST['idciudad2'])) {
    $value_selected = htmlentities($_POST['idciudad2']);
} else {
    $value_selected = htmlentities($rs->fields['idciudad2']);
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
        <label class="control-label col-md-3 col-sm-3 col-xs-12">Dia de Visita</label>
        <div class="col-md-9 col-sm-9 col-xs-12">
            <select name="dia_visita" class="form-control">
                <option value="">Seleccionar...</option>
                <option value="Lunes">Lunes</option>
                <option value="Martes">Martes</option>
                <option value="Miércoles">Miércoles</option>
                <option value="Jueves">Jueves</option>
                <option value="Viernes">Viernes</option>
                <option value="Sábado">Sábado</option>
                <option value="Domingo">Domingo</option>
            </select> 
        </div>
    </div>

	<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Frec. de Visita </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="frecuencia" id="frecuencia" value="<?php  if (isset($_POST['frecuencia'])) {
	    echo htmlentities($_POST['frecuencia']);
	} else {
	    echo htmlentities($rs->fields['frecuencia']);
	}?>" placeholder="Frecuencia de Visitas" class="form-control"  />                    
	</div>
</div>
                    </div>
                   </div>
                  <button id="saveBtn">Guardar</button>
                 </div>
                </div>
	


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
