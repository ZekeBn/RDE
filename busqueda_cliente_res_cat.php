 <?php
/*----------------------------------------------

--------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

//print_r($_POST);

$ruc = antisqlinyeccion($_POST['ruc'], "text-notnull");
$razon_social = antisqlinyeccion($_POST['razon_social'], "text-notnull");
$sucursal_cliente = antisqlinyeccion($_POST['sucursal_cliente'], "text-notnull");
$fantasia = antisqlinyeccion($_POST['fantasia'], "text-notnull");
$documento = antisqlinyeccion($_POST['documento'], "text-notnull");
$idcampo = htmlentities($_POST['idcampo']);
$tipo = intval($_POST['tipo']);
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
if (trim($_POST['sucursal_cliente']) != '') {
    $whereadd = "and sucursal_cliente.sucursal like '%$sucursal_cliente%'";
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
limit 80
";
//echo $consulta;
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if (intval($rs->Recordcount()) > 0) {
    ?>
<div class="table-responsive" id="cuadro_busqueda">
<table width="100%" class="table table-bordered table-hover  bulk_action" id="carrito_busq">
<thead>
  <tr>
      <th></th>
    <th>Id</th>
    <th>Razon Social</th>
    <th>RUC</th>
    <th>Documento</th>
    <th>Fantasia</th>
    <th>Sucursal</th>
  </tr>
  </thead>
  <tbody>
<?php
    $idcampo = "cliente_ped";
    while (!$rs->EOF) {
        $idc = intval($rs->fields['idcliente']);
        $tipocliente = intval($rs->fields['tipocliente']);

        $consulta = "
        select ruc,razon_social,nombre,apellido,fantasia,cliente.idcliente,sucursal_cliente.sucursal,cliente.telefono,cliente.email
        from cliente 
        inner join sucursal_cliente on sucursal_cliente.idcliente = cliente.idcliente
        where 
        cliente.idcliente = $idc ";
        $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;
        $rfanta = trim($rscli->fields['fantasia']);
        if ($rscli->fields['fantasia'] == '') {
            if ($tipocliente == 1) {
                $nf = trim($rscli->fields['nombre'].' '.$rscli->fields['apellido']);
            } else {

                $nf = trim($rscli->fields['razon_social']);
            }
            //$nf=str_replace("'","",trim($rscli->fields['razon_social']));

            $update = "update cliente set fantasia='$nf' where idcliente=$idc ";
            //echo $update;
            $conexion->Execute($update) or die(errorpg($conexion, $update));


        } else {
            $update = "update sucursal_cliente set sucursal='$rfanta' where sucursal='CASA MATRIZ' and idcliente=$idc " ;
            $conexion->Execute($update) or die(errorpg($conexion, $update));

        }
        //si la fantasia es CASA MATRIZ, cambiamos al de la fantasia del cliente





        $array = "";
        $res = [
                'ruc' => $rs->fields['ruc'],
                'razon_social' => $rs->fields['razon_social'],
                'cliente' => $rs->fields['nombre'].' '.$rscli->fields['apellido'],
                'nombre_ruc' => $rs->fields['nombre'],
                'apellido_ruc' => $rs->fields['apellido'],
                'fantasia' => $rs->fields['fantasia'],
                'idcliente' => $rs->fields['idcliente'],
                'sucuvirtual' => $rs->fields['sucursal'],
                'idsucursal_clie' => $rs->fields['idsucursal_clie'],
                'telefono' => $rs->fields['telefono'],
                'email' => $rs->fields['mail'],
                'valido' => 'S'
            ];

        // convierte a formato json
        $respuesta = json_encode($res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        ?>
  <tr>
      <td><button type="button" class="btn  btn-default btn-xs" onMouseUp="seleccionar_item('<?php echo antixss($rs->fields['idsucursal_clie']); ?>','<?php echo $idcampo; ?>',<?php echo $tipo ?>);" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar" ><span class="fa fa-check-square-o"></span></button>
    <input type="hidden" id="idsucursal_clie_<?php echo antixss($rs->fields['idsucursal_clie']); ?>" value='<?php echo $respuesta; ?>' /></td>
    <td><?php echo antixss($rs->fields['idcliente']);  ?></td>
    <td><?php echo antixss($rs->fields['razon_social']); ?></td>
    <td><?php echo antixss($rs->fields['ruc']); ?></td>
    <td><?php echo antixss($rs->fields['documento']); ?></td>
    <td><?php echo antixss($rs->fields['fantasia']); ?></td>
    <td><?php echo antixss($rs->fields['sucursal']); ?></td>
  </tr>
<?php $rs->MoveNext();
    } ?>
  </tbody>
</table>

</div> 
<?php } ?>
