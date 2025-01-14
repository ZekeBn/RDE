<?php

include('../lib/php-simple-html-dom-parser/Src/Sunra/PhpSimple/HtmlDomParser.php');
use Sunra\PhpSimple\HtmlDomParser;

require_once("../includes/funciones.php");
require_once("../includes/conexion.php");
// generar_cabecera_compra();
// echo "eso fue la funcion";
// echo "hola";


// $arr = "asd";
// $array = explode(",",$arr);


// foreach($array as $value){
//     echo $value."<br>";
// }
// $r = select_limit("preferencias","tipocompra, traslado_nostock","1");
// $r = select_max_id("preferencias","idempresa");
// echo json_encode($r);

// $rstranc = select_max_id("transacciones_compras","numero");
// $idtran=$rstranc["numero"];
// $rstrantmp = select_max_id("tmpcompras","idtran");
// $idtran_tmp=$rstrantmp["idtran"];
// $rstrancom = select_max_id("compras","idtran");
// $idtran_com=$rstrancom["idtran"];
// echo seleccionar_mayor_idtran();
// function h(){

//     return array(
//         'zona' => 1,
//         'ruc' => 2,
//         );
// }
// echo json_encode(h());
// function g(&$f){
//     $f="hola";
// }
// $t="no se";
// g($t);
// echo $t
// function h($params){
// echo json_encode($params);
// $params["s"]="adios";
// }
// $u="hola";
// h(array("s"=>&$u));
// echo $u;
// function h(&$params){
//     $params["s"]="adios";
//     $params["r"]="radios";
// }
// $u=array("s"=>"hola");
// h($u);
// echo json_encode($u);

// $buscar="Select * from tmpcompra";
// $rscuerpo=$conexion->Execute($buscar) or die(errorpg($conexion,$buscar));

// // function mostrar_query(&$params){
// //     while (!$params['s']->EOF){
// //         echo $params['s']->fields['idtran']; // no se puede pasar query
// //         $params['s']->MoveNext();
// //     }
// // }
// // mostrar_query(array("s"=>$rscuerpo))
// echo var_dump($rscuerpo);
// $a=1;
// if  ($a==1) {
//     $t="i";
// }
// // echo $t
// $p = array();
// // $p = array("s"=> 1);
// if (isset($p["s"])){
//     echo "hola";
// }else {
//     echo "adios";
//     }
// echo json_encode(antisqlinyeccion(intval("1"),'int') == 0);
// echo (antisqlinyeccion(intval("4sda"),'int'));
echo antisqlinyeccion('', "date");
