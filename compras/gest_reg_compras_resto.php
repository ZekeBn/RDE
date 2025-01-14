<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$dirsup = 'S';
$modulo = "1";
$submodulo = "31";
require_once("../includes/rsusuario.php");

header("location: gest_reg_compras_resto_new.php");
exit;

//Tipo de compra por defecto
$buscar = "select tipocompra from preferencias where idempresa=$idempresa";
$rstc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$tipoc = intval($rstc->fields['tipocompra']);
//echo $tipoc;



/*-----------------------------------------CANCELAR COMPRA-----------------------------*/
if (isset($_POST['chau']) && ($_POST['chau'] > 0)) {
    $borrar = intval($_POST['chau']);
    $delete = "Delete from tmpcompras where idtran=$borrar and idempresa = $idempresa";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
    $delete = "Delete from tmpcompradeta where idt=$borrar  and idemp = $idempresa";
    $conexion->Execute($delete) or die(errorpg($conexion, $delete));
    //marcamos la transaccion
    $update = "Update transacciones set estado=3 where numero=$borrar  and idempresa = $idempresa";
    $conexion->Execute($update) or die(errorpg($conexion, $update));
}
$idt = intval($_POST['idt']);






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

if ($idt == 0) {
    //No vino por un post, por lo cual puede ser una transaccion abierta, la cual deben finalizarla antes de seguir
    $buscar = "Select min(numero) as mayor from transacciones_compras where estado=1 and idempresa=$idempresa and idusu=$idusu ";
    $rst = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $numt = intval($rst->fields['mayor']);
    // busca si existe en compras pero por algun motivo no se marco como utilizado
    $buscar = "Select idtran from compras where idtran = $numt";
    $rsexc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $ext_compras = intval($rsexc->fields['idtran']);
    //echo $ext_compras;
    //exit;
    // si existe en compras
    if ($ext_compras > 0) {
        // marca como utilizado
        $consulta = "
		update transacciones_compras set estado = 3 where numero = $numt
		";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        //Generamos un Id de transaccion
        $buscar = "Select max(numero) as mayor from transacciones_compras  ";
        $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idtransaccion = intval($rsm->fields['mayor']) + 1;

        //Reservamos
        $insertar = "Insert into 
		transacciones_compras
		(idempresa,numero,estado,sucursal,idusu)
		values
		($idempresa,$idtransaccion,1,$idsucursal,$idusu)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
        $numt = $idtransaccion;
    }

    //obtenido el valor
    if ($numt == 0) {
        //Generamos un Id de transaccion
        $buscar = "Select max(numero) as mayor from transacciones_compras  ";
        $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idtransaccion = intval($rsm->fields['mayor']) + 1;

        //Reservamos
        $insertar = "Insert into 
		transacciones_compras(idempresa,numero,estado,sucursal,idusu)
		values
		($idempresa,$idtransaccion,1,$idsucursal,$idusu)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    } else {
        $idtransaccion = $numt;
        //Traemos los datos para mostrar
        $buscar = "Select * from tmpcompras where idtran=$idtransaccion  and idempresa = $idempresa ";
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

    }

} else {
    $idtransaccion = $idt;
}

//echo $tipocompra;

//*-------------------------AGREGAR TMP---------------------------------*/

