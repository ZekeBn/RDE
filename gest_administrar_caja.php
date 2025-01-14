 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
require_once("includes/rsusuario.php");

// caja nueva
if ($rsco->fields['caja_nueva'] == 'S') {
    header("location: gest_administrar_caja_new.php");
    exit;
}



//tipo de impresora
$buscar = "Select * from impresoratk where idsucursal=$idsucursal and idempresa=$idempresa limit 1";
$rsprint = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tipoimpre = trim($rsprint->fields['tipo_impresora']);
if ($tipoimpre == 'COC') {
    $enlace = 'impresora_selecciona.php';
} else {
    $enlace = 'impresora_selecciona_caja.php';
}


// busca si hay una caja abierta por este usuario
$buscar = "
Select * 
from caja_super 
where 
estado_caja=1 
and cajero=$idusu 
and tipocaja=1
order by fecha desc 
limit 1
";
$rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaj->fields['idcaja']);
// si encuentra
if (intval($rscaj->fields['idcaja']) > 0) {
    // valida que sea la misma sucursal del cajero
    if (intval($rscaj->fields['sucursal']) != $idsucursal) {
        echo "Tu usuario tiene una caja abierta en otra sucursal, cierra primero esa caja antes de abrir otra.<br /> <a href='caja_cierre_forzar_cajero.php'>[Forzar Cierre]</a>";
        exit;
    }

    $consulta = "
        insert into caja_gestion
        (idcajaold, fecha_apertura, fechahora_apertura, estado, cajero, idsucursal, monto_apertura, monto_cierre, total_ingresos, total_egresos, faltante, sobrante, fecha_cierre, fechahora_cierre, idtipocaja)
        select $idcaja, fecha_apertura, fecha_apertura, 1, cajero, sucursal as idsucursal, monto_apertura, monto_cierre, 0 as total_ingresos, 0 as total_egresos, 0 as faltante, 0 as sobrante, NULL as fecha_cierre, NULL as fechahora_cierre, 1 as idtipocaja
        from caja_super
        where
        idcaja = $idcaja
        and idcaja not in (select idcajaold from caja_gestion where idcajaold is not null)
        ";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}




// preferencias
$consulta = "SELECT * FROM preferencias WHERE  idempresa = $idempresa ";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$obligaprov = trim($rspref->fields['obligaprov']);
$impresor = trim($rspref->fields['script_ticket']);
$hab_monto_fijo_chica = trim($rspref->fields['hab_monto_fijo_chica']);
$hab_monto_fijo_recau = trim($rspref->fields['hab_monto_fijo_recau']);
$muestraventasciega = trim($rspref->fields['muestra_ventas_ciega']);
if ($hab_monto_fijo_chica == 'S' or $hab_monto_fijo_recau == 'S') {
    // montos de caja fijos
    $consulta = "
        SELECT *
        FROM usuarios
        where
        estado = 1
        and idempresa = $idempresa
        and usuarios.idusu = $idusu
        ";
    $rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $monto_fijo_chica = intval($rsus->fields['monto_fijo_chica']);
    $monto_fijo_recau = intval($rsus->fields['monto_fijo_recau']);
}

$script_impresora = $impresor;
$impresor = strtolower($impresor);

if ($impresor == '') {
    $impresor = 'http://localhost/impresorweb/ladocliente.php';
}




if (isset($_POST['tvouchers'])) {
    $buscar = "Select idcaja from caja_super where cajero=$idusu and estado_caja=1 and tipocaja=1 order by idcaja desc limit 1";
    $rstvcaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcajat = intval($rstvcaja->fields['idcaja']);
    //echo "$idcajat";exit;
    $tv = floatval($_POST['tvouchers']);
    $insertar = "Insert into caja_vouchers(idcaja,cajero,total_vouchers,registrado_el) values ($idcajat,$idusu,$tv,current_timestamp)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

}


$operacion = intval($_POST['cual']);
if ($operacion == 1) {
    //Entrega de valores

    $idcaja = floatval($_POST['ocidcaja']);
    $dp = floatval($_POST['md']);
    $codigo = md5($_POST['codigoau']);
    $codigo = antisqlinyeccion($codigo, 'clave');

    $obs = antisqlinyeccion($_POST['obs'], 'text');
    $montoentrega = floatval($_POST['montoentrega']);

    $buscar = "Select * from usuarios_autorizaciones where codauto=$codigo and estado=1";

    $rscod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $autorizaid = intval($rscod->fields['idusu']);
    $imprimetk = trim($rscod->fields['imprimetk']);
    if ($autorizaid > 0) {
        //esta autorizado, le metemos a comparar montos
        //if ($montoentrega > $dp){
        //$errorautoriza="Monto ingresado supera al disponible. No se registra salida de dinero."    ;
        //} else {

        $consulta = "
                insert into gest_pagos
                (idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
                idtipocajamov,tipomovdinero)
                values
                ($idcaja, '$ahora', 1, $montoentrega, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
                8,'S'
                )
                ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
                select idpago from gest_pagos where idtipocajamov = 8 order by idpago desc limit 1
                ";
        $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpago = $rsultpag->fields['idpago'];

        $consulta = "
                INSERT INTO gest_pagos_det
                (idpago, monto_pago_det, idformapago) 
                VALUES 
                ($idpago, $montoentrega, 1)
                ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        //registramos
        $insertar = "Insert into caja_retiros 
                (idcaja,cajero,fecha_retiro,monto_retirado,retirado_por,codigo_autorizacion,estado,obs,idempresa,idsucursal,idpago)
                values
                ($idcaja,$idusu,current_timestamp,$montoentrega,$autorizaid,$codigo,1,$obs,$idempresa,$idsucursal,$idpago)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $buscar = "Select *,usuario from caja_retiros 
                inner join usuarios on usuarios.idusu=retirado_por 
                where cajero=$idusu order by fecha_retiro desc";
        $rsfr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idret = intval($rsfr->fields['regserialretira']);
        $totalret = intval($rsfr->fields['monto_retirado']);
        $quien = $rsfr->fields['usuario'];
        $obs = $rsfr->fields['obs'];
        if ($imprimetk == 'S') {
            $retirado = 1;
            //Preparamos el tickete
            // centrar nombre empresa
            $nombreempresa_centrado = corta_nombreempresa($nombreempresa);
            $ahorta = date("d-m-Y H:i:s", strtotime($ahora));
            $cajero1 = strtoupper($cajero);
            $texto = "
****************************************
$nombreempresa_centrado
            RETIRO DE VALORES
****************************************
RETIRO ID $idret 
----------------------------------------
FECHA Retiro    : $ahorta
Autorizado por  : $quien
Entregado por   : $cajero1
Monto Retirado  :".formatomoneda($totalret)."
----------------------------------------


Firma Entregado:


Firma Recibido:


$obs
----------------------------------------
";
        } else {
            //No se imprime tickete entrega
            $retirado = 0;
        }





        //}

    } else {
        $errorautoriza = "C&oacute;digo de autorizaci&oacute;n inv&aacute;lido. No se registra salida de dinero."    ;

    }
}
if ($operacion == 2) {

    //reposicion de valores
    $idcaja = floatval($_POST['ocidcaja']);
    $dp = floatval($_POST['md']);
    $codigo = md5($_POST['codigoaure']);
    $codigo = antisqlinyeccion($codigo, 'clave');
    //$codigo=antisqlinyeccion($_POST[''],'text');
    $obs = antisqlinyeccion($_POST['obs2'], 'text');
    $montorecibe = floatval($_POST['montorecibe']);

    $buscar = "Select * from usuarios_autorizaciones where codauto=$codigo";
    $rscod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $autorizaid = intval($rscod->fields['idusu']);
    if ($autorizaid > 0) {

        $consulta = "
            insert into gest_pagos
            (idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
            idtipocajamov,tipomovdinero)
            values
            ($idcaja, '$ahora', 1, $montorecibe, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
            7,'E'
            )
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
            select idpago from gest_pagos where idtipocajamov = 7 order by idpago desc limit 1
            ";
        $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpago = $rsultpag->fields['idpago'];

        $consulta = "
            INSERT INTO gest_pagos_det
            (idpago, monto_pago_det, idformapago) 
            VALUES 
            ($idpago, $montorecibe, 1)
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //registramos
        $insertar = "Insert into caja_reposiciones 
            (idcaja,cajero,fecha_reposicion,monto_recibido,entregado_por,codigo_autorizacion,estado,obs,idempresa,idsucursal,idpago)
            values
            ($idcaja,$idusu,current_timestamp,$montorecibe,$autorizaid,$codigo,1,$obs,$idempresa,$idsucursal,$idpago)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));



    } else {
        $errorautorizav = "C&oacute;digo de autorizaci&oacute;n inv&aacute;lido. No se registra ingreso de dinero."    ;


    }

}
if ($operacion == 5) {
    //Insertamos pago x caja

    $idco = intval($_POST['ocidcajac']);
    $montoabonado = floatval($_POST['montopagoc']);
    $concepto = antisqlinyeccion($_POST['obspago'], 'text');
    $factu = antisqlinyeccion($_POST['nfactu'], 'text');
    $idprovi = intval($_POST['minip']);

    $tipocaja = strtoupper(substr(trim($_POST['tipocajapag']), 0, 1));

    // validaciones de tipo de caja
    // si usa solo caja chica
    if ($rspref->fields['pagoxcaja_chic'] == 'S' && $rspref->fields['pagoxcaja_rec'] == 'N') {
        $tipocaja = "C";
    }
    // si usa solo caja recaudacion
    if ($rspref->fields['pagoxcaja_chic'] == 'N' && $rspref->fields['pagoxcaja_rec'] == 'S') {
        $tipocaja = "R";
    }
    // si usa ambas
    if ($rspref->fields['pagoxcaja_chic'] == 'S' && $rspref->fields['pagoxcaja_rec'] == 'S') {
        // evita hack
        if ($tipocaja != 'R' && $tipocaja != 'C') {
            $tipocaja = "R";
        }
    }
    // si no tiene habilitado ninguno
    if ($rspref->fields['pagoxcaja_chic'] == 'N' && $rspref->fields['pagoxcaja_rec'] == 'N') {
        echo "No tienes permisos para pagos por caja.";
        exit;
    }

    $errores = '';
    if ($montoabonado == 0) {
        $errores = $errores.'* Debe indicar monto abonado. \n';
    }
    if ($concepto == 'NULL') {
        $errores = $errores.'* Debe indicar motivo del pago. \n';
    }
    if (($obligaprov == 'S') && ($idprovi == 0)) {
        $errores = $errores.'* Debe indicar proveedor de factura. \n';

    }
    if ($errores == '') {

        $consulta = "
            insert into gest_pagos
            (idcaja, fecha, medio_pago, total_cobrado,  estado, tipo_pago, idempresa, sucursal, cajero, fechareal, idventa, 
            idtipocajamov,tipomovdinero)
            values
            ($idcaja, '$ahora', 1, $montoabonado, 1, 0, 1, $idsucursal, $idusu, '$ahora', 0, 
            9,'S'
            )
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
            select idpago from gest_pagos where idtipocajamov = 9 order by idpago desc limit 1
            ";
        $rsultpag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpago = $rsultpag->fields['idpago'];

        $consulta = "
            INSERT INTO gest_pagos_det
            (idpago, monto_pago_det, idformapago) 
            VALUES 
            ($idpago, $montoabonado, 1)
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


        $insertar = "Insert  into pagos_extra 
            (fecha,idcaja,monto_abonado,concepto,idusu,factura,idempresa,idprov,estado,tipocaja,idpago)
            values
            ('$ahora',$idco,$montoabonado,$concepto,$idusu,$factu,$idempresa,$idprovi,1,'$tipocaja',$idpago)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        //echo $insertar;exit;

        $consulta = "
            select max(unis) as unis from pagos_extra where idusu = $idusu
            ";
        $rspag = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idpagoex = $rspag->fields['unis'];


        header("location: inf_pagosxcaja_imp.php?id=$idpagoex&redir=2");
        exit;

    } else {

        $war = 1;

    }





}







