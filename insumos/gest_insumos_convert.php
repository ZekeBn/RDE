<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../categorias/preferencias_categorias.php");
$insertado = 'N';
$idtipoiva_venta_pred = $rsco->fields['idtipoiva_venta_pred'];
$idtipoiva_compra_pred = $rsco->fields['idtipoiva_compra_pred'];

$idinsumo = intval($_GET['id']);
if ($idinsumo == 0) {
    header("location: gest_insumos.php");
    exit;
}
$consulta = "
select insumos_lista.*, categorias.margen_seguridad as margen_seguridad_categoria,
sub_categorias.margen_seguridad as margen_seguridad_sub_categorias,
sub_categorias_secundaria.margen_seguridad as margen_seguridad_sub_categorias_secundaria
from insumos_lista 
LEFT  JOIN categorias on categorias.id_categoria = insumos_lista.idcategoria
LEFT  JOIN sub_categorias on sub_categorias.idsubcate = insumos_lista.idsubcate
LEFT  JOIN sub_categorias_secundaria on sub_categorias_secundaria.idsubcate_sec = insumos_lista.idsubcate_sec
where insumos_lista.estado = 'A' 
and insumos_lista.idinsumo = $idinsumo
limit 1
";
$rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idinsumo = intval($rsins->fields['idinsumo']);
$costo = floatval($rsins->fields['costo']);
$idtipoiva_compra = intval($rsins->fields['idtipoiva']);
$cod_bar = ($rsins->fields['bar_code']);
$idcategoria = intval($rsins->fields['idcategoria']);
$idsubcate = intval($rsins->fields['idsubcate']);
$idsubcate_sec = intval($rsins->fields['idsubcate_sec']);
$margen_categoria = floatval($rsins->fields['margen_seguridad_categoria']);
$margen_sub_categoria = floatval($rsins->fields['margen_seguridad_sub_categorias']);
$margen_sub_categoria_secundaria = floatval($rsins->fields['margen_seguridad_sub_categorias_secundaria']);

$margen_seguridad = null;
$errores_margenes = "";
if ($idcategoria > 0) {
    if ($margen_categoria == 0) {
        $errores_margenes .= "- El margen correspondiente a la Categoria no fue cargado.<br>";
    }
    $margen_seguridad = $margen_categoria;
}
if ($idsubcate > 0) {
    if ($margen_sub_categoria == 0) {
        $errores_margenes .= "- El margen correspondiente a la Sub Categoria no fue cargado.<br>";
    }
    $margen_seguridad = $margen_sub_categoria;
}
if ($idsubcate_sec > 0) {
    if ($margen_sub_categoria_secundaria == 0) {
        $errores_margenes .= "- El margen correspondiente a la Sub Categoria Secundaria no fue cargado.<br>";
    }
    $margen_seguridad = $margen_sub_categoria_secundaria;
}
if ($margen_seguridad != 0 && $costo != 0) {
    $costo = $costo + ($costo * ($margen_seguridad / 100));

} else {
    $costo = null;
}


if ($idinsumo == 0) {
    header("location: gest_insumos.php");
    exit;
}

// datos globales
$idmedida = intval($rsins->fields['idmedida']);
$descripcion = $rsins->fields['descripcion'];

