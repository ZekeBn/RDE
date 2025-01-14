 <?php
/*----------------------------------
21/10/23: Se agrega una copia por hoja
26/12/2023: Se agrega observacion de tmpventares_cab


---------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";
if (file_exists('../clases/mpdf/vendor/autoload.php')) {
    require_once '../clases/mpdf/vendor/autoload.php';
} elseif (file_exists('clases/mpdf/vendor/autoload.php')) {
    require_once 'clases/mpdf/vendor/autoload.php';
} else {
    throw new Exception("Archivo no encontrado en ninguna de las rutas especificadas.");
}
require_once("includes/rsusuario.php");
require_once("includes/num2letra.php");
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
/*----------------------------------------

Con impresion de factura A4

------------------------------------------*/
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


if (intval($_GET['v']) > 0) {
    $venta = intval($_GET['v']);
}
if (intval($_GET['vta']) > 0) {
    $venta = intval($_GET['vta']);
}
$idventa = $venta;
$clase = intval($_REQUEST['clase']);

$consulta = "
SELECT 
idventa, factura, facturas.idtimbradotipo, timbrado_tipo.timbrado_tipo
FROM `ventas`
inner join facturas on facturas.idtanda = ventas.idtandatimbrado
inner join timbrado_tipo on timbrado_tipo.idtimbradotipo = facturas.idtimbradotipo
WHERE
ventas.idventa = $venta
";
$rstimb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
if ($rstimb->fields['idtimbradotipo'] == 1) {
    echo "El timbrado de la venta que intentas ver es PREIMPRESO.";
    exit;
}

//incorporamos preferencias de caja
$buscar = "Select * from preferencias_caja limit 1";
$rsbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tipo_a4 = intval($rsbb->fields['tipo_a4']);
$leyenda_credito_a4 = trim($rsbb->fields['leyenda_credito_a4']);
$leyenda_contado_a4 = (trim($rsbb->fields['leyenda_contado_a4']));
$cantidad_copias = intval($rsbb->fields['tipo_copias']);//para saaber si son dos, 3 (ORIGINAL,DUPLICADO<TRIPLICADO)
$copias_por_pagina = intval($rsbb->fields['copias_por_pagina']);//Si es 1, mpdf hara 1 impresion por pagina si o si, dejar cero en preferencias para automatico
$vencimiento_credito_muestra_fac = trim($rsbb->fields['vencimiento_credito_muestra_fac']);

