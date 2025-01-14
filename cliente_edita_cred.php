<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "126";
require_once("includes/rsusuario.php");

$id = intval($_GET['id']);
if ($id == 0) {
    echo "No especifico el cliente.";
    exit;
}

$buscar = "
select * 
from cliente 
where 
idcliente = $id 
and idempresa = $idempresa
";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$borrable = $rscli->fields['borrable'];
$razon_social = $rscli->fields['razon_social'];
if (intval($rscli->fields['idcliente']) == 0) {
    echo "Cliente inexistente!";
    exit;
}
if ($borrable != 'S') {
    echo "El cliente $razon_social no puede ser editado.";
    exit;
}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    //print_r($_POST);

    // recibe variables
    $nombre = antisqlinyeccion($_POST['nombre'], "text");
    $apellido = antisqlinyeccion($_POST['apellido'], "text");
    $documento = antisqlinyeccion($_POST['documento'], "text");
    $ruc = antisqlinyeccion($_POST['ruc'], "text");
    $razon_social = antisqlinyeccion($_POST['razon_social'], "text");
    $email = antisqlinyeccion($_POST['email'], "text");
    $direccion = antisqlinyeccion($_POST['direccion'], "text");
    $celular = antisqlinyeccion($_POST['celular'], "int");
    $telefono = antisqlinyeccion($_POST['telefono'], "int");
    $idclientetipo = antisqlinyeccion($_POST['idclientetipo'], "int");

    //validar
    $valido = "S";
    if (trim($_POST['razon_social']) == '') {
        $errores .= "- Debe indicar la razon social.<br />";
        $valido = "N";
    }
    if (trim($_POST['ruc']) == '') {
        $errores .= "- Debe indicar el ruc.<br />";
        $valido = "N";
    }
    $ruc_ar = explode("-", trim($_POST['ruc']));
    $ruc_pri = intval($ruc_ar[0]);
    $ruc_dv = intval($ruc_ar[1]);
    if ($ruc_pri <= 0) {
        $errores .= "- El ruc no puede ser cero o menor.<br />";
        $valido = "N";
    }
    if (strlen($ruc_dv) <> 1) {
        $errores .= "- El digito verificador del ruc no puede tener 2 numeros.<br />";
        $valido = "N";
    }
    if (calcular_ruc($ruc_pri) <> $ruc_dv) {
        $digitocor = calcular_ruc($ruc_pri);
        $errores .= "- El digito verificador del ruc no corresponde a la cedula el digito debia ser $digitocor para la cedula $ruc_pri.<br />";
        $valido = "N";
    }
    if ($ruc == $ruc_pred && $razon_social <> $razon_social_pred) {
        $errores .= "- La Razon Social debe ser $razon_social_pred si el RUC es $ruc_pred.<br />";
        $valido = "N";
    }
    if (trim($_POST['ruc']) <> $ruc_pred && $razon_social == $razon_social_pred) {
        $errores .= "- El RUC debe ser $ruc_pred si la Razon Social es $razon_social_pred.<br />";
        $valido = "N";
    }
    if (intval($_POST['idclientetipo']) == 0) {
        $errores .= "- Debe indicar el tipo de cliente.<br />";
        $valido = "N";
    }

    // actualiza
    if ($valido == 'S') {
        $consulta = "
		UPDATE cliente 
		SET 
			nombre=$nombre,
			apellido=$apellido,
			documento=$documento,
			ruc=$ruc,
			telefono=$telefono,
			celular=$celular,
			email=$email,
			direccion=$direccion,
			tipocliente=1,
			razon_social=$razon_social,
			estado=1,
			codclie=0,
			actualizado_el='$ahora',
			sucursal=$idsucursal,
			idclieweb=0,
			idnacion=0,
			tipocliente=$idclientetipo
		WHERE
			idcliente=$id
			and idempresa=$idempresa
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;
        header("location: venta_credito.php?id=".$id."&ok=s");
        exit;
    }


}


