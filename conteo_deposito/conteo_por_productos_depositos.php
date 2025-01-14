<?php
require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$dirsup = "S";
$submodulo = "134";
require_once("../includes/rsusuario.php");

$errores = "";
if (isset($_POST['idconteo'])) {
    $idconteo = $_POST['idconteo'];
}
$agregar = intval($_POST['agregar']);
$borrar = intval($_POST['borrar']);
$editar = intval($_POST['editar']);
$idinsumo = intval($_GET['idinsumo']);
if ($idinsumo == 0) {
    $idinsumo = intval($_POST['idinsumo']);
}
if ($agregar == 1) {
    $idinsumo = intval($_POST['idinsumo']);
    $cantidad = floatval($_POST['cantidad']);
    $lote = antisqlinyeccion($_POST['lote'], "text");
    $vencimiento = antisqlinyeccion($_POST['vencimiento'], 'date');
    $iddeposito = intval($_POST['iddeposito']);
    $tipo_almacenamiento = intval($_POST['tipo_almacenamiento']);
    $idmedida = intval($_POST['idmedida']);
    $idconteo = intval($_POST['idconteo']);
    $idalamcto = intval($_POST['idalamcto']);
    $idalm = intval($_POST['idalm']);
    $fila = intval($_POST['fila']);
    $columna = intval($_POST['columna']);
    $idpasillo = intval($_POST['idpasillo']);
    // echo $idinsumo;exit;
    $valido = "S";
    // idalma
    // fila
    // columna
    // estado == 1 o estado == 2  finalizado es 3  anulado es 4


    if ($idconteo == 0) {
        $location = "conteo_por_producto_detalle.php?id=".$iddeposito."&idinsumo=".$idinsumo;
        header("location: $location");
        exit;
    }
    if ($idinsumo == 0) {
        $location = "conteo_stock_detalle.php?id=".$iddeposito;
        header("location: $location");
        exit;
    }

    //verifica si existe el conteo
    $consulta = "
    select *
    from conteo
    where
    estado <> 6
    and (estado = 1 or estado = 2)
    and idconteo = $idconteo
    and afecta_stock = 'N'
    and fecha_final is null
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = intval($rs->fields['iddeposito']);
    $idsucursal = intval($rs->fields['idsucursal']);
    if (intval($rs->fields['idconteo']) == 0) {
        $valido = "N";
        $errores .= "Conteo inexistente o finalizado";
    }

    //verifica si existe en el detalle del conteo
    $consultas = "SELECT conteo_detalles.idconteo, gest_deposito_almcto_grl.nombre as almacenamiento, CONCAT(gest_deposito_almcto.nombre,' ',COALESCE(gest_deposito_almcto.cara, ''))  as tipo_almacenamiento 
    FROM conteo_detalles
    INNER JOIN conteo on conteo.idconteo=conteo_detalles.idconteo
    INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
    INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
    where
    (conteo.estado IN (1, 2))
    and conteo.conteo_consolidado != 1
    and (
        conteo.idconteo_ref is NULL or conteo.idconteo_ref =(
            SELECT conteo.idconteo FROM conteo WHERE conteo.conteo_consolidado = 1 and conteo.estado = 1  
        )
    )
    and conteo.idinsumo = $idinsumo
    and conteo.iddeposito = $iddeposito
    and conteo_detalles.idalm = $idalm
    and conteo.tipo_conteo = 2
    and conteo.idconteo != $idconteo
    ";

    $rs_verificar = $conexion->Execute($consultas) or die(errorpg($conexion, $consultas));
    $idconteo_verificar = intval($rs_verificar->fields['idconteo']);
    $almacenamiento = ($rs_verificar->fields['almacenamiento']);
    $tipo_almacenamiento_verificar = ($rs_verificar->fields['tipo_almacenamiento']);
    $nombre_almacenamiento = "$almacenamiento $tipo_almacenamiento_verificar";
    if ($idconteo_verificar > 0) {
        $valido = "N";
        $errores .= "Ya existe un conteo activo para el almacenamiento $nombre_almacenamiento elija otro tipo Almacenamiento o verifique el conteo id:$idconteo_verificar";
    }

    //verificar si en el detalle de este conteo no existe nadie en ese mismo lugar





    // validaciones basicas

    // recorrer y validar datos
    $totprodenv = 0;
    $totprodenv_ex = 0;
    $idproducto = $idinsumo;
    $cantidad_contada = $cantidad;
    $idprod_select = "";
    if (trim($cantidad) != '' && $idproducto > 0) {
        // busca que exista el insumo
        $idproducto = antisqlinyeccion($idproducto, 'int');
        $buscar = "Select idinsumo as idprod_serial,idproducto as idprod_select, descripcion, estado from insumos_lista where idinsumo=$idproducto";
        //echo $buscar;
        //exit;
        $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        // echo $buscar;exit;
        $idproducto_ex = $rsin->fields['idprod_serial'];
        $idprod_select = $rsin->fields['idprod_select'];
        // echo " el ex ".$idproducto_ex." el select ".$idprod_select;
        $descripcion = antisqlinyeccion($rsin->fields['descripcion'], "text");
        $estado_prod = $rsin->fields['estado'];
        // si el producto esta activo
        if ($estado_prod == 'A') {
            $totprodenv++;

            // si el producto fue borrado
        } else {


        } // if($estado == 1){

    } // if(trim($cantidad) != '' && $idproducto > 0){

    // if($tipo_almacenamiento == 1){
    //   // busca en algun conteo que esta activo si es que no se usa esa posicion
    //   $consulta="SELECT conteo_detalles.unicose, gest_deposito_almcto.tipo_almacenado,
    //              gest_deposito_almcto.cara, gest_deposito_almcto.nombre
    //              FROM conteo_detalles
    //              INNER JOIN conteo ON conteo.idconteo = conteo_detalles.idconteo
    //              INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = conteo_detalles.idalm
    //              where
    //              conteo.estado <> 6
    //              and (conteo.estado = 1 or conteo.estado = 2)
    //              and conteo.afecta_stock = 'N'
    //              and conteo.fecha_final is null
    //              and conteo_detalles.fila = $fila
    //              and conteo_detalles.columna = $columna
    //              and conteo_detalles.idpasillo=0
    //              and conteo_detalles.idalm = $idalm
    //   ";
    //   $rs_articulo_duplicado=$conexion->Execute($consulta) or die(errorpg($conexion,$consulta));
    //   $unicose_conteo_duplicado = intval($rs_articulo_duplicado->fields['unicose']);
    //   $nombre_estante=$rs_articulo_duplicado->fields['nombre'];
    //   $cara_estante=$rs_articulo_duplicado->fields['cara'];
    //   if($unicose_conteo_duplicado > 0){
    //     $valido="N";
    //     $errores.="- Ya existe un conteo Guardado o Pendiente para el artículo en la Fila:$fila, Columna:$columna del Estante $nombre_estante $cara_estante.<br>";
    //   }

    // }

    if ($valido == 'S') {

        if ($idproducto > 0) {
            $whereadd = "";

            if ($lote != "NULL") {
                $whereadd = " and gest_depositos_stock.lote = $lote
                        and gest_depositos_stock.vencimiento = $vencimiento ";
            } else {
                $whereadd = "  and gest_depositos_stock.lote is NULL
                        and gest_depositos_stock.vencimiento is NULL ";
            }

            // stock disponible por lote
            $produc_sucursales_idprod = "";
            if (intval($idprod_select) == 0) {
                $produc_sucursales_idprod = "is NULL";
            } else {
                $produc_sucursales_idprod = " = $idprod_select";
            }
            $consulta = "SELECT 
                    SUM(gest_depositos_stock_almacto.disponible) as disponible, 
                    (
                      select 
                        productos_sucursales.precio 
                      from 
                        productos_sucursales 
                      where 
                        productos_sucursales.idproducto $produc_sucursales_idprod
                        and productos_sucursales.idsucursal = $idsucursal
                    ) as pventa 
                    FROM 
                        gest_depositos_stock_almacto 
                        INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk 
                    where 
                        gest_depositos_stock_almacto.fila = $fila 
                        and gest_depositos_stock_almacto.columna = $columna 
                        $whereadd
                        and gest_depositos_stock_almacto.idpasillo = $idpasillo
                        and gest_depositos_stock.idproducto = $idproducto
                        and gest_depositos_stock_almacto.idalm = $idalm
                        and gest_depositos_stock.iddeposito = $iddeposito 
                        and gest_depositos_stock_almacto.disponible > 0 
                        and gest_depositos_stock_almacto.estado = 1
                    ";
            $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $disponible = floatval($rsdisp->fields['disponible']);
            $pventa = floatval($rsdisp->fields['pventa']);
            $pcosto = 0;
            $cantidad_sistema = $disponible;

            // busca si existe ese producto en detalle para este conteo
            if ($lote != "NULL") {
                $whereadd = " and lote = $lote
                        and vencimiento = $vencimiento ";
            } else {
                $whereadd = "  and lote is NULL
                        and vencimiento is NULL ";
            }

            $consulta = "
                    select * 
                    from conteo_detalles 
                    where 
                    idconteo = $idconteo
                    and idinsumo = $idproducto
                    $whereadd
                    and fila = $fila
                    and columna = $columna
                    and idpasillo = $idpasillo
                    and idconteo in (select idconteo from conteo where idconteo = conteo_detalles.idconteo )
                    ";


            $rsex = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            //calculos
            $venta = floatval($rsdisp->fields['venta']);
            $cantidad_contada = $cantidad;
            $cantidad_teorica = floatval($disponible);
            $cantidad_teorica_cv = $cantidad_teorica + $venta;// venta es cero aca no se vende quizas
            //si se usa el mismo al alterar stock
            $diferencia = $cantidad_contada - $cantidad_teorica;
            $diferencia_cv = $cantidad_contada - $cantidad_teorica_cv;
            $cantidad_venta = "0";
            // if($sumavent == 'S'){
            //     $diferencia=$diferencia_cv;
            //     $cantidad_venta=$venta;
            // }
            $precio_venta = $pventa;
            $precio_costo = $pcosto;
            $diferencia_pv = $diferencia * $precio_venta;
            $diferencia_pc = $diferencia * $precio_costo;
            $unicose = $rsex->fields['unicose'];


            // si no existe inserta

            if (intval($rsex->fields['idinsumo']) == 0) {
                $consulta = "
                        insert into conteo_detalles
                        (idconteo, idinsumo,  cantidad_contada,  cantidad_sistema, cantidad_venta, precio_venta, precio_costo, diferencia, diferencia_pv, diferencia_pc, descripcion, idusu, ubicacion, lote, vencimiento, idpasillo, fila, columna, idalm, idmedida_ref)
                        values
                        ($idconteo, $idproducto,  $cantidad_contada, $cantidad_sistema, $cantidad_venta, $precio_venta, $precio_costo, $diferencia, $diferencia_pv, $diferencia_pc, $descripcion, $idusu, $iddeposito, $lote, $vencimiento, $idpasillo, $fila, $columna, $idalm, $idmedida)
                        ";
                // echo $consulta;exit;
                $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

            }

        } // if(trim($cantidad) != '' && $idproducto > 0){

    }


}
if ($borrar == 1) {
    $unicose = $_POST['unicose'];
    $consulta = "DELETE FROM conteo_detalles WHERE unicose=$unicose";
    $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

}
if ($editar == 1) {
    $idinsumo = intval($_POST['idinsumo']);
    $unicose = intval($_POST['unicose']);
    $fila = intval($_POST['fila']);
    $columna = intval($_POST['columna']);
    $idmedida = intval($_POST['idmedida']);
    $idalamcto = intval($_POST['idalamcto']);
    $idalm = intval($_POST['idalm']);
    $idpasillo = intval($_POST['idpasillo']);
    $idconteo = intval($_POST['idconteo']);
    $tipo_almacenamiento = intval($_POST['tipo_almacenamiento']);
    $cantidad = intval($_POST['cantidad']);
    $lote = antisqlinyeccion(($_POST['lote']), 'text');
    $vencimiento = antisqlinyeccion(($_POST['vencimiento']), 'date');

    //////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////VERIFICACIONES ////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////

    //verifica si existe el conteo
    $consulta = "
    select *
    from conteo
    where
    estado <> 6
    and (estado = 1 or estado = 2)
    and idconteo = $idconteo
    and afecta_stock = 'N'
    and fecha_final is null
    ";
    $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
    $iddeposito = intval($rs->fields['iddeposito']);
    $idsucursal = intval($rs->fields['idsucursal']);
    if (intval($rs->fields['idconteo']) == 0) {
        $errores .= "Conteo inexistente o finalizado";
    }

    // validaciones basicas
    $valido = "S";
    // recorrer y validar datos
    $totprodenv = 0;
    $totprodenv_ex = 0;
    $idproducto = $idinsumo;
    $cantidad_contada = $cantidad;
    if (trim($cantidad) != '' && $idproducto > 0) {
        // busca que exista el insumo
        $idproducto = antisqlinyeccion($idproducto, 'int');
        $buscar = "Select idinsumo as idprod_serial, idproducto as idprod_select, descripcion, estado from insumos_lista where idinsumo=$idproducto";
        //echo $buscar;
        //exit;
        $rsin = $conexion->Execute($buscar) or die(errorpg($conexion, $buscar));
        $idproducto_ex = $rsin->fields['idprod_serial'];
        $idprod_select = $rsin->fields['idprod_select'];
        $descripcion = antisqlinyeccion($rsin->fields['descripcion'], "text");
        $estado_prod = $rsin->fields['estado'];
        // si el producto esta activo
        if ($estado_prod == 'A') {
            $totprodenv++;
            // si el producto fue borrado
        }

    } // if(trim($cantidad) != '' && $idproducto > 0){
    if ($valido == 'S') {

        if ($idproducto > 0) {
            $whereadd = "";
            if ($lote != "NULL") {
                $whereadd .= "  and lote= $lote
                        and vencimiento = $vencimiento ";
            } else {
                $whereadd .= "  and lote is NULL
                        and vencimiento is NULL ";
            }

            // stock disponible por lote
            $produc_sucursales_idprod = "";
            if (intval($idprod_select) == 0) {
                $produc_sucursales_idprod = "is NULL";
            } else {
                $produc_sucursales_idprod = " = $idprod_select";
            }
            $consulta = "SELECT 
                    SUM(gest_depositos_stock_almacto.disponible) as disponible, 
                    (
                      select 
                        productos_sucursales.precio 
                      from 
                        productos_sucursales 
                      where 
                        productos_sucursales.idproducto $produc_sucursales_idprod
                        and productos_sucursales.idsucursal = $idsucursal
                    ) as pventa 
                    FROM 
                        gest_depositos_stock_almacto 
                        INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk 
                    where 
                        gest_depositos_stock_almacto.fila = $fila 
                        and gest_depositos_stock_almacto.columna = $columna 
                        $whereadd
                        and gest_depositos_stock_almacto.idpasillo = $idpasillo
                        and gest_depositos_stock.idproducto = $idproducto
                        and gest_depositos_stock_almacto.idalm = $idalm
                        and gest_depositos_stock.iddeposito = $iddeposito 
                        and gest_depositos_stock_almacto.disponible > 0 
                        and gest_depositos_stock_almacto.estado = 1
                    
                    ";
            ////////////////////////////////////
            $rsdisp = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
            $disponible = floatval($rsdisp->fields['disponible']);
            $pventa = floatval($rsdisp->fields['pventa']);
            $pcosto = 0;
            $cantidad_sistema = $disponible;




            //calculos
            $venta = floatval($rsdisp->fields['venta']);
            $cantidad_contada = $cantidad;
            $cantidad_teorica = floatval($disponible);
            $cantidad_teorica_cv = $cantidad_teorica + $venta;// venta es cero aca no se vende quizas
            //si se usa el mismo al alterar stock
            $diferencia = $cantidad_contada - $cantidad_teorica;
            $diferencia_cv = $cantidad_contada - $cantidad_teorica_cv;
            $cantidad_venta = "0";
            // if($sumavent == 'S'){
            //     $diferencia=$diferencia_cv;
            //     $cantidad_venta=$venta;
            // }
            $precio_venta = $pventa;
            $precio_costo = $pcosto;
            $diferencia_pv = $diferencia * $precio_venta;
            $diferencia_pc = $diferencia * $precio_costo;



            //actualiza
            $consulta = "UPDATE 
                        conteo_detalles
                    set
                        cantidad_contada=$cantidad_contada,
                        cantidad_sistema=$cantidad_sistema,
                        cantidad_venta=$cantidad_venta,
                        precio_venta=$precio_venta, 
                        precio_costo=$precio_costo,
                        diferencia=$diferencia, 
                        diferencia_pv=$diferencia_pv,
                        diferencia_pc=$diferencia_pc,
                        idusu=$idusu,
                        ubicacion=$iddeposito,
                        lote=$lote,
                        vencimiento=$vencimiento,
                        idpasillo=$idpasillo,
                        fila=$fila,
                        columna=$columna,
                        idalm=$idalm,
                        idmedida_ref= $idmedida
                    where
                        unicose=$unicose
                    ";
            $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));





        } // if(trim($cantidad) != '' && $idproducto > 0){



        //} // foreach($_POST as $key => $value){




    }

    ////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////

    // 	$update="UPDATE
    //                 conteo_detalles
    //             set
    //                 idinsumo = $idinsumo,
    //                 fila = $fila,
    //                 columna = $columna,
    //                 idmedida_ref = $idmedida,
    //                 idalm = $idalm,
    //                 idpasillo = $idpasillo,
    //                 cantidad_contada = $cantidad,
    //                 lote = $lote,
    //                 vencimiento = $vencimiento
    //             where
    //                 unicose = $unicose
    //   ";

    //     $conexion->Execute($update) or die(errorpg($conexion,$update));

}


