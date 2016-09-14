<?php

use Illuminate\Support\Facades\Input;
use utilidades\PagosRecurrentes;

class PaymentController extends BaseController
{
    protected $pagosRecurrentes;

    public function __construct()
    {
        $this->pagosRecurrentes = new PagosRecurrentes();
    }

    public function crearPlan()
    {
        $this->pagosRecurrentes->crearPlan();

        return $this->pagosRecurrentes->crearAcuerdoDeFacturacion();
    }

    public function pagoSatifactorio()
    {
        $token = Input::get('token');

        return $this->pagosRecurrentes->ejecutarAcuerdo($token);
    }
}
