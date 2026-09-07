<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaticContentRequest;
use App\Models\StaticContent;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Models\PermissionUser;
use Auth;

class StaticContentController extends Controller
{
    private $content = 5;
    private $subadmin_menu_id = 7;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data['pre'] = $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!isset($pre) || empty($pre)) {
            return redirect('dashboard');
        }
        if ($request->ajax()) {
            // $staticContent = StaticContent::select('slug', \DB::raw('GROUP_CONCAT(content) as content, GROUP_CONCAT(title) as title'))
            //     ->groupBy('slug')
            //     ->get();
            $staticContent = StaticContent::select('slug', \DB::raw('GROUP_CONCAT(content) as content, GROUP_CONCAT(title) as title'))
                ->groupBy('slug')
                ->where('language', 'english')
                ->get();
            if (!empty($pre) && $pre->is_modify == 'yes') {
                return DataTables::of($staticContent)
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = "";
                        $btn .= '<a href="' . url("static-content/" . $row->slug . "/edit") . '" title="Edit" style="margin-left:5px;font-size:20px"><i class="mdi mdi-pencil""></i></a>';
                        return $btn;
                    })
                    ->editColumn('slug', function ($row) {
                        if ($row->slug == 'popup1') {
                            $slug = 'Privacy Copyright & Content Protection Policy';
                        } elseif ($row->slug == 'popup2') {
                            $slug = 'Educational Content Disclaimer';
                        } else {
                            $slug = ucfirst($row->slug);
                        }
                    
                        return $slug;
                    })
                    
                    ->editColumn('content', function ($row) {
                        $row->content = str_replace('../../..', url('/'), $row->content);
                        return "<td style='max-width:500px;'><div>" . $row->content . "</div></td>";
                    })
                    ->rawColumns(['action', 'content'])
                    ->make(true);
            } else {
                return Datatables::of($staticContent)
                    ->addIndexColumn()
                    ->make(true);
            }
        }
        return view('admin.staticContent.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $key)
    {
         
        
        $pre = PermissionUser::checkpermission(Auth::user()->id, $this->subadmin_menu_id);
        if (!empty($pre) && $pre->is_modify == 'yes') {
            $data = StaticContent::where('slug', $key)->get()->keyBy('language');
            // dd($data);
            return view('admin.staticContent.edit', compact('key', 'data'));
        }
        return redirect('dashboard');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StaticContentRequest $request, string $key)
    {
        $languages = ['english'];

        foreach ($languages as $language) {
            StaticContent::updateOrCreate(
                ['slug' => $key, 'language' => $language],
                [
                    'title' => $request->input("title_{$language}"),
                    'content' => $request->input("content_{$language}")
                ]
            );
        }

        // StaticContent::where('slug', $key)->update(['title' => $request->title, 'content' => $request->content]);
        return redirect()->route('static-content.index')->with('success', 'Data updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function upload(Request $request)
    {
        if ($request->hasFile('upload')) {
            $originName = $request->file('upload')->getClientOriginalName();
            $fileName = pathinfo($originName, PATHINFO_FILENAME);
            $extension = $request->file('upload')->getClientOriginalExtension();
            $fileName = $fileName . '_' . time() . '.' . $extension;

            $request->file('upload')->move(public_path('uploads/media'), $fileName);

            $url = asset('uploads/media/' . $fileName);

            return response()->json(['fileName' => $fileName, 'uploaded' => 1, 'url' => $url]);
        }
    }

    public function termCondition(Request $request)
    {
        // dd($request->all());
        // $language = $request->language;
        // $request->session()->put('locale', $language);
        // // App::setLocale($language);
        // $lan = $language == 'en' ? 'english' : 'chinese';

        // $data = StaticContent::where('slug', 'terms-condition')->where('language', 'english')->first();
        // $addresses = Address::where('status','active')->get();
        // $industries = IndustriesBanner::select('id','industrie_name')->where('status','active')->get();
        // return view('admin.staticpages.termsCondition', compact('data','language'));

        $data = StaticContent::where('slug', 'terms-condition')
            ->where('language', 'english')
            ->first();

        return view('admin.staticpages.termsCondition', compact('data'));
    }
    public function helpSupport(Request $request)
    {
        $language = $request->language;
        $request->session()->put('locale', $language);
        // $lan = $language == 'en' ? 'english' : 'chinese';
        $lan =  'english';
        $data = StaticContent::where('slug', 'help-support')->where('language', $lan)->first();
        return view('admin.staticpages.helpSupport', compact('data', 'language'));
    }
    public function meetTheTeam(Request $request)
    {
        $language = $request->language;
        $request->session()->put('locale', $language);
        // $lan = $language == 'en' ? 'english' : 'chinese';
        $lan =  'english';
        $data = StaticContent::where('slug', 'meet-the-team')->where('language', $lan)->first();
        return view('admin.staticpages.meetTheTeam', compact('data', 'language'));
    }
    public function privacyPolicy(Request $request)
    {
        $language = $request->language ?? 'english';
        // $request->session()->put('locale', $language);
        // $lan = $language == 'en' ? 'english' : 'chinese';
        $lan = 'english';
        $data = StaticContent::where('slug', 'privacy-policy')->where('language', $lan)->first();
        return view('admin.staticpages.privacyPolicy', compact('data', 'language'));
    }
    public function aboutUs(Request $request)
    {
        $language = $request->language ?? 'english';
        // $request->session()->put('locale', $language);
        // $lan = $language == 'en' ? 'english' : 'chinese';
        $lan = 'english';
        $data = StaticContent::where('slug', 'about-us')->where('language', $lan)->first();
        return view('admin.staticpages.aboutUs', compact('data', 'language'));
    }

    public function contentProtection()
    {
        $language = $request->language ?? 'english';
        // $request->session()->put('locale', $language);
        // $lan = $language == 'en' ? 'english' : 'chinese';
        $lan = 'english';
        $data = StaticContent::where('slug', 'popup1')->where('language', $lan)->first();
        return view('admin.staticpages.popup1', compact('data', 'language'));
    }

    public function contentDisclaimer()
    {
        $language = $request->language ?? 'english';
        // $request->session()->put('locale', $language);
        // $lan = $language == 'en' ? 'english' : 'chinese';
        $lan = 'english';
        $data = StaticContent::where('slug', 'popup2')->where('language', $lan)->first();
        return view('admin.staticpages.popup2', compact('data', 'language'));
    }
}
