<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
require_once("includes/rsusuario.php");

$razon_social = antisqlinyeccion($_POST['razon_social'], "like");
$ruc = antisqlinyeccion($_POST['ruc'], "text");

if ($_POST['razon_social'] != '') {
    $whereadd = " and razon_social like '%$razon_social%' ";
}
if ($_POST['ruc'] != '') {
    $whereadd = " and ruc = $ruc ";
}

$consulta = "
select * 
from cliente 
where
estado = 1
$whereadd
order by razon_social asc
limit 30
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?>

<hr />
<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action">
	  <thead>
		<tr>
			<th></th>
			<th align="center">Ruc</th>
			<th align="center">Razon social</th>
			<th align="center">Fantasia</th>
		</tr>
	  </thead>
	  <tbody>
<?php while (!$rs->EOF) { ?>
		<tr>
			<td>
				
				<div class="btn-group">
					<a href="#" onclick="seleccionar_cliente(<?php echo $rs->fields['idcliente']; ?>,'<?php echo antixss($rs->fields['ruc']); ?>','<?php echo antixss($rs->fields['razon_social']); ?>');" class="btn btn-sm btn-default" title="Seleccionar" data-toggle="tooltip" data-placement="right"  data-original-title="Seleccionar"><span class="fa fa-plus"></span></a>
				</div>

			</td>
			<td align="center"><?php echo antixss($rs->fields['ruc']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['razon_social']); ?></td>
			<td align="center"><?php echo antixss($rs->fields['fantasia']); ?></td>

		</tr>
<?php $rs->MoveNext();
} //$rs->MoveFirst();?>
	  </tbody>
    </table>
</div>
<br />