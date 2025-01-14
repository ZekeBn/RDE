 <?php
//09092021
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "12";
$submodulo = "446";
require_once("includes/rsusuario.php");


//Vemos si existe POST para apertura
if (isset($_POST['montoaper']) && intval($_POST['montoaper']) >= 0) {

    // busca si hay una caja abierta por este usuario
    $buscar = "
    Select * 
    from caja_super 
    where 
    estado_caja=1   and tipocaja=2
    and cajero=$idusu 
    order by fecha_apertura desc 
    limit 1
    ";

    $rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idcaja = intval($rscaj->fields['idcaja']);
    // si existe caja abierta redirecciona para imprimir
    if ($idcaja > 0) {
        header("location: teso_abrir_caja.php");
        exit;
    }
    // si no existe abre
    if ($idcaja == 0) {

        // centrar nombre de empresa
        $nombreempresa_centrado = corta_nombreempresa($nombreempresa);

        /*--------------------- INICIO APERTURA DE CAJA -------------------------------------------------*/
        $montoabre = intval($_POST['montoaper']);
        $cajachica = intval($_POST['cajachica']);
        $fechaek = date("Y-m-d", strtotime($_POST['selefe']));
        $valido = "S";

        // si no usa caja chica
        if ($usacajachica == 'N') {
            $cajachica = 0;
        }
        // si el monto es fijo omite el post
        if ($hab_monto_fijo_chica == 'S') {
            $cajachica = $monto_fijo_chica;
        }
        if ($hab_monto_fijo_recau == 'S') {
            $montoabre = $monto_fijo_recau;
        }


        //Abrir caja, toda vez que no exista una caja abierta
        $buscar = "Select * from caja_super where estado_caja=1 and cajero=$idusu and tipocaja=2 order by fecha desc limit 1";
        $rscos = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $df = intval($rscos->fields['estado_caja']);


        // si No hay cajas pendientes de cierre
        if ($df == 0) {



            $ahora = date("Y-m-d");

            if ($fechaek < $ahora) {


                $errorfecha = 2;


            } else {
                //echo 'CONT1';exit;
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
                $insertar = "insert into caja_super
                    (fecha,fecha_apertura,cajero,estado_caja,monto_apertura,monto_cierre,total_cobros_dia,total_pagos_dia,
                    sucursal,pe,dia,mes,ano,caja_chica,tipocaja)
                    values
                    ($fechasele,'$ahora',$idusu,1,$montoabre,0,0,0,$idsucursal,$pe,$dia,$mes,$ann,$cajachica,2)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

                $buscar = "Select * from caja_super where cajero=$idusu and tipocaja=2 order by registrado_el desc limit 1";
                $rscaj = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

                $idcaja = intval($rscaj->fields['idcaja']);
                //Terminamos la apertura, ahora se imprime
                $fechahora = date("d/m/Y H:i:s");
                //echo $fechahora;
                //$fechahora=date("d-m-Y H:i:s",strtotime($rscaj->fields['registrado_el']));
                $upc = strtoupper($cajero);


                $imprimir = 0;
            }

            // no permite abrir caja por que hay cajas pendientes de cierre
        } else {
            $errorcierre = 1;
        }

        //echo 'ejecutarconsulta';exit;
        $consulta = "
        insert into caja_gestion
        (idcajaold, fecha_apertura, fechahora_apertura, estado, cajero, idsucursal, monto_apertura, monto_cierre, total_ingresos, total_egresos, faltante, sobrante, fecha_cierre, fechahora_cierre, idtipocaja)
        select $idcaja, fecha_apertura, fecha_apertura, 1, cajero, sucursal as idsucursal, monto_apertura, monto_cierre, 0 as total_ingresos, 0 as total_egresos, 0 as faltante, 0 as sobrante, NULL as fecha_cierre, NULL as fechahora_cierre, 1 as idtipocaja
        from caja_super
        where
        idcaja = $idcaja
        ";
        //$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
        //echo $consulta;exit;

        /*--------------------- FIN APERTURA DE CAJA -------------------------------------------------*/




    } // if($idcaja == 0){

    // redireciona para imprimir
    header("location: teso_abrir_caja.php");
    exit;

} // if(isset($_POST['montoaper']) && intval($_POST['montoaper']) >= 0){

?>
