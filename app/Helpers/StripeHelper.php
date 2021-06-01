<?php

namespace App\Helpers;

use Stripe\StripeClient;

class StripeHelper
{
    protected $stripeClient;

    public function __construct()
    {
        $stripeAPIKey = config('stripe.stripeKey');

        $this->stripeClient = new StripeClient($stripeAPIKey);
    }

    public function retrieveSubscription($subscriptionId)
    {
        return $this->stripeClient->subscriptions->retrieve($subscriptionId);
    }

    public function retrieveProduct($stripeProductId)
    {
        return $this->stripeClient->products->retrieve($stripeProductId, []);
    }
}