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
select *,
(select clientetipo from cliente_tipo where cliente_tipo.idclientetipo=cliente.tipocliente) as tipocliente_desc
from cliente 	
where 
idcliente = $idcliente
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rs->fields['idcliente']);
$tipocliente = intval($rs->fields['tipocliente']);
if ($idcliente == 0) {
    header("location: cliente.php");
    exit;
}
//echo $tipocliente; exit;



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
    $idvendedor = $_POST['idvendedor'];
    $tipocliente = $_POST['tipocliente'];

    $estado = 1;
    $actualizado_por = $idusu;
    $actualizado_el = $ahora;





    $parametros_array = [
        'idusu' => $idusu,
        'idcliente' => $idcliente,
        'idvendedor' => $_POST['idvendedor'],
        'registrado_el' => $registrado_el,
        'registrado_por' => $registrado_por,


    ];

    // si todo es correcto actualiza
    if ($valido == "S") {
        $res = cliente_edit($parametros_array);
        if ($res["valido"] == "S") {
            header("location: cliente.php");
            exit;
        } else {
            $errores .= $res["errores"];
        }

    } else {
        $errores .= $res["errores"];
    }


}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());

// se puede mover esta funcion al archivo funciones_cliente.php y realizar un require_once
function cliente_edit($parametros_array)
{
    global $conexion;
    global $saltolinea;

    // validaciones basicas
    $valido = "S";
    $errores = "";

    $idvendedor = antisqlinyeccion($parametros_array['idvendedor'], "int");
    $idcliente = antisqlinyeccion($parametros_array['idcliente'], "int");
    //echo $idcliente; exit;

    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update cliente
		set
			idvendedor=$idvendedor
		where
			idcliente = $idcliente
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }


    return ["errores" => $errores,"valido" => $valido];
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>

<script>

// Función para ocultar o mostrar campos dependiendo del tipo de cliente
function toggleCamposCliente(tipocliente) {
        if (tipocliente == 1) {
            // Mostrar campos nombre, apellido y documento
            document.getElementById('nombreDiv').style.display = 'block';
            document.getElementById('apellidoDiv').style.display = 'block';
            document.getElementById('documentoDiv').style.display = 'block';
        } else {
            // Ocultar campos nombre, apellido y documento
            document.getElementById('nombreDiv').style.display = 'none';
            document.getElementById('apellidoDiv').style.display = 'none';
            document.getElementById('documentoDiv').style.display = 'none';
        }
    }

    // Ejecutar la función al cargar la página
    window.onload = function() {
        // Obtener el valor de $tipocliente
        var tipocliente = <?php echo $rs->fields['tipocliente']; ?>;
        // Llamar a la función para ocultar o mostrar los campos
        toggleCamposCliente(tipocliente);
    };

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
                    <h2>Editar Vendedor</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                  <!-- AQUI SE COLOCA EL HTML -->
                  <?php if (trim($errores) != "") { ?>
                    
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<a href="../vendedor/vendedor_add.php" class="btn btn-sm btn-default"><span class="fa fa-plus"></span> Agregar Vendedor</a> 
<a href="cliente_borrado.php" class="btn btn-sm btn-default"><span class="fa fa-trash"></span> Borrar Vendedor</a>

<div class="clearfix"></div>
<hr>
<form id="form1" name="form1" method="post" action="">
  

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Cliente</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="hidden" name="tipocliente" id="tipocliente" value="<?php echo htmlentities($rs->fields['tipocliente']); ?>" />
        <span id="tipoClienteActual"><?php echo htmlentities($rs->fields['tipocliente_desc']); ?></span>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="nombreDiv"> 
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="nombre" id="nombre" value="<?php echo htmlentities($rs->fields['nombre']); ?>" placeholder="Nombre" class="form-control" disabled  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="apellidoDiv">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Apellido </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="apellido" id="apellido" value="<?php echo htmlentities($rs->fields['apellido']); ?>" placeholder="Apellido" class="form-control" disabled />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="documentoDiv">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cédula</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input type="text" name="documento" id="documento" value="<?php echo htmlentities($rs->fields['documento']); ?>" placeholder="Cédula" class="form-control" disabled />
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
		<label class="control-label col-md-3 col-sm-3 col-xs-12">Razón social *</label> <!-- Campo de Razon SocialL -->
		<div class="col-md-9 col-sm-9 col-xs-12">
			<input type="text" name="razon_social" id="razon_social" value="<?php  echo htmlentities($rs->fields['razon_social']); ?>" placeholder="Razon social" class="form-control" required disabled />                    
		</div>
	</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">RUC </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="sexo" id="sexo" value="<?php echo htmlentities($rs->fields['ruc']);?>" placeholder="RUC" class="form-control" disabled  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Nombre de Fantasia</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="fantasia" id="fantasia" value="<?php echo htmlentities($rs->fields['fantasia']); ?>" placeholder="Nombre de Fantasia" class="form-control" disabled />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Telefono</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="nombre_corto" id="nombre_corto" value="<?php echo htmlentities($rs->fields['telefono']); ?>" placeholder="Telefono" class="form-control" disabled />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Celular</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idtipdoc" id="idtipdoc" value="<?php echo intval($rs->fields['celular']);?>" placeholder="Celular" class="form-control" required="required" disabled />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Email </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="documento" id="documento" value="<?php echo htmlentities($rs->fields['email']);?>" placeholder="Correo Electronico" class="form-control" disabled />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Fecha de alta </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="fechanac" id="fechanac" value="<?php echo htmlentities($rs->fields['fechanac']); ?>" placeholder="Fecha de Alta" class="form-control" disabled  />                    
	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Comentario</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="ruc" id="ruc" value="<?php echo htmlentities($rs->fields['comentario']);?>" placeholder="Comentarios" class="form-control" disabled />                    
	</div>
</div>

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

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
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