if (isset($_POST['idt']) && ($_POST['idt'] > 0)) {

    $errores = '';
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
        $buscar = "Select * from proveedores where borrable = 'N' and idempresa = $idempresa and estado = 1";
        $rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $prov_interno = intval($rsprov->fields['idproveedor']);
        $incrementa = trim($rsprov->fields['incrementa']);

        //Buscamos la factura
        $buscar = "Select * from compras where facturacompra=$facompra and 
		idproveedor=$provee and idempresa = $idempresa and estado=1";

        $controla = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        if ($controla->fields['facturacompra'] == '') {

            //Tomamos el id del insumo,

            $insumo = intval($_POST['insuoc']);
            $cantidad = antisqlinyeccion($_POST['cantioc'], 'float');
            $costo = antisqlinyeccion($_POST['pcom'], 'float');

            if ($insumo == 0) {
                $errores = $errores."* Debe indicar insumo a comprar.<br />";

            }

            if ($cantidad == 'NULL') {
                //$errores=$errores."* Debe indicar cantidad comprada.<br />";
                $cantidad = 0;

            }
            // si no es proveedor interno el precio debe ser mayor a 1
            if ($_POST['proveedor'] != $prov_interno) {
                if (!(floatval($_POST['pcom']) > 0)) {
                    //$errores=$errores."* Debe indicar precio de compra.<br />";

                }
            }


            //aca agregamos las conversiones
            // busca en tabla de conversioens si hay el producto sino iguala
            $consulta = "
			select * 
			from compras_conversion 
			where 
			idproducto_origen = $insumo 
			and estado <> 6
			order by idconversioncompra desc
			limit 1
			";
            //echo $consulta;
            $rsc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($rsc->fields['idconversioncompra'] > 0) {
                //echo "si";
                //exit;
                $idproducto_conv = $rsc->fields['idproducto_destino'];

                // en la bd
                $consulta = "
				select idmedida, tipoiva from insumos_lista where idinsumo = $idproducto_conv
				";
                $rs2 = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $iva_compra_porc = $rs2->fields['tipoiva'];
                // para conversion
                $cantidad_destino = floatval($rsc->fields['cantidad_destino']);
                $cantidad_origen = floatval($rsc->fields['cantidad_origen']);
                $subtotal = $costo * $cantidad; //del producto origen

                // calculos de conversiones
                $cantidad_conv = round(($cantidad * $cantidad_destino) / $cantidad_origen, 3);
                $precio_conv = round($subtotal / $cantidad_conv, 0);
                $subtotal_conv = $cantidad_conv * $precio_conv;
                $idmedida_conv = $rs2->fields['idmedida'];

            } else {
                $cantidad_conv = $cantidad;
                $precio_conv = $costo;
                $subtotal_conv = $cantidad_conv * $precio_conv;
                $idmedida_conv = $idmedida;
                $idproducto_conv = $insumo;
            }
            $cantidad = $cantidad_conv;
            $costo = $precio_conv;
            $subt = $costo * $cant;
            $insumo = $idproducto_conv;

            $buscar = "Select  * from insumos_lista where idinsumo=$insumo and idempresa = $idempresa";
            $rsex = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            if (intval($rsex->fields['idinsumo']) == 0) {
                $errores = $errores."* Codigo de articulo inexistente.<br />";
            }

            if ($errores == '') {

                // datos del insumo convertido
                $buscar = "Select  * from insumos_lista where idinsumo=$insumo and idempresa = $idempresa";
                $rsde = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $pchar = antisqlinyeccion($rsde->fields['descripcion'], 'text');
                $tipoiva = intval($rsde->fields['tipoiva']);
                $idconcepto = antisqlinyeccion($rsde->fields['idconcepto'], 'int');

                //Vemos si la cabecera ya esta registrada, sino la registramos
                $buscar = "Select * from tmpcompras where idtran=$idtransaccion  and idempresa = $idempresa";
                $rsctm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $idtt = intval($rsctm->fields['idtran']);

                // si no esta registrada la cabecera
                if ($idtt == 0) {
                    $insertar = "Insert into tmpcompras 
						(idtran,fechahora,idempresa,sucursal,totalcompra,facturacompra,fecha_compra,
						tipocompra,proveedor,moneda,cambio,vencimiento,timbrado,vto_timbrado,facturacompra_incrementa,monto_factura)
						values
						($idtransaccion,'$ahora',$idempresa,1,0,$facompra,$fecompra,$tipocompra,$provee,$moneda,$cambio
						,$vencimientofac,$timbrado,$timbradovenc,$facturacompra_incrementa,$monto_factura)";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                } else {
                    // si esta registrada la cabecera
                    $update = "
					update tmpcompras
					set
					facturacompra=$facompra,
					fecha_compra=$fecompra,
					tipocompra=$tipocompra,
					proveedor=$provee,
					vencimiento=$vencimientofac,
					timbrado=$timbrado,
					vto_timbrado=$timbradovenc,
					monto_factura = $monto_factura
					where
					idtran = $idtransaccion
					and idempresa = $idempresa
					";
                    $conexion->Execute($update) or die(errorpg($conexion, $update));
                }

                //Buscamos si el producto ya existe en el temporal
                $buscar = "Select * from tmpcompradeta where idprod=$insumo  and idemp = $idempresa and idt=$idtt";
                $rr = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                if ($rr->fields['idprod'] == '') {
                    $costo2 = 0;

                    if ($costo < 0) {
                        $subt = $costo;

                    } else {
                        $subt = $cantidad * $costo;

                    }

                    //No existe en tmp e  insertamos
                    $insertar = "Insert into tmpcompradeta
						(idprod,idconcepto,idemp,cantidad,costo,pchar,sucursal,existe,idt,subtotal,iva,categoria,subcate,precioventa,preciomin,
						preciomax,listaprecios,medida,p1,p2,p3,costo2)
						values
						($insumo,$idconcepto,$idempresa,$cantidad,$costo,$pchar,1,1,$idtransaccion,$subt,$tipoiva,0,0,0,0,
						0,'',0,$costo,0,0,0)"	;
                    //echo $insertar;exit;
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


                }


            }
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
        $delete = "Delete from tmpcompradeta where idregcc=$borrar  and idemp = $idempresa";
        $conexion->Execute($delete) or die(errorpg($conexion, $delete));
    }
}
/*--------------------------------FINAL POST DELETAR----------------------------*/

