<?php

namespace App\Http\Requests\Api;

/**
 * storeChildMood read every field straight off the request, so an empty body
 * reached the insert and failed with an integrity constraint violation - a 500
 * on ordinary bad input.
 */
class StoreChildMoodRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'child_id'  => 'nullable|integer|exists:users,id',
            'mood_id'   => 'required|integer|exists:moods,id',
            'mood_name' => 'required|string|max:255',
            'points'    => 'nullable|numeric|min:0',
            'language'  => 'nullable|string|in:english,chinese',
        ];
    }
}
