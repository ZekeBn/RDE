<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "81";
$dirsup = "S";
require_once("../includes/rsusuario.php");

header("location: categorias.php");
exit;


$buscar = "
	SELECT * 
	FROM categorias
	where
	estado = 1
	and idempresa = $idempresa
	order by orden asc, nombre asc
	"	;
$prod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

/*
para exportar a excel
SELECT categorias.id_categoria,  sub_categorias.idsubcate, categorias.nombre as categoria, sub_categorias.descripcion as subcategoria FROM `sub_categorias`
inner join categorias on categorias.id_categoria = sub_categorias.idcategoria
WHERE
categorias.estado = 1
and sub_categorias.estado = 1

*/

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("../includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("../includes/head.php"); ?>
<script>
function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
function borrar(desc,id){
	if(window.confirm('Esta seguro que desea borrar: '+desc+' ?')){
		//alert('Acceso Denegado! '+id);	
		document.location.href='gest_categoria_productos_borra.php?id='+id; 
	}
}
</script>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
<script type='text/javascript' src='plugins/ckeditor.js'></script>
</head>
<body bgcolor="#FFFFFF">
	<?php require("../includes/cabeza.php"); ?>    
	<div class="clear"></div>
		<div class="cuerpo">
            <div align="center">
                <?php require_once("../includes/menuarriba.php");?>
            </div>
			<div class="clear"></div><!-- clear1 -->
			<div class="colcompleto" id="contenedor">
             <div align="center">
    		<table width="70" border="0">
          <tbody>
            <tr>
              <td width="62"><a href="res_agregar_categoria.php"><img src="img/pagrega.png" width="64" height="64" title="Agregar Categoria" style="cursor:pointer" /></a></td>
            </tr>
          </tbody>
        </table>
    </div>
 				<div class="divstd">
					<span class="resaltaditomenor">Categorias</span>
				</div>

            <div align="center">
                <p>&nbsp; </p>
    <p><a href="categorias_xls.php">[descargar]</a></p>
    <p>&nbsp; </p>
    <table width="700" border="0" class="tablaconborde">
      <tr>
	  <td  align="center" bgcolor="#CCCCCC"><strong>Id</strong></td>
        <td  align="center" bgcolor="#CCCCCC"><strong>Icono</strong></td>
        <td  align="center" bgcolor="#CCCCCC"><strong>Categoria</strong></td>
        <td  align="center" bgcolor="#CCCCCC"><strong>Orden</strong></td>
        <td  align="center" bgcolor="#CCCCCC"><strong>Acciones</strong></td>
        </tr>
      <?php while (!$prod->EOF) {

          $img = "tablet/gfx/iconos/cat_".$prod->fields['id_categoria'].".png";
          if (!file_exists($img)) {
              $img = "tablet/gfx/iconos/cat_0.png";
          }




          ?>
      <tr>
	  <td align="center"><strong><?php echo trim($prod->fields['id_categoria']) ?></strong></td>
        <td height="27" align="center"><img src="<?php echo $img ?>"  border="0"  /></td>
        <td align="center"><?php echo trim($prod->fields['nombre']) ?></td>
        <td align="center"><?php echo trim($prod->fields['orden']) ?></td>
        <td align="center">
          <input type="button" name="button" id="button" value="Editar" onmouseup="document.location.href='categoria_editar.php?id=<?php echo $prod->fields['id_categoria']; ?>'" />
          
          <input type="button" name="button2" id="button2" value="Cambiar Icono" onmouseup="document.location.href='categoria_icono.php?id=<?php echo $prod->fields['id_categoria']; ?>'" /><br /><input type="button" name="button2" id="button2" value="Sub-Categorias" onmouseup="document.location.href='gest_subcategoria.php?cat=<?php echo $prod->fields['id_categoria']; ?>'" /><br />

          <input type="button" name="button3" id="button3" value="Eliminar" onmouseup="borrar('<?php echo trim($prod->fields['nombre']) ?>','<?php echo $prod->fields['id_categoria']; ?>');" />
        </td>
        </tr>
      <?php $prod->MoveNext();
      } ?>
</table>
    <br /><br />
    * si supera la cantidad de 5 categorias se cambiara la visualizacion de la tablet.<br /><br /><br /><br /><br /><br /><br />
    </div>

		  </div> <!-- contenedor -->
  		
 

   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
   
	<?php require("../includes/pie.php"); ?>
</body>
</html>