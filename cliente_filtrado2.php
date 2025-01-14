<?php
/*-----------------------------PARA USAR CON TIPO DE VENTA SUPERMERCADOS-----------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//print_r($_REQUEST);

$v_rz = trim($_POST['bus_rz']);
$v_ruc = trim($_POST['bus_ruc']);
$v_doc = trim($_POST['bus_doc']);
$tpago = intval($_POST['tpago']);
$idpedido = intval($_POST['idpedido']);
$add = '';
$order = "order by razon_social asc limit 20";


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
	Limit 20
	";
}
if ($v_doc != '') {
    $documento = antisqlinyeccion($_POST['bus_doc'], 'int');
    $documento = intval($documento);
    $add = " and documento like '$documento%' ";
    $order = "order by razon_social asc limit 20";
}
if ($v_ruc != '') {
    $ru = antisqlinyeccion($_POST['bus_ruc'], 'text');
    $ru = str_replace("'", "", $ru);
    $add = " and ruc like '$ru%'";
    $order = "order by razon_social asc limit 20";
}
$buscar = "Select * from cliente where idempresa = $idempresa and estado = 1 $add $order";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
$tcli = $rscli->RecordCount();



?><select size="15" style="width:80%; " name="cliente" id="cliente" onChange="selecciona_cliente_carry(this.value);">
	<?php while (!$rscli->EOF) {

	    $datos_cliente = [
	        'idcliente' => $rscli->fields['idcliente'],
	        'ruc' => trim($rscli->fields['ruc']),
	        'razon_social' => trim($rscli->fields['razon_social']),
	        'direccion' => trim($rscli->fields['direccion']),
	        'telefono' => trim($rscli->fields['telefono'])

	    ];
	    $datos_cliente_json = json_encode($datos_cliente, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);


	    ?>    
    <option value='<?php echo $datos_cliente_json; ?>'><?php echo $rscli->fields['ruc']; ?><?php if ($rscli->fields['documento'] != '') {
        echo " | ".$rscli->fields['documento']."";
    } ?><?php echo ' | '.$rscli->fields['razon_social']; ?> | <?php echo antixss(substr($rscli->fields['direccion'], 0, 30));?></option>
    <?php $rscli->MoveNext();
	}?>
    </select>

<br />

