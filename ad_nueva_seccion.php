 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "21";
$submodulo = "133";
require_once("includes/rsusuario.php");

$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    echo "No especifico el cliente.";
    exit;
}



if (isset($_GET['nombres'])) {

    // recibe parametros
    $nombres = antisqlinyeccion($_GET['nombres'], "text");



    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (trim($_GET['nombres']) == '') {
        $valido = "N";
        $errores .= " - El  nombre/descripcion no puede estar vac&iacute;o.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
        insert into adherentes_tipos_opcionales
        (descripcion, estado, idempresa,inicial)
        values
        ($nombres, 1,  $idempresa,0)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        header("Location: ad_nueva_seccion.php?id=$idcliente");
    }

}
//Mostramos lista existente

$buscar = "Select * from adherentes_tipos_opcionales where idempresa=$idempresa and estado=1 order by descripcion asc";
$rsex = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tdata = $rsex->RecordCount();

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
              <td width="62"><a href="adherentes_credito_agrega.php?id=<?php echo $idcliente; ?>"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
                 <div class="divstd">
                    <span class="resaltaditomenor">Agregar secci&oacute;n p / adherente</span>
                </div>

<p align="center">&nbsp;</p>
<?php if (trim($errores) != "") { ?>
    <div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
                <div align="center"></div>
<form id="form1" name="form1" method="get" action="ad_nueva_seccion.php">
<table width="400" border="1" class="tablaconborde" align="center">
  <tbody>

    <tr>
      <td width="150" align="center">*Descripci&oacute;n</td>
      <td width="234" align="left" style="height: 40px;"><input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
          echo htmlentities($_POST['nombres']);
      } else {
          echo htmlentities($rs->fields['nombres']);
      }?>" placeholder="nombres" required="required"   style="height: 40px; width: 99%;"/></td>
      </tr>

  </tbody>
</table>
<br />
<p align="center">
  <input type="submit" name="button" id="button" value="Registrar" />
  <input type="hidden" name="id" value="<?php echo $idcliente?>" />
</p>
<br />
</form>
<hr />
                <div align="center">
                <?php if ($tdata > 0) {?>
                  <table class="tablalinda2">
                        <tr>
                            <td width="200" height="33" align="center" bgcolor="#E5E5E5">Descripci&oacute;n</td>
                            <td width="61" align="center" bgcolor="#E5E5E5"></td>
                        </tr>
                        <?php while (!$rsex->EOF) {?>
                        <tr>
                            <td><?php echo $rsex->fields['descripcion'] ?></td>
                            <td>[eliminar]</td>
                        </tr>
                        
                        <?php $rsex->MoveNext();
                        }?>
                    </table>
                
                <?php } else {?>
                    <span class="resaltarojomini">No se registrarortipos/secciones adicionales.</span>
            
                    
                    <?php }?>
                </div>
          </div> 
            <!-- contenedor -->
           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
