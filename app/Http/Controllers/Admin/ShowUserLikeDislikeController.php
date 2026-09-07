<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionUser;
use App\Models\UserArticaleLike;
use App\Models\UserLikedVideo;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ShowUserLikeDislikeController extends Controller
{
    public function videoUsersIndex(Request $request)
    {
        $videoId = $request->video_id;
        $type = $request->type; // like, dislike, favourite or null for all

        if ($request->ajax()) {
            $users = UserLikedVideo::where('video_id', $videoId)
                ->when($type && $type != 'all', function ($q) use ($type) {
                    $q->where('type', $type);
                })
                ->with('user:id,name,email')->latest()
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->user->name ?? '-',
                        'email' => $item->user->email ?? '-',
                        'type' => ucfirst($item->type ?? '-')
                    ];
                });

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    return ucfirst($row['name']);
                })
                ->addColumn('type', function ($row) {
                    return $row['type'];
                })
                ->make(true);
        }

        return view('admin.knowledge-base.userlist', [
            'video_id' => $videoId,
        ]);
    }

    public function articleUsersIndex(Request $request)
    {
        $articleId = $request->article_id;
        $type = $request->type; // like, dislike, favourite or null for all

        if ($request->ajax()) {
            $users = UserArticaleLike::where('article_id', $articleId)
                ->when($type && $type != 'all', function ($q) use ($type) {
                    $q->where('type', $type);
                })
                ->with('user:id,name,email')
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->user->name ?? '-',
                        'email' => $item->user->email ?? '-',
                        'type' => ucfirst($item->type ?? '-')
                    ];
                });

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    return ucfirst($row['name']);
                })
                ->addColumn('type', function ($row) {
                    return $row['type'];
                })
                ->make(true);
        }

        return view('admin.knowledgeSession.userlist', [
            'article_id' => $articleId,
        ]);
    }
}