//echo $leyenda_credito_a4;exit;
if ($clase == 0) {
    $tipo_a4 = 1;
}
if ($tipo_a4 == 1) {


    if ($clase == 0) {



        //cabecera
        $consulta = "
        Select factura,ventas.idventa,recibo,ventas.razon_social,ruchacienda,dv,idpedido,ventas.idcliente as idunicocli,
        (select telefono from cliente where idcliente = ventas.idcliente) as telefono,
        (select direccion from cliente where idcliente = ventas.idcliente) as direccion,
        total_cobrado,total_venta,otrosgs,fecha,tipo_venta,descneto,totaliva10,totaliva5,texe,
        (select prox_vencimiento from cuentas_clientes where idventa = ventas.idventa) as factura_vto,
        (select obs_varios from ventas_datosextra where idventa = ventas.idventa ) as obs_varios
        from ventas
        inner join cliente on cliente.idcliente=ventas.idcliente
        where 
        cliente.idempresa=$idempresa 
        and ventas.idempresa=$idempresa 
        and idventa=$venta
        and ventas.estado <> 6
        ";
        $rsvv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $idventa = intval($rsvv->fields['idventa']);
        if ($idventa == 0) {
            echo "La venta fue anulada.";
            exit;
        }


        // auto impresor
        $factura_auto = factura_autoimpresor($idventa);
        $factura_auto = utf8_encode($factura_auto);
        //echo $factura_auto;exit;

        // separa el texto del QR
        $texto_ar = explode('<QR>', $factura_auto);
        $texto1 = $texto_ar[0].'';
        $texto_despues = $texto_ar[1];
        $texto_despues_ar = explode('</QR>', $texto_despues);
        $texto_qr = $texto_despues_ar[0];
        $texto2 = $texto_despues_ar[1];


        require_once  '../clases/mpdf/vendor/autoload.php';


        $mpdf = new mPDF('', 'A4', 0, 0, 0, 0, 0, 0);

        $factura_auto1 = preparePreText($texto1);
        $factura_auto_qr = '<div style="text-align: center;"><barcode code="'.$texto_qr.'" type="QR" class="barcode" size="1.6" error="H" disableborder="1" /></div>';
        $factura_auto2 = preparePreText($texto2);


        $html = '
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <style>
        pre {
          display: block;
          font-family: monospace;
          white-space: pre;
          margin: 1em 0;
          font-size:10px;
        } 
        *{
            margin:0px;
            padding:0px;    
        }
        </style>
        </head>

        <body><br />
        <div style="width:250px; margin:0px auto; border:1px solid #000000; padding:5px;">
        <pre>'.$factura_auto1.$factura_auto_qr.$factura_auto2.'</pre>
        </div>
        </body>
        </html>
        ';









        //$mpdf=new mPDF('utf-8', array(800,1280)); // ancho , alto
        //$mpdf = new mPDF('','A4',55,'dejavusans');
        //$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
        $mpdf->SetWatermarkText('');
        $mpdf->showWatermarkText = false;




        $mini = date('dmYHis');
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->shrink_tables_to_fit = 1;
        //$mpdf->shrink_tables_to_fit = 2.5;
        // Write some HTML code:
        $mpdf->WriteHTML($html);
        $mpdf->showImageErrors = true;

        // Output a PDF file
        $mpdf->Output($archivopdf, 'I'); // mostrar en el navegador
        //$mpdf->Output($archivopdf,'D');  // // descargar directamente
        //$mpdf->Output('facturas_tmp/'.$archivopdf,'F'); // guardar archivo en el servidor


        // una vez enviado el mail borra todos los archivos temporales
        /*$files = glob('facturas_tmp/*'); // obtiene todos los archivos
        foreach($files as $file){
          if(is_file($file)) // si se trata de un archivo
            unlink($file); // lo elimina
        }*/

        exit;
    } else {
        //echo "aca";exit;
        if (file_exists('../clases/mpdf/vendor/autoload.php')) {
            require_once '../clases/mpdf/vendor/autoload.php';
        } elseif (file_exists('clases/mpdf/vendor/autoload.php')) {
            require_once 'clases/mpdf/vendor/autoload.php';
        } else {
            throw new Exception("Archivo no encontrado en ninguna de las rutas especificadas.");
        }
        $html = '
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title></title>
        <style>
        .cabezalcompleto {
          border: 1px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:90%;text-align:center;
          margin-left:auto;
          margin-right:auto;
        } 
        .datosclientes {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:90%;
          margin-left:auto;
          margin-right:auto;
        } 
        .cajustadotit{
            border: 1px solid #000000; margin-left:auto;margin-right:auto; width:90%; height:20px;
            
        }
        .cajustado {
            border: 1px solid #000000; margin-left:auto;margin-right:auto; width:90%; height:400px;
        }
        .cabeza1 {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:20%;
          text-align:center;
          float:left;
        } 
        .cabeza1logo {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:20%;
          text-align:center;
          float:left;
        } 
        .cabeza2 {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:50%;
          float:left;
          text-align:center;
          font-size:0.6em;
        } 
        .cabeza3 {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:20%;
          float:left;
          text-align:center;
          font-size:0.6em;
          font-weight:bold;
        } 
        .cabezacentral {
          border: 0px solid #A37A89; 
          height: 70px;
          border-radius: 15px;
          width:50%;
          float:left;
          font-size:0.8em;
        } 
        .cabezacentralizq {
          border: 0px solid #A37A89; 
          height: 70px;
          border-radius: 15px;
          width:45%;
          float:left;
          font-size:0.8em;
        } 
        .describeproductocab{
            float:left; 
            width:39%; 
            text-align:left; 
            font-size:0.6em; 
            border-right:1px solid;
            margin:0px;
            font-weight:bold;
            font-size: 14px;
        }
        .describeproducto{
            float:left; 
            width:39%; 
            text-align:left; 
            font-size:0.6em; 
            /*border-right:1px solid;*/
            margin:0px;
        }
        .describeproducto10cab{
            float:left; 
            width:10%; 
            text-align:center; 
            font-size:0.6em;
            border-right:1px solid;
            margin:0px;
            font-weight:bold;
            font-size: 14px;
        }
        .describeproducto10{
            float:left; 
            width:10%; 
            text-align:right; 
            font-size:0.6em;
            /*border-right:1px solid;*/
            margin:0px;
        }
        table {
          width: 100%;
        }
        table, th, td {
          border-left: 1px solid;
           font-size:0.8em;
           border-collapse:collapse;
        }

        /* PIE FACTURA ELECTRONICA */

        .pie-factura-electronica-section {
            border: 1px solid #000;
            padding: 10px;
            font-family: Arial, sans-serif;
            margin-left:auto; 
            margin-right:auto; 
            width:87.5%; 
            height:20px;
            text-align:left;
            font-size:8px;
        }
        .header {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }
        .qr-code {
            float: right;
            width: 100px;
        }
        .content {
            background-color: #d3d3d3;
            text-align: center;
            font-size: 8px;
            margin: 5px 0;
            height:5px;
            text-align: center;
            vertical-align: middle;
            line-height: 50px; 
        }
        .footer {
            text-align: center;
            font-size: 10px;
        }

        </style>
        </head>

        <body><br />
        ';

        // crea imagen
        $img = "gfx/empresas/emp_1.png";
        if (!file_exists($img)) {
            $img = "gfx/empresas/emp_1.jpg";
            if (!file_exists($img)) {
                $img = "gfx/empresas/emp_0.png";
            }
        }

        $buscar = "Select * from sucursales where muestrafactura='S' and idsucu <> $idsucursal order by nombre asc";
        $rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $buscar = "Select * from sucursales where idsucu = $idsucursal ";
        $rsv1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $telfoactual = trim($rsv1->fields['telefono']);
        $direccionempresa = trim($rsv1->fields['direccion']);
        $nempresa = trim($rsv1->fields['nombre']);

        $consulta = "
    select * from empresas where idempresa = $idempresa
    ";
        $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $razon_social_empresa = trim($rsemp->fields['razon_social']);
        $ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
        $direccion_empresa = trim($rsemp->fields['direccion']);
        $nombre_fantasia_empresa = trim($rsemp->fields['empresa']);
        $actividad_economica = trim($rsemp->fields['actividad_economica']);
        $telefono_empresa = trim($rsemp->fields['telefono']);
        $correo_empresa = strtolower(trim($rsemp->fields['email']));
        $representante_legal = trim($rsemp->fields['representante_legal']);
        $consulta = "
    INSERT INTO log_impresiones_ventas
    (idventa, impreso_por, impreso_el) 
    VALUES
    ($idventa,$idusu,'$ahora')
    ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
        select *, ventas.fecha as fechaven,ventas.sucursal as idsucursal, (select count(*) from (select idprod FROM ventas_detalles where ventas_detalles.idemp = $idempresa and ventas_detalles.idventa = $idventa GROUP by idprod) as tot)  as total_detalles,
        (select numero_mesa from mesas where idmesa = ventas.idmesa) as numero_mesa,
        (select chapa from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_corto_cliente,
        (select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
        (select observacion_larga from ventas_observacion_larga where idventa = ventas.idventa) as observacion_larga,
        (select usuario from usuarios where idusu = ventas.registrado_por) as cajero,
        direccion as dire_cliente,telefono as telfo_cliente,
        (select wifi from sucursales where sucursales.idsucu = ventas.sucursal limit 1) as wifi,
        (select telefono from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as telefono,
        (select direccion from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as direccion,
        (select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,
        (select llevapos  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as llevapos,
        (select cambio  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as cambio,
        (select cambio-(monto+delivery_costo) as vuelto  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as vuelto,
        (select observacion_delivery  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion_delivery,
        (select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
        (select nombre_deliv  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_deliv,
        (select apellido_deliv  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as apellido_deliv,
        (select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,    
        (select idtmpventares_cab as idpedido from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as idpedido,
        (select motorista from motoristas where ventas.idmotorista = motoristas.idmotorista) as motorista,
        (select cliente_delivery_dom.referencia from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as referencia,
        (select prox_vencimiento from cuentas_clientes where idventa = ventas.idventa) as  prox_vencimiento
        from ventas
        inner join cliente on cliente.idcliente = ventas.idcliente 
        where 
        ventas.idventa = $idventa
        and ventas.estado <> 6 
        limit 1
        ";
        $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // Factura electronica

        $consulta_fact_electronica = "SELECT qr, cdc FROM documentos_electronicos_emitidos
        WHERE idventa = $idventa
        AND estado = 1
        AND estado_set = 3
        ";
        $rs_factura_electronica = $conexion->Execute($consulta_fact_electronica) or die(errorpg($conexion, $consulta_fact_electronica));

        $consulta_preferencias = "SELECT facturador_electronico FROM preferencias WHERE idempresa = 1";
        $rs_preferencias_empresa = $conexion->Execute($consulta_preferencias) or die(errorpg($conexion, $consulta_preferencias));

        //echo $consulta;exit;
        $idventa = $rsv->fields['idventa'];
        $tipo_venta = intval($rsv->fields['tipo_venta']);
        $documento = intval($rsv->fields['documento']);
        $total_detalles = intval($rsv->fields['total_detalles']);
        $idpedido = intval($rsv->fields['idpedido']);
        $idtandatimbrado = intval($rsv->fields['idtandatimbrado']);
        $factura_nro = trim($rsv->fields['factura']);
        $prox_vencimiento = trim($rsv->fields['prox_vencimiento']);
        if ($prox_vencimiento != '') {
            $prox_vencimiento_dmy = ' Vencimiento: '.date('d/m/Y', strtotime($prox_vencimiento));
        }
        if ($vencimiento_credito_muestra_fac == 'N') {
            $prox_vencimiento_dmy = '';
        }
        if ($rsv->fields['obs_varios'] != '') {
            $obs = "&nbsp;<br />".trim($rsv->fields['obs_varios']);
        } else {
            $obs = "";
        }
        //echo $rsv->fields['fechaven'];exit;
        $ventael = date("d/m/Y", strtotime($rsv->fields['fechaven']));
        if ($factura_nro == '') {
            echo "FACTURA NO GENERADA";
            exit;
        }
        if ($rsv->fields['finalizo_correcto'] == 'N') {
            echo "ANULAR VENTA";
            exit;
        }
        $fechahora = date("d/m/Y H:i", strtotime($rsv->fields['fechaven']));
        $idsucursal = intval($rsv->fields['idsucursal']);
        // conversion factura
        $factura_nro = str_replace("-", "", $factura_nro);
        $factura_nro = substr($factura_nro, 0, 3).'-'.substr($factura_nro, 3, 3).'-'.substr($factura_nro, 6, 7);

        if ($idtandatimbrado > 0) {
            $ahorad = date("Y-m-d", strtotime($ahora));
            $consulta = "
            SELECT * 
            FROM facturas 
            where 
            idtanda = $idtandatimbrado
            limit 1
            ";
            $rstimbrado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            /*
            and estado = 'A'
            and valido_hasta >= '$ahorad'
            and valido_desde <= '$ahorad'
            */
            //echo $consulta;
            $timbrado = trim($rstimbrado->fields['timbrado']);
            $valido_desde = date("d/m/Y", strtotime($rstimbrado->fields['valido_desde']));
            $valido_hasta = date("d/m/Y", strtotime($rstimbrado->fields['valido_hasta']));
            $idtanda = intval($rstimbrado->fields['idtanda']);
            if ($idtanda == 0) {
                echo "Timbrado vencido o inexistente.";
                exit;
            }

        } else {

            echo "No hay timbrado activo.";
            exit;
        }
        $consulta = "
        select idventatmp, idprod_serial,
        CASE WHEN 
            ventas_detalles.pchar IS NULL
        THEN
            productos.descripcion
        ELSE    
            ventas_detalles.pchar
        END as producto,  
        ventas_detalles.pchar,
        productos.idtipoproducto,
        CASE WHEN sum(cantidad) > 0 THEN sum(subtotal)/sum(cantidad) ELSE pventa END as pventa,  
        sum(cantidad) as cantidad, 
        (sum(subtotal)-(sum(subtotal)/(1+iva/100))) as iva_monto, iva, barcode,
         sum(subtotal) as subtotal,
         max(idventadet) as idventadet,
         productos.barcode
        from ventas_detalles 
        inner join productos on productos.idprod_serial = ventas_detalles.idprod
        where 
        ventas_detalles.idventa = $idventa
        GROUP by idprod_serial, 
        CASE WHEN 
            ventas_detalles.pchar IS NULL
        THEN
            productos.descripcion
        ELSE    
            ventas_detalles.pchar
        END,
        ventas_detalles.pchar,
         iva, barcode, productos.idtipoproducto, productos.barcode
        order by max(idventadet) asc
        ";
        $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;exit;
        $total_items = $rsdet->RecordCount();
        //echo $total_items;exit;

        // tipos de iva de la factura actual
        if ($iva_multiple == 'N') {
            $consulta = "
            select  iva as iva_porc, sum(subtotal) as subtotal_poriva, (sum(subtotal)-(sum(subtotal)/(1+iva/100))) as subtotal_monto_iva,
            CASE WHEN
                iva = 10
            THEN
                $descuento
            ELSE
                0
            END as descneto10,
            CASE WHEN
                iva = 10
            THEN
                $descuento/11
            ELSE
                0
            END as descnetoiva10
            from ventas_detalles 
            where 
            idventa = $idventa
            group by iva 
            order by iva desc
            ";
            //echo $consulta;
            $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        } else {

            $consulta = "
            select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, sum(ivaml) as subtotal_monto_iva
            from ventas_detalles_impuesto 
            where 
            idventa = $idventa
            group by iva_porc_col
            order by iva_porc_col desc
            ";
            //echo $consulta;
            $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }

        // conversiones
        if ($tipo_venta == 2) {
            $condicion_venta = "CREDITO";
        } else {
            $condicion_venta = "CONTADO";
        }

        if (trim($rsv->fields['ruc']) != '') {
            $dct = $rsv->fields['ruc'];
        } else {
            $dct = $rsv->fields['ruc'].'-'.$rsv->fields['dv'];
        }
        if (trim($documento) != '') {
            if (intval($documento > 0)) {
                $dct .= ' / '.$documento;
            }
        }



        $rz = trim($rsv->fields['razon_social']);
        $dr = trim($rsv->fields['direccion']);
        $telf = trim($rsv->fields['telefono']);
        if ($dr == '') {
            $dr = trim($rsv->fields['dire_cliente']);
        }
        if ($telf == '') {
            $telf = trim($rsv->fields['telfo_cliente']);
        }


        $html .= "
    <br />
        <div class=\"cabezalcompleto\">
            <div class=\"cabeza1\">
                    <img src=\"$img\" style=\"height: 200px;width:200px;\" class=\"cabeza1logo\" />
                </div>
            <div class=\"cabeza2\">
                     $nombreempresa <br />de $razon_social_empresa<br />
                    $nempresa | $direccion_empresa  | Telefono(s): $telefono_empresa<hr />
                    $actividad_economica<br />$correo_empresa";

        while (!$rsvv->EOF) {
            $html .= $rsvv->fields['nombre']." | ".$rsvv->fields['direccion']." | Telefono(s): ".$rsvv->fields['telefono']."<br />";
            $rsvv->MoveNext();
        }

        $html .= "    
            </div>
             <div class=\"cabeza3\">
             <strong>R.U.C. &nbsp; $ruc_empresa</strong><br /> 
             Timbrado Numero: $timbrado<br />
            Valido hasta:  $valido_hasta <hr />
            
                <span style='font-size:1.3em;'> Factura<br /> N&deg;: $factura_nro <br /> Id Venta: $idventa</span>
            </div>

        </div>
        ";
        $html .= "
        <div class=\"datosclientes\">
            <div class=\"cabezacentral\">
                        <br />Fecha : $ventael 
                        <br />Nombre / Raz&oacute;n Social :  $rz 
                        <br />Direcci&oacute;n : $dr 
            </div>
            <div class=\"cabezacentralizq\"> 
                        <br />Condici&oacute;n Venta: $condicion_venta $prox_vencimiento_dmy  $obs
                        <br />RUC / CI: $dct
                        <br />Tel&eacute;fono: $telf 
            </div>
        </div>
        <div style=\"border: 0px solid #000000; height: 400px;\">
            
            
            
            <div class=\"cajustadotit\">
                <div   class=\"describeproductocab\">
                    Producto
                </div>
                <div class=\"describeproducto10cab\">
                    Cantidad
                </div>
                <div   class=\"describeproducto10cab\">
                    Precio
                </div>
                <div   class=\"describeproducto10cab\">
                    Exentas
                </div>
                <div   class=\"describeproducto10cab\">
                    5%
                </div>
                <div  class=\"describeproducto10cab\">
                    10%
                </div>
                <div   class=\"describeproducto10cab\">
                    Subtotal
                </div>
            </div>
            <div class=\"cajustado\">";
        $tgiv10 = 0;
        while (!$rsdet->EOF) {
            $idprodu = trim($rsdet->fields['idprod_serial']);

            $buscar = "Select * from ventas_detalles_impuesto where idventa=$idventa and idproducto=$idprodu ";
            $rsiv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //echo $buscar;
            $iv10 = "";
            $ex = "";
            $iv5 = "";
            $tiv10 = 0;
            $tiv5 = 0;
            $tx = 0;
            while (!$rsiv->EOF) {
                if ($rsiv->fields['iva_porc_col'] == 10) {
                    $iv10 = formatomoneda($rsiv->fields['monto_col'], 0, 'S');
                    $tiv10 = $tiv10 + floatval($rsiv->fields['monto_col']);

                } else {
                    //$iv10="";

                }
                if ($rsiv->fields['iva_porc_col'] == 5) {
                    $iv5 = formatomoneda($rsiv->fields['monto_col'], 0, 'S');
                    $tiv5 = $tiv5 + floatval($rsiv->fields['monto_col']);
                } else {
                    // $iv5="";

                }
                if ($rsiv->fields['iva_porc_col'] == 0) {
                    $ex = formatomoneda($rsiv->fields['monto_col'], 0, 'S');
                    $tx = $tx + $rsiv->fields['monto_col'];
                    $texe = $texe + $rsiv->fields['monto_col'];
                } else {
                    // $ex="";

                }

                $rsiv->MoveNext();
            }
            $subt = $subt + $tx + $tiv5 + $tiv10;
            $tgiv10 = $tgiv10 + $tiv10;

            $html .= "
                            <div class=\"describeproducto\">";
            if (trim($rsdet->fields['barcode']) != '') {
                $html .= "".$rsdet->fields['barcode']." | ";
            }

            $html .= "
                                ".$rsdet->fields['producto']."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($rsdet->fields['cantidad'], 4, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($rsdet->fields['pventa'], 4, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($tx, 0, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($iv5, 0, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($tiv10, 0, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($tx + $tiv5 + $tiv10, 4, 'N')."
                            </div>";
            $rsdet->MoveNext();
        }
        $txt = num2letras($subt);
        if ($rsv->fields['observacion'] != '') {
            $observacion = "OBS: ".trim($rsv->fields['observacion']);
        }
        $html .= "
            </div>
            <div style=\"border: 1px solid #000000;  width:90%; margin-left:auto; margin-right:auto;height:20px;text-align:left;\">
                <div style=\"border: 0px solid #000000; float:left; width:50%; height:20px;text-align:left;font-size:0.7em;\">
                Total a pagar Gs: $txt <br />$observacion
                
                </div>
                <div style=\"border: 0px solid #000000; float:left; width:48%; height:20px;text-align:left;font-size:0.7em;\">
                    
                    ST:  Exenta:&nbsp; ".formatomoneda($tx, 0, 'N')."&nbsp;|&nbsp; 5%&nbsp;".formatomoneda($iv5, 0, 'N')." &nbsp;|&nbsp;10%&nbsp;".formatomoneda($tgiv10, 0, 'N')."&nbsp;|&nbsp;TG:&nbsp;".formatomoneda($subt, 0, 'N')."
            
                
                </div>
                
            </div>
            
            ";

        $buscar = "select sum(ivaml) as tiv,iva_porc_col from ventas_detalles_impuesto where idventa=$idventa and exento='N' group by iva_porc_col";
        $rslp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        while (!$rslp->EOF) {
            if (intval($rslp->fields['iva_porc_col']) == 10) {
                $vv10 = floatval($rslp->fields['tiv']);

            }
            if (intval($rslp->fields['iva_porc_col']) == 5) {
                $vv5 = floatval($rslp->fields['tiv']);

            }
            $rslp->MoveNext();
        }
        $html .= "
            <div style=\"border: 1px solid #000000; margin-left:auto; margin-right:auto; width:90%; height:20px;text-align:left;\">
                <div style=\"border: 0px solid #000000; float:left; width:30%; height:20px;text-align:left;\">
                    Liquidacion IVA
                </div>
                <div style=\"border: 0px solid #000000; float:left; width:30%; height:20px;text-align:left;\">
                    5%: ".formatomoneda($vv5, 0, 'N')."
                </div>
                <div style=\"border: 0px solid #000000; float:left; width:30%; height:20px;text-align:left;\">
                    10%: ".formatomoneda($vv10, 0, 'N')."
                </div>
            </div>
        </div>";
        $muestraleyenda_pagare = 'S';
        if ($muestraleyenda_pagare == 'S') {
            $html .= "
            <div style=\"border: 1px solid #000000;  width:90%; margin-left:auto; margin-right:auto;height:20px;text-align:left;font-size:0.6em;\">
            Recibí conforme el original de esta factura y las mercaderías en ella detalladas
    La Presente Operación fue abonada en Cheque N° .........................Cargo Banco:...................de GS.........................Dejo expresa formal constancia de que si el mismo fuera rechazada por algún motivo, el pago será considerado nulo, tomándose la obligación, impago, líquida y exigible a partir de este momento. Por lo mismo, los autorizo en forma irrevocable para que incluyan mi nombre personal o razón social que presento a la base de Datos de Informconf S.A., conforme lo establecido en la Ley 1.682, como también para que pueda proveer la información a terceros interesados.
    Cantidad que en la fecha de vencimiento arriba señalada pagaremos a $razon_social_empresa; en la ciudad de Asuncion, en su domicilio legal por igual valor recibido por conformidad en mercaderías y/o servicios. en caso contrario, se procederá en vía ejecutiva para exigir su pago. La falta de pago de esta factura a su vencimiento, constituirá en mora al deudor de pleno derecho y generará en forma automática un interés moratorio a la máxima permitida por las leyes. A todos los efectos legales y personales emergente de este documento las partes aceptan la jurisdicción y competencia de los jueces y tribunales de la ciudad de Asunción y declaran prorrogada cualquier otra que pudiera corresponder.
    El único comprobante de cancelación de la factura constituye nuestro recibo oficial.
    </div>
    ";



        }

        // Agregado de QR para facturas electronicas
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($rs_factura_electronica->fields['qr']);
        $pie_factura_electronica = '<div class="pie-factura-electronica-section"><div class="header">Consulte esta Factura electrónica con el número impreso abajo ingresando a: <br><a href="https://ekuatia.set.gov.py/consultas/">https://ekuatia.set.gov.py/consultas/</a></div><img src="'.$qrUrl.'" class="qr-code" alt="QR Code"><div class="content">'.$rs_factura_electronica->fields['cdc'].'</div><div class="footer">Si su documento electrónico presenta algún error puede solicitar la modificación dentro de las 72 horas siguientes de la emisión de este comprobante.</div></div>';

        if ($rs_preferencias_empresa->fields['facturador_electronico'] == 'S') {

            $html .= $pie_factura_electronica;

        }

        $html .= "
        </body>
        </html>
        ";


        //echo $html;exit;
        $mpdf = new mPDF('', 'A4', 0, 0, 0, 0, 0, 0);
        $mpdf->SetWatermarkText('');
        $mpdf->showWatermarkText = false;

        $mini = date('dmYHis');
        $archivopdf = "$mini$factura.pdf";
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->WriteHTML($html);
        $mpdf->showImageErrors = true;
        $mpdf->Output($archivopdf, 'I'); // mostrar en el navegador

    }

}
//de tipoa4
if ($tipo_a4 == 2) {
    //echo "a";exit;
    //echo $leyenda_credito_a4;exit;
    if (file_exists('../clases/mpdf/vendor/autoload.php')) {
        require_once '../clases/mpdf/vendor/autoload.php';
    } elseif (file_exists('clases/mpdf/vendor/autoload.php')) {
        require_once 'clases/mpdf/vendor/autoload.php';
    } else {
        throw new Exception("Archivo no encontrado en ninguna de las rutas especificadas.");
    }
    $html = '
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        
        <style>
        .cabezalcompleto {
          border: 1px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:90%;text-align:center;
          margin-left:auto;
          margin-right:auto;
        } 
        .datosclientes {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:90%;
          margin-left:auto;
          margin-right:auto;
        } 
        .cajustadotit{
            border: 1px solid #000000; margin-left:auto;margin-right:auto; width:90%; height:20px;
            
        }
        .cajustado {
            border: 1px solid #000000; margin-left:auto;margin-right:auto; width:90%; height:190px;
        }
        .cabeza1 {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:20%;
          text-align:center;
          float:left;
        } 
        .cabeza1logo {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:20%;
          text-align:center;
          float:left;
        } 
        .cabeza2 {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:50%;
          float:left;
          text-align:center;
          font-size:0.6em;
        } 
        .cabeza3 {
          border: 0px solid #A37A89; 
          height: 90px;
          border-radius: 15px;
          width:20%;
          float:left;
          text-align:center;
          font-size:0.6em;
          font-weight:bold;
        } 
        .cabezacentral {
          border: 0px solid #A37A89; 
          height: 70px;
          border-radius: 15px;
          width:50%;
          float:left;
          font-size:0.8em;
        } 
        .cabezacentralizq {
          border: 0px solid #A37A89; 
          height: 70px;
          border-radius: 15px;
          width:45%;
          float:left;
          font-size:0.8em;
        } 
        .describeproductocab{
            float:left; 
            width:39%; 
            text-align:left; 
            font-size:0.6em; 
            border-right:1px solid;
            margin:0px;
            font-weight:bold;
            font-size: 14px;
        }
        .describeproducto{
            float:left; 
            width:39%; 
            text-align:left; 
            font-size:0.6em; 
            /*border-right:1px solid;*/
            margin:0px;
        }
        .describeproducto10cab{
            float:left; 
            width:10%; 
            text-align:center; 
            font-size:0.6em;
            border-right:1px solid;
            margin:0px;
            font-weight:bold;
            font-size: 14px;
        }
        .describeproducto10{
            float:left; 
            width:10%; 
            text-align:right; 
            font-size:0.6em;
            /*border-right:1px solid;*/
            margin:0px;
        }
        table {
          width: 100%;
        }
        table, th, td {
          border-left: 1px solid;
           font-size:0.8em;
           border-collapse:collapse;
        }

        /* PIE FACTURA ELECTRONICA */

        .pie-factura-electronica-section {
            border: 1px solid #000;
            padding: 10px;
            font-family: Arial, sans-serif;
            margin-left:auto; 
            margin-right:auto; 
            width:87.5%; 
            height:10px;
            text-align:left;
            font-size:8px;
        }
        .header {
            text-align: center;
            font-size: 8px;
            font-weight: bold;
        }
        .qr-code {
            float: right;
            width: 80px;
        }
        .content {
            background-color: #d3d3d3;
            text-align: center;
            font-size: 8px;
            margin: 5px 0;
            height:5px;
            text-align: center;
            vertical-align: middle;
            line-height: 50px;   
        }
        .footer {
            text-align: center;
            font-size: 8px;
        }

        </style>
        </head>

        <body><br />
        ';

    // crea imagen
    $img = "gfx/empresas/emp_1.png";
    if (!file_exists($img)) {
        $img = "gfx/empresas/emp_1.jpg";
        if (!file_exists($img)) {
            $img = "gfx/empresas/emp_0.png";
        }
    }

    $buscar = "Select * from sucursales where muestrafactura='S' and idsucu <> $idsucursal order by nombre asc";
    $rsvv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $buscar = "Select * from sucursales where idsucu = $idsucursal ";
    $rsv1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $telfoactual = trim($rsv1->fields['telefono']);
    $direccionempresa = trim($rsv1->fields['direccion']);
    $nempresa = trim($rsv1->fields['nombre']);

    $consulta = "
    select * from empresas where idempresa = $idempresa
    ";
    $rsemp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $razon_social_empresa = trim($rsemp->fields['razon_social']);
    $ruc_empresa = trim($rsemp->fields['ruc']).'-'.trim($rsemp->fields['dv']);
    $direccion_empresa = trim($rsemp->fields['direccion']);
    $nombre_fantasia_empresa = trim($rsemp->fields['empresa']);
    $actividad_economica = trim($rsemp->fields['actividad_economica']);
    $telefono_empresa = trim($rsemp->fields['telefono']);
    $correo_empresa = strtolower(trim($rsemp->fields['email']));
    $representante_legal = trim($rsemp->fields['representante_legal']);
    $consulta = "
    INSERT INTO log_impresiones_ventas
    (idventa, impreso_por, impreso_el) 
    VALUES
    ($idventa,$idusu,'$ahora')
    ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta = "
        select *, ventas.fecha as fechaven,ventas.sucursal as idsucursal, (select count(*) from (select idprod FROM ventas_detalles where ventas_detalles.idemp = $idempresa and ventas_detalles.idventa = $idventa GROUP by idprod) as tot)  as total_detalles,
        (select numero_mesa from mesas where idmesa = ventas.idmesa) as numero_mesa,
        (select chapa from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_corto_cliente,
        (select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
        (select observacion_larga from ventas_observacion_larga where idventa = ventas.idventa) as observacion_larga,
        (select usuario from usuarios where idusu = ventas.registrado_por) as cajero,
        (select dias_credito from cliente where idcliente=ventas.idcliente) as dcredito,
        (select wifi from sucursales where sucursales.idsucu = ventas.sucursal limit 1) as wifi,
        (select telefono from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as telefono,
        (select direccion from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as direccion,
        (select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,
        (select llevapos  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as llevapos,
        (select cambio  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as cambio,
        (select cambio-(monto+delivery_costo) as vuelto  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as vuelto,
        (select observacion_delivery  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion_delivery,
        (select observacion  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as observacion,
        (select nombre_deliv  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as nombre_deliv,
        (select apellido_deliv  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as apellido_deliv,
        (select delivery_costo  from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as delivery_costo,    
        (select idtmpventares_cab as idpedido from tmp_ventares_cab where idventa = ventas.idventa order by fechahora desc limit 1) as idpedido,
        (select motorista from motoristas where ventas.idmotorista = motoristas.idmotorista) as motorista,
        (select cliente_delivery_dom.referencia from cliente_delivery_dom where iddomicilio = ventas.iddomicilio) as referencia,
        (select prox_vencimiento from cuentas_clientes where idventa = ventas.idventa) as  prox_vencimiento
        from ventas
        inner join cliente on cliente.idcliente = ventas.idcliente 
        where 
        ventas.idempresa = $idempresa 
        and ventas.idventa = $idventa
        and ventas.estado <> 6 
        limit 1
        ";
    $rsv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $consulta_fact_electronica = "SELECT qr, cdc FROM documentos_electronicos_emitidos
        WHERE idventa = $idventa
        AND estado = 1
        AND estado_set = 3
        ";
    $rs_factura_electronica = $conexion->Execute($consulta_fact_electronica) or die(errorpg($conexion, $consulta_fact_electronica));

    $consulta_preferencias = "SELECT facturador_electronico FROM preferencias WHERE idempresa = 1";
    $rs_preferencias_empresa = $conexion->Execute($consulta_preferencias) or die(errorpg($conexion, $consulta_preferencias));

    //echo $consulta;exit;
    $idventa = $rsv->fields['idventa'];
    $tipo_venta = intval($rsv->fields['tipo_venta']);
    $ocnumero = intval($rsv->fields['ocnumero']);
    $documento = intval($rsv->fields['documento']);
    $total_detalles = intval($rsv->fields['total_detalles']);
    $idpedido = intval($rsv->fields['idpedido']);
    $idtandatimbrado = intval($rsv->fields['idtandatimbrado']);
    $factura_nro = trim($rsv->fields['factura']);
    $total_factura = intval($rsv->fields['totalcobrar']);
    $total_factura_txt = num2letras($total_factura);
    $prox_vencimiento = trim($rsv->fields['prox_vencimiento']);
    if ($prox_vencimiento != '') {
        $prox_vencimiento_dmy = ' Vencimiento: '.date('d/m/Y', strtotime($prox_vencimiento));
    }
    if ($vencimiento_credito_muestra_fac == 'N') {
        $prox_vencimiento_dmy = '';
    }
    if ($rsv->fields['obs_varios'] != '') {
        $obs = "&nbsp;<br />".trim($rsv->fields['obs_varios']);
    } else {
        $obs = "";
    }
    //echo $rsv->fields['fechaven'];exit;
    $ventael = date("d/m/Y", strtotime($rsv->fields['fechaven']));
    if ($factura_nro == '') {
        echo "FACTURA NO GENERADA";
        exit;
    }
    if ($rsv->fields['finalizo_correcto'] == 'N') {
        echo "ANULAR VENTA";
        exit;
    }
    $fechahora = date("d/m/Y H:i", strtotime($rsv->fields['fechaven']));
    $idsucursal = intval($rsv->fields['idsucursal']);
    // conversion factura
    $factura_nro = str_replace("-", "", $factura_nro);
    $factura_nro = substr($factura_nro, 0, 3).'-'.substr($factura_nro, 3, 3).'-'.substr($factura_nro, 6, 7);

    if ($idtandatimbrado > 0) {
        $ahorad = date("Y-m-d", strtotime($ahora));
        $consulta = "
            SELECT * 
            FROM facturas 
            where 
            idtanda = $idtandatimbrado
            limit 1
            ";
        $rstimbrado = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        /*
        and estado = 'A'
        and valido_hasta >= '$ahorad'
        and valido_desde <= '$ahorad'
        */
        //echo $consulta;
        $timbrado = trim($rstimbrado->fields['timbrado']);
        $valido_desde = date("d/m/Y", strtotime($rstimbrado->fields['valido_desde']));
        $valido_hasta = date("d/m/Y", strtotime($rstimbrado->fields['valido_hasta']));
        $idtanda = intval($rstimbrado->fields['idtanda']);
        if ($idtanda == 0) {
            echo "Timbrado vencido o inexistente.";
            exit;
        }

    } else {

        echo "No hay timbrado activo.";
        exit;
    }
    $consulta = "
        select idventatmp, idprod_serial,
        CASE WHEN 
            ventas_detalles.pchar IS NULL
        THEN
            productos.descripcion
        ELSE    
            ventas_detalles.pchar
        END as producto,   
        ventas_detalles.pchar,
        productos.idtipoproducto,
        CASE WHEN sum(cantidad) > 0 THEN sum(subtotal)/sum(cantidad) ELSE pventa END as pventa,  
        sum(cantidad) as cantidad, 
        (sum(subtotal)-(sum(subtotal)/(1+iva/100))) as iva_monto, iva, barcode,
         sum(subtotal) as subtotal,
         max(idventadet) as idventadet,
         productos.barcode
        from ventas_detalles 
        inner join productos on productos.idprod_serial = ventas_detalles.idprod
        where 
        ventas_detalles.idventa = $idventa
        GROUP by idprod_serial, 
        CASE WHEN 
            ventas_detalles.pchar IS NULL
        THEN
            productos.descripcion
        ELSE    
            ventas_detalles.pchar
        END,
        ventas_detalles.pchar,
         iva, barcode, productos.idtipoproducto, productos.barcode
        order by max(idventadet) asc
        ";
    $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $total_items = $rsdet->RecordCount();
    // tipos de iva de la factura actual
    if ($iva_multiple == 'N') {
        $consulta = "
            select  iva as iva_porc, sum(subtotal) as subtotal_poriva, (sum(subtotal)-(sum(subtotal)/(1+iva/100))) as subtotal_monto_iva,
            CASE WHEN
                iva = 10
            THEN
                $descuento
            ELSE
                0
            END as descneto10,
            CASE WHEN
                iva = 10
            THEN
                $descuento/11
            ELSE
                0
            END as descnetoiva10
            from ventas_detalles 
            where 
            idventa = $idventa
            group by iva 
            order by iva desc
            ";
        //echo $consulta;
        $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    } else {

        $consulta = "
            select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, sum(ivaml) as subtotal_monto_iva
            from ventas_detalles_impuesto 
            where 
            idventa = $idventa
            group by iva_porc_col
            order by iva_porc_col desc
            ";
        //echo $consulta;
        $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    }

    // conversiones
    if ($tipo_venta == 2) {
        $condicion_venta = "CREDITO";
    } else {
        $condicion_venta = "CONTADO";
    }

    if (trim($rsv->fields['ruc']) != '') {
        $dct = $rsv->fields['ruc'];
    } else {
        $dct = $rsv->fields['ruc'].'-'.$rsv->fields['dv'];
    }
    if (trim($documento) != '') {
        if (intval($documento > 0)) {
            $dct .= ' / '.$documento;
        }
    }



    $rz = trim($rsv->fields['razon_social']);
    $dr = trim($rsv->fields['direccion']);
    $telf = trim($rsv->fields['telefono']);

    $dias_credito = intval($rsv->fields['dias_credito']);
    if ($dias_credito > 0) {
        $dias_credito = "Plazo: $dias_credito dias &nbsp;";
    } else {
        $dias_credito = '';
    }



    $html .= "
    <br />
        <div class=\"cabezalcompleto\">
            <div class=\"cabeza1\">
                    <img src=\"$img\" style=\"height: 200px;width:200px;\" class=\"cabeza1logo\" />
                </div>
            <div class=\"cabeza2\">
                     $nombreempresa <br />de $razon_social_empresa<br />
                    $nempresa | $direccion_empresa  | Telefono(s): $telefono_empresa<hr />
                    $actividad_economica<br />$correo_empresa";

    while (!$rsvv->EOF) {
        $html .= $rsvv->fields['nombre']." | ".$rsvv->fields['direccion']." | Telefono(s): ".$rsvv->fields['telefono']."<br />";
        $rsvv->MoveNext();
    }

    $html .= "    
            </div>
             <div class=\"cabeza3\">
             <strong>R.U.C. &nbsp; $ruc_empresa</strong><br /> 
             Timbrado Numero: $timbrado<br />
            Valido hasta:  $valido_hasta <hr />
            
                <span style='font-size:1.3em;'> Factura<br /> N&deg;: $factura_nro <br /> Id Venta: $idventa  <br /> Oc N&deg;: $ocnumero</span>
            </div>

        </div>
        ";
    $html .= "
        <div class=\"datosclientes\">
            <div class=\"cabezacentral\">
                        <br />Fecha : $ventael 
                        <br />Nombre / Raz&oacute;n Social :  $rz 
                        <br />Direcci&oacute;n : $dr 
            </div>
            <div class=\"cabezacentralizq\"> 
                        <br />Condici&oacute;n Venta: $condicion_venta $prox_vencimiento_dmy $dias_credito $obs
                        <br />RUC / CI: $dct
                        <br />Tel&eacute;fono: $telf 
            </div>
        </div>
        <div style=\"border: 0px solid #000000; height: 260px;\">
            
            
            
            <div class=\"cajustadotit\">
                <div   class=\"describeproductocab\">
                    Producto
                </div>
                <div class=\"describeproducto10cab\">
                    Cantidad
                </div>
                <div   class=\"describeproducto10cab\">
                    Precio
                </div>
                <div   class=\"describeproducto10cab\">
                    Exentas
                </div>
                <div   class=\"describeproducto10cab\">
                    5%
                </div>
                <div  class=\"describeproducto10cab\">
                    10%
                </div>
                <div   class=\"describeproducto10cab\">
                    Subtotal
                </div>
            </div>
            <div class=\"cajustado\">";
    $tgiv10 = 0;
    while (!$rsdet->EOF) {
        $idprodu = trim($rsdet->fields['idprod_serial']);

        /*$buscar="Select * from ventas_detalles_impuesto where idventa=$idventa and idproducto=$idprodu ";
                        //echo $buscar;exit;
        $rsiv=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
        //echo $buscar;
        $iv10="";$ex="";$iv5="";
        $tiv10=0;  $tiv5=0;  $tx=0;
        while (!$rsiv->EOF){
              if ($rsiv->fields['iva_porc_col']==10){
                $iv10=formatomoneda($rsiv->fields['monto_col'],0,'S');
                $tiv10=$tiv10+floatval($rsiv->fields['monto_col']);

              } else {
                   //$iv10="";

              }
               if ($rsiv->fields['iva_porc_col']==5){
                $iv5=formatomoneda($rsiv->fields['monto_col'],0,'S');
                $tiv5=$tiv5+floatval($rsiv->fields['monto_col']);
              } else {
                  // $iv5="";

              }
               if ($rsiv->fields['iva_porc_col']==0){
                $ex=formatomoneda($rsiv->fields['monto_col'],0,'S');
                $tx=$tx+$rsiv->fields['monto_col'];
                $texe=$texe+$rsiv->fields['monto_col'];
              } else {
                  // $ex="";

              }

            $rsiv->MoveNext();
        }
                        $subt=$subt+$tx+ $tiv5+ $tiv10;
                        $tgiv10=$tgiv10+$tiv10;*/
        $tiv10 = 0;
        $tiv5 = 0;
        $tx = 0;
        $iva_porc = $rsdet->fields['iva'];
        $subtotal = $rsdet->fields['subtotal'];
        if ($iva_multiple == 'S') {
            echo "Este formulario no esta preparado para iva multiple";
            exit;
            // haacer json basado en autoimpresor funciones
        } else {
            if ($iva_porc == 10) {
                $tiv10 = $subtotal;
            } else {
                $tiv10 = 0;
            }
            if ($iva_porc == 5) {
                $tiv5 = $subtotal;
            } else {
                $tiv5 = 0;
            }
            if ($iva_porc == 0) {
                $tx = $subtotal;
            } else {
                $tx = 0;
            }
        }

        $html .= "
                        <div class=\"describeproducto\">";

        if (trim($rsdet->fields['barcode']) != '') {
            $html .= "".$rsdet->fields['barcode']." | ";
        }
        $html .= $rsdet->fields['producto']." [".$rsdet->fields['idprod_serial']."]";

        $html .= "
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($rsdet->fields['cantidad'], 4, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($rsdet->fields['pventa'], 0, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($tx, 0, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($tiv5, 0, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($tiv10, 0, 'N')."
                            </div>
                            <div class=\"describeproducto10\">
                                ".formatomoneda($tx + $tiv5 + $tiv10, 4, 'N')."
                            </div>";
        $rsdet->MoveNext();
    }



    // tipos de iva de la factura actual
    $consulta = "
                select iva_porc_col as iva_porc, sum(monto_col) as subtotal_poriva, sum(ivaml) as subtotal_monto_iva
                from ventas_detalles_impuesto 
                where 
                idventa = $idventa
                group by iva_porc_col
                order by iva_porc_col desc
                ";
    //echo $consulta;
    $rsivaporc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $factura = '';
    while (!$rsivaporc->EOF) {
        if ($rsivaporc->fields['iva_porc'] > 0) {
            //$factura.='Total Grav. '.agregaespacio(floatval($rsivaporc->fields['iva_porc']).'%',3).' : '.formatomoneda($rsivaporc->fields['subtotal_poriva']-$rsivaporc->fields['descneto10'],0,'N')." | ";
        } else {
            //$factura.='Total Exenta    : '.formatomoneda($rsivaporc->fields['subtotal_poriva'],0,'N')." | ";
        }

        // tipo iva
        if ($rsivaporc->fields['iva_porc'] == 0) {
            $subtotal_ex = formatomoneda($rsivaporc->fields['subtotal_poriva']);
        }
        if ($rsivaporc->fields['iva_porc'] == 5) {
            $subtotal_5 = formatomoneda($rsivaporc->fields['subtotal_poriva']);
        }
        if ($rsivaporc->fields['iva_porc'] == 10) {
            $subtotal_10 = formatomoneda($rsivaporc->fields['subtotal_poriva']);
        }


        $rsivaporc->MoveNext();
    }

    // setear valor 0
    if (floatval($subtotal_ex) == 0) {
        $subtotal_ex = 0;
    }
    if (floatval($subtotal_5) == 0) {
        $subtotal_5 = 0;
    }
    if (floatval($subtotal_10) == 0) {
        $subtotal_10 = 0;
    }
    if ($rsv->fields['observacion'] != '') {
        $observacion = "OBS: ".trim($rsv->fields['observacion']);
    }

    $html .= "
            </div>
            
            
            
            
            <div class=\"cajustadotit\">
                <div   class=\"describeproducto\">
                    Subtotales
                </div>
                <div class=\"describeproducto10\">
                    .
                </div>
                <div   class=\"describeproducto10\">
                    .
                </div>
                <div   class=\"describeproducto10\">
                    ".$subtotal_ex."
                </div>
                <div   class=\"describeproducto10\">
                    ".$subtotal_5."
                </div>
                <div  class=\"describeproducto10\">
                    ".$subtotal_10."
                </div>
                <div   class=\"describeproducto10\">
                    ".formatomoneda($total_factura)."
                </div>
            </div>
            
            
            
            
            <div style=\"border: 1px solid #000000;  width:90%; margin-left:auto; margin-right:auto;height:20px;text-align:left;\">
                <div style=\"border: 0px solid #000000; float:left; width:80%; height:20px;text-align:left;font-size:0.7em;\">
                Total a pagar Gs: $total_factura_txt. <br />$observacion
                
                </div>
                <div style=\"border: 0px solid #000000; float:left; width:20%; height:20px;text-align:right;font-size:0.9em;font-weight:bold;\">
                    
                    ".formatomoneda($total_factura)."
            
                
                </div>
                
            </div>
            
            ";
    /*ST:  Exenta:&nbsp; ".formatomoneda($tx,0,'N')."&nbsp;|&nbsp; 5%&nbsp;".formatomoneda($iv5,0,'N')." &nbsp;|&nbsp;10%&nbsp;".formatomoneda($tgiv10,0,'N')."&nbsp;|&nbsp;TG:&nbsp;".formatomoneda($subt,0,'N')."*/
    $buscar = "select sum(ivaml) as tiv,iva_porc_col from ventas_detalles_impuesto where idventa=$idventa and exento='N' group by iva_porc_col";
    $rslp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    while (!$rslp->EOF) {
        if (intval($rslp->fields['iva_porc_col']) == 10) {
            $vv10 = floatval($rslp->fields['tiv']);

        }
        if (intval($rslp->fields['iva_porc_col']) == 5) {
            $vv5 = floatval($rslp->fields['tiv']);

        }
        $rslp->MoveNext();
    }
    $html .= "
            <div style=\"border: 1px solid #000000; margin-left:auto; margin-right:auto; width:90%; height:20px;text-align:left;font-size:10px;\">
                <div style=\"border: 0px solid #000000; float:left; width:30%; height:20px;text-align:left;\">
                    Liquidacion IVA
                </div>
                <div style=\"border: 0px solid #000000; float:left; width:20%; height:20px;text-align:left;\">
                    5%: ".formatomoneda($vv5, 0, 'N')."
                </div>
                <div style=\"border: 0px solid #000000; float:left; width:20%; height:20px;text-align:left;\">
                    10%: ".formatomoneda($vv10, 0, 'N')."
                </div>
                <div style=\"border: 0px solid #000000; float:left; width:20%; height:20px;text-align:left;\">
                    Total IVA: ".formatomoneda($vv5 + $vv10, 0, 'N')."
                </div>
            </div>
        </div>";

    if ($muestraleyenda_pagare == 'S') {
        $html .= "
            <div style=\"border: 1px solid #000000;  width:90%; margin-left:auto; margin-right:auto;height:20px;text-align:left;font-size:0.6em;\">
            Recibí conforme el original de esta factura y las mercaderías en ella detalladas
    La Presente Operación fue abonada en Cheque N° .........................Cargo Banco:...................de GS.........................Dejo expresa formal constancia de que si el mismo fuera rechazada por algún motivo, el pago será considerado nulo, tomándose la obligación, impago, líquida y exigible a partir de este momento. Por lo mismo, los autorizo en forma irrevocable para que incluyan mi nombre personal o razón social que presento a la base de Datos de Informconf S.A., conforme lo establecido en la Ley 1.682, como también para que pueda proveer la información a terceros interesados.
    Cantidad que en la fecha de vencimiento arriba señalada pagaremos $razon_social_empresa; en la ciudad de Asuncion, en su domicilio legal por igual valor recibido por conformidad en mercaderías y/o servicios. en caso contrario, se procederá en vía ejecutiva para exigir su pago. La falta de pago de esta factura a su vencimiento, constituirá en mora al deudor de pleno derecho y generará en forma automática un interés moratorio a la máxima permitida por las leyes. A todos los efectos legales y personales emergente de este documento las partes aceptan la jurisdicción y competencia de los jueces y tribunales de la ciudad de Asunción y declaran prorrogada cualquier otra que pudiera corresponder.
    El único comprobante de cancelación de la factura constituye nuestro recibo oficial.
    </div>
    ";



    }
    //echo $tipo_venta;exit;
    if ($leyenda_credito_a4 != '' && $tipo_venta == 2) {
        $html .= "<div style=\"border: 1px solid #000000;  width:90%; margin-left:auto; margin-right:auto; height:20px;text-align:left;font-size:0.6em;margin-top:-2px;\">
            $leyenda_credito_a4
            </div>";

    }
    //echo $html;exit;
    if ($leyenda_contado_a4 != '' && $tipo_venta == 1) {
        $html .= "<div style=\"border: 1px solid #000000;  width:90%; margin-left:auto; margin-right:auto; height:20px;text-align:left;font-size:0.6em;margin-top:-2px;\">
            $leyenda_contado_a4
            </div>";

    }
    //

    // Agregado de QR para facturas electronicas
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($rs_factura_electronica->fields['qr']);
    $pie_factura_electronica = '<div class="pie-factura-electronica-section"><div class="header">Consulte esta Factura electrónica con el número impreso abajo ingresando a: <a href="https://ekuatia.set.gov.py/consultas/">https://ekuatia.set.gov.py/consultas/</a></div><img src="'.$qrUrl.'" class="qr-code" alt="QR Code"><div class="content">'.$rs_factura_electronica->fields['cdc'].'</div><div class="footer">Si su documento electrónico presenta algún error puede solicitar la modificación dentro de las 72 horas siguientes de la emisión de este comprobante.</div></div>';

    $html_1 = $html.'<div style="border: 0px solid #000000; margin-left:auto; margin-right:auto; width:90%; height:20px;text-align:left; font-size:8px;">ORIGINAL: CLIENTE</div>';
    $html_2 = $html.'<div style="border: 0px solid #000000; margin-left:auto; margin-right:auto; width:90%; height:20px;text-align:left; font-size:8px;">DUPLICADO: CONTABILIDAD</div>';
    $html_3 = $html.'<div style="border: 0px solid #000000; margin-left:auto; margin-right:auto; width:90%; height:20px;text-align:left; font-size:8px;">TRIPLICADO: ARCHIVO TRIBUTARIO</div>';

    if ($rs_preferencias_empresa->fields['facturador_electronico'] == 'S') {

        $html_completo .= $html_1.$pie_factura_electronica;

    } else {

        $html_completo .= $html_1;

    }


    if ($copias_por_pagina == 0) {

        if ($total_items > 20 && $leyenda_credito_a4 != '') {
            //Si hay mas de 20 items, hace 1 por hoja
            $html_completo .= "<pagebreak>";

        }
        $html_completo .= $html_2;

        if ($rs_preferencias_empresa->fields['facturador_electronico'] == 'S') {

            $html_completo .= $pie_factura_electronica;

        }

        if ($cantidad_copias == 3) {
            $html_completo .= "<pagebreak>";
            $html_completo .= $html_3;
            $html_completo .= $pie_factura_electronica;
        }
    } else {
        //es una por hoja
        $html_completo .= "<pagebreak>";
        $html_completo .= $html_2;

        if ($rs_preferencias_empresa->fields['facturador_electronico'] == 'S') {

            $html_completo .= $pie_factura_electronica;

        }

        //$html_completo.="<pagebreak>";
        if ($cantidad_copias == 3) {
            $html_completo .= "<pagebreak>";
            $html_completo .= $html_3;

            if ($rs_preferencias_empresa->fields['facturador_electronico'] == 'S') {

                $html_completo .= $pie_factura_electronica;

            }
        }

    }




    $html_completo .= "
        </body>
        </html>
        ";


    //echo $html_completo;exit;
    $mpdf = new mPDF('', 'Legal', 0, 0, 0, 0, 0, 0);
    $mpdf->SetWatermarkText('');
    $mpdf->showWatermarkText = false;

    $mini = date('dmYHis');
    $archivopdf = "$mini$factura.pdf";
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->shrink_tables_to_fit = 1;
    $mpdf->WriteHTML($html_completo);
    $mpdf->showImageErrors = true;
    $mpdf->Output($archivopdf, 'I'); // mostrar en el navegador













}





?>
