 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "162";
require_once("includes/rsusuario.php");

$ahorad = date("Y-m-d");
$consulta = "
select *,
(select descripcion from gest_depositos where iddeposito = conteo.iddeposito)  as deposito,
(select estadoconteo from estado_conteo where idestadoconteo = conteo.estado ) as estadoconteo
from conteo
where
estado <> 6
and (estado = 1 or estado = 2)
and conteo.idempresa = $idempresa
and conteo.idsucursal = $idsucursal
and conteo.iniciado_por = $idusu
and conteo.fecha_inicio = '$ahorad'
order by idconteo desc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));




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


                 <div class="divstd">
                    <span class="resaltaditomenor">Conteo de Stock PDV</span></div>

<br />
<p align="center">(Inventario Ciego)</p>
<br />
<p align="center"><a href="conteo_stock_add_pdv.php">[Nuevo Conteo]</a></p>
<p align="center">&nbsp;</p>
<table width="900" border="1">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong># Conteo</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Deposito</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Iniciado</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Estado</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Accion</strong></td>
      </tr>
<?php
$i = 1;
while (!$rs->EOF) { ?>
    <tr>
      <td align="center"><?php echo $rs->fields['idconteo']; ?></td>
      <td align="center"><?php echo $rs->fields['deposito']; ?></td>
      <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rs->fields['inicio_registrado_el'])); ?></td>
      <td align="center"><?php echo $rs->fields['estadoconteo']; ?></td>
      <td align="center" style="height:30px;"><?php
$mostrarbtn = "N";
    if ($rs->fields['estado'] == 1) {
        $mostrarbtn = "S";
        $link = "conteo_stock_contar_pdv.php?id=";
        $txtbtn = "Abrir";
    }

    if ($mostrarbtn == 'S') {
        ?><input type="button" name="button" id="button" value="<?php echo $txtbtn?>" onmouseup="document.location.href='<?php echo $link.$rs->fields['idconteo']; ?>'" style="height:30px;" /><?php } ?></td>
      </tr>
<?php $i++;
    $rs->MoveNext();
} ?>
  </tbody>
</table>
<p align="center">&nbsp;</p>
          </div> 
            <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