/*--------------------------------POST registrar compra----------------------------*/
if (isset($_POST['tran']) && ($_POST['tran'] > 0)) {
    $idt = intval($_POST['tran']);

    if ($idt > 0) {
        //Generamos la compra
        $buscar = "Select * from tmpcompras where idtran=$idt  and idempresa = $idempresa ";
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


        // validaciones
        if ($monto_factura != $totalcompra) {
            $valido = "N";
            $errores .= "- El Monto de Factura no coincide con la sumatoria de productos cargados, favor verifique.<br />";
        }


        // validaciones si es a credito
        if ($tipocompra == 2) {
            if ($monto_cuota_venc <= 0) {
                $valido = "N";
                $errores .= "- Debe cargar los vencimientos cuando la factura es a credito.<br />";
            }
            if ($monto_cuota_venc != $monto_factura) {
                $valido = "N";
                $errores .= "- El monto de factura no coincide con la sumatoria de vencimientos, favor verifique.<br />";
            }
            // toma el primer vencimiento como vencimiento de factura
            $vencimientofac = antisqlinyeccion($vencimientomin, 'date');

        }

        //Buscamos la factura
        $buscar = "Select * from compras where facturacompra=$factura and 
		idproveedor=$idprov and idempresa = $idempresa and estado=1";
        $controla = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $registradofac = date("d/m/Y H:i:s", strtotime($controla->fields['registrado']));
        if (trim($controla->fields['facturacompra']) != '') {
            $valido = "N";
            $errores .= "- Factura duplicada, la factura que intentas cargar ya fue cargada el: $registradofac.<br />";
        }

        // buscar si ya existe factura
        $consulta = "
		Select * 
		from facturas_proveedores  
		where 
		id_proveedor=$idprov 
		and factura_numero=$factura
		and estado <> 6
		limit 1
		";
        $rscon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        if ($rscon->fields['factura_numero'] != '') {
            $valido = "N";
            $errores .= " La factura Numero: $factura ya se encuentra registrada y activa para el proveedor seleccionado.";
        }

        if ($valido == 'S') {


            //Buscamos total de iva
            $buscar = "Select  sum(subtotal) as tcompra10 
			from tmpcompradeta where idt=$idt  and idemp = $idempresa and iva=10";
            $rsiva10 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $tiva10 = intval($rsiva10->fields['tcompra10']);
            $buscar = "Select  sum(subtotal) as tcompra5 
			from tmpcompradeta where idt=$idt  and idemp = $idempresa and iva=5";
            $rsiva5 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $buscar = "Select  sum(subtotal) as exe 
			from tmpcompradeta where idt=$idt  and idemp = $idempresa and iva=0";
            $rsivaex = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            $iva10 = intval($rsiva10->fields['tcompra10'] / 11);
            $iva5 = intval($rsiva5->fields['tcompra5'] / 21);

            $excenta = intval($rsivaex->fields['exe']);



            $moneda = intval($rscabecera->fields['moneda']);
            if ($moneda == 0) {
                $moneda = 1;

            }
            $cambio = floatval($rscabecera->fields['cambio']);


            $buscar = "Select max(idcompra) as mayor from compras";
            $rsmay = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idcompra = intval($rsmay->fields['mayor']) + 1;
            //Registramos
            $insertar = "Insert into compras 
			(	
			idtran,idcompra,idproveedor,sucursal,idempresa,fechacompra,facturacompra,registrado_por,total,iva10,
			iva5,exenta,registrado,tipocompra,moneda,cambio,vencimiento,timbrado,vto_timbrado,facturacompra_incrementa,
			ocnum
			)
			values
			(
			$idt,$idcompra,$idprov,$idsucursal,$idempresa,'$fechacompra',$factura,$idusu,$totalcompra,
			$iva10,$iva5,$excenta,'$ahora',$tipocompra,$moneda,$cambio,$vencimientofac,$timbrado,$timbradovenc,$facturacompra_incrementatmp,
			$ocnum
			)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            //Deposito de compras
            $tipolegal = 1; //NO ME ACURDO PARA QUE SE USABA
            //De inmediato, insertamos en el deposito para ser procesado por el encargado, antes de cualquier cosa
            $insertar = "
			 insert into gest_depositos_compras 
			 (fecha_compra,idproveedor,factura_numero,registrado_por,tipo,idcompra,fechareg,idempresa,fecha_revision)
			 values
			 ('$fechacompra',$idprov,$factura,$idusu,$tipolegal,$idcompra,'$ahora',$idempresa,'$ahora')";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));



            //Lista la cabecera seguimos con los detalles
            while (!$rscuerpo->EOF) {

                $idp = antisqlinyeccion($rscuerpo->fields['idprod'], 'text');
                $cant = $rscuerpo->fields['cantidad'];
                $costo = intval($rscuerpo->fields['costo']);
                $costo2 = intval($rscuerpo->fields['costo2']);
                $subtotal = intval($rscuerpo->fields['subtotal']);
                $tipoiva = intval($rscuerpo->fields['iva']);
                $idconcepto = antisqlinyeccion($rscuerpo->fields['idconcepto'], "int");
                //Vemos si el producto existe pero en el stock global

                $buscar = "Select * from productos_stock_global where idproducto=$idp  and idempresa = $idempresa";
                $rsbuscar = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $ee = trim($rsbuscar->fields['idproducto']);
                if ($ee == '') {
                    //no existe y registramos
                    $insertar = "Insert into productos_stock_global (idproducto,disponible,tipo,idempresa) values ($idp,$cant,1,$idempresa) ";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                    //tabla de costos

                    //almacenamos en costos
                    $inserta = "insert into costo_productos 
					(idempresa,id_producto,registrado_el,precio_costo,idproveedor,cantidad,numfactura,
					costo2,disponible,idcompra,fechacompra)
					values
					($idempresa,$idp,'$ahora',$costo,$idprov,$cant,$factura,$costo2,
					$cant,$idcompra,'$fechacompra')";
                    $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

                } else {
                    //ya existe y updateamos
                    $update = "update productos_stock_global set disponible=(disponible+$cant) where idproducto=$idp  and idempresa = $idempresa";
                    $conexion->Execute($update) or die(errorpg($conexion, $update));

                    //almacenamos en costos
                    $inserta = "insert into costo_productos 
					(idempresa,id_producto,registrado_el,precio_costo,idproveedor,cantidad,numfactura,
					costo2,disponible,idcompra,fechacompra)
					values
					($idempresa,$idp,'$ahora',$costo,$idprov,$cant,$factura,$costo2,$cant,$idcompra,'$fechacompra')";
                    $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

                }

                //registramos el detalle de la compra
                $insertar = "Insert into compras_detalles
				(idcompra,idconcepto,codprod,cantidad,costo,idtrans,costo2,subtotal,idempresa,iva)
				values
				($idcompra,$idconcepto,$idp,$cant,$costo,$idt,$costo2,$subtotal,$idempresa,$tipoiva)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


                // actualizamos el ultimo costo en insumos, puede no ser de este registro de compras por eso usamos por fecha desc
                $consulta = "
				update insumos_lista 
				set costo = 
						COALESCE((
						SELECT compras_detalles.costo
						FROM compras
						inner join compras_detalles on compras_detalles.idcompra = compras.idcompra
						where 
						compras_detalles.codprod = $idp
						and compras.idempresa = $idempresa
						order by compras.fechacompra desc
						limit 1
						),0)
				where
				idinsumo = $idp
				and idempresa = $idempresa
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                /*
                global
                update insumos_lista set costo = COALESCE(( SELECT compras_detalles.costo FROM compras inner join compras_detalles on compras_detalles.idcompra = compras.idcompra where compras_detalles.codprod = insumos_lista.idinsumo order by compras.fechacompra desc limit 1 ),0)
                where costo <= 0
                */

                $rscuerpo->MoveNext();
            }
            //Crear cuenta a credito
            //if ($tipocompra==2){
            //credito
            $buscar = "select * from compras where idcompra=$idcompra";
            $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

            $bc = $buscar = "Select max(idcta) as mayor from cuentas_empresa";
            $rsm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $mayor = intval($rsm->fields['mayor']) + 1;
            $idcta = $mayor;

            $factura = antisqlinyeccion($rs->fields['facturacompra'], 'text');
            $fechacompra = antisqlinyeccion($rs->fields['fechacompra'], 'date');
            $iva10 = floatval($rs->fields['iva10']);
            $iva5 = floatval($rs->fields['iva5']);
            $ex = floatval($rs->fields['exenta']);
            $totalc = floatval($rs->fields['total']);
            $idprov = intval($rs->fields['idproveedor']);

            // credito
            if ($tipocompra == 2) {
                $tipo = 1;
            }
            // contado
            if ($tipocompra == 1) {
                $tipo = 2;
                $vencimientofac = "NULL";
            }


            $insertar = "Insert into cuentas_empresa 
						(idcta,facturanum,fechacompra,totalcompra,totaliva10,totaliva5,totalex,registrado_por,registradoel,
						idproveedor,saldo_activo,estado,clase,idempresa,factura_venc,tipo,idcompra)
						values
						($mayor,$factura,$fechacompra,$totalc,$iva10,$iva5,$ex,$idusu,'$ahora',
						$idprov,$totalc,1,1,$idempresa,$vencimientofac,$tipo,$idcompra)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));


            //}


            // para generar cuentas version nueva
            $consulta = "
			INSERT INTO facturas_proveedores
			(idcompra, tipo_factura, id_proveedor, fecha_compra, fecha_carga, usuario_carga, factura_numero, fecha_valida, validado_por, total_factura, total_iva10, total_iva5, total_exenta, anulado_por, anulado_el, vencimiento_factura, estado, total_iva, estado_carga, timbrado, vtotimbrado, saldo_factura, cobrado_factura, quita_factura, iddeposito) 
			select idcompra, tipocompra tipo_factura, idproveedor as id_proveedor, fechacompra as fecha_compra, registrado as fecha_carga, registrado_por as usuario_carga, facturacompra as factura_numero, NULL as fecha_valida, NULL as validado_por, total as total_factura, 0 as total_iva10, 0 as total_iva5, 0 as total_exenta, NULL as anulado_por, NULL as anulado_el, vencimiento as vencimiento_factura, 1 as estado, iva10+iva5 as total_iva, 3 as estado_carga, timbrado, vto_timbrado as vtotimbrado, total as saldo_factura, 0 as cobrado_factura, 0 as quita_factura, 0 as iddeposito 
			from compras 
			where 
			estado <> 6
			and idcompra = $idcompra
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            $consulta = "
			update facturas_proveedores 
			set 
			fact_num = CAST(substring(factura_numero from 7 for 9) as UNSIGNED)
			where 
			fact_num is null
			and idcompra = $idcompra
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            // credito
            if ($tipocompra == 2) {
                // operaciones_proveedores
                $consulta = "
				INSERT INTO operaciones_proveedores
				(idcta, idproveedor, idfactura, monto_factura, abonado_factura, quita_factura, saldo_factura, estado, fecha_factura, fecha_cancelacion, fecha_ultimopago, fecha_prox_vencimiento, saldo_atrasado, idperiodo, plazo_periodo, plazo_periodo_remanente, plazo_periodo_abonado, dias_atraso, max_atraso, prom_atraso, monto_cuota) 
				select idcta, idproveedor, 
				(select id_factura from facturas_proveedores where facturas_proveedores.idcompra = cuentas_empresa.idcompra and estado <> 6)  as idfactura, 
				totalcompra as monto_factura, 0 as abonado_factura, 0 as quitafactura, totalcompra as saldo_factura, 1 as estado, fechacompra as fecha_factura,
				NULL as fecha_cancelacion, NULL as fecha_ultimopago, factura_venc as fecha_prox_vencimiento, 0 as saldo_atrasado, 11 as idperiodo, 1 as plazo_periodo, 1 as plazo_periodo_remanente, 0 as plazo_periodo_abonado, 0 as dias_atraso, 0 as max_atraso, 0 as prom_atraso, totalcompra as monto_cuota
				from cuentas_empresa 
				where
				idcompra = $idcompra
				and estado <> 6
				and (select tipo_factura from facturas_proveedores where facturas_proveedores.idcompra = cuentas_empresa.idcompra and estado <> 6) = 2;
				";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $consulta = "
				select idoperacionprov 
				from operaciones_proveedores 
				where 
				idcta = $idcta 
				and estado <> 6
				order by idoperacionprov desc
				limit 1
				";
                $rsop = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idoperacionprov = $rsop->fields['idoperacionprov'];

                // construye detalle
                $consulta = "
				select idvencimiento, idtran, vencimiento, monto_cuota 
				from tmpcompravenc 
				where 
				idtran = $idt
				order by vencimiento asc
				";
                $rsdet = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                $i = 0;
                while (!$rsdet->EOF) {
                    $i++;
                    $monto_cuota = $rsdet->fields['monto_cuota'];
                    $vencimiento = $rsdet->fields['vencimiento'];
                    $consulta = "
					INSERT INTO operaciones_proveedores_detalle
					(idoperacionprov, periodo, monto_cuota, cobra_cuota, quita_cuota, saldo_cuota, vencimiento, fecha_can, fecha_ultpago, dias_atraso, dias_pago, estado_saldo) 
					VALUES 
					($idoperacionprov,$i,$monto_cuota,0,0,$monto_cuota,'$vencimiento',NULL, NULL, 0, NULL, 1)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    $rsdet->MoveNext();
                }

            } // if ($tipocompra==2){


            // ahora solo falta tocar en anular compras y luego en orden de pago y anular orden de pago


            $consulta = "
			select max(id_factura) as id_factura 
			from facturas_proveedores 
			where 
			idcompra = $idcompra 
			and estado <> 6
			";
            $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $id_factura = $rsfac->fields['id_factura'];





            //Por ultimo, marcamos la transaccion
            $update = "Update transacciones_compras set estado=3 where numero=$idt ";
            $conexion->Execute($update) or die(errorpg($conexion, $update));

            //Eliminamos los Temporales
            $delete = "delete from  tmpcompras  where idtran=$idt and idempresa = $idempresa";
            $conexion->Execute($delete) or die(errorpg($conexion, $delete));

            $delete = "delete from  tmpcompradeta  where idt=$idt and idemp = $idempresa";
            $conexion->Execute($delete) or die(errorpg($conexion, $delete));

            $delete = "delete from  tmpcompravenc  where idtran=$idt";
            $conexion->Execute($delete) or die(errorpg($conexion, $delete));

            // inserta detalle de factura
            $consulta = "
			INSERT INTO facturas_proveedores_compras
			(id_factura, 
			idconcepto, idproducto, cantidad, precio, 
			subtotal, idmoneda, lote, vencimiento,  
			estado, monto_iva, iva_porc) 
			select 
				(
				select id_factura from facturas_proveedores 
				where 
				facturas_proveedores.idcompra = compras_detalles.idcompra 
				limit 1
				), 
			idconcepto, compras_detalles.codprod, cantidad, compras_detalles.costo, 
			compras_detalles.subtotal, 1, compras_detalles.lote, compras_detalles.vencimiento, 
			1, 0, compras_detalles.iva
			from compras_detalles
			where
			idcompra = $idcompra
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // calcula el IVA
            $consulta = "
			update  facturas_proveedores_compras
			set 
			monto_iva = (subtotal-((subtotal)/(1+iva_porc/100)))
			where
			id_factura = $id_factura
			";
            $rsfac = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // inserta en conceptos
            $consulta = "
			INSERT INTO cn_conceptos_mov
			(
			idconcepto, codrefer, fecha_comprobante, 
			registrado_el, registrado_por, estado, idconceptomovtipo, 
			year_comprobante, monto_comprobante, iva_comprobante
			)
			select 
			facturas_proveedores_compras.idconcepto, pkss, facturas_proveedores.fecha_compra, 
			facturas_proveedores.fecha_carga, facturas_proveedores.usuario_carga, 1, 1,
			YEAR(facturas_proveedores.fecha_compra), subtotal, facturas_proveedores_compras.monto_iva
			from facturas_proveedores_compras
			inner join facturas_proveedores on facturas_proveedores.id_factura = facturas_proveedores_compras.id_factura
			where
			facturas_proveedores_compras.id_factura = $id_factura
			and facturas_proveedores.idcompra > 0
			and facturas_proveedores_compras.idconcepto is not null
			";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


            header("location: gest_reg_compras_resto.php");
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

$buscar = "Select * from proveedores where idempresa=$idempresa and estado = 1 order by nombre ASC";
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
) as ultcosto
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

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("../includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<?php require("../includes/head.php"); ?>
<link rel="stylesheet" href="css/magnific-popup.css" type="text/css" media="screen" /> 
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
		alertar('ATENCION: Algo sali� mal.','Fecha de compra incorrecta, habilitado entre: <?php echo date("d/m/Y", strtotime($fechadesdebd)); ?> y <?php echo date("d/m/Y", strtotime($fechahastabd)); ?> y no pude ser mayor a hoy <?php echo date("d/m/Y", strtotime($ahora)); ?>.','error','Lo entiendo!');
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
/*function genera_auto(idt){
	var ocnum = $("#ocnum").val();
	if(ocnum > 0){
		document.location.href='gest_reg_compras_resto_gen.php?ocnum='+ocnum+'&idt='+idt;
	}else{
		alert("Error! no indico el numero de orden de compra.");	
	}
	
}*/
function genera_auto(idt){
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
}
</script>
<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">
</head>
<body bgcolor="#FFFFFF">
<?php require("../includes/cabeza.php"); ?>    
<div class="clear"></div>
	<div class="cuerpo">
    	 <div align="center">
     		<?php require_once("../includes/menuarriba.php");?>
    	 </div>
         <div class="colcompleto" id="contenedor">
         <div id="msg"></div>
         	<div align="center">
         	 <span class="resaltaditomenor">
                Registrar Compras
                </span>
                <br />
               </div>
               <div class="resumenmini"><strong>ATENCION</strong>: El control de facturas se encuentra activo. Para que la presente compra ingrese al dep&oacute;sito, como un stock efectivo; la  factura deber&aacute; ser validada por el <strong>admin o encargado </strong>asignado al dep&oacute;sito. </div>
 				<br />
            
          <div align="center"></div>
         <hr />
         
         <br />

           	<div align="center">
				<form id="rc" action="gest_reg_compras_resto.php" method="post">
                    <table width="950" class="tablalinda" border="0">
                        <tbody>
                            <tr>
                                <td colspan="8" align="center"><span class="resaltaazul">Trans: <input type="hidden" name="idt" id="idt" value="<?php echo $idtransaccion ?>"  />
								<input type="hidden" name="insuoc" id="insuoc" value=""  />
								<input type="hidden" name="cantioc" id="cantioc"  />
                                <input type="hidden" name="pcom" id="pcom"  />
								<?php echo $idtransaccion ?></span></td>
                            </tr>
                            <tr>
                                <td width="146" height="30" align="center" bgcolor="#C4C4C4"><strong>Fecha Compra</strong></td>
                                <td width="146" align="center" bgcolor="#C4C4C4"><strong>Monto Factura</strong></td>
                                <td width="172" align="center" bgcolor="#C4C4C4"><strong>Proveedor</strong></td>
                                <td width="65" align="center" bgcolor="#C4C4C4"><strong>Sucursal</strong></td>
                                <td width="65" align="center" bgcolor="#C4C4C4"><strong>Punto Expedici&oacute;n</strong></td>
                                <td width="200" align="center" bgcolor="#C4C4C4"><strong>N&uacute;mero</strong></td>
              
                            </tr>
                            <tr>
                                 <td><input type="date" id="fechacompra" name="fechacompra"  
                                      value="<?php if ($_POST['fechacompra'] != '') {
                                          echo $_POST['fechacompra'];
                                      } else {
                                          if ($listo != 'S') {
                                              echo $fechacompra;
                                          }
                                      }

