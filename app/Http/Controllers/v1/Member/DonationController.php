<?php

namespace App\Http\Controllers\v1\Member;

use App\Http\Controllers\Controller;
use App\Interfaces\TransactionStatusInterface;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Responser\JsonResponser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\Webhook;

class DonationController extends Controller
{

    public function checkout(Request $request)
    {
        // Set your Stripe secret key
        Stripe::setApiKey(env('STRIPE_SK'));

        try {
            // Create a charge: this will charge the user's card
            $checkout_session = \Stripe\Checkout\Session::create([
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $request->currency, //usd,
                            'product_data' => [
                                'name' => $request->donation_type //Tithe, Offering,
                            ],
                            'unit_amount' => $request->amount * 100 //500,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'metadata' => [
                    'donation_type' => $request->donation_type,
                    'name' => $request->name,
                ],
                'success_url' => env('CLIENT_BASE_URL') . 'success',
                'cancel_url' => env('CLIENT_BASE_URL') . 'cancel-donation',
            ]);
            return response()->json(['success' => true, 'checkout_session' => $checkout_session]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET'); // Your webhook secret

        try {
            // Verify the event by checking the signature
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle successful payment event
        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object; // contains the session info

            // Save payment information to the database
            $transaction = new Transaction();
            $transaction->transaction_ref = $session->id;
            $transaction->payment_status = TransactionStatusInterface::SUCCESSFUL;
            $transaction->currency = $session->currency;
            $transaction->payment_method = $session->payment_method_types[0];
            $transaction->amount = $session->amount_total / 100; // amount in cents
            $transaction->currency = $session->currency;
            $transaction->donation_type = $session->metadata->donation_type; // custom metadata if needed
            $transaction->name = $session->metadata->name; // custom metadata if needed
            $transaction->paid_at = now()->format("Y-m-d H:i:s");
            $transaction->save();
        }

        return response()->json(['status' => 'success'], 200);
    }

    public function processDonation(Request $request)
    {
        $validateRequest = $this->validateDonationRequest($request);

        if ($validateRequest->fails()) {
            return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
        }

        try {
            DB::beginTransaction();
            if ($request->status == 'Success') {
                // handle the successful response
                $record = Transaction::create([
                    'name' => $request->name,
                    'amount' => $request->amount, // Amount in cents (e.g., 1000 = $10.00)
                    'currency' => $request->currency, //'usd',
                    'description' => $request->description,
                    'transaction_ref' => $request->transaction_ref,
                    'payment_method' => $request->payment_method,
                    'donation_type' => $request->donation_type,
                    'paid_at' => now()->format("Y-m-d H:i:s"),
                    'payment_status' => TransactionStatusInterface::SUCCESSFUL
                ]);
                DB::commit();
                return response()->json(['status' => 'success', 'message' => 'Donation successful!'], 200);
            } else {
                // handle the failed response
                $record = Transaction::create([
                    'name' => $request->name,
                    'amount' => $request->amount, // Amount in cents (e.g., 1000 = $10.00)
                    'currency' => $request->currency, //'usd',
                    'description' => $request->description,
                    'transaction_ref' => $request->transaction_ref,
                    'payment_method' => $request->payment_method,
                    'donation_type' => $request->donation_type,
                    'paid_at' => now()->format("Y-m-d H:i:s"),
                    'payment_status' => TransactionStatusInterface::DECLINED
                ]);
                DB::commit();
                return response()->json(['status' => 'success', 'message' => 'Donation failed!'], 200);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function success()
    {
        return response()->json(['success' => true, 'success' => 'Payment Successful']);
    }

    public function failed()
    {
        return response()->json(['success' => true, 'failed' => 'Payment failed']);
    }

    private function validateDonationRequest($request)
    {
        $rules = [
            'amount' => 'required|integer',
            'name' => 'required|string',
            'donation_type' => 'required|string',
            'transaction_ref' => 'required|string'
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }
}
