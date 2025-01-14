<?php 
//if (!isset($_SESSION)) { 
//    session_start(); 
//}
//require_once("includes/conexion.php");
//require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo="1";

//require_once('includes/funciones_busqueda.php');

//$url1="http://181.94.221.41:1704/wsrde/busquedaClientes?busqueda=";

//print_r($_SESSION);
$codclie=antisqlinyeccion($_POST['codclie'],'int');
//$codclie=str_replace("'","",$codclie);
$rz=antisqlinyeccion($_POST['rz'],'text');
$ruc=antisqlinyeccion($_POST['ruc'],'text');
$idvendedor=intval(21);//$_SESSION['idvendedor']);
$idzona=intval( 0);//$_SESSION['idzona']);

$ahora=date("Y-m-d");
$buscar="Select idcliente from tempocar where idvendedor=$idvendedor and estado=3 and date(confirmado_el)='$ahora'";
// $buscar="Select * from tempocar where idvendedor=$idvendedor and (estado=1 or estado=3) and fecha=CURDATE()";
$rs = $conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
$clientes_visitados=[];
while (!$rs->EOF){
    $clientes_visitados[] = $rs->fields['idcliente'];
    $rs->MoveNext();
}

$diaDeLaSemana = date("N");
$url1="http://181.94.221.41:1704/wsrde/busquedaClientesFilterAll?codVendedor=$idvendedor&codZona=$idzona";
//echo $url1;exits;


$rz=str_replace("'","",$rz);
$ruc=str_replace("'","",$ruc);

if($codclie>0){
    $url1=$url1."&codcliente=$codclie&tipoCampo=CODIGO_CLIENTE";
    $metodo=1;
}
//echo $url;exit;
if ($ruc!='' && $ruc!='NULL'){
    $url1=$url1."ruc&tipoCampo=RUC";
    $metodo=2;
}
if ($rz!='' && $rz!='NULL'){
    $url1=$url1."rz&tipoCampo=RAZON_SOCIAL";
    $metodo=3;
}
$urlconsulta=$url1;
// TODO: OCULTANDO CONSULTA
// echo $urlconsulta;
//$respuesta=file_get_contents("$urlconsulta");

$respuesta=abrir_url_simple($url1);
$datos=json_decode($respuesta,true);
$datos_clientes_del_dia=[];
foreach($datos as $dato) {
    if(trim($dato['radio'])==$diaDeLaSemana){
        $datos_clientes_del_dia[]=$dato;
        // $codi = $dato['codCliente'];
        // $url1="http://181.94.221.41:1704/wsrde/busquedaSaldoLimite?busqueda=$codi&tipoCampo=CODIGO_CLIENTE";
        // $respuesta=file_get_contents("$url1");
    }
}
$datos= $datos_clientes_del_dia;
// ordenar ascendente por existencia del articulo
function compararKeys($a, $b)
{
    if ($a === null && $b === null) {
        return 0;
    } elseif ($a === null) {
        return 1;
    } elseif ($b === null) {
        return -1;
    } else {
        return ($a <=> $b);
    }
}
uksort($datos, 'compararKeys');
function compararPorOrden($a, $b) {
    if ($a['orden'] === null) {
        return 1;
    }
    // Si $b['orden'] es nulo, colocarlo al final del array
    if ($b['orden'] === null) {
        return -1;
    }
    // Comparar normalmente si ambos valores de 'orden' son distintos de nulo
    return $a['orden'] - $b['orden'];
}

