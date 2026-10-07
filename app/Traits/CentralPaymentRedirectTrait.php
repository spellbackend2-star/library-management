<?php

namespace App\Traits;

use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

trait CentralPaymentRedirectTrait
{
    /**
     * Get configured frontend URL.
     */
    protected function frontendUrl(): string
    {
        return rtrim(
            config('app.frontend_url'),
            '/'
        );
    }

    /**
     * Attach return_url attribute to payment model.
     */
    protected function paymentWithReturnUrl(
        SubscriptionPayment $payment,
        ?string $returnUrl
    ): SubscriptionPayment {
        if ($returnUrl !== null) {
            $payment->setAttribute('return_url', $returnUrl);
        }

        return $payment;
    }

    /**
     * Redirect payment to frontend URL.
     */
    protected function redirectPaymentFrontend(
        SubscriptionPayment $payment,
        string $returnUrl,
        string $message
    ): RedirectResponse {
        $frontendUrl = rtrim($returnUrl, '/');
        $separator = str_contains($frontendUrl, '?') ? '&' : '?';
        $query = http_build_query([
            'payment' => 'success',
            'payment_id' => $payment->id,
            'subscription_id' => $payment->subscription_id,
            'invoice_id' => $payment->invoice_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'message' => $message,
        ]);

        return redirect()->away($frontendUrl . $separator . $query);
    }

    /**
     * Redirect Khalti verification to frontend.
     */
    protected function redirectKhaltiFrontend(
        SubscriptionPayment $payment,
        string $status,
        string $message,
        ?string $returnUrl = null
    ): RedirectResponse {
        if (! empty($returnUrl)) {
            $frontendUrl = rtrim($returnUrl, '/');
        } else {
            $frontendUrl = $this->frontendUrl() . '/payment/subscription';
        }

        $query = http_build_query([
            'payment' => $status,
            'payment_id' => $payment->id,
            'subscription_id' => $payment->subscription_id,
            'invoice_id' => $payment->invoice_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'message' => $message,
        ]);

        $redirectUrl = $frontendUrl . '?' . $query;

        Log::info(
            'CENTRAL KHALTI FRONTEND REDIRECT',
            [
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
                'invoice_id' => $payment->invoice_id,
                'payment_status' => $payment->status,
                'redirect_url' => $redirectUrl,
            ]
        );

        return redirect()->away($redirectUrl);
    }

    /**
     * Redirect eSewa verification to frontend.
     */
    protected function redirectEsewaFrontend(
        SubscriptionPayment $payment,
        string $status,
        string $message,
        ?string $returnUrl = null
    ): RedirectResponse {
        if (! empty($returnUrl)) {
            $frontendUrl = rtrim($returnUrl, '/');
        } else {
            $frontendUrl = $this->frontendUrl() . '/payment/subscription';
        }

        $query = http_build_query([
            'payment' => $status,
            'payment_id' => $payment->id,
            'subscription_id' => $payment->subscription_id,
            'invoice_id' => $payment->invoice_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'message' => $message,
        ]);

        $redirectUrl = $frontendUrl . '?' . $query;

        Log::info(
            'CENTRAL ESEWA FRONTEND REDIRECT',
            [
                'payment_id' => $payment->id,
                'subscription_id' => $payment->subscription_id,
                'invoice_id' => $payment->invoice_id,
                'payment_status' => $payment->status,
                'redirect_url' => $redirectUrl,
            ]
        );

        return redirect()->away($redirectUrl);
    }
}
