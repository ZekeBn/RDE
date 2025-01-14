<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$dirsup = "S";
$modulo = "11";
$submodulo = "290";
require_once("../includes/rsusuario.php");

function extrae_parametros_url($parametros_get)
{
    foreach ($parametros_get as $key => $value) {
        $parametros .= '&'.htmlentities($key).'='.htmlentities($value);
        $parametros = '?'.substr($parametros, 1, 10000);
    }
    return $parametros;
}


if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}
if (intval($_GET['idsucu']) > 0) {
    $idsucu = intval($_GET['idsucu']);
    $whereadd .= " and ventas.sucursal = $idsucu ";
}

$consulta = "
select cliente.razon_social, ventas.idcliente, sum(totalcobrar) as total_venta, count(*) as cantidad_venta
from ventas 
inner join cliente on cliente.idcliente = ventas.idcliente
where 
 ventas.estado <> 6 
 $whereadd
 and date(ventas.fecha) >= '$desde'
 and date(ventas.fecha) <= '$hasta'
 group by cliente.razon_social, ventas.idcliente
order by sum(totalcobrar)  desc
limit 1000
";
//echo $consulta;
//exit;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

// debe ser igual al de arriba
$consulta = "
select sum(totalcobrar) as total_venta, count(*) as total_cantidad
from ventas 
inner join cliente on cliente.idcliente = ventas.idcliente
where 
 ventas.estado <> 6 
 $whereadd
 and date(ventas.fecha) >= '$desde'
 and date(ventas.fecha) <= '$hasta'
";
$rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$total_venta = $rstot->fields['total_venta'];




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
                    <h2>Ranking de ventas por Cliente (Top 1000)</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php //require_once("includes/menu_venta_cli.php");?>

<form id="form1" name="form1" method="get" action="">

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Desde *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="desde" id="desde" value="<?php  echo $desde; ?>" placeholder="Desde" class="form-control  has-feedback-left" required />                    
    <span class="fa fa-calendar-o form-control-feedback left" aria-hidden="true"></span>
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Hasta *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
	<input type="date" name="hasta" id="hasta" value="<?php echo $hasta; ?>" placeholder="Hasta" class="form-control  has-feedback-left" required />                    
    <span class="fa fa-calendar-o form-control-feedback left" aria-hidden="true"></span>
	</div>
</div>

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Sucursal *</label>
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
if (isset($_GET['idsucu'])) {
    $value_selected = htmlentities($_GET['idsucu']);
} else {
    //$value_selected=htmlentities($rs->fields['idsucu']);
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
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>

	</div>
</div>


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-3">
	   <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Filtrar</button>
        </div>
    </div>


<br />
</form>        
  <hr /><br />
 <a href="clientes_ranking_csv.php<?php echo parametros_url(); ?>" class="btn btn-sm btn-default"><span class="fa fa-file-excel-o"></span> Descargar CSV</a>
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
        	<th align="center"></th>
			<th align="center">Cliente</th>
			<th align="center">Ticket Promedio</th>
            <th align="center">Cantidad Ventas</th>
			<th align="center">Monto Ventas</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="clientes_ranking_det.php?idcliente=<?php echo antixss($rs->fields['idcliente']); ?>&desde=<?php echo antixss($desde); ?>&hasta=<?php echo antixss($hasta); ?>&idsucu=<?php echo intval($_GET['idsucu']); ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a>

				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['total_venta'] / $rs->fields['cantidad_venta']);  ?></td>
			<td align="center"><?php echo formatomoneda($rs->fields['cantidad_venta']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['total_venta']);  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<p align="center">Total Venta: <?php echo formatomoneda($total_venta, 2, 'N'); ?></p>

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
