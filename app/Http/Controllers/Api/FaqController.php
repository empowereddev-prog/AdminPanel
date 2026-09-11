<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\StaticContent;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\Static_;
use Auth;

class FaqController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            // Default empty arrays in case no condition matches
            $schoolFaq = [];
            $parentFaq = [];
            $childFaq = [];
            $data = [];

            if ($user->school_id) {

                $data = Faq::where(['type' => 'school', 'status' => 'active'])->orderBy('id', 'ASC')->get();
            }

            if ($user->school_id === null && $user->user_type == 'parent') {
                $data = Faq::where(['type' => 'parent', 'status' => 'active'])->orderBy('id', 'ASC')->get();
            }

            if ($user->user_type == 'child') {
                $data = Faq::where(['type' => 'child', 'status' => 'active'])->orderBy('id', 'ASC')->get();
            }

            return ApiResponse::success($data, 'FAQ data fetched successfully.');
        } catch (\Exception $e) {
            return ApiResponse::error('Something went wrong: ' . $e->getMessage(), 200, null, []);
        }
    }

    public function getSupport()
    {
        try {



            $data = StaticContent::where(['slug' => 'help-support-child'])->get();
            return ApiResponse::success($data, 'Support data fetched successfully.', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Something went wrong: ' . $e->getMessage(), 200, null, []);
        }
    }
}
