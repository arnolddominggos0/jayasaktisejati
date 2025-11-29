<?php

namespace App\Filament\Resources\VoyageResource\RelationManagers;

use App\Enums\ScheduleState;
use Exception;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class ScheduleRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';
    protected static ?string $title = 'Jadwal TAM';

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Rangkuman Voyage')->schema([
                Forms\Components\Placeholder::make('kapal')->content(fn() => $this->getOwnerRecord()?->vessel?->name ?: '-')->label('Kapal'),
                Forms\Components\Placeholder::make('line')->content(fn() => $this->getOwnerRecord()?->vessel?->shippingLine?->name ?: '-')->label('Shipping Line'),
                Forms\Components\Placeholder::make('rute')->content(fn() => optional($this->getOwnerRecord()?->pol)->code . ' → ' . optional($this->getOwnerRecord()?->pod)->code)->label('Rute'),
                Forms\Components\Placeholder::make('etd')->content(fn() => optional($this->getOwnerRecord()?->etd)?->format('d M Y H:i') ?: '-')->label('ETD'),
                Forms\Components\Placeholder::make('eta')->content(fn() => optional($this->getOwnerRecord()?->eta)?->format('d M Y H:i') ?: '-')->label('ETA'),
            ])->columns(5),
            Forms\Components\TextInput::make('cargo_plan')->numeric()->required()->minValue(1)->default(0)->label('Cargo Plan'),
            Forms\Components\TextInput::make('jss')->maxLength(100)->label('JSS'),
            Forms\Components\TextInput::make('dwelling_days')->numeric()->label('Dwelling (hari)'),
            Forms\Components\Select::make('state')->options(ScheduleState::options())->required()->default(ScheduleState::Draft->value)->label('Status'),
            Forms\Components\Textarea::make('final_note')->label('Catatan'),
            Forms\Components\FileUpload::make('final_attachment_path')->label('Lampiran')->directory('schedule-attachments')->preserveFilenames(),
            Forms\Components\DateTimePicker::make('finalized_at')->label('Tanggal Final'),
        ])->columns(2);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cargo_plan')->label('Cargo Plan'),
                Tables\Columns\TextColumn::make('jss')->label('JSS'),
                Tables\Columns\TextColumn::make('dwelling_days')->label('Dwelling (hari)'),
                Tables\Columns\TextColumn::make('state')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('finalized_at')->dateTime()->label('Tanggal Final'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data) {
                        $owner = $this->getOwnerRecord();
                        $data['etd'] = $owner->etd;
                        $data['eta'] = $owner->eta;
                        $data['period_month'] = optional($owner->etd)?->startOfMonth();
                        $data['vessel_id'] = $owner->vessel_id;
                        $data['vessel_name'] = $owner->vessel?->name;
                        $data['shipping_line_id'] = $owner->vessel?->shippingLine?->id;
                        $data['voyage_no'] = $owner->voyage_no;

                        if (($data['state'] ?? null) === ScheduleState::Final->value) {
                            $tmp = new \App\Models\ShippingSchedule($data);
                            $tmp->voyage()->associate($owner);
                            if (!$this->etd || !$this->eta || $this->cargo_plan_total <= 0) {
                                throw new Exception("Tidak bisa final: ETD/ETA wajib dan Cargo Plan > 0.");
                            }

                            $data['finalized_at'] = $data['finalized_at'] ?? now();
                        }

                        return $owner->schedules()->create($data);
                    })
                    ->visible(fn() => $this->getOwnerRecord()->schedules()->doesntExist()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function ($record, array $data) {
                        $next = $data['state'] ?? $record->state?->value;
                        if ($next === ScheduleState::Final->value) {
                            $tmp = clone $record;
                            $tmp->fill($data);
                            if (!$this->etd || !$this->eta || $this->cargo_plan_total <= 0) {
                                throw new Exception("Tidak bisa final: ETD/ETA wajib dan Cargo Plan > 0.");
                            }

                            $data['finalized_at'] = $data['finalized_at'] ?? now();
                        }
                        $record->update($data);
                        $record->refreshActualSailing();
                        return $record;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->paginated(false);
    }
}