$consulta = "SELECT conteo_detalles.unicose, conteo_detalles.diferencia, conteo_detalles.idconteo, conteo_detalles.idinsumo,conteo_detalles.descripcion,
conteo_detalles.cantidad_contada,conteo_detalles.lote,conteo_detalles.vencimiento,conteo_detalles.idalm,
conteo_detalles.fila,conteo_detalles.columna,
gest_deposito_almcto_grl.nombre  as almacenamiento,
(
    select 
        CONCAT(nombre,' ',COALESCE(cara, '')) 
    from 
        gest_deposito_almcto 
    where 
    gest_deposito_almcto.idalm = conteo_detalles.idalm
) as tipo_almacenamiento,
(
    select 
        nombre
    from 
    gest_almcto_pasillo 
    where 
    gest_almcto_pasillo.idpasillo = conteo_detalles.idpasillo
) as pasillo,
(
    select 
        nombre
    from
        medidas
    where
        medidas.id_medida = conteo_detalles.idmedida_ref
    ) as medida_ref
from conteo_detalles
inner join gest_deposito_almcto on  gest_deposito_almcto.idalm = conteo_detalles.idalm
inner join gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto
where
idconteo = $idconteo
order by 
    fila,columna
 asc
limit 100
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));







///////////////////////////////////
//////visualizar detalles de almacenamientos

