 <?php
/*------------------------------------------*-------------------------
Genera el inventario de la empresa
Mod: Se le agrega bandera para mostrar u ocultar
boton de ver reporte, segun tabla de autorizaciones_inventario
la cual se establece desde gestion->parametros inventario MOD 381
Fecha: 02/02/2021
-------------------------------------------------------------------*/
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "80";

require_once("includes/rsusuario.php");

$insertado = 'N';

set_time_limit(0);

$habilitadet = "N";

// deposito
$iddeposito = intval($_GET['dep']);


//Post para generar cabecera inicial
$buscar = "Select idusu from autoriza_inventario where idusu=$idusu";
$rspermi = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//echo $buscar;
if (intval($rspermi->fields['idusu'] > 0)) {
    //tiene permiso
    $permitedeta = 'S';

} else {
    $permitedeta = 'N';


}
$consulta = "
select permite_verdetalle, obliga_concepto, idconcepto_inicial, idconcepto_final from preferencias_inventario limit 1
";
$rsprefinv = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idconcepto_inicial = intval($rsprefinv->fields['idconcepto_inicial']);
$idconcepto_final = intval($rsprefinv->fields['idconcepto_final']);
$obliga_concepto = trim($rsprefinv->fields['obliga_concepto']);
if ($rsprefinv->fields['permite_verdetalle'] == 'S') {
    $permitedeta = 'S';
}

//echo $permite;exit;


