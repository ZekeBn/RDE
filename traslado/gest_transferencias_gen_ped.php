<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "222";
require_once("includes/rsusuario.php");

$idtanda = intval($_GET['id']);


//Buscamos tanda activa
$buscar = "select * from gest_transferencias where estado=1 and idtanda = $idtanda";
$rstanda = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idtanda = intval($rstanda->fields['idtanda']);
$fecha_transferencia = $rstanda->fields['fecha_transferencia'];
if ($idtanda == 0) {
    header("location: gest_transferencias.php");
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
    $idpedido = antisqlinyeccion($_POST['idpedido'], "int");






    if (intval($_POST['idpedido']) <= 0) {
        $valido = "N";
        $errores .= " - El campo pedido nro no puede estar vacio.<br />";
    }


    $consulta = "
	select * 
	from compras_pedidos
	where
	(estado = 2 or estado = 3)
	and idpedido = $idpedido
	";
    $rsped = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsped->fields['idpedido']) == 0) {
        $valido = "N";
        $errores .= " - El pedido ingresado no existe o no esta activo.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		INSERT INTO tmp_transfer
		(idtanda, idproducto, descripcion, cantidad, necesidad, horareg)
		select 
		$idtanda, compras_pedidos_detalles.idinsumo, insumos_lista.descripcion, compras_pedidos_detalles.cantidad, 0, '$ahora'
		from compras_pedidos_detalles
		inner join insumos_lista on insumos_lista.idinsumo = compras_pedidos_detalles.idinsumo
		where
		compras_pedidos_detalles.idpedido = $idpedido
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // carga el pedido en transferencias
        $consulta = "
		update gest_transferencias
		set 
		idpedidorepo = $idpedido
		where 
		 estado=1 
		 and idtanda = $idtanda
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_transferencias_det.php?id=".$idtanda);
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
                    <h2>Generar en base a Pedido</h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Pedido N# *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="idpedido" id="idpedido" value="<?php  if (isset($_POST['idpedido'])) {
	    echo htmlentities($_POST['idpedido']);
	} else {
	    echo htmlentities($rs->fields['idpedido']);
	}?>" placeholder="Pedido Nro" class="form-control" required />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_transferencias_det.php?id=<?php echo $idtanda; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
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

        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
