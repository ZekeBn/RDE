<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

$idadherente = intval($_GET['id']);
if ($idadherente == 0) {
    echo "No especifico el adherente.";
    exit;
}
$buscar = "
select * 
from cliente 
where 
idcliente in (
			select idcliente from adherentes 
			where 
			idadherente = $idadherente 
			and idempresa = $idempresa 
			and adherentes.idcliente = cliente.idcliente
			and estado <> 6
			)
and idempresa = $idempresa
and permite_acredito = 'S'
and estado <> 6
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

$consulta = "
select * from adherentes 
where 
idcliente = $idcliente
 and idadherente = $idadherente 
 and idempresa = $idempresa
 and estado <> 6
 ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$linea_sobregiro_ad = floatval($rs->fields['linea_sobregiro']);
$max_mensual_ad = floatval($rs->fields['maximo_mensual']);


if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    //print_r($_POST);
    //exit;
    //Array ( [linea_credito_1] => 0.0000 [max_mensual_1] => 0.0000 [linea_credito_2] => 0.0000 [max_mensual_2] => 0.0000 [registrar] => Guardar Cambios [MM_update] => form1 )
    // inicializar variables
    $errores = "";
    $valido = "S";

    // busca todos los servicios habilitados
    $consulta = "
	select *
	from servicio_comida 
	where 
	idempresa = $idempresa 
	and estado = 'A'
	";
    $rsser = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    // recorre todos los servicios
    while (!$rsser->EOF) {
        $idserviciocom = $rsser->fields['idserviciocom'];
        $nombre_servicio = htmlentities($rsser->fields['nombre_servicio']);
        // por cada servicio asigna el valor
        $linea_credito = floatval($_POST['linea_credito_'.$idserviciocom]);
        $max_mensual = floatval($_POST['max_mensual_'.$idserviciocom]);
        // sumar
        $linea_creditoacum += $linea_credito;
        $max_mensualacum += $max_mensual;

        // validar
        if (trim($_POST['linea_credito_'.$idserviciocom]) == '') {
            $valido = "N";
            $errores .= "- No asigno linea de credito al servicio $nombre_servicio.<br />";
        }
        if (trim($_POST['max_mensual_'.$idserviciocom]) == '') {
            $valido = "N";
            $errores .= "- No asigno maximo mensual al servicio $nombre_servicio.<br />";
        }

        $rsser->MoveNext();
    }

    // valida que no supera la linea maxima del adherente
    if ($linea_creditoacum > $linea_sobregiro_ad) {
        $valido = "N";
        $errores .= "- La sumatoria de las lineas de credito asignada a todos los servicios supera la linea de credito total del adherente.<br />";
    }
    // valida que no supera el maximo mensual del adherente
    if ($max_mensualacum > $max_mensual_ad) {
        $valido = "N";
        $errores .= "- La sumatoria de os maximos mensuales asignados a todos los servicios supera el maximo mensual total del adherente.<br />";
    }

    // si todo es correcto vuelve a recorrer para insertar o updatear en la bd
    if ($valido == "S") {
        // vuelve al primer registro
        $rsser->MoveFirst();

        // recorre los registros
        while (!$rsser->EOF) {

            $idserviciocom = $rsser->fields['idserviciocom'];

            // por cada servicio asigna el valor
            $linea_credito_ins = antisqlinyeccion($_POST['linea_credito_'.$idserviciocom], 'float');
            $max_mensual_ins = antisqlinyeccion($_POST['max_mensual_'.$idserviciocom], 'float');

            // busca si existe en la tabla
            $consulta = "
			select * 
			from adherentes_servicioscom 
			where 	
			idadherente = $idadherente
			and idserviciocom = $idserviciocom
			";
            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $existe = intval($rsex->fields['idserviciocom']);
            // si no existe inserta
            if ($existe == 0) {
                $consulta = "
				INSERT INTO adherentes_servicioscom
				(idadherente, idserviciocom, idcliente, linea_credito, max_mensual, disponibleserv) 
				VALUES 
				($idadherente,$idserviciocom,$idcliente,$linea_credito_ins,$max_mensual_ins, $linea_credito_ins)
				";
                // si existe actualiza
            } else {
                $consulta = "
				UPDATE adherentes_servicioscom 
				SET 
				linea_credito=$linea_credito_ins,
				max_mensual=$max_mensual_ins 
				WHERE
				idadherente = $idadherente
				and idserviciocom = $idserviciocom
				and idcliente = $idcliente
				";
            }
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            //echo $consulta."<br >";

            $rsser->MoveNext();
        }
        // vuelve al primer registro
        $rsser->MoveFirst();

        // recorre los registros
        /*while(!$rsser->EOF){

            $idserviciocom=$rsser->fields['idserviciocom'];
            // actualiza saldo sobregiro por cada servicio
            $consulta="
            update adherentes_servicioscom set
            disponibleserv = linea_credito-COALESCE((
                                select sum(cuentas_clientes.saldo_activo) as saldoactivo
                                from cuentas_clientes
                                where
                                cuentas_clientes.idcliente = adherentes_servicioscom.idcliente
                                and cuentas_clientes.idadherente = adherentes_servicioscom.idadherente
                                and cuentas_clientes.idserviciocom = adherentes_servicioscom.idserviciocom
                              ),0)
            where
            adherentes_servicioscom.idcliente=$idcliente
            and adherentes_servicioscom.idadherente = $idadherente
            and adherentes_servicioscom.idserviciocom = $idserviciocom
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

        $rsser->MoveNext(); } */
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


        header("location: adherentes_credito.php?id=".$idcliente);
        exit;

    } // if($valido == "S"){

}
/*
update adherentes adherentes_servicioscom
set
disponibleserv =
*/



