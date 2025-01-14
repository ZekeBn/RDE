 <?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");

$id = intval($_GET['id']);
if (intval($id) == 0) {
    header("location: listado_productos.php");
    exit;
}
$idreceta = $id;

$buscar = "Select * from productos where borrado = 'N' and idprod_serial = $id and combinado = 'N'  order by descripcion asc";
$rsp = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
if (intval($rsp->fields['idprod_serial']) == 0) {
    echo "Producto Inexistente!";
    exit;
}


//print_r($_POST);
// inserta en tabla temporal
/*
if(isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1' && $_POST['reemplazar'] != 'S'){
    // recibe parametros
    $idingrediente=antisqlinyeccion($_POST['ingredientes'],'int');
    $cantidad=antisqlinyeccion($_POST['cantidad'],'int');
    // valida que no exista el ingrediente
    $consulta="
    select * from tmping
    where
    idprod = $id
    and ingrediente = $idingrediente
    ";
    $rstmpex=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    // validaciones
    $valido="S";
    $errores="";
    if($rstmpex->fields['ingrediente'] > 0){
        $valido="N";
        $errores.="- El ingrediente que intentas registrar ya existe, mejor editalo.";
    }

    // si todo es valido inserta
    if($valido=="S"){
        $consulta="
        INSERT INTO tmping
        (idprod,ingrediente,cantidad)
        VALUES
        ($id,$idingrediente,$cantidad)
        ";
        $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    }


}
*/
// transfiere a receta definitiva
if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {


    // buscar si ya existe receta para ese producto
    $consulta = "
        SELECT * 
        FROM recetas
        where
        idproducto = $id
        ";
    $rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idreceta = $rsrec->fields['idreceta'];
    // si no existe crea
    if (intval($rsrec->fields['idproducto']) == 0) {
        $ahora = date("Y-m-d H:i:s");
        $consulta = "
            INSERT INTO recetas
            (nombre, estado, creado_por, fecha_creacion, ultimo_cambio, ultimo_cambio_por, idproducto, idempresa) 
            VALUES
            (NULL,1,$idusu,'$ahora','$ahora',$idusu,$id,$idempresa)
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }
    // si existe
    if (intval($rsrec->fields['idproducto']) > 0) {
        $consulta = "
            update recetas set
            ultimo_cambio = '$ahora',
            ultimo_cambio_por = $idusu
            where
            idproducto = $id
            and idempresa = $idempresa
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    }

    // busca el id de la receta
    $consulta = "
        SELECT * 
        FROM recetas
        where
        idproducto = $id
        and idempresa = $idempresa
        ";
    $rsrec = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idreceta = $rsrec->fields['idreceta'];

    // recibe parametros
    $idinsumo = antisqlinyeccion($_POST['ingredientes'], 'int');
    $cantidad = antisqlinyeccion($_POST['cantidad'], 'float');
    $sacar = antisqlinyeccion($_POST['sacar'], 'text');
    $cantidad_neta = antisqlinyeccion($_POST['cantidad_neta'], "float");
    $rendimiento_porc = antisqlinyeccion($_POST['rendimiento_porc'], "float");
    $comentario_ing = antisqlinyeccion($_POST['comentario_ing'], "text");
    //$alias=antisqlinyeccion($_POST['alias'],'text');
    //$alias=antisqlinyeccion($rsp->fields['descripcion'],'text');

    //Consulta Original
    //$consulta="
    //select idingrediente from ingredientes where idinsumo = $idinsumo and estado = 1  limit 1
    //";

    //Consulta Gabriel
    $consulta = "
        select idingrediente from ingredientes where idingrediente = $idinsumo and estado = 1  limit 1
        ";
    $rsing = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idingrediente = $rsing->fields['idingrediente'];
    if (intval($rsing->fields['idingrediente']) == 0) {
        $valido = "N";
        $errores .= "- El ingrediente que intentas registrar no existe o fue borrado.<br />";
    }

    // valida que no exista el ingrediente
    $consulta = "
        select * from recetas_detalles
        where
        idprod = $id
        and ingrediente = $idingrediente
        ";

    $rstmpex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // validaciones
    $valido = "S";
    $errores = "";
    if ($rstmpex->fields['ingrediente'] > 0) {
        $valido = "N";
        $errores .= "- El ingrediente que intentas registrar ya existe, borralo antes de volver a registrar.<br />";
    }
    if (floatval($_POST['cantidad']) <= 0) {
        $valido = "N";
        $errores .= " - El campo cantidad bruta no puede ser cero o negativo.<br />";
    }
    if (floatval($_POST['cantidad_neta']) <= 0) {
        $valido = "N";
        $errores .= " - El campo cantidad neta no puede ser cero o negativo.<br />";
    }
    if (floatval($_POST['rendimiento_porc']) <= 0) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser cero o negativo.<br />";
    }

    // conversiones por seguridad
    $rendimiento_porc = (100 * floatval($_POST['cantidad_neta'])) / floatval($_POST['cantidad']);
    if (floatval($rendimiento_porc) <= 0) {
        $valido = "N";
        $errores .= " - El campo rendimiento no puede ser cero o negativo.<br />";
    }




    // si todo es valido inserta
    if ($valido == "S") {

        // inserta los ingredientes nuevos en receta detalle
        $consulta = "
            INSERT INTO recetas_detalles
            (idreceta, idprod, ingrediente, cantidad, sacar,alias,idempresa,  cantidad_neta, rendimiento_porc, comentario_ing)
            values
            ($idreceta,$id,$idingrediente,$cantidad,$sacar,'$alias',$idempresa, $cantidad_neta, $rendimiento_porc, $comentario_ing)
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // actualizar alias
        $consulta = "
            update recetas_detalles 
            set 
            alias = (
                    select insumos_lista.descripcion 
                    from insumos_lista 
                    inner join ingredientes on ingredientes.idinsumo = insumos_lista.idinsumo 
                    where 
                    ingredientes.idingrediente = recetas_detalles.ingrediente
                    ) 
            where 
                (
                select 
                insumos_lista.descripcion 
                from insumos_lista 
                inner join ingredientes on ingredientes.idinsumo = insumos_lista.idinsumo 
                where 
                ingredientes.idingrediente = recetas_detalles.ingrediente
                ) is not null
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        $consulta = "
            delete from recetas_detalles where cantidad <= 0
            ";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        header("location: gest_recetas.php?id=$id");
        exit;
    }


}

