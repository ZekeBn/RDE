 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");
ini_set('memory_limit', '512M');



$idtransaccion = intval($_REQUEST['idt']);
if ($idtransaccion == 0) {
    echo "Error al obtener id del presupuesto";
    exit;
}
//traemos los datos de la cabecera
//De la transaccion traemos los datos necesarios pra la visualizacion

$buscar = "Select * from  tmp_pedidos_cabecera where idtransaccion=$idtransaccion";
$rga = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$buscar = "Select * from  pedidos_eventos where idtransaccion=$idtransaccion";
$rgev = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$nombre_evento = trim($rgev->fields['nombre_evento']);
$monto_pedido_actual = floatval($rgev->fields['monto_evento']);
$monto_pedido_real = floatval($rgev->fields['monto_sin_descuento']);
$monto_descuento = floatval($rgev->fields['descuento_neto']);
$idevento = intval($rgev->fields['regid']);


$ex1 = explode("|", $rga->fields['cliente_pedido']);
$idcliente = intval($ex1[0]);
$cp = trim($ex1[2]);
$buscar = "Select * from cliente where idcliente=$idcliente ";
$rfsc = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$ema = trim(strtolower($rfsc->fields['email']));
$telfo = trim(strtolower($rfsc->fields['telefono']));
//print_r($ex1);exit;
$fecha_registro = "<span class='negrito'>".date("d/m/Y H:i:s", strtotime($rga->fields['registrado_el']))."</span>";
$fecha_evento = "<span class='negrito'>".date("d/m/Y", strtotime($rga->fields['fecha_evento']))."</span>";
$hora_evento = "<span class='negrito'>".date("H:i:s", strtotime($rga->fields['hora_evento']))."</span>";
$vendedor = "<span class='negrito'>".trim($rga->fields['vendedor'])."</span>";
$cliente = "<span class='negrito'>$cp</span>";
$cliente = "<span class='negrito'>".capitalizar($cliente)."</span>";
$email = "<span class='negrito'>$ema</span>";





$telefono = "<span class='negrito'>$telfo</span>";
$celular = "<span class='negrito'>".(trim($rga->fields['celular']))."</span>";
$direchar = trim($rga->fields['direccionchar']);
$cantidad_personas = intval($rga->fields['cantidad_personas']);
//traemos los datos del cuerpo
$buscar = "Select * from pedidos_eventos where idtransaccion=$idtransaccion ";
$rgh = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$estado_pedido = intval($rgh->fields['estado']);




