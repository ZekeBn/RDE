<?php
/*-----------------------------PARA USAR CON TIPO DE VENTA SUPERMERCADOS-----------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//print_r($_POST);

$idpedido = intval($_POST['idpedido']);
$v_rz = trim($_POST['bus_rz']);
$v_ruc = trim($_POST['bus_ruc']);
$v_doc = trim($_POST['bus_doc']);
$tpago = intval($_POST['tipopago']);
$add = '';
//echo $v_rz;
$order = "order by razon_social asc limit 20";
if ($v_rz != '') {
    $ra = antisqlinyeccion($_POST['bus_rz'], 'text');
    $ra = str_replace("'", "", $ra);
    $len = strlen($ra);
    $add = " and razon_social like '%$ra%'";
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
    $add = " and documento = $documento ";
    $order = "order by razon_social asc limit 20";
}
if ($v_ruc != '') {
    $ru = antisqlinyeccion($_POST['bus_ruc'], 'text');
    $ru = str_replace("'", "", $ru);
    $add = " and ruc like '%$ru%'";
    $order = "order by razon_social asc limit 20";
}
$buscar = "Select * from cliente where idempresa = $idempresa and estado = 1 $add $order";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
$tcli = $rscli->RecordCount();





?>
<div class="col-md-6 col-sm-6 form-group">
	<div class="col-md-9 col-sm-9 col-xs-12"> 
<a href="javascript:void(0);"  class="btn btn-sm btn-default" onClick="carry_out();"><span class="fa fa-reply"></span> Regresar</a>
	</div>
</div>
 <div align="center"  style="min-height: 450px; width: 100%;">  




<div class="clearfix"></div>
<div class="col-md-4 col-sm-4 form-group">

	<input type="text" name="blci" id="blci" onKeyPress="filtrar_rz_carry();" placeholder="Buscar x Razon Social"  class="form-control">
</div>

<div class="col-md-4 col-sm-4 form-group">
	<input type="text" name="blci2" id="blci2" onKeyPress="filtrar_ruc_carry();" placeholder="Buscar x  RUC"  class="form-control" />
</div>


<div class="col-md-4 col-sm-4 form-group">
	<input type="text" name="blci3" id="blci3" onKeyPress="filtrar_doc_carry();" placeholder="Buscar x  Cedula"  class="form-control" />
</div>

<div class="clearfix"></div>
<hr  />


<div id="clientereca">
    <select size="10" style="width:100%; height: 250px;" name="cliente" id="cliente" onChange="selecciona_cliente_carry(this.value);">
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
    <option value='<?php echo $datos_cliente_json; ?>'><?php echo $rscli->fields['ruc'].' | '.$rscli->fields['razon_social']?> | <?php echo substr(trim($rscli->fields['direccion']), 0, 30); ?></option>
<?php $rscli->MoveNext();
}?>
    </select>
</div>
<br />
</div>