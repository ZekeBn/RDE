<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");
$consulta = "
update clientes_codigos 
set 
us_self = us_cod 
where 
us_self is null 
and us_cod is not null 

";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcliente = intval($_GET['id']);
if ($idcliente == 0) {
    echo "No especifico el cliente.";
    exit;
}
$buscar = "
select * 
from cliente 
where 
idcliente = $idcliente
and idempresa = $idempresa
and permite_acredito = 'S'
";
$rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$borrable = $rscli->fields['borrable'];
$razon_social = $rscli->fields['razon_social'];
$idcliente = $rscli->fields['idcliente'];
if (intval($rscli->fields['idcliente']) == 0) {
    echo "Cliente inexistente!";
    exit;
}
if ($borrable != 'S') {
    echo "El cliente $razon_social no puede tener linea de credito.";
    exit;
}
/*
$consulta="
select * from adherentes
where
idcliente = $idcliente
 and idadherente = $idadherente
 and idempresa = $idempresa
 ";
$rs=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
*/

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // recibe parametros
    $nombres = antisqlinyeccion($_POST['nombres'], "text");
    $apellidos = antisqlinyeccion($_POST['apellidos'], "text");
    $telefono = antisqlinyeccion($_POST['telefono'], "text");
    //$lugar_actual=antisqlinyeccion($_POST['lugar_actual'],"int");
    $maximo_mensual = antisqlinyeccion($_POST['maximo_mensual'], "float");
    $linea_sobregiro = antisqlinyeccion($_POST['linea_sobregiro'], "float");
    $nomape = antisqlinyeccion($_POST['nombres'].' '.$_POST['apellidos'], 'text');
    $adicional1 = intval($_POST['op1']);
    $idtipoad = intval($_POST['tipoop']);
    $us_cod = antisqlinyeccion(trim($_POST['us_cod']), "text");
    $pass_cod = antisqlinyeccion(trim($_POST['pass_cod']), "clave");

    // validaciones basicas
    $valido = "S";
    $errores = "";


    if (trim($_POST['nombres']) == '') {
        $valido = "N";
        $errores .= " - El campo nombre no puede estar vac&iacute;o.<br />";
    }
    if (trim($_POST['apellidos']) == '') {
        $valido = "N";
        $errores .= " - El campo apellido no puede estar vac&iacute;o.<br />";
    }
    // validaciones si envio codigo
    if (trim($_POST['us_cod']) != '') {
        // busca si existe en tabla de codigos
        $consulta = "
		select * 
		from clientes_codigos 
		where
		idempresa = $idempresa
		and us_cod = $us_cod
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si existe alguien con el mismo codigo
        if (intval($rsex->fields['idcodclie']) > 0) {
            $valido = "N";
            $errores .= " - Ya existe otra persona con el codigo seleccionado.<br />";
        }
        $consulta = "
		select * 
		from clientes_codigos 
		where
		idempresa = $idempresa
		and us_self = $us_cod
		";
        $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si existe alguien con el mismo codigo
        if (intval($rsex->fields['idcodclie']) > 0) {
            $valido = "N";
            $errores .= " - Ya existe otra persona con el codigo seleccionado en campo us_self.<br />";
        }

    }
    // validaciones de linea y max mensual
    $linea_sobregiro_global = $rscli->fields['linea_sobregiro'];
    $max_mensual_global = $rscli->fields['max_mensual'];
    // suma todas las lineas y maximos de los adherentes de este titular
    $consulta = "
	select sum(maximo_mensual) as maximo_mensual_tot, sum(linea_sobregiro) as linea_sobregiro_tot 
	from adherentes
	where
	idcliente = $idcliente
	 and idempresa = $idempresa	
	 and estado=1
	";
    $rstot = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $lineasobregiroad = $rstot->fields['linea_sobregiro_tot'] + $_POST['linea_sobregiro'];
    $maxmensualad = $rstot->fields['maximo_mensual_tot'] + $_POST['maximo_mensual'];
    if ($lineasobregiroad > $linea_sobregiro_global) {
        $valido = "N";
        $errores .= " - La sumatoria de sobregiros de los adherentes supera el maximo permitido del titular.<br />";
    }
    if ($maxmensualad > $max_mensual_global) {
        $valido = "N";
        $errores .= " - La sumatoria de maximos mensuales de los adherentes supera el maximo permitido del titular.<br />";
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        $consulta = "
		insert into adherentes
		(nombres, apellidos, telefono, lugar_actual, maximo_mensual, linea_sobregiro, idcliente, idempresa,nomape,disponible,adicional1,idtipoad)
		values
		($nombres, $apellidos, $telefono, 0, $maximo_mensual, $linea_sobregiro, $idcliente, $idempresa,$nomape,$linea_sobregiro,$adicional1,$idtipoad)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        // recorre cada concepto y  actualiza los saldos disponibles
        $buscar = "
				SELECT *
				from adherentes 
				inner join adherentes_servicioscom on adherentes_servicioscom.idadherente=adherentes.idadherente 
				inner join servicio_comida on servicio_comida.idserviciocom=adherentes_servicioscom.idserviciocom
				where
				adherentes.idcliente=$idcliente 
				and adherentes.idempresa=$idempresa
				order by nomape asc";
        $tad = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        // actualiza saldos
        while (!$tad->EOF) {
            $idserviciocom = intval($tad->fields['idserviciocom']);
            $idadherente = intval($tad->fields['idadherente']);
            actualiza_saldos_clientes($idcliente, $idadherente, $idserviciocom);
            $tad->MoveNext();
        }

        // obtiene ultimo adherente insertado
        $consulta = "
	select idadherente
	from adherentes
	where
	idcliente = $idcliente
	 and idempresa = $idempresa	
	 and estado=1
	 order by idadherente desc
	 limit 1
	";
        $rsult = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idadherente = $rsult->fields['idadherente'];

        // inserta el codigo
        $consulta = "
		INSERT INTO clientes_codigos 
		(us_cod, pass_cod, idcliente, idadherente, registrado_por, registrado_el, idempresa, ult_modif, estado) 
		VALUES 
		($us_cod,$pass_cod,NULL,$idadherente,$idusu,'$ahora',$idempresa,NULL,1)
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
		update clientes_codigos 
		set 
		us_self = us_cod 
		where 
		us_self is null 
		and us_cod is not null 

		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //header("location: adherentes_credito.php?id=".$idcliente);
        header("location: adherentes_credito_serv.php?id=".$idadherente);
        exit;

    }

}


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
              <td width="62"><a href="adherentes_credito.php?id=<?php echo $idcliente; ?>"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
            </tr>
          </tbody>
        </table>
    </div>
 				<div class="divstd">
					<span class="resaltaditomenor">Agregar Adherente</span>
				</div>

