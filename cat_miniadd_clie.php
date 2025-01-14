 <?php
/*----------------------------------------------------------
Registra y selecciona clientes para pedidos catering
UR:10/11/2021
-----------------------------------------------------------
*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "29";
$submodulo = "347";
require_once("includes/rsusuario.php");

$valido = "S";
$errores = "";

$idc = intval($_POST['idc']);
$add = intval($_POST['add']);
if ($idc > 0 && $add == 1) {
    //actualizacion de cliente
    $ruc = antisqlinyeccion($_POST['ruc'], 'text');
    $celular = antisqlinyeccion($_POST['cel'], 'text');
    $email = antisqlinyeccion(strtolower($_POST['ema']), 'text');
    $rz = antisqlinyeccion($_POST['rz'], 'text');

    $update = "update cliente set razon_social=$rz,ruc=$ruc,celular=$celular,email=$email where idcliente=$idc";
    //echo $update;exit;
    $conexion->Execute($update) or die(errorpg($conexion, $update));
    $rz = str_replace("'", "", $rz);
    echo $idc.'|'.$rz;
    exit;
}
if ($idc == 0 && $add == 1) {

    //registro de cliente para pedido
    $nombres = antisqlinyeccion($_POST['nombres'], 'text');
    $apellidos = antisqlinyeccion($_POST['apellidos'], 'text');
    $nomape = $nombres." ".$apellidos;
    $tipocliente = intval($_POST['tipocliente']);

    $documento = antisqlinyeccion($_POST['dc'], 'int');
    $dc = intval($_POST['dc']);

    $cel = antisqlinyeccion($_POST['cel'], 'text');
    $email = antisqlinyeccion(strtolower($_POST['ema']), 'text');
    //registro de cliente para facturacion
    $ruc = antisqlinyeccion($_POST['ruc'], 'text');
    $rz = antisqlinyeccion($_POST['rz'], 'text');


    $obs = antisqlinyeccion($_POST['obs'], 'text');
    $errores = "";
    //print_r($_POST);
    if ($tipocliente == 0) {
        $errores .= "* Debe indicar el tipo de cliente. -";
    }
    if ($_POST['ruc'] == '') {
        $errores .= "* El ruc no ha sido indicado. -";
    }
    if ($_POST['nombres'] == '') {
        $errores .= "* Nombre debe ser indicado para pedido. -";
    }
    if ($_POST['apellidos'] == '') {
        $errores .= "* Apellido debe ser indicado para pedido. -";
    }
    if ($_POST['rz'] == '') {
        $errores .= "* Razon social debe ser indicada para facturacion. -";
    }
    if ($_POST['ema'] == '') {
        $errores .= "* Email debe ser requerido para envio de pedidos. -";
    }
    if ($_POST['cel'] == '') {
        $errores .= "* Debe indicar al menos un celular. -";
    }
    //verificamos si existe como cliente el documento

    $consulta = "
    select * from cliente where ruc = $ruc and idempresa = $idempresa order by idcliente asc limit 1";
    $rscli = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

    $idcliente = intval($rscli->fields['idcliente']);
    // sino existe valida
    if ($idcliente == 0) {
        $parametros_array = [
            'idclientetipo' => $tipocliente,
            'ruc' => $_POST['ruc'],
            'razon_social' => $_POST['rz'],
            'documento' => $_POST['dc'],
            'fantasia' => $_POST['fantasia'],
            'nombre' => $_POST['nombres'],
            'apellido' => $_POST['apellidos'],
            'idvendedor' => '',
            'sexo' => '',
            'nombre_corto' => $_POST['nombre_corto'],
            'idtipdoc' => $_POST['idtipdoc'],


            'telefono' => $telefono,
            'celular' => $_POST['cel'],
            'email' => $_POST['ema'],
            'direccion' => $direccion,
            'comentario' => $_POST['comentario'],
            'fechanac' => $_POST['fechanac'],

            'ruc_especial' => $_POST['ruc_especial'],
            'idsucursal' => $idsucursal,
            'idusu' => $idusu,


        ];

        $res = validar_cliente($parametros_array);
        if ($res['valido'] != 'S') {
            $valido = $res['valido'];
            $errores .= nl2br($res['errores']);
        }
    }

    ///echo $errores;
    if ($errores == '') {

        //Primero verificamos si el cliente de pedido existe o no
        $buscar = "select * from cliente_pedido where dcped=$documento";
        $rsclipedido = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $cel = str_replace("'", "", $cel);
        $nomape = str_replace("'", "", $nomape);
        //echo $buscar;exit;
        if ($rsclipedido->fields['idclienteped'] == 0) {
            $consulta = "
            insert into cliente_pedido
            (idcliente, nombres, apellidos,nomape, telefono, fec_ultactualizacion, creado_por, creado_el, idempresa,dcped)
            values
            (0, $nombres, $apellidos,'$nomape',  '$cel', '$ahora', $idusu, '$ahora', $idempresa,'$documento')";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $buscar = "Select * from cliente_pedido where dcped=$documento";
            $rg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idclientepedido = intval($rg->fields['idclienteped']);
        } else {
            $idclientepedido = intval($rsclipedido->fields['idclienteped']);
        }

        // sino existe inserta solo para facturacion
        if ($idcliente == 0) {
            /*$consulta="
            Insert into cliente
            (idempresa,nombre,apellido,ruc,documento,direccion,celular,razon_social)
            values
            ($idempresa,NULL,NULL,$ruc,NULL,NULL,$celular,$rz)
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            // busca en clientes el que acabamos de insertar
            $consulta="
            select * from cliente where ruc = $ruc and idempresa = $idempresa order by idcliente asc limit 1
            ";
            $rscli=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            $idcliente=intval($rscli->fields['idcliente']);*/
            if ($valido == 'S') {
                $res = registrar_cliente($parametros_array);
                $idcliente = $res['idcliente'];

            }
        }



        $buscar = "Select idclienteped,nomape from cliente_pedido where creado_por=$idusu and estado=1 and dcped='$documento' order by idclienteped desc limit 1";
        $rvg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idclientepedido = ($rvg->fields['idclienteped']);
        $rzpedi = trim($rvg->fields['nomape']);
        $idc = intval($idcliente);
        $rz = str_replace("'", "", $rz);
        echo $idc.'|'.$rz."|".$idclientepedido."|".$rzpedi;
        exit;
    } else {
        echo 'error';
        //exit;
        $error = 1;
    }
}

