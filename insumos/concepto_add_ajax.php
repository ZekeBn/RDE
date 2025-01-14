<?php

require_once("../includes/conexion.php");
require_once("../includes/funciones.php");
// Modulo y submodulo respectivamente
$modulo = "1";
$submodulo = "63";
$dirsup = "S";
require_once("../includes/rsusuario.php");
require_once("../insumos/preferencias_insumos_listas.php");
require_once("../categorias/preferencias_categorias.php");

$concepto = antisqlinyeccion($_POST["concepto"], "text");
$registrado_por = $idusu;
$registrado_el = antisqlinyeccion($ahora, "text");

$idempresa = 1;
$idconcepto = select_max_id_suma_uno("cn_conceptos", "idconcepto")["idconcepto"];
$consulta = "
insert into cn_conceptos
(idconcepto, idgrupo, descripcion, estado, registrado_por, registrado_el, borrable, solo_master_fran, permite_carga_manual)
values
($idconcepto, 100000, $concepto, 1, $registrado_por, $registrado_el, 'S', 'N', 'S')
";

$conexion->Execute($consulta) or die(errorpg($conexion, $consulta));

echo json_encode([
    "success" => true,
    "idconcepto" => $idconcepto
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
