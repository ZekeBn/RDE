<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");



$idnotacred = intval($_GET['id']);
if ($idnotacred == 0) {
    header("location: nota_credito_cabeza.php");
    exit;
}
$consulta = "
select *,
(select usuario from usuarios where nota_credito_cabeza.registrado_por = usuarios.idusu) as registrado_por,
(select descripcion from nota_cred_motivos_cli where nota_cred_motivos_cli.idmotivo = nota_credito_cabeza.idmotivo) as motivo,
(select sucursales.nombre from sucursales where sucursales.idsucu = nota_credito_cabeza.idsucursal) as sucursal
from nota_credito_cabeza 
where 
 estado = 3 
 and idnotacred = $idnotacred
order by idnotacred asc
";
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
                    <h2>Detalle de la Nota de credito</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

<p>
<a href="nota_credito_cabeza.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>

<a href="nota_credito_cabeza_imp.php?id=<?php echo $rs->fields['idnotacred']; ?>" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-print"></span> Imprimir</a>
</p>
<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idnotacred</th>
			<th align="center">Motivo</th>
			<th align="center">Sucursal</th>
			<th align="center">Fecha nota</th>
			<th align="center">Numero Nota</th>
			<th align="center">Razon social</th>
			<th align="center">Ruc</th>
			<th align="center">Estado</th>
			<th align="center">Registrado por</th>
			<th align="center">Registrado el</th>


		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo antixss($rs->fields['idnotacred']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['motivo']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['sucursal']); ?></td>
			<td align="center"><?php if ($rs->fields['fecha_nota'] != "") {
			    echo date("d/m/Y", strtotime($rs->fields['fecha_nota']));
			} ?></td>
			<td align="center"><?php echo antixss($rs->fields['numero']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center">Finalizado</td>
			<td align="center"><?php echo antixss($rs->fields['registrado_por']); ?></td>
			<td align="center"><?php if ($rs->fields['registrado_el'] != "") {
			    echo date("d/m/Y H:i:s", strtotime($rs->fields['registrado_el']));
			} ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />
<?php

$consulta = "
select *, (select descripcion from gest_depositos where iddeposito = nota_credito_cuerpo.iddeposito) as deposito,
(select iva_describe from tipo_iva where idtipoiva = nota_credito_cuerpo.idtipoiva) as tipoiva
from nota_credito_cuerpo 
where 
idnotacred = $idnotacred
order by idnotacred asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>

<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>

			<th align="center">Idventa</th>
			<th align="center">Factura</th>
			<th align="center">Cod Articulo</th>
			<th align="center">Concepto</th>
			<th align="center">Cantidad</th>
			<th align="center">Precio</th>
			<th align="center">Subtotal</th>
			<th align="center">Deposito</th>
			<th align="center">IVA %</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>

			<td align="center"><?php echo intval($rs->fields['idventa']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['factura']); ?></td>
			<td align="center"><?php if ($rs->fields['codproducto'] > 0) {
			    echo antixss($rs->fields['codproducto']);
			}  ?></td>
			<td align="left"><?php echo antixss($rs->fields['descripcion']); ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['cantidad'], 4, 'N');  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['precio']);  ?></td>
			<td align="right"><?php echo formatomoneda($rs->fields['subtotal']);  ?></td>
            <td align="left"><?php echo antixss($rs->fields['deposito']);  ?></td>
			<td align="center"><?php echo antixss($rs->fields['tipoiva']);  ?></td>
		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />




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