// centrar nombre de empresa
$nombreempresa_centrado = corta_nombreempresa($nombreempresa);
$loca = intval($_GET['lo']);
if ($loca == 1) {
    //Toma pedido por caja
    $enla = "gest_ventas_resto_caja.php";

}
if ($loca == 2) {
    //Toma pedido mod 2
    $enla = "gest_ventas_resto.php";

}
/*--------------------------------------------ESTADOS DE CAJA---------------------------------------*/
// si se envia fecha
if (isset($_POST['fecha']) && ($_POST['fecha'] != '')) {
    $fecha = antisqlinyeccion($_POST['fecha'], 'date');
    $fechahoy = str_replace("'", "", $fecha);
    $buscar = "Select * from caja_super where cajero=$idusu and estado_caja=1 and fecha=$fecha and tipocaja=1";
    $rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $idcaja = intval($rscaja->fields['idcaja']);
    $estadocaja = intval($rscaja->fields['estado_caja']);

    // si no se envia fecha, se busca la ultima caja abierta
} else {
    //vemos si hay una caja abierta
    //Buscamos datos de la ultima caja abierta
    $buscar = "Select * from caja_super where cajero=$idusu and estado_caja=1 and tipocaja=1 order by fecha desc limit 1";
    $rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcaja = intval($rscaja->fields['idcaja']);
    $estadocaja = intval($rscaja->fields['estado_caja']);
    $fechahoy = date("Y-m-d", strtotime($rscaja->fields['fecha']));

}
// monto de apertura de caja sirve para el cierre
$montobre = floatval($rscaja->fields['monto_apertura']);


