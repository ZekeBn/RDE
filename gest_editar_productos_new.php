<?php
require_once("includes/conexion.php");
require_once("includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "8";
require_once("includes/rsusuario.php");

require_once("includes/funciones_articulos.php");






$usa_concepto = $rsco->fields['usa_concepto'];
$idtipoiva_venta_pred = $rsco->fields['idtipoiva_venta_pred'];
$idtipoiva_compra_pred = $rsco->fields['idtipoiva_compra_pred'];


if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $idp = antisqlinyeccion($_GET['id'], 'int');
    $prodlista = intval($_GET['id']);
    $id = $idp;
    $buscar = "
    Select *, (select idgrupoinsu from insumos_lista where idproducto = productos.idprod_serial) as idgrupoinsu,
    (select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo,
    (select nombre from medidas where id_medida=productos.idmedida) as medida,
    (select idinsumo from insumos_lista where idproducto = productos.idprod_serial) as idinsumo,
    (select idtipoiva from insumos_lista where idproducto = productos.idprod_serial) as idtipoiva_compra,
    (select cant_medida2 from insumos_lista where idproducto = productos.idprod_serial) as cant_medida2,
    (select cant_medida3 from insumos_lista where idproducto = productos.idprod_serial) as cant_medida3
    from productos 
    where 
    idprod_serial=$id 
    and borrado = 'N'  
    and idempresa = $idempresa
    ";
    $rsminip = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
    $idtipoproducto = intval($rsminip->fields['idtipoproducto']);
    $cant_medida2 = intval($rsminip->fields['cant_medida2']);
    $cant_medida3 = intval($rsminip->fields['cant_medida3']);
    $idinsumo = intval($rsminip->fields['idinsumo']);
    $consulta = "
    select * from insumos_lista where idinsumo = $idinsumo limit 1
    ";
    $rsinsumo = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $hab_compra = intval($rsinsumo->fields['hab_compra']);
    if ($idtipoproducto == 0) {
        $idtipoproducto = 1;
    }

    $tr = $rsminip->RecordCount();
    if ($tr == 0) {
        echo "Producto Inexistente!";
        exit;
    }
    //calculamos el costo seguro
    $costoactual = floatval($rsminip->fields['costo_actual']);
    $porce = (($costoactual * 1) / 100);

    $costoseguro = $costoactual + $porce;

    // busca si existe un insumo vinculado
    $consulta = "
    select * 
    from insumos_lista 
    where 
    idproducto = $id
    ";
    $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $idinsumo = intval($rsins->fields['idinsumo']);

}
//EDICION//--------------------

$consulta = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$diferencia_precio_suc = $rspref->fields['diferencia_precio_suc'];

//Eliminar y traer de la nueba tabla de preferencias_productos
$consulta = "Select usa_costo_referencial, usa_recargo_precio_costo from preferencias_caja limit 1";
$rsprefcaj = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


$consulta = "Select * from preferencias_productos limit 1";
$rsprefprod = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

$usa_costo_referencial = trim($rsprefprod->fields['usa_costo_referencial']);
$tipo_precio_defecto = trim($rsprefprod->fields['tipo_precio_defecto']);
// El tipo de aumento debe ser o porcentaje o cantidad, no ambos, si tiene definido ambos, tomamos como prioridad el porcentaje
$porc_aumento_costo = floatval($rsprefprod->fields['porc_aumento_costo']);
$cantidad_veces_pcosto = intval($rsprefprod->fields['cantidad_veces_pcosto']);

if (floatval($porc_aumento_costo) > 0 && ($cantidad_veces_pcosto > 0)) {
    $cantidad_veces_pcosto = 0;//solo tomaremos el porc
    $claseau = 'P';
} else {
    if (floatval($porc_aumento_costo) > 0) {
        $claseau = 'P';
    }
    if (intval($cantidad_veces_pcosto) > 0) {
        $claseau = 'C';
    }

}

if (isset($_POST['MM_insert']) && $_POST['MM_insert'] == 'form1') {
    //print_r($_POST);
    //exit;


    $idp = antisqlinyeccion($_POST['psele'], 'text');
    $descripcion = antisqlinyeccion($_POST['descripcion'], 'text');
    $p1 = antisqlinyeccion($_POST['p1'], 'float');
    $p2 = antisqlinyeccion(0, 'float');
    $p3 = antisqlinyeccion('0', 'float');
    $genero = intval($_POST['genero']);
    $favorito = antisqlinyeccion(trim($_POST['favorito']), 'text');
    $muestra_self = antisqlinyeccion(trim($_POST['muestra_self']), 'text');
    $muestra_vianda = antisqlinyeccion(trim($_POST['muestra_vianda']), 'text');
    $muestra_pedido = antisqlinyeccion(trim($_POST['muestra_pedido']), 'text');
    $subcategoria = intval($_POST['idsubcate']);
    $categoria = intval($_POST['idcategoria']);
    $idtipoiva = antisqlinyeccion($_POST['idtipoiva'], 'int');
    $idtipoiva_compra = antisqlinyeccion($_POST['idtipoiva_compra'], 'int');
    $webtext = ($_POST['']);//hay que ver para que se usa
    $descripcion_larga = antisqlinyeccion($_POST['descripcion_larga'], 'textbox');//esta es para la web
    $desc = antisqlinyeccion($_POST['desc'], 'float');
    $mostrar = intval($_POST['mostrarprod']);//Siempre viene cero, ya no se usa x lo visto
    $idgrupoinsu = antisqlinyeccion($_POST['idgrupoinsu'], 'int');
    //$idtipoproducto=antisqlinyeccion($_POST['idgrupoinsu'],'int'); NO se permite editar el tipo de prod. se debe borar y cargar de nuevo
    $idcpr = antisqlinyeccion($_POST['cpr'], 'int');//id del centro de produccion
    $web_muestra = antisqlinyeccion(substr($_POST['web_muestra'], 0, 1), 'text');
    $combinado = antisqlinyeccion(substr($_POST['combinado'], 0, 1), 'text');
    $combinado_tipoprecio = antisqlinyeccion($_POST['combinado_tipoprecio'], 'int');
    $combinado_maxitem = antisqlinyeccion($_POST['combinado_maxitem'], 'int');
    $combinado_minitem = antisqlinyeccion($_POST['combinado_minitem'], 'int');
    $idmarca = antisqlinyeccion($_POST['idmarca'], 'int');
    $codplu = antisqlinyeccion(substr($_POST['codplu'], 0, 5), 'text');
    $barcode = antisqlinyeccion($_POST['barcode'], 'text');
    $cuentacont = antisqlinyeccion($_POST['cuentacont'], 'int');
    $cuentacont_ven = antisqlinyeccion($_POST['cuentacont_ven'], 'int');
    $idmedida_referencial = antisqlinyeccion($_POST['idmedida_referencial'], "int");
    $cantidad_referencial = antisqlinyeccion($_POST['cantidad_referencial'], "float");

    $precio_abierto = antisqlinyeccion($_POST['precio_abierto'], "text");
    $precio_min = antisqlinyeccion($_POST['precio_min'], "float");
    $precio_max = antisqlinyeccion($_POST['precio_max'], "float");
    $costo_referencial = antisqlinyeccion($_POST['costo_referencial'], "float");
    $recargo_auto_costo = antisqlinyeccion(floatval($_POST['recargo_auto_costo']), "float");
    $idprodexterno = antisqlinyeccion($_POST['idprodexterno'], "textbox");
    $idproveedor = antisqlinyeccion($_POST['idproveedor'], "int");
    $aplica_regalia = antisqlinyeccion($_POST['aplica_regalia'], "text");

    $cant_medida2 = antisqlinyeccion($_POST['cant_medida2'], "float");
    $cant_medida3 = antisqlinyeccion($_POST['cant_medida3'], "float");
    // validaciones
    $valido = "S";
    $errores = "";
    // si es combinado extendido
    if ($rsminip->fields['idtipoproducto'] == 4) {
        // si el tipo de precio es diferente de definido
        if ($_POST['combinado_tipoprecio'] != 3) {
            $p1 = 0;
        }
        if (intval($_POST['combinado_maxitem']) < 2) {
            $valido = "N";
            $errores .= "-El maximo de items debe ser mayor o igual 2.<br />";
        }
        if (intval($_POST['combinado_minitem']) < 2) {
            $valido = "N";
            $errores .= "-El minimo de items debe ser mayor o igual 2.<br />";
        }
        if (intval($_POST['combinado_maxitem']) < intval($_POST['combinado_minitem'])) {
            $valido = "N";
            $errores .= "-El maximo de items debe ser mayor al minimo.<br />";
        }

    }

    if (trim($_POST['descripcion']) == '') {
        $valido = "N";
        $errores .= "-Debe indicar el nombre del producto.<br />";
    }
    if (intval($_POST['idcategoria']) == 0) {
        $valido = "N";
        $errores .= "-Debe indicar la categoria del producto.<br />";
    }
    if (intval($_POST['idsubcate']) == 0) {
        $valido = "N";
        $errores .= "-Debe indicar la subcategoria del producto.<br />";
    }
    if ($diferencia_precio_suc == 'N') {
        if (intval($_POST['p1']) == 0) {
            $valido = "N";
            $errores .= "-Debe indicar el precio del producto.<br />";
        }
    }
    //if($idtipoproducto == 1){
    if (intval($_POST['idgrupoinsu']) == 0) {
        if ($idtipoproducto != 6) {
            $valido = "N";
            $errores .= "-Debe indicar el grupo de insumo.<br />";
        }
    }
    //}
    if (intval($_POST['idtipoiva']) == 0) {

        $valido = "N";
        $errores .= "-Debe indicar el iva venta del producto.<br />";

    }
    if (intval($_POST['idtipoiva_compra']) == 0) {
        if ($idtipoproducto != 6) {
            $valido = "N";
            $errores .= "-Debe indicar el iva compra del producto.<br />";
        }
    }

    if ($_POST['precio_abierto'] == 'S') {
        if (floatval($_POST['precio_min']) < 0) {
            $valido = "N";
            $errores .= " - El campo precio min no puede ser negativo.<br />";
        }
        if (floatval($_POST['precio_max']) <= 0) {
            $valido = "N";
            $errores .= " - El campo precio max no puede ser cero o negativo.<br />";
        }
        if (floatval($_POST['precio_min']) > floatval($_POST['precio_max'])) {
            $valido = "N";
            $errores .= " - El campo precio min no puede ser mayor al precio max.<br />";
        }
    }



    //$idpantallacocina=antisqlinyeccion($_POST['idpantallacocina'],'int');
    //$idimpresoratk=antisqlinyeccion($_POST['idimpresoratk'],'int');
    $idpantallacocina = 0;
    $idimpresoratk = 0;
    $barcode = antisqlinyeccion($_POST['barcode'], 'text');
    $codplu = antisqlinyeccion($_POST['codplu'], 'text');
    $tipo = trim(htmlentities($_POST['tipo']));

    if ($rsminip->fields['combinado'] == 'S') {
        $p1 = 0;
    }


    // conversiones
    // iva venta
    $consulta = "
    select * 
    from tipo_iva
    where 
    idtipoiva = $idtipoiva
    ";
    $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipoiva = $rsiva->fields['iva_porc'];
    $idtipoiva = $rsiva->fields['idtipoiva'];
    $iguala_compra_venta = $rsiva->fields['iguala_compra_venta'];

    // iva compra
    $consulta = "
    select * 
    from tipo_iva
    where 
    idtipoiva = $idtipoiva_compra
    ";
    $rsiva = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $tipoiva_compra = $rsiva->fields['iva_porc'];
    $idtipoiva_compra = $rsiva->fields['idtipoiva'];

    if ($iguala_compra_venta == 'S') {
        if ($idtipoiva <> $idtipoiva_compra) {
            if ($idtipoproducto != 6) {
                $valido = "N";
                $errores .= "-El iva compra y venta debe ser el mismo para el tipo de iva venta seleccionado.<br />";
            }

        }
    }
    // si esta activa la contabilidad
    $contabilidad = intval($rsco->fields['contabilidad']);
    if ($contabilidad == 1) {
        /*if(intval($_POST['cuentacont_ven']) == 0){
            $valido="N";
            $errores.="-Debe indicar la cuenta contable para ventas del producto.<br />";
        }*/
        if ($hab_compra == 1) {
            if (intval($_POST['cuentacont']) == 0) {
                $valido = "N";
                $errores .= "-Debe indicar la cuenta contable para compras del producto, cuando el producto esta habilitado para compras.<br />";
            }
        }
    }
    if (intval($_POST['idmedida_referencial']) > 0) {
        if (floatval($_POST['cantidad_referencial']) <= 0) {
            $valido = "N";
            $errores .= "-Debe indicar la cantidad referencial cuando se carga una medida referencial.<br />";
        }
    }

    // busca que no  exista otro producto con el mismo nombre
    $consulta = "
    select  idprod_serial
    from productos 
    where
    idprod_serial <> $id 
    and borrado = 'N'
    and trim(descripcion) = $descripcion
    limit 1
    ";
    $rspex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rspex->fields['idprod_serial']) > 0) {
        $valido = "N";
        $errores .= "- Ya existe otro producto con el mismo nombre.<br />";
    }
    // busca que no  exista otro articulo con el mismo nombre
    $consulta = "
    select  idinsumo
    from insumos_lista 
    where
    idproducto <> $id 
    and estado = 'A'
    and trim(descripcion) = $descripcion
    limit 1
    ";
    $rsartex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    if (intval($rsartex->fields['idinsumo']) > 0) {
        $valido = "N";
        $errores .= "- Ya existe otro articulo con el mismo nombre.<br />";
    }


    if ($valido == 'S') {

        if ($diferencia_precio_suc == 'N') {
            $precioadd = "
            p1=$p1,
            ";
        }
        //    echo 'ADDP'.$precioadd;exit;
        $update = "update productos 
        set 
        descripcion=$descripcion,p2=$p2,p3=$p3,desc1=$desc,idcategoria=$categoria,
        idsubcate=$subcategoria,describeprodu='$webtext',mostrar=$mostrar,idgen=$genero, 
        muestra_vianda=$muestra_vianda,web_muestra=$web_muestra,idcpr=$idcpr,
        favorito = $favorito, 
        idpantallacocina = $idpantallacocina,
        idimpresoratk=$idimpresoratk,
        barcode=$barcode,
        codplu=$codplu,
        combinado_tipoprecio=$combinado_tipoprecio,
        combinado_maxitem=$combinado_maxitem,combinado_minitem=$combinado_minitem,
        idmarca=$idmarca,
        idtipoiva=$idtipoiva,
        tipoiva=$tipoiva,
        idplancuentadet=$cuentacont_ven,
        idmedida_referencial=$idmedida_referencial,
        cantidad_referencial=$cantidad_referencial,
        
        $precioadd
        
        muestra_self = $muestra_self,
        muestra_pedido = $muestra_pedido,
        web_muestra = $web_muestra,
        descripcion_larga=$descripcion_larga,
        precio_abierto=$precio_abierto,
        precio_min=$precio_min,
        precio_max=$precio_max,
        recargo_auto_costo=$recargo_auto_costo,
        idprodexterno=$idprodexterno

        where 
        idprod_serial=$id 
        and idempresa = $idempresa";
        //echo $update;
        $conexion->Execute($update) or die(errorpg($conexion, $update));
        //exit;
        //si aca se cambio algo, debemos afectar al virtual
        /*$update="update productos_virtual set descripcion=$texto,p1=$p1,p2=$p2,p3=$p3,desc1=$desc,idcategoria=$categoria,
        idsubcate=$subcategoria,describeprodu='$webtext',mostrar=$mostrar,idgen=$genero
        where idprod=$idp";
        $conexion->Execute($update) or die(errorpg($conexion,$update));*/

        // busca si existe un insumo vinculado
        $consulta = "
        select * 
        from insumos_lista 
        where 
        idproducto = $id
        and idempresa = $idempresa
        ";
        $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        $idinsumo = intval($rsins->fields['idinsumo']);

        // si existe producto vinculado al insumo
        if ($idinsumo > 0) {

            // iguala el nombre e iva
            $consulta = "
            update insumos_lista
            set 
            descripcion = $descripcion,
            idgrupoinsu = $idgrupoinsu,
            idtipoiva=$idtipoiva_compra,
            tipoiva=$tipoiva_compra,
            idcentroprod = $idcpr,
            idplancuentadet=$cuentacont,
            idcategoria=$categoria,
            idsubcate=$subcategoria,
            costo_referencial=$costo_referencial,
            idproveedor=$idproveedor,
            aplica_regalia=$aplica_regalia,
            cant_medida2=$cant_medida2,
            cant_medida3=$cant_medida3

            where
            idinsumo = $idinsumo
            and idproducto = $id
            and idempresa = $idempresa
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }

        // si no diferencia precios por sucursales
        if ($diferencia_precio_suc == 'N') {

            $consulta = "
            update productos_sucursales
            set 
            precio = $p1
            where
            idproducto = $id
            and idempresa = $idempresa
            ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        }


        // redirecciona
        header("location: gest_editar_productos_new.php?id=$id&editado=ok");
        exit;

    } // if valido
}
/*
$buscar="Select * from productos  where idempresa = $idempresa  order by descripcion asc";
$rsprodur=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
*/