<p align="center">&nbsp;</p>
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>
				<div align="center">
			    <a href="ad_nueva_seccion.php?id=<?php echo $idcliente?>" target="_self" title="Agregar nueva seccion"><img src="img/1495723121_Properties.png" width="48" height="48" /></a> </div>
				<p align="center" style="font-size:16px;"><br />
</p>
			  <table width="700" border="1" >
			    <tr>
    <td height="39" align="right" bgcolor="#E4E4E4"><strong>Titular Cuenta</strong></td>
    <td><?php

            echo $rscli->fields['razon_social'];

?></td>
    <td bgcolor="#E4E4E4">Linea Credito</td>
    <td width="150" align="right"><?php

  echo formatomoneda($rscli->fields['linea_sobregiro']);

?></td>
  </tr>
  <tr>
    <td height="36" align="right" bgcolor="#E4E4E4"><strong>Ruc</strong></td>
    <td><?php

    echo $rscli->fields['ruc'];


?></td>
    <td bgcolor="#E4E4E4">Max Mensual</td>
    <td align="right"><?php

  echo formatomoneda($rscli->fields['max_mensual']);

?></td>
  </tr>
</table><br /><br /><br />
<form id="form1" name="form1" method="post" action="">
<table width="400" border="1" class="tablaconborde" align="center">
  <tbody>

	<tr>
	  <td align="center"><strong>*Nombres</strong></td>
	  <td width="130" align="left" style="height: 40px;"><input type="text" name="nombres" id="nombres" value="<?php  if (isset($_POST['nombres'])) {
	      echo htmlentities($_POST['nombres']);
	  } else {
	      echo htmlentities($rs->fields['nombres']);
	  }?>" placeholder="nombres" required="required"   style="height: 40px;"/></td>
	  </tr>

	<tr>
		<td align="center"><strong>*Apellidos</strong></td>
		<td width="130" align="left"><input type="text" name="apellidos" id="apellidos" value="<?php  if (isset($_POST['apellidos'])) {
		    echo htmlentities($_POST['apellidos']);
		} else {
		    echo htmlentities($rs->fields['apellidos']);
		}?>" placeholder="apellidos" required="required"  style="height: 40px;" /></td>
	</tr>

	<tr>
		<td align="center"><strong>Tel&eacute;fono</strong></td>
		<td width="130" align="left"><input type="text" name="telefono" id="telefono" value="<?php  if (isset($_POST['telefono'])) {
		    echo htmlentities($_POST['telefono']);
		} else {
		    echo htmlentities($rs->fields['telefono']);
		}?>" placeholder="telefono"  style="height: 40px;" /></td>
	</tr>

	
	<tr>
		<td align="center"><strong>*L&iacute;nea Cr&eacute;dito</strong></td>
		<td width="130" align="left"><input type="text" name="linea_sobregiro" id="linea_sobregiro" value="<?php  if (isset($_POST['linea_sobregiro'])) {
		    echo intval($_POST['linea_sobregiro']);
		} else {
		    echo intval($rs->fields['linea_sobregiro']);
		}?>" placeholder="Linea credito " required="required"  style="height: 40px;" /></td>
	</tr>
	<tr>
	  <td align="center"><strong>*M&aacute;ximo Mensual permitido</strong></td>
	  <td width="130" align="left"><input type="text" name="maximo_mensual" id="maximo_mensual" value="<?php  if (isset($_POST['maximo_mensual'])) {
	      echo intval($_POST['maximo_mensual']);
	  } else {
	      echo intval($rs->fields['maximo_mensual']);
	  }?>" placeholder="Mensual permitido" required="required"  style="height: 40px;" /></td>
	  </tr>
	<tr>
	  <td height="39" align="center"><strong>Valor Num (Opcional)</strong></td>
	  <td align="left"><input type="text" name="op1" id="op1" style="width: 99%; height: 40px;" /></td>
	  </tr>
	<tr>
	  <td height="40" align="center"><strong>Tipo Secci&oacute;n (Opcional)</strong></td>
	  <td align="left"><?php  $buscar = "Select * from adherentes_tipos_opcionales where estado=1 and idempresa=$idempresa";
