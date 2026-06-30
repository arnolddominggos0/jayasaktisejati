<?php

namespace App\Http\Controllers\Jsl;

use App\Http\Controllers\Controller;
use App\Models\JslGalleryItem;
use App\Models\JslSiteSettings;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        $settings = JslSiteSettings::current();
        $items = JslGalleryItem::ordered()->get();

        $categories = $items->pluck('category')->filter()->unique()->values();

        return view('jsl.gallery', compact('settings', 'items', 'categories'));
    }
}
