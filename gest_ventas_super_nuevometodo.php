 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "30";
require_once("includes/rsusuario.php");
$montodelivery = 0;

//Comprobar apertura de caja en fecha establecida

$buscar = "Select * from caja_super where date(fecha)=current_date and cajero=$idusu";
$rscaja = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$idcaja = intval($rscaja->fields['idcaja']);
$estadocaja = intval($rscaja->fields['estado_caja']);

if ($idcaja == 0) {
    echo "<div align='center'>
        <strong>Atenci&oacute;n $cajero<br /></strong>
    Debe efectuar su apertura antes de vender. <a href='gest_administrar_caja.php' target='_self'>Ingrese aqu&iacute;
    </a>
    </div>";
    exit;
}
if ($estadocaja == 3) {

    echo "<div align='center'>
        <strong>Atenci&oacute;n $cajero<br /></strong>
    Su caja se encuentra cerrada. <a href='gest_administrar_caja.php' target='_self'>Ingrese aqu&iacute;
    </a>
    </div>";
    exit;

}





/*---------------------HECHAUKA--------------------------*/
$genericoruc = '44444401';
$genericodv = '7';
$generico = $genericoruc.'-'.$genericodv;

/*------------------FIN -HECHAUKA-GENERICO---------------------*/
//Post de Registro

