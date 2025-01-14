<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");


$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$obligaprov = trim($rspref->fields['obligaprov']);
$impresor = trim($rspref->fields['script_ticket']);
$hab_monto_fijo_chica = trim($rspref->fields['hab_monto_fijo_chica']);
$hab_monto_fijo_recau = trim($rspref->fields['hab_monto_fijo_recau']);
if ($hab_monto_fijo_recau == 'S') {
    echo "Edicion bloqueada por tu administracion.";
    exit;
}


$consulta = "SELECT * FROM preferencias_caja WHERE  idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$caja_compartida = trim($rsprefcaj->fields['caja_compartida']);
$usar_turnos_caja = trim($rsprefcaj->fields['usa_turnos']);
$turno_automatico_caja = trim($rsprefcaj->fields['turno_automatico_caja']);

$arrastre_saldo_anterior = trim($rsprefcaj->fields['arrastre_saldo_anterior']);
$tipo_arrastre = trim($rsprefcaj->fields['tipo_arrastre']);
$cierre_caja_email = trim($rsprefcaj->fields['cierre_caja_mail']);

$consulta = "
select arrastre_caja_suc from sucursales where idsucu = $idsucursal limit 1
";
$rssucar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rssucar->fields['arrastre_caja_suc'] != "DEF") {
    if ($rssucar->fields['arrastre_caja_suc'] == "ACT") {
        $arrastre_saldo_anterior = 'S';
    }
    if ($rssucar->fields['arrastre_caja_suc'] == "INA") {
        $arrastre_saldo_anterior = 'N';
    }
}
if ($arrastre_saldo_anterior == 'S') {
    echo "Bloqueado por arrastre de saldos activos.";
    exit;
}
//echo $arrastre_saldo_anterior; exit;
//$idcaja=intval($_GET['id']);
//Verificamos si hay una caja abierta por este usuario
$buscar = "
Select * 
from caja_super 
where 
estado_caja=1 
and cajero=$idusu  
and tipocaja=1
order by fecha desc 
limit 1
";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaj->fields['idcaja']);

$consulta = "
select * from caja_super where estado_caja = 1 and idcaja = $idcaja
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rs->fields['idcaja']) == 0) {
    header("location: gest_administrar_caja_new.php");
    exit;
}

// loguear todos los cambios







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
    $monto_apertura = antisqlinyeccion($_POST['monto_apertura'], "float");








    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		select idcajalog  from caja_super_log where idcaja = $idcaja limit 1
		";
        $rsl = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if (intval($rsl->fields['idcajalog']) == 0) {
            $consulta = "
			insert into caja_super_log
			(idcaja, monto_apertura, log_registrado_por, log_registrado_el, accion)
			select idcaja, monto_apertura, $idusu, '$ahora', 'O'
			from caja_super 
			where
			idcaja = $idcaja
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }

        $consulta = "
		update caja_super
		set
			monto_apertura = $monto_apertura
		where
			idcaja = $idcaja
			and estado_caja = 1
			and rendido = 'N'
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		insert into caja_super_log
		(idcaja, monto_apertura, log_registrado_por, log_registrado_el, accion)
		values
		($idcaja, $monto_apertura, $idusu, '$ahora', 'U')
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // recalcular caja
        recalcular_caja($idcaja);

        header("location: gest_administrar_caja_new.php");
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
                    <h2>Editando Apertura de Caja #<?php echo $idcaja; ?></h2>
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
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Monto apertura *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="text" name="monto_apertura" id="monto_apertura" value="<?php  if (isset($_POST['monto_apertura'])) {
	    echo floatval($_POST['monto_apertura']);
	} else {
	    echo floatval($rs->fields['monto_apertura']);
	}?>" placeholder="Monto apertura" class="form-control" required="required" />                    
	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_administrar_caja_new.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />


<br /><br /><br />
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