if ($idcaja == 0) {
    $abrircaja = 1;
    $cerrarcaja = 0;
} else {
    if ($estadocaja == 1) {
        $abrircaja = 0;
        $cerrarcaja = 1;
    }
    if ($estadocaja == 3) {
        //caja ya cerrada
        $abrircaja = 0;
        $cerrarcaja = 0;
    }
}
if ($idcaja == 0) {
    //No existe un registro para la caja, por lo cual
    //Fecha Actual
    $fechahoy = date("Y-m-d");
    if (isset($_POST['fecha']) && ($_POST['fecha'] != '')) {
        $fecha = antisqlinyeccion($_POST['fecha'], 'date');
        $fechahoy = str_replace("'", "", $fecha);
    }


} else {
    //ya existe una caja,


}


/*--------------------- INICIO APERTURA DE CAJA -------------------------------------------------*/
require_once("caja_abrir.php");
/*--------------------- FIN APERTURA DE CAJA -------------------------------------------------*/

/*--------------------- INICIO CIERRE DE CAJA -------------------------------------------------*/
require_once("caja_cerrar.php");
/*--------------------- FIN CIERRE DE CAJA -------------------------------------------------*/

//traemos las facturas anuladas
$buscar = "Select numero,motivo,fechahora,usuario from facturas_anuladas
inner join usuarios on usuarios.idusu=facturas_anuladas.cajero";
$rsfal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));









