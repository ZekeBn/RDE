<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");


//Post de Registro

if (isset($_POST['tv']) && intval($_POST['tv']) > 0) {
    $log = '';

    $idtransaccion = intval($_POST['idtoc']);
    //Cliente
    $idcliente = intval($_POST['idclioc']);
    //Descuento
    $desc = floatval($_POST['desc']);
    //Medio de Entrega
    $mentrega = intval($_POST['medioentrega']);
    if ($mentrega == 1) {
        $delivery = 15000;
    } else {
        $delivery = intval($_POST['centrega']);
    }
    $tk = '';

    $ruc = antisqlinyeccion($_POST['ruc'], 'text');
    $ruc = str_replace("'", "", $ruc);
    $explota = explode("-", $ruc);
    $ruc = intval($explota[0]);
    $dv = intval($explota[1]);
    $factura = '';
    //$factura=antisqlinyeccion($_POST['idfact'],'text');

    /* if ($factura=="'0'"){
        $tk=buscartickete($idsucursal,$pe,$idempresa);
    }
    if ($factura=='NULL'){
        $tk=buscartickete($idsucursal,$pe,$idempresa);
    }
    if ($factura==''){
        $tk=buscartickete($idsucursal,$pe,$idempresa);
    } */
    $log = $log."Fac: $factura | Tk: $tk<br />";
    $asignado = intval($_POST['asignado']);
    if ($asignado > 0) {
        $buscar = "Select * from usuarios where idusu=$asignado";
        $rsdd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $quien = trim($rsdd->fields['nombres']);
    } else {
        $quien = '';

    }

    $tipoventa = intval($_POST['condventa']);



    $log = $log."Tipo Venta: $tipoventa<br />";
    $formapago = intval($_POST['formapago']);
    $log = $log."Formapago: $formapago<br />";
    //Cabecera temporal
    $buscar = "Select * from tmpventa where idtran=$idtransaccion";
    $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $log = $log."Buscar cabecera: $buscar<br />";


    //Cuerpo
    $buscar = "Select * from tmpventadeta where idtfk=$idtransaccion";
    $rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $log = $log."Buscar cuerpo: $buscar<br />";

    //Sumamos los valores vendidos
    $buscar = "Select sum(subtotal) as totalvendido,sum(descnetogs) as netodesc from tmpventadeta where idtfk=$idtransaccion";
    $rstv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $log = $log."Neto Vendido: $buscar<br />";
    $totalvendido = floatval($rstv->fields['totalvendido']);
    $totaldescontado = floatval($rstv->fields['netodesc']);

    $buscar = "Select sum(subtotal) as sub10 from tmpventadeta where idtfk=$idtransaccion and iva=10";
    $rsiv1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $buscar = "Select sum(subtotal) as sub5 from tmpventadeta where idtfk=$idtransaccion and iva=5";
    $rsiv5 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $buscar = "Select sum(subtotal) as subex from tmpventadeta where idtfk=$idtransaccion and iva=0";
    $rsex = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $tventa10 = floatval($rsiv1->fields['sub10']);
    $iva10 = (floatval($rsiv1->fields['sub10']) / 11);
    $tventa5 = floatval($rsiv5->fields['sub5']);
    $iva5 = (floatval($rsiv5->fields['sub5']) / 21);
    $tventaex = floatval($rsiv1->fields['subex']);
    $idpedido = intval($_POST['idpedido']);

    //Registramos venta
    $log = $log."<hr />Reg Venta<br />";
    $buscar = "Select max(idventa) as mayor from ventas where idempresa=$idempresa";
    $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idventa = intval($rsm->fields['mayor']) + 1;
    $log = $log."Asignar ID VTA: $idventa<br />";
    //Genermos el TRACKID
    $hoy = date("d-m-Y");
    $fechaex = explode("-", $hoy);
    $dia = $fechaex[0];
    $mes = $fechaex[1];
    $ano = $fechaex[2];

    //armamos el tcid
    $numerobase = $ano.$mes.$dia.$idsucursal.$idempresa.$idcliente;
    $secuencia = $idtransaccion.$idventa;
    $compuesto = $numerobase.$secuencia.$idusu;
    $log = $log."Track ID/ $compuesto <br />";
    //Armamos el TOtal a CObrar, basados en el total de la venta+delivery si existe..se omite el descuento, ya que el sub-total ya es con desc incluido
    $totalcobrar = $totalvendido + $delivery;

    //Crear cuenta si es credito
    if ($tipoventa == 2) {
        $log = $log."<hr />Venta a credito, generar cuenta interna para futura cobranza<br />";
        //Credito
        $buscar = "Select max(idcta) as mayor from cuentas_clientes where idempresa=$idempresa";
        $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $log = $log."buscar<br />";
        $mayor = intval($rs1->fields['mayor']) + 1;

        //Generamos la cuenta

        $insertar = "
		Insert into cuentas_clientes 
		(idcta,idempresa,sucursal,deuda_global,saldo_activo,idcliente,estado,registrado_el,registrado_por,idventa)
		values
		($mayor,$idempresa,$idsucursal,$totalcobrar,$totalcobrar,$idcliente,1,current_timestamp,$idusu,$idventa)
		";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $log = $log."<hr />CUENTA CREDITO GENERADA.<br />
		$insertar <br />";
    }


    $insertar = "Insert into ventas
	(fecha,idcliente,tipo_venta,idempresa,sucursal,factura,recibo,ruchacienda,dv,
	total_venta,idtransaccion,idventa,trackid,registrado_por,totaliva10,totaliva5,texe,idpedido,otrosgs,descneto,deliv,totalcobrar)
	VALUES
	(current_timestamp,$idcliente,$tipoventa,$idempresa,$idsucursal,'$factura','$tk',$ruc,$dv,
	$totalvendido,$idtransaccion,$idventa,$compuesto,$idusu,$iva10,$iva5,$tventaex,0,$delivery,$totaldescontado,'$quien',$totalcobrar)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    $log = $log."Insert Ventas: $insertar<br />";
    $log = $log."<hr />Detalles (CUERPO) <br />";
    //Detalles de la venta
    while (!$rsdet->EOF) {

        $cantidad = floatval($rsdet->fields['cantidad']);
        $precioventa = floatval($rsdet->fields['precioventa']);
        $subtotal = floatval($rsdet->fields['subtotal']);
        $pchar = trim($rsdet->fields['pchar']);
        $idprod = trim($rsdet->fields['idprod']);
        $costo = floatval($rsdet->fields['costo']);
        $utilidad = floatval($rsdet->fields['utilidad']);
        $iva = floatval($rsdet->fields['iva']);
        $desc = floatval($rsdet->fields['descnetogs']);
        //$desc=$desc*$cantidad;
        $p1 = floatval($rsdet->fields['p1']);
        $p2 = floatval($rsdet->fields['p2']);
        $p3 = floatval($rsdet->fields['p3']);

        $insertar = "Insert into ventas_detalles
		(cantidad,pventa,subtotal,idventa,idemp,sucursal,idprod,pchar,costo,utilidad,iva,descuento,p1,p2,p3)
		values
		($cantidad,$precioventa,$subtotal,$idventa,$idempresa,$idsucursal,'$idprod','$pchar',$costo,$utilidad,$iva,$desc,$p1,$p2,$p3)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $log = $log."$insertar<br />";
        //Actualizar costos y STOCK
        $update = "Update productos set disponible=(disponible-$cantidad) where idprod='$idprod'";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        $log = $log."Stock Actualizado: $update<br />";
        $log = $log."Buscar Menor costo FIFO:<br />";
        //Ahora costos
        $buscar = "Select idseriepkcos as serial from costo_productos where id_producto='$idprod' and cantidad > 0 order by registrado_el asc limit 1";
        $rscosto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idserial = intval($rscosto->fields['serial']);
        $log = $log."Serial encontrado: $idserial <br />";
        if ($idserial > 0) {
            $log = $log."Hay serial: <br />";
            $buscar = "select * from costo_productos where idseriepkcos=$idserial";
            $rscosto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //Cuanto hay en costos de ese producto
            $log = $log."$buscar<br />";
            $dispocosto = floatval($rscosto->fields['cantidad']);
            //Caso 1: Venta menor al  disponible en costos
            $log = $log."Caso 1: Ver si el dispo en costos es superior o igual a la cantidad vendida<br />";
            if ($cantidad <= $dispocosto) {
                $update = "Update costo_productos set cantidad=(cantidad-$cantidad) where idseriepkcos=$idserial and id_producto='$idprod'";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
                $log = $log."$update (caso1)<br />";
            } else {
                //La cantidad vendida supera al costo existente
                //Hallamos la diferencia
                $log = $log."Cantidad vendida es mayor al menor costo: diferencia<br />";
                $diferencia = $cantidad - $dispocosto;
                $log = $log."Vendido: $cantidad - Dispo en costos: $dispocosto. Diferencia=$diferencia<br />";
                //Actualizamos la disponibilidad inmediata en costos
                $update = "Update costo_productos set cantidad=0 where idseriepkcos=$idserial and id_producto='$idprod'";
                $conexion->Execute($update) or die(errorpg($conexion, $update));
                $log = $log."Actualizar de inmediato costos: $update<br />";
                //Buscamos el siguiente
                $log = $log."Buscar nuevo costo para diferencias: $<br />";
                $buscar = "select * from costo_productos where id_producto='$idprod' and idseriepkcos<>$idserial order by idseriepkcos asc"  ;
                $rscosto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $log = $log."$buscar<br />Recorrer hasta dif=0<br /><hr />";
                $c = 0;
                while (!$rscosto->EOF) {
                    $c++;

                    $tmca = floatval($rscosto->fields['cantidad']);
                    $idscc = intval($rscosto->fields['idseriepkcos']);
                    if ($diferencia <= $tmca) {
                        $log = $log."Lo que falta completar, es inferior a la cant disponible en costos<br />";
                        $update = "Update costo_productos set cantidad=(cantidad-$diferencia) where idseriepkcos=$idscc
						 and id_producto='$idprod'";
                        $conexion->Execute($update) or die(errorpg($conexion, $update));
                        $diferencia = 0;
                        $log = $log."Actualizamos y salimos del ciclo: dif=$diferencia - $update<br />paso: $c<br />";
                    } else {
                        $update = "Update costo_productos set cantidad=0 where idseriepkcos=$idscc
						 and id_producto='$idprod'";
                        $conexion->Execute($update) or die(errorpg($conexion, $update));
                        $diferencia = $diferencia - $tmca;
                        $log = $log."Actualizamos y continuamos ciclo, ya que es menor a la cantidad $update<br /> paso: $c<br />";
                    }

                    $rscosto->MoveNext();
                    if ($diferencia == 0) {
                        $log = $log."CORTAR ciclo costo por dif=$diferencia<br /> paso: $c<br />";
                        break;
                    }
                }

            }
        }
        $rsdet->MoveNext();
        $log = $log."<hr />Siguiente detalle<br />";

    }
    if (($tk != '') && ($tk != 'NULL')) {
        $update = "Update lastcomprobantes set numtk=numtk+1 ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        $log = $log."Actualizamos Num Tk $update<br /> paso: $c<br />";
    }
    //Borramos los detalles temporales
    $delete = "Delete from tmpventadeta where idtfk=$idtransaccion";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
    $log = $log."<hr />Borrar Temporal: $delete<br />";

    //cabecera
    $update = "update  tmpventa set estado=3 where idtran=$idtransaccion";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    $log = $log."<hr />Actualizar temporal: $update<br />";
    //Marcamos la transaccion
    $update = "update  transacciones set estado=3 where numero=$idtransaccion and idusu=$idusu";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    $log = $log."<hr />Marcar Transaccion: $update <br />";
    //Preparamos archivo para impresion

    $buscar = "Select * from ventas where idventa=$idventa";
    $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $log = $log."<hr />$buscar <br />";

    //Detalles
    $buscar = "Select * from ventas_detalles where idventa=$idventa";
    $rsdeta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $log = $log."<hr />Detalles: $buscar <br />";

    $fac = $rscab->fields['factura'];
    $rec = $rscab->fields['recibo'];
    $idcliente = intval($rscab->fields['idcliente']);

    $buscar = "Select * from cliente where idcliente=$idcliente";
    $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $log = $log."<hr />Cliente: $buscar  <br />";
    $docu = $rscli->fields['documento'];
    $razon = trim($rscli->fields['razon_social']);
    if ($rec == $docu) {
        $tip = 1;

    }
    if ($fac == $docu) {
        $tip = 2;
    }


    $fechaventa = date("Y-m-d", strtotime($rscab->fields['fecha']));
    $tipoventa = intval($rscab->fields['tipo_venta']);
    $totalventa = floatval($rscab->fields['total_venta']);
    $totaldescuento = floatval($rscab->fields['descneto']);
    $totaliva10 = floatval($rscab->fields['totaliva10']);
    $totaliva5 = floatval($rscab->fields['totaliva5']);
    $totalex = floatval($rscab->fields['texe']);
    $trackid = $rscab->fields['trackid'];
    $otros = floatval($rscab->fields['otrosgs']);
    $ruchacienda = $rscab->fields['ruchacienda'];

    $dv = $rscab->fields['dv'];
    $formapago = intval($rscab->fields['formapago']);
    $quien = trim($rscab->fields['deliv']);

    $log = $log."<hr />Cuerpo para tickete:  <br />";
    //armamos el cuerpo
    $arraycuerpo = '';
    while (!$rsdeta->EOF) {

        $pchar = trim($rsdeta->fields['pchar']);
        $precioventa = floatval($rsdeta->fields['pventa']);
        $cantidad = floatval($rsdeta->fields['cantidad']);

        $p1 = floatval($rsdeta->fields['p1']);
        $p2 = floatval($rsdeta->fields['p2']);
        $p3 = floatval($rsdeta->fields['p3']);
        $iva = intval($rsdeta->fields['iva']);

        $descuento = floatval($rsdeta->fields['descuento']);



        $concat = $pchar.','.$precioventa.','.$cantidad.','.$p1.','.$p2.','.$p3.','.$descuento.','.$iva;
        $arraycuerpo = $arraycuerpo.$concat.',';


        $rsdeta->MoveNext();
    }

    $log = $log."<hr />Cuerpo para tickete: Listo  <br />";


    $ventalista = 'S';
    $registrado = 'S';
    $log = $log."<hr />Venta Terminada. <br />";



}

