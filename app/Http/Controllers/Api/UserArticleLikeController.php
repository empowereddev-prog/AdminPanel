<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\UserArticaleLike;
use Illuminate\Http\Request;

class UserArticleLikeController extends Controller
{
    public function storeLikedArticle(Request $request)
    {
        $userId = auth()->id();
        $articleId = $request->input('article_id');
        $type = $request->input('type');

        if (!$articleId || !in_array($type, ['like', 'dislike', 'favourite'])) {
            return ApiResponse::error('Invalid request.', 400);
        }
        // Handle special cases before toggling
        if ($type === 'favourite') {
            // Sirf purana dislike remove karega
            UserArticaleLike::where('user_id', $userId)
                ->where('article_id', $articleId)
                ->where('type', 'dislike')
                ->delete();
        } elseif ($type === 'dislike') {
            // Remove like & favourite before adding dislike
            UserArticaleLike::where('user_id', $userId)
                ->where('article_id', $articleId)
                ->whereIn('type', ['like', 'favourite'])
                ->delete();
        } elseif ($type === 'like') {
            // Remove dislike before adding like
            UserArticaleLike::where('user_id', $userId)
                ->where('article_id', $articleId)
                ->where('type', 'dislike')
                ->delete();
        }

        // Toggle like/dislike/favourite
        $existing = UserArticaleLike::where('user_id', $userId)
            ->where('article_id', $articleId)
            ->where('type', $type)
            ->first();

        if ($existing) {
            $existing->delete();
            return ApiResponse::success(null, ucfirst($type) . ' removed successfully!');
        }

        UserArticaleLike::create([
            'user_id' => $userId,
            'article_id' => $articleId,
            'type' => $type,
        ]);

        return ApiResponse::success(null, ucfirst($type) . ' added successfully!', 201);
    }
}
