<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Custom Filament v3 Signature Pad.
 *
 * - Renders an HTML5 canvas with Alpine.js drawing support.
 * - Stores the signature as a base64 PNG data URL in Livewire state.
 * - On dehydration (before DB save), converts data URL → PNG file
 *   stored at public://signatures/briefing/YYYY/MM/<uuid>.png.
 * - If state is already a file path (from a previous save or AppSheet),
 *   it is left unchanged.
 *
 * Usage:
 *   SignaturePad::make('signature_path')
 *       ->label('Tanda Tangan')
 *       ->required(fn (Get $get) => $get('attendance_status') === 'present')
 */
class SignaturePad extends Field
{
    protected string $view = 'filament.forms.components.signature-pad';

    // ─── Setup ──────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();

        // On save, convert the base64 PNG data URL to a stored file.
        $this->dehydrateStateUsing(static function (?string $state): ?string {
            if (blank($state)) {
                return null;
            }

            // Already a stored file path — leave unchanged.
            if (! str_starts_with($state, 'data:')) {
                return $state;
            }

            // Extract raw PNG bytes from the data URL and persist.
            $raw  = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $state));
            $path = 'signatures/briefing/' . date('Y/m') . '/' . Str::uuid() . '.png';

            Storage::disk('public')->put($path, $raw);

            return $path;  // stored relative path written to DB
        });
    }

    // ─── Helpers for the blade view ─────────────────────────────────────────

    /**
     * Returns a URL the canvas can <img src="..."> to pre-populate an
     * existing signature when the form is opened in edit mode.
     *
     * Returns null if state is empty or the file no longer exists.
     */
    public function getExistingSignatureUrl(): ?string
    {
        $state = $this->getState();

        if (blank($state)) {
            return null;
        }

        // New drawing not yet saved — data URL works directly in <img src>.
        if (str_starts_with($state, 'data:')) {
            return $state;
        }

        // Stored file path — resolve to absolute public URL.
        if (Storage::disk('public')->exists($state)) {
            return Storage::disk('public')->url($state);
        }

        // File missing (e.g. AppSheet-stored URL not in local storage).
        return null;
    }
}
