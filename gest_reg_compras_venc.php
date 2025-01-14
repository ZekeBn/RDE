<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");

$idtran = intval($_GET['idtran']);
if (intval($idtran) == 0) {
    header("location: gest_reg_compras_resto.php");
    exit;
}




$consulta = "
select * from tmpcompravenc where idtran=$idtran order by vencimiento asc
";
$rsvenccomp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtran = intval($rsvenccomp->fields['idtran']);
if (intval($idtran) == 0) {
    header("location: gest_reg_compras_resto.php");
    exit;
}
//cabecera de la compra
$buscar = "Select * from tmpcompras where idtran=$idtran  and idempresa = $idempresa ";
$rscabecera = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {

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


    // si todo es correcto inserta
    if ($valido == "S") {

        $consulta = "
		insert into tmpcompravenc
		(idtran, vencimiento, monto_cuota)
		values
		($idtran, $vencimiento, $monto_cuota)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_reg_compras_venc.php?idtran=".$idtran);
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
<link rel="stylesheet" type="text/css" href="../css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
<?php require_once("includes/head_gen.php"); ?>
</head>
<body bgcolor="#FFFFFF">
	<?php require("includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
			<div class="colcompleto" id="contenedor">
      <br /><br />

      <div class="divstd">
    		<span class="resaltaditomenor">
    			Vencimientos<br />
    		</span>
 		</div>
<br /><hr /><br />
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
<p align="center">
<a href="gest_reg_compras_resto_det.php?id=<?php echo $idtran ?>">[Volver]</a>
</p><br /><br />
<p align="center">Total Compra: <span style="color:#F00;"><?php echo formatomoneda($rscabecera->fields['totalcompra'], 4, 'N'); ?></span></p><br />
<table width="600" border="1" class="tablaconborde">
  <tr>
  	<td align="center" bgcolor="#F8FFCC"><strong>Cuota</strong></td>
    <td align="center" bgcolor="#F8FFCC"><strong>Vencimiento</strong></td>
    <td align="center" bgcolor="#F8FFCC"><strong>Monto</strong></td>
    <td bgcolor="#F8FFCC" align="center"><strong>Acciones</strong></td>
  </tr>
  <?php
  $cuo = 0;
$total = 0;
while (!$rsvenccomp->EOF) {
    $cuo++;
    $total += $rsvenccomp->fields['monto_cuota'];
    ?>
  <tr>
    <td align="center"><?php echo $cuo; ?></td>
    <td align="center"><?php echo date("d/m/Y", strtotime($rsvenccomp->fields['vencimiento'])); ?></td>
    <td align="right"><?php echo formatomoneda($rsvenccomp->fields['monto_cuota'], 4, 'N'); ?></td>
    <td align="center"><a href="gest_reg_compras_venc_edit.php?id=<?php echo $rsvenccomp->fields['idvencimiento'] ?>">[Editar]</a><br />
      <a href="gest_reg_compras_venc_del.php?id=<?php echo $rsvenccomp->fields['idvencimiento'] ?>">[Borrar]</a></td>
  </tr>
<?php $rsvenccomp->MoveNext();
}?>
  <tr>
    <td align="center" bgcolor="#CCCCCC"></td>
    <td align="center" bgcolor="#CCCCCC"></td>
    <td align="right" bgcolor="#CCCCCC"><strong><?php echo formatomoneda($total, 4, 'N'); ?></strong></td>
    <td align="center" bgcolor="#CCCCCC"></td>
  </tr>
</table>
<br /><br />

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
  <input type="submit" name="button" id="button" value="Registrar" />
  <input type="hidden" name="MM_insert" value="form1" />
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