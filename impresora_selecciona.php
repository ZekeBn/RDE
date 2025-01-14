 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");


$consulta = "

    ";
//$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));





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
              <td width="62"><a href="index.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
        </div>
                 <div class="divstd">
                    <span class="resaltaditomenor">Seleccionar Impresora</span>
                </div>

<br /><br />
<div class="resumenmini">
  <p><br />
    <br /><br /><br />  <form id="form1" name="form1" method="get" action="impresor_ticket.php">
    Impresoras: <?php
               $buscar = "select * from impresoratk where idempresa = $idempresa and idsucursal = $idsucursal and borrado = 'N' order by descripcion asc";
//echo $buscar;
$rspant = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$total = $rspant->RecordCount();

?>
    <select name="imp" id="imp">
      <option value="0">Seleccionar...</option>
      <?php while (!$rspant->EOF) {?>
      <option value="<?php echo $rspant->fields['idimpresoratk']?>" <?php if (intval($_POST['idimpresoratk']) == intval($rspant->fields['idimpresoratk']) or ($total == 1)) { ?> selected="selected" <?php } ?>><?php echo trim($rspant->fields['descripcion']) ?></option>
      <?php $rspant->MoveNext();
      }?>
    </select>
  </p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>
    <input type="submit" name="Seleccionar" id="Seleccionar" value="Seleccionar" />
    <br />
    </p>

  </form>
  <p><br />
    <br /><br />
  </p>
</div>


          </div> <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
