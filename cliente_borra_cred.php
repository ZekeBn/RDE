<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "126";
require_once("includes/rsusuario.php");

$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    header("location: venta_credito.php");
    exit;
}
$consulta = "
	select * 
	from cliente
	where 
	idempresa = $idempresa
	and idcliente = $idcliente
	and estado = 1
	limit 1
	";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($rs->fields['idcliente']);
$saldo_sobregiro = floatval($rs->fields['saldo_sobregiro']);
if ($idcliente == 0) {
    header("location: venta_credito.php");
    exit;
}




if ($_POST['borra'] == 'Borrar') {


    $consulta = "
	SELECT * 
	FROM cuentas_clientes 
	where 
	idcliente = $idcliente 
	and saldo_activo > 0 
	and estado <> 6
	limit 1
	";
    $rsdeu = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rsdeu->fields['idcliente'] > 0) {
        echo "ACCION NO PERMITIDA!<BR />No se puede borrar por que tiene deudas activas, cobre las deudas, realice una nota de credito o anule las facturas a credito para poder borrar el cliente.";
        exit;
    }

    $consulta = "
	update cliente 
	set 
	estado = 6,
	bloquear_sistema='S',
	cli_borrado_por=$idusu,
	cli_borrado_el='$ahora'
	where 
	idcliente = $idcliente
	and estado = 1
	and idempresa = $idempresa
	and borrable = 'S'
	";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    header("location: venta_credito.php");
    exit;
}



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
    		<span class="resaltaditomenor">Borrar Cliente a Credito<br />
    		</span>
 		</div>
<br /><hr /><br />


<table width="900" border="1" style="border-collapse:collapse;">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Idcliente</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Ruc</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Razon Social</strong></td>
      <?php if ($rsco->fields['usa_adherente'] == 'S') { ?><?php } ?>
      <!-- <td align="center" bgcolor="#F8FFCC">Linea Sobregiro</td>
      <td align="center" bgcolor="#F8FFCC">Maximo Mensual</td>
      <td align="center" bgcolor="#F8FFCC">Consumo Actual</td>
      <td align="center" bgcolor="#F8FFCC">Saldo Linea</td>
      <td align="center" bgcolor="#F8FFCC">Saldo Mensual</td>
      <td align="center" bgcolor="#F8FFCC">Cant Adherentes.</td>-->
      </tr>
<?php while (!$rs->EOF) {?>
    <tr>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['ruc'];?></td>
      <td align="center"><?php echo $rs->fields['razon_social'];?></td>
      <?php if ($rsco->fields['usa_adherente'] == 'S') { ?><?php } ?>
      <!--<td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>
      <td align="center"><?php echo $rs->fields['idcliente'];?></td>-->
      </tr>
<?php $rs->MoveNext();
}?>
  </tbody>
</table>
<p>&nbsp;</p>
&nbsp;
<form id="form1" name="form1" method="post" action="">
<p align="center">
  <input type="button" name="button" id="button" value="Cancelar" onmouseup="document.location.href='venta_credito.php'" />
  <input type="submit" name="borra" id="borra" value="Borrar" />
  </p>
</form>

<p>&nbsp;</p>

          </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>