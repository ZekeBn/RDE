<?php
require_once("../includes/conexion.php");
//require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("./preferencias_categorias.php");
$id = intval($_GET['id']);

$buscar = "
	SELECT * 
	FROM categorias
	where
	estado = 1
	and idempresa = $idempresa
	and borrable = 'S'
	and id_categoria = $id
	";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$id = intval($rs->fields['id_categoria']);

if ($id == 0) {
    echo "Categoria inexistente!";
    exit;
}



if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {
    if (trim($_POST['categoria']) != '') {

        // recibe parametros
        $categoria = antisqlinyeccion($_POST['categoria'], "text");
        $muestra_self = antisqlinyeccion($_POST['muestra_self'], "text");
        $muestra_ped = antisqlinyeccion($_POST['muestra_ped'], "text"); // web
        $muestra_menu = antisqlinyeccion($_POST['muestra_menu'], "text");
        $muestra_venta = antisqlinyeccion($_POST['muestra_venta'], "text");
        $margen_seguridad = antisqlinyeccion($_POST['margen_seguridad'], "float");
        $orden = intval($_POST['orden']);

        $consulta = "
		update categorias 
		set
		nombre = $categoria,
		orden = $orden,
		muestra_self = $muestra_self,
		muestra_ped = $muestra_ped,
		muestra_menu = $muestra_menu,
		muestra_venta = $muestra_venta,
		margen_seguridad = $margen_seguridad
		where
		id_categoria = $id
		and idempresa= $idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_categoria_productos.php");
        exit;

    }
}


?>

<!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
	
	<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
	<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />


  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Editar Categoria</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
			<div class="clear"></div><!-- clear1 -->
			<div class="colcompleto" id="contenedor">

<p><a href="gest_categoria_productos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a></p>
          
 				
<div style="border:1px solid #000; text-align:center; width:500px; margin:0px auto; padding:5px;">
<strong>Editando:</strong><br /><br />
<h1 align="center" style="font-weight:bold; margin:0px; padding:0px; color:#0A9600;"><?php echo $rs->fields['nombre']; ?></h1><br />

<input type="button" name="button" id="button" value="Cambiar" onmouseup="document.location.href='gest_categoria_productos.php'" />
</div>


<br />

<br />
<?php if (trim($msgimg) != '') { ?>
<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;">
<strong>Errores:</strong> <br />
<?php echo $msgimg; ?>
</div><br />
<?php } ?>

  	  	<div class="sombreado4">
	<form id="productos" action="categoria_editar.php?id=<?php echo $id ?>" method="post"><br />
	  <table width="500" border="0">
	    <tbody>
	      <tr>
	        <td align="right">
	          <strong>Categoria:</strong>&nbsp;&nbsp;</td>
	        <td align="left"><input type="text" name="categoria" id="categoria" required="required" value="<?php echo $rs->fields['nombre']; ?>" /></td>
	        </tr>
	      <tr>
	        <td align="right">
	          <strong>Orden en el Menu:</strong>&nbsp;&nbsp;</td>
	        <td align="left"><input type="text" name="orden" id="orden" required="required" value="<?php echo $rs->fields['orden']; ?>" /></td>
	        </tr>
			<tr>
			<?php if ($margen_seguridad == "S") { ?> 
	        <td align="right">
	          <strong>Margen de Seguridad:</strong>&nbsp;&nbsp;</td>
	        <td align="left"><input type="text" name="margen_seguridad" id="margen_seguridad" value="<?php echo $rs->fields['margen_seguridad']; ?>" /></td>
	        </tr>
			<?php } ?>
	      <tr>
	        <td  align="right">&nbsp;</td>
	        <td align="left">&nbsp;</td>
	        </tr>
	      <tr>
	        <td  align="right"><strong>Self Service:</strong>&nbsp;&nbsp;</td>
	        <td align="left"><input type="radio" name="muestra_self" id="radio3" value="S" <?php if ($rs->fields['muestra_self'] == 'S') { ?>checked="checked"<?php } ?>  />
	          SI&nbsp;&nbsp;&nbsp;
	          <input type="radio" name="muestra_self" id="radio4" value="N" <?php if ($rs->fields['muestra_self'] == 'N') { ?>checked="checked"<?php } ?> />
	          NO</td>
	        </tr>
	      <tr>
	        <td  align="right">Muestra en Web:</td>
	        <td align="left"><input type="radio" name="muestra_ped" id="radio" value="S" <?php if ($rs->fields['muestra_ped'] == 'S') { ?>checked="checked"<?php } ?>  />
SI&nbsp;&nbsp;&nbsp;
<input type="radio" name="muestra_ped" id="radio2" value="N" <?php if ($rs->fields['muestra_ped'] == 'N') { ?>checked="checked"<?php } ?> />
NO</td>
	        </tr>
	      <tr>
	        <td  align="right">Muestra en Menu:</td>
	        <td align="left"><input type="radio" name="muestra_menu" id="radio" value="S" <?php if ($rs->fields['muestra_menu'] == 'S') { ?>checked="checked"<?php } ?>  />
SI&nbsp;&nbsp;&nbsp;
<input type="radio" name="muestra_menu" id="radio2" value="N" <?php if ($rs->fields['muestra_menu'] == 'N') { ?>checked="checked"<?php } ?> />
NO</td>
	        </tr>
	      <tr>
	        <td  align="right">Muestra en Ventas:</td>
	        <td align="left"><input type="radio" name="muestra_venta" id="radio" value="S" <?php if ($rs->fields['muestra_venta'] == 'S') { ?>checked="checked"<?php } ?>  />
SI&nbsp;&nbsp;&nbsp;
<input type="radio" name="muestra_venta" id="radio2" value="N" <?php if ($rs->fields['muestra_venta'] == 'N') { ?>checked="checked"<?php } ?> />
NO</td>
	        </tr>
	      <tr>
	        <td colspan="2" align="center">&nbsp;</td>
	        </tr>
	      <tr>
	        <td colspan="2" align="center"><input type="submit" name="submit" id="submit" value="Guardar Cambios" /></td>
	        </tr>
	      </tbody>
	    </table><input type="hidden" name="MM_insert" value="form1" />
	  <br />
	</form>
    
</div>

<p>&nbsp;</p>
<br />



   <br  />
  <div align="center"></div>
    <br />
    <p align="center">&nbsp;</p>
    <div align="center">
      
      
    </div>
  
  
  
  
  
  
  
    
    
  </div> <!-- contenedor -->
  
  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
            
            
          </div>
        </div>
        <!-- /page content -->
		  
        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
           		<h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
            	Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
            	<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>
