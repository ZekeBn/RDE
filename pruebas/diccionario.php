<?php

class Producto
{
    public $disponible;
    public $fila;
    public $columna;
    public $lote;
    public $vencimiento;

    public function __construct($disponible, $fila, $columna, $lote, $vencimiento)
    {
        $this->disponible = $disponible;
        $this->fila = $fila;
        $this->columna = $columna;
        $this->lote = $lote;
        $this->vencimiento = $vencimiento;
    }

    public function mostrarInformacion()
    {
        echo "Disponible: " . $this->disponible . "<br>";
        echo "Fila: " . $this->fila . "<br>";
        echo "Columna: " . $this->columna . "<br>";
        echo "Lote: " . $this->lote . "<br>";
        echo "Vencimiento: " . $this->vencimiento . "<br>";
    }
}

$diccionario = [];

$producto1 = new Producto(true, 'A', 1, 'LOTE-123', '2023-12-31');
$producto2 = new Producto(true, 'A', 2, 'LOTE-124', '2023-12-31');
$producto3 = new Producto(true, 'B', 1, 'LOTE-125', '2023-12-31');

$clave1 = "1 2";
$clave2 = "1 3";

// Agregar los productos al diccionario
// echo json_encode(array_key_exists($clave1, $diccionario));
if (array_key_exists($clave1, $diccionario)) {
    $diccionario[$clave1][] = $producto1;
    $diccionario[$clave1][] = $producto2;
} else {
    $diccionario[$clave1] = [$producto1];
}
if (array_key_exists($clave1, $diccionario)) {
    $diccionario[$clave1][] = $producto1;
    $diccionario[$clave1][] = $producto2;
} else {
    $diccionario[$clave1] = [$producto1, $producto2];
}

if (array_key_exists($clave2, $diccionario)) {
    $diccionario[$clave2][] = $producto3;
} else {
    $diccionario[$clave2] = [$producto3];
}

// Mostrar la información del diccionario
// foreach ($diccionario as $clave => $productos) {
// 	echo $clave . ":<br>";
// 	foreach ($productos as $producto) {
// 		$producto->mostrarInformacion();
// 		echo "<br>";
// 	}
// 	echo "<br>";
// }
foreach ($diccionario["1 2"] as $productos) {

    $productos->mostrarInformacion();
    echo "<br>";
}
echo "<br>";