//Datos
//Categorias
$buscar = "Select * from categorias 
where 
estado = 1
and (idempresa = $idempresa or borrable = 'N')
and id_categoria not in (SELECT idcategoria FROM categoria_ocultar where idempresa = $idempresa and mostrar = 'N')
 order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Categorias
$buscar = "Select * from sub_categorias where 
(idempresa = $idempresa or idcategoria in (select id_categoria from categorias where especial = 'S'))
 order by descripcion ASC";
$rssubcate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Unidades
$buscar = "Select * from medidas order by nombre ASC";
$rsmed = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
//Proveedor
$buscar = "Select * from proveedores where idempresa=$idempresa order by nombre ASC";
$rsprov = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$tprov = $rsprov->RecordCount();

//Genero
//$buscar="SELECT * FROM gest_genero order by descripcion asc";
//$rsgen=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

$img = "gfx/productos/prod_".$rsminip->fields['idprod_serial'].".jpg";
if (!file_exists($img)) {
    $img = "gfx/productos/prod_0.jpg";
}

if ($rsminip->fields['barcode'] == '') {
    $style = "style='width:90%; height:40px;'";
    $barcode = "<input <?php echo $disabled; ?> name='barcode' type='text' id='barcode' placeholder='Utilizar Lector' $style value=\"".$rsminip->fields['barcode']."\" />";
} else {
    $style = "style='width:90%; height:40px;'";
    $bv = "<span style='color:#0A9600; font-weight:bold;'>".$rsminip->fields['barcode']."</span>";

    $barcode = "<div align='left'><input <?php echo $disabled; ?> name='barcode' type='text' id='barcode' placeholder='Utilizar Lector' $style value=\"".$rsminip->fields['barcode']."\" /><br /><div style='float:left; width:30px; border:1px;'><img src='img/barcode01.png' width='40' height='40'  /></div><div style='float:left; width:30px; border:1px; margin-top:12px; margin-left:5px;'>$bv</div></div>";
}
if ($rsminip->fields['codplu'] == '') {
    $style = "style='width:90%; height:40px;'";
    $codplu = "<input <?php echo $disabled; ?> name='codplu' type='text' id='codplu' placeholder='Utilizar Lector' $style value=\"".$rsminip->fields['codplu']."\" />";
} else {
    $style = "style='width:90%; height:40px;'";
    $bv = "<span style='color:#0A9600; font-weight:bold;'>".$rsminip->fields['codplu']."</span>";

    $codplu = "<input <?php echo $disabled; ?> name='codplu' type='text' id='codplu' placeholder='Utilizar Lector' $style value=\"".$rsminip->fields['codplu']."\" />";
}




