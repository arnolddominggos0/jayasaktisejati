<?php

namespace Database\Seeders;

use App\Models\JslCompanyProfile;
use App\Models\JslGalleryItem;
use App\Models\JslInquiry;
use App\Models\JslMediaAsset;
use App\Models\JslService;
use App\Models\JslSiteSettings;
use App\Models\JslVesselImage;
use App\Models\JslVesselListing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JslWebsiteSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('TRUNCATE TABLE jsl_inquiries, jsl_vessel_images, jsl_gallery_items, jsl_vessel_listings, jsl_services, jsl_company_profiles, jsl_site_settings, jsl_media_assets RESTART IDENTITY CASCADE');

        $mediaAssets = $this->seedMediaAssets();
        $this->seedSiteSettings();
        $this->seedCompanyProfile();
        $this->seedServices();
        $vessels = $this->seedVesselListings();
        $this->seedVesselImages($vessels);
        $this->seedGalleryItems($mediaAssets);
        $this->seedInquiries();
    }

    /**
     * Relative path (on the public disk) of placeholder image N (1-based),
     * cycling through the available maritime placeholder files.
     */
    private function placeholderPath(int $n): string
    {
        $file = (($n - 1) % 6) + 1;

        return "jsl/placeholders/m{$file}.jpg";
    }

    private function makeMediaAsset(int $n): JslMediaAsset
    {
        return JslMediaAsset::create([
            'disk' => 'public',
            'file_path' => $this->placeholderPath($n),
            'file_name' => 'm' . ((($n - 1) % 6) + 1) . '.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 200000,
            'width' => 1280,
            'height' => 853,
        ]);
    }

    private function seedMediaAssets(): array
    {
        $assets = [];

        for ($i = 1; $i <= 8; $i++) {
            $assets[] = $this->makeMediaAsset($i);
        }

        return $assets;
    }

    private function seedSiteSettings(): void
    {
        JslSiteSettings::create([
            'site_name' => 'Jaya Sakti Line',
            'tagline' => 'Your Trusted Partner in Marine Vessels',
            'footer_text' => 'Your trusted partner in marine vessel trading and chartering across Indonesian waters and beyond.',
            'site_name_en' => 'Jaya Sakti Line',
            'tagline_en' => 'Your Trusted Partner in Marine Vessels',
            'footer_text_en' => 'Your trusted partner in marine vessel trading and chartering across Indonesian waters and beyond.',
            'contact_address' => 'Jl. Pelabuhan No. 1, Jakarta Utara, DKI Jakarta, Indonesia',
            'contact_phone_display' => '+62 21 555 0123',
            'contact_email_display' => 'info@jayasaktiline.com',
            'social_facebook_url' => 'https://facebook.com/jayasaktiline',
            'social_instagram_url' => 'https://instagram.com/jayasaktiline',
            'social_linkedin_url' => 'https://linkedin.com/company/jayasaktiline',
            'broker_whatsapp' => '+62 812 3456 7890',
            'broker_email' => 'broker@jayasaktiline.com',
        ]);
    }

    private function seedCompanyProfile(): void
    {
        JslCompanyProfile::create([
            'about' => '<p>Jaya Sakti Line has been a trusted name in the maritime industry for over three decades, specializing in vessel trading and chartering services across Indonesian waters and Southeast Asia. Our deep industry knowledge and extensive network enable us to connect buyers and sellers with confidence and transparency.</p>',
            'overview' => '<p>With decades of collective experience in the maritime sector, our team provides comprehensive solutions for buyers and sellers of marine vessels, ensuring transparency, expertise, and integrity in every transaction.</p>',
            'vision' => 'To be the leading marine vessel trading platform in Southeast Asia, known for trust, transparency, and excellence.',
            'mission' => 'To connect buyers and sellers with transparency, expertise, and integrity, ensuring fair and successful transactions for all parties involved.',
            'about_en' => '<p>Jaya Sakti Line has been a trusted name in the maritime industry for over three decades, specializing in vessel trading and chartering services across Indonesian waters and Southeast Asia.</p>',
            'overview_en' => '<p>With decades of collective experience, our team provides comprehensive solutions for buyers and sellers of marine vessels.</p>',
            'vision_en' => 'To be the leading marine vessel trading platform in Southeast Asia.',
            'mission_en' => 'To connect buyers and sellers with transparency, expertise, and integrity.',
        ]);
    }

    private function seedServices(): void
    {
        $services = [
            [
                'title' => 'Vessel Trading',
                'description' => '<p>Buy and sell marine vessels with confidence. We facilitate transactions for tugboats, barges, tankers, and cargo ships, ensuring transparent pricing and verified documentation.</p>',
                'sort_order' => 1,
            ],
            [
                'title' => 'Vessel Chartering',
                'description' => '<p>Flexible chartering options for your maritime operations. Whether you need a vessel for short-term projects or long-term contracts, we have the right solution.</p>',
                'sort_order' => 2,
            ],
            [
                'title' => 'Marine Brokerage',
                'description' => '<p>Professional brokerage services connecting qualified buyers with vessel owners. Our experienced brokers handle negotiations, inspections, and closing.</p>',
                'sort_order' => 3,
            ],
            [
                'title' => 'Vessel Inspection',
                'description' => '<p>Comprehensive vessel inspection services conducted by certified marine surveyors. Get detailed condition reports before making your decision.</p>',
                'sort_order' => 4,
            ],
            [
                'title' => 'Documentation & Legal',
                'description' => '<p>Full support with vessel documentation, flag registration, ownership transfer, and legal compliance across multiple jurisdictions.</p>',
                'sort_order' => 5,
            ],
            [
                'title' => 'Maritime Consultancy',
                'description' => '<p>Expert advice on vessel acquisition, market trends, regulatory compliance, and operational optimization from our seasoned maritime professionals.</p>',
                'sort_order' => 6,
            ],
        ];

        foreach ($services as $svc) {
            JslService::create(array_merge($svc, [
                'is_visible' => true,
                'title_en' => $svc['title'],
                'description_en' => $svc['description'],
            ]));
        }
    }

    /**
     * @return array<int, JslVesselListing>
     */
    private function seedVesselListings(): array
    {
        $vessels = [
            [
                'public_ref_code' => 'TUG-001',
                'vessel_type' => 'tugboat',
                'year_built' => 2015,
                'flag_registry' => 'Indonesia',
                'gross_tonnage' => 180.50,
                'deadweight' => 120.00,
                'loa_length' => 24.00,
                'beam' => 7.50,
                'draft' => 3.20,
                'engine_power' => '2 x 1200 HP',
                'trading_area' => 'Indonesia Domestic',
                'marketing_description' => '<p>Well-maintained tugboat suitable for harbor and coastal towing operations. Recently dry-docked with all certificates valid. Ready for immediate deployment.</p>',
                'status' => 'open',
                'real_vessel_name' => 'MV Sakti Mandiri',
                'imo_number' => '9512345',
                'owner_details' => 'PT Maritim Jaya',
                'certificates' => 'SBT, IOPP, LOAD LINE, SAFETY CONSTRUCTION',
                'price_commercial_terms' => 'USD 850,000 - negotiable',
            ],
            [
                'public_ref_code' => 'TUG-002',
                'vessel_type' => 'tugboat',
                'year_built' => 2018,
                'flag_registry' => 'Panama',
                'gross_tonnage' => 220.00,
                'deadweight' => 150.00,
                'loa_length' => 28.00,
                'beam' => 8.00,
                'draft' => 3.50,
                'engine_power' => '2 x 1800 HP',
                'trading_area' => 'Southeast Asia',
                'marketing_description' => '<p>Powerful ocean-going tugboat with DP1 capability. Excellent condition, low engine hours. Suitable for offshore towing and anchor handling.</p>',
                'status' => 'open',
                'real_vessel_name' => 'MV Samudra Jaya',
                'imo_number' => '9723456',
                'owner_details' => 'PT Samudra Lines',
                'certificates' => 'SBT, IOPP, LOAD LINE, ISM, DP1',
                'price_commercial_terms' => 'USD 1,250,000 - firm',
            ],
            [
                'public_ref_code' => 'BRG-001',
                'vessel_type' => 'barge',
                'year_built' => 2012,
                'flag_registry' => 'Indonesia',
                'gross_tonnage' => 1200.00,
                'deadweight' => 3000.00,
                'loa_length' => 60.00,
                'beam' => 12.00,
                'draft' => 4.50,
                'engine_power' => 'Non-propelled',
                'trading_area' => 'Indonesia Domestic',
                'marketing_description' => '<p>3000 DWT flat-top barge suitable for bulk cargo and project logistics. Class maintained, ready for loading. Available for sale or charter.</p>',
                'status' => 'open',
                'real_vessel_name' => 'Barge JSL-12',
                'imo_number' => '8612345',
                'owner_details' => 'PT Barging Solutions',
                'certificates' => 'BKI Class, LOAD LINE',
                'price_commercial_terms' => 'USD 450,000 or USD 8,500/day charter',
            ],
            [
                'public_ref_code' => 'TNK-001',
                'vessel_type' => 'tanker',
                'year_built' => 2016,
                'flag_registry' => 'Indonesia',
                'gross_tonnage' => 850.00,
                'deadweight' => 1500.00,
                'loa_length' => 50.00,
                'beam' => 10.50,
                'draft' => 4.00,
                'engine_power' => '1 x 1500 HP',
                'trading_area' => 'Indonesia Domestic',
                'marketing_description' => '<p>Chemical tanker with stainless steel tanks. Suitable for transporting various chemical and petroleum products. Double hull, compliant with MARPOL.</p>',
                'status' => 'open',
                'real_vessel_name' => 'MT Bumi Saktiline',
                'imo_number' => '9654321',
                'owner_details' => 'PT Tanker Express',
                'certificates' => 'SBT, IOPP, IBC Code, ISM',
                'price_commercial_terms' => 'USD 2,100,000 - negotiable',
            ],
            [
                'public_ref_code' => 'CRG-001',
                'vessel_type' => 'cargo',
                'year_built' => 2010,
                'flag_registry' => 'Singapore',
                'gross_tonnage' => 2500.00,
                'deadweight' => 4000.00,
                'loa_length' => 85.00,
                'beam' => 13.00,
                'draft' => 5.50,
                'engine_power' => '1 x 3000 HP',
                'trading_area' => 'Southeast Asia',
                'marketing_description' => '<p>General cargo vessel with twin hatches and cargo cranes. Suitable for dry bulk, break-bulk, and project cargo. Well-maintained with recent dry-docking.</p>',
                'status' => 'open',
                'real_vessel_name' => 'MV Indah Permai',
                'imo_number' => '9412345',
                'owner_details' => 'PT Cargo Lines',
                'certificates' => 'SBT, IOPP, LOAD LINE, ISM, ISPS',
                'price_commercial_terms' => 'USD 1,800,000 - negotiable',
            ],
            [
                'public_ref_code' => 'TUG-003',
                'vessel_type' => 'tugboat',
                'year_built' => 2020,
                'flag_registry' => 'Indonesia',
                'gross_tonnage' => 195.00,
                'deadweight' => 110.00,
                'loa_length' => 25.50,
                'beam' => 8.00,
                'draft' => 3.30,
                'engine_power' => '2 x 1500 HP',
                'trading_area' => 'Indonesia Domestic',
                'marketing_description' => '<p>Modern harbor tug with excellent maneuverability. Equipped with fire-fighting system (FiFi 1). Very low engine hours, practically new condition.</p>',
                'status' => 'open',
                'real_vessel_name' => 'MV Garuda Laut',
                'imo_number' => '9834567',
                'owner_details' => 'PT Garuda Marine',
                'certificates' => 'SBT, IOPP, LOAD LINE, ISM, FiFi 1',
                'price_commercial_terms' => 'USD 1,450,000 - firm',
            ],
        ];

        $created = [];

        foreach ($vessels as $vessel) {
            $created[] = JslVesselListing::create(array_merge($vessel, [
                'marketing_description_en' => $vessel['marketing_description'],
            ]));
        }

        return $created;
    }

    /**
     * Give every vessel 3 images so the listing cards and the detail-page
     * gallery (primary + thumbnails) both render with seeded demo data.
     *
     * @param  array<int, JslVesselListing>  $vessels
     */
    private function seedVesselImages(array $vessels): void
    {
        $n = 0;

        foreach ($vessels as $vessel) {
            for ($slot = 0; $slot < 3; $slot++) {
                $asset = $this->makeMediaAsset(($n % 6) + 1);
                $n++;

                JslVesselImage::create([
                    'vessel_listing_id' => $vessel->id,
                    'media_asset_id' => $asset->id,
                    'sort_order' => $slot + 1,
                    'alt_text' => ucfirst($vessel->vessel_type) . ' ' . $vessel->public_ref_code,
                ]);
            }
        }
    }

    private function seedGalleryItems(array $mediaAssets): void
    {
        $items = [
            ['caption' => 'Tugboat Operations', 'category' => 'Operations'],
            ['caption' => 'Barge Loading', 'category' => 'Operations'],
            ['caption' => 'Port Activities', 'category' => 'Port'],
            ['caption' => 'Fleet Overview', 'category' => 'Fleet'],
            ['caption' => 'Tanker Fleet', 'category' => 'Fleet'],
            ['caption' => 'Cargo Operations', 'category' => 'Operations'],
            ['caption' => 'Dry Dock Maintenance', 'category' => 'Maintenance'],
            ['caption' => 'Vessel Inspection', 'category' => 'Maintenance'],
        ];

        $sortOrder = 1;
        foreach ($items as $index => $item) {
            JslGalleryItem::create([
                'media_asset_id' => $mediaAssets[$index]->id,
                'caption' => $item['caption'],
                'caption_en' => $item['caption'],
                'category' => $item['category'],
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function seedInquiries(): void
    {
        $inquiries = [
            [
                'name' => 'John Anderson',
                'company' => 'Anderson Shipping Co.',
                'email' => 'john@andersonshipping.com',
                'phone' => '+65 9123 4567',
                'message' => 'I am interested in TUG-001. Could you please provide more details about the vessel condition and arrange an inspection?',
                'consent_given' => true,
                'status' => 'new',
            ],
            [
                'name' => 'Budi Santoso',
                'company' => 'PT Lautan Mas',
                'email' => 'budi@lautanmas.co.id',
                'phone' => '+62 812 1111 2222',
                'message' => 'We are looking for a barge for charter. Is BRG-001 available for monthly charter? Please send us the terms.',
                'consent_given' => true,
                'status' => 'contacted',
            ],
            [
                'name' => 'Sarah Chen',
                'company' => 'Pacific Marine Ltd.',
                'email' => 'sarah@pacificmarine.com',
                'phone' => '+852 9876 5432',
                'message' => 'Interested in your tanker vessel TNK-001. What is the current classification status and when was the last dry docking?',
                'consent_given' => true,
                'status' => 'new',
            ],
        ];

        foreach ($inquiries as $inquiry) {
            JslInquiry::create($inquiry);
        }
    }
}