if (isset($_POST['comienzo']) && ($_POST['comienzo'] > 0)) {

    $valido = "S";
    $errores = "";

    //recibe parametros
    $deposito = antisqlinyeccion($_POST['deposito'], 'int');
    $fecha = antisqlinyeccion($_POST['fecha'], 'date');
    $idconcepto_inicial = antisqlinyeccion($_POST['idconcepto_inicial'], 'int');
    $idconcepto_final = antisqlinyeccion($_POST['idconcepto_final'], 'int');
    $fecha_concepto_inicial = antisqlinyeccion($_POST['fecha_concepto_inicial'], 'date');
    $fecha_concepto_final = antisqlinyeccion($_POST['fecha_concepto_final'], 'date');


    if (trim($_POST['fecha']) == '') {
        $valido = "N";
        $errores .= "- No se indico la fecha.<br />";
    }
    if (intval($_POST['deposito']) == 0) {
        $valido = "N";
        $errores .= "- No se indico ningun deposito.<br />";
    }

    if ($obliga_concepto == 'S') {
        if (intval($_POST['idconcepto_inicial']) == 0) {
            $errores .= "- Debes indicar el concepto inicial cuando el parametro esta habilitado como obligatorio.<br />";
            $valido = "N";
        }
        if (intval($_POST['idconcepto_final']) == 0) {
            $errores .= "- Debes indicar el concepto final cuando el parametro esta habilitado como obligatorio.<br />";
            $valido = "N";
        }
        if (trim($_POST['fecha_concepto_inicial']) == '') {
            $errores .= "- Debes indicar la fecha concepto inicial cuando el parametro esta habilitado como obligatorio.<br />";
            $valido = "N";
        }
        if (trim($_POST['fecha_concepto_final']) == '') {
            $errores .= "- Debes indicar la fecha concepto final cuando el parametro esta habilitado como obligatorio.<br />";
            $valido = "N";
        }

    }


    // busca que no haya un inventario abierto para ese deposito
    $buscar = "select idinventario from inventario where idempresa=$idempresa and iddeposito = $deposito and estado = 1";
    $rsab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if (intval($rsab->fields['idinventario']) > 0) {
        $valido = "N";
        $errores .= "- Ya existe otro inventario abierto para el deposito seleccionado.<br />";
    }
    // busca que no exista un inventario cerrado con fecha posterior
    $buscar = "
    select idinventario, fecha_inicio
    from inventario 
    where 
    idempresa=$idempresa 
    and iddeposito = $deposito 
    and estado = 3
    and fecha_inicio > $fecha
    order by fecha_inicio desc
    limit 1
    ";
    $rsabcer = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if (intval($rsabcer->fields['idinventario']) > 0) {
        $valido = "N";
        $errores .= "- Ya existe un inventario cargado con fecha posterior al que intentas cargar. fecha:".date("d/m/Y", strtotime($rsabcer->fields['fecha_inicio']))."<br />";

    }

    // busca que no exista un inventario cerrado con fecha de hoy para el mismo deposito
    $buscar = "
    select idinventario, fecha_inicio
    from inventario 
    where 
    idempresa=$idempresa 
    and iddeposito = $deposito 
    and fecha_inicio = $fecha
    limit 1
    ";
    $rsabcer = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if (intval($rsabcer->fields['idinventario']) > 0) {
        $valido = "N";
        $errores .= "- Ya existe un inventario cargado con la misma fecha para el mismo deposito. fecha:".date("d/m/Y", strtotime($rsabcer->fields['fecha_inicio']))."<br />";
    }
    // busca que no existan transferencias abiertas para este deposito tanto en origen y destino
    $consulta = "
    SELECT gest_transferencias.idtanda
    FROM gest_transferencias 
    where 
    estado = 1
    and fecha_transferencia > $fecha
    and (gest_transferencias.origen = $deposito or gest_transferencias.destino = $deposito)
    and gest_transferencias.idempresa = $idempresa
    ";
    $rstran = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rstran->fields['idtanda'] > 0) {
        $valido = "N";
        $errores .= "- Existe una transferencia abierta en el deposito de origen o destino, cierrela antes de cargar el inventario.<br />";
    }

    // busca que no existan transferencias posteriores al inventario tanto en origen y destino
    $consulta = "
    SELECT gest_depositos_mov.idtanda 
    FROM gest_depositos_mov 
    inner join gest_transferencias on gest_transferencias.idtanda = gest_depositos_mov.idtanda
    where 
    gest_transferencias.fecha_transferencia > $fecha
    and (gest_transferencias.origen = $deposito or gest_transferencias.destino = $deposito)
    and gest_transferencias.idempresa = $idempresa
    and gest_depositos_mov.idempresa = $idempresa
    ORDER BY gest_depositos_mov.fechahora DESC
    ";
    $rstran2 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    if ($rstran2->fields['idtanda'] > 0) {
        $valido = "N";
        $errores .= "- Existe una transferencia en el deposito de origen o destino con fecha posterior a este inventario.<br />";
    }

    // inventario en el futuro
    if (strtotime(date("Y-m-d", strtotime($_POST['fecha']))) > strtotime(date("Y-m-d"))) {
        $valido = "N";
        $errores .= "- No puedes iniciar un inventario con una fecha en el futuro.<br />";

    }

    // busca a que sucursal pertenece el deposito
    $buscar = "
    Select idsucursal
    from gest_depositos 
    where 
    gest_depositos.idempresa=$idempresa 
    and gest_depositos.iddeposito = $deposito
    order by descripcion ASC ";
    //echo $buscar;
    $rsde = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $sucursal = antisqlinyeccion($rsde->fields['idsucursal'], 'int');
    if (intval($sucursal) == 0) {
        $valido = "N";
        $errores .= "- No se pudo obtener la sucursal del deposito indicado.<br />";
    }

    // busca el ultimo inventario que se hizo del mes indicado
    $ano_indicado = date("Y", strtotime($_POST['fecha']));
    $mes_indicado = date("m", strtotime($_POST['fecha']));
    $consulta = "
    select idconcepto_inicial, idconcepto_final
    from inventario 
    where
    estado <> 6
    and iddeposito = $deposito
    and YEAR(fecha_inicio) = $ano_indicado
    and MONTH(fecha_inicio) = $mes_indicado
    order by idinventario desc 
    limit 1
    ";
    //echo $consulta;
    $rsregcon = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsregcon->fields['idconcepto_inicial']) > 0) {
        if ($_POST['idconcepto_inicial'] == $idconcepto_inicial) {
            if ($rsregcon->fields['idconcepto_inicial'] == $idconcepto_inicial) {
                //echo $idconcepto_inicial;exit;
                $valido = "N";
                $errores .= "- Ya existe un concepto de inventario inicial cargado para el mismo mes/a&ntilde;o de este deposito.<br />";
            }
        }
        if ($_POST['idconcepto_final'] == $idconcepto_final) {
            if ($rsregcon->fields['idconcepto_final'] == $idconcepto_final) {
                //echo $rsregcon->fields['idconcepto'];exit;
                $valido = "N";
                $errores .= "- Ya existe un concepto de inventario final cargado para el mismo mes/a&ntilde;o de este deposito.<br />";
            }
        }
    }

    if ($_POST['fecha_concepto_inicial'] != '' or $_POST['fecha_concepto_final'] != '') {
        // el inventario final debe ser del mes anterior al inicial
        if (strtotime($_POST['fecha_concepto_inicial']) <= strtotime($_POST['fecha_concepto_final'])) {
            $valido = "N";
            $errores .= "- El inventario final debe ser del mes anterior al inicial.<br />";
        }

    }

    // si todo es valido
    if ($valido == "S") {

        // genera proximo id
        $buscar = "select max(idinventario) as mayor from inventario";
        $rsmay = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $mayor = intval($rsmay->fields['mayor']) + 1;


        $insertar = "
        Insert into inventario 
        (fecha_inicio,inicio_registrado_el,iniciado_por,estado,idinventario,idsucursal,idempresa,iddeposito,
        idconcepto_inicial,idconcepto_final,fecha_concepto_inicial,fecha_concepto_final) 
        values 
        ($fecha,'$ahora',$idusu,1,$mayor,$sucursal,$idempresa,$deposito,
        $idconcepto_inicial,$idconcepto_final,$fecha_concepto_inicial,$fecha_concepto_final)
        ";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

        header("location: gest_controlar_stock.php?dep=$deposito");
        exit;

    }


}