$idt = intval($_POST['idt']);
if ($idt == 0) {
    //vemos por get
    $idt = intval($_GET['idt']);
    if ($idt == 0) {
        //No vino porpost o get, por lo cual debemos comrobrar que no sea una venta activa (temporal)
        //Vemos si esta venta pertenece a una transaccion abierta o no
        $buscar = "Select * from transacciones where idusu=$idusu and estado=1 and tipo=3";
        $rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idt = intval($rst->fields['numero']);
        if ($idt == 0) {
            //Generar id de transaccion
            $buscar = "Select max(numero) as mayor from transacciones";
            $rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idt = intval($rst->fields['mayor']) + 1;
            //reservamos
            $insertar = "Insert into transacciones
			(idempresa,numero,estado,sucursal,idcliente,fecha,tipo,idusu)
			values
			($idempresa,$idt,1,1,0,current_timestamp,3,$idusu)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        }

    } //de GET

}// de POST

/*---------------------------------------------------------COMPROBAR------------------------------------------------*/

//Segun el codigo de Transaccion, comprobamos si existen datos para la venta abierta
$buscar = "Select * from tmpventa where idtran=$idt and idusu=$idusu";
$rsvta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idvta = $rsvta->fields['idtran'];
if ($idvta == 0) {
    //No existe la transaccion en tmpventas
    $insertar = "Insert into 
	tmpventa (idtran,sucursal,idempresa,fechahora,estado,idusu)
	values
	($idt,1,$idempresa,current_timestamp,1,$idusu)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
    //Si viene de un pedido efectuado, para migrar la informacion correcta
    $idpedido = intval($_GET['idp']);
    if ($idpedido > 0) {

        //buscamos para saber si ya esta migrado
        $buscar = "Select * from tmpventa where idpedido=$idpedido";
        $rsped = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idv = intval($rsped->fields['idcliente']);
        if ($idv == 0) {

            //No existe e insertamos
            $buscar = "Select * from pedidos where idpedido=$idpedido";
            $rspedido = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idclientepedido = intval($rspedido->fields['idcliente']);
            $medioentrega = intval($rspedido->fields['medioentrega']);
            //Detalles
            $buscar = "Select * from pedidos_detalles where idpedido=$idpedido";
            $pd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            //updateamos en tmpventa
            $update = "Update tmpventa set idpedido=$idpedido,idcliente=$idclientepedido where idtran=$idt";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            //Actualizamos la transaccion
            $update = "Update pedidos set procesado=1,idtransaccion=$idt where idpedido=$idpedido";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            //Recorremos
            while (!$pd->EOF) {
                $cantidad = floatval($pd->fields['cantidad']);
                $idprod = trim($pd->fields['idprod']);
                $precio_venta = floatval($pd->fields['precio_venta']);
                $subtotal = floatval($pd->fields['subtotal']);
                //Buscamos los datos que faltan
                $buscar = "Select * from productos where idprod='$idprod'";
                $rsbp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $p1 = floatval($rsbp->fields['p1']);
                $p2 = floatval($rsbp->fields['p2']);
                $p3 = floatval($rsbp->fields['p3']);
                $lp = ($rsbp->fields['listaprecios']);
                $disponible = intval($rsbp->fields['disponible']);
                $descripcion = trim($rsbp->fields['descripcion']);
                $tipoprecio = 1;
                $iva = intval($rsbp->fields['tipoiva']);
                $totdescu = 0;

                //Traemos el costo del producto
                $buscar = "Select * from costo_productos where id_producto='$idprod' and cantidad > 0 order by registrado_el asc";
                $rsco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $costofinal = floatval($rsco->fields['precio_costo']);
                $dispocosto = ($rsco->fields['cantidad']);
                if ($cantidad > $dispocosto) {
                    //Existen dos o mas costos aun para este producto, por lo cual debemos hallar el costo nuevo
                    $buscar = "Select precio_costo from costo_productos where id_producto='$idprod' order by registrado_el desc limit 1";
                    $rscop = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $costofinal = floatval($rscop->fields['precio_costo']);
                }
                $utilidad = $precio_venta - $costofinal;
                //Obtenido el costo e utilidad,

                //Agregamos al temporal
                $insertar = "
				Insert into tmpventadeta
				(idprod,idemp,cantidad,costo,utilidad,disponible,precioventa,subtotal,pchar,idtfk,iva,descnetogs,p1,p2,p3)
				values
				('$idprod',1,$cantidad,$costofinal,$utilidad,$disponible,$precio_venta,$subtotal,'$descripcion',$idt,$iva,$totdescu,$p1,$p2,$p3)	
				";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                $pd->MoveNext();
            }


        }

    }
} else {
    //Ya existe la transaccion en tmpventas
    $idpedido = intval($_GET['idp']);
    if ($idpedido > 0) {

        /*---------------------------------------------------VINCULAR PEDIDO-----------------------------------*/
        //buscamos para saber si ya esta migrado
        $buscar = "Select * from tmpventa where idpedido=$idpedido";
        $rsped = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idv = intval($rsped->fields['idcliente']);

        if ($idv == 0) {

            //No existe e insertamos
            $buscar = "Select * from pedidos where idpedido=$idpedido";
            $rspedido = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idclientepedido = intval($rspedido->fields['idcliente']);
            $medioentrega = intval($rspedido->fields['medioentrega']);
            //Detalles
            $buscar = "Select * from pedidos_detalles where idpedido=$idpedido";
            $pd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            //updateamos en tmpventa
            $update = "Update tmpventa set idpedido=$idpedido,idcliente=$idclientepedido where idtran=$idt";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            //Actualizamos la transaccion
            $update = "Update pedidos set procesado=1,idtransaccion=$idt where idpedido=$idpedido";
            $conexion->Execute($update) or die(errorpg($conexion, $update));
            //Recorremos
            while (!$pd->EOF) {
                $cantidad = floatval($pd->fields['cantidad']);
                $idprod = trim($pd->fields['idprod']);
                $precio_venta = floatval($pd->fields['precio_venta']);
                $subtotal = floatval($pd->fields['subtotal']);
                //Buscamos los datos que faltan
                $buscar = "Select * from productos where idprod='$idprod'";
                $rsbp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $p1 = floatval($rsbp->fields['p1']);
                $p2 = floatval($rsbp->fields['p2']);
                $p3 = floatval($rsbp->fields['p3']);
                $lp = ($rsbp->fields['listaprecios']);
                $disponible = intval($rsbp->fields['disponible']);
                $descripcion = trim($rsbp->fields['descripcion']);
                $tipoprecio = 1;
                $iva = intval($rsbp->fields['tipoiva']);
                $totdescu = 0;

                //Traemos el costo del producto
                $buscar = "Select * from costo_productos where id_producto='$idprod' and cantidad > 0 order by registrado_el asc";
                $rsco = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $costofinal = floatval($rsco->fields['precio_costo']);
                $dispocosto = ($rsco->fields['cantidad']);
                if ($cantidad > $dispocosto) {
                    //Existen dos o mas costos aun para este producto, por lo cual debemos hallar el costo nuevo
                    $buscar = "Select precio_costo from costo_productos where id_producto='$idprod' order by registrado_el desc limit 1";
                    $rscop = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                    $costofinal = floatval($rscop->fields['precio_costo']);
                }
                $utilidad = $precio_venta - $costofinal;
                //Obtenido el costo e utilidad,

                //Agregamos al temporal
                $insertar = "
				Insert into tmpventadeta
				(idprod,idemp,cantidad,costo,utilidad,disponible,precioventa,subtotal,pchar,idtfk,iva,descnetogs,p1,p2,p3)
				values
				('$idprod',1,$cantidad,$costofinal,$utilidad,$disponible,$precio_venta,$subtotal,'$descripcion',$idt,$iva,$totdescu,$p1,$p2,$p3)	
				";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                $pd->MoveNext();
            }


        }


        /*------------------------------------------------------FINAL VINCULAR PEDIDO-------------------------------*/

    }
}
$buscar = "Select * from tmpventa where idtran=$idt and idusu=$idusu";
$rsvta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idclientepedido = intval($rsvta->fields['idcliente']);

