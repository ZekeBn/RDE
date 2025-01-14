<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
$dirsup = 'S';
require_once("../includes/rsusuario.php");


if (isset($_POST['idnotacred'])) {
    $idnotacred = intval($_POST['idnotacred']);
}


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
<hr /><strong>Facturas Agregadas:</strong><br />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
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
<?php
    $tt = 0;
while (!$rs->EOF) {
    $tt = $tt + floatval($rs->fields['subtotal']);

    ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="nota_credito_cuerpo_del.php?id=<?php echo $rs->fields['registro']; ?>" class="btn btn-sm btn-default" title="Borrar" data-toggle="tooltip" data-placement="right"  data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
				</div>

			</td>
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
		<tr>
			<td colspan="8" align="right"><h2>Total Gs: &nbsp;<?php echo formatomoneda($tt, 4, 'N');  ?></h2></td>
		
		</tr>
	  </tbody>
    </table>
</div>
<br />