?>" onchange="validar()" min="<?php echo $fechadesdebd; ?>" max="<?php echo $fechahastabd; ?>"  /></td>
                                 <td>
                                 <input type="text" name="monto_factura" id="monto_factura" style="width:100%; text-align:right;" value="<?php
                                 if ($_POST['monto_factura'] > 0) {
                                     echo intval($_POST['monto_factura']);
                                 } else {
                                     if (intval($monto_factura) > 0) {
                                         echo intval($monto_factura);
                                     }
                                 } ?>" onchange="cabeza();" /></td>
                                 <td><select name="proveedor" id="proveedor" onchange="verifica_factura();" style="width:100%">
                                   <option value="0" selected="selected">Seleccionar</option>
                                   <?php while (!$rsprov->EOF) {

                                       $selected = '';
                                       if (intval($_POST['proveedor']) > 0 && intval($_POST['proveedor']) == intval($rsprov->fields['idproveedor'])) {
                                           $selected = 'selected="selected"';
                                       } elseif ($listo != 'S') {
                                           if ($idprov == intval($rsprov->fields['idproveedor'])) {
                                               $selected = 'selected="selected"';
                                           }
                                       }
                                       // si solo hay un deposito marcarlo
                                       if ($rsprov->RecordCount() == 1) {
                                           $selected = 'selected="selected"';
                                       }
                                       ?>
                                   <option value="<?php echo $rsprov->fields['idproveedor']?>" <?php echo $selected; ?>><?php echo trim($rsprov->fields['nombre']) ?></option>
                                   <?php $rsprov->MoveNext();
                                   }?>
                                 </select></td>
                                <td>
                                <input type="text" name="suc" id="suc" placeholder="Ej: 001" size="7" value="<?php if ($_POST['suc'] != '') {
                                    echo $_POST['suc'];
                                } else {
                                    if ($listo != 'S') {
                                        echo $suc;
                                    }
                                }