?><!DOCTYPE html>
<html lang="en">
  <head>
    <?php require_once("includes/head_gen.php"); ?>
<script>
function subcategorias(idcategoria){
    var direccionurl='subcate_new.php';    
    var parametros = {
      "idcategoria" : idcategoria
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#subcatebox").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(xhr.status === 200){
                $("#subcatebox").html(response);
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
function tipo_producto(idtipoproducto){
    //producto
    if(idtipoproducto == 1){    

    }
    // combo
    if(idtipoproducto == 2){    
    
    }
    // combinado
    if(idtipoproducto == 3){    
        $("#div_combinado_tipoprecio").show();
    }
    // combinado extendido
    if(idtipoproducto == 4){    
        $("#div_combinado_minitem").show();
        $("#div_combinado_maxitem").show();
        $("#div_combinado_tipoprecio").show();    
    }else{
        $("#div_combinado_minitem").hide();
        $("#div_combinado_maxitem").hide();
        if(idtipoproducto != 3){
            $("#div_combinado_tipoprecio").hide();    
        }
    }
    // agregado
    if(idtipoproducto == 5){    
    
    }
    // delivery
    if(idtipoproducto == 6){    
    
    }    
    // servicio
    if(idtipoproducto == 7){    
    
    }    
    
    
}

function alerta_modal(titulo,mensaje){
    $('#dialogobox').modal('show');
    $("#myModalLabel").html(titulo);
    $("#modal_cuerpo").html(mensaje);

    
}
function ventana_categoria(){
    var direccionurl='categoria_prod_add.php';    
    var parametros = {
      "add"        : 'N'
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Agregar Categoria');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $('#dialogobox').modal('show');
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
function ventana_subcategoria(){
    var direccionurl='subcategoria_prod_add.php';    
    var parametros = {
      "add"        : 'N'
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Agregar Sub-Categoria');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#modal_cuerpo").html(response);    
            $('#dialogobox').modal('show');
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
function agregar_categoria(){
    var direccionurl='categoria_prod_add.php';
    var categoria = $("#categoria").val();    
    var parametros = {
      "add"        : 'S',
      "categoria"  : categoria
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Agregar Categoria');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                recargar_categoria(obj.idcategoria);
                $("#modal_cuerpo").html('');
                $('#dialogobox').modal('hide');

            }else{
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
function agregar_subcategoria(){
    var direccionurl='subcategoria_prod_add.php';
    var categoria = $("#categoria").val();    
    var subcategoria = $("#subcategoria").val();
    var parametros = {
      "add"        : 'S',
      "categoria"  : categoria,
      "subcategoria"  : subcategoria
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {
            $("#myModalLabel").html('Agregar Sub-Categoria');    
            $("#modal_cuerpo").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            if(IsJsonString(response)){
                var obj = jQuery.parseJSON(response);
                recargar_categoria(obj.idcategoria);
                recargar_subcategoria(obj.idcategoria,obj.idsubcategoria);
                $("#modal_cuerpo").html('');
                $('#dialogobox').modal('hide');

            }else{
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
function recargar_categoria(idcategoria){
    var direccionurl='cate_new.php';
    var parametros = {
      "idcategoria" : idcategoria,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {    
            $("#categoriabox").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#categoriabox").html(response);    
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
function recargar_subcategoria(idcategoria,idsubcategoria){
    var direccionurl='subcate_new.php';
    var parametros = {
      "idcategoria" : idcategoria,
      "idsubcate" : idsubcategoria,
    };
    $.ajax({          
        data:  parametros,
        url:   direccionurl,
        type:  'post',
        cache: false,
        timeout: 3000,  // I chose 3 secs for kicks: 3000
        crossDomain: true,
        beforeSend: function () {    
            $("#subcatebox").html('Cargando...');                
        },
        success:  function (response, textStatus, xhr) {
            $("#subcatebox").html(response);    
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
                    <h2>Editar Producto</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">


<?php
$disabled = "";
if (trim($_GET['editado']) == "ok") {
    $disabled = "disabled";
    ?>
<div class="alert alert-success alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Edicion Correcta!</strong>  <a href="gest_editar_productos_new.php?id=<?php echo intval($_GET['id']); ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Editar </a>
<a href="gest_listado_productos.php" class="btn btn-sm btn-default" title="Volver a Productos" data-toggle="tooltip" data-placement="right"  data-original-title="Volver a Productos"><span class="fa fa-search"></span> Productos </a>
</div>
<?php } ?>
<?php if (trim($errores) != "") { ?>
<div class="alert alert-danger alert-dismissible fade in" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span>
</button>
<strong>Errores:</strong><br /><?php echo $errores; ?>
</div>
<?php } ?>
<form id="form1" name="form1" method="post" action="">

<strong>Informacion Basica:</strong>
<hr />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Producto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input <?php echo $disabled; ?> type="text" name="descripcion" id="descripcion" value="<?php  if (isset($_POST['descripcion'])) {
        echo htmlentities($_POST['descripcion']);
    } else {
        echo htmlentities(trim($rsminip->fields['descripcion']));
    }?>" placeholder="Producto" class="form-control" required autofocus />     <input <?php echo $disabled; ?> type="hidden" name="psele" id="psele" value="<?php echo trim($rsminip->fields['idprod']); ?>" />
                      <input <?php echo $disabled; ?> type="hidden" name="pcosto" id="pcosto" value="<?php echo $costoseguro ?>" />               
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio venta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php if ($diferencia_precio_suc == 'N') { ?>
    <input <?php echo $disabled; ?> type="text" name="p1" id="p1" size="10"  value="<?php echo intval($rsminip->fields['p1']) ?>" style="width:90%; height:40px;" />
     <?php } else { ?>
    <a href="producto_precio_asigna.php?id=<?php echo $rsminip->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Variable por Sucursal</a>
    <?php }?>
    </div>

</div>

<div class="clearfix"></div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_categoria();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Categoria * </label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="categoriabox">
<?php
require_once("cate_new.php");

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12"><a href="javascript:void(0);" onClick="ventana_subcategoria();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a> Subcategoria *</label>
    <div class="col-md-9 col-sm-9 col-xs-12" id="subcatebox">
<?php
require_once("subcate_new.php");

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Medida </label>
    
    <a href="gest_insumos_edit_medida.php?id=<?php echo $rsminip->fields['idinsumo'] ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> <?php echo $rsminip->fields['medida'] ?> </a>

</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">IVA Venta *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
    // consulta
    $consulta = "
    SELECT idtipoiva, iva_porc, iva_describe
    FROM tipo_iva
    where
    estado = 1
    and hab_venta = 'S'
    order by iva_porc desc
     ";

// valor seleccionado
if (isset($_POST['idtipoiva'])) {
    $value_selected = htmlentities($_POST['idtipoiva']);
} else {
    $value_selected = $rsminip->fields['idtipoiva'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipoiva',
    'id_campo' => 'idtipoiva',

    'nombre_campo_bd' => 'iva_describe',
    'id_campo_bd' => 'idtipoiva',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$disabled,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">IVA Compra *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
// consulta
$consulta = "
    SELECT idtipoiva, iva_porc, iva_describe
    FROM tipo_iva
    where
    estado = 1
    and hab_compra = 'S'
    order by iva_porc desc
     ";

// valor seleccionado
if (isset($_POST['idtipoiva_compra'])) {
    $value_selected = htmlentities($_POST['idtipoiva_compra']);
} else {
    $value_selected = $rsminip->fields['idtipoiva_compra'];
}
/*if ($idtipoproducto!=6){
    $acc=" required='required' ";
} else {
    $acc = "disabled = 'disabled' ";
}*/
// parametros
$parametros_array = [
    'nombre_campo' => 'idtipoiva_compra',
    'id_campo' => 'idtipoiva_compra',

    'nombre_campo_bd' => 'iva_describe',
    'id_campo_bd' => 'idtipoiva',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => $acc.$disabled ,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
                 
    
    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Grupo Stock *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">

    
<?php
// consulta
$consulta = "
SELECT idgrupoinsu, nombre
FROM grupo_insumos
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if ($_POST['idgrupoinsu'] > 0) {
    $value_selected = htmlentities($_POST['idgrupoinsu']);
} else {
    $value_selected = htmlentities($rsminip->fields['idgrupoinsu']);
}
/*if ($idtipoproducto!=6){
        $acc=" required='required' ";
    } else {
        $acc = "disabled = 'disabled' ";
    }*/
// parametros
$parametros_array = [
    'nombre_campo' => 'idgrupoinsu',
    'id_campo' => 'idgrupoinsu',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idgrupoinsu',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => $acc.$disabled ,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);


?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo de Producto *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idtipoproducto, tipoproducto
FROM productos_tipo
order by idtipoproducto asc
 ";

// valor seleccionado
if ($rsminip->fields['idtipoproducto'] > 0) {
    $value_selected = htmlentities($rsminip->fields['idtipoproducto']);
} else {
    $value_selected = htmlentities($rsminip->fields['idtipoproducto']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idtipoproducto',
    'id_campo' => 'idtipoproducto',

    'nombre_campo_bd' => 'tipoproducto',
    'id_campo_bd' => 'idtipoproducto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => 'disabled = "disabled"  ',
    'autosel_1registro' => 'S',


];

// construye campo
echo campo_select($consulta, $parametros_array);



?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
   <label class="control-label col-md-3 col-sm-3 col-xs-12">Cant.de Cajas x Pallet *</label> 
   <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name=" cant_medida2" id="cant_medida2" value="<?php  if (isset($_POST['cant_medida2'])) {
        echo floatval($_POST['cant_medida2']);
    } else {
        echo floatval($rsinsumo->fields['cant_medida2']);
    }?>" placeholder="Cant.Cajas por Pallet" class="form-control" <?php echo $disabled; ?>  />                    
   </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
   <label class="control-label col-md-3 col-sm-3 col-xs-12">Cant.de Pallet *</label> 
   <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name=" cant_medida3" id="cant_medida3" value="<?php  if (isset($_POST['cant_medida3'])) {
        echo floatval($_POST['cant_medida3']);
    } else {
        echo floatval($rsinsumo->fields['cant_medida3']);
    }?>" placeholder="Cant.de Pallet" class="form-control" <?php echo $disabled; ?>  />                    
   </div>
</div>


<?php
$contabilidad = intval($rsco->fields['contabilidad']);

if ($contabilidad == 1) {
    //buscamos el id de insumo del producto
    /*$buscar="Select * from insumos_lista where idinsumo=$idp";
    $rfd=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));
    $idinsumis=intval($rfd->fields['idinsumo']);

    //Buscamos el articulo vinculado para marcar el select
    $buscar="select * from  cn_articulos_vinculados where idinsumo=$idinsumis ";
    $rfd1=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

    $idsercuenta=intval($rfd1->fields['idsercuenta']);

    //ahora el requerido
    $buscar="Select * from cn_plancuentas_detalles where idserieun=$idsercuenta ";
    $rfd2=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

    $cuentis=trim($rfd2->fields['cuenta']);*/

    //echo $cuentis;exit;
    ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cuenta Contable (Compra) *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

    // consulta
    $consulta = "
SELECT idserieun, descripcion
FROM cn_plancuentas_detalles
where 
estado<>6 
and asentable='S' 
order by descripcion asc
 ";

    // valor seleccionado
    if (isset($_POST['cuentacont'])) {
        $value_selected = htmlentities($_POST['cuentacont']);
    } else {
        $value_selected = htmlentities($rsinsumo->fields['idplancuentadet']);
    }

    // parametros
    $parametros_array = [
        'nombre_campo' => 'cuentacont',
        'id_campo' => 'cuentacont',

        'nombre_campo_bd' => 'descripcion',
        'id_campo_bd' => 'idserieun',

        'value_selected' => $value_selected,

        'pricampo_name' => 'Seleccionar...',
        'pricampo_value' => '',
        'style_input' => 'class="form-control"',
        'acciones' => '  '.$disabled,
        'autosel_1registro' => 'N'

    ];

    // construye campo
    echo campo_select($consulta, $parametros_array);

    ?>
    </div>
</div>

<?php } ?>

<div class="col-md-6 col-sm-6 form-group" id="div_combinado_minitem" <?php if ($rsminip->fields['idtipoproducto'] != 4) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Min Piezas/Items *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input <?php echo $disabled; ?> type="text" name="combinado_minitem" id="combinado_minitem" value="<?php  if (isset($_POST['combinado_minitem'])) {
        echo intval($_POST['combinado_minitem']);
    } else {
        echo intval($rsminip->fields['combinado_minitem']);
    }?>" placeholder="Combinado minitem" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group" id="div_combinado_maxitem" <?php if ($rsminip->fields['idtipoproducto'] != 4) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Max Piezas/Items *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input <?php echo $disabled; ?> type="text" name="combinado_maxitem" id="combinado_maxitem" value="<?php  if (isset($_POST['combinado_maxitem'])) {
        echo intval($_POST['combinado_maxitem']);
    } else {
        echo intval($rsminip->fields['combinado_maxitem']);
    }?>" placeholder="Combinado maxitem" class="form-control"  />                    
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group"  id="div_combinado_tipoprecio" <?php if ($_POST['idtipoproducto'] != 3 && $_POST['idtipoproducto'] != 4) { ?>style="display:none;"<?php } ?>>
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tipo Precio *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['combinado_tipoprecio'])) {
    $value_selected = htmlentities($_POST['combinado_tipoprecio']);
} else {
    $value_selected = htmlentities($rsminip->fields['combinado_tipoprecio']);
}
// opciones
$opciones = [
    'Promedio' => 1,
    'Mayor' => 2,
    'Definido' => 3,
];
// parametros
$parametros_array = [
    'nombre_campo' => 'combinado_tipoprecio',
    'id_campo' => 'combinado_tipoprecio',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  ',
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>



<div class="clearfix"></div>
<br />
<strong>Informacion Adicional:</strong>
<hr />

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Descripcion larga </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input <?php echo $disabled; ?> type="text" name="descripcion_larga" id="descripcion_larga" value="<?php  if (isset($_POST['descripcion_larga'])) {
        echo htmlentities($_POST['descripcion_larga']);
    } else {
        echo htmlentities($rsminip->fields['descripcion_larga']);
    }?>" placeholder="Descripcion larga" class="form-control"  />                    
    </div>
</div>



<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo de Barras </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input <?php echo $disabled; ?> type="text" name="barcode" id="barcode" value="<?php  if (isset($_POST['barcode'])) {
        echo htmlentities($_POST['barcode']);
    } else {
        echo htmlentities($rsminip->fields['barcode']);
    } ?>" placeholder="Barcode" class="form-control"  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo Pesable </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input <?php echo $disabled; ?> type="text" name="codplu" id="codplu" value="<?php  if (isset($_POST['codplu'])) {
        echo intval($_POST['codplu']);
    } else {
        echo htmlentities($rsminip->fields['codplu']);
    } ?>" placeholder="Codplu" class="form-control"  />                    
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Proveedor </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php

// consulta
$consulta = "
SELECT idproveedor, nombre
FROM proveedores
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idproveedor'])) {
    $value_selected = htmlentities($_POST['idproveedor']);
} else {
    $value_selected = htmlentities($rsins->fields['idproveedor']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idproveedor',
    'id_campo' => 'idproveedor',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'idproveedor',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' '.$disabled,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Favorito </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if ($_POST['favorito'] != '') {
    $value_selected = htmlentities($_POST['favorito']);
} else {
    $value_selected = htmlentities($rsminip->fields['favorito']);
}
if ($value_selected == '') {
    $value_selected = "N";
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'favorito',
    'id_campo' => 'favorito',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$disabled,
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar en Tienda *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['web_muestra'])) {
    $value_selected = htmlentities($_POST['web_muestra']);
} else {
    $value_selected = $rsminip->fields['web_muestra'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'web_muestra',
    'id_campo' => 'web_muestra',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$disabled,
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar Self Service *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (trim($rsminip->fields['muestra_self']) <> '') {
    $value_selected = htmlentities($rsminip->fields['muestra_self']);
} else {
    $value_selected = htmlentities($_POST['muestra_self']);
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'muestra_self',
    'id_campo' => 'muestra_self',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$disabled,
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>

    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar en vianda *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['muestra_vianda'])) {
    $value_selected = htmlentities($_POST['muestra_vianda']);
} else {
    $value_selected = htmlentities($rsminip->fields['muestra_vianda']);
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'muestra_vianda',
    'id_campo' => 'muestra_vianda',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$disabled,
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Mostrar en Menu Digital *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if ($rsminip->fields['muestra_pedido']) {
    $value_selected = htmlentities($rsminip->fields['muestra_pedido']);
} else {
    $value_selected = htmlentities($rsminip->fields['muestra_pedido']);
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'muestra_pedido',
    'id_campo' => 'muestra_pedido',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$disabled,
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Marca </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT idmarca, marca
FROM marca
where
idestado = 1
order by marca asc
 ";

// valor seleccionado
if (isset($_POST['idmarca'])) {
    $value_selected = htmlentities($_POST['idmarca']);
} else {
    $value_selected = htmlentities($rsminip->fields['idmarca']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmarca',
    'id_campo' => 'idmarca',

    'nombre_campo_bd' => 'marca',
    'id_campo_bd' => 'idmarca',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  '.$disabled,
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>




  
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Centro Produccion </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
// consulta
$consulta = "
Select idcentroprod,  descripcion
from produccion_centros 
where 
estado <> 6 
order by descripcion asc
";

// valor seleccionado
if ($rsminip->fields['idcpr'] > 0) {
    $value_selected = htmlentities($rsminip->fields['idcpr']);
} else {
    $value_selected = htmlentities($_POST['cpr']);
}

// parametros
$parametros_array = [
    'nombre_campo' => 'cpr',
    'id_campo' => 'cpr',

    'nombre_campo_bd' => 'descripcion',
    'id_campo_bd' => 'idcentroprod',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => '  '.$disabled,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);
?>
    </div>
</div>


<?php if ($usa_costo_referencial == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio Referencial Venta </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input  type="text" name="p2ref" id="p2ref" value="<?php  if (isset($_POST['p2ref'])) {
        echo intval($_POST['p2ref']);
    } else {
        echo htmlentities($rsminip->fields['p2']);
    } ?>" placeholder="Precio referencial ventas" class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>
<?php } ?>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Aplica regalia </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// valor seleccionado
if (isset($_POST['aplica_regalia'])) {
    $value_selected = htmlentities($_POST['aplica_regalia']);
} else {
    $value_selected = $rsins->fields['aplica_regalia'];
}
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'aplica_regalia',
    'id_campo' => 'aplica_regalia',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '. $disabled,
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);
?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Medida Referencial </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
<?php
// consulta
$consulta = "
SELECT id_medida, nombre
FROM medidas
where
estado = 1
order by nombre asc
 ";

// valor seleccionado
if (isset($_POST['idmedida_referencial'])) {
    $value_selected = htmlentities($_POST['idmedida_referencial']);
} else {
    $value_selected = $rsminip->fields['idmedida_referencial'];
}

// parametros
$parametros_array = [
    'nombre_campo' => 'idmedida_referencial',
    'id_campo' => 'idmedida_referencial',

    'nombre_campo_bd' => 'nombre',
    'id_campo_bd' => 'id_medida',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' '.$disabled,
    'autosel_1registro' => 'N'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
    </div>
</div>


<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cant Referencial </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="cantidad_referencial" id="cantidad_referencial" value="<?php  if (isset($_POST['cantidad_referencial'])) {
        echo floatval($_POST['cantidad_referencial']);
    } else {
        echo floatval($rsminip->fields['cantidad_referencial']);
    }?>" placeholder="cantidad referencial" class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>
<?php
$cr = floatval($rsinsumo->fields['costo_referencial']);
$pmin = floatval($rsminip->fields['precio_min']);
$pmax = floatval($rsminip->fields['precio_max']);
if ($claseau == 'P') {
    if ($cr > 0) {
        $pmin = $cr;
        $val = floatval($porc_aumento_costo / 100);
        $pmax = ($cr * $val) + $cr;

    }
}
if ($claseau == 'C') {
    if ($cr > 0) {
        $pmin = $cr;
        $pmax = $cr * $cantidad_veces_pcosto;

    }
}




?>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio Abierto </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <?php
    // valor seleccionado
    if (isset($_POST['precio_abierto'])) {
        $value_selected = htmlentities($_POST['precio_abierto']);
    } else {
        if ($tipo_precio_defecto == 'A') {
            $value_selected = 'S';
        } else {
            $value_selected = $rsminip->fields['precio_abierto'];

        }
    }
// opciones
$opciones = [
    'SI' => 'S',
    'NO' => 'N'
];
// parametros
$parametros_array = [
    'nombre_campo' => 'precio_abierto',
    'id_campo' => 'precio_abierto',

    'value_selected' => $value_selected,

    'pricampo_name' => 'Seleccionar...',
    'pricampo_value' => '',
    'style_input' => 'class="form-control"',
    'acciones' => ' required="required" '.$disabled,
    'autosel_1registro' => 'S',
    'opciones' => $opciones

];

// construye campo
echo campo_select_sinbd($parametros_array);  ?>                  
    </div>
</div>


<?php if ($usa_costo_referencial == 'S') { ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Costo Referencial </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="costo_referencial" id="costo_referencial" value="<?php  if (isset($_POST['costo_referencial'])) {
        echo floatval($_POST['costo_referencial']);
    } else {
        echo floatval($rsinsumo->fields['costo_referencial']);
    }?>" placeholder="Costo Referencial" onkeyup="calcularcosto();" class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>
<div class="clearfix"></div>
<?php } ?>




<div class="clearfix"></div>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio abierto min </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="precio_min" id="precio_min" value="<?php  if (isset($_POST['precio_min'])) {
        echo floatval($_POST['precio_min']);
    } else {
        echo floatval($pmin);
    }?>" placeholder="Precio min" class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Precio abierto max </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="precio_max" id="precio_max" value="<?php  if (isset($_POST['precio_max'])) {
        echo floatval($_POST['precio_max']);
    } else {
        echo floatval($pmax);
    }?>" placeholder="Precio max"  class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>


<?php
if ($rsprefcaj->fields['usa_recargo_precio_costo'] == 'S') {
    ?>
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">% Recargo Precio basado en costo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="recargo_auto_costo" id="recargo_auto_costo" value="<?php  if (isset($_POST['recargo_auto_costo'])) {
        echo floatval($_POST['recargo_auto_costo']);
    } else {
        echo floatval($rsminip->fields['recargo_auto_costo']);
    }?>" placeholder="% Recargo Precio basado en costo" class="form-control" <?php echo $disabled; ?> />                    
    </div>
</div>    
<?php } ?>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Cod Externo </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
    <input type="text" name="idprodexterno" id="idprodexterno" value="<?php  if (isset($_POST['idprodexterno'])) {
        echo antixss($_POST['idprodexterno']);
    } else {
        echo antixss($rsminip->fields['idprodexterno']);
    }?>" placeholder="Cod Externo" class="form-control" <?php echo $disabled; ?>  />                    
    </div>
</div>
    
<div class="clearfix"></div>

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Tratamiento Reportes *</label>
    <div class="col-md-9 col-sm-9 col-xs-12">

   <div class="clearfix"></div> 
<?php if ($rsminip->fields['excluye_reporteventa'] == 1) { ?>
<a href="gest_editar_productos_rep.php?id=<?php echo intval($_GET['id']); ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Excluido</a>

<?php } else {  ?>
<a href="gest_editar_productos_rep.php?id=<?php echo intval($_GET['id']); ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Muestra</a>

<?php } ?>

    </div>
</div>
    
    

<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Comandas Asignadas </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
          <a href="impresoras_asignar_suc.php?id=<?php echo $rsminip->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Comandas por Sucursal</a>        
    </div>
</div>
    
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Depositos por Sucursal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
          <a href="productos_depo_suc.php?id=<?php echo $rsminip->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Depositos por Sucursal</a>        
    </div>
</div>
    
<div class="col-md-6 col-sm-6 form-group">
    <label class="control-label col-md-3 col-sm-3 col-xs-12">Depositos por Terminal </label>
    <div class="col-md-9 col-sm-9 col-xs-12">
          <a href="productos_depo_term.php?id=<?php echo $rsminip->fields['idprod_serial']; ?>" class="btn btn-sm btn-default" title="Editar" data-toggle="tooltip" data-placement="right"  data-original-title="Editar"><span class="fa fa-edit"></span> Depositos por Terminal</a>        
    </div>
</div>


<div class="clearfix"></div>
<br />
<?php if ($_GET['editado'] != 'ok') { ?>
    <div class="form-group">
        <div class="col-md-3 col-sm-3 col-xs-12 col-md-offset-5">
       <button type="submit" class="btn btn-success" ><span class="fa fa-check-square-o"></span> Registrar Cambios</button>
       <button type="button" class="btn btn-primary" onMouseUp="document.location.href='productos.php'"><span class="fa fa-ban"></span> Cancelar</button>
        </div>
    </div>

  <input <?php echo $disabled; ?> type="hidden" name="MM_insert" value="form1" />
  <input <?php echo $disabled; ?> type="hidden" name="form_control" value="<?php echo htmlentities($_SESSION['form_control']); ?>">
<?php } ?>
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
            <div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-hidden="true" id="dialogobox">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">

                        <div class="modal-header">
                          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span>
                          </button>
                          <h4 class="modal-title" id="myModalLabel">Titulo</h4>
                        </div>
                        <div class="modal-body" id="modal_cuerpo">
                        ...
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                          
                        </div>

                      </div>
                    </div>
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
<?php if ($usa_costo_referencial == 'S') { ?>
<script>
function calcularcosto(){
    
    var valor=parseInt($("#costo_referencial").val());
    var pmin=valor;
    var porce=0;
    var porce1=0;
    <?php if ($claseau == 'P') { ?>
        var vp=<?php echo $val ?>;
        porce1=(valor * vp);
        porce=parseInt(porce1)+parseInt(valor);
    <?php } ?>
    <?php if ($claseau == 'C') { ?>
        var vp=<?php echo $cantidad_veces_pcosto ?>;
        porce1=(valor * vp);
        porce=parseInt(porce1);
    <?php } ?>    
        
        
        $("#p1").val(porce);
        var pmax=porce;
        //colocamos el costo en min y el max este tambien
        
        //ahora el 40 %
        $("#p1").val((valor*0.4)+valor);
        
        
        $("#precio_min").val(pmin);
        $("#precio_max").val(pmax);

}
</script>
<?php } ?>
  </body>
</html>
