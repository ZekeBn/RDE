<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "216";
require_once("includes/rsusuario.php");


$idatc = intval($_GET['idatc']);
$idm = intval($_GET['idm']);

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
        'idvendedor' => '',
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


    ];


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

        header("location: mesas/cerrar_mesa.php?idatc=".$idatc.'&idm='.$idm.'&idcliente='.$idcliente);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
<script>
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
</script>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Agregar Cliente</h2>
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
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Razon social *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="razon_social" id="razon_social" value="<?php  if (isset($_POST['razon_social'])) {
	    echo htmlentities($_POST['razon_social']);
	} else {
	    echo htmlentities($rs->fields['razon_social']);
	}?>" placeholder="Razon social" class="form-control" required  />                    
	</div>
</div>


<div class="col-md-6 col-sm-6 form-group">

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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Direccion </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="direccion" id="direccion" value="<?php  if (isset($_POST['direccion'])) {
	    echo htmlentities($_POST['direccion']);
	} else {
	    echo htmlentities($rs->fields['direccion']);
	}?>" placeholder="Direccion" class="form-control"  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha nacimiento </label>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idsucu'])) {
    $value_selected = htmlentities($_POST['idsucu']);
} else {
    $value_selected = htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'TODAS',
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

<?php /*if($permite_ruc_duplicado == 'S'){ ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Forzar Duplicidad del RUC *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// valor seleccionado
if(isset($_POST['forzar_duplicidad'])){
    $value_selected=htmlentities($_POST['forzar_duplicidad']);
}else{
    $value_selected='N';
}
// opciones
$opciones=array(
    'SI' => 'S',
    'NO' => 'N'
);
// parametros
$parametros_array=array(
    'nombre_campo' => 'forzar_duplicidad',
    'id_campo' => 'forzar_duplicidad',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

);

// construye campo
echo campo_select_sinbd($parametros_array);

?>
    </div>
</div>
<?php } */?>
<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='mesas/cerrar_mesa.php?idatc=<?php echo $idatc; ?>&idm=<?php echo $idm; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
