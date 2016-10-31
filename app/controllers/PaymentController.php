<?php

use Illuminate\Support\Facades\Input;

class PaymentController extends BaseController
{
    protected $pagosRecurrentes;

    public function __construct()
    {
        $this->pagosRecurrentes = new \PagosRecurrentes();
    }

    public function createRegularPlan()
    {
        $this->pagosRecurrentes->crearPlan();

        return $this->pagosRecurrentes->crearAcuerdoDeFacturacion();
    }

    public function createTrialPlan()
    {
        $this->pagosRecurrentes->crearPlan('trial');

        return $this->pagosRecurrentes->crearAcuerdoDeFacturacion();
    }

    public function SuccessPayment()
    {
        $token = Input::get('token');

        return $this->pagosRecurrentes->ejecutarAcuerdo($token);
    }

    public function canceledPayment()
    {
        $token = Input::get('token');
        return "User Cancelled the Approval with token: ".$token;
    }

    public function cancel()
    {
        $BILLING_AGREEMENT_ID = 'I-HT38K76XPMGJ';

        $this->pagosRecurrentes->cancelar();
    }
}