// busca si usa receta
$busca = "Select * from preferencias where idempresa=$idempresa";
$rspref = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$usa_receta = trim($rspref->fields['usa_receta']);
$usa_marca = trim($rspref->fields['usa_marca']);
//Limpiar y almacenar
if (isset($_POST['p1']) && trim($_POST['p1']) != '') {
    $facompra = antisqlinyeccion($_POST['fcompra'], 'text');
    $fecompra = antisqlinyeccion($_POST['fecompra'], 'date');
    //$codprod=antisqlinyeccion($_POST['codprod'],'text');
    $nombre = antisqlinyeccion($descripcion, 'text');
    $cantidad = antisqlinyeccion($_POST['cantidad'], 'float');
    $barcode = antisqlinyeccion($_POST['barcode'], 'text');
    $idgrupoinsu = antisqlinyeccion($_POST['idgrupoinsu'], 'int');
    $idmarca = antisqlinyeccion($_POST['idmarca'], 'int');
    $costo = 0;
    $tipoprecio = 0;
    $pventa = 0;
    if (trim($_POST['idgrupoinsu']) == '') {
        $idgrupoinsu = 0;
    }
    $idtipoiva = intval($_POST['idtipoiva']);
    /*if ($tipoiva==1){
        $iva=0;
    }
    if ($tipoiva==2){
        $iva=5;
    }
    if ($tipoiva==3){
        $iva=10;
    }*/
    $pminimo = 0;
    $pmaximo = 0;
    $listaprecio = listaprecios($_POST['listaprecio']);
    $listaprecio = antisqlinyeccion($listaprecio, 'text');
    $p1 = floatval($_POST['p1']);
    $p2 = floatval($_POST['p2']);
    $p3 = floatval($_POST['p3']);

    $d1 = 0;
    $provee = 1;
    $catego = antisqlinyeccion($_POST['categoria'], 'int');
    $subcatego = antisqlinyeccion($_POST['subcatels'], 'int');
    $medida = antisqlinyeccion($idmedida, 'int');
    $combinado_tipoprecio = antisqlinyeccion($_POST['combinado_tipoprecio'], 'int');
    $combinado_maxitem = antisqlinyeccion($_POST['combinado_maxitem'], 'int');
    $combinado_minitem = antisqlinyeccion($_POST['combinado_minitem'], 'int');
    //$combinado=antisqlinyeccion($_POST['combinado'],'text');
    $ubicacion = 0;
    $imagen = 'NULL';
    $keyword = 'NULL';
    $vencimiento = 0;
    $garantia = 0;
    $valido = 'S';
    $errores = '';
    //$tipo=trim(htmlentities($_POST['tipo']));
    $tipo = 'PRODU';
    // conversiones
    if ($tipo == 'PRODU') {
        $combo = antisqlinyeccion('N', 'text');
        $combinado = antisqlinyeccion('N', 'text');
        $idtipoproducto = 1;
        $combinado_tipoprecio = antisqlinyeccion('', 'int');
        $combinado_maxitem = antisqlinyeccion('', 'int');
        $combinado_minitem = antisqlinyeccion('', 'int');
    } elseif ($tipo == 'COMBO') {
        $combo = antisqlinyeccion('S', 'text');
        $combinado = antisqlinyeccion('N', 'text');
        $idtipoproducto = 2;
        $combinado_tipoprecio = antisqlinyeccion('', 'int');
        $combinado_maxitem = antisqlinyeccion('', 'int');
        $combinado_minitem = antisqlinyeccion('', 'int');
    } elseif ($tipo == 'COMBI') {
        $combo = antisqlinyeccion('N', 'text');
        $combinado = antisqlinyeccion('S', 'text');
        $idtipoproducto = 3;
        $combinado_maxitem = antisqlinyeccion('', 'int');
        $combinado_minitem = antisqlinyeccion('', 'int');
        if ($_POST['combinado_tipoprecio'] != 3) {
            $p1 = 0;
        }
        if (intval($_POST['combinado_tipoprecio']) == 0) {
            $errores .= "* Debe indicar el tipo de precio.<br />";
            $valido = 'N';
        }
    } elseif ($tipo == 'COMBIEXT') {
        $combo = antisqlinyeccion('N', 'text');
        $combinado = antisqlinyeccion('N', 'text');
        $idtipoproducto = 4;
        if ($_POST['combinado_tipoprecio'] != 3) {
            $p1 = 0;
        }
        if (intval($_POST['combinado_tipoprecio']) == 0) {
            $errores .= "* Debe indicar el tipo de precio.<br />";
            $valido = 'N';
        }
    } else {
        $combo = antisqlinyeccion('N', 'text');
        $combinado = antisqlinyeccion('N', 'text');
        $combinado_tipoprecio = antisqlinyeccion('', 'int');
        $combinado_maxitem = antisqlinyeccion('', 'int');
        $combinado_minitem = antisqlinyeccion('', 'int');
    }


    // validaciones
    /*if ($codprod == 'NULL'){
        $errores=$errores."* Debe ingresar el codigo del producto.<br />";
        $valido='N';
    }*/
    /*if ($nombre == 'NULL'){
        $errores=$errores."* Debe ingresar el nombre del producto.<br />";
        $valido='N';
    }*/
    if (intval($idtipoiva) == 0) {
        $errores = $errores."* Debe indicar tipo de iva.<br />";
        $valido = 'N';
    }
    /*if (($provee == 'NULL') or ($provee==0)){
        $errores=$errores."* Debe ingresar el proveedor del producto.<br />";
        $valido='N';
    }*/

    // validar que no existe un producto con el mismo nombre
    $consulta = "
	select * from productos where descripcion = $nombre and idempresa = $idempresa and borrado = 'N'
	";
    $rsexpr = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    // validar que no exista un insumo con el mismo nombre
    $consulta = "
	select * from insumos_lista where descripcion = $nombre and idempresa = $idempresa and estado = 'A'
	";
    //$rsexin=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));

    // si existe producto
    if ($rsexpr->fields['idprod_serial'] > 0) {
        $errores = $errores."* Ya existe un producto con este nombre.<br />";
        $valido = 'N';
    }
    // si existe insumo
    /*if($rsexin->fields['idinsumo'] > 0){
        $errores=$errores."* Ya existe un insumo con este nombre.<br />";
        $valido='N';
    }*/
    // grupo de insumo
    /*if($idgrupoinsu == 0){
        $errores=$errores."* Debe indicar el grupo de insumo.<br />";
        $valido='N';
    }*/
    // codigo de barras
    if (trim($_POST['barcode']) != '') {
        $permite_duplicar_cbar = "N"; // traer de preferencias
        if ($permite_duplicar_cbar == 'N') {
            $consulta = "
			select * from productos where barcode = $barcode and borrado = 'N' limit 1
			";
            $rsbarcode = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            if ($rsbarcode->fields['idprod_serial'] > 0) {
                $errores = $errores."* Ya existe otro producto con el mismo codigo de barras.<br />";
                $valido = 'N';
            }
        }
    }

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
            $valido = "N";
            $errores .= "-El iva compra y venta debe ser el mismo para el tipo de iva venta seleccionado.<br />";
        }
    }




    // conversiones
    $consulta = "SELECT max(idprod_serial) as mayor FROM productos";
    $rsprox = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $proxid = intval($rsprox->fields['mayor']) + 1;
    $codprod = antisqlinyeccion($proxid, "text");
    $codp = $proxid;
    $idproducto = $proxid;

    if ($valido == 'S') {

        $inserta = "
			insert into productos
			(idempresa,idprod,idprod_serial,descripcion,costo_actual,idcategoria,idmedida,ubicacion,
			imagen,controla_vencimiento,controla_garantia,registrado_el,keywords,idproveedor,disponible,
			precio_min,precio_max,lista_precios,precio_venta,registrado_por,idsubcate,facturacompra,fechacompra,idtipoiva,tipoiva,
			p1,p2,p3,desc1,sucursal,combinado,idpantallacocina,idimpresoratk,barcode,combo,idtipoproducto,combinado_tipoprecio,combinado_maxitem,idmarca,combinado_minitem,descuento,idgen)
			values 
			($idempresa,$codprod,$codprod,$nombre,$costo,$catego,$medida,$ubicacion,$imagen,$vencimiento,
			$garantia,current_timestamp,$keyword,$provee,$cantidad,$pminimo,$pmaximo,$listaprecio,$pventa,$idusu,$subcatego,
			$facompra,$fecompra,$idtipoiva,$tipoiva,$p1,$p2,$p3,$d1,$idsucursal,$combinado,0,0,$barcode,$combo,$idtipoproducto,$combinado_tipoprecio,$combinado_maxitem,$idmarca,$combinado_minitem,0,0)";
        $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

        // si no usa receta
        //if($usa_receta == 'N'){
        if ($tipo == 'PRODU') {

            $consulta = "
				update insumos_lista 
				set
				idproducto = $idproducto
				where
				idinsumo = $idinsumo
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            //Ingrediente
            $insertar = "Insert ignore into ingredientes (idinsumo,estado,idempresa) values ($idinsumo,1,$idempresa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            //trae el que acaba de insertar
            $buscar = "Select max(idingrediente) as ingre from ingredientes where idempresa = $idempresa and idinsumo = $idinsumo";
            $rsg = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idingrediente = intval($rsg->fields['ingre']);


            //Receta
            $insertar = "Insert into recetas
				(nombre,estado,creado_por,fecha_creacion,ultimo_cambio,ultimo_cambio_por,idproducto,idempresa)
				values
				($nombre,1,$idusu,'$ahora','$ahora',$idusu,$idproducto,$idempresa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            //trae el que acaba de insertar
            $buscar = "Select max(idreceta) as mayor from recetas ";
            $rsf1 = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
            $idreceta = $rsf1->fields['mayor'];


            //Detalle de la receta
            $insertar = "Insert into recetas_detalles
				(idreceta,idprod,ingrediente,cantidad,sacar,alias,idempresa)
				values
				($idreceta,'$idproducto',$idingrediente,1,'N',$nombre,$idempresa)";
            $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));

            /*

            para detectar problemas con recetas
            SELECT recetas.idreceta, recetas.nombre, recetas.idproducto, (select insumos_lista.idproducto from insumos_lista where insumos_lista.idproducto = recetas.idproducto) as idprodinsu, (select insumos_lista.idinsumo from insumos_lista where insumos_lista.idproducto = recetas.idproducto) as idinsumo, (select recetas_detalles.ingrediente from recetas_detalles where idreceta = recetas.idreceta ) as idingrediente FROM `recetas` where recetas.idproducto not in (select recetas_detalles.idprod from recetas_detalles where recetas_detalles.idreceta = recetas.idreceta) and (select COUNT(*) from recetas_detalles where recetas_detalles.idreceta = recetas.idreceta) = 1 ORDER BY `recetas`.`ultimo_cambio` DESC

            select * from insumos_lista where idproducto not in ( SELECT insumos_lista.idproducto FROM recetas_detalles inner join ingredientes on recetas_detalles.ingrediente = ingredientes.idingrediente inner join insumos_lista on insumos_lista.idinsumo = ingredientes.idinsumo inner join medidas on medidas.id_medida = insumos_lista.idmedida where insumos_lista.idproducto = recetas_detalles.idprod )
            */


        }
        //}


        //De inmediato registramos la tabla de costos
        $inserta = "insert into costo_productos 
			(idempresa,id_producto,registrado_el,precio_costo,idproveedor,cantidad,numfactura)
			values
			($idempresa,$codprod,current_timestamp,$costo,$provee,$cantidad,$facompra)";
        $conexion->Execute($inserta) or die(errorpg($conexion, $inserta));

        if ($vencimiento == 1) {
            for ($i = 1;$i <= $cantidad;$i++) {
                $insertar = "Insert into productos_vencimiento
					(idprod,idempresa,sucursal,factura)
					values
					($codprod,$idempresa,1,$facompra)";
                $conexion->Execute($insertar) or die(errorpg($conexion, $insertar));
            }


        }

        // busca las sucursales que tienen 1 sola impresora
        $consulta = "
			select *
			from 	(
					SELECT idsucursal, idimpresoratk, idempresa, count(*) as total 
					FROM impresoratk 
					where 
					idempresa = $idempresa
					and tipo_impresora = 'COC'
					 group by idsucursal
					 ) as impresoras_suc
			where
			total = 1
			and idempresa = $idempresa
			";
        $rssucimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // recorre las sucursales que tienen 1 sola impresora
        while (!$rssucimp->EOF) {

            // trae la impresora de esa sucursal
            $idimpresoratk = $rssucimp->fields['idimpresoratk'];

            // busca si existe en producto_impresora
            $consulta = "
				SELECT * 
				FROM producto_impresora
				where
				idproducto = $proxid
				and idempresa = $idempresa
				and idimpresora = $idimpresoratk
				";
            $rsprodimp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            // si no existe agrega
            if (intval($rsprodimp->fields['idproducto']) == 0) {

                $consulta = "
					INSERT INTO producto_impresora 
					(idproducto, idimpresora, idempresa) 
					VALUES 
					($proxid, $idimpresoratk, $idempresa);
					";
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }


            $rssucimp->MoveNext();
        }


        // insertamos en sucursales
        $consulta = "
			INSERT IGNORE INTO productos_sucursales (idproducto, idsucursal, idempresa, precio, activo_suc) 
			select idprod_serial as idproducto, sucursales.idsucu as idsucursal, empresas.idempresa as idempresa, p1 as precio, 1 as activo_suc
			from 
			productos, sucursales, empresas 
			where 
			productos.idprod_serial 
			not in 
			(select productos_sucursales.idproducto from productos_sucursales where idempresa = empresas.idempresa and idsucursal = sucursales.idsucu )
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // actualizar lista precios con recargo automatico // ((precio*(lista_precios_venta.recargo_porc/100))+precio)
        $consulta = "
			update productos_listaprecios
			set
			precio = 
				COALESCE(
					(
					select 
					CASE redondeo_direccion
					WHEN 'A' THEN CEIL(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
					WHEN 'B' THEN FLOOR(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
					ELSE
						ROUND(((precio*(lista_precios_venta.recargo_porc/100))+precio)/POW(10,redondeo_ceros))*(POW(10,redondeo_ceros)) 
					END as redondeado
					from productos_sucursales, lista_precios_venta
					where
					productos_sucursales.idproducto = productos_listaprecios.idproducto
					and productos_sucursales.idsucursal = productos_listaprecios.idsucursal
					and lista_precios_venta.idlistaprecio = productos_listaprecios.idlistaprecio
					)
				,0),
			reg_por = $idusu,
			reg_el = '$ahora'
			where
			idlistaprecio in (select idlistaprecio from lista_precios_venta where recargo_porc <> 0)
			and idlistaprecio > 1
			and idproducto = $proxid
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // generamos el producto para todas las franquicias
        $consulta = "
			INSERT INTO productos_franquicias
			(idproducto,idfranquicia) 
			select 
			$idproducto,idfranquicia
			from franquicia
			";
        $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

        // insertamos en la tabla de pantallas
        /*for ($i=0;$i<count($pantallas);$i++){
            if(intval($pantallas[$i]) > 0){
                $idpantalla=intval($pantallas[$i]);
                $consulta="
                INSERT INTO producto_pantalla
                (idproducto, idpantalla, idempresa)
                VALUES
                ($codprod, $idpantalla, $idempresa);
                ";
                $conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
            }
        }*/
        $idproducto = $proxid;
        // busca si el producto es solo conversion
        $consulta = "
			select * from insumos_lista where idproducto = $idproducto
			";
        $rsins = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        // si es solo conversion
        if ($rsins->fields['solo_conversion'] == 1) {
            // desactiva para la venta
            $consulta = "
				update productos_sucursales set activo_suc = 0 where idproducto = $idproducto
				";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
        }


        $insertado = 'S';

        //echo "aca";exit;

        header("location: gest_insumos_edit.php?id=".$idinsumo);
        exit;

    }

}