//busqueda del Producto
/*if (isset($_POST['inve']) && ($_POST['inve']) > 0){

    $idp=antisqlinyeccion($_POST['codprod'],'text');
    $codigo=antisqlinyeccion($_POST['producto'],'text');
    if (($idp !='NULL') or ($codigo!='NULL')){
        if ($idp !='NULL'){
            $add=antisqlinyeccion($_POST['codprod'],'text');
            $buscar="Select * from insumos_lista where descripcion=$add";
        } else {
            if (($codigo !='NULL')&&($codigo !='0')){
                $add=antisqlinyeccion($_POST['producto'],'text');
                $buscar="Select * from insumos_lista where idinsumo=$add";
            }
        }


        $rspp=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
        $enco=$rspp->RecordCount();
        //echo $buscar;
        $plocal=trim($rspp->fields['idprod']);
        $b=1;
    }



}*/
//agregar a la tabla
if (isset($_POST['ocpro']) && ($_POST['ocpro']) > 0) {
    $lote = antisqlinyeccion($_POST['lote'], 'text');
    $vto = antisqlinyeccion($_POST['vto'], 'date');
    $ubica = $iddeposito;
    $idp = antisqlinyeccion($_POST['ocpro'], 'text');
    $inve = intval($_POST['ocinv']);
    $cantidad = floatval($_POST['cantidad']);

    if (intval($ubica) == 0) {
        echo "No indico el deposito";
        exit;
    }


    $buscar = "select descripcion from insumos_lista where idinsumo=$idp";
    $rsptm = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    $des = antisqlinyeccion($rsptm->fields['descripcion'], 'text');

    //vemos si ya no existe en la tabla de inventario, si existe se omite
    $buscar = "Select * from inventario_detalles where idinventario=$inve and idinsumo=$idp and ubicacion=$ubica";
    $rscoin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $unicoid = intval($rscoin->fields['unicose']);
    if ($unicoid == 0) {
        //no existe y se permite registrar

        $insertar = "
        insert into
        inventario_detalles
        (idinventario,idinsumo,descripcion,cantidad_contada,idusu,ubicacion,lote,vto)
        values
        ($inve,$idp,$des,$cantidad,$idusu,$ubica,$lote,$vto)";
        $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

    } else {

        $update = "
        update
        inventario_detalles
        set
            descripcion=$des,
            cantidad_contada=cantidad_contada+$cantidad,
            idusu=$idusu,
            lote=$lote,
            vto=$vto
        where
            idinventario = $inve
            and idinsumo = $idp
            and ubicacion=$ubica
        ";
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        /*$errorinve="<span class='resaltarojomini'>
        El producto indicado ya se encuentra registrado, con mismo lote, vto.<br />
        Si cometi&oacute; un error en la carga, verifique su listado y elimine el producto para ser ingresado nuevamente."    ;*/

    }

    header("location: gest_controlar_stock.php?dep=$iddeposito");
    exit;

}

