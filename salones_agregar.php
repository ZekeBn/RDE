 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "74";
require_once("includes/rsusuario.php");




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    $nombresalon = antisqlinyeccion($_POST['nombre'], "text");
    $color = antisqlinyeccion($_POST['colorse'], "text");
    $idsucursal = antisqlinyeccion($_POST['idsucu'], "int");

    // valida que no exista el mismo nombre en la misma sucursal
    $consulta = "
    select * from salon
    where
    nombre = $nombresalon
    and idsucursal = $idsucursal
    ";
    $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsex->fields['idsalon']) > 0) {
        echo "Ya existe un salon con el mismo nombre, favor cambie el nombre.";
        exit;
    }
    if (intval($_POST['idsucu']) == 0) {
        echo "Debe seleccionar la sucursal.";
        exit;
    }

    $consulta = "
    INSERT INTO salon
    (nombre, idsucursal, color)
    VALUES 
    ($nombresalon,$idsucursal,$color);
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    header("location: salones.php");
    exit;

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
                    <span class="resaltaditomenor">Agregar Salon</span>
                </div>
<div style="border:1px solid #000; text-align:center; width:500px; margin:0px auto; padding:5px;">
<strong>Editando:</strong><span style="font-weight:bold; margin:0px; padding:0px; color:#0A9600;"> <?php echo $rs->fields['nombre']; ?></span><br />
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
<form id="form1" name="form1" method="post" action="salones_agregar.php">

<table width="400" border="1" class="tablaconborde">
  <tbody>
    <tr>
      <td align="center">Nombre del Salon: </td>
      <td width="130" align="left">
        <input type="text" name="nombre" id="nombre" value="<?php echo htmlentities($_POST['nombre']); ?>" /></td>
    </tr>
    <tr>
      <td align="center">Color:</td>
      <td align="left"><input type="color" name="colorse" id="colorse" value="<?php echo htmlentities($_POST['color']); ?>" /></td>
    </tr>
    

    <tr>
      <td align="center">Sucursal:</td>
      <td align="left">
      
<?php
// consulta
$consulta = "
SELECT idsucu, nombre
FROM sucursales
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idsucu'])) {
    $value_selected = htmlentities($_POST['idsucu']);
} else {
    $value_selected = htmlentities($rs->fields['idsucu']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idsucu',
    'id_campo' => 'idsucu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idsucu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
      </td>
    </tr>
    
  </tbody>
</table>
<p align="center">&nbsp;</p>
<p align="center">
  <input type="submit" name="button" id="button" value="Guardar" />
  <input type="hidden" name="MM_update" value="form1" />
</p>
<br /></form>


    
  </div> <!-- contenedor -->
  


   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>
