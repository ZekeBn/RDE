<?php
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "53";
$dirsup = "S";
require_once("../includes/rsusuario.php");

if (intval($ocnum) == 0) {
    $ocn = intval($_POST['ocn']);
} else {
    $ocn = $ocnum;
}
//Traemos los productos seleccionados para la compra
$buscar = "
Select emb.idembarque, emb.ocnum, compras.fechacompra , puertos.descripcion as puerto
from embarque as emb
inner join compras on compras.idcompra = emb.idcompra
inner join puertos on puertos.idpuerto = emb.idpuerto
where emb.ocnum in (select ocnum from compras_ordenes where ocnum_ref=$ocnum)
";
$rscu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?>
.
<br />
<style>
	.transito{
		background: #ce2d4fa8;
    	color: white;
		font-weight: bold;

	}
	.completo{
		background: #D7FFAB;
    	color: #405467;
		font-weight: bold;
	}
</style>
<div class="table-responsive">
	<h2>Embarques asociados</h2>
    <table width="100%" class="table table-bordered jambo_table bulk_action">
    <thead>
		<tr>
        	<th  align="center" ><strong>Puerto</strong></th>
        	<th  align="center" ><strong>Fecha compra</strong></th>
            <th  align="center" ><strong>Detalles</strong></th>
            
		</tr>
    </thead>
    <tbody>
        <?php $tot = 0;
while (!$rscu->EOF) {
    $puerto = $rscu->fields['puerto'];
    $fechacompra = $rscu->fields['fechacompra'];
    $idembarque = $rscu->fields['idembarque'];
    $subocnum = $rscu->fields['ocnum'];
    ?>
        <tr >
			<td align="center">
					<h6><?php echo trim($puerto) ?></h6>
				</td>	
			<td align="center">
				<h6><?php echo trim($fechacompra) ?></h6>
			</td>
            <td align="center">
                <a href="javascript:void(0);" class="btn btn-sm btn-default " title="Embarque" data-toggle="tooltip"  onmouseup="document.location.href='../embarque/embarque_det.php?id=<?php echo trim($idembarque) ?>'"><span class="fa fa-ship"></span></a>		
            </td>
        </tr>
        <?php $rscu->MoveNext();
} ?>
        </tbody>
	</table>
</div>
