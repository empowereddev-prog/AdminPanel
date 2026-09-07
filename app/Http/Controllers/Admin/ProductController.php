<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\ProductModel;
use App\Models\Image;

use App\Models\ProductCategory;
use App\Models\ProductDataModel;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {  
        if ($request->ajax()) {
            $productDetails = ProductModel::getproductDetails();

            $dataArray = [];
            foreach ($productDetails as $productDetail) {
                $company_id = ProductDataModel::getCompany($productDetail->company_id);
                $model = ProductDataModel::getModel($productDetail->model_id);
                $brand = ProductDataModel::getBrand($productDetail->brand_id);
                $category = ProductCategory::getCategoryData($productDetail->category_id);

                $dataObject = (object)[
                    'id' => $productDetail->id,
                    'name' => $productDetail->name,
                    'specification' => $productDetail->specification,
                    'description' => $productDetail->description,
                    'overview' => $productDetail->overview,
                    'company_id' => $company_id,
                    'brand_id' => $brand,
                    'category_id' => $category,
                    'model_id' => $model,
                ];

                $dataArray[] = $dataObject;
            }
            $data = $dataArray;
            return DataTables::of($data)->addIndexColumn()
            ->addColumn('action', function ($row) {
                $btn = '';
                $btn .= '<a href="' . url("admin/products/" . $row->id . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil"></i></a>&nbsp;';

                $btn .= '<a href="' . url("admin/products/" . $row->id) . '" class="delete" title="Delete" data-id="' . $row->id . '" style="margin-left:5px;font-size:20px"><span class="mdi mdi-trash-can"></span></a>&nbsp;';

                $btn .= '<a href="' . url("admin/product-images/" . $row->id) . '" class="" title="View Images" data-id="' . $row->id . '" style="margin-left:5px; font-size:20px;"><span class="mdi mdi-image-album"></span></a>&nbsp;';
                $btn .= '<a href="' . url("admin/product-videos/" . $row->id) . '" class="" title="View Video" data-id="' . $row->id . '" style="margin-left:5px; font-size:20px;"><span class="mdi mdi-image-album"></span></a>&nbsp;';
                $btn .= '<a href="' . url("admin/show-product-details/" . $row->id) . '" class="" title="View Detail" data-id="' . $row->id . '" style="margin-left:5px; font-size:20px;"><span class="mdi mdi-book"></span></a>&nbsp;';


                return $btn;
            })
            ->addColumn('name', function ($row) {
            return $row->name;
            })
        
            ->addColumn('company_id', function ($row) {
            return $row->company_id;
            })
            ->addColumn('brand_id', function ($row) {
            return $row->brand_id;
            })
              ->addColumn('category_id', function ($row) {
            return $row->category_id;
            })
            ->addColumn('model_id', function ($row) {
            return $row->model_id;
            })
            ->rawColumns(['action'])
            ->make(true);
        }
        return view('admin.products.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $p_category = ProductCategory::all();
        $p_brand = ProductDataModel::getBrand();
        $p_company = ProductDataModel::getCompany();
        $p_model = ProductDataModel::getModel();
        // dd($p_model);
        return view('admin.products.create' , compact('p_category','p_brand','p_company','p_model'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // Validate the request data
    $validatedData = $request->validate([
        'name' => 'required|min:2|max:100|unique:products',
        'description' => 'required|max:1000',
        'overview' => 'required|min:2|max:1000',
        'specification' => 'required|min:2|max:1000',
        'company_id' => 'required',
        'brand_id' => 'required',
        'category_id' => 'required',
        'model_id' => 'required',
        'product_document' => 'mimes:pdf,doc,docx,xls,xlsx'
    ], 
    [
        'company_id.required' => 'The company name is required.',
        'brand_id.required' => 'The brand name is required.',
        'category_id.required' => 'The category name is required.',
        'model_id.required' => 'The model name is required.',
    ]);

    // Handle file upload
    $uploadFileName = '';
    $originalFileName = '';
    if ($request->hasFile('product_document')) {
        $originalFileName = $request->product_document->getClientOriginalName();
        $uploadFileName = uploadImage($request->file('product_document'), 'product_document', 'product_document-');
    }

    // Merge the uploaded file details into the validated data
    $validatedData['product_document'] = $uploadFileName;
    //$validatedData['original_file_name'] = $originalFileName;

    // Save product details
    ProductModel::create($validatedData);

    // Redirect with success message
    return redirect('admin/products')->with('success', 'Product Category Added Successfully.'); 
}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = ProductModel::getCategoryById($id);

        $p_category = ProductCategory::all();
        $p_brand = ProductDataModel::getBrand();
        $p_company = ProductDataModel::getCompany();
        $p_model = ProductDataModel::getModel();
        // dd($data);
        return view('admin.products.edit', compact('data', 'p_category','p_brand','p_company','p_model'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|min:2|max:100',
            'description' => 'required|max:1000',
            'overview' => 'required|min:2|max:1000',
            'specification' => 'required|min:2|max:1000',
            'company_id' => 'required',
            'brand_id' => 'required',
            'category_id' => 'required',
            'model_id' => 'required',
            'product_document' => 'mimes:pdf,doc,docx,xls,xlsx'
        ], 
        [
            'company_id.required' => 'The company name is required.',
            'brand_id.required' => 'The brand name is required.',
            'category_id.required' => 'The category name is required.',
            'model_id.required' => 'The model name is required.',
        ]);
    
        // Handle file upload
        $uploadFileName = '';
        $originalFileName = '';
        if ($request->hasFile('product_document')) {
            $originalFileName = $request->product_document->getClientOriginalName();
            $uploadFileName = uploadImage($request->file('product_document'), 'product_document', 'product_document-');
        }
    
        // Merge the uploaded file details into the validated data
        $validatedData['product_document'] = $uploadFileName;
    
        // Update product details
        ProductModel::updateProductData($validatedData, $id);
    
        // Redirect with success message
        return redirect('admin/products')->with('success', 'Product Updated Successfully.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductModel::deleteProductData($id);
        return redirect()->back()->with('success', 'Product Data Deleted Successfully.');
    }


    public function getProductData(Request $request)
    {
        $category_id = $request->category_id;
        $product['data'] =  ProductDataModel::getproduct($category_id);
        return response()->json($product);
    }

    public function productImages($id)
    {
        $images = Image::where('section_id','=',$id)->get();
        $product = ProductModel::find($id);
        return view('admin.products.image',compact('id','images'));
    }


    public function productImagesStore(Request $request)
    {
        $rules = [
            'image_name' => 'required|array|max:10',
            'image_name.*' => 'image|mimes:jpeg,png,jpg,gif,svg'
        ];
        $messages = [
            'image_name.required' => 'The image field is required.',
            'image_name.max' => 'You can upload a maximum of 10 images.',
            'image_name.*.image' => 'Each file must be an image.',
            'image_name.*.mimes' => 'Supported image formats are jpeg, png, jpg, gif, and svg.',
        ];

        $validatedData = $request->validate($rules, $messages);
        $product_id = $request->input('product_id');

        if ($request->hasfile('image_name')) {
            foreach ($request->file('image_name') as $file) {
                $originalImageName = $file->getClientOriginalName();
                $img = uploadImage($file, 'upload_product_images', 'image_name-');
                if ($img) {
                    $image = new Image; // Assuming Image is your model
                    $image->image_name = $img;
                    $image->original_image_name = $originalImageName;
                    $image->section_id = $product_id;
                    $image->section = "product";
                    $image->type = "image";
                    $image->save();
                }
            }
        }

        return redirect()->back()->with('success', 'Images uploaded successfully.');
    }

    public function destroySelected(Request $request)
    {
        $selectedImageIds = $request->input('selectedImages');
        $selectedImages = Image::whereIn('id', $selectedImageIds)->get();
        foreach ($selectedImages as $selectedImageInfo) {
            unlinkImage($selectedImageInfo->image_name, null, 'upload_product_images');
        }
        Image::whereIn('id', $selectedImageIds)->delete();
        return redirect()->back()->with('success', 'Selected images deleted successfully');
    }

    public function productAllDetail($id)
    {
        $productDetail = ProductModel::getproductdata($id);
        $company_id = ProductDataModel::getCompany($productDetail->company_id);
        $model = ProductDataModel::getModel($productDetail->model_id);
        $brand = ProductDataModel::getBrand($productDetail->brand_id);
        $category = ProductCategory::getCategoryData($productDetail->category_id);

        $productDetaiLdata = (object)[
            'id' => $productDetail->id,
            'name' => $productDetail->name,
            'specification' => $productDetail->specification,
            'description' => $productDetail->description,
            'overview' => $productDetail->overview,
            'company' => $company_id,
            'brand' => $brand,
            'category' => $category,
            'model' => $model,
        ];
        $allImagesOfProduct = Image::getImagesOfProduct($id);
        return view('admin.products.products-details',compact('productDetaiLdata' , 'allImagesOfProduct'));
    }

    public function productVideos($id)
    {
    
        $videos = Image::where('section_id', '=', $id)->where('type','vedio')->get();
        $product = ProductModel::find($id);
        return view('admin.products.video', compact('id', 'videos'));
    }

    public function productVideoStore(Request $request)
    {
        $rules = [
            'video_name' => 'required',
        ];
        $messages = [
            'video_name.required' => 'The name field is required.',
        ];

        $validatedData = $request->validate($rules, $messages);
        $product_id = $request->input('product_id');
        $video_name = $request->input('video_name');
        
        $video = new Image;
        $video->video_name = $video_name; // Assuming the Image model has a 'name' field
        $video->section_id = $product_id;
        $video->section = "product";
        $video->type = "vedio";
        $video->save();
        
        return redirect()->back()->with('success', 'Video Added successfully.');
    }

    public function deleteProductVideos(Request $request)
    {
        $videoIds = $request->input('video_ids');

        if (!empty($videoIds)) {
            Image::whereIn('id', $videoIds)->delete();
            return redirect()->back()->with('success', 'Selected videos deleted successfully.');
        }

        return redirect()->back()->with('error', 'No videos were selected for deletion.');
    }




}