$buscar = "
Select idinsumo,descripcion 
from insumos_lista 
where 
idempresa = $idempresa 
order by descripcion asc";
$rsprod = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$total = $rsprod->RecordCount();

$buscar = "Select inventario.idinventario,inventario.fecha_inicio,inventario.estado,usuarios.usuario,
sucursales.nombre as sucursal_nombre, gest_depositos.descripcion as deposito_nombre
from inventario 
inner join usuarios on usuarios.idusu=inventario.iniciado_por
inner join sucursales on inventario.idsucursal = sucursales.idsucu
inner join gest_depositos on gest_depositos.iddeposito = inventario.iddeposito
where 
inventario.estado=1
and inventario.idempresa = $idempresa
and gest_depositos.idempresa = $idempresa
and sucursales.idempresa = $idempresa
and usuarios.idempresa = $idempresa
and inventario.iddeposito = $iddeposito
";
$rsinv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

$estado = intval($rsinv->fields['estado']);
$idinventario = intval($rsinv->fields['idinventario']);

$buscar = "Select * from gest_depositos where idempresa = $idempresa order by iddeposito asc";
$rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

//Si existen productos en el inventario, mostramos una ventanita para que se edite en otra pagina
$buscar = "
Select count(*) as total 
from inventario_detalles 
where 
idinventario=$idinventario";
//echo $buscar;
$rsconteo = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$totaldeta = intval($rsconteo->fields['total']);





?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title><?php require("includes/title.php"); ?></title>
<link rel="stylesheet" type="text/css" href="ani/css/demo.css" />
<link rel="stylesheet" type="text/css" href="ani/css/style2.css" />
<link rel="stylesheet" type="text/css" href="css/magnific-popup.css" />
<?php require("includes/head.php"); ?>
<script>
function seleccionar(){
    var idpsele=(document.getElementById('producto').value);
    var cod=document.getElementById('codprod').value;
    var errores='';
    if ((cod=='') && (idpsele=='0')){
        errores='Debe seleccionar producto, o bien, ingresar codigo a buscar';
        alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');
    } else {
        var parametros="idpsel="+idpsele+'&cp='+cod;
        enlace='gest_ajuste_ppal.php';
        OpenPage(enlace,parametros,'POST','principal','pred');
        setTimeout(function(){ abrecos(idpsele,cod); }, 500);
    }
    
}
function abrecos(idpsele,cod){
    
        var parametros="idpsel="+idpsele+'&cp='+cod+'&clase=1';
        enlace='gest_ajuste_secu.php';
        OpenPage(enlace,parametros,'POST','costos','pred');

    
    
}
//Agregar productos al stock
function agregar(posicion,serial){
    var idp=document.getElementById('ocpp').value;
    var posi=parseFloat(serial);
    //id unico a dar de alta
    var can=(document.getElementById('cantidad_'+posicion).value)
    can=parseFloat(can);    
    if ((can > 0) && (posi > 0)){
        var parametros='unico='+posi+'&cantidad='+can+'&idp='+idp+'&tipo=1&clase=2';
        enlace='gest_ajuste_secu.php';
        //damos de baja en costos y depositos
        OpenPage(enlace,parametros,'POST','costos','pred');
        //damos de baja en principal
        
        
    } else {
        
            alertar('ATENCION: Algo salio mal.','Debe indicar cantidad a ser agregada','error','Lo entiendo!');
    }
    
}
function chau(posicion,serial){
    var idp=document.getElementById('ocpp').value;
    var posi=parseFloat(serial);
    //id unico a dar de baja
    var can=(document.getElementById('cantidad_'+posicion).value)
    can=parseFloat(can);    
    if ((can > 0) && (posi > 0)){
        var parametros='unico='+posi+'&cantidad='+can+'&idp='+idp+'&tipo=1';
        enlace='gest_ajuste_secu.php';
        //damos de baja en costos y depositos
        OpenPage(enlace,parametros,'POST','costos','pred');
        //damos de baja en principal
        
        
    } else {
        
            alertar('ATENCION: Algo salio mal.','Debe indicar cantidad a eliminar','error','Lo entiendo!');
    }
}
function alertar(titulo,error,tipo,boton){
    swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
    }
