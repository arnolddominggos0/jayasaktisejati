<?php

namespace App\Http\Controllers\Jsl;

use App\Http\Controllers\Controller;
use App\Models\JslService;
use App\Models\JslSiteSettings;
use Illuminate\View\View;

class ServicesController extends Controller
{
    public function index(): View
    {
        $settings = JslSiteSettings::current();
        $services = JslService::visible()->get();

        return view('jsl.services', compact('settings', 'services'));
    }
}
