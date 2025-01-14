<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

require_once("includes/funciones_caja.php");

$idcaja = intval($_GET['idcaja']);
$whereadd = "";
if ($idcaja > 0) {
    $whereadd = " and idcaja = $idcaja ";
}



// trae la ultima caja cerrada del usuario a menos que se envie id de caja
$buscar = "
Select * 
from caja_super 
where 
estado_caja=3 
and cajero=$idusu 
$whereadd
order by fecha_cierre desc
limit 1
";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaj->fields['idcaja']);
if ($idcaja == 0) {
    echo "La caja que intentas imprimir no existe o no pertenece a tu usuario.";
    exit;
}
//actualiza_caja($idcaja);
recalcular_caja($idcaja);


//  vuelve a hacer la consulta
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

// centrar nombre de empresa
$nombreempresa_centrado = corta_nombreempresa($nombreempresa);

//datos de la caja
$fecha_apertura = date("d/m/Y H:i:s", strtotime($rscaj->fields['fecha_apertura']));
$fecha_cierre = date("d/m/Y H:i:s", strtotime($rscaj->fields['fecha_cierre']));
$montoaper = $rscaj->fields['monto_apertura'];
$montocierre = $rscaj->fields['monto_cierre'];
$tefec = $rscaj->fields['total_efectivo'];
$tarjecred = $rscaj->fields['total_tarjeta'];
$tarjedeb = $rscaj->fields['total_tarjeta_debito'];
$tcheque = $rscaj->fields['total_cheque'];
$ttransfer = $rscaj->fields['total_transfer'];
$cred = $rscaj->fields['total_credito'];
$tpagos = $rscaj->fields['total_pagos_dia'];
$tpagosef = $tpagos - $rscaj->fields['total_pagos_dia_ch'];
$faltante = $rscaj->fields['faltante'];
$sobrante = $rscaj->fields['sobrante'];
$ape_ch = $rscaj->fields['caja_chica'];
$caja_chica_cierre = $rscaj->fields['caja_chica_cierre'];

$consulta = "
select sum(total_vouchers) as totalvouchers 
from caja_vouchers 
where 
estado <> 6 
and idcaja = $idcaja
";
$rsvo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$totalvouchers = $rsvo->fields['totalvouchers'];

// preferencias
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$impresor = trim($rspref->fields['script_ticket']);
$impresor = strtolower($impresor);
if ($impresor == '') {
    $impresor = 'http://localhost/impresorweb/ladocliente.php';
}
$script_impresora = $impresor;


$buscar = "Select valor,cantidad,subtotal,registrobill from caja_billetes
inner join gest_billetes
on gest_billetes.idbillete=caja_billetes.idbillete
where caja_billetes.idcajero=$idusu and idcaja=$idcaja and caja_billetes.estado=1
order by valor asc";
$rsbilletitos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tbilletes = $rsbilletitos->RecordCount();
//echo $buscar;

if ($tbilletes > 0) {
    $tg = 0;
    $add1 = '';
    while (!$rsbilletitos->EOF) {
        $valor = trim($rsbilletitos->fields['valor']);
        $cantidad = trim($rsbilletitos->fields['cantidad']);
        $subtotal = trim($rsbilletitos->fields['subtotal']);
        $tg = $tg + $subtotal;
        $add1 .= agregaespacio_tk($cantidad, 5, 'der', 'N').' | '
        .agregaespacio_tk(formatomoneda($valor), 12, 'der', 'N').' | '
        .agregaespacio_tk(formatomoneda($subtotal), 17, 'der', 'N')." \n";

        $rsbilletitos->MoveNext();
    }


    /*$add1.=agregaespacio_tk('123456789',5,'der','S').' | '
        .agregaespacio_tk('222222222222222222222222222222',12,'der','S').' | '
        .agregaespacio_tk('333333333333333333333333333333',17,'der','S')." \n";*/
}

//Monedas extranjeras
$buscar = "Select descripcion,cantidad,subtotal,sermone from caja_moneda_extra 
inner join tipo_moneda on tipo_moneda.idtipo=caja_moneda_extra.moneda 
where idcaja=$idcaja and cajero=$idusu and caja_moneda_extra.estado=1";
$rsmmone = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tmone = $rsmmone->RecordCount();

$teoricogs = $montoaper + $tefec + $tarje + $tcheque + $ttransfer - $tpagos;