if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form2') {

    $elaboracion = antisqlinyeccion($_POST['elaboracion'], "textbox");
    //echo $idmedida;

    // validaciones basicas
    $valido = "S";
    $errores = "";


    //actualizar fecha de ultima modificacion
    $update = "
    update recetas
    set
        elaboracion=$elaboracion,
         ultimo_cambio = '$ahora', 
         ultimo_cambio_por=$idusu 
     where 
         idreceta=$idreceta 
    ";
    $conexion->Execute($update) or die(errorpg($conexion, $update));

    //direccionar
    header("location: gest_recetas.php?id=".$idreceta);
    exit;

}

// busca si existe este producto en alguna promo
// promociones
$ahorad = date("Y-m-d");
$consulta = "
SELECT * 
FROM promociones
where
hasta >= '$ahorad'
and estado = 1
and idempresa = $idempresa
and idproducto = $id
limit 1
";
$rsprom = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
if ($rsprom->fields['idpromo'] > 0) {
    $addfiltrosinpromo = "";
} else {
    /*$addfiltrosinpromo="
    and (insumos_lista.idproducto is null or insumos_lista.idproducto = $id)
     ";    */
}

$buscar = "Select idingrediente,ingredientes.idinsumo,descripcion
from ingredientes 
inner join insumos_lista on insumos_lista.idinsumo=ingredientes.idinsumo
where 
ingredientes.estado = 1
and insumos_lista.estado = 'A'
and insumos_lista.hab_invent = 1
$addfiltrosinpromo
order by descripcion asc";
$rslista = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tlista = $rslista->RecordCount();
//Unidades
$buscar = "Select * from medidas where estado = 1 order by nombre ASC";
$rsmed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));


