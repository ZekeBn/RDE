 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

//'&metodo='+metodo;
if ($idt == 0) {
    $idt = intval($_POST['idt']);
}
$pedido = intval($_POST['pedido']);
$idmesa = intval($_POST['idmesa']);
if ($idmesa > 0) {
    $idt = 0;
    $pedido = 0;
}
//echo $idmesa;

// script de impresion factura
$consulta = "
select * from preferencias where idempresa = $idempresa limit 1
";
$rsscr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$controlfactura = intval($rsscr->fields['controlafactura']);
$factura_obliga = $rsscr->fields['factura_obliga'];
$autoimpresor = $rsscr->fields['autoimpresor'];
//echo $consulta;


if ($idmesa == 0) {
    if ($pedido > 0) {
        $buscar = "Select * from tmp_ventares_cab where  idtmpventares_cab=$pedido and idempresa = $idempresa and idsucursal = $idsucursal order by fechahora desc";
        $rsor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $delivery_zona = intval($rsor->fields['delivery_zona']);
        $delivery_costo = intval($rsor->fields['delivery_costo']);
        $totalgs = intval($rsor->fields['monto']);
        $total_condelivery = $totalgs + $delivery_costo;
        //$totalgs=$total_condelivery;
    } else {
        $buscar = "Select * from tmp_ventares_cab where finalizado='S' and registrado='N' and estado=1 and (idmesa = 0 or idmesa is null) and (idcanal = 1  or idcanal = 2) and idmesa_tmp is null and idempresa = $idempresa and idsucursal = $idsucursal  order by fechahora asc";
        $rsor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $totalgs = intval($rsor->fields['monto']);
        $pedido = intval($rsor->fields['idtmpventares_cab']);
        $idt = $pedido;
    }
}
//echo $totalgs;
if ($idmesa > 0) {
    $buscar = "
    Select sum(monto) as total, mesas.numero_mesa, salon.nombre, mesas.idmesa 
    from tmp_ventares_cab 
    inner join mesas on mesas.idmesa = tmp_ventares_cab.idmesa 
    INNER JOIN salon on mesas.idsalon = salon.idsalon 
    where 
    finalizado='S' 
    and registrado='N' 
    and estado=1 
    and tmp_ventares_cab.idsucursal = $idsucursal
    and tmp_ventares_cab.idempresa = $idempresa
    and mesas.idmesa= $idmesa
    GROUP by mesas.idmesa 
    order by mesas.numero_mesa asc
    ";
    $rsor = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $totalgs = intval($rsor->fields['total']);
}
//echo $idmesa;
$buscar = "
Select gest_zonas.idzona,descripcion,costoentrega
from gest_zonas
where 
gest_zonas.estado=1 
and gest_zonas.idempresa = $idempresa 
and gest_zonas.idsucursal = $idsucursal
order by descripcion asc
";
$rszonas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $delivery_zona;


$parte1 = intval($factura_suc);
$parte2 = intval($factura_pexp);
if ($parte1 == 0 or $parte2 == 0) {
    $parte1f = '001';
    $parte2f = '001';
} else {
    $parte1f = agregacero($parte1, 3);
    $parte2f = agregacero($parte2, 3);
}