$idpedido = intval($_GET['idp']);
if ($idpedido > 0) {
    $buscar = "Select * from pedidos where idtransaccion=$idt";
    $rspp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $medioentrega = intval($rspp->fields['medioentrega']);
    $costoenvio = intval($rspp->fields['costoenv']);
}
//$tk=buscartickete($idsucursal,$pe,$idempresa);
/*---------------------HECHAUKA RUC GENERICO---------------------*/
$genericoruc = '44444401';
$genericodv = '7';
$generico = $genericoruc.'-'.$genericodv;

/*------------------FIN -HECHAUKA-GENERICO---------------------*/

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<!-------------<link rel="stylesheet" href="css/bootstrap.css" type="text/css" media="screen" /> ---------->
<link rel="stylesheet" href="css/magnific-popup.css" type="text/css" media="screen" /> 
<?php require("includes/head.php"); ?>
<script>
	function seleccionarp(valor1,valor2,valor3){
		var desc=valor3;
		var idp=valor1;
		var dis=valor2;
		
		
		if ((idp=='') || (dis=='')){
			alertar('ATENCION: Algo salió mal.','Debe seleccionar un producto.','error','Lo entiendo!');	
		} else {
			
			document.getElementById('prod').value=desc;
			document.getElementById('codigo').value=idp;
			document.getElementById('dispo').value= dis;
			document.getElementById('flechita').hidden='';
			document.getElementById('listota').hidden='hidden'
			
			
		}
	}
	function des(){
		if (document.getElementById('listota').hidden){
			document.getElementById('listota').hidden='';
		} else {
			document.getElementById('listota').hidden='hidden';
		}
	}
	function cerrar(){
		$('.login-popup').magnificPopup('close'); 
		document.getElementById('cantidad').focus(); 
	}
	function agregar(cual){
		if (cual !=4){
			var errores='';
			var idtra=<?php echo $idt?>;
			var idp=document.getElementById('codigo').value;
			
			var tipoprecio=parseInt(cual);
			var cantidad=(document.getElementById('cantidad').value);
			
			var disponible=parseInt(document.getElementById('dispo').value);
			if (disponible==0){
				errores=errores+'Lo sentimos, el producto esta agotado. \n';
			} else {
				//hay pero debemos controlar la cantidad
				if (cantidad > disponible){	
					errores=errores+'No disponemos de la cantidad ingresada en stock.\n Tenemos actualmente '+disponible;
				}  else {
					if (cantidad==0){
						errores=errores+'Debe indicar cantidad a vender \n';
						
					} else {
						if (document.getElementById('cantidad').value=''){
							errores=errores+'Debe indicar cantidad a vender \n';
							
						}
					}
					if (tipoprecio==0){ 
						errores=errores+'Debe indicar tipo de precio \n';
					
					}
				}
			}
			if (errores!=''){
				alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
				
			} else {
					document.getElementById('codigo').value='';
					document.getElementById('cantidad').value='';
					//ya lo tenemos, limpiar busqueda previa
					document.getElementById('mensa').innerHTML='';
					var parametros='tp=1&idp='+idp+'&ca='+cantidad+'&idtransaccion='+idtra+'&tipoprecio='+tipoprecio;
					OpenPage('tmp_productos.php',parametros,'POST','tmproductos','pred');		
			}
		} else {
			// es intermedio
			var errores='';
			var idtra=<?php echo $idt?>;
			var idp=document.getElementById('codigo').value;
			
			var tipoprecio=parseInt(cual);
			var cantidad=(document.getElementById('cantidad').value);
			var pinter=parseFloat(document.getElementById('intermedio').value);
			//var precio2=parseFloat(document.getElementById('precio2').value);
			var costoseguro=parseFloat(document.getElementById('costoseguro').value);
			if (pinter > 0){
				//controlar que no sea inferior al costo seguro 
				if (pinter < costoseguro){
					alertar('ATENCION: Algo salio mal.','Precio incorrecto.No puede ser menor que el Costo de '+costoseguro+' Gs.','error','Lo entiendo!');
				} else {
					
		
					
					var disponible=parseInt(document.getElementById('dispo').value);
					if (disponible==0){
						errores=errores+'Lo sentimos, el producto esta agotado. \n';
					} else {
						//hay pero debemos controlar la cantidad
						if (cantidad > disponible){	
							errores=errores+'No disponemos de la cantidad ingresada en stock.\n Tenemos actualmente '+disponible;
						}  else {
							if (cantidad==0){
								errores=errores+'Debe indicar cantidad a vender \n';
								
							} else {
								if (document.getElementById('cantidad').value=''){
									errores=errores+'Debe indicar cantidad a vender \n';
									
								}
							}
							if (tipoprecio==0){ 
								errores=errores+'Debe indicar tipo de precio \n';
							
							}
						}
					}
					if (idp==''){
						errores=errores+'Debe indicar  un producto a vender\n';
						
						
					}
					if (errores==''){
						document.getElementById('codigo').value='';
						document.getElementById('cantidad').value='';
						document.getElementById('mensa').innerHTML='';
						var parametros='tp=1&idp='+idp+'&ca='+cantidad+'&idtransaccion='+idtra+'&tipoprecio='+tipoprecio+'&inter='+pinter;
						OpenPage('tmp_productos.php',parametros,'POST','tmproductos','pred');
					} else {
						alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
						
					}
				}
				
			} else {
				alertar('ATENCION: Algo salio mal.','Debe indicar precion Intermedio entre 1 y 2 ','error','Lo entiendo!');
				
			}
			
		}
	}
	function eliminar(reg){
		if (reg > 0){
			var idtra=<?php echo $idt?>;
			var parametros='tp=3&idtransaccion='+idtra+'&reg='+reg;
			OpenPage('tmp_productos.php',parametros,'POST','tmproductos','pred');		
			
		}
		
	}
	function seleccionarclie(quien){
		if (quien !=''){
			var parametros='tp=2&idcli='+quien;
			OpenPage('includes/formitoventa.php',parametros,'POST','clientito','pred');	
		
		}
		
	}
	function descontar(valor){
		if (valor !=''){
			valor=parseInt(valor);
			if (valor >30)	{
				valor=30;
				
			}
			var neto=parseInt(document.getElementById('ta').value);//parseInt(document.getElementById('tv').value);
			//alert(neto);
			var descontar=(neto*valor);
			var descuento=descontar/100;
			neto=neto-descuento;
			//vemos si tiene delivery
			var deliv=document.getElementById('centrega').value;
			if (deliv !=''){
				deliv=parseInt(deliv);
				
			} else {
				deliv=0;
				
			}
			
			document.getElementById('neto').value=neto+deliv;
			
		} else {
			var deliv=document.getElementById('centrega').value;
			if (deliv !=''){
				deliv=parseInt(deliv);
				
			} else {
				deliv=0;
				
			}
			var neto=parseInt(document.getElementById('ta').value);
			document.getElementById('neto').value=neto+deliv;
		}
		
	}
	function mentrega(valor){
		//Total global de la venta
		var netov=parseInt(document.getElementById('ta').value);
		//Valor del descuento si hubiere
		var valord=parseInt(document.getElementById('desc').value);
		if (valord >0){
			//Monto a descontar
			//var descontar=parseInt(netov*valord);
			//descontar=descontar/100;
			descontar=0;
		} else {
			descontar=0;	
		}
		var medioentrega=parseInt(document.getElementById('medioentrega').value);
		if (medioentrega==1){
		
			document.getElementById('centrega').value=15000;
			document.getElementById('neto').value=(netov+15000)-descontar;
		} else {
			document.getElementById('centrega').value=0;
			document.getElementById('neto').value=netov-descontar;
			
		}
		
		
	}
	function registrarventa(){
		var errores='';
		var idcliente=parseInt(document.getElementById('idclioc').value);
		var idt=document.getElementById('idtoc').value;
		var fpago=parseInt(document.getElementById('formapago').value);
		var condicionvta=parseInt(document.getElementById('condventa').value);
		var medioentrega=parseInt(document.getElementById('medioentrega').value);
		var costoentrega=parseInt(document.getElementById('centrega').value);
		var totalabonar=parseInt(document.getElementById('ta').value);
		var netoabonar=document.getElementById('neto').value;
		var ruc=document.getElementById('ruc').value;
		var asignado=parseInt(document.getElementById('asignado').value);
		//controlamos
		
		if (idcliente==0){
			errores=errores+'Debes indicar un cliente. \n'	;	
		}
		if (ruc==''){
			errores=errores+'Debes indicar ruc del cliente. \n'	;	
		}
		if (medioentrega==0){
			errores=errores+'Debes indicar medio de entrega. \n'	;	
		} else {
			if (medioentrega==1){
				//Es un delivery, debems exigir que se asigne a uno de ellos
				if (asignado==0){
					errores=errores+'Debe asignar un delivery. \n'	;	
				}
				if (costoentrega==0){
					errores=errores+'Costo de entrega invalido. \n'	;	
				}
			}
			
		}
		if (condicionvta==0){
			errores=errores+'Debe indicar tipo de venta. \n'	;
			
		}
		if (condicionvta==1){
			if (fpago==0){
				errores=errores+'Debe indicar forma de pago p/ venta. \n'	;
				
			}
		} 
		if (errores!=''){
			alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
		} else {
			document.getElementById('vta').submit();
			
		}
	}
	//PREVENTS ENTER ON BRCODE
	$(document).ready(function(){
		$("#codigo").keydown(function(e){
			if(e.which==17 || e.which==74){
				e.preventDefault();
			}else{
				console.log(e.which);
			}
		})
	});	
	function alertar(titulo,error,tipo,boton){
	swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
	}
	function generico(){
	var generico=<?php echo $genericoruc ?>;
	var genericodv=<?php echo $genericodv ?>;
	var generico=generico+'-'+genericodv;
	document.getElementById('ruc').value=generico;
}
</script>
<script>
function espera(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}
<?php if ($registrado == 'S') { ?>

function asignar(cual){
	var idventa=<?php echo $idventa?>;
	
	
	if (cual==1){
		var loadi = '<div style="background-color:#009900; font-weight:bold; width:180px; color:#FFFFFF; margin:0px auto; text-align:center;">Enviando Impresion...Aguarde</div>';
		var factura=document.getElementById('factun').value;
		if (factura !=''){
			document.getElementById('fac').hidden='hidden';
			
			var parametros='fc='+factura+'&idv='+idventa;
			OpenPage('gest_gen_fc.php',parametros,'POST','impresion',loadi);
		} else {
			alertar('ATENCION: Algo salio mal.','Debe ingresar numero de factura aser asignado. ','error','Lo entiendo!');	
		}
	} else {
		document.getElementById('tic').hidden='hidden';
		var sucu=<?php echo $idsucursal?>;
		var pe=<?php echo $pe?>;
		var e=<?php echo $idempresa?>;
		parametros='vta='+idventa+'&s='+sucu+'&pe='+pe+'&e='+e;
		$("#impresion").delay(200).queue(function(n) { 
		$.ajax({
               type: "POST",
                 url: "gest_gen_tk.php",
                 data: parametros,
                 dataType: "html",
                 error: function(){
                       alert("error petición ajax");
                 },
                  success: function(data){                                                      
                             r=$("#impresion").html(data);       
							 if (document.getElementById('ocrec')){
								 var re=(document.getElementById('ocrec').value);
								 document.getElementById('recibo').value=re;
								 espera(2000);
								 envia(2);
							  } else {
								document.getElementById('recibo').value='';
								
							  }
							 n();		
				  }
                             
                  });
		
		 });
	}
	
}



function envia(cual){
	if (cual==1){
		
		
		
		
		
	} else {
		var rec=document.getElementById('recibo').value;
		if (rec!=''){
			var loadi = '<div style="background-color:#009900; font-weight:bold; width:180px; color:#FFFFFF; margin:0px auto; text-align:center;">Enviando Impresion...Aguarde</div>';
			var idventa=<?php echo $idventa?>;
			var idcliente=<?php echo $idcliente ?>;
			var razon=<?php echo "'$razon'" ?>;
			var fact=<?php echo "'$fac'" ?>;
			//var rec=<?php echo "'$rec'" ?>;
			var fechaventa=<?php echo $fechaventa ?>;
			var tipoventa=<?php echo $tipoventa ?>;
			
			var totalventa=<?php echo $totalventa ?>;
			var totaldescuento=<?php echo $totaldescuento ?>;
			var totaliva10=<?php echo $totaliva10?>;
			var totaliva5=<?php echo $totaliva5?>;
			var totalex=<?php echo $totalex ?>;
			var otros=<?php echo $otros ?>;
			var track=<?php echo $trackid ?>;
			var ruch=<?php echo $ruchacienda ?>;
			var dv=<?php echo $dv ?>;
			var formapago=<?php echo $formapago ?>;
			var dt=<?php echo "'$arraycuerpo'" ?>;
			var quien=<?php echo "'$quien'" ?>;
	
		
		
		//var parametros='vta='+idventa+'&s='+sucu+'&pe='+pe+'&e='+e;
		//OpenPage('gest_gen_tk.php',parametros,'POST','impresion',loadi);
			
			var parametros='idv='+idventa+'&idc='+idcliente+'&raz='+razon+'&fac='+fact+'&rec='+rec+'&fec='+fechaventa+'&tipoventa='+tipoventa+'&totalv='+totalventa+'&tdesc='+totaldescuento+'&tiva10='+totaliva10+'&tiva5='+totaliva5+'&texe='+totalex+'&track='+track+'&ruch='+ruch+'&dv='+dv+'&fp='+formapago+'&cuerpo='+dt+'&ot='+otros+'&quien='+quien;
			OpenPage('http://localhost/impresorweb/',parametros,'POST','impresion',loadi);
		} else {
			alert('vacio');
			
		}
		//OpenPage('http://localhost/impresorweb/',parametros,'POST','impresion',loadi);
	}
}
<?php } ?>

