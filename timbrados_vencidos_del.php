<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");


// trae timbrados vencidos
$consulta = "
select *
from timbrado 
where 
 estado = 1 
 AND fin_vigencia < CURDATE()
order by idtimbrado asc;
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {



    // validaciones basicas
    $valido = "S";
    $errores = "";



    // si todo es correcto actualiza
    if ($valido == "S") {

        while (!$rs->EOF) {

            $idtimbrado = intval($rs->fields['idtimbrado']);

            $consulta = "
			update timbrado
			set
				estado = 6,
				borrado_por = $idusu,
				borrado_el = '$ahora'
			where
				idtimbrado = $idtimbrado
				and estado = 1
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // inserta en el log
            $consulta = "
			INSERT INTO timbradolog
			(idtimbrado, timbrado, inicio_vigencia, fin_vigencia, registrado_por, registrado_el, borrado_por, borrado_el, 
			editado_por, editado_el, estado, log_registrado_por, log_registrado_el, log_tipomov) 
			SELECT 
			idtimbrado, timbrado, inicio_vigencia, fin_vigencia, registrado_por, registrado_el, borrado_por, borrado_el, 
			editado_por, editado_el, estado, $idusu, '$ahora', 'D'
			from timbrado
			WHERE
			idtimbrado = $idtimbrado
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $rs->MoveNext();
        } $rs->MoveFirst();

        header("location: timbrados.php");
        exit;

    }

}

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
              <!--<div class="title_left">
                <h3>Plain Page</h3>
              </div>-->

              <!--<div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                      <button class="btn btn-default" type="button">Go!</button>
                    </span>
                  </div>
                </div>
              </div>-->
            </div>

            <div class="clearfix"></div>
			
            
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Administracion de Timbrados</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      <!--<li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                        <ul class="dropdown-menu" role="menu">
                          <li><a href="#">Settings 1</a>
                          </li>
                          <li><a href="#">Settings 2</a>
                          </li>
                        </ul>
                      </li>
                      <li><a class="close-link"><i class="fa fa-close"></i></a>
                      </li>-->
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
					  
<strong>Timbrados que se borraran:</strong>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idtimbrado</th>
			<th align="center">Timbrado</th>
			<th align="center">Inicio vigencia</th>
			<th align="center">Fin vigencia</th>
            <th align="center">Total Documentos</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>
			<th align="center">Editado por</th>
			<th align="center">Editado el</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idtimbrado']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['timbrado']); ?></td>
			<td align="center"><?php if ($rs->fields['inicio_vigencia'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['inicio_vigencia']));
			} ?></td>
			<td align="center"><?php
if ($rs->fields['fin_vigencia'] != "") {
    echo date("d/m/Y", strtotime($rs->fields['fin_vigencia']));
}
    if (strtotime($rs->fields['fin_vigencia']) < strtotime(date("Y-m-d"))) {
        echo "<br /><strong style=\"color: red;\">Vencido!</strong>";
    }
    ?></td>
            <td align="center"><?php echo formatomoneda($rs->fields['total']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['editado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['editado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['editado_el']));
			}  ?></td>
		</tr>
<?php $rs->MoveNext();
} $rs->MoveFirst(); ?>
	  </tbody>
    </table>
</div>
<br />
					  
<form id="form1" name="form1" method="post" action="">



<div class="clearfix"></div>
<br />

    <div class="form-group">
		<div class="col-md-12 col-sm-12 col-xs-12 text-center">
	   <button type="submit" class="btn btn-danger" ><span class="fa fa-trash-o"></span> Borrar Todo</button>
	   <button type="button" class="btn btn-primary" onMouseUp="document.location.href='timbrados.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input type="hidden" name="MM_update" value="form1" />

<br />
</form>

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
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