$buscar = "Select regid,estado_pedido_int,evento_para,hora_entrega,nombre_evento,
    (select razon_social 
        from cliente where idcliente=pedidos_eventos.id_cliente_solicita)
        as  solicitado_por,
        (select ruc 
        from cliente where idcliente=pedidos_eventos.id_cliente_solicita)
        as  ruc,
    (select usuario from usuarios where idusu=pedidos_eventos.registrado_por) as cajero2,
    (Select sucursal from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as scliente,
    (Select telefono from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as celular,
    (Select mail from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as email,
    registrado_por,descuento_porc,monto_evento,monto_sin_descuento,descuento_neto
    from pedidos_eventos where idtransaccion=$idtransaccion ";
$rbba = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$ema = trim($rbba->fields['email']);
$email = "<span class='negrito'>$ema</span>";
$celular = "<span class='negrito'>".(trim($rbba->fields['celular']))."</span>";


$ex = explode("|", $nombre_evento);
$fecha_evento = str_replace("FEC", "", $ex[3]);
$ncliente = $ex[1];

/*--------------------------------------------------------------------------------*/
$buscar = "Select usuario from usuarios inner join pedidos_eventos on pedidos_eventos.ultimo_cambio_por=usuarios.idusu
    where idtransaccion=$idtransaccion";
$rs24 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$cajero3 = trim($rs24->fields['usuario']);

$buscar = "Select regid,estado_pedido_int,evento_para,hora_entrega,nombre_evento,comentarios,comentario_interno,dire_entrega,
        (select razon_social 
            from cliente where idcliente=pedidos_eventos.id_cliente_solicita)
            as  solicitado_por,
            (select ruc 
            from cliente where idcliente=pedidos_eventos.id_cliente_solicita)
            as  ruc,
        (select nombre from organizador_eventos where idorganizador=pedidos_eventos.idorganizador) as organizadornew,
        (select nombre from decorador_eventos where iddecorador=pedidos_eventos.iddecorador) as decorador,
        (select usuario from usuarios where idusu=pedidos_eventos.registrado_por) as cajero2,
        (Select sucursal from sucursal_cliente where idsucursal_clie=pedidos_eventos.id_cliente_sucu_pedido) as scliente,
        registrado_por,descuento_porc,monto_evento,monto_sin_descuento,descuento_neto
        from pedidos_eventos where idtransaccion=$idtransaccion ";
$rbb = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));



//*********************ya calculados desde la aplicaccion*******************//
$monto_pedido_actual = floatval($rbb->fields['monto_evento']);
$monto_pedido_real = floatval($rbb->fields['monto_sin_descuento']);
$monto_descuento = floatval($rbb->fields['descuento_neto']);
//*************************************************************************//
$comentarios = trim($rbb->fields['comentarios']);
$comentario_interno = trim($rbb->fields['comentario_interno']);
$direchar = trim($rbb->fields['dire_entrega']);
$ruc = trim($rbb->fields['ruc']);
$rz = trim($rbb->fields['solicitado_por']);
$descuento_aplicado = floatval($rbb->fields['descuento_porc']);
$clientepedido = trim($rbb->fields['scliente']);
$nombreevento = trim($rbb->fields['nombre_evento']);
//echo $clientepedido;

//$monto_evento=floatval($rbb->fields['monto_evento']);
$monotdescontado = floatval($monto_descuento);
//$subtotal_final=floatval($monto_evento-($monto_evento*$descuento_aplicado)/100);
$estado_pedido = intval($rbb->fields['estado_pedido_int']);
$fecha_para = date("d/m/Y", strtotime($rbb->fields['evento_para']));
$hora_entrega = $rbb->fields['hora_entrega'];
$regid = intval($rbb->fields['regid']);

if ($estado_pedido == 1) {
    $estap = "Presupuesto ";
}
if ($estado_pedido == 2) {
    $estap = "Confirmado ";
}
if ($estado_pedido == 3) {
    $estap = "En produccion ";

}
$idusuario_pedido = intval($rbb->fields['registrado_por']);
$idpresupuesto = intval($rbb->fields['regid']);
$cajero2 = ($rbb->fields['cajero2']);
$organizadorn = ($rbb->fields['organizadornew']);
$decorador = ($rbb->fields['decorador']);






$buscar = "Select *, 
        (select mail from sucursal_cliente where mail is not null and idcliente=pedidos_eventos.id_cliente_solicita limit 1) as correo, 
        (select email from cliente where idcliente=pedidos_eventos.id_cliente_solicita) as emailalt,
        (select nombre from organizador_eventos where idorganizador=pedidos_eventos.idorganizador) as organizador,
        (select nombre from decorador_eventos where iddecorador=pedidos_eventos.iddecorador) as decorador,
        (select cliente_pedido from tmp_pedidos_cabecera where idtransaccion=pedidos_eventos.idtransaccion)
         as clientepedidof 
         from pedidos_eventos where idtransaccion=$idtransaccion";
$rscabcorreo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$ema = trim(strtolower($_REQUEST['emappal']));
//echo "Correo encontrado: $ema";exit;
if ($ema == '') {
    $ema = trim(strtolower($rscabcorreo->fields['emailalt']));

}
//echo $buscar;exit;

//$idtransaccion=intval($rscab->fields['idtransaccion']);//id unico de la transaccion
//$idpedido=intval($rscab->fields['regid']);
//$clientepedido=trim($rscab->fields['clientepedidof']);
$asunto = "Cliente Pedido: $clientepedido - Estado : $estap - Numero :$regid - Fecha: $fecha_para";
$asunto = $rscabcorreo->fields['nombre_evento'];



//7501
$html = "



<div class=\"fondopagina \">

        <div style='margin-top:2%;border:0px solid #000000; height:200px;width:100%;border:1px solid #000000;font-size:14px'>
            <div style='width:100%;text-align:center;font-weight:10px;font-weigth:bold;font-size:12px'>
                <strong>Transaccion Num: $idtransaccion<span style='color:#b8860b;'></span> &nbsp;|    Vendedor: $cajero2<span style='color:#b8860b;'></span>&nbsp;|Modificado:$cajero3<span style='color:#b8860b;'> </span></strong>
            </div>
            <div style=\"margin-left:7%;float:left; text-align:left;color:black;font-size:14px;height:150px;width:43%\">
                <br />
                Fecha:&nbsp;$fecha_evento
                <br />
                Hora:&nbsp;&nbsp;&nbsp;$hora_entrega
                <br />
                Cliente: &nbsp; $ncliente &nbsp; 
                <br />
                Celular: &nbsp;$celular
                <br />
                Email: &nbsp;$ema
                <br />
            </div>
            <div style=\"margin-left:0%;float:left; text-align:left;color:black;font-size:14px;height:150px;width:48%\">
                <br />
                Direccion Entrega:&nbsp;$direchar 
                <br />
                Ruc: $ruc 
                <br />
                Razon Social: $rz 
                    <br />
                Organizador:&nbsp;$organizadorn 
                <br />
                Decorador: $decorador
                
            </div>
                <div style='width:100%;text-align:left;font-weigth:bold;'>
                <strong>Comentario :$comentario_interno</strong>
            </div>
        </div>




    
    <div style=\"width:680px;margin-left:auto;margin-right:auto\">
        <table >
            <thead>
            <tr>
                <th>Id Producto</th>
                <th>Cantidad</th>
                <th colspan=\"2\">Descripcion</th>
                <th>Precio</th>
                <th>Sub total</th>
                
            </tr>
            </thead>
            <tbody>
            ";
//para el cuerpo: tomamos el carrit temporal que no se borra por la transaccion
$buscar = "Select *,
                    (select descripcion from productos where idprod_serial=tmp_carrito_pedidos.idproducto) as descripcion
                    
                    from tmp_carrito_pedidos where idtransaccion=$idtransaccion";
//$rsa=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));


$buscar = "Select titulo_contenedor,idplantilla,descripcion,precio_min,pkl,idtransaccion,idproducto as idprod_serial,cantidad,precio as precioventa,subtotal,estado,
            (Select count(idproducto_padre) as total from pedidos_eventos_referenciales where idproducto_padre=tmp_carrito_pedidos.idproducto) as componentes,
            iddetalle,desglosa_precio,obs,
            (select nombre_plantilla from plantilla_articulos 
            where idplantillaart=tmp_carrito_pedidos.idproducto and idplantilla >0 and titulo_contenedor='S'
            and tmp_carrito_pedidos.idtransaccion=$idtransaccion) as nombreplantilla,
            (select descripcion from productos where productos.idprod_serial=idproducto_vinculado) as pvinculado
            from tmp_carrito_pedidos
            inner join productos on productos.idprod_serial=tmp_carrito_pedidos.idproducto
            where idtransaccion=$idtransaccion
            order by pkl asc ";
//echo $buscar;
$rsa = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


while (!$rsa->EOF) {
    $cantidad_items = $cantidad_items + 1;
    $posicion = $posicion + 1;
    $cantidad = $rsa->fields['cantidad'];//cantidad del componente calculado previamente
    $cantidad_producir = $cantidad;//del original, luego mas abajo , reemplazar por el temporal
    $add = "";
    $ipd = trim($rsa->fields['idprod_serial']);
    $idproducto = $ipd;
    $idunicotmp = intval($rsa->fields['pkl']);
    $pmin = floatval($rsa->fields['precio']);
    $idplantilla = intval($rsa->fields['idplantilla']);
    //$subtotal=$subtotal+floatval($rsa->fields['subtotal']);
    $subtotal = floatval($rsa->fields['subtotal']);
    $subtotalcant = $subtotalcant + $rsa->fields['cantidad'];
    $iddetalle = intval($rsa->fields['iddetalle']);
    $titulo = trim($rsa->fields['titulo_contenedor']);
    $comentario = trim($rsa->fields['obs_producto']);
    $subtotal_producir = $subtotal_producir + $cantidad_producir;
    $iddetalle = intval($rsa->fields['iddeta']);
    $precioventa = floatval($rsa->fields['precioventa']);
    $obs = trim($rsa->fields['obs']);
    $html .= "
                    <tr>
                        <td>".formatomoneda($ipd)."</td>
                        <td >".formatomoneda($cantidad_producir, 3, 'N')."</td>
                        
                        <td align=\"left\" >".utf8_decode($rsa->fields['descripcion'])."&nbsp;";
    if ($rsa->fields['pvinculado'] != '') {
        $html .= " CON ".$rsa->fields['pvinculado'];
    }
    $html .= "    
                        <br />";
    if ($obs != '') {
        $html .= "
                        OBS: &nbsp;<span  style='color:red'>$obs</span>
                        ";
    }
    $html .= "
                        </td>
                        <td>".formatomoneda($precioventa, 3, 'N')."
                        
                        <td>".formatomoneda($rsa->fields['subtotal'])."</td>
                    </tr>
                    
                    ";

    $rsa->MoveNext();
}
$html .= "
            </tr>
            <tr>
                <td style=\"font-weight:bold; font-size:16px;\"><strong>Cantidades: ".formatomoneda($subtotalcant, 2, 'N')."</strong></td>
                <td style=\"font-weight:bold; font-size:16px;\" align=\"right\" colspan=\"4\" ><strong>Total Pedido Gs: ".formatomoneda($monto_pedido_real, 2, 'N')."</strong></td>
            </tr>
            <tr>
                <td style=\"font-weight:bold; font-size:16px;\"><strong></strong></td>
                <td style=\"font-weight:bold; font-size:16px;\" align=\"right\" colspan=\"4\" ><strong>Descuento Aplicado: ".formatomoneda($monto_descuento, 2, 'N')."</strong></td>
            </tr>
            <tr>
                <td style=\"font-weight:bold; font-size:16px;\"><strong></strong></td>
                <td style=\"font-weight:bold; font-size:16px;\" align=\"right\" colspan=\"4\" ><strong>Total Pedido Gs: ".formatomoneda($monto_pedido_actual, 2, 'N')."</strong></td>
            </tr>
            <tr>
                <td colspan=\"6\" align=\"center\"   >
                <span  class=\"button-1\"><a style=\"color:white;\" href=\"$url\" $accion>Regresar</span></a>
                &nbsp;
                <span  class=\"button-1\"><a style=\"color:white;\" href=\"cat_pedidos_pdf_cliente_visor.php?idt=$idtransaccion\" target=\"_blank\"> VER PDF</span></a>
                &nbsp;
                $boton_confirmar
            
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>


";



?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
    <style>
    @page *{
    margin-top: 0cm;
    margin-bottom: 0cm;
    margin-left: 0cm;
    margin-right: 0cm;
    }
    .fondopagina{
        border:1px solid #FFFFFF;
        width:800px;
        min-height:200px;
        margin-top:10px;
        margin-left:auto;margin-right:auto;
        background-image:url('img/fondo_04.jpg') no-repeat;
        #background-size: cover;
    }
    .contenedorppal{
        height:80px;
        width:100%;
        font-size:11px;
        color: #000000;
        margin-top:2%;
    }
    .contenedorppalc{
        color:#b8860b;
        border:0.5px solid #b8860b;
        border-style: dotted;
        height:40px;
        width:650px;
        margin-top:1%;
        margin-left:auto;
        margin-right:auto;
    }
    .contenedorppaldire{
        color:#b8860b;
        border:0.5px solid #b8860b;
        border-style: dotted;
        height:40px;
        width:600px;
        margin-top:2%;
        margin-left:auto;
        margin-right:auto;
    }
    
    .contenedorderechamini{
        color:#b8860b;
        border:0.5px solid #b8860b;
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
    
    .table td{
        border: 1px solid #9F9F9F;
    }
    .table th{
        border: 1px solid #9F9F9F;
    }
</style>
<script>
function modal_formapago(idpago,idpago_afavor){
    var direccionurl='cat_pedidos_new_verificar_final_formapago.php';    
    var parametros = {
      "idpago" : idpago,
      "idpago_afavor" : idpago_afavor
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $('#modal_ventana').modal('show');
            $("#modal_titulo").html('Forma de Pago del Evento');
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}

function modal_formapago_eventos(idpago,idevento,tipofac){
    var direccionurl='cat_pedidos_new_verificar_final_formapago_eventos.php';    
    var parametros = {
      "idpago" : idpago,
      "idevento" : idevento,
      "tipofac" : tipofac
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $('#modal_ventana').modal('show');
            $("#modal_titulo").html('Forma de Pago del Evento');
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'error');
        }
    }).fail( function( jqXHR, textStatus, errorThrown ) {
        errores_ajax_manejador(jqXHR, textStatus, errorThrown, 'fail');
    });
}

