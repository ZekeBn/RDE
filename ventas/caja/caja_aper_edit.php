<?php
require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "269";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");


$idcaja = intval($_GET['id']);
//$idcaja=24;

$consulta = "
select * from caja_super where estado_caja <> 6 and idcaja = $idcaja
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rs->fields['idcaja']) == 0) {
    header("location: caja_cierre_edit.php");
    exit;
}

// loguear todos los cambios


function accion_logcaja($accion)
{
    //O: original I: insert U: update D: delete
    $accion_txt = [
        'O' => 'ORIGINAL',
        'I' => 'AGREGAR',
        'U' => 'ACTUALIZAR',
        'D' => 'BORRAR'
    ];
    return $accion_txt[$accion];

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
			and estado_caja <> 6
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

        header("location: caja_cierre_edit_edit.php?id=".$idcaja);
        exit;

    }

}

// control de formulario despues de recibir el post y validar vuelve a regenerar
$_SESSION['form_control'] = md5(rand());


?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../../includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../../includes/lic_gen.php");?>
            
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
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='caja_cierre_edit_edit.php?id=<?php echo $idcaja; ?>'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><hr />
<strong>Ultimos 100 Cambios:</strong>
<p><a href="caja_aper_log.php" class="btn btn-sm btn-default"><span class="fa fa-search"></span> Ver todas las Cajas</a></p>
<?php
$consulta = "
SELECT idcajalog, idcaja, log_registrado_por, log_registrado_el, accion, 
(select monto_apertura from caja_super_log where idcaja = csl.idcaja and accion = 'O' order by idcajalog desc limit 1 ) as monto_apertura_original, 
monto_apertura as monto_apertura_nuevo,
(select usuario from usuarios where csl.log_registrado_por = usuarios.idusu) as registrado_por
FROM `caja_super_log`  csl
WHERE
csl.accion <> 'O'  
and idcaja = $idcaja
ORDER BY csl.`idcajalog` desc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Idcaja</th>
			<th align="center">Monto apertura original</th>
			<th align="center">Monto apertura nuevo</th>
			<th align="center">Accion</th>
			<th align="center">Log registrado por</th>
			<th align="center">Log registrado el</th>

		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php echo intval($rs->fields['idcaja']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_apertura_original']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['monto_apertura_nuevo']);  ?></td>
			<td align="center"><?php echo accion_logcaja($rs->fields['accion']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['log_registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['log_registrado_el']));
			}  ?></td>
			
		</tr>
<?php


$rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>

    </table>
</div>
<br />					  
					  
					  
					  
<div class="clearfix"></div>
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
		<?php require_once("../../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../../includes/footer_gen.php"); ?>
  </body>
</html>