$consulta = "SELECT 
SUM(gest_depositos_stock_almacto.disponible) as disponible, 
gest_depositos_stock_almacto.fila, 
gest_depositos_stock_almacto.columna,
gest_depositos_stock.lote,
gest_depositos_stock_almacto.idpasillo, 
gest_depositos_stock_almacto.idalm, 
insumos_lista.descripcion as insumo, 
gest_depositos_stock.lote, 
gest_depositos_stock.vencimiento, 
medidas.nombre as medida_ref, 
gest_deposito_almcto_grl.nombre as almacenamiento, 
CONCAT(
  gest_deposito_almcto.nombre, 
  ' ', 
  COALESCE(gest_deposito_almcto.cara, '')
) as tipo_almacenamiento, 
gest_almcto_pasillo.nombre as pasillo 
from 
gest_depositos_stock_almacto 
LEFT JOIN gest_almcto_pasillo on gest_almcto_pasillo.idpasillo = gest_depositos_stock_almacto.idpasillo 
INNER JOIN gest_deposito_almcto on gest_deposito_almcto.idalm = gest_depositos_stock_almacto.idalm 
INNER JOIN gest_deposito_almcto_grl on gest_deposito_almcto_grl.idalmacto = gest_deposito_almcto.idalmacto 
INNER JOIN gest_depositos_stock ON gest_depositos_stock.idregseriedptostk = gest_depositos_stock_almacto.idregseriedptostk 
INNER JOIN insumos_lista ON insumos_lista.idinsumo = gest_depositos_stock.idproducto 
INNER JOIN medidas on medidas.id_medida = gest_depositos_stock_almacto.idmedida 
where 
gest_depositos_stock_almacto.idalm in (
  SELECT 
    DISTINCT(conteo_detalles.idalm) 
  FROM 
    conteo_detalles 
  WHERE 
    conteo_detalles.idconteo =$idconteo
) 
and gest_depositos_stock.idproducto = $idinsumo
and gest_depositos_stock_almacto.disponible > 0 
and gest_depositos_stock_almacto.estado = 1 

