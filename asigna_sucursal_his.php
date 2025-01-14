<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "192";
$asig_pag = 'S';
require_once("includes/rsusuario.php");

if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
if (intval($_GET['idsucursal_ant']) > 0) {
    $idsucursal_ant = intval($_GET['idsucursal_ant']);
    $whereadd .= " and asignasucu_auto.idsucursal_ant = $idsucursal_ant";
}
if (intval($_GET['idsucursal_asig']) > 0) {
    $idsucursal_asig = intval($_GET['idsucursal_asig']);
    $whereadd .= " and asignasucu_auto.idsucursal_asig = $idsucursal_asig";
}

$consulta = "
select *,
(select usuario from usuarios where asignasucu_auto.idusu = usuarios.idusu) as registrado_por,
(select nombre from sucursales where idsucu = asignasucu_auto.idsucursal_ant) as sucursal_ant,
(select nombre from sucursales where idsucu = asignasucu_auto.idsucursal_asig) as sucursal_asig
from asignasucu_auto 
where 
idusu not in (select idusu from usuarios where super = 'S')
and date(fechahora) >= '$desde'
and date(fechahora) <= '$hasta'
$whereadd
order by idasignasucu desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md" <?php if ($_GET['ok'] == 's') { ?>onLoad="guarda_datos_locales();"<?php } ?>>
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
                    <h2>Historico de Asignaciones</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<a href="asigna_sucursal_pc.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a><hr />

					  

<form id="form1" name="form1" method="get" action="">

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Desde" class="form-control" required />                    

	</div>
</div>

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" placeholder="Hasta" class="form-control" required />                    

	</div>
</div>					  

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal Anterior </label>
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
if (isset($_GET['idsucursal_ant'])) {
    $value_selected = htmlentities($_GET['idsucursal_ant']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucursal_ant',
    'id_campo' => 'idsucursal_ant',

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
	

<div class="col-md-6 col-sm-6 form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal Asignada </label>
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
if (isset($_GET['idsucursal_asig'])) {
    $value_selected = htmlentities($_GET['idsucursal_asig']);
} else {
    $value_selected = "";
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucursal_asig',
    'id_campo' => 'idsucursal_asig',

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

<div class="clearfix"></div>
<br />


    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
	   <button type="submit" class="btn btn-default" ><span class="fa fa-search"></span> Filtrar</button>

        </div>
    </div>


<br />
</form> 					  
					  
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Fecha/hora</th>
			<th align="center">Usuario</th>
			<th align="center">Sucursal Anterior</th>
			<th align="center">Sucursal Asignada</th>

			<th align="center">Suc-Exp Anterior </th>
			<th align="center">Suc-Exp Nuevo</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td align="center"><?php if ($rs->fields['fechahora'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['fechahora']));
			}  ?></td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>

			<td align="center"><?php echo antixss($rs->fields['sucursal_ant']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal_asig']); ?></td>

			<td align="center"><?php echo agregacero($rs->fields['factura_suc_ant'], 3).'-'.agregacero($rs->fields['factura_pexp_ant'], 3); ?>
			<td align="center"><?php echo agregacero($rs->fields['factura_suc_asig'], 3).'-'.agregacero($rs->fields['factura_pexp_asig'], 3); ?></td>


		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
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