function arrancar(){
    var fecha=document.getElementById('fecha').value;
    if (fecha!=''){
        document.getElementById('g1').submit();
    } else {
        alertar('ATENCION: Algo salio mal.','Debe indicar fecha inicial de inventario','error','Lo entiendo!');
        
    }
    
    
}
function busca_insumo(valor){
    var n = valor.length;
    if(n > 2){
       var parametros = {
              "s" : valor
       };
       $.ajax({
                data:  parametros,
                url:   'busca_insumo_stock.php',
                type:  'post',
                beforeSend: function () {
                        $("#insumo_box").html("Cargando...");
                },
                success:  function (response) {
                        $("#insumo_box").html(response);
                }
        });    
    }
}
function busca_cprod(e){
    var idinsumo = $("#cod_prod").val();
    tecla = (document.all) ? e.keyCode : e.which;
    // tecla enter
      if (tecla==13){
    /*    var parametros = {
            "idinsumo"      : idinsumo,
            "idinventario"  : <?php echo $idinventario; ?>,
            "iddeposito" : <?php echo $iddeposito; ?>
        };
        $.ajax({
            data:  parametros,
            url:   'gest_controlar_stock_selecciona.php',
            type:  'post',
            beforeSend: function () {
                    $("#insumo_box").html("Cargando...");
            },
            success:  function (response) {
                    $("#insumo_box").html(response);
                    $("#cantidad").focus();
            }
        });    */
        seleccionar_insumo(idinsumo);
    }
}
function busca_cbar(e){
    var codbar = $("#codbar").val();
    tecla = (document.all) ? e.keyCode : e.which;
    // tecla enter
      if (tecla==13){
        var parametros = {
            "codbar" : codbar,
            
        };
        $.ajax({
            data:  parametros,
            url:   'busca_insumo_stock.php',
            type:  'post',
            beforeSend: function () {
                $("#insumo_box").html("Cargando...");
            },
            success:  function (response) {
                if(IsJsonString(response)){
                    var obj = jQuery.parseJSON(response);
                    if(obj.valido == 'S'){
                        seleccionar_insumo(obj.idinsumo);
                    }else{
                        alert('Articulo inexistente');    
                        $("#insumo_box").html(response);
                    }
                }else{
                    $("#insumo_box").html(response);    
                }
            }
        });    
    }
}
function seleccionar_insumo(valor){
    var parametros = {
      "idinsumo"     : valor,
      "idinventario" : <?php echo $idinventario; ?>,
      "iddeposito" : <?php echo $iddeposito; ?>
    };
    $.ajax({
        data:  parametros,
        url:   'gest_controlar_stock_selecciona.php',
        type:  'post',
        beforeSend: function () {
            $("#insumo_box").html("Cargando...");
        },
        success:  function (response) {
            $("#insumo_box").html(response);
            $("#cantidad").focus();
        }
    });
}
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
</script>

<script src="js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="css/sweetalert.css">

