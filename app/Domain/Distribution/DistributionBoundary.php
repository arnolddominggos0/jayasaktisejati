<?php

namespace App\Domain\Distribution;

use App\Models\Customer;
use App\Models\VesselPlan;

/**
 * Boundary domain Distribution.
 *
 * ARCH-01 menetapkan Vessel Plan sebagai master jadwal pelayaran perusahaan —
 * Layer 1. Layer 1 TIDAK boleh mengetahui customer tertentu.
 *
 * Kelas ini adalah satu-satunya tempat pengetahuan tentang "siapa penerima
 * draft" berada. Sebelumnya pengetahuan itu tertanam di dalam
 * VesselPlan::resolveTamCustomer() dan dipanggil dari booted()::creating,
 * sehingga model jadwal mengenal Toyota Astra Motor secara literal.
 *
 * CATATAN BATASAN (disengaja pada sprint ini):
 * Belum ada tabel distribusi, jadi customer_id masih disimpan di vessel_plans.
 * Boundary ini baru memisahkan PENGETAHUAN, belum memisahkan PENYIMPANAN.
 * Pemisahan penuh (satu jadwal → banyak penerima, status Sent/Revision/Approved
 * milik distribusi) memerlukan tabel tersendiri — lihat AUDIT-01 Opsi B.
 */
class DistributionBoundary
{
    /**
     * Customer default penerima draft.
     *
     * Sumber: config('jss_customers.tam_id'), dengan fallback pencarian nama.
     * Dipertahankan apa adanya agar workflow distribusi yang sedang berjalan
     * tidak berubah perilakunya pada sprint ini.
     */
    public function defaultRecipient(): ?Customer
    {
        $configuredId = (int) config('jss_customers.tam_id', 0);

        if ($configuredId > 0) {
            $customer = Customer::find($configuredId);

            if ($customer) {
                return $customer;
            }
        }

        return Customer::query()
            ->whereRaw('LOWER(name) = ?', ['toyota astra motor'])
            ->orWhereRaw('LOWER(name) like ?', ['%toyota astra motor%'])
            ->first();
    }

    public function defaultRecipientId(): ?int
    {
        return $this->defaultRecipient()?->id;
    }

    /**
     * Penerima draft untuk sebuah vessel plan.
     * Selama belum ada tabel distribusi, penerima disimpan di plan itu sendiri.
     */
    public function recipientFor(VesselPlan $plan): ?Customer
    {
        return $plan->customer;
    }

    /**
     * Apakah plan ini punya penerima yang dapat dihubungi?
     */
    public function hasReachableRecipient(VesselPlan $plan): bool
    {
        return filled($plan->customer_id) && filled($plan->whatsapp_phone);
    }
}