?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>

<script>
<?php if ($cerrado == 1) {?>
function cerrado(){
var loadi = '';
    var central=<?php echo $central?>;
    var quien='<?php echo "$cajero" ?>';
    var fechahora=<?php echo "'$fechahora'" ?>;
    var fechaapertura=<?php echo "'$fechaapertura'" ?>;
    var apertura=<?php echo $montoapertura ?>;
    var sobra=<?php echo $sobrantes ?>;
    var faltante=<?php echo $faltantes ?>;
    var totalefe=<?php echo $totalefectivo ?>;
    var tarjetas=<?php echo $totaltarjeta ?>;
    var tpagosdia=<?php echo $totalpagosdia ?>;
    var tcobranza=<?php echo $totalcobrosdia ?>;
    var entregas=<?php echo $totalentregags ?>;
    var repo=<?php echo $totalreposicionesgs ?>;
    var idcaja=<?php echo $idcaja?>;
    
    var enlace='<?php echo $impresor?>';
    
    var parametros='central='+central+'&quien='+quien+'&fechahora='+fechahora+'&apertura='+apertura+'&sobra='+sobra+'&falta='+faltante+'&efectivo='+totalefe+'&tarjetas='+tarjetas+'&pagosxcaja='+tpagosdia+'&tcobranza='+tcobranza+'&entregas='+entregas+'&repo='+repo+'&idcaja='+idcaja+'&fechacaja='+fechaapertura;
    OpenPage(enlace,parametros,'POST','impresion',loadi);
    
}

<?php } ?>
function agregabb(){
    var billete=document.getElementById('billeton').value;
    var cantidad=document.getElementById('ofg').value;
    var sidc=<?php echo $idcaja?>;
    var parametros='idcaja='+sidc+'&billete='+billete+'&canti='+cantidad;
    OpenPage('ar_billetes.php',parametros,'POST','billetitos','pred');
    
    
}
function agregabbm(){
    var moneda=document.getElementById('moneda').value;
    var canti=document.getElementById('cantimoneda').value;
    var coti=document.getElementById('coti').value;
    var sidc=<?php echo $idcaja?>;
    var parametros='idcaja='+sidc+'&moneda='+moneda+'&canti='+canti+'&coti='+coti+'&mo=1';
    OpenPage('ar_billetes.php',parametros,'POST','billetitos','pred');
    
    
}
function chv(unicass){
    var eliminavou=unicass;
    var sidc=<?php echo $idcaja?>;
    var parametros='idcaja='+sidc+'&voucher='+eliminavou;
    OpenPage('ar_billetes.php',parametros,'POST','billetitos','pred');
    
    
}    
function chb(valor){
        var sidc=<?php echo $idcaja?>;
      if (valor!=''){
          var parametros='idcaja='+sidc+'&chau='+valor;
        OpenPage('ar_billetes.php',parametros,'POST','billetitos','pred');
      
      }
  }
  function chb2(valor){
        var sidc=<?php echo $idcaja?>;
      if (valor!=''){
          var parametros='idcaja='+sidc+'&chacu='+valor;
        OpenPage('ar_billetes.php',parametros,'POST','billetitos','pred');
      
      }
  }
