 <?php
//Verificado para 3 copias el 18/10/2023
/*----------------------------------
21/10/23: Se agrega una copia por hoja


---------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "278";
require_once  '../clases/mpdf/vendor/autoload.php';
require_once("includes/rsusuario.php");

require_once("includes/funciones_cobros.php");
setlocale(LC_TIME, 'es_ES');

$consulta = "
select * from preferencias_caja limit 1
";
$rprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$recibo_doble = $rprefcaj->fields['recibo_doble'];
$firma_recibo = $rprefcaj->fields['firma_recibo'];
$tipo_copias = intval($rprefcaj->fields['tipo_copias']);
$copias_por_pagina = intval($rprefcaj->fields['copias_por_pagina']);//Si es 1, mpdf hara 1 impresion por pagina si o si, dejar cero en preferencias para automatico
if ($tipo_copias == 0) {
    $copias = 2;//Indica original y duplicado
} else {
    if ($tipo_copias < 3) {
        $copias = 2;
    } else {
        $copias = 3; //para colocar el triplicado
    }

}
// para php7
if (!function_exists('set_magic_quotes_runtime')) {
    function set_magic_quotes_runtime($value)
    {
        return true;
    }
}
function wp_slash($value)
{
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $value[ $k ] = wp_slash($v);
            } else {
                $value[ $k ] = addslashes($v);
            }
        }
    } else {
        $value = addslashes($value);
    }

    return $value;
}
$idcuentaclientepagcab = intval($_GET['id']);
$buscar = "Select * from recibos_preferencias where factura_suc=$factura_suc and factura_pe=$factura_pexp limit 1";
$rprefrec = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tiporecibo = intval($rprefrec->fields['tipo_recibo']);
if ($tiporecibo == 0) {
    $tiporecibo = 1;
}

$consulta = "
select * 
from cuentas_clientes_pagos_cab 
where 
idcuentaclientepagcab = $idcuentaclientepagcab
and estado <> 6
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcuentaclientepagcab = $rs->fields['idcuentaclientepagcab'];
$idcajamov = intval($rs->fields['idcajamov']);
$idpago_afavor = intval($rs->fields['idpago_afavor']);
if ($idpago_afavor > 0) {
    $consulta = "
    select *,
    (select idadherente from adherentes where idadherente = pagos_afavor_adh.idadherente) as idadherente,
    (select nomape from adherentes where idadherente = pagos_afavor_adh.idadherente) as adherente,
    (select nombre_servicio from servicio_comida where idserviciocom = pagos_afavor_adh.idserviciocom) as servicio_comida
    from pagos_afavor_adh 
    where 
    idpago_afavor = $idpago_afavor
    ";
    $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idadherente = $rspag->fields['idadherente'];
    $adherente = $rspag->fields['adherente'];
    $servicio_comida = $rspag->fields['servicio_comida'];
    $idevento = intval($rspag->fields['idevento']);
    // busca si es un evento
    if ($idevento > 0) {
        $consulta = "
        select nombre_evento from pedidos_eventos where regid = $idevento limit 1 
        ";
        $rsev = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }
    // si es un anticipo multiple
    $consulta = "
    select anticipo_multiple_cab.idanticipomultiplecab
    from anticipo_multiple_cab
    inner join gest_pagos on gest_pagos.idanticipomultiplecab = anticipo_multiple_cab.idanticipomultiplecab
    inner join pagos_afavor_adh on pagos_afavor_adh.idpago = gest_pagos.idpago
    where 
    anticipo_multiple_cab.estado = 1 
    and gest_pagos.estado = 1
    and pagos_afavor_adh.idpago_afavor = $idpago_afavor
    group by pagos_afavor_adh.idpago_afavor
    limit 1
    ";
    $rsmul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idanticipomultiplecab = intval($rsmul->fields['idanticipomultiplecab']);


}

$consulta = "
    select * from empresas where idempresa = $idempresa
    ";
$rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$razon_social_empresa = trim($rsemp->fields['razon_social']);
$ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
$direccion_empresa = trim($rsemp->fields['direccion']);
$nombre_fantasia_empresa = trim($rsemp->fields['empresa']);
$actividad_economica = trim($rsemp->fields['actividad_economica']);
$web_empresa = trim(strtolower($rsemp->fields['web']));

$consulta = "
    select * from preferencias where idempresa = $idempresa
    ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$muestra_fantasia_fac = trim($rspref->fields['muestra_fantasia_fac']);
$muestra_actividad_fac = trim($rspref->fields['muestra_actividad_fac']);


$consulta = "
    select *, cliente.razon_social as razon_social, cliente.ruc as ruc, cliente.nombre, cliente.apellido, cliente.documento
    from cuentas_clientes_pagos_cab 
    inner join cliente on cliente.idcliente = cuentas_clientes_pagos_cab.idcliente
    where 
    idcuentaclientepagcab = $idcuentaclientepagcab
    ";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idcuentaclientepagcab = $rs->fields['idcuentaclientepagcab'];
$recibo_nro = $rs->fields['recibo'];
$fechahora = date("d/m/Y", strtotime($rs->fields['fecha_pago']));
$monto_abonado = $rs->fields['monto_abonado'];
$razon_social = $rs->fields['razon_social'];
$ruc = trim($rs->fields['ruc']);
$nombre = $rs->fields['nombre'];
$apellido = $rs->fields['apellido'];
$documento = formatomoneda($rs->fields['documento'], 0);
if ($ruc == '44444401-7') {
    $razon_social = $nombre.' '.$apellido;
    if (trim($nombre) == '') {
        echo "No se cargo el nombre del cliente.";
        exit;
    }
    if (intval($documento) == 0) {
        echo "No se cargo el documento del cliente.";
        exit;
    }
}




// numeros a letras
require_once("includes/num2letra.php");
$total_recibo_txt = strtoupper(num2letras(floatval($monto_abonado)));


$consulta = "
    select *, monto_abonado as importe, 
    (
    select  factura
    from ventas 
    inner join cuentas_clientes on cuentas_clientes.idventa = ventas.idventa
    where 
    cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta
    ) as factura,
    (select sucursal from sucursal_cliente where idsucursal_clie=
            (select ventas.idsucursal_clie from ventas 
            inner join cuentas_clientes on cuentas_clientes.idventa = ventas.idventa
            where 
            cuentas_clientes.idcta = cuentas_clientes_pagos.idcuenta)
 ) as sucucliente
    from cuentas_clientes_pagos 

    where 
    idcuentaclientepagcab = $idcuentaclientepagcab
    and estado <> 6
    ";
$rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;

$recibo = "";
$recibo .= $saltolinea;
if ($muestra_fantasia_fac == 'S') {
    if (trim($nombre_fantasia_empresa) != '' && trim($nombre_fantasia_empresa) != trim($razon_social_empresa)) {
        $recibo .= texto_tk(trim($nombre_fantasia_empresa), 40, 'S').$saltolinea;
        $recibo .= texto_tk(trim('DE'), 40, 'S').$saltolinea;
    }
}
$recibo .= texto_tk(trim($razon_social_empresa), 40, 'S').$saltolinea;
$recibo .= texto_tk("RUC: ".trim($ruc_empresa), 40, 'S').$saltolinea;
if (trim($actividad_economica) != '' && $muestra_actividad_fac == 'S') {
    $recibo .= "Actividad Economica: ".trim($actividad_economica).$saltolinea;
}
$recibo .= 'C Matriz: '.trim($direccion_empresa).$saltolinea;
if ($rssuc->fields['idsucu'] > 0) {
    $recibo .= 'Sucursal: '.trim($rssuc->fields['nombre']).$saltolinea;
    $recibo .= trim($rssuc->fields['direccion']).$saltolinea;
}
$recibo .= texto_tk('RECIBO DE DINERO', 40, 'S').$saltolinea;
$recibo .= texto_tk('Nro: '.$recibo_nro, 40, 'S').$saltolinea;
$recibo .= texto_tk("Fecha y Hora: ".$fechahora, 40, 'S').$saltolinea;
$recibo .= '----------------------------------------'.$saltolinea;
$recibo .= 'RECIBI DE: '.$razon_social.$saltolinea;
if ($ruc != '44444401-7') {
    $recibo .= 'RUC: '.$ruc.$saltolinea;
} else {
    $recibo .= 'DOCUMENTO: '.$documento.$saltolinea;
}


if ($idpago_afavor > 0) {
    $c1 .= '<strong>CONCEPTO: </strong>ANTICIPO';
    if ($idadherente > 0) {
        $c1 .= " para ".$adherente." [".$idadherente."] del Servicio: ".$servicio_comida.".---<br />";
    } else {
        $c1 .= ".---<br />";
    }
} else {
    $c1 .= '<strong>CONCEPTO: </strong>COBRO DE FACTURAS SEGUN DETALLE.---<br />';
}
if ($idevento > 0) {
    $c1 .= ' <strong>EVENTO: </strong> '.$rsev->fields['nombre_evento'].'. <br />';
}





if ($tiporecibo == 1) {
    //echo 'ss';exit;
    $leyenda = "ORIGINAL";
    $html2 .= "
    <div style=\"width:700; height:30px; border:0px solid #D03638; margin-left:4%; font-size:12px;\"></div>
    <div style=\"width:700; min-height:350px; border:1px solid #00000; margin-left:6%;margin-top:2%;font-family:Arial, Helvetica, sans-serif;\">
    <table width=\"700\">
        <tr>
            <td>
                <table width=\"100%\" border=\"0\">
                    <tr>
                      <td><img src=\"gfx/empresas/emp_1.png\" style=\"height:60px;\ float:left; margin:0px;\" /></td>
                      <td align=\"center\"><span style=\"font-weight:bold; font-size:14px; text-align:right\">$razon_social_empresa | RUC: $ruc_empresa</span>
                      ";
    if (trim($actividad_economica) != '' && $muestra_actividad_fac == 'S') {

        $html2 .= "<br /><span style=\"font-size:11px;\">Actividad Economica: ".trim($actividad_economica).'</span><br />'.$saltolinea;
    }
    $html2 .= "
                     </td>
                    </tr>
                  </table>
            
            
            </td>
            <td height=\"35\" colspan=\"1\" align=\"center\" style=\"font-size:12px;font-weight:bold;\"></td>
            <td width=\"30\" align=\"right\" ><strong>Gs.</strong></td>
            <td width=\"147\"  align=\"right\" style=\"background-color: #E1E1E1;\"><span style=\"font-weight:bold; font-size:16px; text-align:right\">".formatomoneda($monto_abonado, 2, 'N')."</span></td>
        </tr>
        <tr>
            <td colspan=\"4\" width=\"321\" height=\"30\" align=\"center\" style=\"font-size:18px;\">";



    $html2 .= "
            <span style=\"font-size:16px;\"><span style=\"font-weight:bold; font-size:12px; text-align:right\">RECIBO DE DINERO N&deg; $recibo_nro </span></span>
            </td>
        </tr>
         <tr>
            <td height=\"32\" colspan=\"4\" style=\"font-size:12px; border-bottom:1px solid #000000; height:20px;\"></td>
        </tr>
        
        <tr>
            <td height=\"25\" colspan=\"3\" style=\"font-size:12px;\"><strong>Recibimos de</strong> $razon_social </td>
            
            <td colspan=\"1\"  align=\"center\" style=\"font-size:12px;\" ><strong>RUC:</strong> $ruc </td>
        </tr>
        <tr>
            <td height=\"25\" colspan=\"4\" style=\"font-size:12px;\"><strong>La cantidad de Gs:</strong>  $total_recibo_txt.---</td>
           
        </tr>
        <tr>
            <td height=\"25\" colspan=\"4\" style=\"font-size:12px;\">$c1</td>
           
        </tr>
         <tr>
            <td height=\"25\" colspan=\"4\" style=\"font-size:12px;\">
            ";
    /*$html2.=
    <table width=\"400\" border=\"1\"  style=\"border-collapse:collapse;\">
      <tr>
        <td>Factura</td>
        <td>Importe</td>
      </tr>
    ";*/
    if ($idpago_afavor == 0) {
        $html2 .= "<strong>Facturas:</strong> ";
        while (!$rsdet->EOF) {


            $sucursal_cliente = trim($rsdet->fields['sucucliente']);
            //echo $idcajamov;
            $buscar = "Select descripcion from formas_pago2 
                inner join caja_gestion_mov_det
                on caja_gestion_mov_det.idformapago=formas_pago2.idforma
                where caja_gestion_mov_det.idcajamovdet=$idcajamov";
            $rsdex = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $ff = trim($rsdex->fields['descripcion']);
            /*$html2.="<tr>
                <td>".trim($rsdet->fields['factura'])."</td>".
                "<td align=\"right\">".formatomoneda($rsdet->fields['importe'],2,'N')."</td>"."
              </tr>";*/

            if ($sucursal_cliente != '') {
                $ad1 = " ($sucursal_cliente) ";
            }
            $fg .= trim($rsdet->fields['factura'])."$ad1".' (Gs: '.formatomoneda($rsdet->fields['importe'], 2, 'N').'), ';
            $rsdet->MoveNext();
        }
        $fg = substr($fg, 0, -2);
        $html2 .= $fg;





        //$html2.="</table>";
        // si no es un anticipo multiple muestra formas de pago
        if (intval($idanticipomultiplecab) == 0) {

            $html2 .= "<br /><br />
                    <table width=\"400\" border=\"1\"  style=\"border-collapse:collapse;font-size:12px;\">
                      <tr>
                        <td>Forma de Pago</td>
                        <td>Importe</td>
                      </tr>
                    ";

            //$fg=substr($fg,0,-1);
            //echo
            $consulta = "
                    SELECT formas_pago2.descripcion as forma_de_pago, monto_movimiento 
                    FROM `caja_gestion_mov_det`
                    inner join formas_pago2 on formas_pago2.idforma = caja_gestion_mov_det.idformapago
                    where 
                    idcajamov = $idcajamov
                    order by formas_pago2.descripcion asc
                    ";
            $rsdex2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            while (!$rsdex2->EOF) {
                //$fg2.=trim($rsdex2->fields['forma_de_pago']).' x Gs: '.formatomoneda($rsdex2->fields['monto_movimiento'],2,'N').' ,';
                $html2 .= "<tr>
                            <td>".trim($rsdex2->fields['forma_de_pago'])."</td>".
                    "<td align=\"right\">".formatomoneda($rsdex2->fields['monto_movimiento'], 2, 'N')."</td>"."
                          </tr>";
                $rsdex2->MoveNext();
            }
            //$fg2=substr($fg2,0,-1);

            $html2 .= "</table><br />";

        } // if(intval($idanticipomultiplecab) == 0){


    }


    if ($firma_recibo == 'S') {
        $firma_digital = "<img src=\"gfx/firmas/firma_recibo_mini.png\" style='width:120px; height120px;'>";
        $hei = '';
    } else {
        $firma_digital = "";
        $hei = '<tr><td height=\"30\"></td></tr>';
    }

    $html2 .= "
            </td>
           
        </tr>

        $hei
        <tr>
          <td colspan=\"2\" align=\"left\"><strong><br />Fecha de Pago:  $fechahora </strong><br />
          <br /><span style=\"font-size:12px;\">[ORIGINAL]</span></td>
            <td colspan=\"2\" align=\"center\" style=\"border-top:1px solid #00000;\"><br />$firma_digital<br />------------------------------------
            <br /><strong>Firma Autorizada</strong></td>
        </tr>
         
    </table><br />
    </div>
    
    ";
    //$copia=2;
    $unosolo = $html2;

    if ($copias_por_pagina == 0) {

        $html_duplic = str_replace("[ORIGINAL]", "[DUPLICADO]", $html2."<pagebreak>");
        $html2 .= $html_duplic;

        if ($copias == 3) {
            $html_trip = str_replace("[ORIGINAL]", "[TRIPLICADO]", $unosolo);
            $html2 .= $html_trip;

        }
    } else {
        $html_duplic = str_replace("[ORIGINAL]", "[DUPLICADO]", $html2);
        $html2 .= "<pagebreak>".$html_duplic;

        if ($copias == 3) {
            $html_trip = str_replace("[ORIGINAL]", "[TRIPLICADO]", $unosolo);
            $html2 .= "<pagebreak>".$html_trip;

        }
    }



}

