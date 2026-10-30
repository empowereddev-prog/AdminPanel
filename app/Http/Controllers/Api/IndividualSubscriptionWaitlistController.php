<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IndividualSubscriptionWaitlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndividualSubscriptionWaitlistController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            // Honeypot: legitimate visitors must leave this field empty.
            'website' => ['prohibited'],
        ]);

        // firstOrCreate also handles a simultaneous unique-key collision in
        // Laravel 11. The database unique index is the source of truth.
        $registration = IndividualSubscriptionWaitlist::firstOrCreate(
            ['email' => $validated['email']],
            ['source' => 'homepage']
        );

        return response()->json([
            'success' => true,
            'status' => $registration->wasRecentlyCreated ? 'registered' : 'already_registered',
            'message' => $registration->wasRecentlyCreated
                ? 'Thank you! Your interest in individual subscriptions has been registered.'
                : 'This email address is already on the waitlist.',
        ], $registration->wasRecentlyCreated ? 201 : 200)
            ->header('Cache-Control', 'no-store');
    }
}