</script>
<script src="js/sweetalert.min.js"></script>
<script src="js/comprobarprod.js"></script>
<script src="js/comprobarclie.js"></script>
 <link rel="stylesheet" type="text/css" href="css/sweetalert.css">

</head>
<body bgcolor="#FFFFFF">
<?php require("includes/cabeza.php"); ?>    
<div class="clear"></div>
<div class="cuerpo">
 <div align="center" >
 <?php require_once("includes/menuarriba.php");?>
</div>

<div class="colcompleto" id="contenedor">
 	<!-- SECCION DONDE COMIENZA TODO -->
    <br /><br />
    <div align="center">
                    <table width="197" border="0">
                      <tbody>
                        <tr>
                          <td width="62"><a href="index.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a></td>
                          <td width="62"><a href="gest_editar_ventas.php"><img src="img/1455915556_file.png" width="64" height="64" title="Editar Fecha de Venta"/></a></td>
                          <td width="59"><a href="gest_resumen_ventas.php"><img src="img/estd.png" width="64" height="64" title="Resumen de Ventas"/></a></td>
                        </tr>
                      </tbody>
                	</table>
  </div>
    <div class="divstd">
   	  <span class="resaltaditomenor">
    	Ud se encuentra Administrando Ventas
       
   	  </span>
    </div><br />
   <?php  if ($registrado != 'S') {?>
    <div class="resumenmini">
     <br />
       <span class="resaltaditomenor"> IDT: <?php echo $idt ?></span>
    	<table width="400" border="1">
          <tbody>
            <tr>
              <td width="62"><img src="img/bar-code.png" width="64" height="64" alt=""/></td>
              <td colspan="2" align="left">
              	<input type="text" class="barcode" name="codigo" id="codigo" placeholder="Ingrese C&oacute;digo de Barras" onkeyup="cambio()" />
              </td>
              <td width="55"><a href="#login-popup"><img src="img/buscar1.png" width="40" height="40" title="Buscar Poductos" /></a>
              <input type="hidden" name="idlog" id="idlog" value="<?php echo $nivelacceso ?>" />
              <input type='hidden' name='dispo' id='dispo' value="" />
              </td>
            </tr>
          </tbody>
      </table>
           <div id="mensa">
           
           
           
           </div>
          

        <br />
    </div>
    <br />
    <div align="center" id="tmproductos">
    	<?php require_once('tmp_productos.php'); ?>
    
    </div>
    <?php } else { 	?>
    
   	<table width="200" border="1px">
    <tr>
        	<td height="39" colspan="2" align="center" bgcolor="#E3E3E3"><strong>Impresi&oacute;n de Documentos</strong></td>
      </tr>
    	<tr>
        	<td height="30" align="center" bgcolor="#E3E3E3"><strong>Factura N&uacute;mero <br />
        	  <span class="resaltarojomini">(Ingrese Numero y presione la impresora)</span></strong></td>
            <td align="center" bgcolor="#E3E3E3"><strong>Recibo N&uacute;mero<span class="resaltarojomini"> (Presione la Impresora para generar)</span></strong></td>
      </tr>
        <tr>
        	<td><input type="text" name="factun" id="factun" value="" /><td><input type="text" name="recibo" id="recibo" value="" />
            
        </tr>
    	<tr>
        	<td align="center"><a href="javascript: void(0);" onclick="asignar(1)" ><img id="fac" src="img/1458502603_printer.png" width="64" height="64" title="Imprimir Factura" /></a></td>
            <td align="center"><a href="javascript: void(0);" onclick="asignar(2); "><img id="tic" src="img/1458502603_printer.png" width="64" height="64" title="Imprimir Tickete" /></a></td>
        </tr>
    
    </table>
    <br />
    <div class="resumenmini"><br /><span class="resaltaverde">
    Venta efectuada correctamente!<br />Presiona el bot&oacute;n para efectuar nueva venta</span>
      <a href="gest_ventas.php" target="_self"><img src="img/ok012801.png" width="32" height="32" Title=" Presione aqu&iacute; para realizar otra venta"/></a>
    </div>
    <div align="center" id="impresion"></div>
   <?php } ?>
   <br />
   <div align="center">
   <?php echo $log; ?>
   
   </div>
    <!---------------------------------------------------OC------------------------------->
    <div id="login-popup" class="white-popup login-popup mfp-hide">
    
   	<?php require_once('minilogin.php') ?>
    </div>
   
</div> <!-- contenedor -->
  <script>
  
        $(function mag() {
            $('a[href="#login-popup"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: false
            });
            
        }); 
 
 		function buscar(cual){
			var texto=cual.trim();
			var parametros='texto='+texto;
			OpenPage('buscaprod.php',parametros,'POST','encontrados','pred');
			
			
		}
    </script>
	<script src="js/jquery.magnific-popup.min.js"></script>

   <div class="clear"></div><!-- clear1 -->
</div> <!-- cuerpo -->
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
</body>
</html>


 