<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "256";
require_once("includes/rsusuario.php");

$metodo = intval($_POST['metodo']);
if ($metodo == 1) {
    $valor = antisqlinyeccion($_POST['valor'], 'text');
    $add = " where nota_credito_cabeza_proveedor.estado <>6 and nota_credito_cabeza_proveedor.numero=$valor ";
}
if ($metodo == 2) {
    $valor = intval($_POST['valor']);
    $add = " where nota_credito_cabeza_proveedor.estado <>6 and nota_credito_cabeza_proveedor.idnotacred=$valor ";
}
if ($metodo == 3) {
    $add = '';
}
if ($metodo == 0) {
    $add = ' where nota_credito_cabeza_proveedor.estado <>6';

}
//echo $metodo;
$buscar = "Select timbrado,(select nombre from proveedores where idproveedor=nota_credito_cabeza_proveedor.idproveedor) as proveedor,
  numero,fecha_nota,idnotacred,usuario,nota_credito_cabeza_proveedor.registrado_el,nota_credito_cabeza_proveedor.estado,
  (select sum(subtotal) as t from nota_credito_cuerpo_proveedor
  where idnotacred=nota_credito_cabeza_proveedor.idnotacred) as totalnc
  from nota_credito_cabeza_proveedor
  inner join usuarios on usuarios.idusu=nota_credito_cabeza_proveedor.registrado_por
  $add
  order by numero desc limit 20";
$rnotas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//  echo $buscar;
$tnotas = intval($rnotas->RecordCount());
if ($tnotas > 0) {
    ?>
      <form id="hgj1" action="nota_credito_prov_anula.php" method="get">
      	<input type="hidden" name="ocidreg" id="ocidreg" value="" />
      </form>
	  <div class="col-md-12">
			<table class="table table-striped jambo_table bulk_action">
			<thead>
				<tr class="headings">
					 <th class="column-title">Acciones</th>
					 <th class="column-title">Proveedor</th>
					<th class="column-title">Fecha Nota </th>
					<th class="column-title">Numero</th>
					 <th class="column-title">Monto NC</th>
					<th class="column-title">Motivo NC </th>
					 <th class="column-title">Registrado por</th>
					 <th class="column-title">Registrado el</th>
					
				</tr>
			</thead>
			
			<tbody>
			 <?php while (!$rnotas->EOF) {?>
				<tr class="even pointer">
					<td height="35" class=" "><a href="javascript:void(0);" onClick="confirmar(<?php echo $rnotas->fields['idnotacred']?>)"><span class="fa fa-trash"></span><?php echo $rnotas->fields['idnotacred']?></a></td>
					<td height="35" class=" "><?php echo $rnotas->fields['proveedor']?></td>
					<td align="center" class=" "><?php echo date("d/m/Y", strtotime($rnotas->fields['fecha_nota']));?></td>
					<td align="center" class=" "><?php echo $rnotas->fields['numero']?></td>
					<td align="center" class=" "><?php echo formatomoneda($rnotas->fields['totalnc']); ?></td>
				  <td align="center" class=" "><?php echo $rnotas->fields['timbrado']?></td>
				 
					<td class=" "><?php echo $rnotas->fields['usuario']?></td>
			 <td align="center" class=" "><?php echo date("d/m/Y", strtotime($rnotas->fields['fecha_nota']));?></td>
				   
				</tr>
				<?php $rnotas->MoveNext();
			 }?>
			</tbody>
			
			
			</table>
			
			
			
			
			
	  </div>
	  
	  
	  <?php  } ?>
                            