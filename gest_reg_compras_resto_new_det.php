<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "31";
require_once("includes/rsusuario.php");
require_once("includes/funciones_iva.php");
require_once("includes/funciones_compras.php");

$idtransaccion = intval($_GET['id']);
if ($idtransaccion == 0) {
    $idtransaccion = intval($_POST['idt']);
    if ($idtransaccion == 0) {
        header("location: gest_reg_compras_resto_new.php");
        exit;
    }
}
$idt = $idtransaccion;
/*
rellenar compras retroactivo
INSERT INTO `facturas_proveedores_det_impuesto`
( `id_factura`, `codreferdet`, `iva_porc_col`, `monto_col`, `gravadoml`, `ivaml`, `exento`, idtipoiva)

select
facturas_proveedores.id_factura, facturas_proveedores_compras.pkss, facturas_proveedores_compras.iva_porc,
facturas_proveedores_compras.subtotal as montocol, facturas_proveedores_compras.subtotal-facturas_proveedores_compras.monto_iva as gravadoml,
facturas_proveedores_compras.monto_iva,
CASE WHEN facturas_proveedores_compras.iva_porc = 0 THEN 'S' ELSE 'N' END as exento,
(select idtipoiva from tipo_iva where idtipoiva < 4 and tipo_iva.iva_porc =  facturas_proveedores_compras.iva_porc) as idtipoiva
from facturas_proveedores
inner join facturas_proveedores_compras on facturas_proveedores_compras.id_factura = facturas_proveedores.id_factura
WHERE
facturas_proveedores.idcompra > 0
and facturas_proveedores.estado <> 6
and facturas_proveedores_compras.estado <> 6
and facturas_proveedores_compras.pkss  not in (
    select facturas_proveedores_det_impuesto.codreferdet
    from facturas_proveedores_det_impuesto
    inner join facturas_proveedores on facturas_proveedores.id_factura = facturas_proveedores_det_impuesto.id_factura
    where
    facturas_proveedores.idcompra > 0
    and facturas_proveedores.estado <> 6

)

rellenar gastos retroactivo

INSERT INTO `facturas_proveedores_det_impuesto`
( `id_factura`, `codreferdet`, `iva_porc_col`, `monto_col`, `gravadoml`, `ivaml`, `exento`, idtipoiva)

select
facturas_proveedores.id_factura, facturas_proveedores_gastos.unss, facturas_proveedores_gastos.iva_porc,
facturas_proveedores_gastos.monto_gasto as montocol, facturas_proveedores_gastos.monto_gasto-facturas_proveedores_gastos.monto_iva as gravadoml,
facturas_proveedores_gastos.monto_iva,
CASE WHEN facturas_proveedores_gastos.iva_porc = 0 THEN 'S' ELSE 'N' END as excento,
(select idtipoiva from tipo_iva where idtipoiva < 4 and tipo_iva.iva_porc =  facturas_proveedores_gastos.iva_porc) as idtipoiva
from facturas_proveedores
inner join facturas_proveedores_gastos on facturas_proveedores_gastos.id_factura = facturas_proveedores.id_factura
WHERE
facturas_proveedores.idgasto > 0
and facturas_proveedores.estado <> 6
and facturas_proveedores.estado_carga = 3
and facturas_proveedores_gastos.estado <> 6
and facturas_proveedores_gastos.unss  not in (
    select facturas_proveedores_det_impuesto.codreferdet
    from facturas_proveedores_det_impuesto
    inner join facturas_proveedores on facturas_proveedores.id_factura = facturas_proveedores_det_impuesto.id_factura
    where
    facturas_proveedores.idgasto > 0
    and facturas_proveedores.estado <> 6

)

*/

//Tipo de compra por defecto
$buscar = "select tipocompra from preferencias where idempresa=$idempresa";
$rstc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tipoc = intval($rstc->fields['tipocompra']);
//echo $tipoc;










// fechas habilitadas para compras
$consulta = "
	select *
	from compras_habilita
	where
	idempresa = $idempresa
	and estado = 1
	and idtipotransaccion = 1
	order by idcomprahab desc
	limit 1
	";
$rscomprahab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$fechadesdebd = $rscomprahab->fields['fechadesde'];
$fechahastabd = $rscomprahab->fields['fechahasta'];


//Fecha del servidor
//$buscar="Select (current_date) as ahora";
//$rsf=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
$hoy = $ahora;
//$convertido=convert_date_php_js($hoy);

$explota = explode("-", $hoy);
$an = $explota[0];
$me = $explota[1];
if ($me < 10) {
    $me = "$me";
}
$dd = intval($explota[2]);

if ($dd < 10) {
    $dd = "0$dd";
}



