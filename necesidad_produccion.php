<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "17";
$submodulo = "293";
require_once("includes/rsusuario.php");


$iddeposito = intval($_GET['iddeposito']);
if ($iddeposito > 0) {


    $consulta = "
	select *,
		(
		select sum(disponible) as actual 
		from gest_depositos_stock_gral
		where
		gest_depositos_stock_gral.iddeposito = stock_minimo.iddeposito
		and gest_depositos_stock_gral.idproducto = stock_minimo.idinsumo
		) as stock_actual,
		(
		select cantidad_resultante
		from recetas_produccion
		where
		idobjetivo = prod_lista_objetivos.unicopkss
		order by prod_lista_objetivos.unicopkss desc
		limit 1
		) as cantidad_porvuelta
	from stock_minimo 
	inner join insumos_lista on insumos_lista.idinsumo = stock_minimo.idinsumo
	inner join prod_lista_objetivos on prod_lista_objetivos.idinsumo = insumos_lista.idinsumo
	where
	insumos_lista.estado = 'A'
	and stock_minimo.iddeposito = $iddeposito
	order by insumos_lista.descripcion asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

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
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Informe de Necesidad de Produccion</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">Deposito *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
and tiposala <> 3
order by descripcion asc
 ";

// valor seleccionado
if (isset($_GET['iddeposito'])) {
    $value_selected = htmlentities($_GET['iddeposito']);
} else {
    $value_selected = htmlentities($rs->fields['iddeposito']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'iddeposito',
    'id_campo' => 'iddeposito',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'iddeposito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" onchange="document.location.href=\'necesidad_produccion.php?iddeposito=\'+this.value" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
	</div>
</div>
<?php if ($iddeposito > 0) {?>
<div class="clearfix"></div>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th align="center">Codigo</th>
			<th align="center">Articulo</th>
			<th align="center">Stock minimo</th>
			<th align="center">Stock actual</th>
			<th align="center">Diferencia</th>
			<th align="center">Cantidad por Vuelta</th>
			<th align="center">Total Vueltas</th>
            <th align="center">Cantidad según Vueltas</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) {

    $diferencia = $rs->fields['stock_actual'] - $rs->fields['stock_minimo'];
    // en base a la receta y puede haber varias, ver cual tomar o usar cantidad por bandeja o definir 1 receta principal entre todas y tomar esa
    $cantidad_porvuelta = $rs->fields['cantidad_porvuelta'];
    //$total_vueltas_necesarias=ceil(($diferencia*-1)/$cantidad_porvuelta);
    $total_vueltas_necesarias = round(($diferencia * -1) / $cantidad_porvuelta, 0);
    $cantidad_segun_vueltas = $total_vueltas_necesarias * $cantidad_porvuelta;
    ?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idinsumo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['stock_minimo']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['stock_actual']);  ?></td>
            <td align="center"><?php echo formatomoneda($diferencia, 4, 'N'); ?></td>
			<td align="center"><?php echo formatomoneda($cantidad_porvuelta, 4, 'N'); ?></td>
			<td align="right"><?php echo formatomoneda($total_vueltas_necesarias, 4, 'N');  ?></td>
            <td align="right"><?php echo formatomoneda($cantidad_segun_vueltas, 4, 'N');  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />

<?php } else { ?>
<br /><br /><br />


<?php } ?>

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