?>" onchange="verifica_factura();" style="text-align:right;" /></td>
                                <td><input type="text" name="pex" id="pex" placeholder="Ej: 001" size="7" value="<?php if ($_POST['pex'] != '') {
                                    echo $_POST['pex'];
                                } else {
                                    if ($listo != 'S') {
                                        echo $pex;
                                    }
                                }
?>" onchange="verifica_factura();" style="text-align:right;" /></td>
                                <td><input type="text" name="fa" id="fa" placeholder="Ej: 0001234" size="12" value="<?php if ($_POST['fa'] != '') {
                                    echo $_POST['fa'];
                                } else {
                                    if ($listo != 'S') {
                                        echo $fa;
                                    }
                                }

?>" onchange="verifica_factura();" style="width:100%; text-align:right;"  /></td>
                       
                          </tr>
                      </tbody>
                  </table>
                            
				  <table>
                            <tbody>
                    </tbody>
                  </table>
                    
                            <table width="950">
                            <tbody>
                            <tr>
                            <td height="31" width="200" align="center" bgcolor="#C4C4C4"><strong>Tipo Compra</strong></td>
                            <td width="200" align="center" bgcolor="#C4C4C4" class="tablalinda"><strong>Timbrado</strong></td>
                            <td width="76" align="center" bgcolor="#C4C4C4" class="tablalinda"><strong>Vencimiento Timbrado</strong></td>

                            <td width="82" align="center" bgcolor="#C4C4C4"><strong>Vencimiento Factura</strong></td>
                            <td width="200" rowspan="2" align="center" ><input type="button" value="Actualizar Cabecera" onclick="cabeza()"/>                             </td>
                            </tr>
                            <tr>
                            <td>
                              <?php
                                //Condicionantes para select
                                $tipopost = intval($_POST['tipocompra']);