</script>
  </head>

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
                    <h2>REVISION DE CARGA</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
<?php echo $html; ?>
<?php
$consulta = "
select * ,
(select usuario from usuarios where cuentas_clientes_pagos_cab.registrado_por = usuarios.idusu) as registrado_por,
(select razon_social from cliente where cliente.idcliente = cuentas_clientes_pagos_cab.idcliente) as cliente
from cuentas_clientes_pagos_cab 
inner join pagos_afavor_adh on pagos_afavor_adh.idpago_afavor = cuentas_clientes_pagos_cab.idpago_afavor
inner join pedidos_eventos on pedidos_eventos.regid = pagos_afavor_adh.idevento
where 
cuentas_clientes_pagos_cab.estado = 1
and pagos_afavor_adh.estado <> 6
and cuentas_clientes_pagos_cab.notanum is null
and pagos_afavor_adh.idevento = $idevento
order by cuentas_clientes_pagos_cab.registrado_el asc;
";
$rscob = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<div class="clearfix" style="
clear: left;
clear: right;
clear: both;
clear: inline-start;
clear: inline-end;
clear: inherit;
"></div><br />
<div style="margin-top:10px;"></div>
<br />        <br />                  
<strong>Recibos de Eventos</strong>


<div class="table-responsive">
    <table border="1" width="100%" class="table table-bordered jambo_table bulk_action" style="border-collapse: collapse;border: 1px solid #9F9F9F;">
      <thead>
            <tr align="center" valign="middle">
              <th></th>
              <th>Nombre Evento</th>
              <th>Recibo</th>
              <th>Monto Anticipo</th>
              <th>Monto Facturado</th>
              <th>Saldo No Facturado</th>
              <th>Fecha Hora</th>
            </tr>
    </thead>    
    <tbody>
