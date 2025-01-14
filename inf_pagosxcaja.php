 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

$el = intval($_GET['el']);
if ($el > 0) {

    // consulta a la tabla
    $consulta = "
    select * 
    from pagos_extra 
    where 
    unis = $el
    and estado = 1
    limit 1
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $el = intval($rs->fields['unis']);
    $idcaja = intval($rs->fields['idcaja']);
    $idpago = intval($rs->fields['idpago']);

    if ($idpago > 0) {

        $consulta = "
        update gest_pagos set estado = 6 where idpago = $idpago
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $update = "Update pagos_extra set estado=6,anulado_el='$ahora',anulado_por=$idusu where unis=$el";
        $conexion->Execute($update) or die(errorpg($conexion, $update));

    }







    header("Location: inf_pagosxcaja.php");
    exit;
}

//busca si hay una caja abierta por este usuario
$buscar = "
Select * 
from caja_super 
where 
estado_caja=1 
and cajero=$idusu 
order by fecha desc 
limit 1
";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
// si encuentra
if (intval($rscaj->fields['idcaja']) > 0) {
    // valida que sea la misma sucursal del cajero
    if (intval($rscaj->fields['sucursal']) != $idsucursal) {
        echo "Tu usuario tiene una caja abierta en otra sucursal, cierra primero esa caja antes de abrir otra.";
        exit;
    }

}

$idcaja = $rscaj->fields['idcaja'];

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
    <!-- SECCION DONDE COMIENZA TODO -->
    <div align="center">
        <div class="resumenmini">
          <table width="400">
                <tr>
                    <td height="21" colspan="4" align="center" bgcolor="#F0F0F0"><strong>Resumen de pagos x caja</strong></td>
              </tr>
                <tr>
                    <td width="51" height="24" bgcolor="#F0F0F0">Cajero:</td>
                    <td width="131"><?php echo $cajero?></td>
                    <td width="58" bgcolor="#F0F0F0">Id Caja:</td>
                    <td width="140" align="center"><?php echo $idcaja?></td>
                </tr>
                
          </table>
            
            
      </div>
        <br />
            <div align="center">
                <?php
                    $buscar = "Select estado,unis,fecha,concepto,monto_abonado,(select nombre from proveedores 
                    where idempresa=$idempresa and idproveedor=pagos_extra.idprov)as provee,factura,anulado_el
                    ,(select usuario from usuarios where idusu=pagos_extra.anulado_por) as quien
                    from pagos_extra where idusu=$idusu and idcaja=$idcaja  order by fecha asc";
$rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$td = $rst->RecordCount();
if ($td == 0) {?>
                        <span class="resaltarojominni">No se registraron pagos x caja</span>
                    <?php } else {?>
                        <table width="980" border="1">
                            <tr>
                                <td width="62" height="35" align="center" bgcolor="#B0B0B0"><strong>Fecha/hora</strong></td>
                                <td width="87" align="center" bgcolor="#B0B0B0"><strong>Concepto</strong></td>
                                <td width="77" align="center" bgcolor="#B0B0B0"><strong>Proveedor</strong></td>
                                <td width="45" align="center" bgcolor="#B0B0B0"><strong>Monto abonado</strong></td>
                                <td width="100" align="center" bgcolor="#B0B0B0"><strong>Factura Num</strong></td>
                                <td width="100" align="center" bgcolor="#B0B0B0"><strong>[Imprimir]</strong></td>
                                <td width="225" align="center" bgcolor="#B0B0B0"><strong>Acci&oacute;n</strong></td>
                                
                            </tr>
                            <?php while (!$rst->EOF) {?>
                            <tr>
                                <td><?php echo date("d/m/Y H:i:s", strtotime($rst->fields['fecha'])); ?></td>
                                <td><?php echo $rst->fields['concepto']?></td>
                                <td align="center"><?php echo $rst->fields['provee']?></td>
                                <td align="right"><?php echo formatomoneda($rst->fields['monto_abonado'])?></td>
                                <td align="center"><?php echo $rst->fields['factura']?></td>
                                <td align="center"><a href="inf_pagosxcaja_imp.php?id=<?php echo $rst->fields['unis']?>&redir=1">[Imprimir]</a></td>
                                <td align="center"><?php if ($rst->fields['estado'] == 1) {?><a href="inf_pagosxcaja.php?el=<?php echo $rst->fields['unis']?>">[anular]</a><?php }?>
                                <?php if ($rst->fields['estado'] == 6) {?>
                                    Anulado: <?php echo $rst->fields['anulado_el']?> | <br />Usuario: <?php echo $rst->fields['quien']?>
                                    <?php }?>
                                </td>
                            </tr>
                            
                            <?php $rst->MoveNext();
                            }?>
                        </table>
                    <?php }?>
                
            </div>
    </div>
        
        
      </div> <!-- contenedor -->
   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>
