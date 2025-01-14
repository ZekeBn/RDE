<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "21";
$submodulo = "133";
require_once("includes/rsusuario.php");

$idadherente = intval($_GET['id']);
if ($idadherente == 0) {
    $idadherente = intval($_POST['ida']);
    if ($idadherente == 0) {
        echo "No especifico el adherente.";
        exit;
    }
}
$buscar = "
select * 
from cliente 
where 
idcliente in (
			select idcliente from adherentes 
			where 
			idadherente = $idadherente 
			and idempresa = $idempresa 
			and adherentes.idcliente = cliente.idcliente
			)
and idempresa = $idempresa
and permite_acredito = 'S'
";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$borrable = $rscli->fields['borrable'];
$razon_social = $rscli->fields['razon_social'];
$idcliente = $rscli->fields['idcliente'];
if (intval($rscli->fields['idcliente']) == 0) {
    echo "Cliente inexistente!";
    exit;
}
if ($borrable != 'S') {
    echo "El cliente $razon_social no puede tener linea de credito.";
    exit;
}

$consulta = "
select *, (select us_cod from clientes_codigos where idadherente = adherentes.idadherente and idempresa = $idempresa) as us_cod,
(select pass_cod from clientes_codigos where idadherente = adherentes.idadherente and idempresa = $idempresa) as pass_cod
from adherentes 
where 
idcliente = $idcliente
 and idadherente = $idadherente 
 and idempresa = $idempresa
 ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {


    // validaciones basicas
    $valido = "S";
    $errores = "";



    // si todo es correcto actualiza
    if ($valido == "S") {



        $consulta = "
		update adherentes
		set
			estado = 6
		where
			idempresa=$idempresa
			and idcliente=$idcliente
			and idadherente=$idadherente
			and estado <> 6
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        header("location: adherentes_credito.php?id=".$idcliente);
        exit;

    }

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

           <div align="center">
    		<table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="adherentes_credito.php?id=<?php echo $idcliente; ?>"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
 				<div class="divstd">
					<span class="resaltaditomenor">Borrar Adherente?</span>
				</div>

<p align="center">&nbsp;</p>
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<form id="form1" name="form1" method="post" action="adherentes_credito_borra.php">
	<table width="400" border="1" class="tablaconborde" align="center">
  <tbody>

	<tr>
	  <td align="center"><strong>*Nombres</strong></td>
	  <td width="130" align="left" ><?php  if (isset($_POST['nombres'])) {
	      echo htmlentities($_POST['nombres']);
	  } else {
	      echo htmlentities($rs->fields['nombres']);
	  }?></td>
	  </tr>

	<tr>
		<td align="center"><strong>*Apellidos</strong></td>
		<td width="130" align="left"><?php  if (isset($_POST['apellidos'])) {
		    echo htmlentities($_POST['apellidos']);
		} else {
		    echo htmlentities($rs->fields['apellidos']);
		}?></td>
	</tr>
	

  </tbody>
</table>

<br />
<p align="center">
  <input type="submit" name="button2" id="button2" value="Borrar" />
  <input type="button" name="button" id="button" value="Cancelar" onmouseup="document.location.href='adherentes_credito.php?id=<?php echo $idcliente; ?>'" />
  <input type="hidden" name="MM_update" value="form1" />
	 <input type="hidden" name="ida" value="<?php echo $idadherente?>" />
	
</p>
<br />
</form>
<p align="center">&nbsp;</p>
		  </div> 
			<!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>