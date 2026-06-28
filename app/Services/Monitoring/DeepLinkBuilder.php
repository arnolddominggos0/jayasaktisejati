<?php

namespace App\Services\Monitoring;

use App\Models\Shipment;
use App\Models\Unit;
use App\ViewModels\Monitoring\DeepLinkData;

final class DeepLinkBuilder
{
    public function build(Shipment $shipment, Unit $unit): array
    {
        $links = [];

        // Voyage
        if ($shipment->voyage_id && $shipment->voyageRecord) {
            $voyageLabel = $shipment->voyageRecord->voyage_no ?? "Voyage #{$shipment->voyage_id}";
            $links[] = new DeepLinkData(
                label: $voyageLabel,
                url: "/admin/voyages/{$shipment->voyage_id}",
                icon: 'heroicon-o-paper-airplane',
                description: 'Lihat detail voyage',
            );
        }

        // SPPB / Shipment
        $links[] = new DeepLinkData(
            label: $shipment->doc_number,
            url: "/admin/shipments/{$shipment->id}",
            icon: 'heroicon-o-document-text',
            description: 'Lihat detail SPPB',
        );

        // Customer
        if ($shipment->customer_id) {
            $customerName = $shipment->relationLoaded('customer')
                ? optional($shipment->customer)->name
                : "Customer #{$shipment->customer_id}";

            $links[] = new DeepLinkData(
                label: (string) $customerName,
                url: "/admin/customers/{$shipment->customer_id}",
                icon: 'heroicon-o-building-office-2',
                description: 'Lihat profil customer',
            );
        }

        // Attachments (metadata only — count, no preview)
        $attachments = $shipment->attachments ?? [];
        if (!empty($attachments)) {
            $links[] = new DeepLinkData(
                label: count($attachments) . ' Lampiran',
                url: "/admin/shipments/{$shipment->id}#attachments",
                icon: 'heroicon-o-paper-clip',
                description: 'Dokumen & foto terlampir',
            );
        }

        return $links;
    }
}
