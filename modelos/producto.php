<?php

class Producto
{
    public $disponible;
    public $fila;
    public $columna;
    public $lote;
    public $vencimiento;
    public $insumo;
    public $tipo_almacenamiento;

    public function __construct($insumo, $disponible, $fila, $columna, $lote, $vencimiento, $tipo_almacenamiento = null)
    {
        $this->disponible = $disponible;
        $this->fila = $fila;
        $this->columna = $columna;
        $this->lote = $lote;
        $this->vencimiento = $vencimiento;
        $this->insumo = $insumo;
        $this->tipo_almacenamiento = $tipo_almacenamiento;
    }

    public function mostrarInformacion()
    {
        return "Articulo: " . $this->insumo ."<br>Disponible: " . $this->disponible ."<br>Lote: " . $this->lote ."<br>Vencimiento: "  . $this->vencimiento . "<br>";
    }
}