$buscar = "Select max(idprod_serial) as mayor from productos";
$rsps = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
$maxid = intval($rsps->fields['mayor']) + 1;
//Datos
//Categorias
$buscar = "Select * from categorias where 
estado = 1
and idempresa = $idempresa 
order by nombre ASC";
$rscate = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));





//if (intval($_POST['medida'])== intval($rsmed->fields['id_medida'])) {
?><!DOCTYPE html>
<html lang="en">
  <head>
	<?php require_once("../includes/head_gen.php"); ?>
<script>
	function alerta_modal(titulo,mensaje){
		$('#dialogobox').modal('show');
		$("#myModalLabel").html(titulo);
		$("#modal_cuerpo").html(mensaje);
	}
	function cerrar_pop(){
		$("#dialogobox").modal("hide");
	}
	function recargar_marca(){
		var direccionurl='select_marca.php';
		var parametros = {
		"idcategoria" : 1,
		};
		$.ajax({		  
			data:  parametros,
			url:   direccionurl,
			type:  'post',
			cache: false,
			timeout: 3000,  // I chose 3 secs for kicks: 3000
			crossDomain: true,
			beforeSend: function () {	
						
			},
			success:  function (response, textStatus, xhr) {
				$("#medidatd6").html(response);	
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

	function modal_marcas(){
		var direccionurl='modal_marca.php';	
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
				$("#myModalLabel").html('Agregar Marcas');	
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
	function recargar(idc){
		var parametros='idc='+idc;
		OpenPage('subcate.php',parametros,'POST','subcate','pred');
		
	}
	function verificar(cual){
			if (cual==1){
				//Codigo Produc
				$("#codprod").focus();
     	 		consulta = $("#codprod").val();  
			}
			if (cual==2){
				//CNombre prod
				//$("#nombre").focus();
     	 		consulta = $("#nombre").val();  
			}
      		if (consulta !=''){
				//$("#mensa").delay(200).queue(function(n) {    
			        var parametros = {
							"b" : consulta
					};  
					  $.ajax({
							type: "POST",
							url: "res_verprod.php",
							data: parametros,
							dataType: "html",
							error: function(){
										alert("error petici�n ajax");
							},
							success: function(response){                                                      
								//r=$("#mensa").html(response);
								//alert(response);
								if(response == 'EX_pr'){
									$("#reg").hide();
									$("#nombre").focus();
									$("#nombre").select();
									alertar('ATENCION: Algo salio mal.','Ya existe un producto con el mismo nombre','error','Lo entiendo!');
									
								}
								if(response == 'EX_in'){
									$("#reg").hide();
									$("#nombre").focus();
									$("#nombre").select();
									alertar('ATENCION: Algo salio mal.','Existe un insumo con el mismo nombre','error','Lo entiendo!');
								}
								if(response == ''){
									$("#reg").show();	
								}
								/*if (document.getElementById('ocex')){
									var re=document.getElementById('ocex').value;
									if (re!=''){
										alertar('ATENCION: Algo salio mal.','El nombre ya existe en el sistema','error','Lo entiendo!');	
										//document.getElementById('n1').hidden='hidden';	
										$("#reg").hide();
									} else {
										//document.getElementById('n1').hidden='';
										$("#reg").show();
									}
								} else {
									//document.getElementById('n1').hidden='';
									$("#reg").show();
								}*/
										 
								//n();
							}
					  });
											   
				 //});
				
				
			} else {
				 $("#mensa").html('');
			}
			
	}
	
	
	
	function controlar(){
		
		var errores='';
		//var prod=document.getElementById('codprod').value;
		/*if (prod=='')	{
			errores=errores+'Debe indicar codigo del producto. \n'	;
			
		}*/
		/*if (document.getElementById('nombre').value==' ')	{
			errores=errores+'Debe indicar nombre del producto. \n'	;
			
		}*/
		
		/*if (document.getElementById('medida').value=='0')	{
			errores=errores+'Debe indicar unidad de medida del producto. \n'	;
			
		}*/
		if (document.getElementById('categoria').value=='0')	{
			errores=errores+'Debe indicar categoria principal del producto. \n'	;
			
		}
		if (document.getElementById('subcatels')){
			
			if (document.getElementById('subcatels').value=='0'){
				errores=errores+'Debe indicar sub-categoria del producto. \n'	;
			}	
		} else {
			
			errores=errores+'Debe indicar sub-categoria del producto. \n'	;	
			
		}
		var p1=document.getElementById('p1').value;
		//Precios segun tipo de producto
		//var tipoprod=document.getElementById('tipo').value;
		
		if(tipoprod!='COMBIEXT'){
			if (p1==''){
				errores=errores+'Debe indicar Precio de venta Principal (P1). \n'	;
			}	
		} else {
			//tipo de precio 
			/*if(parseInt(document.getElementById('combinado_tipoprecio').value) ==0  ){
				errores=errores+'Debe indicar tipo de precio cuando es un combinado extendido. \n';
			}
			if(parseInt(document.getElementById('combinado_tipoprecio').value) ==3  && p1=='' ){
				errores=errores+'Debe indicar precio de venta cuando es extendido definido. \n';
			}
			
			if(parseInt(document.getElementById('combinado_minitem').value) < 2 || document.getElementById('combinado_minitem').value == ''){
				errores=errores+'La cantidad minima debe ser mayor a 1 cuando es un combinado extendido. \n';
			}
			var minimo=document.getElementById('combinado_minitem').value;
			if(parseInt(document.getElementById('combinado_maxitem').value) < 2 || document.getElementById('combinado_maxitem').value == ''){
				errores=errores+'La cantidad maxima debe ser mayor a 1 cuando es un combinado extendido. \n';
			}*/
			
			
		}
		
		
		
		
		//Iva
		if (document.getElementById('idtipoiva').value=='0')	{
				errores=errores+'Debe indicar tipo del iva. \n'	;
		}
		
		
		
		if (errores==''){
			//$("#n1").hide();
			$("#reg").hide();
			document.getElementById('productos').submit();
		} else {
			alertar('ATENCION: Algo salio mal.',errores,'error','Lo entiendo!');	
		}
	}
	function tipoprod(tipo){
		if(tipo == 'COMBIEXT'){
			$("#tipoprecio").show();
			$("#combinado_tipoprecio").show();
			
			$("#combinaex_2").show();	
		}else if(tipo == 'COMBI'){
			$("#tipoprecio").show();
			$("#combinado_tipoprecio").show();
			
			$("#combinaex_2").hide();
		}else{
			$("#tipoprecio").hide();
			$("#combinado_tipoprecio").hide();
			$("#combinaex_2").hide();	
		}
	}

</script>
<script>
	function alertar(titulo,error,tipo,boton){
		swal({   title: titulo,   text: error,   type: tipo,   confirmButtonText: boton });
		}
	

	window.onload = function() {
		var selectElement = document.getElementById('subcatels');
            selectElement.removeAttribute('style');
            selectElement.classList.add('form-control');
	};
	
</script>

<script src="../js/sweetalert.min.js"></script>
<link rel="stylesheet" type="text/css" href="../css/sweetalert.css">
<style>
	#productos td,th{
		border:none;
	}
</style>
</head>
<body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <?php require_once("../includes/menu_gen.php"); ?>

        <!-- top navigation -->
       <?php require_once("../includes/menu_top_gen.php"); ?>
        <!-- /top navigation -->

        <!-- page content -->
        <div class="right_col" role="main">
          <div class="">
            <div class="page-title">
            </div>
            <div class="clearfix"></div>
			<?php require_once("../includes/lic_gen.php");?>
            
            <!-- SECCION -->
            <div class="row">
              <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2> Convertir Insumo a Producto</h2>
                    <ul class="nav navbar-right panel_toolbox">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">











		<div class="clear"></div><!-- clear1 -->
		<div class="colcompleto" id="contenedor">
 			<div class="divstd">
			<a href="gest_insumos.php" class="btn btn-sm btn-default"><span class="fa fa-reply"></span> Volver</a>
				<br />
				<span class="resaltaditomenor">
               </span><br />
			</div>
            <div align="center"></div>
        <div align="center" id="errores" class="errorpost">
			<?php echo $errores; ?>
			<?php echo $errores_margenes; ?>
		</div>
		<br  />   
        <div align="center">
         <div id="mensa">
           
           
           
           </div>
       	  	<div class="sombreado4" style="width:900px;">
	<form id="productos" action="gest_insumos_convert.php?id=<?php echo $idinsumo ?>" method="post">  
        <div class="col-md-12 col-sm-12">
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Producto:</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input class="form-control" type="text" name="nombre" id="nombre"   value="<?php echo $rsins->fields['descripcion']; ?>" placeholder="Nombre del producto" onchange="verificar(2)"  required="required" disabled="disabled" />
					<?php /*?><input type="hidden" name="codprod" id="codprod"  title="Ingrese Codigo Identificador."  placeholder="Codigo unico del Prod."  onkeyup="verificar(1)" style="height:40px; width:100%"  readonly="readonly" value="<?php echo $maxid?>" /><?php */ ?>
				</div>
			</div>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Precio Venta:</label>
				<div class="col-md-9 col-sm-9 col-xs-12">
					<input class="form-control" type="text" name="p1" id="p1" size="10" value="<?php echo $_POST['p1'] ? $_POST['p1'] : $costo ?>" required="required"   />
				</div>
			</div>
			<div class="col-md-6 col-sm-6 form-group" id="categoriatd">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">
					
					 Categoria:</strong></label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				
				<select class="form-control" name="categoria" id="categoria" onchange="recargar(this.value)" required="required" >
								<option value="0" selected="selected">Seleccionar</option>
								<?php while (!$rscate->EOF) {?>
								<option value="<?php echo $rscate->fields['id_categoria']?>" <?php if ((intval($_POST['categoria']) == intval($rscate->fields['id_categoria'])) || (isset($idcategoria) && intval($idcategoria) == intval($rscate->fields['id_categoria']))) { ?> selected="selected" <?php } ?>><?php echo trim($rscate->fields['nombre']) ?></option>
								<?php $rscate->MoveNext();
								}?>
							</select>                    
				</div>
			</div>

			<div class="col-md-6 col-sm-6 form-group" id="subcate">
				<label class="control-label col-md-3 col-sm-3 col-xs-12"><strong>Sub Categoria:</strong></label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<?php require_once('subcate.php')?>
				</div>
			</div>

			<?php if ($sub_categoria_secundaria == "S") { ?>
		<div class="col-md-6 col-sm-6 form-group">
			<label class="control-label col-md-3 col-sm-3 col-xs-12">Sub Categoria Secundaria</label>
			<div class="col-md-9 col-sm-9 col-xs-12" id="subcatesecbox">
				<?php
                require_once("subcate_sec_new.php");
			    ?>
			</div>
		</div>
	<?php } ?>

			<?php if ($usa_marca == 'S') {?>
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12"><strong>
				<a href="javascript:void(0);" onClick="modal_marcas();" class="btn btn-sm btn-default" title="Agregar" data-toggle="tooltip" data-placement="right"  data-original-title="Agregar"><span class="fa fa-plus"></span></a>	
					 Marca:</strong></label>
				<div class="col-md-9 col-sm-9 col-xs-12" id="medidatd6">
					<?php require_once('select_marca.php')?>      
				</div>
			</div>
			<?php } ?>
			
			<div class="col-md-6 col-sm-6 form-group">
				<label class="control-label col-md-3 col-sm-3 col-xs-12">Codigo de Barra: </label>
				<div class="col-md-9 col-sm-9 col-xs-12">
				<input name="barcode" type="text" id="barcode" value="<?php echo $cod_bar ? $cod_bar : ""; ?>" placeholder="Utilizar Lector" class="form-control" />
				</div>
			</div>
					
					
			
			
			<div class="col-md-6 col-sm-6 form-group">
					<label class="control-label col-md-3 col-sm-3 col-xs-12"><strong>I.V.A VENTA</strong></label>
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
    $value_selected = $rsins->fields['idtipoiva'];
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
    'acciones' => ' class="form-control" required="required" ',
    'autosel_1registro' => 'S'

];

// construye campo
echo campo_select($consulta, $parametros_array);

?>
					</div>
				</div>
			
		</div>
        
			<tr>
				<td align="center" style="border-bottom:groove; "><br />
				<?php if ($insertado == 'N') {?>
                <div id="reg">
					<a  id="n1" href="javascript:void(0);" onclick="controlar()" class="btn btn-success" style="cursor:pointer"><span class="fa fa-check-square-o"></span>Registrar</a>

                </div>
				<?php } else { ?>
                
<script>

//alertar('Todo listo.','El Registro  ha sido correcto!','success','Aguarde!');	
</script>

				<meta http-equiv="refresh" content="3;URL=gest_insumos_edit.php?id=<?php echo $idinsumo ?>" />
				<?php }?>   <br /> <br /> 
				</td>
			</tr>
				</div>
	</form>
    
</div>

<div id="login-popup" class="white-popup login-popup mfp-hide">
   	 		<?php //require_once('gest_proveedores.php');?>
</div><!-- /.login-popup -->
        </div>       
  		</div> <!-- contenedor -->
  		

                  </div>
                </div>
              </div>
            </div>
            <!-- SECCION --> 


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
            
            
            
            
          </div>
        </div>
        <!-- /page content -->

        <!-- footer content -->
		<?php require_once("../includes/pie_gen.php"); ?>
        <!-- /footer content -->
      </div>
    </div>
<?php require_once("../includes/footer_gen.php"); ?>
  </body>
</html>