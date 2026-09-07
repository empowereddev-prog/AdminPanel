<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\User;
use App\Notifications\AdminBroadcast;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Broadcast;
use Yajra\Datatables\datatables;
use Validator;
use Auth;
use Carbon\Carbon;
use App\Jobs\SendAdminNotification;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AdminNotification::selectRaw('
                        title,
                        MAX(updated_at) as latest_update,
                        GROUP_CONCAT(user_id) as user_ids, 
                        MAX(id) as id,
                        MAX(message) as message,
                        MAX(is_sent) as is_sent
                    ')
                    ->groupBy('title')
                    ->orderByDesc('latest_update');
            return DataTables::of($query)
                ->addIndexColumn()
                // ->addColumn('user_name', fn($row) => $row->user->name ?? 'N/A')
                ->addColumn('message', function ($row) {
                    return strlen($row->message) > 50 ? substr($row->message, 0, 50) . '...' : $row->message;
                })
                ->addColumn('date_time', fn($row) => Carbon::parse($row->updated_at)->format('d-m-Y h:i A') ?? 'N/A')
                ->addColumn('status', fn($row) => $row->is_sent ? 'Sent' : 'Pending')
                ->addColumn('action', function ($row) {
                    $edit = '<a href="'.route('notifications.edit', $row->id).'" class="btn btn-sm btn-warning">Edit</a>';
                    $delete = '<button class="btn btn-sm btn-danger deleteNotification" data-id="'.$row->id.'">Delete</button>';
                    return $edit . ' ' . $delete;
                })
                ->rawColumns(['action','message','date_time'])
                ->make(true);
        }
    
        return view('admin.notifications.index');
    }

    public function create()
    {
        $users = User::select('id', 'name','user_type')->get();
        return view('admin.notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|min:3|max:200',
            'message' => 'required',
            'user_ids' => 'required|array',
        ],[
            'user_ids.required' => 'The selection of user field is required'
        ]);

        foreach ($request->user_ids as $user_id) {
            AdminNotification::create([
                'user_id' => $user_id,
                'title' => $request->title,
                'message' => $request->message,
            ]);
            SendAdminNotification::dispatch($user_id, $request->title, $request->message,'admin');

        }

        return redirect()->route('notifications.index')->with('success', 'Notifications queued successfully.');
    }

    public function show(AdminNotification $notification)
    {
        return view('admin.notifications.show', compact('notification'));
    }

    public function edit(AdminNotification $notification)
    {
        $users = User::select('id', 'name','user_type')->get();
        $selectedUserIds = AdminNotification::where('title',$notification->title)->pluck('user_id')->toArray();
        $selectedUserType = User::whereIn('id',$selectedUserIds)->pluck('user_type')->toArray();
        return view('admin.notifications.edit', compact('notification', 'users','selectedUserIds','selectedUserType'));
    }

    public function update(Request $request, AdminNotification $notification)
    {
      
        $request->validate([
            'title' => 'required',
            'message' => 'required',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ],[
            'user_ids.required' => 'The selection of user field is required'
        ]);
        AdminNotification::where('title',$notification->title)->delete();

        // Loop through each selected user and create a new notification for them
        foreach ($request->user_ids as $userId) {
            AdminNotification::create([
                'title' => $request->title,
                'message' => $request->message,
                'user_id' => $userId,
            ]);
            SendAdminNotification::dispatch($userId, $request->title, $request->message,'admin');

        }


        return redirect()->route('notifications.index')->with('success', 'Notification updated.');
    }

    public function destroy(AdminNotification $notification)
    {
        $notification->delete();
        return redirect()->route('notifications.index')->with('success', 'Notification deleted.');
    }
}