</head>
<body bgcolor="#FFFFFF">
    <?php require("includes/cabeza.php"); ?>    
    <div class="clear"></div>
    <div class="cuerpo">
         <div align="center">
             <?php require_once("includes/menuarriba.php");?>
        </div>
        <div class="clear"></div><!-- clear1 -->
        <div class="colcompleto" id="contenedor">
        
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert" style="background-color:#FFC; border:2px solid #F00; width:500px; margin:0px auto;">
<br />
<strong>Errores:</strong><br /><?php echo $errores; ?>
<br />
</div><br /><br />
<?php } ?>
        
        <?php if ($estado > 0) {?>
             <div class="divstd">
                <a href="gest_controlar_stock.php"><img src="img/homeblue.png" width="64" height="64" title="Regresar"/>
                
            </a></div>
            <?php } ?>
          <?php /*?><p>- al abrir inventario seleciona la sucursal, eso va en un modulo aparte de administrador<br />
           - permitir al administrador cancelar o editar la fecha de un inventario abierto<br />
          - Lote y vencimiento traer por defecto de compras, si en tabla parametro_empresa esta marcado como S</p>
           <p>- validar que no duplique el mismo insumo en el mismo inventario para que no de el error de duplicate key en bd</p>
           <p>- icono para imprimir hojas de inventario por deposito</p><?php */ ?>

          <div class="resumenmini" style="width:600px;">
          <br />
                <strong>Inventario Global</strong>
                <br />
                <div style="font-weight:normal; width:400px; margin:0px auto; text-align:left;">
                Importante:<br />
                1) Se debe hacer con local cerrado, tanto el conteo fisico hasta el registro en el sistema.<br />
                2) Todos los productos que no registres aqui el sistema pondra su stock en 0 (cero).<br />
                </div>
              <br />
              <br />
              <?php if ($estado > 0) {?>
              <?php if ($totaldeta == 0) {?>
              <a href="gest_controlar_stock_del.php?inv=<?php echo $idinventario ?>">[Borrar]</a><br /><br />
              <?php } ?>
              <a href="inventario_importar.php?id=<?php echo $idinventario ?>">[Importar]</a><br /><br />
              <table width="400">
                  <tr>
                    <td width="98" height="21" align="center" bgcolor="#DFDFDF"><strong>Fecha Inicio</strong></td>
                    <td width="107" align="center" bgcolor="#DFDFDF"><strong>Iniciado por</strong></td>
                    <?php if ($totaldeta > 0) {?><td width="107" align="center" bgcolor="#DFDFDF"><strong>Inventariado</strong></td><?php } ?>
                </tr>
                  <tr>
                    <td height="32" align="center"><?php echo date("d/m/Y H:i:s", strtotime($rsinv->fields['fecha_inicio'])); ?></td>
                    <td align="center"><?php echo $rsinv->fields['usuario']?></td>
                    <?php if ($totaldeta > 0) {?><td align="center"> <?php echo $totaldeta ?></td><?php } ?>
                   </tr>
                  <tr>
                    <td height="21" align="center" bgcolor="#DFDFDF"><strong>Sucursal</strong></td>
                    <td align="center" bgcolor="#DFDFDF"><strong>Deposito</strong></td>
                    <?php
                  if ($totaldeta > 0) {?>
                  <td align="center" bgcolor="#DFDFDF">
                  
                  <strong>Ver Detalle</strong>
                 
                  </td>
                  <?php } ?>
                   </tr>
                  <tr>
                    <td height="32" align="center"><?php echo $rsinv->fields['sucursal_nombre']?></td>
                    <td align="center"><?php echo $rsinv->fields['deposito_nombre']?></td>
                    <?php if ($totaldeta > 0) {?><td align="center">
                    <?php if ($permitedeta == 'S') { ?>
                    <a href="gest_control_lista.php?inv=<?php echo $idinventario?>"  >
                        <img src="img/1438110755_vector_65_13.png" width="32" height="32" /> 
                    </a>
                     <?php } else {?>
                     Solicitar autorizacion
                      <?php } ?>
                    </td><?php } ?>
                   </tr>
              </table>
              <?php } else {

                  $buscar = "Select iddeposito,descripcion,tiposala,color,direccion,usuario
from gest_depositos 
inner join usuarios on usuarios.idusu=gest_depositos.idencargado 
where 
usuarios.idempresa=$idempresa 
and gest_depositos.idempresa=$idempresa 
order by descripcion ASC ";
                  $rsd = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
                  ?>
              <form id="g1" name="g1" method="post" action="gest_controlar_stock.php" >
              <table width="400" border="1">
                <tbody>
                  <tr>
                    <td><strong>Fecha Inventario:</strong></td>
                    <td align="left"><input type="date" name="fecha" id="fecha" value="<?php if ($_POST['fecha'] == '') {
                        echo date("Y-m-d");
                    } else {
                        echo htmlentities($_POST['fecha']);
                    } ?>" /></td>
                  </tr>
                  <tr>
                    <td><strong>Deposito:</strong></td>
                    <td align="left"><?php // consulta