$buscar = "Select * from recetas where idproducto = $id and idempresa = $idempresa order by nombre asc";
$recetas = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$treceta = $recetas->RecordCount();
?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function IsJsonString(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}

function buscar_insumo_ventana(){
    var direccionurl='busqueda_producto.php';        
    var parametros = {
      "dest" : 'ingredientes'
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $('#modal_ventana').modal('show');
            $("#modal_titulo").html('Buscar Articulo');
            $("#modal_cuerpo").html('Cargando...');        
            $("#ingredientes").val('');            
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        }
    });
}
function prod_busq(dest){
    var direccionurl='busqueda_producto.php';        
    var parametros = {
      "dest" : dest          
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $('#cuadro_pop').modal('show');
            $("#myModalLabel").html('Busqueda de Ingredientes de Origen');
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        }
    });
    
}
function buscar_producto(destino_resultado){
    
    var busqueda = $("#producto").val();
    var direccionurl='busqueda_ingredientes_prodrec.php';        
    var parametros = {
      "prod"   : busqueda,
      "dest"   : destino_resultado
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        beforeSend: function () {
            $("#busqueda_prod").html('Cargando...');                
        },
        success:  function (response) {
            $("#busqueda_prod").html(response);
        }
    });
}
function buscar_producto_codbar(e,destino_resultado){
    
    var codbar = $("#codbar").val();
    tecla = (document.all) ? e.keyCode : e.which;
    // tecla enter
      if (tecla==13){
        busca_producto_codbar_res(codbar,destino_resultado);
    }
    
}
function busca_producto_codbar_res(codbar,destino_resultado){
    //alert(destino_resultado);
    var parametros = {
      "codbar"   : codbar,
      "dest"   : destino_resultado
    };
    $.ajax({          
        data:  parametros,
        url:   'busqueda_ingredientes_prodrec.php',
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#busqueda_prod").html('Cargando...');                
        },
        success:  function (response) {
            $("#busqueda_prod").html(response);
            //alert(response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
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
    
    
}
function seleccionar_item(idproducto,idinput,descipcion,idinsumo){
    $("#idinsumo2").val(idinsumo+' - '+descipcion.replace("'",""));
    $("#ingredientes").val(idproducto);
    //$('#modal').modal().hide();
    $('#modal_ventana').modal('hide');
    muestra_alias(idinsumo);
    
}

function muestra_alias(idinsumo){
    var texto = document.getElementById('idinsumo2').value;
    var insumo = document.getElementById('ingredientes').value;
    var parametros = {
                "id" : idinsumo
    };
    $.ajax({
        data:  parametros,
        url:   'medida_receta.php',
        type:  'post',
        beforeSend: function () {
                $("#medida").val("Cargando...");
        },
        success:  function (response) {
            //alert(response);
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                $("#medida").val(obj.medida);
                $("#rendimiento_porc_0").val(obj.rendimiento_porc);
            }else{
                alert(response);    
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
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
}
function eliminar(id,prod){
    if(window.confirm('Esta seguro que desea eliminar '+prod+' ?')){
        document.location.href='receta_ingrediente_elimina.php?id='+id;
    }
}
function editar_receta(idreceta_detalle){
    var parametros = {
            "idreceta_detalle" : idreceta_detalle
    };
    $.ajax({
        data:  parametros,
        url:   'gest_recetas_edit.php',
        type:  'post',
        beforeSend: function () {
            $("#modal_titulo").html('Editar Receta');
            $("#modal_cuerpo").html('Cargando...');
            $('#modal_ventana').modal('show');
        },
        success:  function (response) {
            $("#modal_cuerpo").html(response);
        }
    });
    
}
function registra_edicion(idreceta_detalle){
    var ingredientes_ed = $("#ingredientes").val();
    var cantidad_ed = $("#cantidad_1").val();
    var cantidad_neta_ed = $("#cantidad_neta_1").val();
    var rendimiento_porc_ed = $("#rendimiento_porc_1").val();
    var sacar_ed = $("#sacar_ed").val();
    var comentario_ing_ed = $("#comentario_ing_ed").val();
    
    var parametros = {
            "ingredientes" : ingredientes_ed,
            "cantidad_ed" : cantidad_ed,
            "cantidad_neta_ed" : cantidad_neta_ed,
            "rendimiento_porc_ed" : rendimiento_porc_ed,
            "comentario_ing_ed" : comentario_ing_ed,
            "sacar_ed" : sacar_ed,
            "idreceta_detalle" : idreceta_detalle,
            "MM_update" : "form1"
            
    };
    $.ajax({
        data:  parametros,
        url:   'gest_recetas_edit.php',
        type:  'post',
        beforeSend: function () {
            $("#modal_titulo").html('Editar Receta');
            $("#modal_cuerpo").html('Cargando...');
            $('#modal_ventana').modal('show');
        },
        success:  function (response) {
            
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){

                    document.location.href='gest_recetas.php?id=<?php echo $id; ?>';
                }else{
                    $("#modal_cuerpo").html(response);
                }
            }else{
                $("#modal_cuerpo").html(response);    
            }
        }
    });
}
function calcular_neto(id){
    
    var cantidad_bruta = $("#cantidad_"+id).val();
    var rendimiento_porc = $("#rendimiento_porc_"+id).val();
    
    var cantidad_neta = cantidad_bruta*(rendimiento_porc/100);
    $("#cantidad_neta_"+id).val(cantidad_neta);
    
    
        
}
function calcular_bruto(id){
    
    var cantidad_neta = $("#cantidad_neta_"+id).val()
    var rendimiento_porc = $("#rendimiento_porc_"+id).val();
    
    var cantidad_bruta = (100*cantidad_neta)/rendimiento_porc
    $("#cantidad_"+id).val(cantidad_bruta);
    
    
        
}
function guardar(idreceta_detalleprod){
    
    var cantidad = $("#cantidad_1").val();    
    var cantidad_neta = $("#cantidad_neta_1").val();    
    var rendimiento_porc = $("#rendimiento_porc_1").val();    
    var comentario_ing = $("#comentario_ing_1").val();
    
    var direccionurl='prod_receta_edit_cant.php';    
    var parametros = {
      "idreceta_detalleprod"  : idreceta_detalleprod,
      "cantidad"              : cantidad,
      "cantidad_neta"         : cantidad_neta,
      "rendimiento_porc"      : rendimiento_porc,
      "comentario_ing"        : comentario_ing,
      "guardar"               : "S"
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 5000,  // I chose 5 secs for kicks: 5000
        crossDomain: true,
        beforeSend: function () {
            $("#modal_titulo").html('Editar Ingrediente en Receta');    
            $("#modal_cuerpo").html('Guardando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                if(obj.valido == 'S'){
                    // hacer algo
                    //$("#modal_cuerpo").html(obj.saldo_factura);
                    $('#modal_ventana').modal('hide');
                    document.location.href='prod_receta_edit.php?id=<?php echo $idreceta ?>';
                }else{
                    //alert('Errores: '+obj.errores);    
                    $("#error_box_msg").html(nl2br(obj.errores));
                    $("#error_box").show();
                }
            }else{
                alert(response);
                $("#modal_cuerpo").html(response);    
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(jqXHR.status == 404){
                alert('Pagina no encontrada. '+jqXHR.status+' '+errorThrown);
            }else if(jqXHR.status == 0){
                alert('Se ha rechazado la conexión.');
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
                    <h2>Receta de <?php echo $rsp->fields['descripcion']; ?> [<?php echo $rsp->fields['idprod_serial']; ?>]</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">

                  
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<p>
<a href="gest_listado_productos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
<a href="gest_recetas_copiar.php?idproducto_origen=<?php echo intval($_GET['id']); ?>" class="btn btn-sm btn-default"><span class="fa fa-copy"></span> Copiar Receta</a>


</p>
<hr />
       <form id="form1" name="form1" method="post" action="">
       
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Ingrediente *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idinsumo2" id="idinsumo2" value="<?php  if (intval($_GET['idinsumo2']) > 0) {
        echo antixss($_GET['idinsumo2']);
    } ?>" placeholder="Click para Buscar Ingrediente..." class="form-control" readonly onMouseUp="buscar_insumo_ventana();"  /> 
    <input type="hidden" name="ingredientes" id="ingredientes"  value="<?php  if (intval($_GET['ingredientes']) > 0) {
        echo antixss($_GET['ingredientes']);
    } ?>"   /> 
        
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Medida *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <input name="medida" type="text" disabled="disabled" id="medida"  value="Gramos" readonly class="form-control" />             
    </div>
</div>
<?php /*?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cantidad" id="cantidad" value="<?php  if(isset($_POST['cantidad'])){ echo htmlentities($_POST['cantidad']); }else{ echo htmlentities($rs->fields['cantidad']); }?>" placeholder="Cantidad" class="form-control" required />
    </div>
</div>
<?php */ ?>           
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad Bruta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cantidad" id="cantidad_0" value="<?php  if (isset($_POST['cantidad'])) {
        echo htmlentities($_POST['cantidad']);
    } else {
        echo htmlentities($rs->fields['cantidad']);
    }?>" placeholder="Cantidad Bruta" class="form-control" required onKeyUp="calcular_neto(0);" />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cantidad Neta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cantidad_neta" id="cantidad_neta_0" value="<?php  if (isset($_POST['cantidad_neta'])) {
        echo htmlentities($_POST['cantidad_neta']);
    } else {
        echo htmlentities($rs->fields['cantidad_neta']);
    }?>" placeholder="Cantidad Neta" class="form-control" required onKeyUp="calcular_bruto(0);" />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">% Rendimiento *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="rendimiento_porc" id="rendimiento_porc_0" value="<?php  if (isset($_POST['rendimiento_porc'])) {
        echo htmlentities($_POST['rendimiento_porc']);
    } else {
        echo htmlentities($rs->fields['rendimiento_porc']);
    }?>" placeholder="Rendimiento" class="form-control" required onKeyUp="calcular_neto(0);" />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Observacion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="comentario_ing" id="comentario_ing" value="<?php  if (isset($_POST['comentario_ing'])) {
        echo htmlentities($_POST['comentario_ing']);
    } else {
        echo htmlentities($rs->fields['comentario_ing']);
    }?>" placeholder="Observacion" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Permite Sacar *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
        <select id="sacar" name="sacar" class="form-control">
        <option value="S" selected="selected" >SI</option>
        <option value="N" >NO</option>
        </select>                    
    </div>
</div>
    
    


<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='gest_listado_productos.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>
    
    <input type="hidden" name="alias" id="alias"  />
  <input type="hidden" name="reemplazar" id="textfield" value="N" />
  <input type="hidden" name="MM_insert" value="form1" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>
<div class="clearfix"></div>
<br /><br />

   <div id="componentes" align="center">
       <?php require_once("addrece.php");?>
   
   </div>

<div class="clearfix"></div>
            
            <hr />
<?php
$consulta = "
SELECT elaboracion
FROM recetas
where 
recetas.idreceta = $idreceta
and recetas.estado <> 6
";
$rsrecelab = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
?>
<strong>Elaboracion: </strong><br />
<br />
<form id="form2" name="form2" method="post" action="">
<textarea name="elaboracion" id="elaboracion" cols="" rows="10" class="form-control"><?php echo $rsrecelab->fields['elaboracion']; ?>
</textarea>

<div class="clearfix"></div>
<br />

    <div class="form-group">
        <div class="col-md-5 col-sm-5 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-default" ><span class="fa fa-save"></span> Guardar Elaboracion</button>

        </div>
    </div>
  <input type="hidden" name="MM_update" value="form2" />
  <input type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<br />
</form>

<div class="clearfix"></div>


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
