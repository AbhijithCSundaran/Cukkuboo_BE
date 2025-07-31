<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

require_once(ROOTPATH . 'vendor/autoload.php');

class StripePayment extends ResourceController
{
    use ResponseTrait;
    private $secretKey = 'sk_test_';//

    public function createCheckoutSession()
    {
       \Stripe\Stripe::setApiKey($this->secretKey);

        $json = $this->request->getJSON();
        $priceId = $json->price_id; // RevenueCat uses Stripe Price ID

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => 'http://cukkuboo.com/payment-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'http://cukkuboo.com/payment-cancel',
            ]);

            return $this->respond([
                'sessionId' => $session->id,
                'checkout_url' => $session->url,
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
