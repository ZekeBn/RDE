<?php

require_once("../../includes/conexion.php");
require_once("../../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "22";
$dirsup_sec = "S";
require_once("../../includes/rsusuario.php");
// salon asignado cuando se abrio la caja
$idsalon_caja = intval($idsalon_usu);

// busca si el salon es una playa
if ($idsalon_usu > 0) {
    $consulta = "
	select idsalon, playa
	from salon 
	where 
	idsalon = $idsalon_usu
	";
    $rssalon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $playa = trim($rssalon->fields['playa']);
}
$consulta = "SELECT * FROM preferencias_caja WHERE  idempresa = $idempresa ";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$caja_compartida = trim($rsprefcaj->fields['caja_compartida']);
$usar_turnos_caja = trim($rsprefcaj->fields['usa_turnos']);
$turno_automatico_caja = trim($rsprefcaj->fields['turno_automatico_caja']);

$arrastre_saldo_anterior = trim($rsprefcaj->fields['arrastre_saldo_anterior']);
$tipo_arrastre = trim($rsprefcaj->fields['tipo_arrastre']);
$consulta = "
select arrastre_caja_suc from sucursales where idsucu = $idsucursal limit 1
";
$rssucar = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rssucar->fields['arrastre_caja_suc'] != "DEF") {
    if ($rssucar->fields['arrastre_caja_suc'] == "ACT") {
        $arrastre_saldo_anterior = 'S';
    }
    if ($rssucar->fields['arrastre_caja_suc'] == "INA") {
        $arrastre_saldo_anterior = 'N';
    }
}

//print_r($_POST);exit;
//Vemos si existe POST para apertura