<?php
$monto_factu_acum = 0;
$monto_pago_det_acum = 0;
$monto_saldo_acum = 0;

while (!$rscob->EOF) {
    /*$banco=$rscob->fields['banco'];
    $transfer_numero=$rscob->fields['transfer_numero'];
    $tarjeta_boleta=$rscob->fields['tarjeta_boleta'];
    $cheque_numero=$rscob->fields['cheque_numero'];
    $boleta_deposito=$rscob->fields['boleta_deposito'];
    $retencion_numero=$rscob->fields['retencion_numero'];


    $datos_extra="";
    if(trim($banco) != ''){
        $datos_extra.="Banco: $banco<br />";
    }
    if(trim($transfer_numero) != ''){
        $datos_extra.="Transfer Nro.: $transfer_numero<br />";
    }
    if(trim($tarjeta_boleta) != ''){
        $datos_extra.="Voucher Tarj Nro.: $tarjeta_boleta<br />";
    }
    if(trim($cheque_numero) != ''){
        $datos_extra.="Cheque Nro.: $cheque_numero<br />";
    }
    if(trim($boleta_deposito) != ''){
        $datos_extra.="Boleta Dep. Nro.: $boleta_deposito<br />";
    }
    if(trim($retencion_numero) != ''){
        $datos_extra.="Retencion Nro.: $retencion_numero<br />";
    }*/
    $monto_facturado = $rscob->fields['monto_abonado'] - $rscob->fields['saldo'];
    ?>
            <tr align="center" valign="middle">
                <td><a href="javascript:void(0);" onclick="modal_formapago(<?php echo intval($rscob->fields['idpago']); ?>,<?php echo intval($rscob->fields['idpago_afavor']); ?>);" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a></td>
              <td align="left"><?php echo $rscob->fields['nombre_evento']; ?></td>
              <td align="center"><?php echo antixss($rscob->fields['recibo']); ?></td>
              <td align="right"><?php echo formatomoneda($rscob->fields['monto_abonado']); ?></td>
              <td align="right"><?php echo formatomoneda($monto_facturado); ?></td>
              <td align="right"><?php echo formatomoneda($rscob->fields['saldo']); ?></td>
             <!-- <td align="left"><?php echo antixss($rscob->fields['formapago']); ?></td>
              <td align="left"><?php echo $datos_extra; ?></td>-->
              <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rscob->fields['fechahora'])); ?></td>
            </tr>