if ($tipocompra == 0 && $tipoc == 0 && $tipopost == 0) {
    //No hay preferencias, ni tipodecompra x cabecera ni post de seleccion
    $op1 = "selected='Selected'";

}
if ($tipocompra == 1) {
    $op2 = "selected='Selected'";
} else {
    if ($tipocompra == 0 && $tipoc == 1 && $tipopost == 0) {
        $op2 = "selected='Selected'";
    }
}
if ($tipocompra == 2) {
    $op3 = "selected='Selected'";

} else {
    if ($tipocompra == 0 && $tipoc == 2 && $tipopost == 0) {
        $op3 = "selected='Selected'";
    }
}
?>
                                        <select name="tipocompra" id="tipocompra" onchange="recalcular();" style="width:100%">
                                        <option value="" <?php echo $op1;?>>Seleccionar</option>
                                        <option value="1" <?php echo $op2;?>>CONTADO</option>
                                        <option value="2" <?php echo $op3;?>>CREDITO</option>
                                        </select>
                              </td>
                            <td align="center" class="tablalinda"><input type="text" name="timbrado" id="timbrado" size="9" value="<?php echo $timbrado?>" style="text-align:right; width:100%;" /></td>
                            <td align="center" class="tablalinda"><input type="date" name="timbrado_venc" id="timbrado_venc" value="<?php echo $timvto?>"/></td>
                           
                            <td align="center" id="vencefactu"><input type="date" name="factura_venc" id="factura_venc" value="<?php echo $vtofac?>" />                            
                              </tbody>
                    </table><br />
                    <label for="textfield2"></label>
                    <table width="400" border="1">
                      <tr>
                        <td colspan="3" align="center" bgcolor="#C4C4C4"><strong>Generar Automaticamente en Base a Orden de Compra</strong></td>

                      </tr>
                      <tr>
                        <td align="right"><strong>Orden N&ordm;</strong></td>
                        <td><input type="text" name="ocnum" id="ocnum" value="<?php if (isset($_POST['ocnum'])) {
                            echo intval($_POST['ocnum']);
                        } else {
                            echo $ocnum;
                        } ?>" /></td>
                        <td><input name="" type="button" value="Generar" onclick="genera_auto(<?php echo $idtransaccion; ?>);" /></td>
                      </tr>
                    </table>
                    
                    
                    <br />
              </form>
                <br />
                <div id="updatecabeza">
                
                
                </div>
            
            </div>
            <div align="center" id="selecompra">
    			<?php require_once('gesr_fcompras.php')?>
            </div>
            <div class="clear"></div>
            <br />
              <div align="center">
            		<?php require("gestd_fcompras.php"); ?>
            
            </div>
            
            
