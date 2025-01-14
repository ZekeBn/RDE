<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "118";
$pag = "index";
require_once("includes/rsusuario.php");

//print_r($_POST);exit;

// stock
if (isset($_POST['ocstck']) && ($_POST['ocstck']) > 0) {
    $traslados = intval($_POST['primfs']);
    $produccion = intval($_POST['sefs']);
    $ventas = intval($_POST['tefs']);

    $update = "Update preferencias set traslado_nostock=$traslados,produccion_nostock=$produccion,ventas_nostock=$ventas
	where idempresa=$idempresa";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    header("Location: gest_parametrizar.php");
    exit;
}


// compras
if (isset($_POST['ocomp']) && ($_POST['ocomp']) > 0) {
    $tipocompra = intval($_POST['tc1']);
    $update = "
	Update preferencias 
	set 
	tipocompra=$tipocompra
	where 
	idempresa=$idempresa
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    header("Location: gest_parametrizar.php");
    exit;
}
// productos
if (isset($_POST['ocprod']) && ($_POST['ocprod']) > 0) {
    //print_r($_POST);
    $usa_recetap = substr(trim($_POST['usa_receta']), 0, 1);
    $usa_marcap = substr(trim($_POST['usa_marca']), 0, 1);
    $usa_webp = substr(trim($_POST['usa_web']), 0, 1);

    $update = "
	Update preferencias 
	set usa_receta='$usa_recetap',
	usa_marca = '$usa_marcap',
	usa_web = '$usa_webp'
	where 
	idempresa=$idempresa
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    header("Location: gest_parametrizar.php");
    exit;
}
//ventas
if (isset($_POST['ocvta']) && ($_POST['ocvta']) > 0) {

    $zonificar = substr(trim($_POST['radiozona']), 0, 1);
    if ($zonificar == 'S') {
        $tf = 0;
    } else {
        $tf = 1;
    }
    $cajacompleta = substr(trim($_POST['vtacaja']), 0, 1);
    $adherente = substr(trim($_POST['activaad']), 0, 1);
    $usa_cajachica = substr(trim($_POST['usa_cajachica']), 0, 1);
    $pagoxcaja_rec = substr(trim($_POST['pagoxcaja_rec']), 0, 1);
    $pagoxcaja_chic = substr(trim($_POST['pagoxcaja_chic']), 0, 1);
    $autopeso = substr(trim($_POST['autopeso']), 0, 1);
    $usabcode = substr(trim($_POST['usabcode']), 0, 1);
    $pesable = intval($_POST['pesable']);
    $borraped = substr(trim($_POST['borraped']), 0, 1);
    $selmozo = substr(trim($_POST['selmozo']), 0, 1);
    $obligachapa = substr(trim($_POST['obligachapa']), 0, 1);
    $usa_descuento = substr(trim($_POST['usa_descuento']), 0, 1);
    $diferencia_precio_suc = substr(trim($_POST['diferencia_precio_suc']), 0, 1);
    $factura_obliga = substr(trim($_POST['factura_obliga']), 0, 1);
    if ($pesable == 0) {
        $pesable = 'NULL';
    }
    $uni = intval($_POST['uni']);
    if ($uni == 0) {
        $uni = 'NULL';
    }
    $usabalanza = substr(trim($_POST['usabalanza']), 0, 1);
    $script_balanza = antisqlinyeccion($_POST['scriptb'], 'text');
    $script_balanza = str_replace("'", "", $script_balanza);
    $script_balanza = strtolower($script_balanza);
    $cantcodigo = intval($_POST['totcodigo']);
    $alumnolista_idprod = intval($_POST['alumnolista_idprod']);

    $cod_plu_desde = intval($_POST['cod_plu_desde']);
    $cod_plu_cantdigit = intval($_POST['cod_plu_cantdigit']);
    $cant_plu_entero_desde = intval($_POST['cant_plu_entero_desde']);
    $cant_plu_entero_cantdigit = intval($_POST['cant_plu_entero_cantdigit']);
    $cant_plu_decimal_desde = intval($_POST['cant_plu_decimal_desde']);
    $cant_plu_decimal_cantdigit = intval($_POST['cant_plu_decimal_cantdigit']);
    $redondear_subtotal = substr(trim($_POST['redondear_subtotal']), 0, 1);
    $redondeo_ceros = intval($_POST['redondeo_ceros']);
    $redondear_direccion = substr(trim($_POST['redondear_direccion']), 0, 1);
    $cant_plu_entero_unit_desde = intval($_POST['cant_plu_entero_unit_desde']);
    $cant_plu_entero_unit_cantdigit = intval($_POST['cant_plu_entero_unit_cantdigit']);


    $update = "
	Update preferencias 
	set deliveryproducto=$tf,
	usa_adherente = '$adherente',
	usa_vta_completa = '$cajacompleta',
	zonificar_delivery='$zonificar',
	usa_cajachica='$usa_cajachica',
	pagoxcaja_rec='$pagoxcaja_rec',
	pagoxcaja_chic='$pagoxcaja_chic',
	usabcode='$usabcode',
	codplu_pesable='$pesable',
	codplu_unitario='$uni',
	borrar_ped='$borraped',
	usa_balanza='$usabalanza',script_balanza='$script_balanza',autopeso='$autopeso',total_numeros_cod=$cantcodigo,
	alumnolista_idprod=$alumnolista_idprod,
	selmozo='$selmozo',
	obligachapa='$obligachapa',
	usa_descuento='$usa_descuento',
	diferencia_precio_suc='$diferencia_precio_suc',
	factura_obliga='$factura_obliga'
	where 
	idempresa=$idempresa
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    $update = "
	update preferencias_caja 
	set
	cod_plu_desde=$cod_plu_desde,
	cod_plu_cantdigit=$cod_plu_cantdigit,
	cant_plu_entero_desde=$cant_plu_entero_desde,
	cant_plu_entero_cantdigit=$cant_plu_entero_cantdigit,
	cant_plu_decimal_desde=$cant_plu_decimal_desde,
	cant_plu_decimal_cantdigit=$cant_plu_decimal_cantdigit,
	redondear_subtotal='$redondear_subtotal',
	redondeo_ceros=$redondeo_ceros,
	redondear_direccion	='$redondear_direccion',
	cant_plu_entero_unit_desde='$cant_plu_entero_unit_desde',
	cant_plu_entero_unit_cantdigit='$cant_plu_entero_unit_cantdigit'
	where 
	idempresa = 1
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));



    header("Location: gest_parametrizar.php");
    exit;

}
//impresion
if (isset($_POST['ocimpre']) && ($_POST['ocimpre']) > 0) {
    $tki = antisqlinyeccion($_POST['impretk'], 'text');
    $tki = str_replace("'", "", $tki);
    $tki = strtolower($tki);
    $fci = antisqlinyeccion($_POST['imprefc'], 'text');
    $fci = str_replace("'", "", $fci);
    $fci = strtolower($fci);
    $fca = antisqlinyeccion($_POST['imprefclc'], 'text');
    $fca = str_replace("'", "", $fca);
    $fca = strtolower($fca);
    $autoimpresor = antisqlinyeccion($_POST['autoimpresor'], 'text');
    $auto_impresor = antisqlinyeccion($_POST['auto_impresor'], 'text');
    $update = "
	Update preferencias 
	set script_factura='$fci',script_factura_cliente='$fca',
	script_ticket='$tki', 
	autoimpresor = $autoimpresor, auto_impresor = $auto_impresor
	where idempresa=$idempresa
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    if (trim($_POST['autoimpresor']) == 'S') {
        $update = "
		update preferencias_caja 
		set 
		valida_duplic_tipo = 'FT'
		";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

    } else {
        $update = "
		update preferencias_caja 
		set 
		valida_duplic_tipo = 'FS'
		";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    }




    header("Location: gest_parametrizar.php");
    exit;
}
//informes
if (isset($_POST['ocinfo']) && ($_POST['ocinfo']) > 0) {

    $muestrac = substr(trim($_POST['infocaja']), 0, 1);
    $update = "
	Update preferencias 
	set muestra_caja_abierta='$muestrac'
	where idempresa=$idempresa
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}
//CAJA
if (isset($_POST['occaja']) && ($_POST['occaja']) > 0) {

    $obligar = substr(trim($_POST['obligaprov']), 0, 1);
    $fijocajachica = substr(trim($_POST['mfijo1']), 0, 1);
    $fijocajarecau = substr(trim($_POST['mfijo2']), 0, 1);

    $update = "
	Update preferencias 
	set obligaprov='$obligar',hab_monto_fijo_chica='$fijocajachica',hab_monto_fijo_recau='$fijocajarecau'
	where idempresa=$idempresa
	";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    header("Location: gest_parametrizar.php");
    exit;
}
//Traemos las preferencias de la empresa
$busca = "Select * 
from preferencias 
where 
idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$consulta = "
select * 
from preferencias_caja 
limit 1
";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if ($rspref->fields['idempresa'] == '') {
    $insertar = "Insert into preferencias(idempresa) values ($idempresa)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


}