if (isset($_POST['montoaper']) && intval($_POST['montoaper']) >= 0) {

    $idcaja_ant = 'NULL';

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
		and usuarios.idusu = $idusu
		";
        $rsus = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $monto_fijo_chica = intval($rsus->fields['monto_fijo_chica']);
        $monto_fijo_recau = intval($rsus->fields['monto_fijo_recau']);
    }

    if ($arrastre_saldo_anterior == 'S') {
        // estirar saldo de la ultima caja cerrada de esa sucursal que aun no se haya utilizado
        $consulta = "
		select 
		idcaja, monto_cierre 
		from caja_super 
		where 
		estado_caja = 3 
		and sucursal = $idsucursal 
		and idcaja not in (select idcaja_arrastre from caja_super where idcaja_arrastre is not null)
		and cajero not in (select idusu from usuarios where soporte =1) 
		order by idcaja desc 
		limit 1
		";
        $rscajant = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idcaja_ant = intval($rscajant->fields['idcaja']);


        $consulta = "
		select formas_pago.descripcion as formapago, sum(monto) as total
		from caja_arqueo_fpagos
		inner join formas_pago on formas_pago.idforma = caja_arqueo_fpagos.idformapago
		where
		caja_arqueo_fpagos.idcaja = $idcaja_ant
		and caja_arqueo_fpagos.estado <> 6 
		and formas_pago.idforma=1
		group by formas_pago.descripcion
		order by formas_pago.descripcion asc
		";
        $rsarq = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        //echo $consulta;exit;

        $monto_cierre_ant = floatval($rsarq->fields['total']);

        $monto_fijo_recau = $monto_cierre_ant;

    }


    //echo $hab_monto_fijo_recau;

    // busca si hay una caja abierta por este usuario
    $buscar = "
	Select * 
	from caja_super 
	where 
	estado_caja=1 
	and cajero=$idusu 
	 and tipocaja=1
	order by fecha_apertura desc 
	limit 1
	";
    $rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $idcaja = intval($rscaj->fields['idcaja']);
    // si existe caja abierta redirecciona para imprimir
    if ($idcaja > 0) {
        header("location: caja_abrir_imprime.php");
        exit;
    }
    // si no existe abre
    if ($idcaja == 0) {
        // centrar nombre de empresa
        $nombreempresa_centrado = corta_nombreempresa($nombreempresa);


        $idturno_caja = 'NULL';
        $idturno_tanda = 'NULL';

        /// INICIO TURNOS
        if ($usar_turnos_caja == 'S') {



            if ($turno_automatico_caja == 'S') {
                $parametros_array_tur = [
                    'hora_actual' => date("H:i:s"),
                    'idsucursal' => $idsucursal
                ];
                $res_turno = obtener_turno($parametros_array_tur);
                $idturno = intval($res_turno['idturno']);
                $idturno_caja = $idturno;

            } else {
                $idturno_caja = intval($_POST['idturno']);
            }

            // datos del turno
            $consulta = "
			select idturno, descripcion, hora_desde, hora_hasta, idsucursal, registrado_el, registrado_por, estado, anulado_el, anulado_por
			from turnos
			where 
			idturno = $idturno_caja
			";
            $rsturnos = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // busca si hay un turno abierto para esa sucursal en el horario de turno actual
            $ahorad = date("Y-m-d");

            if (intval($idturno) == 0) {
                echo "- No existen turnos para el horario actual.";
                exit;
            }

            // busca si hay alguna tanda de turnos que aun no fue cerrada en la sucursal actual
            $consulta = "
			select idturnotanda, idturno, idsucursal 
			from turnos_tandas
			where
			idsucursal = $idsucursal
			and estado=1
			";
            $rsturtand_ex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idturno_tanda = intval($rsturtand_ex->fields['idturnotanda']);

            // si no hay busca el turno horario actual
            if ($idturno_tanda == 0) {

                // busca si el turno actual ya se abrio pero se volvio a cerrar para la sucursal y horario de turno
                $consulta = "
				select idturnotanda, idturno, idsucursal 
				from turnos_tandas
				where
				fecha_apertura = '$ahorad'
				and idturno = $idturno_caja
				and idsucursal = $idsucursal
				";
                $rsturtand = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                $idturno_tanda = intval($rsturtand->fields['idturnotanda']);

                // si no hay abre
                if ($idturno_tanda == 0) {

                    $consulta = "
					INSERT INTO turnos_tandas
					(idturno, fecha_apertura, fecha_cierre, estado, idsucursal, abierto_por, cerrado_por ) 
					VALUES 
					($idturno_caja, '$ahora', NULL, 1, $idsucursal, $idusu, NULL)
					";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                    $consulta = "
					select idturnotanda, idturno, idsucursal 
					from turnos_tandas
					where
					date(fecha_apertura) = '$ahorad'
					and idturno = $idturno_caja
					and idsucursal = $idsucursal
					and abierto_por  =  $idusu
					order by idturnotanda desc
					limit 1
					";
                    $rsturtand = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                    $idturno_tanda = $rsturtand->fields['idturnotanda'];

                    // si  hay obliga a cerrar
                } else {
                    echo "Ya se cerro el turno para el horario actual, puede volver a abrir en el horario del siguiente turno.";
                    exit;
                }
                // si hay asigna
            } else {
                $idturno_tanda = intval($rsturtand_ex->fields['idturnotanda']);
            }

        } // if ($usar_turnos_caja=='S'){


        $idnumeradorcab = "NULL";
        if ($playa == 'S') {
            // busca si hay una tanda de numeradores abierta
            $consulta = "
			select * 
			from combustibles_numeradores_cab
			where
			idsucursal = $idsucursal
			and estado = 1
			";
            $rsnumtand = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $idnumeradorcab = intval($rsnumtand->fields['idnumeradorcab']);
            if ($idnumeradorcab == 0) {
                echo "Debe iniciar los numeradores antes de abrir caja.";
                exit;
            }
        }

        /// FIN TURNOS
        //echo $idturno_tanda;exit;
        /*--------------------- INICIO APERTURA DE CAJA -------------------------------------------------*/
        /*$monto=10000;
        $nombrevar="monto";

        echo $$nombrevar;*/

        //print_r($_POST);
        $montoabre = intval($_POST['montoaper']);
        $cajachica = intval($_POST['recauda']);
        $fechaek = date("Y-m-d", strtotime($_POST['selefe']));
        $valido = "S";

        $idcaja_compartida = antisqlinyeccion($_POST['idcaja_compartida'], "int");
        //echo $idcaja_compartida;exit;

        // si no usa caja chica
        if ($rspref->fields['usa_cajachica'] == 'N') {
            $cajachica = 0;
        }
        // si el monto es fijo omite el post
        if ($hab_monto_fijo_chica == 'S') {
            $cajachica = $monto_fijo_chica;
        }
        if ($hab_monto_fijo_recau == 'S') {
            $montoabre = $monto_fijo_recau;
        }
        if ($arrastre_saldo_anterior == 'S') {
            $montoabre = $monto_cierre_ant;
        }

        //Abrir caja, toda vez que no exista una caja abierta
        $buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and tipocaja=1 order by fecha desc limit 1";
        $rscos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $df = intval($rscos->fields['estado_caja']);


        // si No hay cajas pendientes de cierre
        if ($df == 0) {



            $ahora = date("Y-m-d");

            if ($fechaek < $ahora) {


                $errorfecha = 2;


            } else {

                $fechasele = antisqlinyeccion($fechahoy, 'date');
                $nf = str_replace("'", "", $fechasele);
                $nueva = date("Y-m-d", strtotime($nf));
                $nueva2 = explode("-", $nueva);
                $dia = intval($nueva2[2]);
                $mes = intval($nueva2[1]);
                $ann = intval($nueva2[0]);




                //activar esata seccion para entrega por tesoreria
                $buscar = "select * from gest_entrega_valores where date(fechahora)=$fechasele and movimiento_real=1 and estado=1 
					and cajero=$idusu";
                //$rsaper=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

                //esta ok para abrir
                //$buscar="select max(idcaja) as mayor from caja_super where cajero=$idusu";
                //$rsmayor=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
                //$mayor=intval($rsmayor->fields['mayor'])+1;



                $ahora = date("Y-m-d H:i:s");
                $insertar = "
					insert into caja_super
					(fecha,fecha_apertura,cajero,estado_caja,monto_apertura,monto_cierre,total_cobros_dia,total_pagos_dia,
					sucursal,pe,dia,mes,ano,caja_chica,idcaja_compartida,idsalon_caja,
					idturno_caja, idturno_tanda, idnumeradorcab, idcaja_arrastre)
					values
					($fechasele,'$ahora',$idusu,1,$montoabre,0,0,0,$idsucursal,$pe,$dia,$mes,$ann,$cajachica,$idcaja_compartida,$idsalon_caja,
					$idturno_caja, $idturno_tanda, $idnumeradorcab, $idcaja_ant)
					";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                $buscar = "Select * from caja_super where cajero=$idusu order by registrado_el desc limit 1";
                $rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $idcaja = intval($rscaj->fields['idcaja']);

                $consulta = "
					insert into caja_gestion
					(idcajaold, fecha_apertura, fechahora_apertura, estado, cajero, idsucursal, monto_apertura, monto_cierre, total_ingresos, total_egresos, faltante, sobrante, fecha_cierre, fechahora_cierre, idtipocaja)
					select $idcaja, fecha_apertura, fecha_apertura, 1, cajero, sucursal as idsucursal, monto_apertura, monto_cierre, 0 as total_ingresos, 0 as total_egresos, 0 as faltante, 0 as sobrante, NULL as fecha_cierre, NULL as fechahora_cierre, 1 as idtipocaja
					from caja_super
					where
					idcaja = $idcaja
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

                if ($usar_turnos_caja == 'S') {
                    // log de turnos
                    $consulta = "
						INSERT INTO caja_turnos_log
						(idcaja, idturno, registrado_por, registrado_el,  iniciado_por, iniciado_el, finalizado_por, finalizado_el) 
						VALUES
						($idcaja,$idturno_caja,  $idusu, '$ahora', $idusu, '$ahora', NULL, NULL)
						";
                    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
                }
                //Si tiene arrastre de saldo, logueamos
                $buscar = "Select * from preferencias_caja limit 1";
                $rscajbal2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $arrastre_saldo_anterior = trim($rscajbal2->fields['arrastre_saldo_anterior']);
                if ($arrastre_saldo_anterior == 'S') {
                    $insertar = "Insert into caja_super_arrastres
						(idcaja,monto_arrastre,estado,registrado_por,registrado_el,anulado_por,anulado_el)
						values
						($idcaja,$montoabre,1,$idusu,'$ahora',NULL,NULL)";
                    $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                }





                //Terminamos la apertura, ahora se imprime
                $fechahora = date("d/m/Y H:i:s");
                //echo $fechahora;
                //$fechahora=date("d-m-Y H:i:s",strtotime($rscaj->fields['registrado_el']));
                $upc = strtoupper($cajero);
                //**********************************************//
                //Agregado 19/05/22: se colocan turnos a la caja
                $buscar = "Select * from preferencias_caja";
                $rsca = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $usar_turnos_caja = trim($rsca->fields['usa_turnos']);
                if ($usar_turnos_caja == 'S') {
                    if ($playa == 'S') {
                        //require_once('combustibles_abrir_turnos.php');
                    }
                }
                //*********************************************//

                $imprimir = 1;
            }

            // no permite abrir caja por que hay cajas pendientes de cierre
        } else {
            $errorcierre = 1;
        }





        /*--------------------- FIN APERTURA DE CAJA -------------------------------------------------*/




    } // if($idcaja == 0){

    // redireciona para imprimir
    header("location: caja_abrir_imprime.php");
    exit;

} // if(isset($_POST['montoaper']) && intval($_POST['montoaper']) >= 0){