//Traemos los datos para mostrar
$buscar = "Select *,
(select nombre from proveedores where idproveedor=tmpcompras.proveedor) as descproveedor
 from tmpcompras where idtran=$idtransaccion   and estado = 1 ";
$rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idprov = intval($rscab->fields['proveedor']);
$factura = trim($rscab->fields['facturacompra']);
$suc = substr($factura, 0, 3);
$pex = substr($factura, 3, 3);
$fa = substr($factura, 6, 15);
$fechacompra = $rscab->fields['fecha_compra'];
$monto_factura = $rscab->fields['monto_factura'];
$tipocompra = intval($rscab->fields['tipocompra']);
$vtofac = $rscab->fields['vencimiento'];
$timbrado = $rscab->fields['timbrado'];
$timvto = $rscab->fields['vto_timbrado'];
$ocnum = $rscab->fields['ocnum'];
$idproveedor = $idprov;
$idtransaccion = $rscab->fields['idtran'];
$moneda_compra = $rscab->fields['moneda'];//Moneda origen seleccionada en la cabecera
$cotizacion_compra = floatval($rscab->fields['cambio']);//Cotizacion de la compra que puede
// venir de OC o de la seleccion en la cabecera
$proveedor_char = trim($rscab->fields['descproveedor']);//Nombre del proveedor, solo para mostrar en pantalla
if ($idtransaccion == 0) {
    header("location: gest_reg_compras_resto_new.php");
    exit;
}

//*-------------------------AGREGAR TMP---------------------------------*/

