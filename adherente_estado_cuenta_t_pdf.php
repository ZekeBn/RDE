<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "21";
$submodulo = "163";
require_once("includes/rsusuario.php");


require_once  '../clases/mpdf/vendor/autoload.php';

$img = "gfx/empresas/emp_".$idempresa.".png";
if (!file_exists($img)) {
    $img = "gfx/empresas/emp_0.png";
}



//header("Content-type:application/pdf");

if (isset($_POST['busqueda'])) {
    //hay una busqueda
    //Busqueda x titular
    //filtros
    $codigo = intval($_POST['codigo']);
    $busqueda = antisqlinyeccion($_POST['busqueda'], 'text');
    $medio = trim($_POST['medio']);
    $bustrim = str_replace("'", "", $busqueda);
    if ($medio == 'S') {
        //titular

        if ($codigo > 0) {
            //Vemos segun el tipo de busqueda seleccionado
            $add = " and cliente.codclie=$codigo";
            $orderby .= " cliente.ruc asc, ".$saltolinea;
            $hayfiltro = "S";
        }
        if ($busqueda != 'NULL') {
            $add = " and cliente.razon_social like ('%$bustrim%')";
            $orderby .= " cliente.ruc asc, ".$saltolinea;
            $hayfiltro = "S";
        }
    } else {
        //adherente
        if ($codigo > 0) {
            //Vemos segun el tipo de busqueda seleccionado
            $add = " and adherentes.codadhe=$codigo";
            $orderby .= " cliente.ruc asc, ".$saltolinea;
            $hayfiltro = "S";
        }
        if ($busqueda != 'NULL') {
            $add = " and idcliente in(select idcliente from adherentes where nomape like '%$bustrim%' )";
            $orderby .= " cliente.ruc asc, ".$saltolinea;
            $hayfiltro = "S";
        }
    }

    if ($hayfiltro == "S") {
        $consulta = "
		select * 
		from cliente 
		where 
		idcliente is not null
		$add
		order by 
		$orderby
		cliente.idcliente asc
		";
        $rsb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $tfound = $rsb->RecordCount();
    }




}
if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
    $desde = date("Y-m-").'01';
    $hasta = date("Y-m-d");
} else {
    $desde = date("Y-m-d", strtotime($_GET['desde']));
    $hasta = date("Y-m-d", strtotime($_GET['hasta']));
}


$idcliente = intval($_GET['id']);
if (isset($_GET['id']) && intval($_GET['id']) > 0) {


    // llenar estado de cuenta
    $consulta = "
INSERT INTO adherente_estadocuenta 
( fechahora, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim)
SELECT registrado_el as fechahora, 'D' as tipomov, idcliente, idadherente, idserviciocom, deuda_global as monto, idventa, 
NULL as idpago, idcta, idempresa , NULL as idpagodiscrim
from cuentas_clientes 
where 
idcta not in (
				select idcta 
				from adherente_estadocuenta 
				where 
				idempresa = cuentas_clientes.idempresa 
				and adherente_estadocuenta.idcliente = $idcliente
				and idcta is not null
			 )
and cuentas_clientes.estado <> 6
and cuentas_clientes.idcliente = $idcliente
order by registrado_el asc, idcta asc;
;
";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $consulta = "
INSERT INTO adherente_estadocuenta 
( fechahora, tipomov, idcliente, idadherente, idserviciocom, monto, idventa, idpago, idcta, idempresa, idpagodiscrim)
SELECT registrado_el as fechahora, 'C' as tipomov, idcliente, idadherente, idservicio as idserviciocom, monto_asignado as monto, NULL as idventa, idpago, NULL, idempresa, unicorrffg as idpagodiscrim
FROM adherentes_pagos_reg
where
monto_asignado > 0
and unicorrffg not in (
						select idpagodiscrim 
						from adherente_estadocuenta 
						where 
						idempresa = adherentes_pagos_reg.idempresa 
						and adherente_estadocuenta.idcliente = $idcliente
						and idpagodiscrim is not null
					  )
and adherentes_pagos_reg.estado <> 6
and adherentes_pagos_reg.idcliente = $idcliente
ORDER BY fecha ASC, unicorrffg asc;
";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



    //filtros
    $whereadd = "";
    $orderby = "";
    // filtro fijo
    if (trim($_GET['desde']) == '' or trim($_GET['hasta']) == '') {
        $desde = date("Y-m-").'01';
        $hasta = date("Y-m-d");
    } else {
        $desde = date("Y-m-d", strtotime($_GET['desde']));
        $hasta = date("Y-m-d", strtotime($_GET['hasta']));
    }

    // otros filtos
    if (intval($_GET['adh']) > 0) {
        $idadherente = antisqlinyeccion(trim($_GET['adh']), "int");
        $whereadd .= " and adherente_estadocuenta.idadherente = $idadherente ".$saltolinea;
        $buscar = "Select nomape from adherentes where idempresa=$idempresa and idadherente=$idadherente";
        $rsadcl = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $adherente = $rsadcl->fields['nomape'];
    } elseif (trim($_GET['adh']) == 'st') {
        $whereadd .= " and adherente_estadocuenta.idadherente = 0 ".$saltolinea;
        $adherente = 'SOLO TITULAR';
    } else {
        $adherente = 'TODOS';

    }
    if (intval($_GET['sc']) > 0) {
        $idserviciocom = antisqlinyeccion(trim($_GET['sc']), "int");
        $whereadd .= " and adherente_estadocuenta.idserviciocom = $idserviciocom ".$saltolinea;
        $buscar = "Select nombre_servicio from servicio_comida where idserviciocom=$idserviciocom";
        $rsff = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $servicios = $rsff->fields['nombre_servicio'];
    } else {
        $servicios = 'TODOS';
    }


    $buscar = "
			 Select (
    				select sum(monto) from adherente_estadocuenta where tipomov = 'D' and fechahora < '$desde' and idcliente=$idcliente 
					$whereadd
						) as montodeb,
					(   
   					select sum(monto) from adherente_estadocuenta where tipomov = 'C' and fechahora < '$desde' and idcliente=$idcliente
					$whereadd
    				) as montocred  
    
   					from adherente_estadocuenta where idcliente=$idcliente 
					$whereadd
					limit 1 
	";
    $rssal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $saldocredito = intval($rssal->fields['montocred']);
    $saldodebito = intval($rssal->fields['montodeb']);
    $saldoinicial = $saldocredito - $saldodebito;
    $saldoacum = $saldoinicial;

    $consulta = "
	select *
	from adherente_estadocuenta
	where 
	idcliente=$idcliente 
	and date(fechahora) between '$desde' and '$hasta'
	$whereadd
	order by fechahora asc
	";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    //echo $consulta;
    $tr = $rs->RecordCount();

}