$rstpv = $conexion->Execute($buscar) or die(errorpg($buscar, $buscar));
$tot = $rstpv->RecordCount();
if ($tot > 0) {?>
	    <select name="tipoop2" id="tipoop2" style="height: 40px;width: 99%;">
	      <option value="0" selected="selected">Seleccione tipo</option>
	      <?php while (!$rstpv->EOF) {?>
	      <option value="<?php echo $rstpv->fields['idtipoad']?>"><?php echo $rstpv->fields['descripcion']?></option>
	      <?php $rstpv->MoveNext();
	      }?>
	      </select>
	    <?php } else {?>
	    <span class="resaltarojomini">No se registraron tipos opcionales</span>
	    <?php } ?></td>
	  </tr>
	<tr>
	  <td height="40" align="center"><strong>Usuario/COD</strong></td>
	  <td align="left"><?php
            $consulta = "
		select max(idcodclie) as prox
		from clientes_codigos 
		";
$rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$proxid = intval($rsex->fields['prox']) + 1;

?><input type="text" name="us_cod" id="us_cod" value="<?php  if (isset($_POST['us_cod'])) {
    echo htmlentities($_POST['us_cod']);
} else {
    echo htmlentities($proxid);
}?>" /></td>
	  </tr>
	<tr>
	  <td height="40" align="center"><strong>Clave/PIN</strong></td>
	  <td align="left"><input type="text" name="pass_cod" id="pass_cod" value="<?php  if (isset($_POST['pass_cod'])) {
	      echo htmlentities($_POST['pass_cod']);
	  }?>" /></td>
	  </tr>

  </tbody>
</table>
<br />
<p align="center">
  <input type="submit" name="button" id="button" value="Registrar" />
  <input type="hidden" name="MM_update" value="form1" />
</p>
<br />
</form>
<p align="center">&nbsp;</p>
		  </div> 
			<!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>