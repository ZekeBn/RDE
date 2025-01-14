 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "430";
require_once("includes/rsusuario.php");


$ruc = antisqlinyeccion($_POST['ruc'], "text-notnull");
$razon_social = antisqlinyeccion($_POST['razon_social'], "text-notnull");

$v_rz = trim($_POST['razon_social']);
if ($v_rz != '') {
    $ra = antisqlinyeccion($v_rz, 'text');
    $ra = str_replace("'", "", $ra);
    $len = strlen($ra);
    // armar varios likes por cada palabra
    $v_rz_ar = explode(" ", $v_rz);
    foreach ($v_rz_ar as $palabra) {
        $whereadd .= " and cliente.razon_social like '%$palabra%' ";
    }
    $order = "
    order by 
    CASE WHEN
        substring(cliente.razon_social from 1 for $len) = '$ra'
    THEN
        0
    ELSE
        1
    END asc, 
    cliente.razon_social asc
    ";
}
if (trim($_POST['ruc']) != '') {
    $whereadd = "and cliente.ruc like '%$ruc%'";
}

$consulta = "
select *
from cliente
where
estado = 1
$whereadd
order by razon_social asc
limit 20
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><div class="table-responsive" id="cuadro_busqueda">
<table width="100%" class="table table-bordered table-hover  bulk_action" id="carrito_busq">
<thead>
  <tr>
      <th></th>
    <th>Id</th>
    <th>Razon Social</th>
    <th>RUC</th>
    <th>Nombre y Apellido</th>
  </tr>
  </thead>
  <tbody>
<?php while (!$rs->EOF) { ?>
  <tr>
      <td><button type="button" class="btn  btn-default btn-xs" onMouseUp="seleccionar_item(<?php echo antixss($rs->fields['idcliente']); ?>,'<?php echo antixss($rs->fields['razon_social']); ?>');" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar" ><span class="fa fa-check-square-o"></span></button></td>
    <td><?php echo antixss($rs->fields['idcliente']);  ?></td>
    <td><?php echo antixss($rs->fields['razon_social']); ?></td>
    <td><?php echo antixss($rs->fields['ruc']); ?></td>
    <td><?php echo antixss($rs->fields['nombres'].' '.$rs->fields['apellidos']); ?></td>
  </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>

</div> 