//total en monedas arqueadas
$buscar = "select sum(subtotal) as total from caja_billetes where idcaja=$idcaja and idcajero=$idusu";
$tarqueo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tarquegs = intval($tarqueo->fields['total']);
//total en monedas extranjeras pero convertidas a gs
$buscar = "select sum(subtotal) as tmone from caja_moneda_extra where idcaja=$idcaja and cajero=$idusu";
$extra = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$textra = floatval($extra->fields['tmone']);

//$fecha_cierre=date("d/m/Y H:i:s");
$cajero = strtoupper($cajero);



$consulta = "
select tipotk from usuarios where idusu = $idusu limit 1
";
$rstipotk = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$tipotk = $rstipotk->fields['tipotk'];

// ticket visible
if ($tipotk == 'V') {


    $parametros_array = [
        'idcaja' => $idcaja,
        'tipotk' => 'V', // visible o ciega

    ];
    $tickete = imprime_cierre_caja($parametros_array);

    // ticket ciego
} else {

    // caja ciega
    $tickete = "
----------------------------------------
$nombreempresa_centrado
            CIERRE DE CAJA
----------------------------------------
FECHA APERTURA : $fecha_apertura
FECHA CIERRE   : $fecha_cierre
NRO CAJA       : $idcaja
SUCURSAL       : $nombresucursal
CAJERO         : $cajero
----------------------------------------
MONTO APERTURA   : ".formatomoneda($montoaper)."
MONTO CIERRE     : ".formatomoneda($montocierre)."
C. CHICA APERTURA: ".formatomoneda($ape_ch)."
C. CHICA CIERRE  : ".formatomoneda($caja_chica_cierre)."
----------------------------------------
          ARQUEO DE BILLETES
CANT  | VALOR        | SUBTOTAL             
----------------------------------------
$add1
----------------------------------------
TOTAL BILLETES: ".formatomoneda($tg)."
TOTAL OTRAS FP: ".formatomoneda($totalvouchers)."
TOTAL ARQUEO  : ".formatomoneda($tg + $totalvouchers)."
----------------------------------------";

    if ($muestraventasciega == 'S') {
        $tickete = $tickete."
        RESUMEN DE MOVIMIENTOS
* INGRESOS
EFECTIVO             :".formatomoneda($tefec)."
TARJETA CRED         :".formatomoneda($tarjecred)."
TARJETA DEB          :".formatomoneda($tarjedeb)."
CHEQUE               :".formatomoneda($tcheque)."
TRANSFER             :".formatomoneda($ttransfer)."
VENTA A CREDITO      :".formatomoneda($cred)."
* EGRESOS
PAGOS X CAJA         :".formatomoneda($tpagos)."
PAGOS X CAJA EFECTIVO:".formatomoneda($tpagosef)."
----------------------------------------";

    } // if ($muestraventasciega=='S'){

} // if($tipotk == 'V'){

$tickete = $tickete."
";


$texto = trim($tickete);




?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cierre de Caja</title>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">

function imprime_cliente(){
		//alert('a');
		var texto = document.getElementById("texto").value;
		//alert(texto);
        var parametros = {
                "tk"            : texto,
				"duplic_control" : "N"
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora; ?>',
                type:  'post',
				dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
				crossDomain: true,
                success:  function (response) {
						//$("#impresion_box").html(response);	
						//si impresion es correcta marcar
						var str = response;
						var res = str.substr(0, 18);
						//alert(res);
						if(res == 'Impresion Correcta'){
							document.location.href='gest_administrar_caja.php';
						}else{
							$("#impresion_box").html(response);	
						}
						
                }
        });
	
}
function volver_caja(){
	document.location.href='gest_administrar_caja.php';
}	
</script>
</head>

<body bgcolor="#CCCCCC" onLoad="imprime_cliente()">
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" id="impresion_box">
<p align="center"><input type="button" value="imprimir" style="padding:10px;" onmouseup="imprime_cliente();"></p>
</div><br />
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" >
<p align="center"><input type="button" value="Volver a la Caja" style="padding:10px;" onmouseup="volver_caja();"></p>
</div><br />
<div style="width:320px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px;">
<textarea readonly id="texto" style="display:; width:315px; height:420px;"><?php echo $texto; ?></textarea>
<pre>
<?php //echo $texto;?>
</pre>
</div>

</body>
</html>