<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");



$id = intval($_GET['id']);
if ($id == 0) {
    header("location: venta_credito.php");
    exit;
}

$buscar = "
select * 
from cliente 
where 
idcliente = $id 
and idempresa = $idempresa
and permite_acredito = 'S'
and estado <> 6
";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$borrable = $rscli->fields['borrable'];
$razon_social = $rscli->fields['razon_social'];
if (intval($rscli->fields['idcliente']) == 0) {
    echo "Cliente inexistente o no tiene permitido operar a credito.";
    exit;
}
if ($borrable != 'S') {
    echo "El cliente $razon_social no puede tener linea de credito.";
    exit;
}


$buscar = "
select * ,(select descripcion from adherentes_tipos_opcionales where idempresa=$idempresa and idtipoad=adherentes.idtipoad) as adic
from adherentes
where 
idcliente = $id 
and idempresa = $idempresa
and estado <> 6
order by nombres asc
";
$rsad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


// establece maximos mensuales automaticamente, parametrizar en preferencias
/*if($database == 'sistema_martaelena_sil' or $database == 'sistema_martaelena_bautista' or $database == 'benditas'){

    // maximos mensuales
    $consulta="
    update cliente set max_mensual = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set maximo_mensual = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set max_mensual = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // linea de credito
    $consulta="
    update cliente set linea_sobregiro = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set linea_sobregiro = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set linea_credito = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // disponible trucho
    $consulta="
    update cliente set saldo_sobregiro = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set disponible = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set disponibleserv = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
}*/

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
					<span class="resaltaditomenor">Adherentes</span>
				</div>

<p align="center">&nbsp;</p>
<p align="center" style="font-size:16px;">Titular:</p><br />
<table width="700" border="1" >
  <tr>
    <td height="39" align="right" bgcolor="#E4E4E4"><strong>*Razon Social</strong></td>
    <td><?php

            echo $rscli->fields['razon_social'];

?></td>
    <td bgcolor="#E4E4E4">Linea Credito</td>
    <td width="150" align="right"><?php

  echo formatomoneda($rscli->fields['linea_sobregiro']);

?></td>
  </tr>
  <tr>
    <td height="36" align="right" bgcolor="#E4E4E4"><strong>*Ruc</strong></td>
    <td><?php

    echo $rscli->fields['ruc'];


?></td>
    <td bgcolor="#E4E4E4">Max Mensual</td>
    <td align="right"><?php

  echo formatomoneda($rscli->fields['max_mensual']);

?></td>
  </tr>
</table><br /><br /><br />
<p align="center"  style="font-size:16px;">Adherentes:</p>
<p align="center"  style="font-size:16px;">&nbsp;</p>
<p align="center"><a href="adherentes_credito_agrega.php?id=<?php echo $id; ?>">[Agregar]</a></p>
<br />
<table width="700" border="1">
  <tbody>
    <tr>
     <td width="25" align="center" bgcolor="#F8FFCC">N&deg;</td>
      <td width="90" align="center" bgcolor="#F8FFCC">Nombre y Apellido</td>
      <td width="27" align="center" bgcolor="#F8FFCC">Linea</td>
      <td width="71" align="center" bgcolor="#F8FFCC">Max Mensual</td>
      <td width="55" align="center" bgcolor="#F8FFCC">[Servicios]</td>
      <td width="40" align="center" bgcolor="#F8FFCC">[Editar]</td>
      <td width="44" align="center" bgcolor="#F8FFCC">[Borrar]</td>
		<td width="109" align="center" bgcolor="#F8FFCC">Opcional / Adicional</td>
		<td width="81" align="center" bgcolor="#F8FFCC">Opcional Tipo</td>
      </tr>
<?php
$i = 0;
while (!$rsad->EOF) {
    $i = $i + 1;
    ?>
    <tr>
    	<td><?php echo $i; ?></td>
      <td align="center"><?php echo $rsad->fields['nombres'];?> <?php echo $rsad->fields['apellidos'];?></td>
      <td align="center"><?php echo formatomoneda($rsad->fields['linea_sobregiro']);?></td>
      <td align="center"><?php echo formatomoneda($rsad->fields['maximo_mensual']);?></td>
      <td align="center"><a href="adherentes_credito_serv.php?id=<?php echo $rsad->fields['idadherente'];?>">[Servicios]</a></td>
      <td align="center"><a href="adherentes_credito_edita.php?id=<?php echo $rsad->fields['idadherente'];?>">[Editar]</a></td>
      <td align="center"><a href="adherentes_credito_borra.php?id=<?php echo $rsad->fields['idadherente'];?>">[Borrar]</a></td>
	  <td align="center"><?php echo($rsad->fields['adicional1']);?></td>
		<td align="center"><?php echo($rsad->fields['adic']);?></td>
      </tr>
<?php $rsad->MoveNext();
}?>
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