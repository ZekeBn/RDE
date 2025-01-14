 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "333";
require_once("includes/rsusuario.php");

//print_r($_POST);

$ruc = antisqlinyeccion($_POST['ruc'], "text-notnull");
$razon_social = antisqlinyeccion($_POST['razon_social'], "text-notnull");
$fantasia = antisqlinyeccion($_POST['fantasia'], "text-notnull");
$documento = antisqlinyeccion($_POST['documento'], "text-notnull");
$idpedido = intval($_POST['idpedido']);

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
$v_fant = trim($_POST['fantasia']);
if ($v_fant != '') {
    $fa = antisqlinyeccion($v_fant, 'text');
    $fa = str_replace("'", "", $fa);
    $len = strlen($fa);
    // armar varios likes por cada palabra
    $v_fant_ar = explode(" ", $v_fant);
    foreach ($v_fant_ar as $palabra) {
        $whereadd .= " and cliente.fantasia like '%$palabra%' ";
    }
    $order = "
    order by 
    CASE WHEN
        substring(cliente.fantasia from 1 for $len) = '$fa'
    THEN
        0
    ELSE
        1
    END asc, 
    cliente.fantasia asc
    ";
}
if (trim($_POST['ruc']) != '') {
    $whereadd = "and cliente.ruc like '%$ruc%'";
}
if (trim($_POST['documento']) != '') {
    $whereadd = "and cliente.documento like '%$documento%'";
}


$consulta = "
select *
from cliente
inner join sucursal_cliente on sucursal_cliente.idcliente = cliente.idcliente
where
cliente.estado = 1 
and sucursal_cliente.estado = 1
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
    <th>Fantasia</th>
    <th>Sucursal</th>
  </tr>
  </thead>
  <tbody>
<?php while (!$rs->EOF) {

    $array = "";
    $array = [
        'idcliente' => $rs->fields['idcliente'],
        'idsucursal_clie' => $rs->fields['idsucursal_clie'],
        'idpedido' => $idpedido,
        'ruc' => $rs->fields['ruc'],
        'razon_social' => $rs->fields['razon_social'],
    ];
    // convierte a formato json
    $respuesta = json_encode($array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    ?>
  <tr>
      <td><button type="button" class="btn  btn-default btn-xs" onMouseUp="seleccionar_item('<?php echo antixss($rs->fields['idsucursal_clie']); ?>');" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar" ><span class="fa fa-check-square-o"></span></button><input type="hidden" id="idsucursal_clie_<?php echo antixss($rs->fields['idsucursal_clie']); ?>" value='<?php echo $respuesta; ?>' /></td>
    <td><?php echo antixss($rs->fields['idcliente']);  ?></td>
    <td><?php echo antixss($rs->fields['razon_social']); ?></td>
    <td><?php echo antixss($rs->fields['ruc']); ?></td>
    <td><?php echo antixss($rs->fields['nombres'].' '.$rs->fields['apellidos']); ?></td>
    <td><?php echo antixss($rs->fields['fantasia']); ?></td>
    <td><?php echo antixss($rs->fields['sucursal']); ?></td>
  </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>

</div> 
