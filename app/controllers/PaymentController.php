<?php

use Illuminate\Support\Facades\Input;

class PaymentController extends BaseController
{
    protected $pagosRecurrentes;

    public function __construct()
    {
        $this->pagosRecurrentes = new \PagosRecurrentes();
    }

    public function crearPlaRegular()
    {
        $this->pagosRecurrentes->crearPlan();

        return $this->pagosRecurrentes->crearAcuerdoDeFacturacion();
    }

    public function crearPlanTrial()
    {
        $this->pagosRecurrentes->crearPlan('trial');

        return $this->pagosRecurrentes->crearAcuerdoDeFacturacion();
    }

    public function pagoSatifactorio()
    {
        $token = Input::get('token');

        return $this->pagosRecurrentes->ejecutarAcuerdo($token);
    }

    public function pagoCancelado()
    {
        $token = Input::get('token');
        return "User Cancelled the Approval with token: ".$token;
    }

    public function cancelar()
    {
        $BILLING_AGREEMENT_ID = 'I-HT38K76XPMGJ';

        $this->pagosRecurrentes->cancelar();
    }
}
