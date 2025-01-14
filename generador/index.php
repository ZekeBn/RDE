<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
$adodb_conn = $conexion;

// Modulo y submodulo respectivamente
/*$modulo="1";
$submodulo="1";
require_once("../includes/rsusuario.php"); */


if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $_SERVER['REMOTE_ADDR'] != '::1') {
    echo "acceso denegado ".$_SERVER['REMOTE_ADDR'];
    exit;
}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Armar Codigo</title>
<script src="../vendors/jquery/dist/jquery.min.js"></script>
<script>
function armar(){
	var tipo = $('#tipo').val();
	var tabla = $('#tabla').val();
	var coldoble = $('#coldoble').val();
	if(tipo == 1){
		document.location.href='generador_codigo_new.php?t='+tabla+'&ac=i&coldoble='+coldoble+'&tipo=add';
	}
	if(tipo == 2){
		document.location.href='generador_codigo_new.php?t='+tabla+'&ac=u&coldoble='+coldoble+'&tipo=edit';
	}
	if(tipo == 3){
		document.location.href='generador_codigo_grilla_new.php?t='+tabla;
	}
	if(tipo == 4){
		document.location.href='generador_codigo_deta_new.php?t='+tabla;
		//document.location.href='generador_codigo_new.php?t='+tabla+'&ac=';
	}
	if(tipo == 5){
		document.location.href='generador_codigo_del_new.php?t='+tabla+'&coldoble='+coldoble+'&tipo=delete';
		//alert("proximamente!");
		//document.location.href='generador_codigo_new.php?t='+tabla+'&ac=';
	}
	if(tipo == 6){
		document.location.href='generador_codigo_res_new.php?t='+tabla;
		//alert("proximamente!");
		//document.location.href='generador_codigo_new.php?t='+tabla+'&ac=';
	}
	if(tipo == 7){
		document.location.href='generador_codigo_grilla_new.php?t='+tabla+'&tg=i';
		//alert("proximamente!");
		//document.location.href='generador_codigo_new.php?t='+tabla+'&ac=';
	}
	if(tipo == 8){
		document.location.href='generador_codigo_botones.php?t='+tabla+'&tg=i';
		//alert("proximamente!");
		//document.location.href='generador_codigo_new.php?t='+tabla+'&ac=';
	}
}
</script>
</head>

<body>
Tipo: 
<form id="form1" name="form1" method="post" action="">
  <p>
    <label for="select"></label>
    <select name="tipo" id="tipo">
      <option value="1">Agregar</option>
      <option value="2">Editar</option>
      <option value="3">Grilla</option>
      <option value="4">Detalle</option>
      <option value="5">Borrar</option>
      <option value="6">Restaurar</option>
      <option value="7">Grilla Restaura</option>
      <option value="8">Botones</option>
    </select>
  </p>
  <p>Tabla: 
    <input name="tabla" id="tabla" type="text" />
  </p>
    <p>Col Doble (s/n): 
    <input name="coldoble" id="coldoble" type="text" value="s" />
  </p>
  <p>
    <input type="button" name="button" id="button" value="Generar" onmouseup="armar();" />
  </p>
</form>
</body>
</html>