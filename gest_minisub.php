 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "186";
require_once("includes/rsusuario.php");
if (intval($totalacum) == 0) {

    $totalacum = intval($_POST['ta']);
}
$buscar = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$controlfactura = intval($rspref->fields['controlafactura']);
//Traemos la numeracion de factura secuencia x sucursal y punto expedicion
if ($controlfactura == 1) {

    $ano = date("Y");
    // busca si existe algun registro
    $buscar = "Select max(numfac) as mayor from lastcomprobantes where idsuc=$idsucursal and pe=$pe and idempresa=$idempresa order by ano desc limit 1";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    if ($maxnfac <= 1) {

        $consulta = "
                    INSERT INTO lastcomprobantes
                    (idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
                    numhoja, hojalevante, idempresa) 
                    VALUES
                    ($idsucursal, 0, $maxnfac, NULL, 0, NULL, 0, $ano, $pe, NULL, 
                    NULL, 0, '', $idempresa)
                    ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
}
//Buscamos si hay cabecera, sino usamos el tradicional
$buscar = "Select * from cabeceras where idempresa=$idempresa and idsucursal=$idsucursal";
$rcab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
if ($rcab->fields['sucursal'] != '') {
    $parte1 = intval($rcab->fields['sucursal']);
    $parte2 = intval($rcab->fields['puntoexp']);
    $a = strlen($parte1);
    $b = strlen($parte2);
    $part1 = '';
    $part2 = '';
    if ($a < 3) {
        $df = 3 - $a;
        for ($i = 1;$i <= $df;$i++) {
            $part1 = $part1.'0';
        }

    }
    $parte1f = $part1.$parte1;
    if ($b < 3) {
        $df = 3 - $b;
        for ($i = 1;$i <= $df;$i++) {
            $part2 = $part2.'0';
        }

    }
    $parte2f = $part2.$parte2;
} else {
    $parte1f = '001';
    $parte2f = '001';


}
//echo $controlfactura;
?>
<?php
$ahorad = date("Y-m-d");
$buscar = " select * from (
              
                    select IFNULL((
                        select cotizaciones.cotizacion
                        from cotizaciones
                        where 
                        cotizaciones.estado = 1 
                        and date(cotizaciones.fecha) = '$ahorad'
                        and tipo_moneda.idtipo = cotizaciones.tipo_moneda
                        order by cotizaciones.fecha desc
                        limit 1
                        ),0) as cotizacion,
                        banderita,
                         (
                        select cotizaciones.idcot
                        from cotizaciones
                        where 
                        cotizaciones.estado = 1 
                        and date(cotizaciones.fecha) = '$ahorad'
                        and tipo_moneda.idtipo = cotizaciones.tipo_moneda
                        order by cotizaciones.fecha desc
                        limit 1
                        ) as idcot,
                        tipo_moneda,descripcion
                            from cotizaciones
                            inner join tipo_moneda 
                            on tipo_moneda.idtipo=cotizaciones.tipo_moneda
                            where 
                                cotizaciones.estado = 1 
                                group by cotizaciones.tipo_moneda
                                order by descripcion asc
                        
                        ) as vtacor where cotizacion > 0 order by descripcion asc
              
                      ";

$rscotiza = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tcotiza = $rscotiza->RecordCount();

?><br />
<div>
<table width="100%"  style="border-collapse:collapse; border: none;">
    <?php
    if ($ruc == '') {
        $ruc = $ruc_pred;
    };
if ($razonsocial == '') {
    $razonsocial = $razon_social_pred;
};
if ($idcliente == 0) {

    $buscar = "Select * from cliente where borrable='N' limit 1";
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcliente = intval($rscli->fields['idcliente']);
}

?>
                    <tr>
                      <td height="34"  colspan="2" bgcolor="#DEDEDE" style="height: 20px;"><strong>Tipo Venta </strong></td>
                      <td align="left" style="font-size:1.3em; font-weight: bold;" id="rucbox2"><select name="tipoventa" id="tipoventa" style="height: 40px;width: 99%;">
                          <option value="1" selected="Selected">CONTADO</option>
                           <option value="2" <?php if (isset($_POST['tipoventa']) == 2) {?>selected="Selected"<?php } ?>>CREDITO</option>
                          </select></td>
    </tr>
                    <tr>
                        <td height="34"  colspan="2" bgcolor="#DEDEDE" style="height: 20px;"><input type="hidden" name="ccliedefe" id="ccliedefe" value="<?php echo '1'?>" />
                            <input type="hidden" name="occliente" id="occliente" value="<?php echo $idcliente?>" /><strong>RUC</strong></td>
                      <td width="70%" align="left" style="font-size:1.3em; font-weight: bold;" id="rucbox"><?php echo($ruc);?></td>
                    </tr>
                    <tr>
                        <td height="32"  colspan="2" bgcolor="#DEDEDE"><strong>Razon Social:</strong></td>
                      <td width="70%" align="left" style="font-size:1.3em; font-weight: bold;" ><span id="rzbox"><?php echo($razonsocial);?></span><input type="text" name="rzc" id="rzc" value="" style="height: 40px;width:99%; display: none" /></td>
                    </tr>
    <?php if ($rsco->fields['usa_chapa'] == 'S') {?>
    <tr >
      <td height="39"  colspan="2" bgcolor="#DEDEDE"><strong>Chapa / Nombre / Otros: </strong></td>
      <td align="center"><input type="text" name="chapa" id="chapa" style="height: 40px; width: 99%;text-align: left;font-size:1.2em; font-weight: bold;" readonly="readonly" /></td>
    </tr>
    <?php }?>
                <tr>
                    <td height="32"  colspan="2" bgcolor="#DEDEDE"><strong>Total Pagar GS:</strong></td>
                  <td width="70%" align="left"><span style="font-size:1.6em; font-weight: bold;"><?php echo formatomoneda($totalacum);?></span><input type="hidden"  name="totalventaoc" id="totalventaoc" value="<?php echo $totalacum;?>" /></td>
                </tr>
    
            
