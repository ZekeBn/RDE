 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
$pag = "index";
require_once("includes/rsusuario.php");






?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
            <?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><span style="color:#F00;" title="25 de Agosto Día del Idioma Guaraní">Ikat&uacute; o&ntilde;epyr&utilde; mba'e tuichaitere&iacute;va jahasavo &aacute;ra, ha &aacute;ra &ntilde;ande rekov&eacute;pe</span> <?php echo($rsco->fields['nombre_sys']); ?></h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<strong>25 de Agosto Día del Idioma Guaraní</strong><br />
<br />El 25 de agosto de cada a&ntilde;o se celebra en el pa&iacute;s el D&iacute;a del Idioma Guaran&iacute;, uno de los legados m&aacute;s importantes que fuera heredado por nuestros antepasados, y que se constituye como patrimonio cultural inmaterial de la naci&oacute;n paraguaya.
<br />
Paraguay es el &uacute;nico pa&iacute;s de Am&eacute;rica Latina en que la gran mayor&iacute;a de sus ciudadanos pueden comunicarse en una lengua nativa, de uso extendido en todo el territorio nacional.
<br /><br />
<strong>&ldquo;El guaran&iacute; es el cord&oacute;n umbilical con nuestro verdadero ser, es la lengua que nos acerca, nos une y nos hace &uacute;nicos como paraguayos- Guarani niko &ntilde;ande rekoves&atilde;, &ntilde;anemo&rsquo;ag̃ui, &ntilde;anembojoaju ha &ntilde;anemopeteĩva paraguaiguah&aacute;icha&rdquo;</strong>,  Secretar&iacute;a de Pol&iacute;ticas Ling&uuml;&iacute;sticas.
<br /><br />
As&iacute; tambi&eacute;n explica que recordamos el &Ntilde;e&rsquo;ẽ &Aacute;ra porque el 25 de agosto de 1967, fecha de promulgaci&oacute;n de la Constituci&oacute;n Nacional del Paraguay, se declaraba  como idioma nacional de la Rep&uacute;blica, junto con el espa&ntilde;ol.
<br /><br />
Por primera vez se le otorgaba un rango jur&iacute;dico a la lengua nativa guaran&iacute;, reconoci&eacute;ndola como Lengua Nacional dando libertad a los ciudadanos el poder hablar libremente sin discriminaci&oacute;n.
<br /><br />
Luego, la Carta Magna de 1992 consolid&oacute; al guaran&iacute; como idioma oficial, disponiendo la obligatoriedad de la ense&ntilde;anza en la lengua materna del educando.
<br /><br />
El guaran&iacute; o ava&ntilde;e&rsquo;ẽ es una lengua de la familia tup&iacute;-guaran&iacute;, pueblo originario que habit&oacute; esa regi&oacute;n del continente americano, su uso pas&oacute; de lo cotidiano hasta incluso llegar a estar presente en las diferentes manifestaciones art&iacute;sticas y en su utilizaci&oacute;n de actos oficiales.
<br /><br />
<strong>¡¡¡ FELIZ DIA DEL IDIOMA GUARANI  !!!</strong>
<p align="center"><a href="<?php echo $rsco->fields['web_sys']; ?>" target="_blank"><img src="<?php echo $rsco->fields['logo_sys_indnew']; ?>" class="img-thumbnail" alt="<?php echo $rsco->fields['nombre_sys'] ?>"/></a></p>
<p align="right"><img src="img/paraguayocomovos.jpg" alt="" height="100"></p>
                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
