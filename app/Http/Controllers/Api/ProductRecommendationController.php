<?php

namespace App\Http\Controllers\Api;

use App\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductRecommendationResource;
use App\Models\ProductRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductRecommendationController extends Controller
{
    public function index()
    {
        $product = ProductRecommendation::where('status', 'active')->orderBy(DB::raw('CAST(priority AS UNSIGNED)'), 'asc')
            ->paginate(10); // sirf paginate karo, get() hata do
    
        $data = ProductRecommendationResource::collection($product);
    
        return ApiResponse::success(
            $data,
            "Get Product data successfully done",
            200,
            [],
            // meta is payload the app pages on, not a v1 alias, so it must
            // survive into v2.
            ['meta' => [
                'current_page' => $product->currentPage(),
                'last_page'    => $product->lastPage(),
                'per_page'     => $product->perPage(),
                'total'        => $product->total(),
            ]]
        );
    }
    
}