function recibirvalor(){
    var errores='';
    var montorecibe=document.getElementById('montorecibe').value;
    var codigoauto=document.getElementById('codigoaure').value;
    if (montorecibe==''){
        errores=errores+'Debe indicar monto a recibir. \n';
    } else {
        if (montorecibe==0){
            errores=errores+'Debe indicar monto a recibir. \n';
        } 
    }
    if (codigoauto==''){
        errores=errores+'Debe ingresar clave para autorizar \n';
    }
    if (errores!=''){
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
    } else {
        document.getElementById('recibeval').submit();
        
    }
    
    
}
function sacarvalor(){
    var errores='';
    var montoentregar=document.getElementById('montoentrega').value;
    var codigoauto=document.getElementById('codigoau').value;
    if (montoentregar==''){
        errores=errores+'Debe indicar monto a entregar. \n';
    } else {
        if (montoentregar==0){
            errores=errores+'Debe indicar monto a entregar. \n';
        } else {
            var disponible=parseInt(document.getElementById('montodispo').value);
            /*if (montoentregar > disponible){
                errores=errores+'No dispone del valor ingresado para entregar. \n';
                
            }*/
        }
    }
    if (codigoauto==''){
        errores=errores+'Debe ingresar clave para autorizar \n';
    }
    if (errores!=''){
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
    } else {
        document.getElementById('entregaval').submit();
        
    }
    
}
function chaucaja(){
swal({
    title: "Esta seguro?",
    text: "La caja no podra ser abierta nuevamente!",
    type: "warning",
    showCancelButton: true,
    confirmButtonColor: '#DD6B55',
    confirmButtonText: 'Si, cerrar caja.',
    cancelButtonText: "No, esperar mas.",
    closeOnConfirm: false,
    closeOnCancel: false
 },
 function(isConfirm){

   if (isConfirm){
           var caja_chica_cierre = document.getElementById('caja_chica_cierre_tmp').value;
        document.getElementById('caja_chica_cierre').value=caja_chica_cierre;
        var caja_chica_cierre_new = document.getElementById('caja_chica_cierre').value;
        if(caja_chica_cierre_new != ''){
            document.getElementById('cerrarcaja').submit();
        }else{
            swal("Error", "No indico el monto de cierre de la caja chica.", "error");
        }
    } else {
      swal("Cancelado", "La caja continua abierta", "error");
    }
 });    
    
    
    
    
}
<?php if ($retirado == 1) {?>
function imprime_retiro(){
    
    
        var texto = document.getElementById("texto").value;
        
        var parametros = {
                "tk" : texto
        };
       $.ajax({
                data:  parametros,
                url:   '<?php echo $script_impresora; ?>',
                type:  'post',
                dataType: 'html',
                beforeSend: function () {
                        $("#impresion_box").html("Enviando Impresion...");
                },
                crossDomain: true,
                success:  function (response) {
                        $("#impresion_box").html(response);    
                        
                            
                }
        });
    
    
    
}    
    
<?php } ?>
function confirmado(cual){
    if (cual==1){
         swal("Listo!", "Caja cerrada Correctamente", "success");    
    }
}
function pagarmini(){
    var obligaprov='<?php echo $obligaprov?>';
    var provee=document.getElementById('minip').value;
    var factura=document.getElementById('nfactu').value;
    var obs=document.getElementById('obspago').value;
    var montopaga=document.getElementById('montopagoc').value;
    var caj=document.getElementById('tipocajapag').value;
    var errores='';
    if ((obligaprov=='S')&& (provee=='0')){
        errores=errores+'Debe indicar proveedor para pago de factura.\n';
    }
    if (factura==''){
        errores=errores+'Debe indicar factura para registrar pago.\n';
    }
    if (montopaga=='' || montopaga=='0'){
        errores=errores+'Debe indicar monto abonado.\n';
    }
    if ((obs=='')){
        errores=errores+'Debe indicar motivo del  pago.\n';
    }
    if (errores==''){
        $("#registrapagocaj").hide();
        document.getElementById('pagochi').submit();
    } else {
    alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
        
    }
    
        
        
}
<?php if ($imprimir == 1) {?>
function imprimir(){
    var enlace='<?php echo $impresor?>';
    var parametros='tk='+document.getElementById('tickete').value;
    
    OpenPage(enlace,parametros,'POST','impresion','pred');
    setTimeout(function(){ document.body.innerHTML='<meta http-equiv="refresh" content="0; url=gest_administrar_caja.php" />'; }, 200);
}
<?php }?>
</script>
<script src="js/sweetalert.min.js"></script>
 <link rel="stylesheet" type="text/css" href="css/sweetalert.css">
 <script>
 function refa(){
        document.getElementById('rf').submit(); 
     
 }
 function alertar(titulo,error,tipo,boton){
    swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
    }
 </script>
 <script src="js/jquery-1.9.1.js"></script>
 <!--  Esto modificar para que el logo de cabecera no se deforme -->
