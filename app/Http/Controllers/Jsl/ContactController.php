<?php

namespace App\Http\Controllers\Jsl;

use App\Http\Controllers\Controller;
use App\Models\JslInquiry;
use App\Models\JslSiteSettings;
use App\Models\JslVesselListing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $settings = JslSiteSettings::current();

        return view('jsl.contact', compact('settings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
            'vessel_listing_id' => ['nullable', 'exists:jsl_vessel_listings,id'],
            'consent_given' => ['accepted'],
        ]);

        $validated['consent_given'] = true;
        $validated['status'] = 'new';

        JslInquiry::create($validated);

        return redirect()->route('jsl.contact.success')
            ->with('success', 'Thank you for your inquiry. Our team will contact you shortly.');
    }

    public function success(): View
    {
        $settings = JslSiteSettings::current();

        return view('jsl.contact-success', compact('settings'));
    }
}
