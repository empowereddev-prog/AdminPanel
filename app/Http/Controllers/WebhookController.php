<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;

/**
 * Deliberately NOT on the ApiResponse envelope.
 *
 * These two actions are routed under api/ but their peer is Google Play and
 * Apple's server-to-server notification infrastructure, not the shipped mobile
 * app. What those senders read is the status code; wrapping the bodies in the
 * mobile envelope would change a contract the app never sees and the snapshot
 * gate does not cover.
 *
 * @envelope-exempt
 */
class WebhookController extends Controller
{
    /**
     * Neither store's payload is signature-checked here, so anyone who learns a
     * purchase token or transaction id could flip that subscription's status.
     * When WEBHOOK_SHARED_SECRET is set, the store must be configured to call
     * /api/webhooks/{google|apple}?token=<secret> (or send X-Webhook-Secret)
     * and anything else is refused. With no secret configured the endpoints
     * behave as before, so existing in-app-purchase sync keeps working until
     * the secret is rolled out.
     */
    private function rejected(Request $request): bool
    {
        $secret = (string) config('services.webhooks.shared_secret');
        if ($secret === '') {
            return false;
        }

        $given = (string) ($request->query('token') ?? $request->header('X-Webhook-Secret', ''));

        return !hash_equals($secret, $given);
    }

    public function google(Request $request)
    {
        if ($this->rejected($request)) {
            return response()->json(['status' => false], 401);
        }

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
        if ($this->rejected($request)) {
            return response()->json(['status' => false], 401);
        }

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