$consulta = "
SELECT iddeposito, descripcion
FROM gest_depositos
where
estado = 1
order by descripcion asc
 ";

                  // valor seleccionado
                  if (isset($_POST['deposito'])) {
                      $value_selected = htmlentities($_POST['deposito']);
                  } else {
                      //$value_selected=htmlentities($rs->fields['idsucu']);
                  }

                  // parametros
                  $parametros_array = [
                      'nombre_campo' => 'deposito',
                      'id_campo' => 'deposito',

                      'nombre_campo_bd' => 'descripcion',
                      'id_campo_bd' => 'iddeposito',

                      'value_selected' => $value_selected,

                      'pricampo_name' => 'Seleccionar...',
                      'pricampo_value' => '',
                      'style_input' => 'class="form-control"',
                      'acciones' => ' required="required" ',
                      'autosel_1registro' => 'S'

                  ];

                  // construye campo
                  echo campo_select($consulta, $parametros_array);?></td>
                  </tr>
<?php if ($obliga_concepto == "S") {?>
                  <tr>
                    <td><strong>Concepto Final:</strong></td>
                    <td align="left"><?php

                  // consulta
                  $consulta = "
SELECT idconcepto, descripcion
FROM cn_conceptos
where
estado = 1
and idconcepto = $idconcepto_final
order by descripcion desc
 ";

    // valor seleccionado
    if (isset($_POST['idconcepto_final'])) {
        $value_selected = htmlentities($_POST['idconcepto_final']);
    } else {
        $value_selected = htmlentities($idconcepto_final);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idconcepto_final',
        'id_campo' => 'idconcepto_final',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idconcepto',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);?></td>
                  </tr>
                  <tr>
                    <td><strong>Fecha Concepto Final<br />(Mes Anterior)</strong></td>
                    <td align="left"><input type="date" name="fecha_concepto_final" value="<?php if ($_POST['fecha_concepto_final'] != '') {
                        echo date("Y-m-d", strtotime($_POST['fecha_concepto_final']));
                    } ?>"  /></td>
                  </tr>
                  <tr>
                    <td><strong>Concepto Inicial: </strong></td>
                    <td align="left"><?php

// consulta
$consulta = "
SELECT idconcepto, descripcion
FROM cn_conceptos
where
estado = 1
and idconcepto = $idconcepto_inicial
order by descripcion desc
 ";

    // valor seleccionado
    if (isset($_POST['idconcepto_inicial'])) {
        $value_selected = htmlentities($_POST['idconcepto_inicial']);
    } else {
        $value_selected = htmlentities($idconcepto_inicial);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'idconcepto_inicial',
        'id_campo' => 'idconcepto_inicial',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idconcepto',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => ' required="required" ',
        'autosel_1registro' => 'S'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);?></td>
                  </tr>
                  <tr>
                    <td><strong>Fecha Concepto Incial<br />(Mes Nuevo)</strong></td>
                    <td align="left"><input type="date" name="fecha_concepto_inicial" value="<?php if ($_POST['fecha_concepto_inicial'] != '') {
                        echo date("Y-m-d", strtotime($_POST['fecha_concepto_inicial']));
                    } ?>"  /></td>
                  </tr>

