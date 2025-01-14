<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "2";
require_once("includes/rsusuario.php");

$css = "
<style>
	@page *{
	margin-top: 0cm;
	margin-bottom: 0cm;
	margin-left: 0cm;
	margin-right: 0cm;
	}
	.fondopagina{
		border:0px solid #000000;
		width:1200px;
		height:1200px;
		margin-top:10px;
		margin-left:auto;margin-right:auto;
		
		background-image:url('gfx/presupuestos/01.jpg') no-repeat;
		background-size: cover;
	}
	.fondopagina_pagos{
		border:0px solid #FFFFFF;
		width:1200px;
		height:1200px;
		margin-top:10px;
		margin-left:auto;margin-right:auto;
		background-image:url('gfx/presupuestos/p02new.jpg') no-repeat;
		background-size: cover;
	}
	.contenedorppal{
		width:100%;
		height:50px;
		border:2px solid #b8860b;
		border-style: dotted;
	}
	.contenedorppalc{
		color:#b8860b;
		border:0.5px solid #b8860b;
		border-style: dotted;
		height:120px;
		width:650px;
		margin-left:auto;
		margin-right:auto;
	}
	.contenedorppaldire{
		color:#b8860b;
		border:0.5px solid #b8860b;
		border-style: dotted;
		height:40px;
		width:600px;
		margin-top:3%;
		margin-left:auto;
		margin-right:auto;
	}
	
	.contenedorderechamini{
		color:#b8860b;
		border:0px solid #b8860b;
		border-style: dotted;
		width:200px;
		height:60px;
		float:right;
		margin-top:5%;
		margin-right:4%;
	}
	.contenedorizqmini{
		color:#b8860b;
		#border:0.5px solid #b8860b;
		#border-style: dashed;
		width:130px;
		height:40px;
		float:left;
		margin-left:0%;
		margin-top:0%;
		
	}
	.button-1 {
	  background-color: #EA4C89;
	  border-radius: 8px;
	  border-style: none;
	  box-sizing: border-box;
	  color: #FFFFFF;
	  cursor: pointer;
	  display: inline-block;
	  font-family: \"Haas Grot Text R Web\", \"Helvetica Neue\", Helvetica, Arial, sans-serif;
	  font-size: 14px;
	  font-weight: 500;
	  height: 40px;
	  line-height: 20px;
	  list-style: none;
	  margin: 0;
	  outline: none;
	  padding: 10px 16px;
	  position: relative;
	  text-align: center;
	  text-decoration: none;
	  transition: color 100ms;
	  vertical-align: baseline;
	  user-select: none;
	  -webkit-user-select: none;
	  touch-action: manipulation;
	}

	.button-1:hover,
	.button-1:focus {
	  background-color: #F082AC;
	}
	.contenedorceqmini{
		#color:#b8860b;
		#border:0.5px solid #b8860b;
		#border-style: dashed;
		width:300px;
		height:40px;
		float:left;
		margin-top:0%;
		
	}
	.button-29 {
	  align-items: center;
	  appearance: none;
	  background-image: radial-gradient(100% 100% at 100% 0, #5adaff 0, #5468ff 100%);
	  border: 0;
	  border-radius: 6px;
	  box-shadow: rgba(45, 35, 66, .4) 0 2px 4px,rgba(45, 35, 66, .3) 0 7px 13px -3px,rgba(58, 65, 111, .5) 0 -3px 0 inset;
	  box-sizing: border-box;
	  color: white;
	  cursor: pointer;
	  display: inline-flex;
	  font-family: \"JetBrains Mono\",monospace;
	  height: 40px;
	  justify-content: center;
	  line-height: 1;
	  list-style: none;
	  overflow: hidden;
	  padding-left: 16px;
	  padding-right: 16px;
	  position: relative;
	  text-align: left;
	  text-decoration: none;
	  transition: box-shadow .15s,transform .15s;
	  user-select: none;
	  -webkit-user-select: none;
	  touch-action: manipulation;
	  white-space: nowrap;
	  will-change: box-shadow,transform;
	  font-size: 18px;
	}

	.button-29:focus {
	  box-shadow: #3c4fe0 0 0 0 1.5px inset, rgba(45, 35, 66, .4) 0 2px 4px, rgba(45, 35, 66, .3) 0 7px 13px -3px, #3c4fe0 0 -3px 0 inset;
	}

	.button-29:hover {
	  box-shadow: rgba(45, 35, 66, .4) 0 4px 8px, rgba(45, 35, 66, .3) 0 7px 13px -3px, #3c4fe0 0 -3px 0 inset;
	  transform: translateY(-2px);
	}

	.button-29:active {
	  box-shadow: #3c4fe0 0 3px 7px inset;
	  transform: translateY(2px);
	}
	.contenedordermini{
		#color:#b8860b;
		#border:0.5px solid #b8860b;
		#border-style: dashed;
		width:202px;
		height:40px;
		float:left;
		margin-top:0.8%;
		
	}
	.colordorado{
		 color:#b8860b;
		 
	}
	.negrito{
		color:black;
	}
	table {
		border-collapse: collapse; width:100%;
		font-size:12px;
	}
	 
	table,
	th,
	td {
		border: 0px solid black; align:center;
	}
	 
	th,
	td {
		padding: 5px;
	}
</style>
";



function limpiacsv($txt)
{
    global $saltolinea;
    $txt = trim($txt);
    $txt = str_replace(";", ",", $txt);
    $txt = str_replace($saltolinea, "", $txt);
    return $txt;
}
/*------------------------------------------RECEPCION DE VALORES--------------------------------*/
$idcpr = intval($_REQUEST['cpr']);
$fecha_prod_desde = antisqlinyeccion($_REQUEST['prod_desde'], 'date');
$fecha_prod_hasta = antisqlinyeccion($_REQUEST['prod_hasta'], 'date');
$fecha_prod_desde_hora = antisqlinyeccion($_REQUEST['prod_desde_hora'], 'text');
$fecha_prod_hasta_hora = antisqlinyeccion($_REQUEST['prod_hasta_hora'], 'text');
$fecha_evento_desde = antisqlinyeccion($_REQUEST['evento_desde'], 'date');
$fecha_evento_hasta = antisqlinyeccion($_REQUEST['evento_hasta'], 'date');
$fecha_evento_desde_hora = antisqlinyeccion($_REQUEST['evento_desde_hora'], 'text');
$fecha_evento_hasta_hora = antisqlinyeccion($_REQUEST['evento_hasta_hora'], 'text');
$fecha_registro_desde = antisqlinyeccion($_REQUEST['freg'], 'date');
$fecha_registro_hasta = antisqlinyeccion($_REQUEST['freg2'], 'date');
$fecha_reg_desde_hora = antisqlinyeccion($_REQUEST['freghoradesde'], 'date');
$fecha_reg_hasta_hora = antisqlinyeccion($_REQUEST['freghorahasta'], 'date');




$idproducto = intval($_REQUEST['idproducto']);
$idcategoria = intval($_REQUEST['idcategoria']);
$id_sub_categoria = intval($_REQUEST['idsubcate']);
$especial = intval($_REQUEST['especial']);// Solo para uso de PDF y Producciones  eventos (clientes)
//echo $especial;exit;
if ($_REQUEST['freg'] == '') {
    $fecha_registro_desde = '';
}
$fecha_registro_hasta = antisqlinyeccion($_REQUEST['freg2'], 'date');
if ($_REQUEST['freg2'] == '') {
    $fecha_registro_hasta = '';
}
$idlugar_entrega = intval($_REQUEST['lugar']);
$ocvalor = intval($_REQUEST['ocvalor']);



/*------------------------------------------RECEPCION DE VALORES--------------------------------*/

if ($especial == 0) {

    if (($ocvalor == 1) or ($ocvalor == 0)) {
        if ($idcpr > 0) {
            $whereadd .= " and produccion_orden_new_cpr.idcpr = $idcpr ";
        }
        if ($_REQUEST['prod_desde'] != '') {
            $whereadd .= " and  produccion_orden_new_cpr.fecha_producir>=$fecha_prod_desde ";
        }
        if ($_REQUEST['prod_hasta'] != '') {
            $whereadd .= " and  produccion_orden_new_cpr.fecha_producir<=$fecha_prod_hasta ";
        }
        if ($_REQUEST['prod_desde_hora'] != '') {
            $whereadd .= " and  produccion_orden_new_cpr.hora_producir>=$fecha_prod_desde_hora ";
        }
        if ($_REQUEST['prod_hasta_hora'] != '') {
            $whereadd .= " and  produccion_orden_new_cpr.hora_producir<=$fecha_prod_hasta_hora ";
        }
        if ($_REQUEST['evento_desde'] != '') {
            $whereadd .= " and  (select evento_para from pedidos_eventos where idtransaccion=(select idtransaccion from produccion_orden_new where idordengral=produccion_orden_new_detalles.idordengral))>=$fecha_evento_desde ";
        }
        if ($_REQUEST['evento_hasta'] != '') {
            $whereadd .= " and  (select evento_para from pedidos_eventos where idtransaccion=(select idtransaccion from produccion_orden_new where idordengral=produccion_orden_new_detalles.idordengral))<=$fecha_evento_hasta ";
        }
        if ($_REQUEST['evento_desde_hora'] != '') {
            $whereadd .= " and  (select hora_entrega from pedidos_eventos where idtransaccion=(select idtransaccion from produccion_orden_new where idordengral=produccion_orden_new_detalles.idordengral))>=$fecha_evento_desde_hora ";
        }
        if ($_REQUEST['evento_hasta_hora'] != '') {
            $whereadd .= " and  (select hora_entrega from pedidos_eventos where idtransaccion=(select idtransaccion from produccion_orden_new where idordengral=produccion_orden_new_detalles.idordengral))<=$fecha_evento_hasta_hora ";
        }
        if ($idlugar_entrega > 0) {
            $whereadd .= " and idtransaccion in (select idtransaccion from pedidos_eventos where idlugar_entrega=$idlugar_entrega)";

        }
        if ($idcategoria > 0) {
            $whereadd .= " and (select idprod_serial from productos where idprod_serial=produccion_orden_new_detalles.idinsumo) in 
				(select idprod_serial from productos where idcategoria=$idcategoria)";
        }
        if ($id_sub_categoria > 0) {
            $whereadd .= " and (select idprod_serial from productos
				where idprod_serial=produccion_orden_new_detalles.idinsumo) 
				in 
				(select idprod_serial from productos where idsubcate=$id_sub_categoria)";
        }
        if (trim($_GET["prod"]) != '') {
            //$whereadd.=" and productos.descripcion like '%$prodn%' ";
        }
        if ($idproducto > 0) {
            $whereadd .= " and produccion_orden_new_detalles.idinsumo=$idproducto";
        }
        $buscar = "
			select produccion_orden_new_cpr.fecha_producir,produccion_orden_new_cpr.hora_producir,nombre as categoria,sub_categorias.descripcion as subcategoria,
				idprod_serial,productos.descripcion,
				(select descripcion from produccion_centros where idcentroprod=productos.idcpr) as centro_produccion,
				(select descripcion from productos where idprod_serial=produccion_orden_new_detalles.id_insumo_vinculado) as agregado,
				(select evento_para from pedidos_eventos where idtransaccion=(select idtransaccion from produccion_orden_new where idordengral=produccion_orden_new_detalles.idordengral)) as fecha_evento,
				(select hora_entrega from pedidos_eventos where idtransaccion=(select idtransaccion from produccion_orden_new where idordengral=produccion_orden_new_detalles.idordengral)) as hora_evento,
				(select idtransaccion from produccion_orden_new where idordengral=produccion_orden_new_detalles.idordengral) as idtransaccion,
				sum(cantidad_producir) as total_producir
				from produccion_orden_new_detalles 
				inner join produccion_orden_new_cpr 
				on produccion_orden_new_cpr.idcpr=produccion_orden_new_detalles.idcpr 
				and produccion_orden_new_cpr.idordengral=produccion_orden_new_detalles.idordengral
				inner join productos on productos.idprod_serial=produccion_orden_new_detalles.idinsumo
				inner join categorias on categorias.id_categoria=productos.idcategoria
				inner join sub_categorias on sub_categorias.idsubcate=productos.idsubcate
				where produccion_orden_new_detalles.idordengral is not null
				$whereadd
				group by produccion_orden_new_detalles.idcpr,idinsumo;

			";



    } else {
        $whereadd = "";
        //para traer de panel de pedidos pero consolidar reporte
        if ($idcpr > 0) {
            $whereadd .= " and pedidos_eventos_detalles.idcpr = $idcpr ";
        }
        if ($_REQUEST['freg1'] != '' && $_REQUEST['freg1'] != 'NULL') {
            $whereadd .= " and idtransaccion in (select idtransaccion from pedidos_eventos where date(pedidos_eventos.registrado_el) >=$fecha_registro_desde)";
        }
        if ($_REQUEST['freg2'] != '' && $_REQUEST['freg2'] != 'NULL') {
            $whereadd .= " and idtransaccion in (select idtransaccion from pedidos_eventos where date(pedidos_eventos.registrado_el) <=$fecha_registro_hasta)";
        }
        if ($_REQUEST['freghoradesde'] != '') {
            $conjunto = date("Y-m-d H:i:s", strtotime($_REQUEST['freg'].' '.$_REQUEST['freghoradesde']));
            $whereadd .= " and idtransaccion in (select idtransaccion from pedidos_eventos where (registrado_el) >='$conjunto')";
        }
        if ($_REQUEST['freghorahasta'] != '') {
            $conjunto = date("Y-m-d H:i:s", strtotime($_REQUEST['freg2'].' '.$_REQUEST['freghorahasta']));
            $whereadd .= " and idtransaccion in (select idtransaccion from pedidos_eventos where (registrado_el) <='$conjunto')";
        }

        if ($idlugar_entrega > 0) {
            $whereadd .= " and idtransaccion in (select idtransaccion from pedidos_eventos where idlugar_entrega=$idlugar_entrega)";

        }
        if ($_REQUEST['evento_desde'] != '') {
            $whereadd .= " and idtransaccion in (select idtransaccion from pedidos_eventos where date(evento_para) >=$fecha_evento_desde)";
        }
        if ($_REQUEST['evento_hasta'] != '') {
            $whereadd .= " and idtransaccion in (select idtransaccion from pedidos_eventos where date(evento_para) <=$fecha_evento_hasta)";
        }
        if ($idproducto > 0) {
            $whereadd .= " and pedidos_eventos_detalles.idprodserial=$idproducto";
        }
        if ($idcategoria > 0) {
            $whereadd .= " and productos.idcategoria=$idcategoria";
        }
        if ($id_sub_categoria > 0) {
            $whereadd .= " and productos.idsubcate=$id_sub_categoria ";
        }



        $buscar = "
			select nombre as categoria,sub_categorias.descripcion as subcategoria,
				idprod_serial,productos.descripcion,
				(select descripcion from produccion_centros where idcentroprod=productos.idcpr) as centro_produccion,
				REPLACE(COALESCE(sum(cantidad),0),'.',',') as total_producir
				,(select nombre from medidas where id_medida=productos.idmedida) as medida,registrado
			FROM pedidos_eventos_detalles
				inner join productos on productos.idprod_serial=pedidos_eventos_detalles.idprodserial
				inner join categorias on categorias.id_categoria=productos.idcategoria
				inner join sub_categorias on sub_categorias.idsubcate=productos.idsubcate
			where 
				pedidos_eventos_detalles.idtransaccion in (select idtransaccion from pedidos_eventos where estado <> 6)
				$whereadd
				group by idprodserial order by registrado asc
			
			";


    }// Final de Tipo de reporte

    //echo $buscar;exit;

    $clase = 1;
    if ($clase == 1) {
        //XLS
        $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

        $impreso = date("d/m/Y H:i:s");

        // asigna los datos de la consulta a una variable
        $array = $rs->fields;

        // CONSTRUYE CABECERA
        foreach ($array as $key => $value) {
            $i++;
            $datos .= limpiacsv($key).';';
        }
        reset($array);
        $datos .= $saltolinea;

        //CONSTRUYE CUERPO
        $ante = 0;
        $fila = 1;
        while (!$rs->EOF) {
            $fila++;
            $array = $rs->fields;
            $i = 0;
            foreach ($array as $key => $value) {
                $i++;
                $datos .= limpiacsv($value).';';
            }
            $datos .= $saltolinea;
            $rs->MoveNext();
        }



        $impreso = date("d/m/Y H:i:s");

        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename=nec_prod_'.$impreso.'.csv');

        echo $datos;
        exit;








    } else {
        //PDF



    }
} else {
    $listafiltros = "&nbsp;";
    //echo "Es consoliddo por cliente";exit;
    $whereadd = "";
    //para traer de panel de pedidos pero consolidar reporte

    if ($_REQUEST['freg'] != '' && $_REQUEST['freg'] != 'NULL') {
        $whereadd .= " and  date(pedidos_eventos.registrado_el) >=$fecha_registro_desde ";
        $fr = date("d/m/Y", strtotime($_REQUEST['freg']));
        $listafiltros .= " Reg Desde : $fr ";
    } else {
        $listafiltros .= " Reg Desde : -- ";
    }
    if ($_REQUEST['freg2'] != '' && $_REQUEST['freg2'] != 'NULL') {
        $whereadd .= " and date(pedidos_eventos.registrado_el) <=$fecha_registro_hasta ";
        $fr = date("d/m/Y", strtotime($_REQUEST['freg2']));
        $listafiltros .= "&nbsp;&nbsp;| Reg Hasta : $fr ";
    } else {
        $listafiltros .= "&nbsp;&nbsp;| Reg Hasta : -- ";
    }
    if ($_REQUEST['evento_desde'] != '') {
        $whereadd .= " and date(evento_para) >=$fecha_evento_desde ";
        $fr = date("d/m/Y", strtotime($_REQUEST['evento_desde']));
        $listafiltros .= " &nbsp;| Evento Desde : $fr ";
    } else {
        $listafiltros .= " &nbsp;|Evento Desde : -- ";
    }
    if ($_REQUEST['evento_hasta'] != '') {
        $whereadd .= " and date(evento_para) <=$fecha_evento_hasta ";
        $fr = date("d/m/Y", strtotime($_REQUEST['evento_hasta']));
        $listafiltros .= " | Evento hasta : $fr ";
    } else {
        $listafiltros .= " | Evento hasta : -- ";
    }


    if ($idcpr > 0) {
        $buscar = "Select descripcion as nombre from produccion_centros where idcentroprod=$idcpr";
        $rscv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $dpce = trim($rscv->fields['nombre']);

        //$whereadd.=" and regid in(select regid from pedidos_eventos_detalles where idcpr = $idcpr )";
        $listafiltros .= "<br/>&nbsp; Centro de Produccion : $dpce ";
    } else {
        $listafiltros .= "<br />&nbsp; Centro de Produccion : Todos  ";
        ;
    }
    if ($idlugar_entrega > 0) {
        $whereadd .= " and pedidos_eventos.idlugar_entrega=$idlugar_entrega ";

    }

    if ($idproducto > 0) {
        //$whereadd.=" and pedidos_eventos_detalles.idprodserial=$idproducto";
    }
    if ($idcategoria > 0) {
        //$whereadd.=" and productos.idcategoria=$idcategoria";
    }
    if ($id_sub_categoria > 0) {
        /*$whereadd.=" and (select idprod_serial from productos
            where idprod_serial=produccion_orden_new_detalles.idinsumo)
            in
            (select idprod_serial from productos where idsubcate=$id_sub_categoria)";*/
    }


    /*---------------------------------------------------------------------*/

    $buscar = "
		
		Select regid,idtransaccion,nombre_evento,evento_para,hora_entrega,comentario_interno,dire_entrega,
		(select razon_social from cliente where idcliente=pedidos_eventos.idcliente_factura) as clienterz,
		(select telefono from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as clientepedtelfo,
		(select mensaje from pedidos_eventos_mensajes where idtransaccion=pedidos_eventos.idtransaccion) as mensaje,
		(select remitente from pedidos_eventos_mensajes where idtransaccion=pedidos_eventos.idtransaccion) as remitente,
		(select celular from pedidos_eventos_mensajes where idtransaccion=pedidos_eventos.idtransaccion) as telefonoremitente,
		(select 
			CASE when idlugar_entrega=99 then 'DELIVERY'
				 when idlugar_entrega=0  then 'DELIVERY'
			ELSE
				(select sucursales.nombre from sucursales where idsucu=pedidos_eventos.idlugar_entrega)			
			END ) as direccion_entrega
		from pedidos_eventos where pedidos_eventos.estado <> 6
			$whereadd 
		order by hora_entrega asc 
		
	
		";
    //echo $buscar;exit;
    $rs = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $html = "
		$css
		";

    /*--------------------------CABECERA CON FILTROS----------------------------*/
    $html .= "<div style='border:0px solid #000000'>
			<div style=\"margin-top:2%;width:80%;border:1px solid #000000; margin-left:auto;margin-right:auto;text-align:center;height:30px;\">
				<span style='font-size:18px;font-weight:bold;'>Consolidado de Produccion por clientes</span>
				
			</div>
			<div style=\"margin-top:0%;width:80%;border:1px solid #000000;  margin-left:auto;margin-right:auto;text-align:left;height:50px;\">
				$listafiltros
			</div>
		</div>";
    while (!$rs->EOF) {
        $nombre_evento = trim($rs->fields['nombre_evento']);
        $dire_char = trim($rs->fields['dire_entrega']);
        $regid = intval($rs->fields['regid']);//id unico del pedido
        $idtransaccion = intval($rs->fields['idtransaccion']);//id unico de transaccion
        //echo $nombre_evento;exit;
        $ex = explode("|", $nombre_evento);
        $fecha = date("d/m/Y", strtotime($rs->fields['evento_para']));
        $hora = ($rs->fields['hora_entrega']);
        $mensaje = trim(capitalizar($rs->fields['mensaje']));
        $remitente = trim(capitalizar($rs->fields['remitente']));
        $telefonoremitente = trim(($rs->fields['telefonoremitente']));
        $cliente = trim($ex[1]);
        $clienterz = trim($rs->fields['clienterz']);
        $telefono1 = trim($rs->fields['clientepedtelfo']);

        $cabecera = "
			
		
			
			<div style='border:1px solid #000000;height:130px; width:80%;margin-left:auto;margin-right:auto;margin-top:2%;'>
				<div style='margin-top:1%;border:0px solid #000000; height:150px;'>
					<div style=\"font-size:12px;font-weight:bold;margin-left:0%;float:left; text-align:left;color:black;height:150px;width:60%\">
						IDT: $idtransaccion &nbsp;Pedido: $regid
						<br />
						Cliente: &nbsp;$cliente &nbsp; 
						<br />
						Razon Social: &nbsp;$clienterz &nbsp; 
						<br />
						Fecha:&nbsp;$fecha / Hora: $hora
						<br />
						Direccion Entrega:&nbsp;$dire_char 
						<br/>
						Mensaje : $mensaje
						
					</div>
					<div style=\"font-size:12px;font-weight:bold;margin-left:0%;float:left; text-align:left;color:black;height:150px;width:35%\">
						<br />
						Telefono: $telefono1 
						<br />
						Telefono: $telefono 
						<hr />
						<br />
						Remitente : $remitente
						<br />
						Telefono : $telefonoremitente
						<hr />
					</div>
					
				</div>
			</div>
				";
        if ($idcpr > 0) {
            $cpradd = " and pedidos_eventos_detalles.idcpr=$idcpr ";
        } else {
            $cpradd = "";
        }
        if ($id_sub_categoria > 0) {
            $whereaddcuerpo .= " and productos.idsubcate=$id_sub_categoria ";
        }





        $buscar = "
			SELECT nombre as categoria,sub_categorias.descripcion as subcategoria,productos.idcpr,idtransaccion,
				idprod_serial,productos.descripcion,			
				(select descripcion from produccion_centros where idcentroprod=productos.idcpr) as centro_produccion,
				REPLACE(COALESCE(sum(cantidad),0),'.',',') as total_producir
				,(select nombre from medidas where id_medida=productos.idmedida) as medida
			FROM pedidos_eventos_detalles
				inner join productos on productos.idprod_serial=pedidos_eventos_detalles.idprodserial
				inner join categorias on categorias.id_categoria=productos.idcategoria
				inner join sub_categorias on sub_categorias.idsubcate=productos.idsubcate
			WHERE 
				pedidos_eventos_detalles.idpedidocatering=$regid and idtransaccion=$idtransaccion
				$cpradd $whereaddcuerpo
				group by idprodserial,idcpr order by idcpr asc";
        $rs1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $cpr = 0;
        $tcuerpo = $rs1->RecordCount();

        /*------------CUERPO DEL PEDIDO--------*/
        $paso = 0;

        if ($tcuerpo > 0) {
            $html .= "$cabecera";

            $html .= "
				
				<div style='border:0px solid #000000;height:130px; width:80%;margin-left:auto;margin-right:auto;'>
				<table>
				
				";


            while (!$rs1->EOF) {
                $idcpr2 = intval($rs1->fields['idcpr']);
                $producto = trim($rs1->fields['descripcion']);
                $idprod_serial = intval($rs1->fields['idprod_serial']);

                $cantidad = floatval($rs1->fields['total_producir']);
                $idt = intval($rs1->fields['idtransaccion']);
                $obscuerpo = trim($rs1->fields['obs_cuerpo']);
                $buscar = "select produccion_orden_new.idordengral,idtransaccion,obs_producto
						from produccion_orden_new
						inner join produccion_orden_new_detalles
						on produccion_orden_new_detalles.idordengral=produccion_orden_new.idordengral
						where idtransaccion=$idtransaccion
						and idinsumo=$idprod_serial
						
						";
                $rs4 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                $obscuerpo = trim($rs4->fields['obs_producto']);


                if ($idcpr2 != $cpr) {
                    $paso = 0;
                    //$ds=trim($rs1->fields['centro_produccion']);
                    $cpr = $idcpr2;
                } else {
                    $l1 = "";
                }

                $paso = $paso + 1;
                if ($paso == 1) {
                    $ds = trim($rs1->fields['centro_produccion']);
                    $l1 = "
							<thead>
								<tr>
									<th align='left' colspan='3'>CPR: $ds</th>
								</tr>
							</thead>
							";
                    $l2 = "<tbody>";
                } else {
                    $l2 = "";
                    $l1 = "";
                }

                $html .= "
							$l1
							$l2
							<tr>
								<td width=20% align='center'>$cantidad</td>
								<td align='left'>$producto | $obscuerpo</td>
								<td>$idprod_serial</td>
							</tr>
								";

                $rs1->MoveNext();
            }
            $html .= "
							</tbody>
						</div>
						</table >
						</hr>
					</div>";
        } else {



        }

        $rs->MoveNext();
    }
    $html .= "

		";
    /*----------------------------------GENERAR PDF----------------------------------------*/
    require_once  '../clases/mpdf/vendor/autoload.php';

    //$mpdf = new mPDF('','Legal-P', 0, 0, 0, 0, 0, 0);

    $mpdf = new mPDF('c', 'Legal', 0, '', 0, 0, 0, 0, 0, 0);
    //$mpdf = new mPDF('c','A4','100','',32,25,27,25,16,13);
    $mpdf->showWatermarkText = false;
    $mini = "C-$idpresupuesto";
    $mpdf->SetDisplayMode('fullpage');
    //$mpdf->shrink_tables_to_fit = 1;
    $mpdf->shrink_tables_to_fit = 2.5;
    // Write some HTML code:
    $mpdf->SetHTMLHeader(
        "<div style='background-color:white;height:150px;margin-left:2%;margin-top:10%;'>
<p></p> 
</div>",
        'O'
    )
    ;



    $mpdf->SetHTMLFooter(
        "<div style='height:120px;'>
<img src='gfx/presupuestos/pie02.jpg' />
</div>",
        'O'
    )
    ;
    $mpdf->WriteHTML($html);

    // Output a PDF file directly to the browser
    //si no se usa el tributo I, no permite usar el nombre indicado y los archivos no sedescargan nunca!!
    //Bandera I saca en pantalla, bandera F graba en la ubicacion seleccionada
    $mpdf->Output('tmp_consolidados/consolidados_'.$mini.'.pdf', "I");

    /*------------------------------------------------------------------------------------*/
}//de consolidado por cliente