$ta = intval($rspref->fields['traslado_nostock']);
$pp = intval($rspref->fields['produccion_nostock']);
$vta = intval($rspref->fields['ventas_nostock']);
$tipoc = intval($rspref->fields['tipocompra']);
$usa_receta = trim($rspref->fields['usa_receta']);
$usa_marca = trim($rspref->fields['usa_marca']);
$usa_web = trim($rspref->fields['usa_web']);
$zonificar = trim($rspref->fields['zonificar_delivery']);
$ventacajacompleta = trim($rspref->fields['usa_vta_completa']);
$usaradherente = trim($rspref->fields['usa_adherente']);
$usa_cajachica = trim($rspref->fields['usa_cajachica']);
$pagoxcaja_rec = trim($rspref->fields['pagoxcaja_rec']);
$pagoxcaja_chic = trim($rspref->fields['pagoxcaja_chic']);
$alumnolista_idprod = intval($rspref->fields['alumnolista_idprod']);
$usa_descuento = trim($rspref->fields['usa_descuento']);
$diferencia_precio_suc = trim($rspref->fields['diferencia_precio_suc']);
//Pedidos
$borraped = trim($rspref->fields['borrar_ped']);
//Barcode-  Balanzas -
$usabarcode = trim($rspref->fields['usabcode']);

$codplu_pesable = trim($rspref->fields['codplu_pesable']);
$codplu_unitario = trim($rspref->fields['codplu_unitario']);
$usa_balanza = trim($rspref->fields['usa_balanza']);
$autopeso = trim($rspref->fields['autopeso']);
$script_balanza = trim($rspref->fields['script_balanza']);
$script_balanza = strtolower($script_balanza);
$cantcodigo = intval($rspref->fields['total_numeros_cod']);
//impresores
$script_tk = trim($rspref->fields['script_ticket']);
$script_tk = strtolower($script_tk);
$script_fc = trim($rspref->fields['script_factura']);
$script_fc = strtolower($script_fc);
$script_fclc = trim($rspref->fields['script_factura_cliente']);
$script_fclc = strtolower($script_fclc);
$muestrac = trim($rspref->fields['muestra_caja_abierta']);

