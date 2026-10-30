<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndividualSubscriptionWaitlist;
use Illuminate\Http\Request;

class IndividualSubscriptionWaitlistController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(IndividualSubscriptionWaitlist::canView($request->user('admin')), 403);

        $request->validate(['search' => ['nullable', 'string', 'max:254']]);
        $search = trim((string) $request->input('search', ''));
        $registrations = IndividualSubscriptionWaitlist::query()
            ->when($search !== '', fn ($query) => $query->where('email', 'like', '%' . $search . '%'))
            ->latest('id')->paginate(25)->withQueryString();
        $total = IndividualSubscriptionWaitlist::count();

        if ($request->expectsJson()) {
            return response()->json(['total_unique' => $total, 'registrations' => $registrations])
                ->header('Cache-Control', 'private, no-store');
        }

        return response()->view('admin.waitlist.index', [
            'title' => IndividualSubscriptionWaitlist::MENU_NAME,
            'registrations' => $registrations,
            'total' => $total,
            'search' => $search,
        ])->header('Cache-Control', 'private, no-store');
    }
}
