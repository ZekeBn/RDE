<?php

class Perro
{
    public $nombre;
    public $raza;

    public function __construct($nombre, $raza)
    {
        $this->nombre = $nombre;
        $this->raza = $raza;
    }
}

// Ejemplo: Crear un array de objetos Perro
$arrayPerros = [
    new Perro('Fido', 'Labrador'),
    new Perro('', 'Poodle'),
    new Perro('Bobby', 'Husky'),
    new Perro('', 'Bulldog'),
    // ... más objetos Perro ...
];
function borrar_perro(&$a)
{

    unset($a[0]);
}
borrar_perro($arrayPerros);
foreach ($arrayPerros as $perro) {
    echo "{$perro->raza} - ";
}