<?php } ?>
                </tbody>
              </table><br />
              <br />
               <input type="hidden" name="comienzo"value="<?php echo rand()?>"  />
                  <input type="button" value="Iniciar Inventario" onclick="arrancar();" /><br /><br />
               </form>
              <?php } ?>
            </div><br />

        <br  />
        
        <hr />
        <?php if ($estado > 0) {?>
        <br />
<?php
$consulta = "
select *, insumos_lista.descripcion as descripcion,
 (select barcode from productos where idprod_serial = insumos_lista.idproducto) as codbar
from inventario_detalles 
inner join insumos_lista on insumos_lista.idinsumo = inventario_detalles.idinsumo
where
idinventario = $idinventario
order by unicose desc
limit 1
";
            $rsul = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($rsul->fields['idinsumo'] > 0) {
                ?>
<div class="resumenmini">
<span style="font-size:16px;">
Ultimo Articulo Inventariado: <?php echo $rsul->fields['descripcion']; ?> [<?php echo $rsul->fields['idinsumo']; ?>] | <?php echo $rsul->fields['codbar']; ?>
</span>
</div>
<br /><br />
<?php  } ?>
        <div align="center">
            <span class="resaltaazul">B&uacute;squeda de Insumos</span><br />
            <span class="resaltarojomini"><?php echo $errorinve?></span>
        </div>
        <div align="center"><br />
<form id="cso" name="cso" method="post" action="gest_controlar_stock.php?dep=<?php echo $iddeposito; ?>#acc">
            <table width="700" class="tablaconborde">
            <tr>
            <td align="center" bgcolor="#DDDDDD"><strong>Codigo Barras</strong></td>
              <td align="center" bgcolor="#DDDDDD"><strong>Codigo Articulo</strong></td>
                <td height="26" align="center" bgcolor="#DDDDDD"><strong>Nombre Articulo</strong></td>
          </tr>
            <tr>
              
                <td align="center"><input type="text" name="codbar" id="codbar" onkeyup="busca_cbar(event);" autofocus="autofocus"  /></td>
                <td align="center"><input type="text" name="cod_prod" id="cod_prod" onkeyup="busca_cprod(event);"  /></td>
                <td align="center"><input type="text" name="desc_prod" id="desc_prod" onkeyup="busca_insumo(this.value);"  /></td>
            </tr>
            <tr>

        </table><input type="hidden" name="inve" id="inve" value="<?php echo $idinventario?>" />
</form><br />
        <div id="insumo_box"></div>
        </div><br />
        
        
        <hr /><?php if ($b == 1) {?>
        <br /><br />
        <?php if ($enco == 1) {?>

        <?php }?>
        <?php } ?>
        <?php } else {

            // buscar si hay inventarios en curso
            $buscar = "select * from inventario where idempresa=$idempresa and estado = 1";
            $rsab = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            if (intval($rsab->fields['idinventario']) == 0) {
                ?>
            <div align="center">
                <span class="resaltarojomini">No existen inventarios en curso actualmente.</span>
            </div>
<?php } else {

    ?>
<h1 align="center"><strong>Seleccionar inventario en Curso:</strong></h1>
<table width="900" border="1">
  <tbody>
    <tr>
      <td align="center" bgcolor="#F8FFCC"><strong>Fecha Inicio</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Deposito</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Sucursal</strong></td>
      <td align="center" bgcolor="#F8FFCC"><strong>Iniciado Por</strong></td>
      <td align="center" bgcolor="#F8FFCC"><input type="button" name="Seleccionar2" id="Seleccionar2" value="Seleccionar" /></td>
      </tr>
<?php  while (!$rsab->EOF) {

    $iddeposito = $rsab->fields['iddeposito'];
    $buscar = "Select inventario.idinventario,inventario.fecha_inicio,inventario.estado,usuarios.usuario,inventario.iddeposito,
sucursales.nombre as sucursal_nombre, gest_depositos.descripcion as deposito_nombre
from inventario 
inner join usuarios on usuarios.idusu=inventario.iniciado_por
inner join sucursales on inventario.idsucursal = sucursales.idsucu
inner join gest_depositos on gest_depositos.iddeposito = inventario.iddeposito
where 
inventario.estado=1
and inventario.idempresa = $idempresa
and gest_depositos.idempresa = $idempresa
and sucursales.idempresa = $idempresa
and usuarios.idempresa = $idempresa
and inventario.iddeposito = $iddeposito
";
    $rsinv = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));

    ?>
    <tr>
      <td align="center"><?php echo date("d/m/Y", strtotime($rsinv->fields['fecha_inicio'])); ?></td>
      <td align="center"><?php echo $rsinv->fields['deposito_nombre']; ?></td>
      <td align="center"><?php echo $rsinv->fields['sucursal_nombre']; ?></td>
      <td align="center"><?php echo $rsinv->fields['usuario']; ?></td>
      <td width="50" align="center"><input type="button" name="Seleccionar" id="Seleccionar" value="Seleccionar" onmouseup="document.location.href='gest_controlar_stock.php?dep=<?php echo $rsinv->fields['iddeposito']; ?>'" /></td>
      </tr>
<?php  $rsab->MoveNext();
} ?>
  </tbody>
</table>
<p align="center">&nbsp;</p>
<p align="center">&nbsp;</p>
<br />

<?php } ?>
        <?php } ?>
        </div> <!-- contenedor -->
          


           <div class="clear"></div><!-- clear1 -->
    </div> <!-- cuerpo -->
    <div class="clear"></div><!-- clear2 -->
    <?php require("includes/pie.php"); ?>
</body>
</html>
