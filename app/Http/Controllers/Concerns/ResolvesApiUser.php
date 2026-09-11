<?php

namespace App\Http\Controllers\Concerns;

use App\Support\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Resolves the user an API request is allowed to act on.
 *
 * Most mobile endpoints historically took a user_id / child_id straight from the
 * request body and acted on it without checking it against the bearer token,
 * which let any authenticated caller read and mutate other families' records.
 * These helpers centralise that check.
 */
trait ResolvesApiUser
{
    /**
     * True when the authenticated user may act on $target: it is either
     * themselves, or their own child.
     */
    protected function canActOnUser(?User $target): bool
    {
        $actor = auth()->user();

        if (!$actor || !$target) {
            return false;
        }

        if ((int) $actor->id === (int) $target->id) {
            return true;
        }

        return $actor->user_type === 'parent'
            && (int) $target->parent_id === (int) $actor->id;
    }

    /**
     * Resolve the target user for a request. Falls back to the authenticated
     * user when the key is absent. Returns null when the caller is not
     * authorised for the id they supplied, so callers can reject it.
     */
    protected function resolveTargetUser(Request $request, string $key = 'user_id'): ?User
    {
        $actor = auth()->user();

        if (!$request->filled($key)) {
            return $actor;
        }

        $target = User::find($request->input($key));

        return $this->canActOnUser($target) ? $target : null;
    }

    /**
     * Resolve the target user's id, or null when unauthorised.
     */
    protected function resolveTargetUserId(Request $request, string $key = 'user_id'): ?int
    {
        $target = $this->resolveTargetUser($request, $key);

        return $target?->id;
    }

    /**
     * Standard rejection for an unauthorised target, in the v1 envelope.
     */
    protected function unauthorisedTargetResponse(string $language = 'english')
    {
        return ApiResponse::error(
            $language === 'english'
                ? 'You are not allowed to access this record.'
                : '您无权访问此记录。',
            200,
            null,
            (object) []
        );
    }
}