<?php
$monto_pago_det_acum += $rscob->fields['monto_abonado'];
    $monto_factu_acum += $monto_facturado;
    $monto_saldo_acum += $rscob->fields['saldo'];
    $rscob->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
    <tfoot>
            <tr align="center" valign="middle">
              <td align="left">Totales</td>
              <td align="center"></td>
              <td align="center"></td>
              <td align="right"><?php echo formatomoneda($monto_pago_det_acum); ?></td>
              <td align="right"><?php echo formatomoneda($monto_factu_acum); ?></td>
              <td align="right"><?php echo formatomoneda($monto_saldo_acum); ?></td>
              <td align="left"></td>

            </tr>

      </tfoot>
    </table>
</div>

                
<?php
$consulta = "
select ventas.factura, 'CONTADO' AS condicion, ventas.fecha,
ventas_pedidos_eventos.monto_aplicado as monto_facturado, 
ventas_pedidos_eventos.monto_aplicado as monto_cobrado, 
'' as idpagodetdatos, 
'' as idpagodet, 
'' as idbanco, 
'' as idbanco_propio, 
'' as transfer_numero, 
'' as tarjeta_boleta, 
'' as cheque_numero, 
'' as boleta_deposito, 
'' as retencion_numero, 
'' as idpago_afavor, 
'' as id_denominacion_tarjeta, 
'' as id_forma_procesamiento_pago, 
'' as idevento, 
'' as numero_cheque, 
'' as banco,
'' as banco_propio,
(select pedidos_eventos.nombre_evento from pedidos_eventos where pedidos_eventos.regid =  ventas_pedidos_eventos.idevento) as nombre_evento,
gest_pagos.idpago,ventas.tipo_venta
from ventas 
inner join gest_pagos on gest_pagos.idventa = ventas.idventa
inner join ventas_pedidos_eventos on ventas_pedidos_eventos.idventa = ventas.idventa
where ventas_pedidos_eventos.idevento =  $idevento
and ventas.estado <> 6
and ventas.tipo_venta = 1
union all