if ($tiporecibo == 2) {

    $txt_color = trim($rprefrec->fields['lat_color_txt']);
    if ($txt_color == '') {
        $txt_color = "white";
    }
    $lat_color = trim($rprefrec->fields['lat_color']);
    if ($lat_color == '') {
        $lat_color = "#160909";
    }
    $dex1 = trim($rprefrec->fields['dex1']);
    $dex2 = trim($rprefrec->fields['dex2']);
    $dex3 = trim($rprefrec->fields['dex3']);
    $muestra_recibofecha = intval($rprefrec->fields['muestrafechalat']);
    if ($muestra_recibofecha == 1) {
        $dex4 = "Recibo N&deg;  $recibo_nro<br />Fecha: $fechahora";
    }
    $muestraidref = intval($rprefrec->fields['muestra_id_ref']);
    if ($muestraidref == 1) {
        $dex5 = "<span style='font-size: 14px; font-weight:bold;'>CV: $idcuentaclientepagcab</span>";
    }
    $html2 .= "";
    //echo $fechahora;exit;
    //$d=date("d/m/Y",strtotime($fechahora));
    //echo $d;exit;
    $ex = explode("/", $fechahora);
    $di = $ex[0];
    $me = $ex[1];
    $an = $ex[2];
    $fecha = DateTime::createFromFormat('!m', $me);
    //$mes = strftime("%B", $fecha->getTimestamp()); // marzo
    $mes = mesespanol($me);


    $html2 .= "
<div style=\"width:700; height:30px; border:0px solid #D03638; margin-left:4%; font-size:12px;\"></div>
    <div style=\"width:700; min-height:350px; border:1px solid #00000; margin-left:6%;margin-top:2%;font-family:Arial, Helvetica, sans-serif;\">
      <table width=\"700\"  style=\"border-collapse:collapse;font-size:14px;\">
                <tr>
                    <td width=\"200\" rowspan=\"7\" style=\"width:200px; border:0px solid #000000;color:$txt_color\" bgcolor=\"$lat_color\"  >
                    <div align=\"center\" style=\"margin-left:2%;\">
                        $razon_social_empresa <br /> $ruc_empresa
                    </div>
                    <br />
                     $direccion_empresa 
                     <br /> <br />
                      $dex1 
                      <br /> <br />
                       $dex2 
                      <br /> <br />
                      $dex3 
                      $web_empresa
                       <br /> <br />
                      $dex4 
                       <br /> <br />
                        <div align=\"center\">
                         $dex5 
                           </div>
                    </td>
                    <td height=\"37\" colspan=\"2\" align=\"center\"><img src=\"gfx/empresas/emp_1.png\" style=\"height:120px;  margin:0px;\" /></td>
                    <td width=\"186\" align=\"center\"><span style=\"font-size:18px;\">RECIBO DE PAGO</span><br /><span style=\"font-size:18px;\">$recibo_nro </span></td>
                </tr>
                <tr>
                    <td height=\"37\" colspan=\"3\"  style=\" text-align: justify\">Recibimos de  $razon_social, con RUC N&deg; $ruc, en fecha  $di de  $mes del $an, los pagos segun detalle, por un total de Gs: ".formatomoneda($monto_abonado, 2, 'N');

    if ($idpago_afavor > 0) {
        $html2 .= ", En concepto de ANTICIPO";
        if ($idadherente > 0) {
            $html2 .= " para ".$adherente." [".$idadherente."] del Servicio: ".$servicio_comida.".";
        } else {
            $html2 .= ".";
        }

    }
    if ($idevento > 0) {
        $html2 .= ' <strong>EVENTO: </strong> '.$rsev->fields['nombre_evento'].'. <br />';
    }

    $html2 .= "</td>
                </tr>";


    if ($idpago_afavor == 0) {
        $html2 .= "
                 <tr>
                     
                    <td height=\"28\" align=\"center\" bgcolor=\"  $lat_color\" style=\"color:$txt_color\">Factura</td>
                    <td colspan=\"2\" align=\"center\" bgcolor=\"$lat_color\"  style=\"color:$txt_color\">Monto</td>
                </tr>";



        while (!$rsdet->EOF) {
            $html2 .= "
                        <tr>
                            <td height=\"28\" align=\"center\">".$rsdet->fields['factura']."</td>
                            <td colspan=\"2\" align=\"center\">".formatomoneda($rsdet->fields['importe'], 2, 'N')."</td>
                        </tr>
                        ";
            $rsdet->MoveNext();
        }

    } // if($idpago_afavor ==0 ){

    $fg = substr($fg, 0, -2);



    $html2 .= "
            </table>
            <table width=\"700\"   style=\"border-collapse:collapse;font-size:12px;\">
              <tr>
              <td width=\"199\" height=\"25\" align=\"center\" bgcolor=\"$lat_color\" style=\"color:$txt_color\"></td>
                <td width=\"217\" align=\"center\" bgcolor=\"$lat_color\" style=\"color:$txt_color\">Medio de Pago</td>
                <td width=\"268\" align=\"center\" bgcolor=\"$lat_color\" style=\"color:$txt_color\">Importe</td>
              </tr>
            
            ";
    if ($idpago_afavor == 0) {
        $consulta = "
                SELECT formas_pago2.descripcion as forma_de_pago, monto_movimiento 
                FROM `caja_gestion_mov_det`
                inner join formas_pago2 on formas_pago2.idforma = caja_gestion_mov_det.idformapago
                where 
                idcajamov = $idcajamov
                order by formas_pago2.descripcion asc
                ";
        $rsdex2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rsdex2->EOF) {
            //$fg2.=trim($rsdex2->fields['forma_de_pago']).' x Gs: '.formatomoneda($rsdex2->fields['monto_movimiento'],2,'N').' ,';
            $html2 .= "
                    <tr>
                        <td height=\"28\" align=\"center\" bgcolor=\"$lat_color\" style=\"color:echo $txt_color\"></td>
                        <td align=\"center\">".trim($rsdex2->fields['forma_de_pago'])."</td>
                        <td align=center>".formatomoneda($rsdex2->fields['monto_movimiento'], 2, 'N')."</td>
                  </tr>";
            $rsdex2->MoveNext();
        }
    }
    if ($idpago_afavor > 0) {
        $consulta = "
                SELECT formas_pago.descripcion as forma_de_pago, monto_pago_det as monto_movimiento 
                FROM gest_pagos_det
                inner join gest_pagos on gest_pagos.idpago = gest_pagos_det.idpago
                inner join pagos_afavor_adh on pagos_afavor_adh.idpago = gest_pagos.idpago
                inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
                where 
                pagos_afavor_adh.idpago_afavor = $idpago_afavor
                order by formas_pago.descripcion asc
                ";
        $rsdex2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        while (!$rsdex2->EOF) {
            //$fg2.=trim($rsdex2->fields['forma_de_pago']).' x Gs: '.formatomoneda($rsdex2->fields['monto_movimiento'],2,'N').' ,';
            $html2 .= "
                    <tr>
                        <td height=\"28\" align=\"center\" bgcolor=\"$lat_color\" style=\"color:echo $txt_color\"></td>
                        <td align=\"center\">".strtoupper(trim($rsdex2->fields['forma_de_pago']))."</td>
                        <td align=center>".formatomoneda($rsdex2->fields['monto_movimiento'], 2, 'N')."</td>
                  </tr>";
            $rsdex2->MoveNext();
        }
    }
    if ($firma_recibo == 'S') {
        $firma_digital = "<img src=\"gfx/firmas/firma_recibo_mini.png\" style='width:150px;'>";
    } else {
        $firma_digital = "";
    }
    $html2 .= "
            </table>
            <table  width=\"700\"   style=\"border-collapse:collapse;font-size:12px;\">
                <tr>
                    <td style=\"width:200px;\"></td>
                     <td height=\"37\" colspan=\"3\" align=\"left\"><strong>Total Gs: ".formatomoneda($monto_abonado, 2, 'N')."($total_recibo_txt)</strong></td>
                    
                </tr>
                 <tr>
                 <td style=\"width:200px;\"></td>
                       <td height=\"37\" colspan=\"4\" align=\"center\">$firma_digital<strong><br /><br/>Firma / Sello</strong></td>
                    
                </tr>
        </table>
    </div>
    
";

    if ($recibo_doble == 'S') {
        $html2 .= $html2;

    }
}
//echo  $html2;exit;

require_once  '../clases/mpdf/vendor/autoload.php';

//echo $html2;exit;

$mpdf = new mPDF('', 'A4', 0, 0, 0, 0, 0, 0);
$mpdf->SetWatermarkText('');
$mpdf->showWatermarkText = false;
$mini = date('dmYHis');
$mpdf->SetDisplayMode('fullpage');
$mpdf->shrink_tables_to_fit = 1;
//$mpdf->shrink_tables_to_fit = 2.5;
// Write some HTML code:
$mpdf->WriteHTML($html2);
$mpdf->showImageErrors = true;
// Output a PDF file
$mpdf->Output($archivopdf, 'I'); // mostrar en el navegador

exit;

?>
