<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermissionUser;
use App\Models\VideoRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class VideoRequestController extends Controller
{
    private $subadmin_menu_id = 39;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data['title'] = "Video Requests";

        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);

        if (!isset($pre) || empty($pre)) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect('dashboard');
        }

        if ($request->ajax()) {
            $query = VideoRequest::query()->orderBy('id', 'DESC');

            if ($request->has('status_filter') && $request->status_filter != '' && $request->status_filter != 'all') {
                $query->where('status', $request->status_filter);
            }

            $videoRequests = $query->get();

            return DataTables::of($videoRequests)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? Carbon::parse($row->created_at)->format('d M Y, h:i A') : 'N/A';
                })
                ->editColumn('status', function ($row) {
                    $statusClasses = [
                        'new'       => 'status-new',
                        'in_review' => 'status-in_review',
                        'completed' => 'status-completed',
                        'rejected'  => 'status-rejected',
                    ];
                    $class = $statusClasses[$row->status] ?? 'status-new';
                    $label = ucfirst(str_replace('_', ' ', $row->status));
                    return '<span class="status-badge ' . $class . '">' . $label . '</span>';
                })
                ->addColumn('action', function ($row) use ($pre) {
                    $btn = '';
                    if (!empty($pre) && $pre->is_modify == 'yes') {
                        $btn .= '<a href="javascript:void(0)" data-id="' . $row->id . '" title="View Details" class="view-request btnn"><i class="mdi mdi-eye" style="font-size: 20px;"></i></a> ';
                        $btn .= '<a href="javascript:void(0)" class="delete btnn" title="Delete" data-id="' . $row->id . '"><i class="mdi mdi-trash-can" style="font-size: 20px;"></i></a>';
                    }
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $data['pre'] = $pre;
        return view('admin.video-requests.index')->with($data);
    }

    /**
     * Display the specified resource for AJAX Modal.
     */
    public function show($id)
    {
        $requestData = VideoRequest::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => [
                'id'            => $requestData->id,
                'user_message'  => $requestData->user_message,
                'username'      => $requestData->username ?? '',
                'email'         => $requestData->email ?? '',
                'status'        => $requestData->status,
                'admin_notes'   => $requestData->admin_notes ?? '',
                'requested_on'  => $requestData->created_at ? Carbon::parse($requestData->created_at)->format('d M Y, h:i A') : 'N/A',
            ]
        ]);
    }

    /**
     * Admin Side: Update Status and Admin Notes (from Modal Form)
     */
    public function update(Request $request, $id)
    {
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (empty($pre) || $pre->is_modify != 'yes') {
            return response()->json(['status' => false, 'message' => 'Unauthorized action'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status'      => 'required|in:new,in_review,completed,rejected',
            'admin_notes' => 'nullable|string|max:1500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $videoRequest = VideoRequest::findOrFail($id);
        $videoRequest->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Video request updated successfully!'
        ]);
    }

    /**
     * Admin Side: Delete Request (AJAX Delete)
     */
    public function destroy($id)
    {
        $data = VideoRequest::findOrFail($id);
        $data->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Video request deleted successfully.'
        ]);
    }

    /**
     * Mobile App API Endpoint
     */
    public function requestVideo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'user_message' => 'required|string|max:1500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = auth('api')->user();

        $videoRequest = VideoRequest::create([
            'username'     => $request->name ?? null,
            'email'        => $request->email ?? null,
            'user_message' => $request->user_message,
            'status'       => 'new',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Video request submitted successfully',
            'data'    => $videoRequest
        ], 200);
    }
}
