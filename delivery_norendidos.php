 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");

$buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and sucursal = $idsucursal order by fecha desc limit 1";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);
if ($idcaja == 0) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}
if ($estadocaja == 3) {
    echo "<meta http-equiv='refresh' content='0; url=gest_administrar_caja.php'/>"     ;
    exit;
}


$consulta = "
    Select *, total_cobrado as totalpend  
    from gest_pagos 
        where 
        cajero=$idusu  
        and estado=1 
        and idcaja=$idcaja 
        and rendido ='N'
        and idempresa = $idempresa
        order by fecha desc
    ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;




?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
    <?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
        <div class="cuerpo">
            <div class="colcompleto" id="contenedor">
            
            <div align="center">
            <table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="gest_administrar_caja.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>

                 <div class="divstd">
                    <span class="resaltaditomenor">Deliverys no Rendidos en mi caja</span></div>
    <hr />            <p>&nbsp;</p>
    <table width="900" border="1">
      <tbody>
        <tr align="center" bgcolor="#F8FFCC">
          <td>Fecha/Hora</td>
          <td>Cod Venta</td>
          <td>Monto</td>
          <td>[Rendido]</td>
        </tr>
<?php while (!$rs->EOF) {    ?>
        <tr>
          <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['fecha'])); ?></td>
          <td align="center"><?php echo $rs->fields['idventa']; ?></td>
          <td align="center"><?php echo formatomoneda($rs->fields['totalpend']); ?></td>
          <td align="center"><a href="delivery_norendidos_rendir.php?id=<?php echo $rs->fields['idpago']; ?>">[Rendido]</a></td>
        </tr>
<?php $rs->MoveNext();
} ?>
      </tbody>
    </table>
    <p>&nbsp;</p>
           




          </div> <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
