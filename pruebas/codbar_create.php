<?php

require_once("../../clases/php-barcode/src/BarcodeBar.php");
require_once("../../clases/php-barcode/src/Barcode.php");
require_once("../../clases/php-barcode/src/Types/TypeInterface.php");
require_once("../../clases/php-barcode/src/Types/TypeCode128.php");
require_once("../../clases/php-barcode/src/BarcodeGenerator.php");
require_once("../../clases/php-barcode/src/BarcodeGeneratorHTML.php");
require_once("../../clases/php-barcode/src/BarcodeGeneratorPNG.php");
$redColor = [255, 0, 0];

$generator = new Picqer\Barcode\BarcodeGeneratorPNG();
echo '<img src="data:image/png;base64,' . base64_encode($generator->getBarcode('081231723897', $generator::TYPE_CODE_128)) . '">';