// Ordenar el array utilizando la función de comparación
usort($datos, 'compararPorOrden');
// echo var_dump($datos);exit;
if ($metdodo==1){
    //es un codigo fijo de cliente, por lo cual directo devolvemos el array oara tocar
    print_r($datos);
} else {
    
    
    
    
    ?>
    <script>
         function google_maps_flutter(t) {
            var result = '{"metodo":"RDE_GEOLOCATOR", "lat":"-25.28754","long":"'+t+'"}';
            window.flutter_inappwebview.callHandler('ApiChannel', result);
        }
    </script>
    <style>
        .hover_table tr:hover td {
        background-color: #D9DEE4;
    }
    </style>
    <div class="table-responsive">
    <table class="table table-bordered hover_table">
        <thead>
            <tr>
                <th colspan="5">Total de clientes asignados: <span id="numero_clientes"><?php echo (count($datos))?></span> </th>
            </tr>
            <tr>
                <th colspan="5">Quedan <span id="numero_clientes"><?php echo (count($datos) - count($clientes_visitados))?></span> Clientes por Visitar</th>
            </tr>
            <tr>
                <th>INICIAR PEDIDO</th>
                <th>No Compra</th>
                <th>Ruc</th>
                <th>Razon Social</th>
                <th>Direccion</th>
                <th>Codigo Cliente</th>
                
            </tr>
        </thead>
        <tbody>
        <?php    
        $tres=0;
            foreach ($datos as $dato){
                if (!in_array($dato['codCliente'], $clientes_visitados)) {

                    $tres=$tres+1;
                    $rz=$dato['razonSocial'];
                    $ruc=$dato['ruc'];
                    $codi=$dato['codCliente'];
                    $lc=$dato['lineaCredito'];
                    $sdispo=$dato['saldoLineaCredito'];
                    $codlistap=$dato['codListaPrecio'];
                    $factupendientestotal=floatval($dato['sumatoriaFacturasPendientes']);
                    $tienependiente=$dato['facturasVencida'];
                    $direccion=$dato['direccion'];
                    $latitud=$dato['latitud'];
                    $longitud=$dato['longitud'];
                    $userAgent = $_SERVER['HTTP_USER_AGENT'];
                    $codLista=$dato['codListaPrecio'];
                    $link=null;
                    // if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false) {
                    //     // Es un dispositivo móvil
                    //     $link="geo:".$latitud.",".$longitud."?q=".$latitud.",".$longitud."";
                    // } else {
                        // Es una computadora
                        // $link="https://www.google.com/maps?q=".$latitud.",".$longitud."";
                        $link="".$latitud.",".$longitud."";
                    // }
                
                    $compuesto=$rz."|".$codi."|".$ruc."|".formatomoneda($lc,4,'N')."|".formatomoneda($sdispo,4,'N')."|".formatomoneda($factupendientestotal,4,'N');
                
        ?>
            <tr>
                <td><a href="javascript:void(0);" onclick="seleccionarcliente(<?php echo $codi ?>,<?php echo $codLista; ?>)" class="btn btn-sm btn-default" style="color:#5A738E;border-radius: 3px;display:flex;justify-content: center;margin: 0;padding: 4px;align-items: center;" ><span class="fa fa-shopping-cart  fa-1x" style="color:#5A738E;"></span>&nbsp;PEDIR</a></td>
                <td> <a href="javascript:void(0);" class="btn btn-sm btn-default" onclick="cargaMotivo(<?php echo $codm ?>)><span class="fa fa-shopping-cart  fa-1x" style="color:#5A738E;"></span> </a></td>
                <td><?php echo $ruc; ?></td>
                <td><?php echo $rz; ?></td>
                <td style="<?php echo empty($latitud)? "" :"display: flex;" ?>justify-content: space-between;"><?php echo $direccion; ?><a  href="javascript:void(0);" class=" btn btn-default fa fa-map-marker <?php echo empty($latitud)? " hide" :"" ?>" onclick="google_maps_flutter('<?php echo $link ?>')"  ></a></td>
                <td><?php echo formatomoneda($codi,4,'N'); ?>
                <input type="hidden" name="ocdclie_<?php echo $codi ?>" id="ocdclie_<?php echo $codi ?>" value="<?php echo $compuesto ?>" />
                </td>
            </tr>

        <?php 
        }
            }// del foreach
            ?>
            </tbody>
        </table>
        </div>
    <?php
    }
?>
<input type="hidden" name="octr_<?php echo $codi ?>" id="octr_<?php echo $codi ?>" value="<?php echo $compuesto ?>" />

