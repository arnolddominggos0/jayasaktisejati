<?php

namespace App\Http\Controllers\Jsl;

use App\Http\Controllers\Controller;
use App\Models\JslSiteSettings;
use App\Models\JslVesselListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VesselListingController extends Controller
{
    public function index(): View
    {
        $settings = JslSiteSettings::current();
        $vessels = JslVesselListing::open()->get();

        return view('jsl.trading.index', compact('settings', 'vessels'));
    }

    public function show(string $refCode): View|RedirectResponse
    {
        $settings = JslSiteSettings::current();
        $vessel = JslVesselListing::where('public_ref_code', $refCode)
            ->where('status', 'open')
            ->first();

        if (! $vessel) {
            return redirect()->route('jsl.trading.index')
                ->with('error', 'Vessel listing not found or no longer available.');
        }

        return view('jsl.trading.show', compact('settings', 'vessel'));
    }
}