//Comprobamos por seguridad que existan al menos un prod para la venta
$buscar = "Select * from productos where disponible > 0 limit 1";
$rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tp = $rst->RecordCount();
if ($tp == 0) {
    ?>
<div align="center">
<a href="index.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/></a><br />
    ATENCION: <span class="resaltarojomini">Los productos para la venta no han sido registrados en el sistema.</span>
</div>
<?php exit;
}
$imprimir = 0;
/*---------------------------------------NUEVO POST PARA VENTAS V3---------------------------------*/
if (isset($_POST['fin']) && intval($_POST['fin']) > 0) {


    //Transaccion
    $idtransaccion = intval($_POST['fin']);
    $formapago = intval($_POST['formapago']);
    //CRE-CON
    $condventa = intval($_POST['condventa']);
    //Cliente
    $idcliente = intval($_POST['clientesel']);
    //descuento si es que usan (no aplica por ahora)
    $desc = floatval($_POST['desc']);
    //Medio de Entrega (no aplica por ahora)
    $mentrega = intval($_POST['medioentrega']);

    $ruc = antisqlinyeccion($_POST['ruch'], 'text');
    $rucgra = antisqlinyeccion($_POST['ruch'], 'text');
    $ruc = str_replace("'", "", $ruc);
    $explota = explode("-", $ruc);
    $ruc = intval($explota[0]);
    $dv = intval($explota[1]);
    //tipo de impresion
    $tipoimpre = intval($_POST['tipodocusele']);
    $anterior = intval($idtransaccion);



    //Metodo de imresion,factura o tickete
    $metodo = $tipoimpre;

    if ($tipoimpre == 1) {
        //TK
        $sucuca.$puntoex;
        $tk = buscartickete($idsucursal, $pe, $idempresa);
        $update = "update lastcomprobantes set numtk=numtk+1 where idsuc=$idsucursal and idempresa=$idempresa";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        $factura = '';
        $sucuca = '';
        $puntoex = '';
    } else {
        //FAC
        $factura = intval($_POST['nf']);
        $sucuca = trim($_POST['suc']);
        $puntoex = trim($_POST['pe']);
        if ($factura > 0) {
            $tmp = $sucuca.$puntoex.$factura;
            $a = strlen($tmp);
            if ($a < 13) {
                //completamos
                $dife = 13 - $a;
                $meio = '';
                for ($i = 1;$i <= $dife;$i++) {
                    $meio = $meio.'0';

                }
                $cabeza = $sucuca.$puntoex;
                $cuerpo = $meio.$factura;
                $factura = $cabeza.$cuerpo;
            } else {
                $factura = $sucuca.$puntoex.$factura;


            }

        }
    }


    //Cabecera temporal
    $buscar = "Select * from tmpventa where idtran=$idtransaccion";
    $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $buscar = "Select * from tmpventadeta where idtfk=$idtransaccion";
    $rsdet = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $delivery = 0;//no aplica por ahora
    //Sumamos los valores vendidos
    $buscar = "Select sum(subtotal) as totalvendido,sum(descnetogs) as netodesc from tmpventadeta where idtfk=$idtransaccion";
    $rstv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

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

    if ($formapago == 1) {
        //efectivo
        $banco = 0;
        $efectivo = $totalvendido;
        $numerotrans = 0;
        $numcheque = 0;
        $montotransferido = 0;
        $montocheque = 0;
        $montotarjeta = 0;
        $numtarjeta = 0;
    } else {
        if ($formapago == 2) {
            //tarjeta
            $banco = intval($_POST['banco']);
            $efectivo = 0;
            $numerob = intval($_POST['numerob']);
            $numerotrans = 0;
            $numcheque = 0;
            $montotransferido = 0;
            $montocheque = 0;
            $montotarjeta = $totalvendido;
            $numtarjeta = $numerob;
        }
        if ($formapago == 3) {
            //transferencia
            $banco = intval($_POST['banco']);
            $numerob = intval($_POST['numerob']);
            $efectivo = 0;
            $numerotrans = $numerob;
            $montotransferido = $totalvendido;
            $montocheque = 0;
            $montotarjeta = 0;
            $numtarjeta = 0;

        }
        if ($formapago == 4) {
            //cheque
            $banco = intval($_POST['banco']);
            $numerob = intval($_POST['numerob']);
            $efectivo = 0;
            $numerotrans = 0;
            $montotransferido = 0;
            $montocheque = $totalvendido;
            $montotarjeta = 0;

        }
    }

    $buscar = "Select max(idventa) as mayor from ventas where idempresa=$idempresa";
    $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idventa = intval($rsm->fields['mayor']) + 1;

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

    //Armamos el TOtal a CObrar, basados en el total de la venta+delivery si existe..se omite el descuento, ya que el sub-total ya es con desc incluido
    $totalcobrar = $totalvendido + $delivery;

    //Si se va utilizar o agregar el input de monto recibido, cambiar esto para tomar del POST (VER TAMBIEN LOS MEDIOS DE PAGO)
    $totalrecibido = $totalcobrar;

    //Crear cuenta si es credito
    if ($condventa == 2) {

        //Credito
        $buscar = "Select max(idcta) as mayor from cuentas_clientes where idempresa=$idempresa";
        $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $mayor = intval($rs1->fields['mayor']) + 1;

        //Generamos la cuenta

        $insertar = "
        Insert into cuentas_clientes 
        (idcta,idempresa,sucursal,deuda_global,saldo_activo,idcliente,estado,registrado_el,registrado_por,idventa)
        values
        ($mayor,$idempresa,$idsucursal,$totalcobrar,$totalcobrar,$idcliente,1,current_timestamp,$idusu,$idventa)
        ";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    }
    //Cobranza efectuada

    /*----------------GEST PAGOS-------------------------*/
    $insertar = "Insert into gest_pagos
        (idcliente,fecha,medio_pago,total_cobrado,chequenum,banco,numtarjeta,montotarjeta,
          factura,recibo,tickete,ruc,tipo_pago,idempresa,sucursal,efectivo,codtransfer,montotransfer,montocheque,cajero,idventa)
          values
          ($idcliente,current_timestamp,$formapago,$totalrecibido,$numcheque,$banco,$numtarjeta,$montotarjeta,
        '$factura','','$recibo',$rucgra,$condventa,$idempresa,$idsucursal,$efectivo,
        $numerotrans,$montotransferido,$montocheque,$idusu,$idventa)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    /*---------------------VENTAS*------------------------*/
    $insertar = "Insert into ventas
    (fecha,idcliente,tipo_venta,idempresa,sucursal,factura,recibo,ruchacienda,dv,
    total_venta,idtransaccion,idventa,trackid,registrado_por,totaliva10,totaliva5,texe,idpedido,otrosgs,descneto,deliv,totalcobrar,tipoimpresion,vendedor)
    VALUES
    (current_timestamp,$idcliente,$condventa,$idempresa,$idsucursal,'$factura','$tk',$ruc,$dv,    
    $totalvendido,$idtransaccion,$idventa,$compuesto,$idusu,$iva10,$iva5,$tventaex
    ,0,$delivery,$totaldescontado,'$quien',$totalcobrar,$tipoimpre,$idusu)";
    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    //Contado
    if ($condventa == 1) {
        $update = "Update ventas set total_cobrado=(total_cobrado+$totalcobrar),estado=3 where idventa=$idventa";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    }
    //Detalles de la venta
    while (!$rsdet->EOF) {

        $cantidad = floatval($rsdet->fields['cantidad']);
        $precioventa = floatval($rsdet->fields['precioventa']);
        $subtotal = intval($rsdet->fields['subtotal']);
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


        $update = "update gest_depositos_stock_gral set disponible=disponible-$cantidad where idproducto='$idprod'";
        $conexion->Execute($update) or die(errorpg($conexion, $update));


        $insertar = "Insert into ventas_detalles
        (cantidad,pventa,subtotal,idventa,idemp,sucursal,idprod,pchar,costo,utilidad,iva,descuento,p1,p2,p3)
        values
        ($cantidad,$precioventa,$subtotal,$idventa,$idempresa,$idsucursal,'$idprod','$pchar',$costo,$utilidad,$iva,$desc,$p1,$p2,$p3)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        //Actualizar costos y STOCK
        $update = "Update productos set disponible=(disponible-$cantidad) where idprod='$idprod'";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //Ahora costos
        $buscar = "Select idseriepkcos as serial from costo_productos where id_producto='$idprod' and disponible > 0 order by vencimiento asc limit 1";
        $rscosto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idserial = intval($rscosto->fields['serial']);

        if ($idserial > 0) {

            $buscar = "select * from costo_productos where idseriepkcos=$idserial";
            $rscosto = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            //Cuanto hay en costos de ese producto

            $dispocosto = floatval($rscosto->fields['disponible']);
            //Caso 1: Venta menor al  disponible en costos
            $log = $log."Caso 1: Ver si el dispo en costos es superior o igual a la cantidad vendida<br />";
            if ($cantidad <= $dispocosto) {
                $update = "Update costo_productos set disponible=(disponible-$cantidad) where idseriepkcos=$idserial and id_producto='$idprod'";
                $conexion->Execute($update) or die(errorpg($conexion, $update));

            } else {
                //La cantidad vendida supera al costo existente
                //Hallamos la diferencia
                $diferencia = $cantidad - $dispocosto;
                $log = $log."Vendido: $cantidad - Dispo en costos: $dispocosto. Diferencia=$diferencia<br />";
                //Actualizamos la disponibilidad inmediata en costos
                $update = "Update costo_productos set disponible=0 where idseriepkcos=$idserial and id_producto='$idprod'";
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

                    $tmca = floatval($rscosto->fields['disponible']);
                    $idscc = intval($rscosto->fields['idseriepkcos']);
                    if ($diferencia <= $tmca) {
                        $log = $log."Lo que falta completar, es inferior a la cant disponible en costos<br />";
                        $update = "Update costo_productos set disponible=(disponible-$diferencia) where idseriepkcos=$idscc
                         and id_producto='$idprod'";
                        $conexion->Execute($update) or die(errorpg($conexion, $update));
                        $diferencia = 0;
                        $log = $log."Actualizamos y salimos del ciclo: dif=$diferencia - $update<br />paso: $c<br />";
                    } else {
                        $update = "Update costo_productos set disponible=0 where idseriepkcos=$idscc
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


    }
    //Borramos los detalles temporales
    $delete = "Delete from tmpventadeta where idtfk=$idtransaccion";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));



    //cabecera
    $update = "update  tmpventa set estado=3 where idtran=$idtransaccion";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //Marcamos la transaccion
    $update = "update  transacciones set estado=3 where numero=$idtransaccion and idusu=$idusu";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    $imprimir = 1;

    if ($imprimir == 1) {
        //Preparamos archivo para impresion
        //cabecera
        $buscar = "Select * from ventas where idventa=$idventa";
        $rscab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        //Detalles
        $buscar = "Select * from ventas_detalles where idventa=$idventa";
        $rsdeta = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $fac = $rscab->fields['factura'];
        $rec = $rscab->fields['recibo'];
        $idcliente = intval($rscab->fields['idcliente']);

        $buscar = "Select * from cliente where idcliente=$idcliente";
        $rscli = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $docu = $rscli->fields['documento'];
        $razon = trim($rscli->fields['razon_social']);
        $dire = $rscli->fields['direccion'];
        $telfo = $rscli->fields['celular'];

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
        $tipoimpre = $rscab->fields['tipoimpresion'];
        $dv = $rscab->fields['dv'];
        $formapago = intval($rscab->fields['formapago']);
        $quien = trim($rscab->fields['deliv']);

        $arraycuerpo = '';
        while (!$rsdeta->EOF) {

            $pchar = trim($rsdeta->fields['pchar']);
            $precioventa = floatval($rsdeta->fields['pventa']);
            $cantidad = floatval($rsdeta->fields['cantidad']);

            $p1 = floatval($rsdeta->fields['p1']);
            $p2 = floatval($rsdeta->fields['p2']);
            $p3 = floatval($rsdeta->fields['p3']);
            $iva = intval($rsdeta->fields['iva']);
            $subtotal = floatval($rsdeta->fields['subtotal']);
            $descuento = floatval($rsdeta->fields['descuento']);



            $concat = $pchar.'}'.$precioventa.'}'.$cantidad.'}'.$p1.'}'.$p2.'}'.$p3.'}'.$descuento.'}'.$iva.'}'.$subtotal;
            $arraycuerpo = $arraycuerpo.$concat.'}';


            $rsdeta->MoveNext();
        }

    }

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
            //$buscar="Select max(numero) as mayor from transacciones";
            //$rst=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
            //$idt=intval($rst->fields['mayor'])+1;
            //reservamos
            /*
            $insertar="Insert into transacciones
            (idempresa,numero,estado,sucursal,idcliente,fecha,tipo,idusu)
            values
            ($idempresa,$idt,1,1,0,current_timestamp,3,$idusu)";
            */
            //cambiamos por el Auto Incremental
            $insertar = "Insert into transacciones
            (idempresa,estado,sucursal,idcliente,fecha,tipo,idusu)
            values
            ($idempresa,1,1,0,current_timestamp,3,$idusu)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            //Buscamos el generado
            $buscar = "Select max(numero) as mayor from transacciones where idusu=$idusu";
            $may = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idt = intval($may->fields['mayor']);

            //echo $idt;
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

//preferencisd

$buscar = "Select * from preferencias";
$rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$autorizar = intval($rsp->fields['autorizar']);


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<!-------------<link rel="stylesheet" href="css/bootstrap.css" type="text/css" media="screen" /> ---------->
<link rel="stylesheet" href="css/magnific-popup.css" type="text/css" media="screen" /> 
<script src="js/jquery-1.9.1.js"></script>
<?php require("includes/head.php"); ?>
<script>
    function filtrar(){
        var buscar=document.getElementById('blci').value;
        var parametros='bus='+buscar;
        OpenPage('gest_cliev4venta.php',parametros,'POST','clientereca','pred');
    }
    function activar(cual){
        espera(1000);
        document.getElementById('clientesel').value=parseInt(cual);
        var parametros='mini='+cual;
        OpenPage('bcliemin4.php',parametros,'POST','adicio','pred');
        document.getElementById('adicio').hidden='';
        setTimeout(function(){ enfocar(); }, 300);
        
    }
    function busprod(){
        var buscar=document.getElementById('prodbus').value;
        var parametros='bus='+buscar;
        OpenPage('gest_listaproducto.php',parametros,'POST','lpr','pred');
    }
    function precio(cual){
        if (cual!=''){
            document.getElementById('tipoprecio').value=parseInt(cual);
            
        } else {
            document.getElementById('tipoprecio').value=0;
            
        }
        
        
    }
    function buscliente(valor){
        if (valor!=''){
            var parametros='bus='+valor;
            OpenPage('gest_listaproducto.php',parametros,'POST','lpr','pred');
        }
    }
    function seleccionar(valor){
        var idp=valor;
        var parametros='idp='+valor;
        OpenPage('gest_listaproducto.php',parametros,'POST','lpr','pred');
        
        
    }
    function seleccionarproducto(){
        var errores='';
        var fecha=document.getElementById('fecha').value;
        var nf=document.getElementById('nf').value;
        var sucu=document.getElementById('suc').value;
        var pe=document.getElementById('pe').value;
        if (fecha==''){
            errores=errores+'* Debe indicar fecha de factura . \n';
            
        }
        if ((sucu=='') || (pe=='') || (nf=='')){
            errores=errores+'* Debe indicar numeracion de factura . \n';
        }
        var idtra=<?php echo $idt?>;
        var cantidad=document.getElementById('cantidad').value;
        var idp=document.getElementById('lproductos').value;
        var tipoprecio=parseInt(document.getElementById('tipoprecio').value);
        var idcliente=parseInt(document.getElementById('clientesel').value);
        if (idcliente==0){
            errores=errores+'* Debe indicar cliente . \n';
            
        }
        if (idp==''){
            errores=errores+'* Debe seleccionar producto. \n';
        }
        if (cantidad==''){
            errores=errores+'* Debe indicar cantidad a vender . \n';
        }    
        
        if (tipoprecio==0){
            errores=errores+'* Debe indicar tipo de precio a utilizar . \n';
            
        }
        var tipoven=parseInt(document.getElementById('condventa').value);
        if (tipoven==0){
            errores=errores+'* Debe indicar condicion de la venta . \n';
        }
        var pago=parseInt(document.getElementById('formapago').value);
        if (pago==0){
            errores=errores+'* Debe indicar forma pago . \n';
        }
        if (errores==''){
            var parametros='idp='+idp+'&tp=1&ca='+cantidad+'&idtransaccion='+idtra+'&tipoprecio='+tipoprecio+'&idc='+idcliente;
            OpenPage('tmp_productosv_super.php',parametros,'POST','tmproductos','pred');
        } else {
            alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');    
            
        }
    }
    function seleccionarp(valor1,valor2,valor3){
        var desc=valor3;
        var idp=valor1;
        var dis=valor2;
        
        
        if ((idp=='') || (dis=='')){
            alertar('ATENCION: Algo sali� mal.','Debe seleccionar un producto.','error','Lo entiendo!');    
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
                    OpenPage('tmp_productosv_super.php',parametros,'POST','tmproductos','pred');        
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
                        OpenPage('tmp_productosv_super.php',parametros,'POST','tmproductos','pred');
                    } else {
                        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
                        
                    }
                }
                
            } else {
                alertar('ATENCION: Algo salio mal.','Debe indicar precion Intermedio entre 1 y 2 ','error','Lo entiendo!');
                
            }
            
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
            if (valor >30)    {
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
        
            document.getElementById('centrega').value=<?php echo intval($montodelivery)?>;
            document.getElementById('neto').value=(netov+<?php echo intval($montodelivery)?>)-descontar;
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
            errores=errores+'Debes indicar un cliente. \n'    ;    
        }
        if (ruc==''){
            errores=errores+'Debes indicar ruc del cliente. \n'    ;    
        }
        if (medioentrega==0){
            errores=errores+'Debes indicar medio de entrega. \n'    ;    
        } else {
            if (medioentrega==1){
                //Es un delivery, debems exigir que se asigne a uno de ellos
                if (asignado==0){
                    errores=errores+'Debe asignar un delivery. \n'    ;    
                }
                if (costoentrega==0){
                    errores=errores+'Costo de entrega invalido. \n'    ;    
                }
            }
            
        }
        if (condicionvta==0){
            errores=errores+'Debe indicar tipo de venta. \n'    ;
            
        }
        if (condicionvta==1){
            if (fpago==0){
                errores=errores+'Debe indicar forma de pago p/ venta. \n'    ;
                
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
//Asigna FAC o TK
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
                       alert("error petici�n ajax");
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



<?php } ?>
<?php if ($imprimir == 1) {?>
function envia(cual){
    if (cual==1){
        //factura a generar
        var rec='';
        
        if (rec==''){
            var loadi = '';
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
            var quien='<?php echo "$cajero" ?>';
            var idtra=<?php echo $anterior?>;
            var tipoimpre=<?php echo $tipoimpre?>;
            var dire=<?php echo "'$dire'"?>;
            var telfo=<?php echo "'$telfo'"?>;
            
        var parametros='central=4&idv='+idventa+'&idc='+idcliente+'&raz='+razon+'&fac='+fact+'&rec='+rec+'&fec='+fechaventa+'&tipoventa='+tipoventa+'&totalv='+totalventa+'&tdesc='+totaldescuento+'&tiva10='+totaliva10+'&tiva5='+totaliva5+'&texe='+totalex+'&track='+track+'&ruch='+ruch+'&dv='+dv+'&fp='+formapago+'&cuerpo='+dt+'&ot='+otros+'&quien='+quien+'&idtrans='+idtra+'&tipoimpresion='+tipoimpre+'&direccion='+dire+'&telfo='+telfo;
        
        
            OpenPage('http://localhost/impresorweb/',parametros,'POST','impresion',loadi);
        } 
        
        
        
    } else {
        
            var loadi = '';
            var idventa=<?php echo $idventa?>;
            var idcliente=<?php echo $idcliente ?>;
            var razon=<?php echo "'$razon'" ?>;
            var fact=<?php echo "'$fac'" ?>;
            var rec=<?php echo "'$rec'" ?>;
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
            var quien='<?php echo "$cajero" ?>';
            var idtra=<?php echo $anterior?>;
            var tipoimpre=<?php echo $tipoimpre?>;
            
        var parametros='central=3&idv='+idventa+'&idc='+idcliente+'&raz='+razon+'&fac='+fact+'&rec='+rec+'&fec='+fechaventa+'&tipoventa='+tipoventa+'&totalv='+totalventa+'&tdesc='+totaldescuento+'&tiva10='+totaliva10+'&tiva5='+totaliva5+'&texe='+totalex+'&track='+track+'&ruch='+ruch+'&dv='+dv+'&fp='+formapago+'&cuerpo='+dt+'&ot='+otros+'&quien='+quien+'&idtrans='+idtra+'&tipoimpresion='+tipoimpre;
            OpenPage('http://localhost/impresorweb/',parametros,'POST','impresion',loadi);
        
    }
}
<?php }?>
</script>
<script type="text/javascript">
//----------------------------------------------CONTROL DE TECLAS---------------------------------------------------//
var eventoControlado = false;
/* $( document ).ready(function() {
   $(document).keypress= mostrarInformacionCaracter;
}); */


function mostrarInformacionCaracter(evObject) {
    //alert('llega');
    var elCaracter = String.fromCharCode(evObject.which);
    var abierto=parseInt(document.getElementById('abierto').value);
    
    if (evObject.which!=0 && evObject.which!=13) {
        //ver si no es mas
        if (elCaracter=='+'){
            document.getElementById('sumar').value=1;
            document.getElementById('cantidad').value='';
            document.getElementById('cantidad').focus();
        } else {
            //no es mas, ver si es un numero
            if (isNaN(elCaracter)){
                // Es texto, por seguridad
                document.getElementById('sumar').value=0;
                if (elCaracter=='l'){
                    var dc=document.activeElement.name;
                    if (dc!='bcli'){
                        if (dc=='prodbus'){
                            
                            //document.getElementById('prodbus').focus();
                        }
                        if ((dc!='nombreclie') && (dc!='filtrarprodtexto') && (abierto==0)){
                            document.getElementById('prodbus').value='';
                            setTimeout(function(){ abrepop(); }, 50);
                            //document.getElementById('prodbus').focus();
                        }
                        
                        
                        
                    }
                            
                }
                if (elCaracter=='t'){
                    //ciudar el enfoque
                    var abierto=parseInt(document.getElementById('abierto').value);
                    var dc=document.activeElement.name;
                    if(dc==''){
                        if (document.getElementById('vl1')){
                            document.getElementById('vl1').click(); 
                        } else {
                            var errores='No existen productos a cobrar. \n';
                            alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
                        }
                    } else {
                        if(dc=='prodbus'){
                            if (document.getElementById('vl1')){
                                document.getElementById('vl1').click(); 
                            } else {
                                var errores='No existen productos a cobrar. \n';
                                alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
                            }
                        } else {
                            if((abierto==0) && (dc!='filtrarprodtexto')){
                                if (document.getElementById('vl1')){
                                    document.getElementById('vl1').click(); 
                                } else {
                                    var errores='No existen productos a cobrar. \n';
                                    alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
                                }
                            }
                        }
                    }
                }
                
            } else {
                //es numero, si es mayor a cero, sumar, sino agregar una
                if (evObject.which==13) {
                    var nu=parseInt(elCaracter);
                    
                    if (document.getElementById('sumar').value==1){
                        document.getElementById('cantidad').value=nu;
                        document.getElementById('sumar').value=0;
                        setTimeout(function(){ enfocar(); }, 300);
                    }
                }
            }
        }
     } else  {
            if (evObject.which==13) {            
                //aca enviamos el post, solo si no es cantidad
                var dc=document.activeElement.name;
                if (dc=='prodbus'){
                    //alert('si');
                    var errores='';
                    var auto=<?php echo $autorizar?>;
                    var fecha='';//document.getElementById('fecha').value;
                    var nf=document.getElementById('nf').value;
                    var sucu=document.getElementById('suc').value;
                    var pe=document.getElementById('pe').value;
                    var tipodocu=parseInt(document.getElementById('tipodocusele').value);
                    if (tipodocu==0){
                        document.getElementById('prodbus').value='';
                        errores=errores+'* Debe indicar tipo de documento a usar . \n';
                        
                        
                    }
                    if (fecha==''){
                        //errores=errores+'* Debe indicar fecha de factura . \n';
                        
                    }
                    if ((sucu=='') || (pe=='') || (nf=='')){
                        //errores=errores+'* Debe indicar numeracion de factura . \n';
                    }
                    var idtra=<?php echo $idt?>;
                    var cantidad=document.getElementById('cantidad').value;
                    
                    var idp=document.getElementById('prodbus').value;
                    var tipoprecio=1;
                    //parseInt(document.getElementById('tipoprecio').value);
                    var idcliente=parseInt(document.getElementById('clientesel').value);
                    if (idcliente==0){
                        errores=errores+'* Debe indicar cliente . \n';
                        
                    }
                    if (idp==''){
                        errores=errores+'* Debe seleccionar producto. \n';
                    }
                    if (cantidad==''){
                        //si ahy un idproducto, se agrega 1
                        cantidad=1;
                        //errores=errores+'* Debe indicar cantidad a vender . \n';
                    }    
                    
                    if (tipoprecio==0){
                        errores=errores+'* Debe indicar tipo de precio a utilizar . \n';
                        
                    }
                    var tipoven=parseInt(document.getElementById('condventa').value);
                    if (tipoven==0){
                        errores=errores+'* Debe indicar condicion de la venta . \n';
                    }
                    var pago=parseInt(document.getElementById('formapago').value);
                    if (pago==0){
                        errores=errores+'* Debe indicar forma pago . \n';
                    }
                    if (errores==''){
                        var parametros='idp='+idp+'&tp=1&ca='+cantidad+'&idtransaccion='+idtra+'&tipoprecio='+tipoprecio+'&idc='+idcliente+'&auto='+auto+'&nf='+nf+'&tipodocu='+tipodocu;
                        OpenPage('tmp_productosv_super.php',parametros,'POST','tmproductos','pred');
                        document.getElementById('cantidad').value='';
                        setTimeout(function(){ reca(); }, 300);
                        setTimeout(function(){ enfocar(); }, 300);
                    } else {
                        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');    
                        
                    }
                } else {
                    //no es prodbus, es cantidad
                    if (evObject.which==13) {
                        var nu=parseInt(document.getElementById('cantidad').value);
                        
                        if (document.getElementById('sumar').value==1){
                            document.getElementById('cantidad').value=nu;
                            document.getElementById('sumar').value=0;
                            setTimeout(function(){ enfocar(); }, 300);
                        }
                    }    
                    
                }

            }
    }
    eventoControlado=true;
}
function reca(){
    if(document.getElementById('totlaventaf')){
        var tv=document.getElementById('totlaventaf').value;
        document.getElementById('tventa').value=tv;
    }
}
function mostrarInformacionTecla(evObject) {
                    var msg = ''; var teclaPulsada = evObject.keyCode;
                    if (teclaPulsada == 20) { msg = 'Pulsado caps lock (act/des may�sculas)';}
                    else if (teclaPulsada == 16) { msg = 'Pulsado shift';}
                    else if (eventoControlado == false) { msg = 'Pulsada tecla especial';}
                    if (msg) {control.innerHTML += msg + '-----------------------------<br/>';}
                    eventoControlado = false;
}
//----------------------------------------FIn -CONTROL DE TECLAS---------------------------------------------------//
//------------------------------Eliminar temporal---------------------------------------------------------------------//
function eliminar(reg){
    var td=document.getElementById('tipodocusele').value;    
    
        if (reg > 0){
            var idtra=<?php echo $idt?>;
            var auto=<?php echo $autorizar?>;
            var nf=document.getElementById('nf').value;
            
            var parametros='tp=3&idtransaccion='+idtra+'&reg='+reg+'&auto='+auto+'&nf='+nf;
            OpenPage('tmp_productosv_super.php',parametros,'POST','tmproductos','pred');        
            setTimeout(function(){ enfocar(); }, 300);
            setTimeout(function(){ llenar(); }, 400);
        }
        
}
//-------------------------------ASIGNAR POPUS-----------------------------------------------//
function popupasigna(){
         $(function mag() {
            $('a[href="#pop1"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
            setTimeout(function(){ enfo(1); }, 300);
        });    
        
        
}

function popupasignabb(){
        $(function mag() {
            $('a[href="#pop2"]').magnificPopup({
                type:'inline',
                midClick: false,
                closeOnBgClick: true
                
            });
            
        });    
        
}
//-----------------------------ENFOQUES-------------------------------------------------------//

function enfocar(){
    document.getElementById('prodbus').value='';
    document.getElementById('prodbus').focus();
}
function enfo(cual){
    if (cual==1){
        document.getElementById('nombreclie').focus();
    }
    
}    

function enfoquenuevo(){
    document.getElementById('prodbus').focus();    
    
}
<!------ESTOS FUNCIONAN JUNTOS------>
function abrepop(){
    document.getElementById('enlace2').click();
    setTimeout(function(){ enfoquenuevo(); }, 80);
}

function lprodu(){
    var parametros='';
    OpenPage('gest_miniprod.php',parametros,'POST','pop2','pred');
    setTimeout(function(){ popupasignabb(); }, 50);
    
}
<!------------------------------------------>
function asignarpop(reg){
    var parametros='idr='+reg;
    OpenPage('gest_autorizarem.php',parametros,'POST','pop1','pred');
    setTimeout(function(){ popupasigna(); }, 50);
    
}
function asignarv(){
    var parametros='';
    OpenPage('gest_minic4.php',parametros,'POST','pop1','pred');
    setTimeout(function(){ document.getElementById('abierto').value=1; }, 20);
    setTimeout(function(){ popupasigna(); }, 20);
    
}
function nclie(){
    var errores='';
    var nombres=document.getElementById('nombreclie').value;
    
    var apellidos=document.getElementById('apellidos').value;
    var docu=0;
    var ruc=document.getElementById('ruccliente').value;
    var direclie=document.getElementById('direccioncliente').value;
    var telfo=document.getElementById('telefonoclie').value;
    
    if (nombres==''){
        errores=errores+'Debe indicar nombres del cliente. \n';
    }
    if (apellidos==''){
        errores=errores+'Debe indicar apellidos del cliente. \n';
    }
    if (docu==''){
        //errores=errores+'Debe indicar documento del cliente. \n';
    }
    if (ruc==''){
        errores=errores+'Debe indicar documento del cliente o ruc generico. \n';
    }
    if (errores==''){
        var parametros='n=1&nom='+nombres+'&ape='+apellidos+'&dc='+docu+'&ruc='+ruc+'&dire='+direclie+'&telfo='+telfo;
        OpenPage('gest_cliev3venta.php',parametros,'POST','clientereca','pred');
        
        setTimeout(function(){ cerrar(1); }, 100);

    } else {
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
        
    }
    
}
function sel(prod){
    if (prod !=''){
        document.getElementById('prodbus').value='';
        document.getElementById('prodbus').value=prod;
        setTimeout(function(){ cerrar(1); }, 100);
        //setTimeout(function(){ enfocar(); }, 100);
        document.getElementById('prodbus').onkeypress=13;
        //var e = $.Event( "keypress", { which: 13 } );
        //$('#prodbus').trigger(e);
        //($('#prodbus').event.keycode==13){
                
        //setTimeout(function(){ enfocar(); }, 100);
        
    }
    
}
function cerrar(n){
    if (n==1){
         $.magnificPopup.close();
            
    }
    
}
function validar(tipo,numero){
    var cod=document.getElementById('codauto').value    ;

    var parametros='codauto='+cod+'&tp='+tipo;
    //OpenPage('gest_codauto.php',parametros,'POST','res','pred');
    $("#res").delay(200).queue(function(n) { 
        $.ajax({
               type: "POST",
                 url: "gest_codauto.php",
                 data: parametros,
                 dataType: "html",
                 error: function(){
                       alert("error petici�n ajax");
                 },
                  success: function(data){                                                      
                             r=$("#res").html(data);       
                             if (document.getElementById('resok')){
                                var resul=parseInt(document.getElementById('resok').value);
                                if (resul > 0){
                                    //autorizamos
                                    document.getElementById('autorizar_'+numero).innerHTML="<a href='javascrip:void(0);' onClick='eliminar("+numero+")'><img src='img/no.PNG' width='16' height='16' title='Eliminar Producto'/></a>";
                                    setTimeout(function(){ cerrar(1); }, 100);
                                } else {
                                    //no autoriza    
                                    
                                }
                              } 
                             n();        
                  }
                             
                  });
        
         });
    
}
function tipodocu(cual){
    document.getElementById('tipodocusele').value=parseInt(cual);    
    if (cual==1){
        //tk
        document.getElementById('tk').disabled="disabled";
        document.getElementById('fc').disabled="";
        document.getElementById('nf').value="";
        document.getElementById('nf').readOnly="readOnly";
        document.getElementById('metodo').value=1;
        setTimeout(function(){ enfocar(); }, 300);
    }
    if (cual==2){
        //fc
        document.getElementById('nf').readOnly="";
        document.getElementById('nf').value="";
        
        document.getElementById('tk').disabled="";
        document.getElementById('fc').disabled="disabled";
        document.getElementById('metodo').value=2;
        setTimeout(function(){ enfocar(); }, 300);
    }
}
function mediopago(medio){
    if (medio >1){
        document.getElementById('mediopagotr').innerHTML='';
        document.getElementById('mediopagotr').hidden='';
        var parametros='medio='+medio;
        OpenPage('mini_clase.php',parametros,'POST','mediopagotr','pred');
        
    } else {
        document.getElementById('mediopagotr').innerHTML='';
        document.getElementById('mediopagotr').hidden='hidden';
        
    }
    
}

function vuelto(monto){
    if (monto!=''){
        var monto=parseInt(monto);
        if (monto > 0){
            if (document.getElementById('totlaventaf')){
                var totalventa=parseInt(document.getElementById('totlaventaf').value);
                if (totalventa > 0){
                    if (parseInt(monto) >=totalventa){
                        var dife=parseInt(monto-totalventa);
                        document.getElementById('vueltogs').value=dife;
                    } else {
                        document.getElementById('vueltogs').value='0';
                    }
                }
            } else {
                document.getElementById('vueltogs').value='0';
                
            }
    } else 
        document.getElementById('vueltogs').value='0';
        
    }
    

}

function llenar(){
    if (document.getElementById('totlaventaf')){
        var nf=document.getElementById('totlaventaf').value;
        document.getElementById('tventa').value=nf;
    
    }
}
function terminar(modo){
    var modo=document.getElementById('tdocuoc').value;
    var errores='';
    document.getElementById('metodo').value=modo;
    var tipodocu=parseInt(document.getElementById('tipodocusele').value);    
    if (tipodocu==0){
        errores=errores+'* Debe indicar tipo de documento a usar \n';
        
    } else {
        if (tipodocu==2){
            //si es factura, debe introducir el numero    
            var nf=document.getElementById('nf').value;
            if (nf==''){
                errores=errores+'* Debe indicar numero de factura \n';
            }
        }
    }
    //Conrolamos de nuevo antes de enviar la venta
    var totalventa=parseInt(document.getElementById('totlaventaf').value);
    var montorecibe=document.getElementById('montorecibe').value;
    if (montorecibe==''){
            errores=errores+'Debe indicar Monto recibido.';
    } else {
        //comprar los monos
        if (montorecibe < totalventa){
            errores=errores+'Monto recibido es inferior. Verifique';
        } else {
            var dife=parseInt(montorecibe-totalventa);
            document.getElementById('vueltogs').value=dife;
        
        }
    }
        
    
    if (errores==''){
        
        document.getElementById('regventa').submit();
    } else {
        document.getElementById('montorecibe').focus();
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');    
        
    }

        //
}
function filtrarproducto(texto){
    if (texto!=''){
        var parametros='texto='+texto;
        OpenPage('gest_minibuscar.php',parametros,'POST','lprtest','pred');
        
    }
    
}

</script>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
 <link rel="stylesheet" href="css/magnific-popup.css">
<script>
function operaEvento(evento){
   $("#loescrito").html($("#loescrito").html() + evento.type + ": " + evento.which + ", ")
}
$(document).ready(function(){
   $(document).keypress(mostrarInformacionCaracter);
   //$(document).keydown(operaEvento);
   //$(document).keyup(operaEvento);
})
</script>
</head>
<body bgcolor="#FFFFFF" style="background-color:#FFFFFF" onLoad="<?php if (intval($imprimir) == 1) {?> <?php if ($metodo == 1) {?>envia(2);<?php } else {?>envia(1);<?php }
} ?>activar(1);">
<div class="clear"></div>
<a href="#pop2" id="enlace2" onClick="lprodu();" hidden="hidden">fff</a>
<div id="impresion">
        
</div>
<div id="loescrito"></div>
<!------------------------------TIPO DOCUMENTO------------------------------------------->
<div style="width:80%; margin-left:auto; margin-right:auto; border:0px solid #000000; height:150px;style="background-color:#FFFFFF"">
    <!-------------------------BOTONES------------->
    <div style="height:130px; border:0px solid #21FF71; width:10%; float:left">
        <input type="button" name="tk" id="tk" value="Tickete" onClick="tipodocu(1);" style="background-color:#FF0004; color:#FFFFFF; height:30px; width:100%" />
        
         <input type="button" name="fc" id="fc" value="Factura"  onclick="tipodocu(2);" style="height:30px; width:100%" />
         <br /><br />
         <a href="gest_reimpresion.php" target="_blank"><input type="button" name="fc" id="fc" value="Reimpresion"  style="height:30px; width:100%; background-color:#84FCB7" /></a>
         <a href="gest_administrar_caja.php" target="_self"><input type="button" name="fc" id="fc" value="Mi caja"   style="height:30px; width:100%" /></a>
    </div>
    <!---------------------DATOS FACTURA /CLIENTE------------>
    <form id="regventa" name="regventa" method="post" action="gest_ventas_super.php">
    <div style="float:left; width:52%; border:0px solid #111010; height:130px;">
         <div align="center">
              <select id="condventa" name="condventa" style="height:25px;">
                    <option value="1" selected="selected" >CONTADO</option>
                    <option value="2">CREDITO</option>
              </select>
               <input type="text" name="suc" id="suc" style="width:30px; height:20px;" value="001" />
               <input type="text" name="pe" id="pe" style="width:30px;height:20px;" value="001" />
               <input type="text" name="nf" id="nf" style="width:80px;height:20px;" value="" />   
                 <select id="formapago" name="formapago" onChange="mediopago(this.value)" style="height:25px;">
                    <option value="1" selected="selected" >EFECTIVO</option>
                    <option value="2">TARJETA</option>
                    <option value="3">TRANSFERENCIA</option>
                    <option value="4">CHEQUE</option>
                </select>  
                <input type="hidden" name="fin" id="fin" value="<?php echo $idt?>" />
            <input type="hidden" name="metodo" id="metodo" value="0" /> 
               <input type="hidden" name="clientesel" id="clientesel" value="0" />
                             <input type="hidden" name="sumar" id="sumar" value="0" />
                             <input type="hidden" name="tipodocusele" id="tipodocusele" value="0"  />               
         </div>
         <div id="adicio" hidden="hidden">
         
         </div>
    </div>
    </form>
    <!----------------FILTRO CLIENTES------------------------->
    <div style="float:right; width:37%; border:0px solid #FFF502">
      
         <input type="text" name="bcli" id="blci" onKeyUp="filtrar()" placeholder="Filtrar clientes" style="width:80%; height:40px;" /><a href="#pop1" onMouseOver="asignarv();" title="Registrar Nuevo"><img src="img/02p64.png" width="30" height="30" alt=""/>  </a>  <input type="hidden" name="abierto" id="abierto" value="0"/>
                    <table width="100%" border="0">
                            <tbody>
                             <tr  id="buscl">
                                <td colspan="2">
                                     <div id="clientereca">
                                        <?php require_once("gest_cliev4venta.php"); ?>
                        
                                    </div>    
                                </td>
                             
                             </tr>
                            </tbody>
                    </table>
    </div>
    
</div>
<!---------------------------------------------------------------------------------------->
<div style="width:100%;min-height:600px; border: 0px solid color:#4B1AF0; border-bottom-style:double; border-top-style:double; background-color:#FFFFFF; border-left-style:groove " id="centro">
     
    <!-------------------------------PANEL IZQ-------------------------------------------->
     <div style=" width:60%; border:0px solid #F50004; min-height:500px; float:left">
         <div align="center">
                <span class="resaltaditomenor"> IDT: <?php echo $idt ?> / CAJERO: <?php echo $cajero ?></span>
         </div>
        <div align="center">
            
            <input type="text" name="prodbus" id="prodbus" placeholder="Escanear / ingresar codigo del Producto"  
                style="width:60%; height:40px;"  />
                <input type="text" name="cantidad" id="cantidad" value=""  style="height:40px;" placeholder="Cantidad" width="20%" />
        </div>
         <div id="tmproductos" style="background-color:#FFFFFF; min-height:200px;">
            <?php require_once('tmp_productosv_super.php')?>
        </div>
     </div>
    <!------------------------------PANEL DERECHO------------------------------------------>
     
     <div class="totalsuper" style="width:30%;" >
        <div align="left" style="color:#F8090D; font-size:36px;">
        <table width="100%">
            <tr>
            <td width="40%" align="right">Total Gs </td>
            <td width="1%" align="right"> </td>
            <td width="50%"><input name="tventa" id="tventa" style="height:45px;font-size:30px; color:#20B409; border:0px; width:80%;" value="0"   /></td>
          </tr>
        </table>
        </div>
         <div align="left" style="font-size:28px;">
              <table width="100%">
            <tr>
            <td width="40%" align="right"><strong>Recibido</strong> </td>
            <td width="1%" align="right"> </td>
            <td width="50%"><input name="montorecibe" id="montorecibe" style="height:45px; width:80%;font-size:30px; color:#20B409; "  onkeyup="vuelto(this.value);" /></td>
          </tr>
          
        </table>
       </div>
       <div align="left" style="color:#F8090D; font-size:28px;">
              <table width="100%">
            <tr>
            <td width="40%" align="right">Vuelto  </td>
            <td width="1%" align="right"> </td>
            <td width="50%"><input name="vueltogs" id="vueltogs" style="height:45px; width:80%;font-size:30px; border:0px; " value="0" /></td>
          </tr>
          <tr>
              <td colspan="3" align="center"><input type="button" value="Terminar" id="vl1"  onClick="terminar()" /></td>
          </tr>
        </table>
        <br />
        
       </div>
     </div>
     <div class="totalsuper" style="width:30%;" >
       <table width="100%">
    <tr>
        <td colspan="2" align="center"><img src="img/1444616944_info.png" width="32" height="32" alt=""/><br /> Teclas r&aacute;pidas</td>
         </tr>
    <tr>
        <td width="25%" height="37" align="center">
          
        <strong>L</strong></td>
        <td width="75%" align="left">Listado de Productos c/ Precio </td>
      </tr>
        <tr>
            <td height="35" align="center">
          <strong>T</strong></td>
            <td align="left"> Finalizar Venta - Imprime </td>
           </tr>
    </table>
    </div>
</div> <!-- cuerpo -->
    <div id="pop1" class="mfp-hide" style="background-color:#F9F7F7; width:400px; height:auto; margin-left:auto; margin-right:auto;">
        
         </div>
        <div id="pop2" class="mfp-hide" style="background-color:#F9F7F7; width:600px; height:auto; margin-left:auto; margin-right:auto;">
        
         </div>
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
<div class="clear"></div><!-- clear2 -->
<?php require("includes/pie.php"); ?>
<script>
lprodu();
llenar();
</script>
</body>
</html>


 
