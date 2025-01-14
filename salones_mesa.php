 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "74";
require_once("includes/rsusuario.php");

$idsalon = intval($_GET['salon']);

$consulta = "
select * 
from salon
where
salon.idsalon = $idsalon
and estado_salon = 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idsucursal = intval($rs->fields['idsucursal']);
$idsalon = intval($rs->fields['idsalon']);
$id = $idsalon;

if ($idsalon == 0) {
    echo "Salon inexistente!";
    exit;
}

$consulta = "
select * from mesas
inner join salon on mesas.idsalon = salon.idsalon
where
mesas.idsalon = $idsalon
and salon.idsucursal = $idsucursal
and estadoex = 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
<?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
        <div class="cuerpo">
            <div align="center">
                <?php require_once("includes/menuarriba.php");?>
            </div>
            <div class="clear"></div><!-- clear1 -->
            <div class="colcompleto" id="contenedor">
             <div align="center">
            <table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="salones.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
                 <div class="divstd">
                    <span class="resaltaditomenor">Mesas en el Salon</span>
                </div>
<div style="border:1px solid #000; text-align:center; width:500px; margin:0px auto; padding:5px;">
<strong>Editando:</strong><span style="font-weight:bold; margin:0px; padding:0px; color:#0A9600;"> <?php echo $rs->fields['nombre']; ?></span><br />
</div>



<br />
<?php if (trim($msgimg) != '') { ?>
<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;">
<strong>Errores:</strong> <br />
<?php echo $msgimg; ?>
</div><br />
<?php } ?>
<p align="center"><input type="button" name="button5" id="button5" value="Agregar Mesa" onmouseup="document.location.href='mesas_agregar.php?salon=<?php echo $idsalon; ?>'" />
  <input type="button" name="button6" id="button6" value="Ubicacion Mesas" onmouseup="document.location.href='mesas.php?salon=<?php echo $idsalon; ?>'" />
</p>
<br />
<table width="500" border="1" class="tablaconborde">
  <tbody>
    <tr>
      <td align="center" bgcolor="#CCC"><strong>Mesa N&deg; </strong></td>
      <td width="130" align="center" bgcolor="#CCC"><input type="button" name="button3" id="button3" value="Editar" />        <input type="button" name="button" id="button" value="Eliminar" /></td>
    </tr>
<?php
//$rs->MoveFirst();
while (!$rs->EOF) { ?>
    <tr>
      <td align="center">Mesa <?php echo $rs->fields['numero_mesa']; ?></td>
      <td align="center"><input type="button" name="button4" id="button4" value="Editar" onmouseup="document.location.href='mesas_editar.php?id=<?php echo $rs->fields['idmesa']; ?>'" />        
      <input type="button" name="button2" id="button2" value="Eliminar" onmouseup="document.location.href='mesas_eliminar.php?idmesa=<?php echo $rs->fields['idmesa']; ?>'" /></td>
    </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>
<br />


    
  </div> <!-- contenedor -->
  


   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>
