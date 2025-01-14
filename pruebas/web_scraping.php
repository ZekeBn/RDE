<?php

include('../lib/php-simple-html-dom-parser/Src/Sunra/PhpSimple/HtmlDomParser.php');
use Sunra\PhpSimple\HtmlDomParser;

require_once("../includes/funciones.php");


// Establecer las opciones de cURL
$html = file_get_contents("https://www.maxicambios.com.py//share");
$dom = HtmlDomParser::str_get_html($html);

// echo $elemento->children()[0]->children()[0]->children()[0]->children()[0]->children()[0]->src;
// hacer for
// echo strpos(strtolower($dom->find('img')[1]->src),"usd.")?"si":"no";
// echo $dom->find('img')[1]->parentNode();
// echo strtolower($dom->find('img')[1]->parentNode()->find("p")[1]->plaintext)=="compra"?"si":"no";
// echo $dom->find('img')[1]->parentNode()->find("p")[1]->parentNode()->children()[1]->plaintext;
foreach ($dom->find('img') as $imagen) {
    if (strpos(($imagen->src), "/USD.png")) {
        // echo $imagen->parentNode()->find("p")[1]->parentNode()->children()[1]->plaintext;
        foreach ($imagen->parentNode()->find("p") as $parrafo) {
            // echo strtolower($parrafo->plaintext)=="compra"?"s":"n";
            if (strtolower($parrafo->plaintext) == "compra") {
                echo "compra maxicompra<br>".$parrafo->parentNode()->children()[1]->plaintext;
                break;
            }
        }
        break;
    }

}
// Establecer las opciones de cURL
$html = file_get_contents("https://www.set.gov.py/portal/PARAGUAY-SET/InformesPeriodicos");
$dom = HtmlDomParser::str_get_html($html);
foreach ($dom->find('td') as $col) {
    if ($col->class == "UIDolarComVen") {
        echo "<br>compra set<br>";
        echo solonumeros($col->parentNode()->previousSibling()->children()[0]->plaintext);
        break;
    }
}
