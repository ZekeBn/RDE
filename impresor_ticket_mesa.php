 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "2";

require_once("includes/rsusuario.php");

$idmesa = intval($_GET['idmesa']);
$idatcdet = intval($_GET['idatcdet']);

$modredir = intval($_GET['modredir']);
if ($modredir == 1) {
    $url1 = "cuenta_mesas.php";
}
if ($modredir == 2) {
    $url1 = "separa_cuenta_new.php";
}
/*
function getOS($user_agent) {

    //global $user_agent;

    $os_platform  = "Desconocido";

    $os_array     = array(
                          '/windows nt 10/i'      =>  'Windows 10',
                          '/windows nt 6.3/i'     =>  'Windows 8.1',
                          '/windows nt 6.2/i'     =>  'Windows 8',
                          '/windows nt 6.1/i'     =>  'Windows 7',
                          '/windows nt 6.0/i'     =>  'Windows Vista',
                          '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                          '/windows nt 5.1/i'     =>  'Windows XP',
                          '/windows xp/i'         =>  'Windows XP',
                          '/windows nt 5.0/i'     =>  'Windows 2000',
                          '/windows me/i'         =>  'Windows ME',
                          '/win98/i'              =>  'Windows 98',
                          '/win95/i'              =>  'Windows 95',
                          '/win16/i'              =>  'Windows 3.11',
                          '/macintosh|mac os x/i' =>  'Mac OS X',
                          '/mac_powerpc/i'        =>  'Mac OS 9',
                          '/linux/i'              =>  'Linux',
                          '/ubuntu/i'             =>  'Ubuntu',
                          '/iphone/i'             =>  'iPhone',
                          '/ipod/i'               =>  'iPod',
                          '/ipad/i'               =>  'iPad',
                          '/android/i'            =>  'Android',
                          '/blackberry/i'         =>  'BlackBerry',
                          '/webos/i'              =>  'Mobile'
                    );

    foreach ($os_array as $regex => $value)
        if (preg_match($regex, $user_agent))
            $os_platform = $value;

    return $os_platform;
}
*/