// establece maximos mensuales automaticamente, parametrizar en preferencias
/*if($database == 'sistema_martaelena_sil' or $database == 'sistema_martaelena_bautista' or $database == 'benditas'){

    // maximos mensuales
    $consulta="
    update cliente set max_mensual = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set maximo_mensual = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set max_mensual = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // linea de credito
    $consulta="
    update cliente set linea_sobregiro = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set linea_sobregiro = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set linea_credito = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // disponible trucho
    $consulta="
    update cliente set saldo_sobregiro = 1000000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes set disponible = 100000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    $consulta="
    update adherentes_servicioscom set disponibleserv = 10000000;
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
}*/


$consulta = "
	select *, 
			coalesce((
			SELECT sum(linea_credito) as linea_credito 
			FROM adherentes_servicioscom 
			where 
			adherentes_servicioscom.idadherente = $idadherente 
			and adherentes_servicioscom.idserviciocom = servicio_comida.idserviciocom 
			and estado <> 6
			),0)  as linea_credito,
			coalesce((
			SELECT sum(max_mensual) as max_mensual 
			FROM adherentes_servicioscom 
			where 
			adherentes_servicioscom.idadherente = $idadherente
			and adherentes_servicioscom.idserviciocom = servicio_comida.idserviciocom 
			and estado <> 6
			),0)  as max_mensual
	from servicio_comida 
	where 
	idempresa = $idempresa 
	and estado = 'A'
	";
$rsser = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
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
					<span class="resaltaditomenor">Servicios de Comida del Adherente</span>
				</div>

<p align="center">&nbsp;</p>
<?php if (trim($errores) != "") { ?>
	<div class="mensaje" style="border:1px solid #F00; background-color:#FFC; font-size:12px; padding:10px; margin:10px auto; width:500px; text-align:center;"><strong>Errores:</strong> <br /><?php echo $errores; ?></div><br />
<?php } ?>

	<table width="800" border="1" class="tablaconborde" align="center">
  <tbody>

	<tr>
	  <td width="25%" align="center">Nombres</td>
	  <td width="25%" align="left" style="height: 40px;"><?php  if (isset($_POST['nombres'])) {
	      echo htmlentities($_POST['nombres']);
	  } else {
	      echo htmlentities($rs->fields['nombres']);
	  }?></td>
	  <td width="25%" align="left" style="height: 40px;">Apellidos</td>
	  <td width="25%" align="left" style="height: 40px;"><?php  if (isset($_POST['apellidos'])) {
	      echo htmlentities($_POST['apellidos']);
	  } else {
	      echo htmlentities($rs->fields['apellidos']);
	  }?></td>
	  </tr>

	
	<tr>
	  <td align="center">L&iacute;nea Cr&eacute;dito</td>
	  <td width="130" align="left"><?php  if (isset($_POST['linea_sobregiro'])) {
	      echo htmlentities($_POST['linea_sobregiro']);
	  } else {
	      echo htmlentities($rs->fields['linea_sobregiro']);
	  }?></td>
	  <td width="130" align="left">M&aacute;ximo Mensual permitido</td>
	  <td width="130" align="left"><?php  if (isset($_POST['maximo_mensual'])) {
	      echo htmlentities($_POST['maximo_mensual']);
	  } else {
	      echo htmlentities($rs->fields['maximo_mensual']);
	  }?></td>
	  </tr>

  </tbody>
</table>

<br />
<p align="center">Servicios:</p>
<p align="center">&nbsp;</p>

<form id="form1" name="form1" method="post" action="">

<table width="900" border="1">
  <tbody>
    <tr>
      <td width="34%" align="center" bgcolor="#F8FFCC">Servicio</td>
      <td width="33%" align="center" bgcolor="#F8FFCC">Linea de Credito</td>
      <td width="33%" align="center" bgcolor="#F8FFCC">Maximo Mensual</td>
    </tr>
<?php while (!$rsser->EOF) {
    $idserviciocom = $rsser->fields['idserviciocom'];
    ?>
    <tr>
      <td align="center"><?php echo $rsser->fields['nombre_servicio'];?></td>
      <td align="center"><input type="text" name="linea_credito_<?php echo $idserviciocom; ?>" id="linea_credito_<?php echo $idserviciocom; ?>" value="<?php  if (isset($_POST['linea_credito_'.$idserviciocom])) {
          echo intval($_POST['linea_credito_'.$idserviciocom]);
      } else {
          echo intval($rsser->fields['linea_credito']);
      }?>" placeholder="Linea credito "   style="height: 40px; width:100%;" /></td>
      <td align="center"><input type="text" name="max_mensual_<?php echo $idserviciocom; ?>" id="max_mensual_<?php echo $idserviciocom; ?>" value="<?php  if (isset($_POST['max_mensual_'.$idserviciocom])) {
          echo intval($_POST['max_mensual_'.$idserviciocom]);
      } else {
          echo intval($rsser->fields['max_mensual']);
      }?>" placeholder="Mensual permitido"   style="height: 40px; width:100%;" /></td>
      </tr>
<?php $rsser->MoveNext();
} ?>
  </tbody>
</table>
<p>&nbsp;</p>
<p align="center">
  <input type="submit" name="registrar" id="registrar" value="Guardar Cambios" />  <input type="hidden" name="MM_update" value="form1" />
</p>
</form>
<p align="center">&nbsp;</p>
<br />

<p align="center">&nbsp;</p>
		  </div> 
			<!-- contenedor -->
   		<div class="clear"></div><!-- clear1 -->
	</div> <!-- cuerpo -->
	<div class="clear"></div><!-- clear2 -->
	<?php require("includes/pie.php"); ?>
</body>
</html>