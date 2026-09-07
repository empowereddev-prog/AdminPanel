<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;

class WebhookController extends Controller
{
    public function google(Request $request)
    {
        // Google sends base64 encoded message
        $message = json_decode(
            base64_decode($request->message['data'] ?? ''),
            true
        );

        if (!isset($message['subscriptionNotification'])) {
            return response()->json(['status' => true]);
        }

        $notification = $message['subscriptionNotification'];

        $purchaseToken = $notification['purchaseToken'] ?? null;
        $type = $notification['notificationType'] ?? null;

        if (!$purchaseToken) {
            return response()->json(['status' => true]);
        }

        $subscription = Subscription::where('receipt', $purchaseToken)->first();

        if (!$subscription) {
            return response()->json(['status' => true]);
        }

        switch ($type) {
            case 1: // RENEWED
                $subscription->status = 'Successful';
                break;

            case 2: // CANCELED
                $subscription->status = 'cancelled';
                break;

            case 3: // EXPIRED
                $subscription->status = 'expired';
                break;
        }

        $subscription->save();

        return response()->json(['status' => true]);
    }
    public function apple(Request $request)
    {
        $payload = $request->all();

        $type = $payload['notificationType'] ?? null;
        $transactionId = $payload['data']['transactionId'] ?? null;

        if (!$type || !$transactionId) {
            return response()->json(['status' => true]);
        }

        $subscription = Subscription::where('transaction_id', $transactionId)->first();

        if (!$subscription) {
            return response()->json(['status' => true]);
        }

        switch ($type) {
            case 'DID_RENEW':
                $subscription->status = 'Successful';
                break;

            case 'DID_CANCEL':
                $subscription->status = 'cancelled';
                break;

            case 'DID_EXPIRE':
                $subscription->status = 'expired';
                break;

            case 'REFUND':
                $subscription->status = 'revoked';
                break;
        }

        $subscription->save();

        return response()->json(['status' => true]);
    }
}
