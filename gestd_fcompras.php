<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");

//Listamos los productos en detalle
$consulta = "
Select * , (
select productos.barcode 
from productos 
inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
where
tmpcompradeta.idprod = insumos_lista.idinsumo
) as barcode,
(
select costo 
from insumos_lista 
where 
idinsumo = tmpcompradeta.idprod
) as ultcosto
from tmpcompradeta 
where idt=$idtransaccion 
and idemp=$idempresa 
order by  idregcc desc
limit 1
";
//echo $consulta;
$rsdetult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><?php if ($tdet > 0) {?>
  <hr />
 Ultimo Cargado: <?php echo $rsdetult->fields['pchar']; ?> [<?php echo $rsdetult->fields['idprod']?>] - <?php echo $rsdetult->fields['barcode']?><br />
 <?php if ($rsdetult->fields['costo'] != $rsdetult->fields['ultcosto']) { ?>
 <span style="color:#F00;">COSTO CAMBIADO! Costo Factura: <?php echo formatomoneda($rsdetult->fields['costo'], 4, 'N'); ?> | Costo Anterior: <?php echo formatomoneda($rsdetult->fields['ultcosto'], 4, 'N'); ?></span><br />
 <?php } ?>
 <br />
 
    	<span class="resaltaazul">Productos comprados</span>
        <table width="1140" border="1" class="tablaconborde">
  <tbody>
    <tr>
      <td  align="center" bgcolor="#F8FFCC"><strong>Cod Prod</strong></td>
      <td  align="center" bgcolor="#F8FFCC"><strong>Cod Barra</strong></td>
      <td  align="center" bgcolor="#F8FFCC"><strong>Producto</strong></td>
      <td  align="center" bgcolor="#F8FFCC"><strong>Cantidad</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>P.U.</strong></td>
      <td  align="center" bgcolor="#F8FFCC"><strong>Sub Total</strong></td>
      <td  align="center" bgcolor="#F8FFCC"><strong>IVA</strong></td>
      <td  align="center" bgcolor="#F8FFCC"><strong>Acci&oacute;n</strong></td>
      </tr>
      <?php
       $t = 0;
    $tiva10 = 0;
    $tiva5 = 0;
    $texenta = 0;
    $tventa10 = 0;
    $tventa5 = 0;
    while (!$rsdet->EOF) {
        $cant = ($rsdet->fields['cantidad']);
        $costo = $rsdet->fields['costo'];
        $subt = $rsdet->fields['subtotal'];
        $t = $t + $subt;
        $iva = intval($rsdet->fields['iva']);
        $cant_acum += $cant;
        $subt_acum += $subt;

        if ($iva == 5) {
            $tm = ($subt / 21);

            $xp = explode('.', $tm);
            $entero = $xp[0];
            $v = substr($xp[1], 0, 1);
            //Compraramos si el primer caracter es smayor a 5
            if (intval($v > 5)) {
                $entero = $entero + 1;
            } else {
                $entero = $entero;
            }
            $ivaf = $entero;
            $tiva5 = $tiva5 + $ivaf;

            $tventa5 = $tventa5 + $subt;
        }
        if ($iva == 10) {
            $tm = ($subt / 11);

            $xp = explode('.', $tm);
            $entero = $xp[0];
            $v = substr($xp[1], 0, 1);
            //Compraramos si el primer caracter es smayor a 5
            if (intval($v > 5)) {
                $entero = $entero + 1;
            } else {
                $entero = $entero;
            }
            $ivaf = $entero;
            $tiva10 = $tiva10 + $ivaf;

            $tventa10 = $tventa10 + $subt;
        }

        $costo_factura = $rsdet->fields['costo'];
        $costo_ultimo = $rsdet->fields['ultcosto'];
        ?>
        <tr>
          <td align="center"><strong><?php echo $rsdet->fields['idprod']?></strong></td>
          <td align="center"><strong><?php echo $rsdet->fields['barcode']?></strong></td>
          <td height="34"><strong><?php echo $rsdet->fields['pchar']?></strong></td>
          <td align="center"><?php echo formatomoneda($rsdet->fields['cantidad'], 4, 'N')?></td>
          <td align="center"><?php echo formatomoneda($costo_factura, 4, 'N')?><?php if ($costo_factura != $costo_ultimo) { ?> <span style="color:#F00;" title="Ultimo Costo fue: <?php echo formatomoneda($costo_ultimo, 4, 'N'); ?>">(*)</span><?php } ?></td>
           <td align="center"><?php echo formatomoneda($rsdet->fields['subtotal'], 4, 'N')?></td>
           <td align="center"><?php echo antixss($rsdet->fields['tipo_iva']); ?></td>
          <td align="center"><a href="#pop1" onMouseUp="asignardt(<?php echo $rsdet->fields['idregcc']?>);"><img src="img/1476042796_office-05.png" width="24" height="24" /></a>&nbsp;&nbsp;<a href="#" onclick="eliminar(<?php echo $rsdet->fields['idregcc']?>)"><img src="img/no.PNG" width="16" height="16" title="Eliminar"/></a>
         
          </td>
        </tr>
        <?php
        $rsdet->MoveNext();
    }
    //update de la cabecera
    $update = "update tmpcompras set totalcompra=$t where idtran=$idtransaccion and idempresa = $idempresa";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    //echo $update;


    ?>
        <tr>
          <td height="34" bgcolor="#D8D8D8"><strong>Totales</strong></td>
          <td bgcolor="#D8D8D8">&nbsp;</td>
          <td  bgcolor="#D8D8D8"></td>
          <td align="center" bgcolor="#D8D8D8"><strong><?php echo formatomoneda($cant_acum, 4, 'N'); ?></strong></td>
          <td align="center" bgcolor="#D8D8D8">&nbsp;</td>
          <td align="center" bgcolor="#D8D8D8"><strong><?php echo formatomoneda($subt_acum, 4, 'N')?></strong></td>
          <td align="center" bgcolor="#D8D8D8">&nbsp;</td>
          <td align="center" bgcolor="#D8D8D8">&nbsp;</td>
        </tr>

        <form id="deletar" name="deletar" action="gest_reg_compras_resto_det.php?id=<?php echo $idtransaccion ?>" method="post"> 
         	<input type="hidden" name="regse" id="regse" value="" />
            <input type="hidden" name="ida" id="ida" value="1" />
        </form> 
 
</table><br />
<?php
$consulta = "
select * from tmpcompras where idtran=$idtransaccion
";
    $rscompracab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $vencimiento = $rscompracab->fields['vencimiento'];
    $totalcompra = $rscompracab->fields['totalcompra'];
    $monto_factura = $rscompracab->fields['monto_factura'];
    if ($monto_factura < 0) {
        $monto_factura = 0;
    }
    $diferencia = $monto_factura - $subt_acum;

    ?>
<table width="500" border="1" class="tablaconborde">
  <tr bgcolor="#F8FFCC">
    <td><strong>Total Factura</strong></td>
    <td><strong>Total Detalle</strong></td>
    <td><strong>Diferencia</strong></td>
	<?php if ($diferencia > 0) { ?><td><strong>Ajustar</strong></td><?php } ?>
  </tr>
  <tr>
    <td align="right"><?php echo formatomoneda($monto_factura, 4, 'N')?></td>
    <td align="right"><?php echo formatomoneda($subt_acum, 4, 'N')?></td>
    <td align="right"><?php echo $diferencia; ?></td>
	<?php if ($diferencia > 0) { ?><td align="center"><a href="compras_ajuste_auto.php?id=<?php echo intval($_GET['id']);  ?>">[Ajustar]</a></td><?php } ?>
  </tr>
</table>
<br />

<?php



    // si es a credito
     if ($rscompracab->fields['tipocompra'] == 2) {

         $consulta = "
	select * from tmpcompravenc where idtran=$idtransaccion
	";
         $rstieneven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
         $totalvenc = intval($rstieneven->RecordCount());

         if (intval($rstieneven->fields['idtran']) == 0) {
             $consulta = "
		INSERT INTO tmpcompravenc
		 ( idtran, vencimiento, monto_cuota) 
		 VALUES 
		 ($idtransaccion,'$vencimiento',$monto_factura);
		";
             $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

         }
         if ($totalvenc == 1 && intval($rstieneven->fields['idtran']) > 0) {

             $consulta = "
		update tmpcompravenc
		set
		vencimiento = '$vencimiento',
		monto_cuota = $monto_factura
		where
		idtran=$idtransaccion
		";
             $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
         }


         $consulta = "
	select * from tmpcompravenc where idtran=$idtransaccion order by vencimiento asc
	";
         $rsvenccomp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

         ?>
<span class="resaltaazul">Dias de Credito</span><br />
<table width="600" border="1" class="tablaconborde">
  <tr>
  	<td bgcolor="#F8FFCC"><strong>Cuota</strong></td>
    <td bgcolor="#F8FFCC"><strong>Vencimiento</strong></td>
    <td bgcolor="#F8FFCC"><strong>Monto</strong></td>
  </tr>
  <?php
  $cuo = 0;
         $totalcuo = 0;
         while (!$rsvenccomp->EOF) {
             $cuo++;
             $totalcuo += $rsvenccomp->fields['monto_cuota'];
             ?>
  <tr>
    <td align="center"><?php echo $cuo; ?></td>
    <td align="center"><?php echo date("d/m/Y", strtotime($rsvenccomp->fields['vencimiento'])); ?></td>
    <td align="right"><?php echo formatomoneda($rsvenccomp->fields['monto_cuota'], 4, 'N'); ?></td>
  </tr>
<?php $rsvenccomp->MoveNext();
         }?>
  <tr bgcolor="#CCCCCC" >
    <td align="center"> </td>
    <td align="center"> </td>
    <td align="right"><strong><?php echo formatomoneda($totalcuo, 4, 'N'); ?></strong></td>
  </tr>
</table><br />
<p><a href="gest_reg_compras_venc.php?idtran=<?php echo $idtransaccion; ?>">[Personalizar]</a></p>
<br />
  <br />
  
  <?php } ?>
  <span class="resaltaazul">Liquidaciones</span><br />

<div class="resumenmini">
  <br />
    <table width="90%" border="1">
      <tbody>
        <tr>
          <td align="center" bgcolor="#F8FFCC"><strong>Total Compra</strong></td>
          <td align="center" bgcolor="#F8FFCC"><strong>Sumatoria Cantidades</strong></td>
        </tr>
        <tr>
          <td align="center"><span class="resaltarojomini" style="font-size:20px;"><span class="resaltarojomini" style="font-size:20px;">Gs. </strong></span><?php echo formatomoneda($t, 4, 'N') ?></span></td>
          <td align="center"><span class="resaltarojomini" style="font-size:20px;"><?php echo formatomoneda($cant_acum, 4, 'N') ?></span></td>
        </tr>
      </tbody>
    </table>

  <br /><hr /><br />
<?php
$consulta = "
select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, sum(ivaml) as subtotal_monto_iva
from tmpcompradetaimp 
where 
idtran = $idtransaccion
group by iva_porc_col
order by iva_porc_col desc
";
    //echo $consulta;
    $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    ?>
  
  <strong>Sub Totales por IVA</strong><br />
<table width="90%" border="1" style="border-collapse:collapse;">
  <tr style="background-color:#CCC;  font-weight:bold;">
    <td><strong>IVA %</strong></td>
    <td><strong>Monto</strong></td>

  </tr>
<?php while (!$rsivaporc->EOF) {?>
  <tr>
    <td><?php
      if ($rsivaporc->fields['iva_porc'] > 0) {
          echo agregaespacio(floatval($rsivaporc->fields['iva_porc']).'%', 3);
      } else {
          echo "Exento";
      }
    ?></td>
    <td><?php echo formatomoneda($rsivaporc->fields['subtotal_poriva'], 0, 'N'); ?></td>

  </tr>
<?php $rsivaporc->MoveNext();
}  ?>
</table>
<?php
$rsivaporc->MoveFirst();


    ?>
    

  </p>
<br /><hr /><br />
  
    <strong>Liquidacion del IVA</strong><br />
<table width="90%" border="1" style="border-collapse:collapse;">
  <tr style="background-color:#CCC; font-weight:bold;">
    <td><strong>IVA %</strong></td>
    <td><strong>Monto</strong></td>

  </tr>
<?php while (!$rsivaporc->EOF) {?>
  <tr>
    <td><?php
        if ($rsivaporc->fields['iva_porc'] > 0) {
            echo agregaespacio(floatval($rsivaporc->fields['iva_porc']).'%', 3);
        } else {
            echo "Exento";
        }
    ?></td>
    <td><?php echo formatomoneda($rsivaporc->fields['subtotal_monto_iva'], 0, 'N'); ?></td>

  </tr>
<?php $rsivaporc->MoveNext();
}  ?>
</table>

  <br />  
  <hr />

  <br />
  <input type="button" id="rpc" onclick="cerrar()" value="Finalizar Compra" />
  <form id="registracompra" action="gest_reg_compras_resto_det.php?id=<?php echo $idtransaccion ?>" method="post">
	<input type="hidden" name="iva10" id="iva10" value="<?php echo $tiva10 ?>"/>
    <input type="hidden" name="iva5" id="iva5" value="<?php echo $tiva5 ?>"/>
	<input type="hidden" name="tran" id="tran" value="<?php echo $idtransaccion ?>"/>
    <input type="hidden" name="totcomp" id="totcomp" value="<?php echo $t; ?>">
</form>
 <form id="chaucompra"   action="gest_reg_compras_resto_det.php?id=<?php echo $idtransaccion ?>" method="post">
 	<input type="hidden" name="chau" id="chau" value="<?php echo $idtransaccion ?>"/>
 
 </form><br />
</div>


    
    <?php } ?>

    <br /><br />