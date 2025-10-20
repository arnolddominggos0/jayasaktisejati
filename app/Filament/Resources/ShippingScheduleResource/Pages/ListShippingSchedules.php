<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource;
use App\Models\ShippingSchedule;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListShippingSchedules extends ListRecords
{
    protected static string $resource = ShippingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_log_final_whatsapp')
                ->label('Buat Jadwal Bulanan')
                ->icon('heroicon-o-check-badge')
                ->modalHeading('Buat Paket & Log Final')
                ->form([
                    TextInput::make('period_ym')
                        ->label('Periode (YYYY-MM)')
                        ->required()
                        ->rule('regex:/^\d{4}\-\d{2}$/')
                        ->default(now()->format('Y-m')),
                    Select::make('customer_id')
                        ->label('Customer')
                        ->relationship('customer', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('pol_id')
                        ->label('POL')
                        ->relationship('pol', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('pod_id')
                        ->label('POD')
                        ->relationship('pod', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('title')
                        ->label('Judul (opsional)')
                        ->maxLength(150),
                    Textarea::make('notes')
                        ->label('Catatan paket (opsional)')
                        ->rows(2),

                    Forms\Components\Section::make('Final dari WhatsApp')
                        ->schema([
                            Textarea::make('paste_table')
                                ->label('Tempel Tabel Final')
                                ->placeholder("Line\tVessel\tVoyage\tPOL\tPOD\tETD\tETA\tService")
                                ->rows(10)
                                ->required(),
                            TextInput::make('approved_by_name')
                                ->label('Disetujui oleh')
                                ->maxLength(120),
                            Textarea::make('final_note')
                                ->label('Catatan Final')
                                ->rows(2),
                            FileUpload::make('final_attachment')
                                ->label('Lampiran WA/Excel (opsional)')
                                ->disk('public')
                                ->directory('schedules/' . date('Y/m'))
                                ->visibility('public'),
                        ])->collapsible(),
                ])
                ->action(function (array $data) {
                    $schedule = ShippingSchedule::create([
                        'customer_id'  => $data['customer_id'],
                        'pol_id'       => $data['pol_id'],
                        'pod_id'       => $data['pod_id'],
                        'period_ym'    => $data['period_ym'],
                        'title'        => $data['title'] ?? null,
                        'notes'        => $data['notes'] ?? null,
                        'state'        => ScheduleState::Draft->value,
                        'created_by'   => auth()->id(),
                    ]);

                    $path = null;
                    if (!empty($data['final_attachment'])) {
                        $path = is_array($data['final_attachment'])
                            ? ($data['final_attachment'][0] ?? null)
                            : $data['final_attachment'];
                    }

                    $result = $schedule->finalizeFromWhatsapp(
                        (string)($data['paste_table'] ?? ''),
                        $data['final_note'] ?? null,
                        $path,
                        $data['approved_by_name'] ?? null,
                        auth()->id()
                    );

                    Notification::make()
                        ->title("Paket dibuat & final dicatat ({$result['voyages']} voyage)")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
