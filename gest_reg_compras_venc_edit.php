<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");

$idvencimiento = intval($_GET['id']);
if (intval($idvencimiento) == 0) {
    header("location: gest_reg_compras_resto.php");
    exit;
}




$consulta = "
select * from tmpcompravenc where idvencimiento=$idvencimiento order by vencimiento asc
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtran = intval($rs->fields['idtran']);
if (intval($idtran) == 0) {
    header("location: gest_reg_compras_resto.php");
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], "text");
    $monto_cuota = antisqlinyeccion($_POST['monto_cuota'], "float");


    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (trim($_POST['vencimiento']) == '') {
        $valido = "N";
        $errores .= " - El campo vencimiento no puede estar vacio.<br />";
    }
    if (floatval($_POST['monto_cuota']) <= 0) {
        $valido = "N";
        $errores .= " - El campo monto_cuota no puede ser cero o negativo.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		update tmpcompravenc
		set
			vencimiento=$vencimiento,
			monto_cuota=$monto_cuota
		where
			idvencimiento=$idvencimiento
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_reg_compras_venc.php?idtran=$idtran");
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
      <br /><br />

      <div class="divstd">
    		<span class="resaltaditomenor">
    			Editar Vencimiento<br />
    		</span>
 		</div>
<br /><hr /><br />
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<form id="form1" name="form1" method="post" action="">
<table width="400" border="1" class="tablaconborde" align="center">
  <tbody>

	<tr>
		<td align="center">*vencimiento</td>
		<td width="130" align="left"><input type="date" name="vencimiento" id="vencimiento" value="<?php  if (isset($_POST['vencimiento'])) {
		    echo htmlentities($_POST['vencimiento']);
		} else {
		    echo htmlentities($rs->fields['vencimiento']);
		}?>" placeholder="vencimiento" required="required" /></td>
	</tr>

	<tr>
		<td align="center">*monto_cuota</td>
		<td width="130" align="left"><input type="text" name="monto_cuota" id="monto_cuota" value="<?php  if (isset($_POST['monto_cuota'])) {
		    echo htmlentities($_POST['monto_cuota']);
		} else {
		    echo htmlentities($rs->fields['monto_cuota']);
		}?>" placeholder="monto_cuota" required="required" /></td>
	</tr>

  </tbody>
</table>
<br />
<p align="center">
  <input type="submit" name="button" id="button" value="Actualizar" />
  <input type="button" name="button" id="button" value="Cancelar" onmouseup="document.location.href='gest_reg_compras_venc.php?idtran=<?php echo $idtran; ?>'" />
  <input type="hidden" name="MM_update" value="form1" />
</p>
<br />
</form>
<p><br />
  <br /><br />
</p>

          </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>