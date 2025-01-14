<?php

class Proveedor
{
    public $idproveedor;
    public $idempresa;
    public $ruc;
    public $nombre;
    public $fantasia;
    public $direccion;
    public $sucursal;
    public $comentarios;
    public $web;
    public $telefono;
    public $estado;
    public $email;
    public $contacto;
    public $area;
    public $email_conta;
    public $borrable;
    public $diasvence;
    public $dias_entrega;
    public $incrementa;
    public $acuerdo_comercial;
    public $acuerdo_comercial_coment;
    public $archivo_acuerdo_comercial;
    public $acuerdo_comercial_desde;
    public $acuerdo_comercial_hasta;
    public $persona;
    public $idpais;
    public $idmoneda;
    public $agente_retencion;
    public $idtipo_servicio;
    public $idtipo_origen;
    public $idtipocompra;
    public $cuenta_cte_mercaderia;
    public $cuenta_cte_deuda;
    public $registrado_por;
    public $registrado_el;
    public $form_completo;


    public function __construct(
        $idproveedor,
        $idempresa,
        $ruc,
        $nombre,
        $fantasia,
        $direccion,
        $sucursal,
        $comentarios,
        $web,
        $telefono,
        $estado,
        $email,
        $contacto,
        $area,
        $email_conta,
        $borrable,
        $diasvence,
        $dias_entrega,
        $incrementa,
        $acuerdo_comercial,
        $acuerdo_comercial_coment,
        $archivo_acuerdo_comercial,
        $acuerdo_comercial_desde,
        $acuerdo_comercial_hasta,
        $persona,
        $idpais,
        $idmoneda,
        $agente_retencion,
        $idtipo_servicio,
        $idtipo_origen,
        $idtipocompra,
        $cuenta_cte_mercaderia,
        $cuenta_cte_deuda,
        $registrado_por,
        $registrado_el,
        $form_completo
    ) {
        $this->idproveedor = $idproveedor;
        $this->idempresa = $idempresa;
        $this->ruc = $ruc;
        $this->nombre = $nombre;
        $this->fantasia = $fantasia;
        $this->direccion = $direccion;
        $this->sucursal = $sucursal;
        $this->comentarios = $comentarios;
        $this->web = $web;
        $this->telefono = $telefono;
        $this->estado = $estado;
        $this->email = $email;
        $this->contacto = $contacto;
        $this->area = $area;
        $this->email_conta = $email_conta;
        $this->borrable = $borrable;
        $this->diasvence = $diasvence;
        $this->dias_entrega = $dias_entrega;
        $this->incrementa = $incrementa;
        $this->acuerdo_comercial = $acuerdo_comercial;
        $this->acuerdo_comercial_coment = $acuerdo_comercial_coment;
        $this->archivo_acuerdo_comercial = $archivo_acuerdo_comercial;
        $this->acuerdo_comercial_desde = $acuerdo_comercial_desde;
        $this->acuerdo_comercial_hasta = $acuerdo_comercial_hasta;
        $this->persona = $persona;
        $this->idpais = $idpais;
        $this->idmoneda = $idmoneda;
        $this->agente_retencion = $agente_retencion;
        $this->idtipo_servicio = $idtipo_servicio;
        $this->idtipo_origen = $idtipo_origen;
        $this->idtipocompra = $idtipocompra;
        $this->cuenta_cte_mercaderia = $cuenta_cte_mercaderia;
        $this->cuenta_cte_deuda = $cuenta_cte_deuda;
        $this->registrado_por = $registrado_por;
        $this->registrado_el = $registrado_el;
        $this->form_completo = $form_completo;
    }

}