<div id="pop1" class="mfp-hide" style="background-color:#F9F7F7; width:800px; height:auto; margin-left:auto; margin-right:auto;">
</div>
<script>
function registrar_cambio_cant(idregcc){
	var cantidad_modif = $("#cantidad_modif").val();
	var costo_modif = $("#costo_modif").val();
	 var parametros = {
                "id"         : idregcc,
				"MM_update"  : "form1",
				"cantidad"   : cantidad_modif,
				"costo"      : costo_modif
        };
       $.ajax({
                data:  parametros,
                url:   'gest_reg_compras_resto_editcant.php',
                type:  'post',
                beforeSend: function () {
                        $("#pop1").html("<br /><br /><br />Registrando...<br /><br /><br />");
                },
                success:  function (response) {
						//$("#pop1").html(response);
						if(response == 'OK'){
							cabeza();
							document.location.href='gest_reg_compras_resto.php';
						}else{
							$("#pop1").html(response);
						}
                }
        });
}
function asignardt(cual){
	
	 var parametros = {
                "id" : cual
        };
       $.ajax({
                data:  parametros,
                url:   'gest_reg_compras_resto_editcant.php',
                type:  'post',
                beforeSend: function () {
                        $("#pop1").html("Cargando...");
                },
                success:  function (response) {
						popupasigna();
						$("#pop1").html(response);
                }
        });
	
}
function popupasigna(){
		 $(function mag() {
			$.magnificPopup.open({
                items: {
                    src: '#pop1',
                },
                type:'inline',
                midClick: false,
                closeOnBgClick: true
            });
        });		
}

$(function mag() {
	$('a[href="#login-popup"]').magnificPopup({
		type:'inline',
		midClick: false,
		closeOnBgClick: false
	});
	
}); 


</script>
<script src="js/jquery.magnific-popup.min.js"></script>
            
         </div> <!-- COLCOMPLETO -->
    </div><!-- CUERPO -->     
<div class="clear"></div><!-- clear2 -->
<?php require("../includes/pie.php"); ?>

</body>
</html>