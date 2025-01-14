 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "126";
require_once("includes/rsusuario.php");





$v_rz = trim($_POST['bus_rz']);
$v_ruc = trim($_POST['bus_ruc']);
$add = '';
//echo $v_rz;
$order = "order by razon_social asc limit 20";
if ($v_rz != '') {
    $ra = antisqlinyeccion($_POST['bus_rz'], 'text');
    $ra = str_replace("'", "", $ra);
    $len = strlen($ra);
    $add = " and razon_social like '%$ra%'";
    $order = "
    order by 
    CASE WHEN
        substring(razon_social from 1 for $len) = '$ra'
    THEN
        0
    ELSE
        1
    END asc, 
    razon_social asc
    Limit 20
    ";
}
if ($v_ruc != '') {
    $ru = antisqlinyeccion($_POST['bus_ruc'], 'text');
    $ru = str_replace("'", "", $ru);
    $add = " and ruc like '%$ru%'";
    $order = "order by razon_social asc limit 20";
}
$buscar = "Select * from cliente where idempresa = $idempresa and permite_acredito <> 'S' $add $order";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//echo $buscar;



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
              <td width="62"><a href="venta_credito.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
                 <div class="divstd">
                    <span class="resaltaditomenor">Habilitar Credito</span>
                </div>

<br />
<p align="center">&nbsp;  </p>
<form id="form1" name="form1" method="post" action="">
<p align="center">
  <label for="textfield">Razon Social:</label>
  <input type="text" name="bus_rz" id="bus_rz" />
RUC: 
  <input type="text" name="bus_ruc" id="bus_ruc" />
  <input type="submit" name="submit" id="submit" value="Buscar" />
</p>
</form>

<p align="center">&nbsp;</p>
<table width="900" border="1">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC">Titular</td>
      <td align="center" bgcolor="#F8FFCC">Ruc</td>
      <td align="center" bgcolor="#F8FFCC">Razon Social</td>
     <!-- <td align="center" bgcolor="#F8FFCC">Linea Sobregiro</td>
      <td align="center" bgcolor="#F8FFCC">Maximo Mensual</td>
      <td align="center" bgcolor="#F8FFCC">Consumo Actual</td>
      <td align="center" bgcolor="#F8FFCC">Saldo Linea</td>
      <td align="center" bgcolor="#F8FFCC">Saldo Mensual</td>
      <td align="center" bgcolor="#F8FFCC">Cant Adherentes.</td>-->
      <td align="center" bgcolor="#F8FFCC">[Permitir]</td>
    </tr>
<?php while (!$rs->EOF) {?>
    <tr>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['ruc'];?></td>
      <td align="center"><?php echo $rs->fields['razon_social'];?></td>
      <!--<td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>-->
      <td align="center"><a href="cliente_edita_cred_linea.php?id=<?php echo $rs->fields['idcliente'];?>">[Permitir]</a></td>
    </tr>
<?php $rs->MoveNext();
}?>
  </tbody>
</table>
<p align="center">&nbsp;</p>


          </div> <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