//Traemos la numeracion de factura secuencia x sucursal y punto expedicion
if ($controlfactura == 1) {

    $ano = date("Y");
    // busca si existe algun registro
    $buscar = "
    Select idsuc, numfac as mayor 
    from lastcomprobantes 
    where 
    idsuc=$factura_suc 
    and pe=$factura_pexp 
    and idempresa=$idempresa 
    order by ano desc 
    limit 1";
    $rsfactura = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    //$maxnfac=intval(($rsfactura->fields['mayor'])+1);
    // si no existe inserta
    if (intval($rsfactura->fields['idsuc']) == 0) {
        $consulta = "
        INSERT INTO lastcomprobantes
        (idsuc, factura, numfac, recibo, numrec, tickete, numtk, ano, pe, numcheque, secuencia_cancela, 
        numhoja, hojalevante, idempresa) 
        VALUES
        ($factura_suc, 0, 0, NULL, 0, NULL, 0, $ano, $factura_pexp, NULL, 
        NULL, 0, '', $idempresa)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    $ultfac = intval($rsfactura->fields['mayor']);
    if ($ultfac == 0) {
        $maxnfac = 1;
    } else {
        $maxnfac = intval(($rsfactura->fields['mayor']) + 1);
    }
}
?>
<div align="center">
  <div class="resumenmini">
    <div align="center">
        <table width="90%">
            <tr>
              <td align="left"><span class="resaltaditomenor"><?php if ($idmesa == 0) {?>Pedido: <?php echo $pedido ?><!-- | Transacci&oacute;n: <?php echo $idt ?>  --><?php } else { ?>Mesa: <?php echo $rsor->fields['numero_mesa']; ?> Salon: <?php echo $rsor->fields['nombre']; ?> <?php } ?></span></td>
              <td width="20" align="center">&nbsp;</td>
                <td align="right">
                <input type="button" id="mifa" name="mifa" value="Mis Facturas" style="background-color:#F8FFCC" onMouseUp="document.location.href='gest_impresiones.php'" />
                <input type="button" id="mica" name="mica" value="Mi Caja" style="background-color:#FF6B6D" onMouseUp="document.location.href='gest_administrar_caja.php?lo=2'" />
                
                </td>

          </tr>
        
        </table>
    </div>
    <form id="regventamini" name="regventamini" action="registrar_venta.php" method="post" target="_self">
    <div >
    <table width="403" height="64">
        <tr>
            <td width="212" align="left"><strong>Delivery</strong>:
<select id="tipozona" name="tipozona" onChange="esplitear(this.value)">
    <option value="<?php echo '0 - 0';?>" >Seleccionar</option>
    
    <?php while (!$rszonas->EOF) {?>
        <option value="<?php echo $rszonas->fields['idzona'].' - '.$rszonas->fields['costoentrega']; ?>" <?php if ($delivery_zona == $rszonas->fields['idzona']) { ?> selected<?php } ?>><?php echo $rszonas->fields['descripcion']?> -><?php echo formatomoneda($rszonas->fields['costoentrega'])?></option>

    <?php $rszonas->MoveNext();
    } ?>
</select></td>
            <td width="179" align="left">
                <select id="condventa" name="condventa" style="height:25px;">
                    <option value="1" selected="selected" >CONTADO</option>
                 <option value="2">CREDITO</option>
                 </select>
              </td>
            <!--<td align="left"><select id="formapago" name="formapago" onChange="mediopago(this.value)" style="height:25px;">
              <option value="1" selected="selected" >EFECTIVO</option>
              <option value="2">TARJETA</option>
              <option value="3">TRANSFERENCIA</option>
              <option value="4">CHEQUE</option>  
               <option value="5">MIXTO</option>
            </select></td>
            <td align="left"><input type="button" name="button" id="button" value="Tarjeta" onClick="formadepago(2);"></td>
          </tr>-->
        <tr>
          <td align="left">&nbsp;<?php if ($autoimpresor != 'S') {?><input type="radio" name="tkofac" id="rad_tk" value="tk" <?php if ($factura_obliga != 'S') {?>checked="checked"<?php } ?> onMouseUp="tipo_fac('tk');">
            Ticket <?php } ?><input type="radio" name="tkofac" id="rad_fac" value="fac" onMouseUp="tipo_fac('fac');" <?php if ($factura_obliga == 'S') {?> checked="checked"<?php } ?>>
            Factura
              <label for="radio"></label></td>
          <td align="left" id="facturabox" style="display:<?php if ($factura_obliga != 'S') {?>none<?php } ?>;">
<input type="text" name="suc" id="suc" style="width:30px; height:20px;" value="<?php echo $parte1f ?>" />
            <input type="text" name="pe" id="pe" style="width:30px;height:20px;" value="<?php echo $parte2f; ?>" />
            <input type="text" name="nf" id="nf" style="width:60px;height:20px;" value="<?php  echo agregacero($maxnfac, 7); //echo agregacero(buscarfactura2($parte1f,$parte2f,$idempresa),7);?>" /></td>
          </tr>
    </table>
    <input type="hidden" name="fin" id="fin" value="<?php echo $idt?>" />
    <input type="hidden" name="totalventaf2" id="totalventaf2" value="<?php echo $totalgs?>" />
    <input type="hidden" name="montogsoc" id="montogsoc" value="0"  />
    <input type="hidden" name="pedidooc" id="pedidooc" value="<?php echo $pedido; ?>"  />
    <input type="hidden" name="mesaoc" id="mesaoc" value="<?php echo $idmesa; ?>"  />
    <input type="hidden" name="redir" value="S">
    </div>
      <div id="adicio" hidden="hidden">
          <input type="hidden" name="clientesel" id="clientesel" value="1" />
      </div>
      <table width="100%" border="0">
          <tbody>
              <tr>
                <td width="14%" bgcolor="#EDEDED"><strong>Total Venta 
                  <input type="hidden" name="totalventaf" id="totalventaf" value="<?php echo $totalgs?>" />
                </strong></td>
                <td width="18%" bgcolor="#EDEDED"><strong>Descuento</strong></td>
                <td width="26%" height="33" bgcolor="#EDEDED"><strong>Neto Cobrar</strong></td>
                <td width="27%" bgcolor="#EDEDED"><strong>Monto Recibido</strong></td>
                <td width="15%" bgcolor="#EDEDED"><strong>Vuelto</strong></td>
            </tr>
            <tr>
              <td align="right"><input type="text" name="ventatot" style="font-size:16px; color:#FD0004;font-weight:bold;height:40px; border:0px; width:100px; text-align:right;" id="ventatot" value="<?php echo ($totalgs)?>" /></td>
              <td align="right"><input type="text" name="descu" id="descu" value="0" style="height:30px;font-size:16px;width:90%;" readonly  /></td>
                <td align="right"><input type="text" name="netos" id="netos" readonly style="height:30px;font-size:16px;width:90%;"  value="<?php echo ($totalgs)?>"   /></td>
                 <td>
                     <input type="text" name="montogs" id="montogs" value="<?php echo ($totalgs)?>" style="height:30px;font-size:16px; width:90%;" onKeyUp="vuelto(this.value);"  />
              </td>
              <td> <input type="text" name="vueltogs" id="vueltogs" value="0" style="height:30px;font-size:16px;width:90%;" readonly  />
                
              </td>
            </tr>
              <tr>
                <td height="36" align="center">&nbsp;</td>
                <td height="36" align="center">&nbsp;</td>
                <td height="36" align="center" valign="top"><strong>Efectivo GS:</strong>
                   <input type="text" name="efec2" id="efec2" value="<?php echo ($totalgs)?>" style="height:40px; width:120px;" onKeyUp="dividir(1,this.value)" />     </td>
                <td height="36" align="center" valign="top"><strong>Tarjeta GS:</strong>                  
                        <input type="text" name="tarj" id="tarj" value="0"  style="height:40px; width:120px;" onKeyUp="dividir(2,this.value)" />
                </td>
                <td height="36" align="center">&nbsp;</td>
            </tr>
            <tr>
                <td colspan="5" align="center">
                <?php if ($pedido > 0 or $idmesa > 0) {?>
                
                    <input type="button" value="Aplicar Descuento" style="background-color:#A1FFF1" onMouseUp="asignarv(3);" />
                 &nbsp;&nbsp; 
               <!-- <input type="button" name="terminar" id="terminar" value="Finalizar Venta" style="height:30px;" onClick="finalizar();" >-->
               <input type="hidden" id="pedido_id" name="pedido_id" value="<?php echo $pedido; ?>">
                <input type="button" name="terminar" id="terminar" value="Finalizar Venta" style="height:30px;" onClick="registrar_venta();" >
                <?php }?>
                </td>
            
            </tr>
        </tbody>
    </table>
    <textarea id="ocmoti" style="display:none" name="ocmoti"></textarea>
    </form>
      <span style="font-size:16px;"><br /></span>
      
   </div><br />
<div id="clientereca" style="display:none;"></div>
</div>
