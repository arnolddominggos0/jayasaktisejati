<?php

namespace App\Http\Controllers\Jsl;

use App\Http\Controllers\Controller;
use App\Models\JslCompanyProfile;
use App\Models\JslSiteSettings;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function index(): View
    {
        $settings = JslSiteSettings::current();
        $profile = JslCompanyProfile::current();

        return view('jsl.about', compact('settings', 'profile'));
    }
}