$obligar = trim($rspref->fields['obligaprov']);
$mfijochica = trim($rspref->fields['hab_monto_fijo_chica']);
$mfijorec = trim($rspref->fields['hab_monto_fijo_recau']);
$obligachapa = trim($rspref->fields['obligachapa']);
$selmozo = trim($rspref->fields['selmozo']);
$factura_obliga = trim($rspref->fields['factura_obliga']);

$auto_impresor = trim($rspref->fields['auto_impresor']);
$autoimpresor = trim($rspref->fields['autoimpresor']);


$cod_plu_desde = intval($rsprefcaj->fields['cod_plu_desde']);
$cod_plu_cantdigit = intval($rsprefcaj->fields['cod_plu_cantdigit']);
$cant_plu_entero_desde = intval($rsprefcaj->fields['cant_plu_entero_desde']);
$cant_plu_entero_cantdigit = intval($rsprefcaj->fields['cant_plu_entero_cantdigit']);
$cant_plu_decimal_desde = intval($rsprefcaj->fields['cant_plu_decimal_desde']);
$cant_plu_decimal_cantdigit = intval($rsprefcaj->fields['cant_plu_decimal_cantdigit']);
$redondear_subtotal = antixss($rsprefcaj->fields['redondear_subtotal']);
$redondeo_ceros = intval($rsprefcaj->fields['redondeo_ceros']);
$redondear_direccion = antixss($rsprefcaj->fields['redondear_direccion']);
$cant_plu_entero_unit_desde = intval($rsprefcaj->fields['cant_plu_entero_unit_desde']);
$cant_plu_entero_unit_cantdigit = intval($rsprefcaj->fields['cant_plu_entero_unit_cantdigit']);


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
<?php require("includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">
     <div align="center" >
     <?php require_once("includes/menuarriba.php");?>
    </div>
	<div class="colcompleto" id="contenedor">
			<div align="center" class="resumenmini">
	    	<br />
				<p align="center" style="font-weight:bold; font-size:16px;">Configuraciones</p><br />
				Especifique las formas operativas que tendra el sistema en diferentes modulos
			
			</div>

		<p align="center">&nbsp;</p>
			<div align="center">
			<form id="f2" action="gest_parametrizar.php" method="post">
				<table width="650" border="1">
					<tr>
					  <td height="34" colspan="3" align="center"><strong><img src="img/1.png" width="32" height="32" alt=""/><br />Stock de Productos<input type="hidden" name="ocstck" id="ocstck" value="1" /></strong></td>
					</tr>
					<tr>
					  <td width="114" height="36" align="center" bgcolor="#EFF8DA"><strong>Proceso</strong></td>
						<td width="180" align="center" bgcolor="#EFF8DA"><strong>Descripci&oacute;n</strong></td>
						<td width="134" align="center" bgcolor="#EFF8DA"><strong>Condici&oacute;n</strong></td>
					</tr>
					<tr>
						<td height="35"><em>* Traslados </em></td>
						<td><strong>Trasladar sin stock en origen</strong> (afectara el costeo)</td>
				        <td align="center">
						      <input type="radio" name="primfs" <?php if ($ta == 1) {?>checked="checked"<?php }?> value="1" id="primfs_0" />
						      SI &nbsp;&nbsp;
						      <input type="radio" name="primfs"<?php if ($ta == 2) {?>checked="checked"<?php }?> value="2" id="primfs_1" />
NO</td>
					      </tr>
						  
					  
				  
				  <tr>
						<td height="33"><em>* Producci&oacute;n</em></td>
						<td><strong>Producir sin stock</strong> (afectara el costeo)</td>
						<td align="center"><input type="radio" name="sefs" value="1" <?php if ($pp == 1) {?>checked="checked"<?php }?> id="primfs_0" />
						      SI &nbsp;&nbsp;
						      <input type="radio" name="sefs" <?php if ($pp == 2) {?>checked="checked"<?php }?> value="2" id="primfs_1" />
						      NO</td>
			  </tr>
				  <tr>
						<td height="36"><em>* Ventas</em></td>
						<td><strong>Vender sin stock</strong> (afectara el costeo)</td>
						
						<td align="center"><input type="radio" name="tefs" <?php if ($vta == 1) {?>checked="checked"<?php }?> value="1" id="tfs_0" />
						      SI &nbsp;&nbsp;
						      <input type="radio" name="tefs" <?php if ($vta == 2) {?>checked="checked"<?php }?>  value="2" id="tfs_1" />
						      NO</td>
				  </tr>
				  <tr>
						<td height="33" colspan="3" align="center"><input type="submit" value="Actualizar Procesos" class="btnhotel" /></td>
				  </tr>
				</table>
				</form>
			</div>
  		<br />
	  		<div align="center">
	  		<form id="f3" action="gest_parametrizar.php" method="post">
				<table width="650" border="1">
					<tr>
					  <td height="34" colspan="3" align="center" bgcolor="#E4E4E4"><strong><img src="img/2.png" width="32" height="32" alt=""/><br />
					  Compras
					      <input type="hidden" name="ocomp" id="ocomp" value="2" />
					  </strong></td>
					</tr>
					<tr>
					  <td width="114" height="36" align="center" bgcolor="#E4E4E4"><strong>Proceso</strong></td>
						<td width="180" align="center" bgcolor="#E4E4E4"><strong>Descripci&oacute;n</strong></td>
						<td width="134" align="center" bgcolor="#E4E4E4"><strong>Condici&oacute;n</strong></td>
					</tr>
					<tr>
						<td height="35"><em>* Tipo Compra</em></td>
						<td><strong>Establece tipo de compra x defecto (Credito / Contado)</strong></td>
				        <td align="center">
						      <input type="radio" name="tc1" <?php if ($tipoc == 2) {?>checked="checked"<?php }?> value="2" id="primfs_0" />
						      CREDITO &nbsp;&nbsp;
						      <input type="radio" name="tc1" <?php if ($tipoc == 1) {?>checked="checked"<?php }?> value="1" id="primfs_1" /> 
						      CONTADO
</td>
					      </tr>
				  <tr>
						<td height="33" colspan="3" align="center"><input type="submit" value="Actualizar Compras" style="background-color: darkcyan" class="btnhotel" /></td>
				  </tr>
				</table>
			  </form>
			</div>
	  		<br />
            
            <div align="center">
	  		<form id="f3" action="gest_parametrizar.php" method="post">
				<table width="650" border="1">
					<tr>
					  <td height="34" colspan="3" align="center" bgcolor="#E4E4E4"><strong><img src="img/3.png" width="32" height="32" alt=""/><br />
					  Productos
					      <input type="hidden" name="ocprod" id="oprod" value="3" />
					  </strong></td>
					</tr>
					<tr>
					  <td width="114" height="36" align="center" bgcolor="#E4E4E4"><strong>Proceso</strong></td>
						<td width="180" align="center" bgcolor="#E4E4E4"><strong>Descripci&oacute;n</strong></td>
						<td width="134" align="center" bgcolor="#E4E4E4"><strong>Condici&oacute;n</strong></td>
					</tr>
					<tr>
						<td height="35"><em>* Usar Receta</em></td>
						<td><strong>Si no usamos receta por cada producto creara un insumo con el mismo nombre del producto para stock</strong></td>
				        <td align="center">
						      <input type="radio" name="usa_receta" <?php if ($usa_receta == 'S') {?>checked="checked"<?php }?> value="S"  />
						      SI&nbsp;&nbsp;
						      <input type="radio" name="usa_receta" <?php if ($usa_receta == 'N') {?>checked="checked"<?php }?> value="N"  /> 
						      NO
</td>
			      </tr>
					<tr>
						<td height="35"><em>* Usar Marca</em></td>
						<td><strong>Normalmente se utiliza para ventas de productos donde la marca es importante</strong></td>
				        <td align="center">
						      <input type="radio" name="usa_marca" <?php if ($usa_marca == 'S') {?>checked="checked"<?php }?> value="S"  />
						      SI&nbsp;&nbsp;
						      <input type="radio" name="usa_marca" <?php if ($usa_marca == 'N') {?>checked="checked"<?php }?> value="N"  /> 
						      NO
</td>
			      </tr>
					<tr>
					  <td height="35"><em>* Usar Web</em></td>
					  <td><strong>Habilita campos que se utilizaran en el website</strong></td>
					  <td align="center"><input type="radio" name="usa_web" <?php if ($usa_web == 'S') {?>checked="checked"<?php }?> value="S"  />
					    SI&nbsp;&nbsp;
					    <input type="radio" name="usa_web" <?php if ($usa_web == 'N') {?>checked="checked"<?php }?> value="N"  />
					    NO </td>
				  </tr>
				  <tr>
						<td height="33" colspan="3" align="center"><input type="submit" value="Actualizar Productos" style="background-color: darkcyan" class="btnhotel" /></td>
				  </tr>
				</table>
			  </form>
				<br />
				
			  <div align="center">
	  				<form id="f4" action="gest_parametrizar.php" method="post">
						<table width="650" border="1">
							<tr>
							  <td height="34" colspan="3" align="center" bgcolor="#E4E4E4"><strong><img src="img/4.png" width="32" height="32" alt=""/><br />
							  VENTAS
								  <input type="hidden" name="ocvta" id="ocvta" value="4" />
							  </strong></td>
							</tr>
					<tr>
					  <td width="80" height="36" align="center" bgcolor="#E4E4E4"><strong>Proceso</strong></td>
						<td width="406" align="center" bgcolor="#E4E4E4"><strong>Descripci&oacute;n</strong></td>
						<td width="142" align="center" bgcolor="#E4E4E4"><strong>Condici&oacute;n</strong></td>
					</tr>
                    
                    
							<tr>
						<td height="35"><em>* Habilita Ticket</em></td>
						<td><strong>Si se facturan el 100% de las ventas o se permite ticket de prueba.</strong></td>
				        <td align="center">
						      <input type="radio" name="factura_obliga" <?php if ($factura_obliga == 'N') {?>checked="checked"<?php }?> value="N"  /> 
						      SI
						      <input type="radio" name="factura_obliga" <?php if ($factura_obliga == 'S') {?>checked="checked"<?php }?> value="S"  />
						      NO&nbsp;&nbsp;

</td>
			      </tr>
                    
                    
							<tr>
						<td height="35"><em>* Activar Adherentes</em></td>
						<td><strong>Los adherentes dependen de un responsable, y su consumo le ser&aacute; facturado al titular de la cuenta.Todas las ventas se&aacute;n a cr&eacute;dito, pero individualizadas/separadas por adherente.</strong></td>
				        <td align="center">
						      <input type="radio" name="activaad" <?php if ($usaradherente == 'S') {?>checked="checked"<?php }?> value="S"  />
						      SI&nbsp;&nbsp;
						      <input type="radio" name="activaad" <?php if ($usaradherente == 'N') {?>checked="checked"<?php }?> value="N"  /> 
						      NO
</td>
			      </tr>
					<tr>
						<td height="35"><em>* Venta x caja Completa</em></td>
						<td><strong>Utiliza el formato de caja tradicional, con selecci&oacute;n de medios de pago. Si se activan las cuentas de adherentes a cr&eacute;dito, se agrega un bot&oacute;n m&aacute;s el cual es venta adherente</strong></td>
				        <td align="center">
						      <input type="radio" name="vtacaja" <?php if ($ventacajacompleta == 'S') {?>checked="checked"<?php }?> value="S"  />
						      SI&nbsp;&nbsp;
						      <input type="radio" name="vtacaja" <?php if ($ventacajacompleta == 'N') {?>checked="checked"<?php }?> value="N"  /> 
						      NO
</td>
			      </tr>
					
					<tr>
					  <td height="35"><em>* Zonificar Delivery</em></td>
					  <td><strong>El costo x delivery est&aacute; basado en zonas y al momento de cobrar debe seleccionar la zona de entrega. Si es <strong>NO</strong>, debe incluir su delivery como un producto (<strong>no recomendado</strong>).</strong></td>
					  <td align="center"><input type="radio" name="radiozona" <?php if ($zonificar == 'S') {?>checked="checked"<?php }?> value="S"  />
					    SI&nbsp;&nbsp;
					    <input type="radio" name="radiozona" <?php if ($zonificar == 'N') {?>checked="checked"<?php }?> value="N"  />
					    NO </td>
				  </tr>
					<tr>
					  <td height="35">* Usar Caja Chica</td>
					  <td>Caja separada de la recaudaci&oacute;n</td>
					  <td align="center"><input type="radio" name="usa_cajachica" <?php if ($usa_cajachica == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="usa_cajachica" <?php if ($usa_cajachica == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </td>
					  </tr>
					<tr>
					  <td height="35">* Borrar Pedidos</td>
					  <td>Permite borrar los pedidos NO COBRADOS .</td>
					  <td align="center"><input type="radio" name="borraped" <?php if ($borraped == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="borraped" <?php if ($borraped == 'N') {?>checked="checked"<?php }?> value="N"  />
NO</td>
					  </tr>
					<tr>
					  <td height="35">* Pagos por Caja Recaudaci&oacute;n</td>
					  <td>Permite Pagos que se descontar&aacute;n de la Caja de Recaudaci&oacute;n (ventas) para calcular el faltante o sobrante</td>
					  <td align="center"><input type="radio" name="pagoxcaja_rec" <?php if ($pagoxcaja_rec == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="pagoxcaja_rec" <?php if ($pagoxcaja_rec == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </td>
					  </tr>
					<tr>
					  <td height="35">*  Pagos por Caja Chica</td>
					  <td>Permite Pagos que se descontar&aacute;n de la Caja Chica para el faltante o sobrante</td>
					  <td align="center"><input type="radio" name="pagoxcaja_chic" <?php if ($pagoxcaja_chic == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="pagoxcaja_chic" <?php if ($pagoxcaja_chic == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </td>
					  </tr>
					<tr>
					  <td height="35">* Producto por Defecto en Lista Alumnos</td>
					  <td>Establece el producto que se registrara al marcar como retirado en la tablet de la lista de alumnos. indique el codigo de producto.</td>
					  <td align="center"><input type="number"  name="alumnolista_idprod" id="alumnolista_idprod" style="height: 40px; width: 99%;" value="<?php echo $alumnolista_idprod?>" /></td>
					  </tr>
					<tr>
					  <td height="35">* Obliga Nombre/Chapa</td>
					  <td>Indica si es obligatorio completar este campo en el modulo de toma de pedidos por tablet.</td>
					  <td align="center"><input type="radio" name="obligachapa" <?php if ($obligachapa == 'S') {?>checked="checked"<?php }?> value="S"  />
					    SI&nbsp;&nbsp;
					    <input type="radio" name="obligachapa" <?php if ($obligachapa == 'N') {?>checked="checked"<?php }?> value="N"  />
					    NO</td>
					  </tr>
					<tr>
					  <td height="35">* Seleccionar Mozo</td>
					  <td>Permite que el cajero pueda seleccionar a que mozo pertenece un ticket.</td>
					  <td align="center"><input type="radio" name="selmozo" <?php if ($selmozo == 'S') {?>checked="checked"<?php }?> value="S"  />
					    SI&nbsp;&nbsp;
					    <input type="radio" name="selmozo" <?php if ($selmozo == 'N') {?>checked="checked"<?php }?> value="N"  />
					    NO </td>
					  </tr>
					<tr>
					  <td height="35">* Usar Descuento</td>
					  <td>si se permitira hacer descuento en caja</td>
					  <td align="center"><input type="radio" name="usa_descuento" <?php if ($usa_descuento == 'S') {?>checked="checked"<?php }?> value="S"  />
					    SI&nbsp;&nbsp;
					    <input type="radio" name="usa_descuento" <?php if ($usa_descuento == 'N') {?>checked="checked"<?php }?> value="N"  />
					    NO </td>
					  </tr>
					<tr>
					  <td height="35">* Variar Precios y Productos por Sucursal</td>
					  <td>Permite tener precios diferentes para un mismo producto en otra sucursal y tambien mostrar o no un producto en las sucursales asignadas</td>
					  <td align="center"><input type="radio" name="diferencia_precio_suc" <?php if ($diferencia_precio_suc == 'S') {?>checked="checked"<?php }?> value="S"  />
					    SI&nbsp;&nbsp;
					    <input type="radio" name="diferencia_precio_suc" <?php if ($diferencia_precio_suc == 'N') {?>checked="checked"<?php }?> value="N"  />
					    NO </td>
					  </tr>
					<tr>
					  <td height="35" colspan="3" align="center"><strong>CODIGOS DE BARRA Y BALANZA:</strong></td>
					  </tr>
					<tr>
					  <td height="35">* Usar Cod Barras PLU</td>
					  <td>Habilita la busqueda x codigo de barras en ventas x caja. ATENCION: debe programar la balanza para que imprima el tickete con el codigo PLU.Estilo Supermercado.  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="radio" name="usabcode" <?php if ($usabarcode == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="usabcode" <?php if ($usabarcode == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </td>
					  </tr>
					<tr>
					  <td height="35">* COD PLU Pesable</td>
					  <td>Indica el c&oacute;digo inicial pesable para el sistema. <strong>Ej: 20 |</strong> Debe programarse en la balanza(num&eacute;rico solamente).  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number" value="<?php echo $codplu_pesable?>" name="pesable" id="pesable" style="height: 40px; width: 99%;" /> </td>
					  </tr>
					
					  <td height="35">* COD PLU Unitario</td>
					  <td>Indica el c&oacute;digo inicial unitario para el sistema.<strong> Ej: 21 </strong> Debe programarse en la balanza(num&eacute;rico solamente)  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="uni" id="uni" style="height: 40px; width: 99%;" value=<?php echo $codplu_unitario?> /></td>
					  </tr>
						<tr>
					  <td height="35">* Cantidad N&uacute;meros en C&oacute;digo</td>
					  <td>Indica la cantidad de numeros que componen el c&oacute;digo del producto impreso en el tickete. <strong>Ej: 13</strong>.  <strong>ATENCION: NO INCLUYA EL COD PLU EN LA CANTIDAD DECLARADA. Si la cantidad indicada en esta secci&oacute;n es ingresada incorrectamente; <strong>el peso NO ser&aacute; obtenido por el sistema como corresponde, pudiendo ocasionar un c&aacute;lculo incorrecto, tanto para la venta, como para el descuento de stock. </strong></strong>  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="totcodigo" id="totcodigo" style="height: 40px; width: 99%;" value="<?php echo $cantcodigo?>" /></td>
					  </tr>
					<tr>
					  <td height="35">* Usar Balanza</td>
					  <td>Indica al sistema la lectura de una balanza programada en red / local. Adem&aacute;s muestra una columna para la captura del peso en el m&oacute;dulo de ventas x caja.</td>
					  <td align="center"><input type="radio" name="usabalanza" <?php if ($usa_balanza == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="usabalanza" <?php if ($usa_balanza == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </td>
					  </tr>
					<tr>
					  <td height="35">* Script Balanza</td>
					  <td>Indica el script a usar para captura de datos via ajax.Debe instalarse en cliente local.</td>
					  <td align="center"><input type="text" value="<?php echo ($script_balanza)?>" name="scriptb" id="scriptb" style="height: 40px; width: 99%;" /></td>
					  </tr>
					<tr>
					  <td height="35">* Auto Captura Peso</td>
					  <td>Indica si al obtener el peso de la balanza se agrega autom&aacute;ticamente al carrito.</td>
					  <td align="center"><input type="radio" name="autopeso" <?php if ($autopeso == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="autopeso" <?php if ($autopeso == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </td>
					  </tr>
					<tr>
					  <td height="35"><p>Codigo PLU posicion desde</p>
				      <p>cod_plu_desde</p></td>
					  <td>Posicion inicial del codigo plu, se refiere al codigo de producto configurado en la balanza, no el codigo de producto de nuestro sistema. <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="cod_plu_desde" id="cod_plu_desde" style="height: 40px; width: 99%;" value="<?php echo $cod_plu_desde; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35"><p>Codigo PLU cantidad digitos</p>
				      <p>cod_plu_cantdigit</p></td>
					  <td>es la cantidad de digitos del codigo plu desde la posicion inicial.  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="cod_plu_cantdigit" id="cod_plu_cantdigit" style="height: 40px; width: 99%;" value="<?php echo $cod_plu_cantdigit; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35"><p><br />
					    Cantidad PLU Entero posicion Desde  (para productos Pesables)</p>
					    <p>cant_plu_entero_desde<br />
				      </p></td>
					  <td>Posicion inicial del peso en el codigo de barras, solo la parte entera del peso, en kilos sin tener en cuenta los gramos.  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="cant_plu_entero_desde" id="cant_plu_entero_desde" style="height: 40px; width: 99%;" value="<?php echo $cant_plu_entero_desde; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35"><p>Cantidad PLU Entero cantidad digitos  (para productos Pesables)</p>
					    <p>cant_plu_entero_cantdigit<br />
				      </p></td>
					  <td>Cantidad de digitos de la parate entera del peso desde la posicion inicial.  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="cant_plu_entero_cantdigit" id="cant_plu_entero_cantdigit" style="height: 40px; width: 99%;" value="<?php echo $cant_plu_entero_cantdigit; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35"><p>Cantidad PLU Decimal posicion Desde   (para productos Pesables)</p>
					    <p>cant_plu_decimal_desde<br />
				      </p></td>
					  <td>Posicion inicial del peso en el codigo de barras, solo la parte decimal del peso, seria los gramos.  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="cant_plu_decimal_desde" id="cant_plu_decimal_desde" style="height: 40px; width: 99%;" value="<?php echo $cant_plu_decimal_desde; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35"><p>Cantidad PLU Decimal cantidad digitos   (para productos Pesables)</p>
					    <p>cant_plu_decimal_cantdigit<br />
				      </p></td>
					  <td>Cantidad de digitos de la parate decimal del peso desde la posicion inicial, solo los gramos.  <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="cant_plu_decimal_cantdigit" id="cant_plu_decimal_cantdigit" style="height: 40px; width: 99%;" value="<?php echo $cant_plu_decimal_cantdigit; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35"><p>Cantidad PLU Entero posicion Desde (para productos Unitarios)</p>
				      <p>cant_plu_entero_unit_desde</p></td>
					  <td>Posicion inicial de la cantidad en el codigo de barras (solo para productos unitarios) debe ser un numero enterio positivo. <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="cant_plu_entero_unit_desde" id="cant_plu_entero_unit_desde" style="height: 40px; width: 99%;" value="<?php echo $cant_plu_entero_unit_desde; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35"><p>Cantidad PLU Entero cantidad de digitos (para productos Unitarios)</p>
				      <p>cant_plu_entero_unit_cantdigit</p></td>
					  <td>Cantidad de digitos de la cantidad desde la posicion inicial (solo para productos unitarios) . <a href="test_codplu.php" target="_blank">[Tester]</a></td>
					  <td align="center"><input type="number"  name="cant_plu_entero_unit_cantdigit" id="cant_plu_entero_unit_cantdigit" style="height: 40px; width: 99%;" value="<?php echo $cant_plu_entero_unit_cantdigit; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35">redondear_subtotal<br /></td>
					  <td>si redondea el subtotal (Peso*Precio Unitario) en caso que tenga decimales se puede redondear</td>
					  <td align="center">
						  
						  <?php
// valor seleccionado
if (isset($_POST['redondear_subtotal'])) {
    $value_selected = htmlentities($_POST['redondear_subtotal']);
} else {
    $value_selected = $redondear_subtotal;
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'redondear_subtotal',
    'id_campo' => 'redondear_subtotal',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>
						
						</td>
					  </tr>
					<tr>
					  <td height="35">redondeo_ceros<br /></td>
					  <td>En caso que el redondeo este activo a cuantos ceros enteros redondear</td>
					  <td align="center"><input type="number"  name="redondeo_ceros" id="redondeo_ceros" style="height: 40px; width: 99%;" value="<?php echo $redondeo_ceros; ?>" /></td>
					  </tr>
					<tr>
					  <td height="35">redondear_direccion </td>
					  <td>Direccion del redondeo (Normal (0,5), Arriba o Abajo)</td>
					  <td align="center">
 <?php
// valor seleccionado
if (isset($_POST['redondear_direccion'])) {
    $value_selected = htmlentities($_POST['redondear_direccion']);
} else {
    $value_selected = $redondear_direccion;
}
// opciones
$opciones = [
    'NORMAL' => 'N',
    'ARRIBA' => 'A',
    'ABAJO' => 'B'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'redondear_direccion',
    'id_campo' => 'redondear_direccion',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);


?>
</td>
					  </tr>
				  <tr>
						<td height="33" colspan="3" align="center"><input type="submit" value="Actualizar Ventas" style="background-color: darkcyan" class="btnhotel" /></td>
				  </tr>
				</table>
			  </form>
				
				<br />
				<form id="f6" action="gest_parametrizar.php" method="post" >
				  <table  width="650" border="1">
						 <td height="34" colspan="3" align="center"><strong><img src="img/5.png" width="32" height="32" alt=""/><br />
						 Comportamiento Impresor
						       <input type="hidden" name="ocimpre" id="ocimpre" value="1" /></strong></td>
						<tr>
					<tr>
					  <td width="80" height="36" align="center" bgcolor="#E4E4E4"><strong>Proceso</strong></td>
						<td width="406" align="center" bgcolor="#E4E4E4"><strong>Descripci&oacute;n</strong></td>
						<td width="142" align="center" bgcolor="#E4E4E4"><strong>Condici&oacute;n</strong></td>
					</tr>
							<td width="80">* Impresor Ticketes  lado cliente</td>
							<td width="410">Indique el script utilizado para la impresion de ticketes en el sistema</td>
							<td width="138"><input type="text" value="<?php echo ($script_tk)?>" name="impretk" id="impretk" style="height: 40px; width: 99%;" /></td>
						</tr>
					<tr>
							<td width="80">* Impresor Facturas lado servidor</td>
							<td width="410">Indique el script utilizado para la impresion de facturas en el sistema.ATENCION: este modulo trabaja con el impresor de windows Vfp.</td>
							<td width="138"><input type="text" value="<?php echo ($script_fc)?>" name="imprefc" id="imprefc" style="height: 40px; width: 99%;" /></td>
						</tr>
					<tr>
							<td width="80">* Impresor Facturas lado cliente</td>
							<td width="410">Indique el script utilizado para la impresion de facturas lado cliente.ATENCION: este modulo trabaja con el impresor de windows Vfp.</td>
							<td width="138"><input type="text" value="<?php echo ($script_fclc)?>" name="imprefclc" id="imprefclc" style="height: 40px; width: 99%;" /></td>
						</tr>
					<tr>
					  <td>Auto Impresor Factura</td>
					  <td>Por defecto autoimpresor o preimpreso, esto se sobreescribe en el timbrado para preimpresos combinados con autoimpresor</td>
					  <td align="center"><strong>
						    <input type="radio" name="autoimpresor" <?php if ($autoimpresor == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="autoimpresor" <?php if ($autoimpresor == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </strong></td>
				    </tr>
					<tr>
					  <td>Auto Impresor Nota Cred</td>
					  <td>Auto impresor o Preimpreso para notas de credito</td>
					  <td align="center"><strong>
						    <input type="radio" name="auto_impresor" <?php if ($auto_impresor == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="auto_impresor" <?php if ($auto_impresor == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </strong></td>
				    </tr>
		<tr>
						<td height="33" colspan="3" align="center"><input type="submit" value="Actualizar Impresion" style="background-color: darkcyan" class="btnhotel" /></td>
				  </tr>
				  </table>
				</form>
				<br />
				<form id="f7" action="gest_parametrizar.php" method="post" >
					<table  width="650" border="1">
						 <td height="34" colspan="3" align="center"><strong><img src="img/6.png" width="32" height="32" alt=""/><br />
						 Informes
						       <input type="hidden" name="ocinfo" id="ocinfo" value="1" /></strong></td>
						<tr>
					  <td width="80" height="36" align="center" bgcolor="#E4E4E4"><strong>Proceso</strong></td>
						<td width="410" align="center" bgcolor="#E4E4E4"><strong>Descripci&oacute;n</strong></td>
						<td width="142" align="center" bgcolor="#E4E4E4"><strong>Condici&oacute;n</strong></td>
					</tr>
							  <td width="80">*Informe de Caja</td>
							<td width="410">Permite ver resumen y detalle si la caja est&aacute; abierta</td>
							<td width="142" align="center"><strong>
						    <input type="radio" name="infocaja" <?php if ($muestrac == 'S') {?>checked="checked"<?php }?> value="S"  />
SI&nbsp;&nbsp;
<input type="radio" name="infocaja" <?php if ($muestrac == 'N') {?>checked="checked"<?php }?> value="N"  />
NO </strong></td>
						</tr>
					</tr>
		<tr>
						<td height="33" colspan="3" align="center"><input type="submit" value="Actualizar Informes" style="background-color: darkcyan" class="btnhotel" /></td>
				  </tr>
					</table>
				</form>
<br />
				<!-------------------------------CAJA------------------------------->
				<form id="f8" action="gest_parametrizar.php" method="post" >
					<table  width="650" border="1">
						<tr>
						 <td height="34" colspan="3" align="center">
							 <strong><img src="img/7.png" width="32" height="32" alt=""/>
							 <br />
						 		CAJA
						       <input type="hidden" name="occaja" id="occaja" value="1" /></strong></td>
						</tr>	
						<tr>
					  		<td width="80" height="36" align="center" bgcolor="#E4E4E4"><strong>Proceso</strong></td>
							<td width="410" align="center" bgcolor="#E4E4E4"><strong>Descripci&oacute;n</strong></td>
							<td width="142" align="center" bgcolor="#E4E4E4"><strong>Condici&oacute;n</strong></td>
						</tr>
					  <tr>
						<td width="80">*Proveedor obligatorio</td>
							<td width="410">Indica si el proveedor mostrado en la caja, ser&aacute; de uso obligatorio para registrar el gasto asociado.</td>
							<td width="142" align="center"><strong>
						    <input type="radio" name="obligaprov" 
								   <?php if ($obligar == 'S') {?>checked="checked"<?php }?> value="S"  />
									SI&nbsp;&nbsp;
									<input type="radio" name="obligaprov"
										   <?php if ($obligar == 'N') {?>checked="checked"<?php }?> value="N"  />
						NO </strong></td>
						</tr>
						<tr>
							<td width="80">*Monto Fijo x caja Chica</td>
						  <td width="410">Indica si el sistema permite registrar un monto fijo para uso en caja chica</td>
							<td width="142" align="center"><strong>
						    <input type="radio" name="mfijo1" 
								   <?php if ($mfijochica == 'S') {?>checked="checked"<?php }?> value="S"  />
									SI&nbsp;&nbsp;
							<input type="radio" name="mfijo1"
										   <?php if ($mfijochica == 'N') {?>checked="checked"<?php }?> value="N"  />
									NO </strong></td>
						</tr>
						<tr>
							<td width="80">*Monto Fijo x caja recaudaci&oacute;n</td>
						  <td width="410">Indica si el sistema permite registrar un monto fijo para uso en caja recaudaci&oacute;n</td>
							<td width="142" align="center"><strong>
						    <input type="radio" name="mfijo2" 
								   <?php if ($mfijorec == 'S') {?>checked="checked"<?php }?> value="S"  />
									SI&nbsp;&nbsp;
							<input type="radio" name="mfijo2"
										   <?php if ($mfijorec == 'N') {?>checked="checked"<?php }?> value="N"  />
									NO </strong></td>
						</tr>
						<tr>
                        
							<td height="33" colspan="3" align="center">
								<input type="submit" value="Actualizar Caja" style="background-color: darkcyan" class="btnhotel" />
							</td>
				  		</tr>
					</table>
				</form>
			</div>
            
            
  </div>
	  <!-- SECCION DONDE COMIENZA TODO -->
  	</div> <!-- contenedor -->
   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>