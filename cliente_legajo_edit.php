<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "180";
require_once("includes/rsusuario.php");



$unsf = intval($_GET['id']);
if ($unsf == 0) {
    header("location: cliente_legajo.php");
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from cliente_legajo 
where 
unsf = $unsf
and estado = 1
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$unsf = intval($rs->fields['unsf']);
$idcliente = intval($rs->fields['idcliente']);
if ($unsf == 0) {
    header("location: cliente_legajo.php");
    exit;
}




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
    //$unsf=antisqlinyeccion($_POST['unsf'],"text");
    //$idcliente=antisqlinyeccion($_POST['idcliente'],"int");
    $idtipodocumento = antisqlinyeccion($_POST['idtipodocumento'], "int");
    //$archivo=antisqlinyeccion($_POST['archivo'],"text");
    //$nombre_antiguo_arch=antisqlinyeccion($_POST['nombre_antiguo_arch'],"text");
    $comentario = antisqlinyeccion($_POST['comentario'], "text");
    //$estado=1;
    //$registrado_por=$idusu;
    //$registrado_el=antisqlinyeccion($ahora,"text");
    $idsoportegastopla = antisqlinyeccion($_POST['idsoportegastopla'], "text");



    /*if(intval($_POST['idcliente']) == 0){
        $valido="N";
        $errores.=" - El campo idcliente no puede ser cero o nulo.<br />";
    }*/
    if (intval($_POST['idtipodocumento']) == 0) {
        $valido = "N";
        $errores .= " - El campo idtipodocumento no puede ser cero o nulo.<br />";
    }

    // si envio un gasto vinculado
    if (intval($_POST['idsoportegastopla']) > 0) {
        $consulta = "
		select * 
		from soportes_gastos_pla
		where
		idsoportegastopla = $idsoportegastopla
		and estado = 1
		";
        $rssop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcliente_sop = intval($rssop->fields['idcliente']);
        // valida que este activo
        if (intval($rssop->fields['idsoportegastopla']) == 0) {
            $valido = "N";
            $errores .= "El idsoportegastopla proporcionado no existe o fue borrado.<br />";
        }

        //valida que corresponda al mismo cliente
        if ($idcliente_sop != $idcliente) {
            $valido = "N";
            $errores .= "El cliente del soporte no es el mismo que el del legajo.<br />";
        }
        // valida que no este asignado a otro legajo
        $consulta = "
		select unsf, idcliente,
		(select ruc from cliente where idcliente = cliente_legajo.idcliente) as ruc
		from cliente_legajo 
		where 
		idsoportegastopla = $idsoportegastopla
		and estado = 1
		and unsf <> $unsf
		";
        $rslegex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $unsf_ex = intval($rslegex->fields['unsf']);
        $idcliente_ex = intval($rslegex->fields['idcliente']);
        $ruc_ex = antixss($rslegex->fields['ruc']);
        //valida que no fue asignado a otro legajo
        if ($unsf_ex > 0) {
            $valido = "N";
            $errores .= "El idsoportegastopla ya fue asignado a otro legajo [$unsf_ex] del cliente <a href='cliente_legajo.php?id=$idcliente_ex' target='_blank'>[$idcliente_ex]</a> ruc: $ruc_ex<br />";
        }


    }

    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update cliente_legajo
		set
			idtipodocumento=$idtipodocumento,
			comentario=$comentario,
			idsoportegastopla=$idsoportegastopla
		where
			unsf = $unsf
			and estado = 1
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: cliente_legajo.php?id=".$idcliente);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());




?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
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
                    <h2>Editar Archivo del Legajo</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Documentacion *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idtipodoc, descripcion
FROM tipos_documentos
where
estado = 1
order by descripcion asc
 ";

// valor seleccionado
if (isset($_POST['idtipodocumento'])) {
    $value_selected = htmlentities($_POST['idtipodocumento']);
} else {
    $value_selected = $rs->fields['idtipodocumento'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipodocumento',
    'id_campo' => 'idtipodocumento',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idtipodoc',

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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Cod Servicio </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idsoportegastopla" id="idsoportegastopla" value="<?php  if (isset($_POST['idsoportegastopla'])) {
	    echo htmlentities($_POST['idsoportegastopla']);
	} else {
	    echo htmlentities($rs->fields['idsoportegastopla']);
	}?>" placeholder="idsoportegastopla" class="form-control"  />                    
	</div>
</div>
	
	

<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
        
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='cliente_legajo.php?id=<?php echo $idcliente; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />

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
