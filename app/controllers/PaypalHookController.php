<?php

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;

class PaypalHookController extends BaseController
{
    public function ipn()
    {
        $inputs = Input::all();

        Mail::send('emails.paypal.pago-realizado', $inputs, function($message) {
            $message
                ->subject('Pago Realizado')
                ->to('paypal@sume.tips');
        });

        return Response::make();
    }
}
