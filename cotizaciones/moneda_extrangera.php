<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo="16";
$submodulo="76";
$dirsup = 'S';
require_once("../includes/rsusuario.php"); 


$consulta="
select * 
from tipo_moneda
where
estado = 1
and idempresa = $idempresa
";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/





?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("../includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("../includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF">
	<?php require("../includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
      <br /><br />
   <div align="center">
    		<table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="index.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
      <div class="divstd">
    		<span class="resaltaditomenor">
    			Monedas Extrangeras<br />
    		</span>
 		</div>
<br /><hr /><br />

<p align="center">&nbsp;</p>
<p align="center"><a href="cotizaciones_add.php">[Agregar]</a></p>
<p>&nbsp;</p>
<table width="500" border="1">
  <tbody>
    <tr align="center" bgcolor="#F8FFCC">
      <td><strong>Moneda Extrangera</strong></td>
      <td><strong>[Borrar]</strong></td>
      </tr>
  <?php while(!$rs->EOF){  ?>
    <tr>
      <td align="center"><?php echo $rs->fields['descripcion']; ?></td>
      <td align="center"><strong><a href="cotizaciones_del.php?id=<?php echo $rs->fields['idcot']; ?>">[Borrar]</a></strong></td>
      </tr>
<?php $rs->MoveNext(); } ?>
  </tbody>
</table><br />




		  </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("../includes/pie.php"); ?>
</body>
</html>