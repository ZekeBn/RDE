<?php

class factura
{
    public $idcompra;
    public $idreg;
    public $idinsumo;
    public $producto;
    public $deposito;
    public $concepto;
    public $cantidad;
    public $idmoneda;
    public $costo;
    public $subtotal;
    public $iva;
    public $lote;
    public $vencimiento;
    public $comentarios;
    public $nombre_moneda;
    public $idconcepto;
    public $usa_cot_despacho;
    public $facturacompra;
    public $total;
    public $iva5;
    public $iva10;
    public $ivaml;
    public $gravadoml;
    public $idcot;

    public function __construct($idcompra, $idreg, $idinsumo, $producto, $depositom, $concepto, $idconcepto, $cantidad, $idmoneda, $costo, $subtotal, $iva, $lote, $vencimiento, $comentarios, $nombre_moneda, $usa_cot_despacho, $facturacompra = "", $total = "", $iva5 = "", $iva10 = "", $ivaml = "", $gravadoml = "", $idcot = "")
    {
        $this->idcompra = $idcompra;
        $this->usa_cot_despacho = $usa_cot_despacho;
        $this->idreg = $idreg;
        $this->idinsumo = $idinsumo;
        $this->producto = $producto;
        $this->deposito = $depositom;
        $this->concepto = $concepto;
        $this->idconcepto = $idconcepto;
        $this->cantidad = $cantidad;
        $this->idmoneda = $idmoneda;
        $this->costo = $costo;
        $this->subtotal = $subtotal;
        $this->iva = $iva;
        $this->lote = $lote;
        $this->vencimiento = $vencimiento;
        $this->comentarios = $comentarios;
        $this->nombre_moneda = $nombre_moneda;
        $this->facturacompra = $facturacompra;
        $this->total = $total;
        $this->iva5 = $iva5;
        $this->iva10 = $iva10;
        $this->ivaml = $ivaml;
        $this->gravadoml = $gravadoml;
        $this->idcot = $idcot;
    }

}
