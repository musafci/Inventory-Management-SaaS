<?php

namespace App\Http\Controllers;

use App\Services\StripeBillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected StripeBillingService $billingService,
    ) {}

    public function handle(Request $request): Response
    {
        try {
            $this->billingService->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (RuntimeException $exception) {
            return response($exception->getMessage(), 400);
        } catch (\UnexpectedValueException $exception) {
            return response('Invalid payload.', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $exception) {
            return response('Invalid signature.', 400);
        }

        return response('OK', 200);
    }
}