/*---------------------------------------------------------busqueda---------------------------------*/
$valorbusqueda = antisqlinyeccion($_POST['valorbusca'], 'text');
$metodobusca = intval($_POST['metodobusca']);

$v_rz = trim($_POST['bus_rz']);
$v_ruc = trim($_POST['bus_ruc']);
$v_doc = trim($_POST['bus_doc']);

$add = '';
$order = "order by razon_social asc limit 20";


if ($v_rz != '') {
    $ra = antisqlinyeccion($_POST['bus_rz'], 'text');
    $ra = str_replace("'", "", $ra);
    $len = strlen($ra);
    // armar varios likes por cada palabra
    $v_rz_ar = explode(" ", $v_rz);
    foreach ($v_rz_ar as $palabra) {
        $add .= " and razon_social like '%$palabra%' ";
    }
    $order = "
    order by 
    CASE WHEN
        substring(razon_social from 1 for $len) = '$ra'
    THEN
        0
    ELSE
        1
    END asc, 
    razon_social asc
    Limit 20
    ";
}
if ($v_doc != '') {
    $documento = antisqlinyeccion($_POST['bus_doc'], 'int');
    $documento = intval($documento);
    $add = " and documento like '$documento%' ";
    $order = "order by razon_social asc limit 20";
}
if ($v_ruc != '') {
    $ru = antisqlinyeccion($_POST['bus_ruc'], 'text');
    $ru = str_replace("'", "", $ru);
    $add = " and ruc like '$ru%'";
    $order = "order by razon_social asc limit 20";
}
$buscar = "
Select * , sucursal_cliente.direccion as direccion
from cliente 
inner join sucursal_cliente on sucursal_cliente.idcliente = cliente.idcliente
where 
cliente.estado = 1 
and sucursal_cliente.estado = 1
$add 
$order
";
$rfg1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$td = $rfg1->RecordCount();






