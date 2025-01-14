 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "74";
require_once("includes/rsusuario.php");
//ALTER TABLE `mesas` DROP INDEX `idsalon`;
$idmesa = intval($_GET['id']);

$consulta = "
select * from mesas
inner join salon on mesas.idsalon = salon.idsalon
where
mesas.idmesa = $idmesa
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idmesa = intval($rs->fields['idmesa']);
$idsalon = intval($rs->fields['idsalon']);
$idsucursal = intval($rs->fields['idsucursal']);
if ($idmesa == 0) {
    echo "Mesa inexistente!";
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {
    $numero_mesa = intval($_POST['numero_mesa']);

    // validar que no exista en ese salon
    $consulta = "
    select * from mesas
    inner join salon on mesas.idsalon = salon.idsalon
    where
    salon.idsucursal = $idsucursal
    and mesas.numero_mesa = $numero_mesa
    and mesas.idsalon = $idsalon
    and mesas.idmesa <> $idmesa
    and mesas.estadoex <> 6
    ";
    //echo $consulta;
    $rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rs2->fields['numero_mesa']) > 0) {
        echo "Ya existe el numero de mesa seleccionado en este salon, favor asigne otro numero.";
        exit;
    }

    if ($numero_mesa > 0) {
        $consulta = "  
        update mesas
        set numero_mesa = $numero_mesa
        where
        idmesa=$idmesa    
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        header("location: salones_mesa.php?salon=".$idsalon);
    }
}



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
                    <span class="resaltaditomenor">Editar Mesa</span>
                </div>
              <br />
<?php if (trim($msgimg) != '') { ?>
<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;">
<strong>Errores:</strong> <br />
<?php echo $msgimg; ?>
</div><br />
<?php } ?>
<p align="center">&nbsp;</p>
<br />
<form id="form1" name="form1" method="post" action="">
<table width="500" border="1" class="tablaconborde">
  <tbody>
<?php
//$rs->MoveFirst();
while (!$rs->EOF) { ?>
    <tr>
      <td align="center"><strong>Mesa N&deg; </strong></td>
      <td align="left"><input type="text" name="numero_mesa" id="numero_mesa" value="<?php echo $rs->fields['numero_mesa']; ?>" /></td>
      </tr>
<?php $rs->MoveNext();
} ?>
  </tbody>
</table>
<p>&nbsp;</p>
<p align="center">
  <input type="submit" name="submit" id="submit" value="Guardar" />
  <input type="hidden" name="MM_update" value="form1" />
</p>

</form>
<br />


    
  </div> <!-- contenedor -->
  


   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>
