<?php

namespace App\Http\Controllers\Api;

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
    
        return response()->json([
            'status'  => true,
            'message' => "Get Product data successfully done",
            'data'    => $data,
            'meta'    => [
                'current_page' => $product->currentPage(),
                'last_page'    => $product->lastPage(),
                'per_page'     => $product->perPage(),
                'total'        => $product->total(),
            ]
        ]);
    }
    
}