/*
if ($metodobusca==1){
    //dc
    $addbus=" dcped=$valorbusqueda ";
    $dc=str_replace("'","",$valorbusqueda);
    //buscamos por documento identidad para ver si ya es cliente.
    $buscar="Select * from cliente_pedido where dcped='$dc'";
    $rfg1=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $td=$rfg1->RecordCount();
    $idcliente=intval($rfg1->fields['idcliente']);
    $idclientepedido=intval($rfg1->fields['idclienteped']);
    $td=$rfg1->RecordCount();
    //echo $buscar;
}
if ($metodobusca==2){
    //rz
    $dc='';
    $valorbusqueda=str_replace("'","",$valorbusqueda);


    $buscar="Select * from cliente_pedido where nomape like('%$valorbusqueda%') and estado <> 6";
    $rfg1=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $idcliente=intval($rfg1->fields['idcliente']);
    $idclientepedido=intval($rfg1->fields['idclienteped']);
    $td=$rfg1->RecordCount();

}
*/
if ($td == 1) {
    $idc = intval($rfg1->fields['idcliente']);
    $idcliente = $idc;
    $rz = trim($rfg1->fields['nomape']);
    $cel = trim($rfg1->fields['telefono']);
    $email = strtolower(trim($rfg1->fields['email']));
    $nombres = trim($rfg1->fields['nombres']);
    $apellidos = trim($rfg1->fields['apellidos']);
    $nomape = $nombres.' '.$apellidos;
    $celular = trim($rfg1->fields['celular']);
    //si posee un id de cliente
    $buscar = "Select * from cliente where idcliente=$idcliente";
    $rfactu = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $dsp = "display";
} else {
    if ($td > 1) {
        $dsp = "none";
    } else {
        $dsp = "display";
    }
}
$cel = str_replace("'", "", $cel);
?>
    <?php if ($error == 1) { ?>
        <div class="alert alert-danger alert-dismissible fade in" role="alert">
    
        <strong><?php echo $errores; ?></strong>
        </div>
    <?php } ?>    
    <textarea name="erroresvv" id="erroresvv" style="display:none"><?php echo $errores; ?></textarea>
    <div class="alert alert-danger alert-dismissible fade in" role="alert" id="errorcontinua" style="display:none">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
        </button>
        <strong>Errores:</strong><br /><span id="errordetalle1"></span>
    </div>
    
