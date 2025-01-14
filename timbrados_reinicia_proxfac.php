<?php

require_once("includes/conexion.php");
require_once("includes/funciones.php");
// nombre del modulo al que pertenece este archivo
$modulo = "1";
$submodulo = "224";
require_once("includes/rsusuario.php");

require_once("includes/funciones_timbrado.php");


$idtanda = intval($_POST['idtanda']);
if ($idtanda == 0) {
    echo "No se envio el idtanda!";
    exit;
}

// consulta a la tabla
$consulta = "
select * 
from facturas 
where 
idtanda = $idtanda
limit 1
";
$rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtanda = intval($rs->fields['idtanda']);
$idtimbrado = intval($rs->fields['idtimbrado']);
$idtipodocutimbrado = $rs->fields['idtipodocutimbrado'];
$factura_suc = $rs->fields['sucursal'];
$factura_pexp = $rs->fields['punto_expedicion'];
if ($idtanda == 0) {
    echo "Tanda de Timbrado inexistente!";
    exit;
}

$consulta = "
select * from timbrado where idtimbrado = $idtimbrado and estado = 1
";
$rstimb = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));
$idtimbrado = intval($rstimb->fields['idtimbrado']);
$timbrado = intval($rstimb->fields['timbrado']);
$valido_desde = $rstimb->fields['inicio_vigencia'];
$valido_hasta = $rstimb->fields['fin_vigencia'];

if ($idtimbrado == 0) {
    echo "Timbrado inexistente!";
    exit;
}




if (isset($_POST['MM_update']) && $_POST['MM_update'] == 'form1') {

    // validaciones basicas
    $valido = "S";
    $errores = "";

    $parametros_array = [
        'numfac' => 1,
        'factura_suc' => $factura_suc,
        'factura_pexp' => $factura_pexp,
        'idusu' => $idusu,
        'idtipodocutimbrado' => $idtipodocutimbrado
    ];
    $res = validar_correccion_correlatividad($parametros_array);
    if ($res['valido'] != 'S') {
        $valido = 'N';
        $errores .= $res['errores'];
    }

    $idtanda_excluye = $idtanda;
    $timbradodatos = timbrado_tanda($factura_suc, $factura_pexp, $idempresa, 1, $idtipodocutimbrado, $ahora, $idtanda_excluye);
    $idtandatimbrado = $timbradodatos['idtanda'];
    if (intval($idtandatimbrado) > 0) {
        $valido = 'N';
        $errores .= '- Ya existe una tanda de timbrado vigente para la sucursal y punto de expedicion: '.agregacero(intval($factura_suc), 3).'-'.agregacero(intval($factura_pexp), 3).' si desea reiniciar debe borrar primero el timbrado antiguo.<br />';
    }


    // si todo es correcto actualiza
    if ($valido == "S") {

        // registra la correccion
        registrar_correccion_correlatividad($parametros_array);

        $consulta = "
        select *,
        (select usuario from usuarios where facturas.registrado_por = usuarios.idusu) as registrado_por,
        (select tipo_documento from timbrado_tipodocu where timbrado_tipodocu.idtipodocutimbrado = facturas.idtipodocutimbrado) as tipo_documento,
        
        
        CASE WHEN 
            idtipodocutimbrado = 1
        THEN
            (select max(SUBSTRING(REPLACE(factura,'-',''), 7,7)) ultfactura from ventas where idtandatimbrado = facturas.idtanda) 
        ELSE
            (select max(SUBSTRING(REPLACE(numero,'-',''), 9,7)) ultnota from nota_credito_cabeza where idtandatimbrado = facturas.idtanda) 
        END as ultfactura,
        
        
        facturas.idtimbradotipo,
        (SELECT timbrado_tipo from timbrado_tipo where idtimbradotipo = facturas.idtimbradotipo) as tipoimpreso,
        
        CASE WHEN 
            idtipodocutimbrado = 1
        THEN
            (
                SELECT COALESCE(numfac,0)+1
                FROM lastcomprobantes 
                where 
                idsuc=facturas.sucursal
                and pe=facturas.punto_expedicion
                order by ano desc 
                limit 1 
            )
        ELSE
            (
                SELECT COALESCE(numero_nc,0)+1
                FROM lastcomprobantes 
                where 
                idsuc=facturas.sucursal
                and pe=facturas.punto_expedicion
                order by ano desc 
                limit 1 
            )
        END as prox_factura
        
        
        
        from facturas 
        where 
         estado = 'A' 
         AND idtanda = $idtanda
        ";
        $rs = $conexion->Execute($consulta) or die(errorpg($conexion, $consulta));



        // MUESTRA PROXIMA FACTURA
        echo    formatomoneda($rs->fields['prox_factura']);
        // si no es preimpreso verifica
        if (intval($rs->fields['idtimbradotipo']) > 1) {
            // la ultima factura +1 debe ser igual a la proxima si aun no se uso en otro sistema
            if (intval($rs->fields['ultfactura']) + 1 != intval($rs->fields['prox_factura'])) {
                echo "<br /><strong style=\"color: red;\">CUIDADO, podria estar mal asignado, verificar.</strong>";
            }
            // si es preimpreso
        } else {
            // solo si ya se uso con ese timbrado alguna venta ahi valida
            if (intval($rs->fields['ultfactura']) > 0) {
                if (intval($rs->fields['ultfactura']) + 1 != intval($rs->fields['prox_factura'])) {
                    echo "<br /><strong style=\"color: red;\">CUIDADO, podria estar mal asignado, verificar.</strong>";
                }
            }
        }

        exit;

    } else {
        echo $errores;
    }

}
