<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "2";
require_once("includes/rsusuario.php");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Documento sin título</title>
</head>

<body>
<form id="form1" name="form1" method="post" action="">
<table width="980" border="1">
  <tr>
    <td>Consulta SQL</td>
    <td><textarea cols="50" rows="6" required="required" name="consultasql">select id, nombre 
from tabla 
where 
estado = 1 
order by nombre asc</textarea></td>
  </tr>
  <tr>
    <td>HTML Name</td>
    <td><input type="text" name="htmlname" id="htmlname"  required="required" /></td>
  </tr>
  <tr>
    <td> HTML Id</td>
    <td><input type="text" name="htmlid" id="htmlid" required="required" /></td>
  </tr>
  <tr>
    <td>BD Name</td>
    <td><input type="text" name="bdname" id="bdname" required="required" /></td>
  </tr>
  <tr>
    <td>BD Id</td>
    <td><input type="text" name="bdid" id="bdid" required="required" /></td>
  </tr>
  <tr>
    <td>BD Id rs edit</td>
    <td><input type="text" name="bdid2" id="bdid2" required="required" /></td>
  </tr>
  <tr>
    <td>Metodo:</td>
    <td><input type="radio" name="metodo" id="metodop" value="P"  required="required" />
    <label for="radio">POST
      <input type="radio" name="metodo" id="metodog" value="G"  required="required" />
      GET</label></td>
  </tr>
  <tr>
    <td>$rs (nombre variable edicion)</td>
    <td><label for="textfield6"></label>
      <input type="text" name="recordsetnombre" id="recordsetnombre" value="$rs"  required="required" /></td>
  </tr>
  <tr>
    <td>Nombre primera opcion</td>
    <td><input type="text" name="priopcion" id="priopcion" value="seleccionar..."  required="required" /></td>
  </tr>
  <tr>
    <td>Valor primera opcion</td>
    <td><input type="text" name="valpriopcion" id="valpriopcion" /></td>
  </tr>
  <tr>
    <td>Style</td>
    <td><input type="text" name="estilo" id="estilo" value='class="form-control"'  /></td>
  </tr>
  <tr>
    <td>Acciones (onmouseup=&quot;&quot;, required, etc...)</td>
    <td><input type="text" name="acciones" id="acciones" value='required="required"' /></td>
  </tr>
  <tr>
    <td>Auto Selecciona cuando hay solo 1 registro</td>
    <td><input type="radio" name="autosel" id="autosels" value="S" checked="checked"  required="required" />
      <label for="radio3">SI
        <input type="radio" name="autosel" id="autoseln" value="N"  required="required" />
      NO</label></td>
  </tr>
</table>
<p>
  <input type="submit" name="button" id="button" value="Generar" />
</p>

</form>

<div style="border:1px solid #000; background-color:#fff;">
<?php

//print_r($_POST);
/*
Array ( [consultasql] => select id, nombre from tabla where estado = 1 order by nombre asc [htmlname] => nombre [htmlid] => id [bdname] => nombre [bdid] => id [metodo] => P [rs] => $rs [priopcion] => seleccionar... [valpriopcion] => [estilo] => class="form-control" [acciones] => required="required" [autosel] => S [button] => Generar )
*/


// datos
$consultasql = htmlentities($_POST['consultasql']);
$htmlname = htmlentities($_POST['htmlname']);
$htmlid = htmlentities($_POST['htmlid']);
$bdname = htmlentities($_POST['bdname']);
$bdid = htmlentities($_POST['bdid']);
$bdid2 = htmlentities($_POST['bdid2']);
$metodo = htmlentities($_POST['metodo']);
$recordsetnombre = htmlentities($_POST['recordsetnombre']);
$priopcion = htmlentities($_POST['priopcion']);
$valpriopcion = htmlentities($_POST['valpriopcion']);
$estilo = htmlentities($_POST['estilo']);
$acciones = htmlentities($_POST['acciones']);
$autosel = htmlentities($_POST['autosel']);

if ($metodo == 'G') {
    $metod = "GET";
} elseif ($metodo == 'P') {
    $metod = "POST";
} else {
    $metod = "REQUEST";
}


$txtgenera = "";

$txtgenera .= htmlentities('
<div class="form-group">
	<label class="control-label col-md-3 col-sm-3 col-xs-12">'.$htmlname.' *</label>
	<div class="col-md-9 col-sm-9 col-xs-12">
');

$txtgenera .= '
// consulta
$consulta="
'.$consultasql.'
 ";

';
$txtgenera .= '
// valor seleccionado
if(isset($_POST[\''.$htmlid.'\'])){ 
	$value_selected=htmlentities($_'.$metod.'[\''.$htmlid.'\']); 
}else{
	$value_selected=htmlentities('.$recordsetnombre.'->fields[\''.$bdid2.'\']);
}
';

$txtgenera .= '
// parametros
$parametros_array=array(
		\'nombre_campo\' => \''.$htmlname.'\',
		\'id_campo\' => \''.$htmlid.'\',
		
		\'nombre_campo_bd\' => \''.$bdname.'\',
		\'id_campo_bd\' => \''.$bdid.'\',
		
		\'value_selected\' => $value_selected,
		
		\'pricampo_name\' => \''.$priopcion.'\',
		\'pricampo_value\' => \''.$valpriopcion.'\',
		\'style_input\' => \''.$estilo.'\', 
		\'acciones\' => \''.$acciones.'\',
		\'autosel_1registro\' => \''.$autosel.'\'
		
);
';

$txtgenera .= '
// construye campo
echo campo_select($consulta,$parametros_array);
';

$txtgenera .= htmlentities("
	</div>
</div>
");


echo "<pre>";
echo $txtgenera;
echo "</pre>";

?><br /><br />
        </div>
        
        <br /><br />
</body>
</html>