// trae la primera impresora
$consulta = "SELECT * FROM impresoratk where idsucursal = $idsucursal and borrado = 'N' and tipo_impresora = 'CAJ' order by idimpresoratk asc limit 1";
$rsimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
//echo $consulta;exit;
$pie_pagina = $rsimp->fields['pie_pagina'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora = trim($rsimp->fields['script']);
$idimpresoratk = $rsimp->fields['idimpresoratk'];
//echo $script_impresora;exit;
if (trim($script_impresora) == '') {
    $script_impresora = $defaultprnt;
}
/*

$user_agent=trim($_SERVER['HTTP_USER_AGENT']);
$os=getOS($user_agent);
$os=htmlentities($os);
$os_mobiles=array('ANDROID','IPHONE','IPOD','IPAD','BLACKBERRY','MOBILE');
if(in_array(strtoupper($os),$os_mobiles)){
    //$script_impresora="http://192.168.100.237/impresorweb/ladocliente_tkfox.php";
    // buscar impresora remota si existe
    $consulta="SELECT * FROM impresoratk where idsucursal = $idsucursal
    and borrado = 'N' and tipo_impresora='REM' order by idimpresoratk  asc limit 1";
    $rsimprem = $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // si existe reemplaza, caso contrario usa la de caja que trae arriba
    if(trim($rsimprem->fields['script']) != ''){
        $script_impresora=trim($rsimprem->fields['script']);
        $rsimp=$rsimprem;
    }

}
*/

// actualiza monto de cabecera
$consulta = "
update tmp_ventares_cab 
    set 
    monto = (
                COALESCE
                (
                    (
                        select sum(subtotal) as total_monto
                        from tmp_ventares
                        where
                        tmp_ventares.idempresa = tmp_ventares_cab.idempresa
                        and tmp_ventares.idsucursal = tmp_ventares_cab.idsucursal
                        and tmp_ventares.borrado = 'N'
                        and tmp_ventares.borrado_mozo = 'N'
                        and tmp_ventares.idtmpventares_cab = tmp_ventares_cab.idtmpventares_cab
                    )
                ,0)
                
            )
    WHERE
    idmesa = $idmesa
    and idventa is null
";
$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

/*
$buscar="Select max(idatc) as mayor from mesas_atc where idmesa=$idmesa and estado<>6";
$rsmy=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
$idatc=intval($rsmy->fields['mayor']);
*/

if ($idmesa > 0) {
    // busca si tiene atc activo
    $consulta = "
    select idatc from mesas_atc 
    where 
    idmesa = $idmesa 
    and estado = 1 
    order by idatc desc 
    limit 1
    ";
    $rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idatc = intval($rsatc->fields['idatc']);
    // si tiene atc activo
    if ($idatc > 0) {
        $update = "Update mesas set estado_mesa=3 where idmesa=$idmesa";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
    }
}

$consulta = "
select idatc from mesas_atc where idmesa = $idmesa and estado = 1 order by idatc desc limit 1
";
$rsatc = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idatc = $rsatc->fields['idatc'];

if ($rsco->fields['ticket_fox'] == 'S') {


    $ticket_json = preticket_mesa_json($idatc);


} else {



    //echo $idmesa;
    if ($idmesa == 0) {
        echo "Mesa Inexistente o no tiene cuenta activa!";
        exit;
    }
    $consulta = " 
    select numero_mesa, nombre, idmesa
    from mesas
    inner join salon on mesas.idsalon = salon.idsalon
    where 
    idmesa = $idmesa
    and salon.idsucursal = $idsucursal
    ";
    //echo $consulta;
    $rsmes = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $numeromesa = $rsmes->fields['numero_mesa'];
    $salon = $rsmes->fields['nombre'];
    $idmesa = intval($rsmes->fields['idmesa']);
    if ($idmesa == 0) {
        echo "Mesa Inexistente.";
        exit;
    }

    // tipo de impresor
    $impresor_tip = "MES";
    $redir_impr = "impresor_ticket_mesa.php";

    // parametros
    $consolida = $rsimp->fields['consolida'];
    $leyenda_credito = $rsimp->fields['leyenda_credito'];
    $datos_fiscal = $rsimp->fields['datos_fiscal'];
    $muestra_nombre = $rsimp->fields['muestra_nombre'];
    $usa_chapa = $rsimp->fields['usa_chapa'];
    $usa_obs = $rsimp->fields['usa_obs'];
    $usa_precio = $rsimp->fields['usa_precio'];
    $usa_total = $rsimp->fields['usa_total'];
    $usa_nombreemp = $rsimp->fields['usa_nombreemp'];
    $usa_totaldiscreto = $rsimp->fields['usa_totaldiscreto'];
    $txt_codvta = $rsimp->fields['txt_codvta'];
    $cabecera_pagina = $rsimp->fields['cabecera_pagina'];
    $pie_pagina = $rsimp->fields['pie_pagina'];
    $usa_enfasis = $rsimp->fields['usa_enfasis'];
    $propina_porcentajes = $rsimp->fields['propina_porcentajes'];
    $propina_libre = $rsimp->fields['propina_libre'];
    $propina_sinpropina = $rsimp->fields['propina_sinpropina'];


    //recalcular ticket mesa
    $parametros_entrada = [
        'idatc' => $idatc,
        'quien' => $idusu,
        'idmesa' => $idmesa,
        'idempresa' => $idempresa,
        'idsucursal' => $idsucursal
    ];
    recalcular_servicio_mesa($parametros_entrada);
    require_once("impresor_motor.php");

}
// buscar impresora remota para app
$consulta = "
SELECT * 
FROM impresoratk 
where 
borrado = 'N' 
and tipo_impresora='REM' 
and idsucursal = $idsucursal
order by idimpresoratk  asc 
limit 1
";
$rsimprem = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
// si no existe impresora remota para la sucursal actual
if (intval($rsimprem->fields['idimpresoratk']) == 0) {
    // trae de cualquier sucursal
    $consulta = "
    SELECT * 
    FROM impresoratk 
    where 
    borrado = 'N' 
    and tipo_impresora='REM' 
    order by idimpresoratk  asc 
    limit 1
    ";
    $rsimprem = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
}

// si existe reemplaza, caso contrario usa la de caja que trae arriba
if (trim($rsimprem->fields['script']) != '') {
    $script_impresora_app = trim($rsimprem->fields['script']);
    $rsimp = $rsimprem;
}
$pie_pagina = $rsimp->fields['pie_pagina'];
$metodo_app = $rsimp->fields['metodo_app'];
$defaultprnt = "http://localhost/impresorweb/ladocliente.php";
$script_impresora_rem = trim($rsimp->fields['script']);
$metodo_app = $rsimprem->fields['metodo_app'];
$version_app = $rsimp->fields['version_app'];
$version_app_orig = $rsimp->fields['version_app'];
$tipo_saltolinea_app = $rsimp->fields['tipo_saltolinea_app'];
//echo $version_app;exit;
if (trim($script_impresora_rem) == '') {
    $script_impresora_rem = $defaultprnt;
}
if (intval($version_app) == 0) {
    $version_app = 1;
}

/*
$texto="hola
mundo";*/

// auto impresor para app
// ticket para app
if ($tipo_saltolinea_app != '') {
    $factura_auto_app = str_replace($saltolinea, $tipo_saltolinea_app, $texto); // \\r
} else {
    $factura_auto_app = $texto;
}
$factura_auto_app = str_replace("'", "", $factura_auto_app);
$factura_auto_app = str_replace('"', '', $factura_auto_app);
/*$factura_auto_app=str_replace('[','(',$factura_auto_app);
$factura_auto_app=str_replace(']',')',$factura_auto_app);*/
$texto_app = $factura_auto_app;
//$url1="reimprimir_facturas_retro.php";

// lista de post a enviar
if ($metodo_app == 'POST_URL') {
    $lista_post = [
        'tk' => $texto_app,
        'tk_json' => $ticket_json
    ];
}
//parametros para la funcion
$parametros_array_tk = [
    'texto_imprime' => $texto_app, // texto a imprimir
    'url_redir' => $url1, // redireccion luego de imprimir
    'lista_post' => $lista_post, // se usa solo con metodo POST_URL
    'imp_url' => $script_impresora_rem, // se usa solo con metodo POST_URL
    'metodo' => $metodo_app // POST_URL, SUNMI, ''
];
$parametros_app = [
    'parametros_tk' => $parametros_array_tk,
    'div_msg' => 'impresion_box',
    'version_app' => $version_app
];


$js_app = javascript_app_webview($parametros_app);


?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Imprimir</title>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">
<?php echo $js_app['funcion_externa']; ?>
function imprime_cliente(){
// impresor app
<?php echo $js_app['inicio']; ?>
        var texto = document.getElementById("texto").value;
            var parametros = {
                    "tk"       : texto,
                    "tk_json" : '<?php echo $ticket_json; ?>',
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
                            //$("#impresion_box").html(response);    
                            //si impresion es correcta marcar
                            var str = response;
                            var res = str.substr(0, 18);
                            //alert(res);
                            if(res == 'Impresion Correcta'){
                                //marca_impreso('<?php echo $id; ?>');
                                document.body.innerHTML = "Impresion Enviada!";
                                $('#reimprimebox',window.parent.document).html('');
                            }else{
                                $("#impresion_box").html(response);    
                            }
                            
                            // si no es correcta avisar para entrar al modulo de reimpresiones donde se pone la ultima impresion correcta y desde ahi se marca como no impreso todas las que le siguen
                            
                    },
            error: function(jqXHR, textStatus, errorThrown) {
                if(jqXHR.status == 404){
                    alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
                }else if(jqXHR.status == 0){
                    alert('Se ha rechazado la conexión. \n URL: <?php echo $script_impresora; ?> \n OS: <?php echo $os; ?>');
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
    // impresor app final
<?php echo $js_app['final']; ?>
}
// si es la app
<?php echo trim($js_app['if_es_app_inicio']).$saltolinea; ?>
    // si se cargo la pagina 
    <?php echo trim($js_app['document_ready_app_ini']).$saltolinea; ?>
        imprime_cliente();
    <?php echo trim($js_app['document_ready_app_fin']).$saltolinea; ?>
<?php echo trim($js_app['if_es_app_fin']).$saltolinea; ?>
// si no es la app
<?php echo trim($js_app['if_no_app_inicio']).$saltolinea; ?>
    // ejecutar al cargar la pagina
    $( document ).ready(function() {
        imprime_cliente();
    });
<?php echo trim($js_app['if_no_app_fin']).$saltolinea; ?>
</script>
</head>
<body bgcolor="#CCCCCC">
<?php echo $js_app['html']; ?>
<div style="width:290px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px; text-align:center; min-height:50px;" id="impresion_box">
<p align="center"><input type="button" value="imprimir" style="padding:10px;" onClick="imprime_cliente();"></p>
</div><br />
<div style="width:290px; border:1px solid; margin:0px auto; background-color:#FFFFFF; padding:5px;">
<textarea style="display:; width:310px; height:500px;" id="texto"><?php echo $texto; ?></textarea>
<pre>
<?php //echo $texto;?>
</pre>
</div>
</body>
</html>