if (($idcliente > 0)) {

    $buscar = "Select * from cliente where idempresa=$idempresa and idcliente=$idcliente"				;
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    if ($tr > 0) {



        $html = '';
        $html .= "
<!doctype html>
<html>
<head>
<meta charset=\"utf-8\">
<title>Estado de Cuenta</title>
</head>
<body	style=\"margin:0px;	padding:0px; font-size:12px;\">
 <div align=\"center\">
<br />
        <table width=\"380\" border=\"1\" style=\"border-collapse:collapse; text-align:center; margin:0px auto;  font-size:12px;\">
        <tr>
            	<td height=\"45\"  align=\"left\"><img src=\"$img\" height=\"80\" alt=\"\"/></td>
            	<td colspan=\"4\" align=\"center\"><strong>".$nombreempresa."</strong><br />Estado de Cuenta</td>
       	  </tr>
        </table>
      
         <br />
    </div>
";

        $html .= "
<table width=\"800\" border=\"1\" style=\"border-collapse:collapse; font-size:12px;  margin:0px auto; margin-left:10px; margin-right:10px;\">
  <tbody>
    <tr>
      <td colspan=\"2\" align=\"center\" bgcolor=\"#FFFEC4\"><strong>Titular: ".$rscli->fields['nombre']." ".$rscli->fields['apellido']."&nbsp;| Razon Social: ".$rscli->fields['razon_social']."</strong></td>
    </tr>
    <tr>
      <td><strong>Desde: ".date("d/m/Y", strtotime($desde))."</strong></td>
      <td width=\"50%\"><strong>Hasta:  ".date("d/m/Y", strtotime($hasta))."</strong></td>
    </tr>
    <tr>
      <td><strong>Adherente:".$adherente."</strong></td>
      <td><strong>Servicio: ".$servicios."</strong></td>
    </tr>
  </tbody>
</table>
";


        $html .= "<br />
<table width=\"800\"  style=\"border-collapse:collapse; font-size:12px; margin:0px auto; margin-left:10px; margin-right:10px; \">
		<tr>
				<td width=\"182\" height=\"28\" align=\"left\" bgcolor=\"#FFFEC4\"><strong>Fecha/Hora</strong></td>
				
				<td width=\"83\" align=\"right\" bgcolor=\"#FFFEC4\"><strong>D&eacute;bito</strong></td>
				<td width=\"116\" align=\"right\" bgcolor=\"#FFFEC4\"><strong>Cr&eacute;dito</strong></td>
				<td width=\"116\" align=\"right\" bgcolor=\"#FFFEC4\"><strong>Saldo Acumulado</strong></td>
		  </tr>
			<tr>
			  <td height=\"32\"><strong>Saldo Anterior</strong></td>
				<td></td>
			  <td></td>
				<td align=\"right\"";
        if ($saldoinicial < 0) {
            $html .= "style=\"color:#FF0000;\"";
        }
        $html .= "><strong>";
        if ($saldoinicial < 0) {
            $html .= "-";
        }
        $html .= formatomoneda($saldoinicial, 4, 'N')."</strong></td>
		  </tr>
";

        while (!$rs->EOF) {

            $idventa = intval($rs->fields['idventa']);
            $idpago = intval($rs->fields['idpago']);
            $tipo = $rs->fields['tipomov'];

            if ($tipo == 'C') {
                $credito = $rs->fields['monto'];
                $debito = '';
                $tven = 0;
            }
            if ($tipo == 'D') {
                $credito = '';
                $debito = $rs->fields['monto'];
                $buscar = "Select  sum(cantidad) as cantidad,descripcion,idprod_serial,sum(subtotal) as subtotal
				from ventas_detalles
				inner join productos on productos.idprod_serial=ventas_detalles.idprod
				where ventas_detalles.idventa=$idventa group by idprod_serial order by descripcion asc";
                $rsven = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $tven = $rsven->RecordCount();

            }
            $saldoacum = $saldoacum + (intval($credito) - intval($debito));


            $html .= "

			<tr style=\"border-top: 1px solid #000;\">
			  <td align=\"left\" ><strong>".date("d/m/Y H:i", strtotime($rs->fields['fechahora']))."</strong>
";

            if ($debito != '') {
                $html .= " - Ticket: ".$idventa."";
            } else {
                $html .= " - Pago: ".$idpago."";
            }


            $html .= "</td>
		
				<td align=\"right\"><span style=\"color:#ff0000;\">";
            if ($debito != '') {
                $html .= "-".formatomoneda($debito)."";
            }
            $html .= "</span></td>";
            $html .= "<td align=\"right\">";
            if ($credito != '') {
                $html .= formatomoneda($credito);
            }
            $html .= "</td>
				<td align=\"right\"";
            if ($saldoacum < 0) {
                $html .= "style=\"color:#FF0000;\"";
            }
            $html .= ">";
            $html .= formatomoneda($saldoacum)."</td>
			</tr>";
            if ($tven > 0) {
                $html .= "<tr>
				
			  <td height=\"30\" align=\"left\">
					<div style=\"float: left; margin-left: 20px; border: 1px solid #EDEDED\">
					<table width=\"350\" style=\"border-collapse:collapse; text-align:center; margin:0px auto; font-size:12px;\">";

                while (!$rsven->EOF) {
                    $html .= "
						<tr>
							<td width=\"180\" align=\"left\">".$rsven->fields['descripcion']."</td>
							<td width=\"18\" align=\"left\">-></td>
							<td width=\"32\" align=\"center\">".formatomoneda($rsven->fields['cantidad'], 'f')."</td>
							<td width=\"37\" align=\"right\">".formatomoneda($rsven->fields['subtotal'], 'f')."</td>
						</tr>";
                    $rsven->MoveNext();
                }
                $html .= "</table>
					</div>
			  </td>
			  <td height=\"30\" colspan=\"3\" align=\"left\">&nbsp;</td>
		  </tr>

";




            }
            $rs->MoveNext();
        }



        $html .= "
<tr>
			  <td height=\"30\" align=\"left\" bgcolor=\"#DCDCDC\"><strong>Total:</strong></td>
			  <td height=\"30\" align=\"left\" bgcolor=\"#DCDCDC\">&nbsp;</td>
			  <td height=\"30\" align=\"left\" bgcolor=\"#DCDCDC\">&nbsp;</td>
			  <td height=\"30\" align=\"right\" bgcolor=\"#DCDCDC\"><strong>".formatomoneda($saldoacum)."</strong></td>
	      </tr>
		</table>
";
    } else {
        $html .= "<br />			
    <p align=\"center\">* Sin registros para los filtros seleccionados.</p><br />";
    }
}

$html .= "
<div style=\"font-size:10px; text-align:center;\">
PDF Generated by: e-Kar&uacute;<br />
www.restaurante.com.py<br />
</div><br />	<br />	
</body>
</html>";

//echo $html;


$mpdf = new mPDF('', 'Legal-P', 0, 0, 0, 0, 0, 0);
//$mpdf = new mPDF('','A4',55,'dejavusans');
//$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
$mini = date('dmYHis');
$mpdf->SetDisplayMode('fullpage');
$mpdf->use_kwt = true;
//$mpdf->shrink_tables_to_fit = 1;
$mpdf->shrink_tables_to_fit = 2.5;
// Write some HTML code:
$mpdf->WriteHTML($html);
// Output a PDF file directly to the browser
//si no se usa el tributo I, no permite usar el nombre indicado y los archivos no sedescargan nunca!!
$mpdf->Output('estadocuenta_'.$mini.'.pdf', 'I');

?>		