GROUP BY 
    gest_depositos_stock_almacto.idalm,
    gest_depositos_stock_almacto.fila,
    gest_depositos_stock.lote,
    gest_depositos_stock_almacto.columna,
    gest_depositos_stock_almacto.idpasillo
ORDER BY 
    tipo_almacenamiento,
    fila,
    columna
";
// echo $consulta;exit;
$rs_sc_deposito = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));


?>
<script>
  function cerrar_error_guardar(event){
        event.preventDefault();
        $('#boxErroresArticulosGuardar').removeClass('show');
        $('#boxErroresArticulosGuardar').addClass('hide');
    }
</script>
<?php if ($errores != "") { ?>
  <div class="alert alert-danger alert-dismissible fade in " role="alert" id="boxErroresArticulosGuardar">
            <button type="button" class="close" onclick="cerrar_error_guardar(event)" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <strong>Errores:</strong><br /><p id="erroresArticulosModal"><?php echo $errores;?></p>
        </div>
<?php } ?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th></th>
                <th>Producto</th>
                <th>Cantidad(Unidades)</th>
                <th>Diferencia</th>
                <th>Vencimiento</th>
                <th>Almacenamiento</th>
                <th>Almacenado en</th>
                <th>Fila</th>
                <th>Columna</th>
                <th>Pasillo</th>
                <th>Medida_ref</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $datos_mostrados = [];
