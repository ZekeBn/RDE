<?php
/*-----------------------------PARA USAR CON TIPO DE VENTA SUPERMERCADOS-----------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//print_r($_REQUEST);
$buscar = "Select * from preferencias_caja limit 1";
$rzs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$limite = intval($rzs->fields['limite_clientes_busqueda']);
if ($limite == 0) {
    $limite = 20;

}

$v_rz = trim($_POST['bus_rz']);
$v_ruc = trim($_POST['bus_ruc']);
$v_doc = trim($_POST['bus_doc']);
$tpago = intval($_POST['tpago']);
$idpedido = intval($_POST['idpedido']);
$add = '';
$order = "order by razon_social asc limit $limite";


if ($v_rz != '') {
    $ra = antisqlinyeccion($_POST['bus_rz'], 'text');
    $ra = str_replace("'", "", $ra);
    $len = strlen($ra);
    // armar varios likes por cada palabra
    $v_rz_ar = explode(" ", $v_rz);
    foreach ($v_rz_ar as $palabra) {
        $add .= " and razon_social like '%$palabra%' ";
    }
    $order = "
	order by 
	CASE WHEN
		substring(razon_social from 1 for $len) = '$ra'
	THEN
		0
	ELSE
		1
	END asc, 
	razon_social asc
	Limit $limite
	";
}
if ($v_doc != '') {
    $documento = antisqlinyeccion($_POST['bus_doc'], 'int');
    $documento = intval($documento);
    $add = " and documento like '$documento%' ";
    $order = "order by razon_social asc limit $limite";
}
if ($v_ruc != '') {
    $ru = antisqlinyeccion($_POST['bus_ruc'], 'text');
    $ru = str_replace("'", "", $ru);
    $add = " and ruc like '$ru%'";
    $order = "order by razon_social asc limit $limite";
}
$buscar = "
Select * , sucursal_cliente.direccion as direccion
from cliente 
inner join sucursal_cliente on sucursal_cliente.idcliente = cliente.idcliente
where 
cliente.estado = 1 
and sucursal_cliente.estado = 1
$add 
$order
";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
$tcli = $rscli->RecordCount();



?><select size="15" style="width:80%; " name="cliente" id="cliente" onChange="selecciona_cliente(this.value,<?php echo $tpago?>,<?php echo $idpedido?>);">
	<?php while (!$rscli->EOF) {
	    $array = "";
	    $array = [
	        'idcliente' => $rscli->fields['idcliente'],
	        'idsucursal_clie' => $rscli->fields['idsucursal_clie'],
	    ];
	    // convierte a formato json
	    $respuesta = json_encode($array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

	    // devuelve la respuesta formateada
	    //echo $respuesta;

	    ?>    
    <option value='<?php echo $respuesta; ?>'><?php echo $rscli->fields['ruc']; ?><?php if ($rscli->fields['documento'] != '') {
        echo " | ".$rscli->fields['documento']."";
    } ?><?php echo ' | '.$rscli->fields['razon_social']; ?> | <?php echo antixss(substr($rscli->fields['direccion'], 0, 30));?> | <?php echo antixss(substr($rscli->fields['sucursal'], 0, 30));?></option>
    <?php $rscli->MoveNext();
	}?>
    </select>

<br />