<!--  <link rel="stylesheet" href="css/main.css"> -->
<!-- <link rel="stylesheet" href="css/docs.css"> -->
<style>
/*----- Tabs -----*/
.tabs {
    width:100%;
    display:inline-block;
}
 
    /*----- Tab Links -----*/
    /* Clearfix */
    .tab-links:after {
        display:block;
        clear:both;
        content:'';
    }
 
    .tab-links li {
        margin:0px 5px;
        float:left;
        list-style:none;
    }
 
        .tab-links a {
            padding:9px 15px;
            display:inline-block;
            border-radius:3px 3px 0px 0px;
            background:#7FB5DA;
            font-size:16px;
            font-weight:600;
            color:#4c4c4c;
            transition:all linear 0.15s;
        }
 
        .tab-links a:hover {
            background:#FFFFFF;
            text-decoration:none;
        }
 
    li.active a, li.active a:hover {
        background:#fff;
        color:#4c4c4c;
    }
 
    /*----- Content of Tabs -----*/
    .tab-content {
        padding:15px;
        border-radius:3px;
        box-shadow:-1px 1px 1px rgba(0,0,0,0.15);
        background:#fff;
    }
 
        .tab {
            display:none;
        }
 
        .tab.active {
            display:block;
        }
        .montoscajaap{
            width:80px;    
        }
        
</style>
<script>
function recibirvalor(){
    var errores='';
    var montorecibe=document.getElementById('montorecibe').value;
    var codigoauto=document.getElementById('codigoaure').value;
    if (montorecibe==''){
        errores=errores+'Debe indicar monto a recibir. \n';
    } else {
        if (montorecibe==0){
            errores=errores+'Debe indicar monto a recibir. \n';
        } 
    }
    if (codigoauto==''){
        errores=errores+'Debe ingresar clave para autorizar \n';
    }
    if (errores!=''){
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
    } else {
        $("#recibevalcaj").hide();
        document.getElementById('recibeval').submit();
        
    }
    
    
}
function sacarvalor(){
    var errores='';
    var montoentregar=document.getElementById('montoentrega').value;
    var codigoauto=document.getElementById('codigoau').value;
    if (montoentregar==''){
        errores=errores+'Debe indicar monto a entregar. \n';
    } else {
        if (montoentregar==0){
            errores=errores+'Debe indicar monto a entregar. \n';
        } else {
            var disponible=parseInt(document.getElementById('md').value);
            /*if (montoentregar > disponible){
                errores=errores+'No dispone del valor ingresado para entregar. \n';
                
            }*/
        }
    }
    if (codigoauto==''){
        errores=errores+'Debe ingresar clave para autorizar \n';
    }
    if (errores!=''){
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
    } else {
        $("#sacavalcaj").hide();
        document.getElementById('entregaval').submit();
        
    }
    
}
  
  jQuery(document).ready(function() {
    jQuery('.tabs .tab-links a').on('click', function(e)  {
        var currentAttrValue = jQuery(this).attr('href');
 
        // Show/Hide Tabs
        jQuery('.tabs ' + currentAttrValue).show().siblings().hide();
 
        // Change/remove current tab to active
        jQuery(this).parent('li').addClass('active').siblings().removeClass('active');
 
        e.preventDefault();
    });
});