if ($rs->RecordCount() > 0) {
    while (!$rs->EOF) {
        $unicose_det = $rs->fields['unicose'];
        $idalm = intval($rs->fields['idalm']);
        $fila = intval($rs->fields['fila']);
        $columna = intval($rs->fields['columna']);
        $datos_mostrados[] = [$idalm,$fila,$columna];
        ?>
            <tr>
                <td align="center">
                    <!-- <a href="javascript:void(0);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Detalle"><span class="fa fa-search"></span></a> -->
                    <a href="javascript:void(0);" onclick="editar_articulo(<?php echo $unicose_det ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Editar"><span class="fa fa-edit"></span></a>
                    <a href="javascript:void(0);" onclick="eliminar_articulo(<?php echo $unicose_det ?>);" class="btn btn-sm btn-default" title="" data-toggle="tooltip" data-placement="right" data-original-title="Borrar"><span class="fa fa-trash-o"></span></a>
                </td>
                <td><?php echo antixss($rs->fields['descripcion']); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['cantidad_contada'], 2, 'N'); ?></td>
                <td align="center"><?php echo formatomoneda($rs->fields['diferencia'], 2, 'N'); ?></td>
                <td> Vencimiento: <?php echo $rs->fields['vencimiento'] ? date("d/m/Y", strtotime($rs->fields['vencimiento'])) : "--" ?> <br> Lote: <?php echo ($rs->fields['lote'])   ?> </td>
                <td><?php echo antixss($rs->fields['almacenamiento']); ?></td>
                <td><?php echo antixss($rs->fields['tipo_almacenamiento']); ?></td>
                <td><?php echo antixss($rs->fields['fila']); ?></td>
                <td><?php echo antixss($rs->fields['columna']); ?></td>
                <td><?php echo antixss($rs->fields['pasillo']); ?></td>
                <td><?php echo antixss($rs->fields['medida_ref']); ?></td>
                
            </tr>

            <?php
        $rs->MoveNext();
    }
}
?>
           
        </tbody>
    </table>