select ventas.factura, 'CREDITO' AS condicion, ventas.fecha, 
ventas_pedidos_eventos.monto_aplicado as monto_facturado, 
(
select 
sum(cuentas_clientes_pagos.monto_abonado) as monto_cobrado
from cuentas_clientes_pagos 
inner join cuentas_clientes on cuentas_clientes.idcta =  cuentas_clientes_pagos.idcuenta
where
cuentas_clientes_pagos.estado <> 6
and cuentas_clientes.idventa = ventas.idventa
) as monto_cobrado,
'' as idpagodetdatos, 
'' as idpagodet, 
'' as idbanco, 
'' as idbanco_propio, 
'' as transfer_numero, 
'' as tarjeta_boleta, 
'' as cheque_numero, 
'' as boleta_deposito, 
'' as retencion_numero, 
'' as idpago_afavor, 
'' as id_denominacion_tarjeta, 
'' as id_forma_procesamiento_pago, 
'' as idevento, 
'' as numero_cheque, 
'' as banco,
'' as banco_propio,
(select pedidos_eventos.nombre_evento from pedidos_eventos where pedidos_eventos.regid =  ventas_pedidos_eventos.idevento) as nombre_evento,
'' as idpago,ventas.tipo_venta
from ventas 
inner join ventas_pedidos_eventos on ventas_pedidos_eventos.idventa = ventas.idventa
where 
ventas_pedidos_eventos.idevento = $idevento
and ventas_pedidos_eventos.idevento =  $idevento
and ventas.estado <> 6
and ventas.tipo_venta = 2
order by  fecha asc
";
$rsven = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
/*$consulta="
select pedidos_eventos.nombre_evento, ventas.factura, ventas.fecha,
formas_pago.descripcion as formapago, gest_pagos_det.monto_pago_det,
gest_pagos_det_datos.*,
(select nombre from bancos where idbanco = gest_pagos_det_datos.idbanco) as banco,
(select nombre from bancos where idbanco = gest_pagos_det_datos.idbanco_propio) as banco_propio
from ventas
inner join ventas_pedidos_eventos on ventas_pedidos_eventos.idventa = ventas.idventa
inner join gest_pagos on gest_pagos.idventa = ventas.idventa
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
left join gest_pagos_det_datos on gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
inner join pedidos_eventos on pedidos_eventos.regid = ventas_pedidos_eventos.idevento
where
ventas_pedidos_eventos.idevento is not null
and ventas_pedidos_eventos.idevento = $idevento
and gest_pagos_det_datos.idevento = $idevento
order by pedidos_eventos.nombre_evento asc, ventas.fecha asc
";
$rsven = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));*/
/*
$consulta="
select pedidos_eventos.nombre_evento, ventas.factura, ventas.fecha, ventas_pedidos_eventos.monto_aplicado,
(
inner join gest_pagos on gest_pagos.idventa = ventas.idventa
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
left join gest_pagos_det_datos on gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
)
from ventas
inner join ventas_pedidos_eventos on ventas_pedidos_eventos.idventa = ventas.idventa
inner join pedidos_eventos on pedidos_eventos.regid = ventas_pedidos_eventos.idevento
where
ventas_pedidos_eventos.idevento is not null
and ventas_pedidos_eventos.idevento = $idevento
order by pedidos_eventos.nombre_evento asc, ventas.fecha asc;
";
$rsven = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
*/
/*
para traer y reconstruir luego con excel
select gest_pagos_det_datos.idpagodetdatos, pedidos_eventos.regid as idevento_ev, gest_pagos_det_datos.idevento as idevento_pag, pedidos_eventos.nombre_evento, ventas.factura, ventas.fecha,
formas_pago.descripcion as formapago, gest_pagos_det.monto_pago_det,
gest_pagos_det_datos.*,
(select nombre from bancos where idbanco = gest_pagos_det_datos.idbanco) as banco,
(select nombre from bancos where idbanco = gest_pagos_det_datos.idbanco_propio) as banco_propio
from ventas
inner join ventas_pedidos_eventos on ventas_pedidos_eventos.idventa = ventas.idventa
inner join gest_pagos on gest_pagos.idventa = ventas.idventa
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
left join gest_pagos_det_datos on gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
inner join pedidos_eventos on pedidos_eventos.regid = ventas_pedidos_eventos.idevento
where
ventas_pedidos_eventos.idevento is not null
and gest_pagos_det_datos.idevento is null


order by pedidos_eventos.nombre_evento asc, ventas.fecha asc;
*/
?>
<br />                      
<strong>Facturas de Eventos</strong>




