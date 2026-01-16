<?php

namespace App\Console\Commands;

use App\Mail\ShipmentEtaReminderMail;
use App\Models\Shipment;
use App\Models\ShipmentEmailNotification;
use App\Models\User;
use App\Enums\TrackStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendShipmentEtaNotifications extends Command
{
    protected $signature = 'shipments:send-eta-notifications 
                            {--dry-run : Run without actually sending emails}
                            {--days= : Only process specific days before ETA (e.g., 3,2,1,0)}';

    protected $description = 'Send email notifications for shipments arriving in 3, 2, 1, or 0 days (H-3, H-2, H-1, H-0)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $daysFilter = $this->option('days');
        
        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No emails will be sent');
        }

        $this->info('🚀 Starting ETA notification process...');
        $this->info('📅 Date: ' . now()->format('Y-m-d H:i:s'));
        
        $today = Carbon::today();
        $notificationsSent = 0;
        $notificationsFailed = 0;
        $notificationsSkipped = 0;

        $daysToProcess = $daysFilter 
            ? array_map('intval', explode(',', $daysFilter))
            : [3, 2, 1, 0];

        $this->line("Processing for H-" . implode(', H-', $daysToProcess));
        $this->newLine();

        foreach ($daysToProcess as $daysBeforeEta) {
            $targetEtaDate = $today->copy()->addDays($daysBeforeEta);
            
            $this->info("📊 Processing H-{$daysBeforeEta} (ETA: {$targetEtaDate->format('Y-m-d')})");

            $shipments = Shipment::query()
                ->whereDate('eta', $targetEtaDate)
                ->whereHas('latestTrack', function ($q) {
                    $q->where('status', TrackStatus::VesselDepart->value);
                })
                ->with(['customer', 'receiver', 'branch', 'latestTrack'])
                ->get();

            $this->line("   Found {$shipments->count()} shipments with vessel_depart status");

            if ($shipments->isEmpty()) {
                $this->line("   No shipments to process for H-{$daysBeforeEta}");
                $this->newLine();
                continue;
            }

            foreach ($shipments as $shipment) {
                $this->line("   Processing shipment: {$shipment->code}");
                
                $users = $this->getUsersToNotify($shipment);

                if (empty($users)) {
                    $this->warn("   ⚠️  No users found for {$shipment->code}");
                    $notificationsSkipped++;
                    continue;
                }

                $this->line("   Found " . count($users) . " user(s) to notify");

                foreach ($users as $user) {
                    try {
                        $existingNotification = ShipmentEmailNotification::where([
                            'shipment_id' => $shipment->id,
                            'user_id' => $user->id,
                            'days_before_eta' => $daysBeforeEta,
                        ])->first();

                        if ($existingNotification && $existingNotification->status === 'sent') {
                            $this->line("   ⏭️  Skipped {$shipment->code} → {$user->email} (already sent)");
                            $notificationsSkipped++;
                            continue;
                        }

                        $notification = ShipmentEmailNotification::updateOrCreate(
                            [
                                'shipment_id' => $shipment->id,
                                'user_id' => $user->id,
                                'days_before_eta' => $daysBeforeEta,
                            ],
                            [
                                'user_email' => $user->email,
                                'shipment_code' => $shipment->code,
                                'eta_date' => $shipment->eta,
                                'status' => 'pending',
                            ]
                        );

                        if (!$isDryRun) {
                            Mail::to($user->email)
                                ->send(new ShipmentEtaReminderMail($shipment, $daysBeforeEta));

                            $notification->markAsSent();
                            $notificationsSent++;
                            
                            $this->line("   ✅ Sent to {$user->email} for {$shipment->code}");
                            
                            Log::info('Shipment ETA notification sent', [
                                'shipment_id' => $shipment->id,
                                'shipment_code' => $shipment->code,
                                'user_id' => $user->id,
                                'user_email' => $user->email,
                                'days_before_eta' => $daysBeforeEta,
                                'eta_date' => $shipment->eta->format('Y-m-d'),
                            ]);
                        } else {
                            $this->line("   🧪 [DRY RUN] Would send to {$user->email} for {$shipment->code}");
                            $notificationsSent++;
                        }

                        if (!$isDryRun) {
                            usleep(100000); // 0.1 sec
                        }

                    } catch (\Exception $e) {
                        $notificationsFailed++;
                        
                        if (isset($notification)) {
                            $notification->markAsFailed($e->getMessage());
                        }

                        $this->error("   ❌ Failed {$user->email}: {$e->getMessage()}");
                        
                        Log::error('Shipment ETA notification failed', [
                            'shipment_id' => $shipment->id,
                            'shipment_code' => $shipment->code,
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'days_before_eta' => $daysBeforeEta,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }

            $this->newLine();
        }

        // $this->newLine();
        // $this->info('📊 SUMMARY REPORT');
        // $this->info('═══════════════════════════════════════════════');
        // $this->line("   ✅ Notifications sent: {$notificationsSent}");
        // $this->line("   ⏭️  Notifications skipped: {$notificationsSkipped}");
        // $this->line("   ❌ Notifications failed: {$notificationsFailed}");
        // $this->info('═══════════════════════════════════════════════');
        
        if ($isDryRun) {
            $this->warn('🧪 This was a DRY RUN - no emails were actually sent');
        }

        $this->newLine();

        return Command::SUCCESS;
    }

    private function getUsersToNotify(Shipment $shipment): array
    {
        $users = collect();

        if ($shipment->branch_id) {
            $branchUsers = User::query()
                ->where('port_id', $shipment->branch_id)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereNull('customer_id')
                ->get();

            $users = $users->merge($branchUsers);
        }

        if ($shipment->customer_id) {
            $customerUsers = User::query()
                ->where('customer_id', $shipment->customer_id)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            $users = $users->merge($customerUsers);
        }

        $users = collect($users)->unique('id')->values()->all();

        if (empty($users)) {
            Log::warning('No users found for shipment notification', [
                'shipment_id' => $shipment->id,
                'shipment_code' => $shipment->code,
                'branch_id' => $shipment->branch_id,
                'customer_id' => $shipment->customer_id,
            ]);
        }

        $this->line("Sekarang jam " . now()->format('H:i:s'));

        return $users;
    }
}