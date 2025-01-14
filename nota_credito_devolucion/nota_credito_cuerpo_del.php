<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");

// traer del cookie de asignacion para notas de credito igual que en ventas
$nota_suc = "001";
$nota_pexp = "001";



$registro = intval($_GET['id']);
if ($registro == 0) {
    header("location: nota_credito_cabeza.php");
    exit;
}

$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
inner join nota_credito_cuerpo on nota_credito_cuerpo.idnotacred = nota_credito_cabeza.idnotacred
where 
 nota_credito_cabeza.estado = 1 
 and nota_credito_cuerpo.registro = $registro
limit 1
";
//echo $consulta;exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idnotacred = intval($rs->fields['idnotacred']);
$notacredito_numero = $rs->fields['numero'];
$fecha_nota = $rs->fields['fecha_nota'];
$ruc_notacred = $rs->fields['ruc'];
$idcliente_notacred = $rs->fields['idcliente'];
if ($idnotacred == 0) {
    header("location: nota_credito_cabeza.php");
    exit;
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $factura = antisqlinyeccion($_POST['factura'], "text");


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





    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		delete from nota_credito_cuerpo
		where
			registro = $registro
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		delete from nota_credito_cuerpo_impuesto
		where
			idnotacreddet = $registro
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: nota_credito_cuerpo.php?id=".$idnotacred);
        exit;

    }

}


// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
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
                    <h2>Borrar Item</h2>
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

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Idnotacred *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="idnotacred" id="idnotacred" value="<?php  if (isset($_POST['idnotacred'])) {
	    echo htmlentities($_POST['idnotacred']);
	} else {
	    echo htmlentities($rs->fields['idnotacred']);
	}?>" placeholder="Idnotacred" class="form-control" required="required" readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Factura </label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="" name="factura" id="factura" value="<?php  if (isset($_POST['factura'])) {
	    echo htmlentities($_POST['factura']);
	} else {
	    echo htmlentities($rs->fields['factura']);
	}?>" placeholder="Factura" class="form-control"  readonly="readonly" disabled="disabled" />                    
	</div>
</div>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='nota_credito_cuerpo.php?id=<?php echo $idnotacred ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>


  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>


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
