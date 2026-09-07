<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeepLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeepLinkResolveController extends Controller
{
    public function __construct(protected DeepLinkService $deepLinks)
    {
    }

    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:article,podcast',
            'id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'not_found',
                'type' => null,
                'id' => null,
                'title' => null,
                'user_type' => null,
                'canonical_url' => null,
                'custom_scheme_url' => null,
                'message' => 'Invalid type or id.',
            ], 422);
        }
        $validated = $validator->validated();

        $viewer = null;
        try {
            $viewer = $request->user('api');
        } catch (\Throwable $e) {
            $viewer = null;
        }

        $resolved = $this->deepLinks->resolve(
            $validated['type'],
            $validated['id'],
            $viewer,
            true
        );

        $body = [
            'status' => $resolved['status'],
            'type' => $resolved['type'],
            'id' => $resolved['id'],
            'title' => $resolved['title'],
            'user_type' => $resolved['user_type'],
            'canonical_url' => $resolved['canonical_url'],
            'custom_scheme_url' => $resolved['custom_scheme_url'],
        ];

        if ($resolved['status'] === DeepLinkService::STATUS_OK || $resolved['status'] === DeepLinkService::STATUS_SUBSCRIPTION_REQUIRED) {
            $body['teaser'] = $resolved['teaser'];
            $body['banner'] = $resolved['banner'];
        }

        return response()->json($body, $resolved['http_status']);
    }
}
