<?php

namespace utilidades;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use PayPal\Api\Agreement;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\Payer;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Common\PayPalModel;
use PayPal\Rest\ApiContext;

class PagosRecurrentes
{
    private  $paypal_config;
    private  $api_context;
    private $planCreado;
    private $agreement;
    public $fechaDeCreacion;

    public function __construct()
    {
        $this->paypal_config = Config::get('paypal');

        $this->api_context = new ApiContext(
            new OAuthTokenCredential($this->paypal_config['client_id'], $this->paypal_config['secret'])
        );

        $this->api_context->setConfig($this->paypal_config['settings']);

        $this->fechaDeCreacion = Carbon::now()->addMinute(5)->toIso8601String();
    }

    public function crearPlan()
    {
        //Crea una instacia de la clase Plan

        $plan = new Plan();

        //Informacion Basica del plan Recurrente

        $plan->setName('T-Shirt of the Month Club Plan')
            ->setDescription('Template creation.')
            ->setType('INFINITE');

        // # Definicion de pago para la factura.
        $definicionDePago = new PaymentDefinition();

        $definicionDePago->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval("1")
            ->setCycles("0")
            ->setAmount(new Currency(array('value' => 60, 'currency' => 'USD')));

        $preferenciaDeComerciante = new MerchantPreferences();
        $baseUrl = 'http://localhost:8000/';

        $preferenciaDeComerciante->setReturnUrl($baseUrl. "paypal/payment-success")
            ->setCancelUrl($baseUrl . "paypal/payment-cancel")
            ->setAutoBillAmount("yes")
            ->setInitialFailAmountAction("CONTINUE")
            ->setMaxFailAttempts("0");

        $plan->setPaymentDefinitions([$definicionDePago]);
        $plan->setMerchantPreferences($preferenciaDeComerciante);

        try {
            $planCreado = $plan->create($this->api_context);
        } catch (Exception $x) {
            echo $x->getMessage();
            exit(1);
        }

        $planActualizado = $this->actualizarPlan($planCreado);

        $this->setPlanCreado($planActualizado);

        return $this; 
    }

    public function actualizarPlan($planCreado)
    {
        if ($planCreado->id) {
            try {
               $patch = new Patch();

               $value = new PayPalModel('{
                "state":"ACTIVE"
            }');

               $patch->setOp('replace')
                   ->setPath('/')
                   ->setValue($value);

               $patchRequest = new PatchRequest();
               $patchRequest->addPatch($patch);

               $planCreado->update($patchRequest, $this->api_context);

               $planActualizado = Plan::get($planCreado->getId(), $this->api_context);

            } catch (\Exception $ex) {
               echo $ex->getMessage();
                exit(1);
            }

            return $planActualizado;
        }
    }

    public function crearAcuerdoDeFacturacion()
    {
        $plan = $this->getPlanCreado();

        if ($plan->state == "ACTIVE") {
            $agreement = new Agreement();

            $agreement->setName('T-Shirt of the Month Club Agreement')
                ->setDescription('Agreement for T-Shirt of the Month Club Plan')
                ->setStartDate($this->fechaDeCreacion);

            $setPlan = new Plan();
            $setPlan = $setPlan->setId($plan->getId());
            $agreement->setPlan($setPlan);

            $payer = new Payer();
            $payer->setPaymentMethod('paypal');

            $agreement->setPayer($payer);

            try {
                $agreement = $agreement->create($this->api_context);
            } catch (Exception $ex) {
                dd("Created Billing Agreement.", $ex->getMessage());
            }

//            $params = array('page_size' => '20','status' => 'active');

//            $planList = Plan::all($params, $this->api_context);

            return Redirect::to($agreement->getApprovalLink());
        }
    }

    public function ejecutarAcuerdo($token)
    {
        $agreement = new Agreement();

        try {
            // ## Execute Agreement
            // Execute the agreement by passing in the token
            $agreement->execute($token, $this->api_context);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }

        $agreement = Agreement::get($agreement->getId(), $this->api_context);

        return $agreement;
    }
    
    /**
     * @param mixed $planCreado
     */
    public function setPlanCreado($planCreado)
    {
        $this->planCreado = $planCreado;
    }

    /**
     * @return mixed
     */
    public function getPlanCreado()
    {
        return $this->planCreado;
    }
}