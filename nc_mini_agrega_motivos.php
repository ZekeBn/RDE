<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "232";
require_once("includes/rsusuario.php");


$buscar = "Select descripcion,idmotivo,registrado_el,estado,anulado_el,
(select usuario from usuarios where idusu=nota_cred_motivos_cli.anulado_por) as usuariodel,
(select usuario from usuarios where idusu=nota_cred_motivos_cli.registrado_por) as usuario
 from nota_cred_motivos_cli order by descripcion asc";
$rslista = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


?>
<div class="col-md-6">
	<input type="text" class="form-control" id="descripcion" placeholder="Descripcion/Motivo" />
</div>
<div class="col-md-6">
	<a class="btn btn-success" href="javascript:void(0);" onclick="registrar();"><span class="fa fa-plus"></span>&nbsp; Registrar</a>
</div>

<div id="reca" style="display:display;"></div>
<hr />
   <table width="100%" class="table table-bordered jambo_table bulk_action">
	<thead>
	<tr>
		<th>Descripcion/Motivo</th>
		<th>Registrado el</th>
		<th>Registrado por</th>
		<th></th>
	</tr>
	</thead>
	<tbody>
	<?php while (!$rslista->EOF) {
	    $userdel = trim($rslista->fields['usuariodel']);
	    $idmotivo = intval($rslista->fields['idmotivo']);
	    if ($rslista->fields['estado'] == 1) {
	        $enl = "<a href=javascript:void(0);' onclick='eliminar_motivo($idmotivo)'>[<span class='fa fa-trash'></span>&nbsp; Eliminar]</a>";
	    } else {
	        if ($rslista->fields['estado'] == 6) {
	            $enl = "Anulado el ".date("d/m/Y H:i:s", strtotime($rslista->fields['anulado_el']))." por $userdel ";
	        }
	    }



	    ?>
	<tr>
	<td><?php echo $rslista->fields['descripcion']; ?></td>
		<td><?php echo date("d/m/Y H:i:s", strtotime($rslista->fields['registrado_el'])); ?></td>
		<td><?php echo $rslista->fields['usuario']; ?></td>
		<td><?php echo $enl ?></td>
	</tr>
	
	<?php $rslista->MoveNext();
	} ?>
	</tbody>
</table>