<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action"  style="border-collapse: collapse;border: 1px solid #9F9F9F;">
      <thead>
            <tr align="center" valign="middle">
              <th></th>
              <th>Nombre Evento</th>
              <th>Factura</th>
              <th>Condicion</th>
              <th>Monto Facturado</th>
              <th>Monto Cobrado</th>
              <th>Saldo Factura</th>
              <th>Fecha Hora</th>
            </tr>
    </thead>    
    <tbody>
<?php
$monto_pago_det_acum = 0;
while (!$rsven->EOF) {
    $banco = $rsven->fields['banco'];
    $banco_propio = $rsven->fields['banco_propio'];
    $transfer_numero = $rsven->fields['transfer_numero'];
    $tarjeta_boleta = $rsven->fields['tarjeta_boleta'];
    $cheque_numero = $rsven->fields['cheque_numero'];
    $boleta_deposito = $rsven->fields['boleta_deposito'];
    $retencion_numero = $rsven->fields['retencion_numero'];


    $datos_extra = "";
    if (trim($banco) != '') {
        $datos_extra .= "Banco Cliente: $banco<br />";
    }
    if (trim($banco_propio) != '') {
        $datos_extra .= "Banco Destino: $banco_propio<br />";
    }
    if (trim($transfer_numero) != '') {
        $datos_extra .= "Transfer Nro.: $transfer_numero<br />";
    }
    if (trim($tarjeta_boleta) != '') {
        $datos_extra .= "Voucher Tarj Nro.: $tarjeta_boleta<br />";
    }
    if (trim($cheque_numero) != '') {
        $datos_extra .= "Cheque Nro.: $cheque_numero<br />";
    }
    if (trim($boleta_deposito) != '') {
        $datos_extra .= "Boleta Dep. Nro.: $boleta_deposito<br />";
    }
    if (trim($retencion_numero) != '') {
        $datos_extra .= "Retencion Nro.: $retencion_numero<br />";
    }
    $saldo_factura = $rsven->fields['monto_facturado'] - $rsven->fields['monto_cobrado'];

    $monto_facturado_acum += $rsven->fields['monto_facturado'];
    $monto_pago_det_acum += $rsven->fields['monto_cobrado'];
    $saldo_factura_acum += $saldo_factura;
    ?>
            <tr align="center" valign="middle">
            <td><a href="javascript:void(0);" onclick="modal_formapago_eventos(<?php echo intval($rsven->fields['idpago']); ?>,<?php echo $idevento ?>,<?php echo intval($rsven->fields['tipo_venta']); ?>);" class="btn btn-sm btn-default" title="Detalle" data-toggle="tooltip" data-placement="right"  data-original-title="Detalle"><span class="fa fa-search"></span></a></td>
              <td align="left"><?php echo $rsven->fields['nombre_evento']; ?></td>
              <td align="center"><?php echo antixss($rsven->fields['factura']); ?></td>
              <td align="center"><?php echo antixss($rsven->fields['condicion']); ?></td>
              <td align="right"><?php echo formatomoneda($rsven->fields['monto_facturado']); ?></td>
              <td align="right"><?php echo formatomoneda($rsven->fields['monto_cobrado']); ?></td>
              <td align="right"><?php echo formatomoneda($saldo_factura); ?></td>
              <td align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsven->fields['fecha'])); ?></td>
            </tr>
