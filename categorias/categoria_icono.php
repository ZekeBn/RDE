<?php
require_once("../includes/conexion.php");
//require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");

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



//http://www.lawebdelprogramador.com/foros/PHP/1334558-Redimensionar-un-PNG-sin-perder-la-transparencia.html
function create_tb($img_o, $w_tb, $h_tb, $bg_color)
{
    $img_return = imagecreatetruecolor($w_tb, $h_tb);
    if (isset($bg_color) && $bg_color != "") {
        $color = imagecolorallocate($img_return, hexdec(substr($bg_color, 0, 2)), hexdec(substr($bg_color, 2, 2)), hexdec(substr($bg_color, 4, 2)));
    } else {
        $color = imagecolorallocate($img_return, 255, 255, 255);
    }
    imagefilledrectangle($img_return, 0, 0, $w_tb, $h_tb, $color);
    $wo = imagesx($img_o);
    $ho = imagesy($img_o);
    if ($wo >= $ho) {
        $wtb_copy = $w_tb;
        $htb_copy = ($ho * (($w_tb * 100) / $wo)) / 100;
        $xtb_copy = 0;
        $ytb_copy = ($h_tb / 2) - ($htb_copy / 2);
    } elseif ($ho > $wo) {
        $wtb_copy = ($wo * (($h_tb * 100) / $ho)) / 100;
        $htb_copy = $h_tb;
        $xtb_copy = ($w_tb / 2) - ($wtb_copy / 2);
        $ytb_copy = 0;
    }
    imagecopyresampled($img_return, $img_o, $xtb_copy, $ytb_copy, 0, 0, $wtb_copy, $htb_copy, $wo, $ho);
    return $img_return;
}
function create_tb_png($img_o, $w_tb, $h_tb, $bg_color)
{
    $img_return = imagecreatetruecolor($w_tb, $h_tb);
    if (isset($bg_color) && $bg_color != "" && $bg_color != "T") {
        $color = imagecolorallocate($img_return, hexdec(substr($bg_color, 0, 2)), hexdec(substr($bg_color, 2, 2)), hexdec(substr($bg_color, 4, 2)));
    } else {
        $color = imagecolorallocate($img_return, 255, 255, 255);
    }
    if ($bg_color == "T") {
        imagecolortransparent($img_return, $color);
    }
    imagefilledrectangle($img_return, 0, 0, $w_tb, $h_tb, $color);
    $wo = imagesx($img_o);
    $ho = imagesy($img_o);
    if ($wo >= $ho) {
        $wtb_copy = $w_tb;
        $htb_copy = ($ho * (($w_tb * 100) / $wo)) / 100;
        $xtb_copy = 0;
        $ytb_copy = ($h_tb / 2) - ($htb_copy / 2);
    } elseif ($ho > $wo) {
        $wtb_copy = ($wo * (($h_tb * 100) / $ho)) / 100;
        $htb_copy = $h_tb;
        $xtb_copy = ($w_tb / 2) - ($wtb_copy / 2);
        $ytb_copy = 0;
    }
    imagecopyresampled($img_return, $img_o, $xtb_copy, $ytb_copy, 0, 0, $wtb_copy, $htb_copy, $wo, $ho);
    return $img_return;
}
$msgimg = "";
$editFormAction = $_SERVER['PHP_SELF'];
if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
    $valido = 'S';
    $file = $_FILES["file"];
    if ($file["type"] == "image/pjpeg" || $file["type"] == "image/jpg" || $file["type"] == "image/jpeg" || $file["type"] == "image/png") {
        if ($file["size"] >= 5120000) {
            $valido = 'N';
            $msgimg .= "La imagen muy grande, tama�o maximo: 400x400px. y 500 kb.";
        }
    } else {
        $valido = 'N';
        $msgimg .= "Tipo incorrecto, la imagen debe ser .jpg o .png";
    }
    if ($valido == 'S') {
        if ($file["type"] == "image/png") {
            $imgGrande = imagecreatefrompng($file["tmp_name"]);
        } else {
            $imgGrande = imagecreatefromjpeg($file["tmp_name"]);
        }
        //$imggrande=create_tb($imgGrande,300,300,"FFFFFF");
        $imgChica = create_tb_png($imgGrande, 100, 100, 'T');
        $imgBig = create_tb_png($imgGrande, 600, 451, 'T');
        imagepng($imgBig, "../ecom/gfx/fotosweb/cat_".($id).".png");
        imagepng($imgChica, "../tablet/gfx/iconos/cat_".($id).".png");

        header("location: categoria_icono.php?id=".$id);
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
                    <h2>Agregar Icono a Categor&iacute;a</h2>
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
			
			
 				
<div class="resumenmini">
<h1 align="center" style="font-weight:bold; margin:0px; padding:0px; color:#0A9600;"><?php echo $rs->fields['nombre']; ?></h1><br />

<input type="button" name="button" id="button" value="Otra categor&iacute;a" onmouseup="document.location.href='gest_categoria_productos.php'" />
</div>


<br />

<br />
<?php if (trim($msgimg) != '') { ?>
<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;">
<strong>Errores:</strong> <br />
<?php echo $msgimg; ?>
</div><br />
<?php } ?>
<?php

$img = "../tablet/gfx/iconos/cat_".$rs->fields['id_categoria'].".png";
if (!file_exists($img)) {
    $img = "tablet/gfx/iconos/cat_0.png";
}

$img2 = "../ecom/gfx/fotosweb/cat_".$rs->fields['id_categoria'].".png";
if (!file_exists($img2)) {
    $img2 = "../ecom/gfx/fotosweb/cat_0.png";
}
?>
<form action="categoria_icono.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" name="form1" id="form1">
  <table align="center" style="margin:0px auto;" width="600">
    <tr valign="baseline">
      <td align="center" nowrap="nowrap"><img src="<?php echo $img ?>?rnd=<?php echo rand(5, 100); ?>" height="100" border="0" /></td>
		 <td align="center" nowrap="nowrap"><img src="<?php echo $img2 ?>?rnd=<?php echo rand(5, 100); ?>" height="400" border="0" /></td>
      </tr>
    <tr valign="baseline">
      <td align="center" nowrap="nowrap" colspan="2">  




          <input type="file" name="file" id="file" />



       



    </td>
    </tr>
    <tr valign="baseline">
      <td align="center" nowrap="nowrap" colspan="2">&nbsp;</td>
    </tr>
    <tr valign="baseline">
      <td align="center" nowrap="nowrap" colspan="2"> <input type="submit" name="button" id="button" value="Cambiar Imagen" />



        <input type="hidden" name="MM_insert" id="MM_insert" value="form1" /></td>
    </tr>
  </table>  </form>

<p>&nbsp;</p>
<br />
<p align="center"><strong>Tama&ntilde;o ideal de Imagen: </strong></p>
<p align="center">Ancho: 600 px.</p>
<p align="center">Alto: 300 px.</p>


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