</div>
<?php if ($rs_sc_deposito->RecordCount()) { ?>
    <h2>Articulo relacionado con Almacenamientos Seleccionados</h2>
    <p class="alert alert-info">Los productos que se muestran en la tabla a continuación reflejan la selección de los tipos de almacenamiento seleccionados. En caso de que la tabla superior sobrescriba una posición ya ocupada en la tabla inferior, al consolidar el recuento, el producto seleccionado reemplazará al existente en la posición elegida.</p class="alert alert-info">
    <div class="col-md-12" id="conteo_productos_relacionados">
    <div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad(Unidades)</th>
                <th>Diferencia</th>
                <th>Vencimiento</th>
                <th>Almacenamiento</th>
                <th>Almacenado en</th>
                <th>Fila</th>
                <th>Columna</th>
                <th>Pasillo</th>
                <th>Medida_ref</th>
            </tr>
        </thead>
        <tbody>
            <?php
 if ($rs_sc_deposito->RecordCount() > 0) {
     while (!$rs_sc_deposito->EOF) {
         $fila_sc = intval($rs_sc_deposito->fields['fila']);
         $columna_sc = intval($rs_sc_deposito->fields['columna']);
         $idpasillo_sc = intval($rs_sc_deposito->fields['idpasillo']);
         $lote_sc = antixss($rs_sc_deposito->fields['lote']);
         $disponible_sc = intval($rs_sc_deposito->fields['disponible']);
         $insumo_sc = $rs_sc_deposito->fields['insumo'];
         $vencimiento_sc = $rs_sc_deposito->fields['vencimiento'];
         $medida_ref_sc = $rs_sc_deposito->fields['medida_ref'];
         $almacenamiento_sc = $rs_sc_deposito->fields['almacenamiento'];
         $tipo_almacenamiento_sc = $rs_sc_deposito->fields['tipo_almacenamiento'];
         $pasillo_sc = $rs_sc_deposito->fields['pasillo'];
         $idalm_sc = intval($rs_sc_deposito->fields['idalm']);
         $datos_buscado = [$idalm_sc,$fila_sc,$columna_sc];
         // echo json_encode(in_array([1,1,2],$datos_mostrados));

         $almacenamiento_contado = in_array($datos_buscado, $datos_mostrados);
         ?>
            <tr <?php if ($almacenamiento_contado == true) { ?>class="alert alert-success"<?php } else { ?>class="alert alert-danger"<?php } ?> >
                <td ><?php echo $insumo_sc; ?> </td>
                <td align="center" ><?php echo $disponible_sc; ?> </td>
                <td align="center" >0 </td>
                <td > Vencimiento: <?php echo $vencimiento_sc ? date("d/m/Y", strtotime($vencimiento_sc)) : "--" ?> <br> Lote: <?php echo ($lote_sc)   ?>  </td>
                <td ><?php echo $almacenamiento_sc; ?> </td>
                <td ><?php echo $tipo_almacenamiento_sc; ?> </td>
                <td ><?php echo $fila_sc; ?> </td>
                <td ><?php echo $columna_sc; ?> </td>
                <td ><?php echo $pasillo_sc; ?> </td>
                <td ><?php echo $medida_ref_sc; ?> </td>
            </tr>

            <?php
         $rs_sc_deposito->MoveNext();
     }
 }
    ?>
           
        </tbody>
    </table>
</div>
    </div>
<?php } ?>