?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
<style>
#idclientetipo{
	height:30px;
	width:100%;
}
</style>
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
              <td width="62"><a href="venta_credito.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
			<div class="divstd">
            	<span class="resaltaditomenor">Editar Cliente</span>
            </div>  
            <?php if ($_GET['ok'] != 's') { ?>    
              <form id="form1" name="form1" method="post" action="">     
<div align="center">
<?php if ($valido == "N") { ?><br />
<div class="mensaje" style="border:1px solid #FF0000; background-color:#F8FFCC; width:600px; margin:0px auto; text-align:center; padding:5px;">
<strong>Errores:</strong><br />
<?php echo $errores; ?>
</div><br />
<?php } ?>
  
  <table width="500" height="469">
    	<tr>
        	<td height="30" colspan="2" align="center"><strong>Datos del Cliente</strong></td>
    </tr>
        <tr>
          <td height="39" align="right" bgcolor="#E4E4E4"><strong>*Tipo Cliente</strong></td>
          <td><?php
        // consulta
        $consulta = "select * from cliente_tipo where estado = 1";

                // valor seleccionado
                if (isset($_POST['idclientetipo'])) {
                    $value_selected = htmlentities($_POST['idclientetipo']);
                } else {
                    $value_selected = htmlentities($rscli->fields['tipocliente']);
                }

                // parametros
                $parametros_array = [
                'nombre_campo' => 'idclientetipo',
                'id_campo' => 'idclientetipo',

                'nombre_campo_bd' => 'clientetipo',
                'id_campo_bd' => 'idclientetipo',

                'value_selected' => $value_selected,

                'pricampo_name' => 'Seleccionar...',
                'pricampo_value' => ''
                ];

                // construye campo
                echo campo_select($consulta, $parametros_array);

                ?></td>
        </tr>
        <tr>
          <td height="39" align="right" bgcolor="#E4E4E4"><strong>*Razon Social</strong></td>
          <td><input type="text" name="razon_social" id="razon_social" placeholder="Razon Social" style="height:30px;width:100%" value="<?php
                  if (isset($_POST['razon_social'])) {
                      echo antixss($_POST['razon_social']);
                  } else {
                      echo $rscli->fields['razon_social'];
                  }
                ?>" /></td>
        </tr>
        <tr>
          <td height="36" align="right" bgcolor="#E4E4E4"><strong>*Ruc</strong></td>
          <td><input type="text" name="ruc" id="ruc" placeholder="Ruc / Gen&eacute;rico" style="height:30px;width:100%" value="<?php
                if (isset($_POST['ruc'])) {
                    echo antixss($_POST['ruc']);
                } else {
                    echo $rscli->fields['ruc'];
                }


                ?>" /></td>
        </tr>
        <tr>
          <td height="39" align="right" bgcolor="#E4E4E4"><strong>Documento:</strong></td>
          <td><input type="text" name="documento" id="documento" placeholder="Documento" style="height:30px;width:100%" value="<?php
                if (isset($_POST['documento'])) {
                    echo antixss($_POST['documento']);
                } else {
                    echo $rscli->fields['documento'];
                }


                ?>" /></td>
        </tr>
        <tr>
            <td width="91" height="39" align="right" bgcolor="#E4E4E4"><strong>Nombres</strong></td>
          <td width="197"><input type="text" name="nombre" id="nombre" placeholder="Nombres" style="height:30px; width:100%" value="<?php
                if (isset($_POST['nombre'])) {
                    echo antixss($_POST['nombre']);
                } else {
                    echo $rscli->fields['nombre'];
                }


                ?>"  /></td>
      </tr>
        <tr>
            <td height="38" align="right" bgcolor="#E4E4E4"><strong>Apellidos</strong></td>
          <td><input type="text" name="apellido" id="apellido" placeholder="Apellidos" style="height:30px;width:100%" value="<?php
          if (isset($_POST['apellido'])) {
              echo antixss($_POST['apellido']);
          } else {
              echo $rscli->fields['apellido'];
          }


                ?>" /></td>
      </tr>
        <tr>
          <td height="36" align="right" bgcolor="#E4E4E4"><strong>Tel&eacute;fono</strong></td>
          <td><input type="text" name="telefono" id="telefono" placeholder="Tel&eacute;fono" style="height:30px;width:100%" value="<?php
          if (isset($_POST['telefono'])) {
              echo antixss($_POST['telefono']);
          } else {
              echo $rscli->fields['telefono'];
          }



                ?>" /></td>
        </tr>
        <tr>
            <td height="36" align="right" bgcolor="#E4E4E4"><strong>Celular</strong></td>
            <td><input type="text" name="celular" id="celular" placeholder="Celular" style="height:30px;width:100%" value="<?php
         if (isset($_POST['celular'])) {
             if ($_POST['celular'] > 0) {
                 echo '0'.intval($_POST['celular']);
             }
         } else {
             if ($rscli->fields['celular'] > 0) {
                 echo '0'.intval($rscli->fields['celular']);
             }
         }

                ?>" /></td>
      </tr>
        <tr>
          <td height="36" align="right" bgcolor="#E4E4E4"><strong>Email</strong></td>
          <td><input type="text" name="email" id="email" placeholder="Email" style="height:30px;width:100%" value="<?php
                 if (isset($_POST['email'])) {
                     echo antixss($_POST['email']);
                 } else {
                     echo $rscli->fields['email'];
                 }

                ?>" /></td>
        </tr>
        <tr>
          <td height="37" align="right" valign="top" bgcolor="#E4E4E4"><strong>Direccion</strong></td>
          <td><textarea cols="45" rows="5" name="direccion" id="direccion" placeholder="Direccion" style="width:100%" ><?php
                  if (isset($_POST['direccion'])) {
                      echo antixss($_POST['direccion']);
                  } else {
                      echo $rscli->fields['direccion'];
                  }


                ?></textarea></td>
        </tr>
    <tr>
        <td height="36" colspan="2" align="center"><input type="submit" name="reg" value="Guardar Cambios"/></td>
    </tr>
  </table>
</div><input type="hidden" name="MM_update" value="form1" /></form><?php } else { ?>
<h1 style="font-weight:bold; color:#00C02D;">Registro Exitoso!</h1>
<?php } ?>
		  </div> <!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>