<?php $rsven->MoveNext();
} //$rs->MoveFirst();?>
      </tbody>
      <tfoot>
            <tr align="center" valign="middle">
                <td></td>
              <td align="left">Totales</td>
              <td align="center"></td>
              <td align="left"></td>
              <td align="right"><?php echo formatomoneda($monto_facturado_acum); ?></td>
              <td align="right"><?php echo formatomoneda($monto_pago_det_acum); ?></td>
              <td align="right"><?php echo formatomoneda($saldo_factura_acum); ?></td>
              <td align="center"></td>
            </tr>
      </tfoot>
    </table>
</div>

<?php /*  ?>
<hr />

<strong>Resumen por Forma de pago de Eventos</strong>
<?php


$consulta="
select formapago, sum(monto_pago_det) as monto
from (
select
formas_pago.descripcion as formapago, gest_pagos_det.monto_pago_det
from pagos_afavor_adh
inner join cuentas_clientes_pagos_cab on cuentas_clientes_pagos_cab.idpago_afavor = pagos_afavor_adh.idpago_afavor
inner join gest_pagos on gest_pagos.idpago = pagos_afavor_adh.idpago
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
left join gest_pagos_det_datos on gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
inner join pedidos_eventos on pedidos_eventos.regid = pagos_afavor_adh.idevento
where
pagos_afavor_adh.idevento is not null
and cuentas_clientes_pagos_cab.estado <> 6
and pagos_afavor_adh.idevento = $idevento
union all
select
formas_pago.descripcion as formapago, gest_pagos_det.monto_pago_det as monto
from ventas
inner join gest_pagos on gest_pagos.idventa = ventas.idventa
inner join gest_pagos_det on gest_pagos_det.idpago = gest_pagos.idpago
left join gest_pagos_det_datos on gest_pagos_det_datos.idpagodet = gest_pagos_det.idpagodet
inner join formas_pago on formas_pago.idforma = gest_pagos_det.idformapago
where
gest_pagos_det_datos.idevento = $idevento
and ventas.estado <> 6
) fp
group  by formapago
order by formapago asc
";
$rsfp = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

?>



<div class="table-responsive">
    <table width="100%" class="table table-bordered jambo_table bulk_action"  style="border-collapse: collapse;border: 1px solid #9F9F9F;">
      <thead>
        <tr>

            <th align="center">Forma Pago</th>
            <th align="center">Monto</th>
        </tr>
      </thead>
      <tbody>
<?php
$monto_acum=0;
while(!$rsfp->EOF){
$monto_acum+=$rsfp->fields['monto'];
?>
        <tr>

            <td align="left"><?php echo antixss($rsfp->fields['formapago']); ?></td>
            <td align="right"><?php echo formatomoneda($rsfp->fields['monto']); ?></td>

        </tr>
<?php

$rsfp->MoveNext(); } //$rs->MoveFirst(); ?>
      </tbody>
      <tfoot>
        <tr>
            <td>Totales</td>
            <td align="right"><?php echo formatomoneda($monto_acum); ?></td>
        </tr>
      </tfoot>
    </table>
</div>
<br />
<?php */ ?>                      
                      
    </div>                  
                      
                      
<div class="clearfix"></div>
<br /><br />

<br /><br />

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 
            

            
    
            
          </div>
        </div>
        <!-- /page content -->

        <!-- POPUP DE MODAL OCULTO -->
<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="modal_ventana">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button>
                   <h4 class="modal-title" id="modal_titulo">Titulo</h4>
            </div>
            <div class="modal-body" id="modal_cuerpo">
                Contenido...
            </div>
            <div class="modal-footer" id="modal_pie">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        
        </div>
    </div>
</div>
        <!-- POPUP DE MODAL OCULTO -->

        <!-- footer content -->
        <?php require_once("includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("includes/footer_gen.php"); ?>
  </body>
</html>