function carga_cotizacion(idmoneda){
    var direccionurl='cotizacion_datos.php';    
    var parametros = {
      "idmoneda" : idmoneda
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#coti").val('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#coti").val(response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexi�n.');
            }else{
                alert(jqXHR.status+' '+errorThrown);
            }
        }
        
        
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        
        if (jqXHR.status === 0) {
    
            alert('No conectado: verifique la red.');
        
        } else if (jqXHR.status == 404) {
        
            alert('Pagina no encontrada [404]');
        
        } else if (jqXHR.status == 500) {
        
            alert('Internal Server Error [500].');
        
        } else if (textStatus === 'parsererror') {
        
            alert('Requested JSON parse failed.');
        
        } else if (textStatus === 'timeout') {
        
            alert('Tiempo de espera agotado, time out error.');
        
        } else if (textStatus === 'abort') {
        
            alert('Solicitud ajax abortada.'); // Ajax request aborted.
        
        } else {
        
            alert('Uncaught Error: ' + jqXHR.responseText);
        
        }
        
    });
}
</script>
<?php require("includes/head.php"); ?>
</head>
<body bgcolor="#FFFFFF" onLoad="<?php if (intval($imprimir) == 1) {?> imprimir();<?php } ?><?php if ($cerrado == 1) {?>cerrado();<?php }?><?php if ($retirado == 1) {?>imprime_retiro();<?php } ?>">
<?php require("includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">
     
    <?php if (intval($imprimir) == 1) {?>
<textarea style="display:none;" id="tickete"><?php echo $tickete; ?></textarea>
<?php }?>
    <div id="impresion">
    
    </div>
    
    <div class="colcompleto" id="contenedor" style="min-height:1100px;">
    <div align="center">
    <table width="100" border="0">
  <tbody>
    <tr>
    <?php ?>
      <td align="center" valign="middle"><a href="<?php echo $enla?>"><img src="img/homeblue.png" width="64" height="64" title="Regresar" /></a></td>
      <td align="center" valign="middle"><a href="<?php echo $enlace?>" target="_blank"><img src="img/1495739936_printer.png" width="48" height="48" alt=""/></a></td>
    </tr>
  </tbody>
</table>
    </div>
    
    
             <div class="resumenmini">
                        <br />  
               <strong><strong>Cajero:</strong> <span class="resaltarojomini"><?php echo $cajero; ?>&nbsp;&nbsp;<?php echo "Idcaja: ".$idcaja; ?></span></strong>  
                 <br /><br />
                 <div align="center">
                 <?php if ($errorfecha == 2) {?>
                     <span class="resaltarojomini">No se permite abrir caja de dias anteriores. <br />Seleccionado: <?php echo date("d-m-Y", strtotime($fechaek)); ?> </span>
                 <?php }?>
                 </div>
                 <?php /* ?><form id="bfecha" name="abrecaja" action="gest_administrar_caja.php" method="post">
                   <input type="date" name="fecha"  id="fecha" value="<?php echo $fechahoy ?>" min="<?php echo $ahora; ?>" />
                    <input type="submit" name="abp" value="Buscar Caja" />
                   </form><?php */ ?>
               <?php if ($idcaja == 0) {?>           
                  
<?php
    // montos de caja fijos
    $consulta = "
    SELECT *
    FROM usuarios
    where
    estado = 1
    and idempresa = $idempresa
    and usuarios.idusu = $idusu
    ";
                   $rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                   $monto_fijo_chica = intval($rsus->fields['monto_fijo_chica']);
                   $monto_fijo_recau = intval($rsus->fields['monto_fijo_recau']);

                   if ($hab_monto_fijo_recau != 'S') {
                       $monto_fijo_recau = 0;
                   }
                   if ($hab_monto_fijo_chica != 'S') {
                       $monto_fijo_chica = 0;
                   }


                   ?>
               <form id="abrecaja" name="abrecaja" action="gest_administrar_caja.php" method="post">
               <table width="400px">
                       <tr>
                        <td align="center" bgcolor="#C2F3F0"><strong>Fecha Apertura</strong></td>
                        <td height="31" align="center" bgcolor="#C2F3F0"><strong>Monto Inicial</strong></td>
                         <?php if ($rspref->fields['usa_cajachica'] == 'S') { ?>
                        <td align="center" bgcolor="#C2F3F0"><strong>Caja Chica</strong></td>
                        <?php } ?>
                        <td align="center" bgcolor="#C2F3F0"><strong>Acci&oacute;n</strong></td>
                    </tr>
                    <tr>
                        <td align="center"><?php echo date("d/m/Y", strtotime($fechahoy)); ?></td>
                         <td align="center"><input type="text" id="montoaper" name="montoaper" class="montoscajaap" required="required" onkeypress="return validar(event,'numero')"  onchange="this.value = get_numbers(this.value)" value="<?php echo $monto_fijo_recau; ?>" <?php if ($hab_monto_fijo_recau == 'S') { ?>readonly="readonly" style="background-color:#CCC; border:#FFFFFF; text-align:right; display:none;"<?php } ?>  /><?php
                                         if ($hab_monto_fijo_recau == 'S') {
                                             echo formatomoneda($monto_fijo_recau, 2, 'N');
                                         }
                   ?></td>
                      <?php if ($rspref->fields['usa_cajachica'] == 'S') { ?>
                         <td><input type="text" id="cajachica" name="cajachica" class="montoscajaap" required="required" onkeypress="return validar(event,'numero')"  onchange="this.value = get_numbers(this.value)" value="<?php echo $monto_fijo_chica; ?>" <?php if ($hab_monto_fijo_chica == 'S') { ?>readonly="readonly" style="background-color:#CCC; border:#FFFFFF; text-align:right;  display:none;"<?php } ?> /><?php
                   if ($hab_monto_fijo_chica == 'S') {
                       echo formatomoneda($monto_fijo_chica, 2, 'N');
                   }
                          ?></td>
                      <?php } ?>
                        <td> <input type="submit" name="ab" value="Abrir Caja" /></td>
                    </tr>
               
               </table>
                    <input type="hidden" name="abrirm"  id="abrirm" value="<?php echo $abre?>" />
                    <input type="hidden" name="abrir" id="abrir" value="1" />
                    <input type="hidden" name="selefe" id="selefe" value="<?php echo $fechahoy ?>" />
                   
                  </form><br />
               <?php } ?>
               <?php if ($idcaja > 0) {?>
               
                       <?php if ($estadocaja == 1) { ?>
                     <form id="cerrarcaja" name="cerrarcaja" action="gest_administrar_caja.php" method="post">
                    <input type="hidden" name="ocidcaja" id="ocidcaja" value="<?php echo $idcaja?>" />
                      <input type="hidden" name="cual" id="cual" value="3" />
                      <input type="hidden" name="selefe" id="selefe" value="<?php echo $fechahoy ?>" />
                      <input type="hidden" name="caja_chica_cierre" id="caja_chica_cierre" value="" />
                    <input type="button" name="cv" value="Cerrar Caja" onclick="chaucaja()" />
                    </form><br />
                    <?php }?>
               
               <?php } ?>
                </div>
               <div class="main" id="main">
                <div class="container" style="height:auto;">
                 <div align="center"><br />
                 <?php /*if ($idcaja > 0 && $estadocaja == 1){ ?><br />
                 <div style="text-align:center; font-weight:bold; cursor:pointer; width:180px; margin:0px auto;" onmouseup="document.location.href='<?php echo $enla?>';">

                 <img src="img/ventas.png" alt="" width="96" height="96"/><br /><br />Ir al M&oacute;dulo de Ventas </div>
                 <?php }*/ ?>
                <hr /> 
 <div class="divstd">
 <span class="resaltaditomenor">  
 ESTE MODDULO SERA BORRADO MUY PRONTO Y REEMPLAZADO POR UNO MEJOR.     <BR />  
LE INVITAMOS A VER EL NUEVO MODULO DE CAJA MEJORADO!!!
</span><BR /> 
<BR /> <a href="gest_administrar_caja_new.php">
>> [VER MODULO NUEVO] << 
</a><BR /> <BR /> </div>
                    <!----------------TABS----------------->
                       <div align="center"> <br />
                     <hr />
                           <div class="tabs">
                            <ul class="tab-links">
                                <?php if ($tipocaja == 'V') { ?><li class="active"><a href="#tab1">Resumen</a></li><?php } ?>
                                <li><a href="#tab2" <?php if ($tipocaja == 'C') { ?>class="active"<?php } ?>>Operaciones</a></li>
                                <?php if ($tipocaja == 'V') { ?><li><a href="#tab3">Ventas</a></li>
                                <li><a href="#tab4">Pagos</a></li><?php } ?>
                                
                            </ul>
                            <!------------------------------CONTENIDO TABS---------------------------------->
                            <div class="tab-content">
                                <?php if ($tipocaja == 'V') { ?>
                                <div id="tab1" class="tab active">
                                     <?php require_once("rs_cajamini.php");?>
                                    <div class="clear"></div>
                                </div>
                                <?php } ?>
                                 <div id="tab2" <?php if ($tipocaja == 'C') { ?>class="tab active"<?php } else { ?> class="tab" <?php } ?>>
                                 <?php if ($estadocaja == 1) {?>
                                       <?php require_once("rs_operaciones.php");?>
                                    <?php } else {?>
                                    <span class="resaltarojomini">
                                        La caja se encuentra cerrada. no se permiten modificar / agregar operaciones
                                    </span>
                                    <?php } ?>
                                     <div class="clear"></div>
                                </div>
                                <?php if ($tipocaja == 'V') { ?>
                                <div id="tab3" class="tab">
                                 <?php if ($estadocaja == 1) {?>
                                       <?php require_once("rs_caja_ventas.php");?>
                                    <?php } else {?>
                                    <span class="resaltarojomini">
                                        La caja se encuentra cerrada. no se permiten modificar / agregar operaciones
                                    </span>
                                    <?php } ?>
                                     <div class="clear"></div>
                                </div>
                                 <div id="tab4" class="tab">
                                 <?php if ($estadocaja == 1) {?>
                                       <?php require_once("rs_caja_pagos.php");?>
                                    <?php } else {?>
                                    <span class="resaltarojomini">
                                        La caja se encuentra cerrada. no se permiten modificar / agregar operaciones
                                    </span>
                                    <?php } ?>
                                     <div class="clear"></div>
                                </div>
                               <?php } ?>
                                
                            </div>
                            <!-------------------TAB CONTENT------------------>
                        </div>
                    </div>
       
                
                </div><!-- /CONTAINER -->
            </div><!-- /MAIN -->
     </div>
     <script>
  function openCity(evt, cityName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i <= tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tablinks");
            for (i = 0; i <= tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(cityName).style.display = "block";
            evt.currentTarget.className += " active";
 }
        $(document).ready(function(){
            var estado = false;
         });
       </script>    
        <div  id="impresion_box" hidden="hidden"><textarea readonly id="texto" style="display:; width:310px; height:500px;" ><?php echo $texto; ?></textarea></div><br />
      <div class="clear"></div><!-- clear1 -->
     </div> <!-- contenedor -->
     <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>