</table>
<!--------------------MONEDAS EXTRANJERAS------------------->
<?php
if ($tcotiza > 0) {
    //hay monedas ex y cargadas zlas cotizaciones

    ?>
<table width="100%"  style="border-collapse:collapse; border: none;">        
  <tr>
    <td colspan="4" style="background-color: antiquewhite; text-align: center"><strong> Otras Monedas </strong></td>
 </tr>
 <?php while (!$rscotiza->EOF) {
     $banderita = trim($rscotiza->fields['banderita']);
     $cotidia = floatval($rscotiza->fields['cotizacion']);
     ?>
 <tr>
    <td width="12%"><?php if ($banderita != '') { ?><img src="img/<?php echo $banderita?>" height="40px;" width="70px;"/><?php } ?></td>
     <td width="19%" align="center"><?php echo ($cotidia)?></td>
    <td width="29%" align="left"><?php echo $rscotiza->fields['descripcion']?></td>
    <td width="40%" align="center"><?php if ($cotidia > 0) {
        echo formatomoneda(($totalacum / $cotidia), 2);
    } else {
        echo 'N/C';
    }?></td>
</tr>
              <?php $rscotiza->MoveNext();
 } ?>
              
            </table>
<?php }?>
<!----------------------------------------------->
<table width="100%"  style="border-collapse:collapse; border: none;">
    <tr>
        <td  colspan="2" bgcolor="#EADEDE"><strong>Monto Recibido GS:</strong></td>
      <td width="70%" align="center"><input type="text" name="mrecibe"  id="mrecibe" style="height: 40px; width: 99%; text-align: left;font-size:1.2em; font-weight: bold;" onKeyUp="calcular(this.value);" /></td>
    </tr>
    
    <tr id="vueltogs" style="display:none">
        <td  colspan="2" bgcolor="#EADEDE"><strong>Vuelto GS:</strong></td>
      <td width="70%" align="center"><input type="text" name="vueltogsr"  id="vueltogsr" style="height: 40px; width: 99%;text-align: left;font-size:1.2em; font-weight: bold;" readonly="readonly" /></td>
    </tr>
    <tr>
        <td colspan="2" bgcolor="#EADEDE"><strong>Medio Pago: </strong></td>
        <td width="70%" align="center" style="font-size: 1 em;"><select name="mpago" id="mpago" style="height: 40px; width: 99%;">
            <option value="1" selected="selected">EFECTIVO</option>
            <option value="4" <?php if ($tv == 4) {?>selected="selected"<?php }?>>T. DEBITO</option>
            <option value="2" <?php if ($tv == 2) {?>selected="selected"<?php }?>>T. CREDITO</option>
            <option value="5" <?php if ($tv == 5) {?>selected="selected"<?php }?>>CHEQUE</option>
            <option value="7" <?php if ($tv == 7) {?>selected="selected"<?php }?>>VTA CRED</option>
            <option value="8" <?php if ($tv == 8) {?>selected="selected"<?php }?>>ADHERENTE</option>
            </select></td>
    </tr>
    <tr style="display: none">
        <td colspan="2" bgcolor="#EADEDE"><strong>Numero / Referencia / Otros: </strong></td>
        <td width="70%" align="center" style="font-size: 1 em;"><select name="refere" id="refere" style="height: 40px; width: 99%;">
            <option value="1" selected="selected">EFECTIVO</option>
            <option value="4" <?php if ($tv == 4) {?>selected="selected"<?php }?>>T. DEBITO</option>
            <option value="2" <?php if ($tv == 2) {?>selected="selected"<?php }?>>T. CREDITO</option>
            <option value="5" <?php if ($tv == 5) {?>selected="selected"<?php }?>>CHEQUE</option>
            <option value="7" <?php if ($tv == 7) {?>selected="selected"<?php }?>>VTA CRED</option>
            <option value="8" <?php if ($tv == 8) {?>selected="selected"<?php }?>>ADHERENTE</option>
            </select></td>
    </tr>
    <tr>
    <td colspan="4" align="center"><input name="pref1" type="text" id="pref1" value="<?php echo $parte1f?>" size="3" maxlength="3" style="height: 40px;"><input name="pref2" type="text" id="pref2" value="<?php echo $parte2f?>" size="3" maxlength="3" style="height: 40px;"><input type="text" name="fact" id="fact" value="<?php echo $maxnfac?>" style="height: 40px;" size="5" /><input type="button" value="F - FACTURA"  style="height: 40px; background-color: blanchedalmond;font-weight: bold" onclick="registrar_venta(1)"; /> &nbsp;&nbsp; <input type="button" value="T - TICKETE"  style="height: 40px;font-weight: bold" onclick="registrar_venta(2)"; /> </td>
    </tr>
    </table>
    </div>
