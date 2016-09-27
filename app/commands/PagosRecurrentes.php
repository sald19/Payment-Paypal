<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
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
    private $apiContext;

    private $planCreado;

    public function __construct()
    {
        $config = Config::get('paypal');

        $this->apiContext = new ApiContext(
            new OAuthTokenCredential($config['client_id'], $config['secret'])
        );

        $this->apiContext->setConfig($config['settings']);
    }

    public function crearPlan($type = null)
    {
        $plan = new Plan();

        $planGratis = new Plan();

        $planGratis->setName('T-Shirt Trial');

        $plan->setName('T-Shirt of the Month Club Plan')
            ->setDescription('Template creation.')
            ->setType('INFINITE');

        // # Definicion de pago para la factura.
        $definicionDePago = new PaymentDefinition();

        $definicionDePago->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval('1')
            ->setCycles('0')
            ->setAmount(new Currency(['value' => 60, 'currency' => 'USD']));

        $tiposDePAgos = [$definicionDePago];

        if (!is_null($type)) {
            $definicionDePagoTrial = new PaymentDefinition();
            $definicionDePagoTrial->setName('Regular Payments')
                ->setType('TRIAL')
                ->setFrequency('Month')
                ->setFrequencyInterval('1')
                ->setCycles('1')
                ->setAmount(new Currency(['value' => 1, 'currency' => 'USD']));

            array_push($tiposDePAgos,$definicionDePagoTrial);
        }

        $preferenciaDeComerciante = new MerchantPreferences();
        $baseUrl = 'http://localhost:8000/';

        $preferenciaDeComerciante->setReturnUrl($baseUrl.'paypal/payment-success')
            ->setCancelUrl($baseUrl.'paypal/payment-cancel')
            ->setAutoBillAmount('yes')
            ->setInitialFailAmountAction('CONTINUE')
            ->setMaxFailAttempts('0');

        $plan->setPaymentDefinitions($tiposDePAgos);
        $plan->setMerchantPreferences($preferenciaDeComerciante);

        try {
            $planCreado = $plan->create($this->apiContext);
        } catch (Exception $x) {
            echo $x->getMessage();
            exit(1);
        }

        $this->planCreado = $this->actualizarPlan($planCreado);

        return $this;
    }

    public function actualizarPlan($planCreado)
    {
        if (!$planCreado->id) {
            return;
        }

        try {
            $value = new PayPalModel('{"state":"ACTIVE"}');

            $patch = new Patch();
            $patch->setOp('replace')
                ->setPath('/')
                ->setValue($value);

            $patchRequest = new PatchRequest();
            $patchRequest->addPatch($patch);

            $planCreado->update($patchRequest, $this->apiContext);

            $planActualizado = Plan::get($planCreado->getId(), $this->apiContext);
        } catch (Exception $ex) {
            dd($ex->getMessage());
        }

        return $planActualizado;
    }

    public function crearAcuerdoDeFacturacion()
    {
        $fecha = Carbon::now()->addMinute(5)->toIso8601String();
        $plan = $this->getPlanCreado();

        if ($plan->state == 'ACTIVE') {
            $agreement = new Agreement();

            $agreement->setName('T-Shirt of the Month Club Agreement')
                ->setDescription('Agreement for T-Shirt of the Month Club Plan')
                ->setStartDate($fecha);

            $setPlan = new Plan();
            $setPlan = $setPlan->setId($plan->getId());
            $agreement->setPlan($setPlan);

            $payer = new Payer();
            $payer->setPaymentMethod('paypal');

            $agreement->setPayer($payer);

            try {
                $agreement = $agreement->create($this->apiContext);
            } catch (Exception $ex) {
                dd('Created Billing Agreement.', $ex->getMessage());
            }

            return Redirect::to($agreement->getApprovalLink());
        }
    }

    public function ejecutarAcuerdo($token)
    {
        $agreement = new Agreement();

        try {
            // ## Execute Agreement
            // Execute the agreement by passing in the token
            $agreement->execute($token, $this->apiContext);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }

        $agreement = Agreement::get($agreement->getId(), $this->apiContext);

        return $agreement;
    }

    public function cancelar($id)
    {
        $agreement = Agreement::get($id, $this->apiContext);

        $descriptor = new AgreementStateDescriptor();
        $descriptor->setNote('Suspending the agreement');

        $agreement->cancel($descriptor, $this->apiContext);

        return 'Agreement cancelado';
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