if (isset($_POST['idt']) && ($_POST['idt'] > 0)) {

    $errores = '';
    $valido = 'S';
    //print_r($_POST);
    $idtransaccion = intval($_POST['idt']);
    //Post Agregar Productos
    $tipocompra = intval($_POST['tipocompra']);
    //$numfactura=intval($_POST['numfactura']);
    $moneda = intval($_POST['moneda']);
    $cambio = floatval($_POST['cambio']);
    $suc = antisqlinyeccion($_POST['suc'], 'text');
    $pex = antisqlinyeccion($_POST['pex'], 'text');
    $fa = antisqlinyeccion($_POST['fa'], 'text');
    $provee = antisqlinyeccion($_POST['proveedor'], 'int');
    // proveedor
    $buscar = "Select * from proveedores where idproveedor = $provee and idempresa = $idempresa and estado = 1";
    $rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $prov_interno = intval($rsprov->fields['idproveedor']);
    $incrementa = trim($rsprov->fields['incrementa']);
    if ($incrementa == 'S') {
        /*$consulta="
        select max(facturacompra_incrementa) as ultfac from compras
        ";
        $rscf=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
        // actualiza numeracion proveedor
        $consulta = "
		update facturas_proveedores 
		set 
		fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
		where 
		fact_num is null
		and id_proveedor=$prov_interno ;
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $consulta = "
		select max(fact_num) as ultfac from facturas_proveedores where id_proveedor = $provee
		";
        $rscf = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $proxfac = $rscf->fields['ultfac'] + 1;
        $fa = antisqlinyeccion($proxfac, 'int');
    }

    $suc = str_replace("'", "", $suc);
    $pex = str_replace("'", "", $pex);
    $fa = str_replace("'", "", $fa);
    $monto_factura = antisqlinyeccion($_POST['monto_factura'], 'float');
    if (($suc != '') && ($pex != '') && ($fa != '')) {
        if (strlen($fa) > 7) {
            $fa = agregacero(intval($fa), strlen($fa));
        } else {
            $fa = agregacero(intval($fa), 7);
        }
        $facompra = agregacero(intval($suc), 3).agregacero(intval($pex), 3).$fa;
        $facompra = antisqlinyeccion($facompra, 'text');
        //echo $facompra;
        //exit;
    } else {
        $errores .= '* Encabezado no puede estar vacio';
    }
    if (strlen(trim($_POST['suc'])) > 3 or strlen(trim($_POST['suc'])) == 0) {
        $errores .= "* Formato de factura incorrecto verifique sucursal.";
    }
    if (strlen(trim($_POST['pex'])) > 3 or strlen(trim($_POST['pex'])) == 0) {
        $errores .= "* Formato de factura incorrecto verifique punto de expedicion.";
    }
    $fechadesdebd_dmy = date("d/m/Y", strtotime($fechadesdebd));
    $fechahastabd_dmy = date("d/m/Y", strtotime($fechahastabd));
    if (strtotime($_POST['fechacompra']) < strtotime($fechadesdebd)) {
        $errores .= "* La fecha de compra que intenta ingresar no esta habilitada, debe estar entre $fechadesdebd_dmy y $fechahastabd_dmy.";
    }
    if (strtotime($_POST['fechacompra']) > strtotime($fechahastabd)) {
        $errores .= "* La fecha de compra que intenta ingresar no esta habilitada, debe estar entre $fechadesdebd_dmy y $fechahastabd_dmy.";
    }
    if ($tipocompra == 2) {
        $vencimientofacval = trim($_POST['factura_venc']);
        if ($vencimientofacval == '') {
            $errores .= "* Debe cargar la fecha de vencimiento cuando la factura es credito.";
        }
    }
    if (intval($_POST['monto_factura']) <= 0) {
        $errores .= "* Debe ingresar el monto de la factura.";
    }

    // buscar si ya existe factura
    $consulta = "
	Select * 
	from facturas_proveedores  
	where 
	id_proveedor=$provee 
	and factura_numero=$facompra
	and timbrado = $timbrado
	and estado <> 6
	limit 1
	";
    $rscon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if ($rscon->fields['factura_numero'] != '') {
        $valido = "N";
        $errores .= " La factura Numero: $facompra ya se encuentra registrada y activa para el proveedor seleccionado.";
    }

    //Final de control de cabeza
    if ($errores == '') {
        $fecompra = antisqlinyeccion($_POST['fechacompra'], 'date');
        $provee = antisqlinyeccion($_POST['proveedor'], 'int');
        $vencimientofac = antisqlinyeccion($_POST['factura_venc'], 'date');
        $timbrado = intval($_POST['timbrado']);
        $timbradovenc = antisqlinyeccion($_POST['timbrado_venc'], 'date');
        $facturacompra_incrementa = intval($_POST['fa']);
        // proveedor
        $buscar = "Select * from proveedores where idproveedor = $provee and idempresa = $idempresa and estado = 1";
        $rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $prov_interno = intval($rsprov->fields['idproveedor']);
        $incrementa = trim($rsprov->fields['incrementa']);
        if ($incrementa == 'S') {
            // actualiza numeracion proveedor
            $consulta = "
			update facturas_proveedores 
			set 
			fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
			where 
			fact_num is null
			and id_proveedor=$prov_interno ;
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $consulta = "
			select max(fact_num) as ultfac from facturas_proveedores where id_proveedor = $provee
			";
            $rscf = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $proxfac = $rscf->fields['ultfac'] + 1;
            /*$fa=antisqlinyeccion($proxfac,'int');
            $consulta="
            select max(facturacompra_incrementa) as ultfac from compras
            ";
            $rscf=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
            $proxfac = $rscf->fields['ultfac'] + 1;
            $facturacompra_incrementa = antisqlinyeccion($proxfac, 'int');
        } else {
            $facturacompra_incrementa = intval(substr($_POST['fa'], -9));
        }

        // proveedor
        $buscar = "Select * from proveedores where borrable = 'N' and estado = 1";
        $rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $prov_interno = intval($rsprov->fields['idproveedor']);
        $incrementa = trim($rsprov->fields['incrementa']);

        //Buscamos la factura
        $buscar = "Select * 
		from compras 
		where facturacompra=$facompra 
		and idproveedor=$provee
		and timbrado = $timbrado 
		and estado=1";
        $controla = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //echo $buscar;exit;
        if ($controla->fields['facturacompra'] == '') {

            //Array ( [idt] => 13 [insuoc] => 677 [cantioc] => 12 [pcom] => 1500 [fechacompra] => 2022-06-08 [monto_factura] => 1000000 [proveedor] => 952 [suc] => 001 [pex] => 001 [fa] => 0000013 [tipocompra] => 1 [timbrado] => 1 [timbrado_venc] => 2022-06-08 [factura_venc] => [ocnum] => )


            // agregar al carrito de compras
            $parametros_array = [
                'idinsumo' => $_POST['insuoc'],
                'cantidad' => $_POST['cantioc'],
                'costo_unitario' => $_POST['pcom'],
                'idtransaccion' => $_POST['idt'],
                'lote' => $_POST['lote'],
                'vencimiento' => $_POST['vencimiento']
            ];

            $res = validar_carrito_compra($parametros_array);
            if ($res['valido'] == 'N') {
                $valido = $res['valido'];
                $errores .= nl2br($res['errores']);
            }
            //print_r($res);exit;
            // si todo es valido
            if ($valido == 'S') {
                $res = agregar_carrito_compra($parametros_array);
                $idregcc = $res['idregcc'];

                header('location: gest_reg_compras_resto_det.php?id='.$idt);
                exit;
            }

            //print_r($_POST);exit;

        } else {
            //posible duplicidad de factura
            echo 'Error! factura duplicada.';
            exit;

        }
    }//Final de errores vacios
    //Traemos los datos para mostrar
    $buscar = "Select * from tmpcompras where idtran=$idtransaccion  and idempresa = $idempresa ";
    $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idprov = intval($rscab->fields['proveedor']);
    $factura = trim($rscab->fields['facturacompra']);
    $suc = substr($factura, 0, 3);
    $pex = substr($factura, 3, 3);
    $fa = substr($factura, 6, 15);
    $fechacompra = $rscab->fields['fecha_compra'];
    $tipocompra = intval($rscab->fields['tipocompra']);
    $vtofac = $rscab->fields['vencimiento'];
    $timbrado = $rscab->fields['timbrado'];
    $timvto = $rscab->fields['vto_timbrado'];
    $monto_factura = $rscab->fields['monto_factura'];

}
/*--------------------------------FIN POST- AGREA TMP---------------------*/

/*--------------------------------POST DELETAR- ARTICULO----------------------*/
if (isset($_POST['ida']) && ($_POST['ida'] > 0)) {
    $borrar = intval($_POST['regse']);
    if ($borrar > 0) {
        $parametros_array = [
            "borrar" => intval($_POST['regse']),
            "idempresa" => $idempresa
        ];
        borrar_carrito_compra($parametros_array);
        header('location: gest_reg_compras_resto_det.php?id='.intval($_GET['id']));
        exit;

    }
}
/*--------------------------------FINAL POST DELETAR----------------------------*/

/*--------------------------------POST registrar compra----------------------------*/
if (isset($_POST['tran']) && ($_POST['tran'] > 0)) {
    $idt = intval($_POST['tran']);

    if ($idt > 0) {

        //Generamos la compra
        $buscar = "Select * from tmpcompras where idtran=$idt  ";
        $rscabecera = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        //echo $buscar;
        //exit;

        //Generamos los detalles
        $buscar = "Select * from tmpcompradeta where idt=$idt  and idemp = $idempresa";
        $rscuerpo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        // generamos los dias de pago
        $buscar = "select * from tmpcompravenc where idtran=$idt";
        $rscompravenc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        // sumar dias de pago
        $buscar = "select sum(monto_cuota) as monto_cuota, min(vencimiento) as vencimiento from tmpcompravenc where idtran=$idt";
        $rscompravencsum = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $monto_cuota_venc = floatval($rscompravencsum->fields['monto_cuota']);
        $vencimientomin = $rscompravencsum->fields['vencimiento'];
        // validacioens
        $valido = "S";
        $errores = "";



        $factura = antisqlinyeccion($rscabecera->fields['facturacompra'], 'text');
        $fechacompra = ($rscabecera->fields['fecha_compra']);
        $tipocompra = intval($rscabecera->fields['tipocompra']);

        $totalcompra = intval($rscabecera->fields['totalcompra']);
        $monto_factura = intval($rscabecera->fields['monto_factura']);
        $idprov = intval($rscabecera->fields['proveedor']);
        $vencimientofac = antisqlinyeccion($rscabecera->fields['vencimiento'], 'date');

        $timbrado = intval($rscabecera->fields['timbrado']);
        $timbradovenc = antisqlinyeccion($rscabecera->fields['vto_timbrado'], 'date');
        $facturacompra_incrementatmp = antisqlinyeccion($rscabecera->fields['facturacompra_incrementa'], 'int');
        $ocnum = antisqlinyeccion($rscabecera->fields['ocnum'], 'int');
        $idsucursal = intval($rscabecera->fields['sucursal']);
        $idtipocomprobante = antisqlinyeccion($rscabecera->fields['idtipocomprobante'], "int");
        $cdc = antisqlinyeccion(trim($rscabecera->fields['cdc']), 'text');
        $moneda = intval($rscabecera->fields['moneda']);
        $cambio = floatval($rscabecera->fields['cambio']);
        $parametros_array = [
            'idt' => $idt,
            'idprov' => $idprov,
            'idsucursal' => $idsucursal,
            'idempresa' => $idempresa,
            'fechacompra' => $fechacompra,
            'factura' => $factura,
            'idusu' => $idusu,
            'totalcompra' => $totalcompra,
            'tipocompra' => $tipocompra,
            'timbrado' => $timbrado,
            'timbradovenc' => $timbradovenc,
            'facturacompra_incrementatmp' => $facturacompra_incrementatmp,
            'ocnum' => $ocnum,
            'idtipocomprobante' => $idtipocomprobante,
            'cdc' => $cdc,
            'monto_factura' => $monto_factura,
            'monto_cuota_venc' => $monto_cuota_venc,
            'vencimientomin' => $vencimientomin,
            'vencimientofac' => $vencimientofac,
            'moneda' => $moneda,
            'cambio' => $cambio
        ];

        $respuesta = validar_compra($parametros_array);
        if ($respuesta['valido'] == 'N') {
            $valido = $respuesta['valido'];
            $errores .= nl2br($respuesta['errores']);
        }

        if ($respuesta['valido'] == 'S' && $valido == 'S') {

            $respuesta = registrar_compra($parametros_array);// regresa idcompra como array


            //header("location: gest_reg_compras_resto_det.php?id=".$idtransaccion);
            header("location: gest_adm_depositos_compras_det.php?idcompra=".$idcompra);
            exit;


        } // if($valido == 'S'){

    }//idt > 0
}
/*----------------------------FINAL--POST REGISTRAR COMPRA----------------------------*/
if ($listo == 'S') {
    $buscar = "Select max(numero) as mayor from transacciones_compras";
    $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtransaccion = intval($rsm->fields['mayor']) + 1;

}

$buscar = "Select * from proveedores where idempresa=$idempresa and estado = 1 and idproveedor = $idproveedor order by nombre ASC";
$rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tprov = $rsprov->RecordCount();

//Categorias
$buscar = "Select * from categorias where idempresa=$idempresa order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas order by nombre ASC";
$rsmed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


//Listamos los productos en detalle
$buscar = "
Select * , (
select productos.barcode 
from productos 
inner join insumos_lista on insumos_lista.idproducto = productos.idprod_serial
where
tmpcompradeta.idprod = insumos_lista.idinsumo
) as barcode,
(
select costo 
from insumos_lista 
where 
idinsumo = tmpcompradeta.idprod
) as ultcosto,
(select iva_describe from tipo_iva where idtipoiva = tmpcompradeta.idtipoiva) as tipo_iva
from tmpcompradeta 
where idt=$idtransaccion 
and idemp=$idempresa 
order by  pchar asc";
$rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tdet = $rsdet->RecordCount();

//Monedas
$buscar = "Select * from tipo_moneda order by idtipo asc";
$rsmo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$totmoneda = $rsmo->RecordCount();
$buscar = "Select * from insumos_lista where idempresa=$idempresa and estado = 'A' order by descripcion asc ";
$rsprod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("includes/head_gen.php"); ?>
	<!----------------------------JS------------------------------------->
	<script>
function cancelar(transa){
	if (transa !=''){
		document.getElementById('chaucompra').submit();
		
	}
	
}
	
function agregatmp(){
		var errores='';
		var fecompra=document.getElementById('fechacompra').value;
		if (fecompra==''){
			errores=errores+'Debe indicar fecha de compra. \n'	;
		}
		var suc=document.getElementById('suc').value;
		if (suc==''){
			errores=errores+'Debe indicar encabezado(sucursal) para factura. \n';
		}
		
		var pe=document.getElementById('pex').value;
		if (pe==''){
			errores=errores+'Debe indicar encabezado(punto exp) para factura. \n';
		}
		var fc=document.getElementById('fa').value;
		if (fc==''){
			errores=errores+'Debe indicar numero para factura de compra. \n';
		}
		var tc=document.getElementById('tipocompra').value;
		if (tc==0){
			errores=errores+'Debe indicar tipo de compra. \n';
		}
		if (document.getElementById('proveedor').value=='0')	{
				errores=errores+'Debe indicar proveedor del producto. \n'	;
				
		}
		
		if (errores==''){
			var insu=document.getElementById('insuag').value;
			if (insu=='')	{
				errores=errores+'Debe indicar Insumo a comprar. \n'	;
				
			} else {
				document.getElementById('insuoc').value=insu;
				
			}
			if (document.getElementById('nombre').value==' ')	{
				errores=errores+'Debe indicar nombre del producto. \n'	;
				
			}
			
			//Producto seleccionado
			if (document.getElementById('cantidad').value=='')	{
				errores=errores+'Debe indicar cantidad comprada producto. \n'	;
				
			}
			if (document.getElementById('costobase').value=='')	{
				
				errores=errores+'Debe indicar precio del producto. \n'	;
			}
			
			if (document.getElementById('monto_factura').value=='')	{
				
				errores=errores+'Debe indicar monto de la factura. \n'	;
			}
			
			
			
			
			if (errores==''){
				  var cantidad=document.getElementById('cantidad').value;
		 		  var precom=document.getElementById('costobase').value;
		  		 document.getElementById('cantioc').value=cantidad;
		   		document.getElementById('pcom').value=precom
				
				
				
				document.getElementById('rc').submit();
			} else {
				alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
			}
	} else {
				alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
	}
}
function validar(){
	
	var fecha=document.getElementById('fechacompra').value;
	var valido = 'S';
	var fe=fecha.split("-");
	var ano=fe[0];
	var mes=fe[1];
	var dia=fe[2];
	var f1 = new Date(ano, mes, dia); 
	var f2 = new Date(<?php echo $an ?>, <?php echo $me ?>, <?php echo $dd ?>);
	var fdesde = new Date(<?php echo date("Y", strtotime($fechadesdebd)); ?>, <?php echo date("m", strtotime($fechadesdebd)); ?>, <?php echo date("d", strtotime($fechadesdebd)); ?>);
	var fhasta = new Date(<?php echo date("Y", strtotime($fechahastabd)); ?>, <?php echo date("m", strtotime($fechahastabd)); ?>, <?php echo date("d", strtotime($fechahastabd)); ?>);
    // fecha no puede estar en el futuro
	if (f1 > f2){
		valido = 'N';
	}
	// la fecha no puede ser menor a la fecha desde
	if(f1 < fdesde){
		valido = 'N';	
	}
	// la fecha no puede ser mayor a la fecha hasta
	if(f1 > fhasta){
		valido = 'N';	
	}
	if(valido == 'N'){
		alertar('ATENCION: Algo salió mal.','Fecha de compra incorrecta, habilitado entre: <?php echo date("d/m/Y", strtotime($fechadesdebd)); ?> y <?php echo date("d/m/Y", strtotime($fechahastabd)); ?> y no pude ser mayor a hoy <?php echo date("d/m/Y", strtotime($ahora)); ?>.','error','Lo entiendo!');
		document.getElementById('fechacompra').value='';
	}else{
		cargavto();
	}
	
}
function listar(que){
	//var parametros='idc='+que;
		var parametros = {
                "idc"   : que
        };
		$.ajax({
                data:  parametros,
                url:   'minilistaprod.php',
                type:  'post',
                beforeSend: function () {
                      $("#listaprodudiv").html('Cargando...');  
                },
                success:  function (response) {
					  $("#listaprodudiv").html(response);
                }
        });
	
	//OpenPage('minilistaprod.php',parametros,'POST','listaprodudiv','pred');
	setTimeout(function(){ controlar(); }, 200);
}
function este(valor,cbar=''){
		//var parametros='insu='+valor+'&p=2';
		//OpenPage('gesr_fcompras.php',parametros,'POST','selecompra','pred');	
		var parametros = {
                "insu"   : valor,
				"cbar"   : cbar,
				"p"      : 2
        };
		$.ajax({
                data:  parametros,
                url:   'gesr_fcompras.php',
                type:  'post',
                beforeSend: function () {
                      $("#selecompra").html('Cargando...');  
                },
                success:  function (response) {
					  $("#selecompra").html(response);
					  $("#cantidad").focus();
                }
        });	
		setTimeout(function(){ controlar(); }, 200);
}
function eliminar(valor){
	document.getElementById('regse').value=valor;
	document.getElementById('deletar').submit();		
}
function cerrar(){
	var monto_factura = $("#monto_factura").val();
	var totcomp = $("#totcomp").val();
	if(monto_factura == totcomp && monto_factura > 0){
		$("#rpc").hide();
		document.getElementById('registracompra').submit();	
	}else{
		alert("El monto de factura con la sumatoria total de los montos de productos cargados.");
	}	
}
function controlar(){
  	if (document.getElementById('existep')){
	   var listo=parseInt(document.getElementById('existep').value);
	   if (listo==1){
		   var insumo=$("#insu").val();
		   var cantidad=document.getElementById('cantidad').value;
		   var precom=document.getElementById('costobase').value;
		   document.getElementById('insuoc').value=insumo;
		   document.getElementById('cantioc').value=cantidad;
		   document.getElementById('pcom').value=precom
		   $("#agp").show();
		   //document.getElementById('agp').hidden='';
	
	   } else {
		   //document.getElementById('agp').hidden='hidden';
		   $("#agp").hide();
		    document.getElementById('insuoc').value=0;
	   }
	} else {
		//document.getElementById('agp').hidden='hidden';
		$("#agp").hide();
		 document.getElementById('insuoc').value=0;
	}
}
function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
function verifica_factura(){
	var suc = $("#suc").val();
	var pex = $("#pex").val();
	var fa = $("#fa").val();
	var prov = $("#proveedor").val();
	if(parseInt(suc) > 0 && parseInt(pex) > 0 && parseInt(fa) > 0 && parseInt(prov) > 0){	
		var parametros = {
                "suc"   : suc,
				"pex"   : pex,
				"fa"    : fa,
				"prov"  : prov
        };
		$.ajax({
                data:  parametros,
                url:   'verifica_factura_compra.php',
                type:  'post',
                beforeSend: function () {
                      //$("#adicio").html('');  
                },
                success:  function (response) {
						cargavto();
						if(response == 'error'){
							alertar('ATENCION: Algo salio mal.','Factura Duplicada para el proveedor seleccionado.','error','Lo entiendo!');
						}
                }
        });
	}else{
		cargavto();	
	}
	if(parseInt(prov) > 0){
		carga_timbrado();
	}
	
}
function cargavto(){
	var prov = $("#proveedor").val();
	var tipocompra= $("#tipocompra").val();
	var fechacompra = $("#fechacompra").val();
	var parametros='pp='+prov+'&tpc='+tipocompra+'&fcomp='+fechacompra;
    OpenPage('cargavto.php',parametros,'POST','vencefactu','pred');
	
}
function recalcular(){
	var prov = $("#proveedor").val();
	var tipocompra= $("#tipocompra").val();
	var fechacompra = $("#fechacompra").val();
	var parametros='pp='+prov+'&tpc='+tipocompra+'&fcomp='+fechacompra;
    OpenPage('cargavto.php',parametros,'POST','vencefactu','pred');
	
}
function cabeza(){
	
	var fec = $("#fechacompra").val();
	var suc = $("#suc").val();
	var pex = $("#pex").val();
	var tipocompra= $("#tipocompra").val();
	var fa = $("#fa").val();
	var prov = $("#proveedor").val();
	var timbrado=$("#timbrado").val();
	var vencetimbra=$("#timbrado_venc").val();
	var vencefactu=$("#factura_venc").val();
	var monto_factura = $("#monto_factura").val();
	
	if(parseInt(suc) > 0 && parseInt(pex) > 0 && parseInt(fa) > 0 && parseInt(prov) > 0  && parseInt(tipocompra) > 0 && (fec)!='' ){
		//var idt=<?php echo $idtransaccion?>;
		/*var parametros='idt='+idt+'&tpc='+tipocompra+'&fe='+fec+'&suc='+suc+'&pe='+pex+'&fa='+fa+'&prov='+prov+'&timb='+timbrado+'&vencefc='+vencefactu+'&vencetm='+vencetimbra;
   		 OpenPage('update_cabeza.php',parametros,'POST','updatecabeza','pred');*/
		 
		var parametros = {
                "idt"     : <?php echo $idtransaccion?>,
				"tpc"     : tipocompra,
				"fe"      : fec,
				"suc"     : suc,
				"pe"      : pex,
				"fa"      : fa,
				"prov"    : prov,
				"timb"    : timbrado,
				"vencefc" : vencefactu,
				"vencetm" : vencetimbra,
				"mfac"    : monto_factura
        };
		$.ajax({
                data:  parametros,
                url:   'update_cabeza.php',
                type:  'post',
                beforeSend: function () {
                	$("#updatecabeza").html('Actualizando...');  
                },
                success:  function (response) {
					$("#updatecabeza").html(response);
                }
        });
	
	}
}
function carga_timbrado(){
	var prov = $("#proveedor").val();
	var timbrado = $("#timbrado").val();
	var timbrado_venc = $("#timbrado_venc").val();
	// condicion de busqueda
	var cambia = "S";
	if(timbrado != ''){
		if(window.confirm('Existe un timbrado escrito en el campo, desea reemplazarlo?')){
			cambia = "S";	
		}else{
			cambia = "N";	
		}
	}
	if(cambia == 'S'){
		var parametros = {
				"prov"    : prov
        };
		$.ajax({
                data:  parametros,
                url:   'gest_compras_carga_timbrado.php',
                type:  'post',
                beforeSend: function () {
                	$("#timbrado").val('cargando...');  
					$("#timbrado_venc").val('');  
                },
                success:  function (response) {
					var datos = response.split(',');
					var timb = datos[0];
					var timbv = datos[1];
					var facincre = datos[2];
					var faactu = $("#fa").val();
					//alert(facincre);
                	$("#timbrado").val(timb);  
					$("#timbrado_venc").val(timbv);
					if(parseInt(facincre) > 0 && faactu == ''){
						$("#suc").val('1');
						$("#pex").val('1');
						$("#fa").val(facincre);					
					}
                }
        });
	}
}
function buscar_codbar(e){

	
	var codbar = $("#codbar").val();
	tecla = (document.all) ? e.keyCode : e.which;
	// tecla enter
  	if (tecla==13){
		// selecciona
		este(0,codbar);
	
	}
}
function genera_auto(idt){
	var ocnum = $("#ocnum").val();
	if(ocnum > 0){
		document.location.href='gest_reg_compras_resto_gen.php?ocnum='+ocnum+'&idt='+idt;
	}else{
		alert("Error! no indico el numero de orden de compra.");	
	}
	
}
/*function genera_auto(idt){
		//var parametros='insu='+valor+'&p=2';
		//OpenPage('gesr_fcompras.php',parametros,'POST','selecompra','pred');	
		
		var ocnum=$("#ocnum").val();
		var fechacompra=$("#fechacompra").val();
		var proveedor=$("#proveedor").val();
		var factura_venc=$("#factura_venc").val();
		var timbrado=$("#timbrado").val();
		var timbrado_venc=$("#timbrado_venc").val();
		var tipocompra=$("#tipocompra").val();
		var moneda=$("#moneda").val();
		var cambio=$("#cambio").val();
		var suc=$("#suc").val();
		var pex=$("#pex").val();
		var fa=$("#fa").val();
		var proveedor=$("#proveedor").val();
		


		
		var parametros = {
                "idt"              : idt,
				"ocnum"            : ocnum,
				"fechacompra"      : fechacompra,
				"proveedor"        : proveedor,
				"factura_venc"     : factura_venc,
				"timbrado"         : timbrado,
				"timbrado_venc"    : timbrado_venc,
				"tipocompra"       : tipocompra,
				"moneda"           : moneda,
				"cambio"           : cambio,
				"suc"              : suc,
				"pex"              : pex,
				"fa"     		   : fa,
				"proveedor"        : proveedor
        };
		$.ajax({
                data:  parametros,
                url:   'gest_reg_compras_resto_gen.php',
                type:  'post',
                beforeSend: function () {
                      $("#selecompra").html('Cargando...');  
                },
                success:  function (response) {
					  $("#selecompra").html(response);
					  
                }
        });	
}*/
</script>
	
	<!------------------------------------------------------------------->
  </head>
<?php
//Rellenamos algunas variables aca hast que se ordene arriba
$factura_completa = $suc."-".$pex."-".$fa;
if ($moneda_compra == 0) {
    //Buscamos la moneda predeterminada
    $buscar = "Select * from moneda where nacional='S' ";
    $rsmoneda_ppal = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $ima = trim($rsmoneda_ppal->fields['banderita']);

    $banderita = "<img src='$ima' width:30px height:30px >";
}
?>
  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Registrando compra</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
				  <div class="x_content">
					<div class="col-md-12 col-xs-12">
					<a href="javascript:void(0);" title="Ver detalles de cabecera"onclick="mostrar_datos(<?php echo $idt ?>);">[Cabecera &nbsp;Idt:<?php echo $idtransaccion ?>]&nbsp;<span class="fa fa-search"></span> </a>|&nbsp;  Factura Nº&nbsp;<?php echo $factura_completa; ?> |&nbsp;Monto <?php echo $banderita ?>:<?php echo formatomoneda($monto_factura, 2, 'N');?> |&nbsp; <?php echo $proveedor_char ?></h2>
					</div>
						

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            
			 <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2><span class"fa fa-shopping-cart"></span>&nbsp;Detalle Compras</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
						<div class="col-md-6 col-xs-12">
							<div class="col-md-12">
								Buscar Productos
							</div>
						</div>
						<div class="col-md-6 col-xs-12">
								<div class="col-md-12">
									Carrito Compras
							</div>
						</div>






                  </div>
                </div>
              </div>
            </div>
            
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="ventanamodal">
				<div class="modal-dialog modal-lg">
				  <div class="modal-content">
					<div class="modal-header">
					  <h4 class="modal-title" id="titulov"></h4>
					</div>
					<div class="modal-body" id="cuerpov" >
						
					</div>
					<div class="modal-footer"  id="piev">
					  
					  <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>&nbsp;
					  
					</div>

				  </div>
				</div>
			</div>
			
			
			
			
			
            
          </div>
        </div>
        <!-- /page content -->
		<script>
			function mostrar_datos(){
				var idtransaccion=<?php echo $idt ?>;
				var parametros = {
					"idtransaccion"	: idtransaccion
				};
				
				$.ajax({		  
					data:  parametros,
					url:   'compras_mostrar_cabecera.php',
					type:  'post',
					cache: false,
					timeout: 3000,  // I chose 3 secs for kicks: 3000
					crossDomain: true,
					beforeSend: function () {	
										
					},
					success:  function (response) {
						$("#datos_anexos").html(response);
						// TODO: ACA CAMBIAR OK 	
						if (response=='OK'){
							actualiza_carrito();
						} else  {
							alert(response);
						}
					}
				});

			}
		</script>
        <!-- footer content -->
		<?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
