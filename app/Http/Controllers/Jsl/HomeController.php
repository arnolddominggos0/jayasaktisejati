<?php

namespace App\Http\Controllers\Jsl;

use App\Http\Controllers\Controller;
use App\Models\JslCompanyProfile;
use App\Models\JslService;
use App\Models\JslSiteSettings;
use App\Models\JslVesselListing;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $settings = JslSiteSettings::current();
        $profile = JslCompanyProfile::current();
        $services = JslService::visible()->get();
        $vessels = JslVesselListing::open()->limit(6)->get();

        return view('jsl.home', compact('settings', 'profile', 'services', 'vessels'));
    }
}