<div id="contenedor_datos" style="display:<?php echo $dsp; ?>">
    <input type="hidden" name="occlietot" id="occlietot" value="<?php echo $td; ?>" />
    <div class="clearfix"></div>
    <div class="col-md-12" style="border: 1px solid #000000; display:none;"  id="primero">
    <table>
        <tr>
            <td>Cliente</td>
            <td>Documento</td>
            <td>Celular</td>
            <td></td>
        </tr>    
        
    </table>
    </div>
    <div class="clearfix"></div>
    
    <div class="col-md-12" style="border: 1px solid #000000;  <?php if ($error != 1) { ?>display:none; <?php } ?>" id="segundo">
        <h1><small>Cliente</small></h1>
        <div class="col-md-4 col-sm-4 form-group" >
            <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Cliente  </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
                <input type="checkbox" name="fisico" id="fisico" class="flat" value="1" onClick="verificartc(1);" /> F&iacute;sico &nbsp;&nbsp; 
                <input type="checkbox" name="juridico" id="juridico" class="flat" value="2" onClick="verificartc(2);"/> Jur&iacute;dico
            </div>
        </div>
        <div class="col-md-4 col-sm-4 form-group" >
            <label class="control-label col-md-3 col-sm-3 col-xs-12">Nombres </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="nombres" id="nombres" value="<?php if (isset($_POST['nombres'])) {
                echo $_POST['nombres'];
            } else {
                echo $nombres;
            } ?>" class="form-control">                    
            </div>
        </div>
        <div class="col-md-4 col-sm-4 form-group" >
            <label class="control-label col-md-3 col-sm-3 col-xs-12">Apellidos </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="apellidos" id="apellidos" value="<?php if (isset($_POST['apellidos'])) {
                echo $_POST['apellidos'];
            } else {
                echo $apellidos;
            }?>" class="form-control">                    
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-4 col-sm-4 form-group" >
            <label class="control-label col-md-3 col-sm-3 col-xs-9">Documento </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="docu" id="docu" value="<?php echo $dc?>" class="form-control"      <?php if ($td > 1) {
                echo "readonly='readonly'";
            } ?>>                    
            </div>
        </div>
        <div class="col-md-4 col-sm-4 form-group" >
            <label class="control-label col-md-3 col-sm-3 col-xs-12">Celular </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="celu" id="celu" value="<?php echo $cel?>" class="form-control">                    
            </div>
        </div>
        <div class="col-md-4 col-sm-4 form-group" >
            <label class="control-label col-md-3 col-sm-3 col-xs-12">Email </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="email" name="em" id="em" value="<?php if (isset($_POST['ema'])) {
                echo $_POST['ema'];
            } else {
                echo $email;
            }?>" class="form-control">                    
            </div>
        </div>
        
        
        <div class="clearfix"></div>
        <h1><small>Facturaci&oacute;n</small></h1>
        
        <div class="col-md-4 col-sm-4 form-group" >
            <label class="control-label col-md-3 col-sm-3 col-xs-12">R.U.C. </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="ruc" id="ruc" value="<?php if (isset($_POST['ruc'])) {
                echo $_POST['ruc'];
            } else {
                echo $rfactu->fields['ruc'];
            }?>" class="form-control">                    
            </div>
        </div>
        <div class="col-md-4 col-sm-4 form-group" >
            <label class="control-label col-md-3 col-sm-3 col-xs-12">Raz&oacute;n Social </label>
            <div class="col-md-9 col-sm-9 col-xs-12">
            <input type="text" name="rz" id="rz" value="<?php if (isset($_POST['rz'])) {
                echo $_POST['rz'];
            } else {
                echo $rfactu->fields['razon_social'];
            }?>" class="form-control">                    
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="col-md-12 col-sm-12 form-group" >
            <label class="control-label col-md-1 col-sm-1 col-xs-1">Obs </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
            <input type="text" name="obclie" id="obclie" value="<?php echo $obclie?>" class="form-control">                    
            </div>
        </div>
    </div>

    <?php if ($td == 0 && $error != 1) {?>
    <div class="col-md-6 col-sm-6 form-group">
        <button type="button" class="btn btn-success" onClick="registrarcliente();"><span class="fa fa-plus-square-o"></span> Registrar</button>
    </div>
    <?php } ?>
    <?php if ($error == 1) { ?>

        <div class="col-md-6 col-sm-6 form-group">
        <button type="button" class="btn btn-success" onClick="registrarcliente();"><span class="fa fa-plus-square-o"></span> Registrar</button>
        </div>
    <?php  } ?>
    <?php if ($td > 0 && $error != 1) {?>    
        <input type="hidden" name="clientepedidochar" id="clientepedidochar" value="<?php echo $nomape; ?>" />
        <input type="hidden" name="clientefactuchar" id="clientefactuchar" value="<?php echo $rz ?>" />
    <div class="col-md-6 col-sm-6 form-group">
        <input type="hidden" name="ocidclientepedido" id="ocidclientepedido" value="<?php echo $idclientepedido; ?>" />
        <input type="hidden" name="ocidclientefactura" id="ocidclientefactura" value="<?php echo $idcliente; ?>" />
        <button type="button" class="btn btn-info" onClick="actualizarcliente(<?php echo $idcliente?>);"><span class="fa fa-user"></span> Actualizar</button> &nbsp;
        <button type="button" class="btn btn-dark" onClick="seleccionar(<?php echo $idcliente?>,<?php echo $idclientepedido ?>);"><span class="fa fa-check-square-o"></span> Seleccionar</button>
    </div>

    <?php }?>
 </div>
 <?php if ($td > 1) { ?>
 <div id="selector_clientes" style="height:160px; overflow-y:scroll">
     <table class="table">
        <thead>
            <th>Id Unico</th>
            <th>Nombres / Apellidos</th>
            <th>Documento</th>
            <th>Ruc</th>
            
            <th></th>
        </thead>
        <tbody>
        <?php while (!$rfg1->EOF) {
            //si es por seleccion, deberiamos colocar el ultimo usado para facturacion por cualquier cosa
            //queda pendiente esa subconsulta para probar
            $compu = $rfg1->fields['idcliente']."|"."'".trim($rfg1->fields['razon_social'])."'";
            $compu2 = "'".$rfg1->fields['idcliente'].":".$rfg1->fields['razon_social']."'";
            ?>
            <tr>
                <td><?php echo $rfg1->fields['idcliente']; ?></td>
                <td><?php echo $rfg1->fields['razon_social']; ?></td>
                <td><?php echo $rfg1->fields['documento']; ?></td>
                <td><?php echo $rfg1->fields['ruc']; ?></td>
                
                <td><button type="button" class="btn btn-primary" onClick="clientelista(<?php echo $compu2; ?>);">SELECCIONAR</button></td>
            </tr>
        <?php $rfg1->MoveNext();
        } ?>
        </tbody>
     
     </table>
 </div>
 <?php } ?>
