{{--
    TaskItem — the one reusable component representing exactly one
    Operational Task (P2, P3). Rendered identically regardless of
    whether it is classified as Action / Awaiting / Checkpoint
    (Sprint DS1 §5, ES1 §16's "smallest reusable building block").

    All branching (label, action button, invocation type) is
    pre-computed by TaskClassifier (MonitoringKapalTam::getBrief*())
    into a normalized item shape — this component performs zero
    domain logic, only rendering (DS1 §2, ES1 §8: "rendering
    components must never mutate data, must never classify").

    Props:
      item — normalized array:
             voyage_id, vessel_name, label, severity,
             action_label, action_type ('modal'|'drawer'), modal_type
      tone — 'action' | 'awaiting' | 'checkpoint' — display-only,
             supplied by the caller (which list is rendering it),
             never derived from the item's own data (severity and
             tone are independent axes — P8 §7/DS1 §7).
--}}
@props([
    'item',
    'tone' => 'action',
])

@php
    // VC1 Phase 4: the "action" tone's button now carries a filled
    // background at rest (not just border+hover) — the Coordinator must
    // find the one thing to click within one second, without the button
    // relying on a hover state to read as clickable. Awaiting/Checkpoint
    // stay lighter (they are not "click me now" affordances).
    $toneStyle = match ($tone) {
        'action' => [
            'row'   => 'bg-red-50/40 border border-red-100',
            'label' => 'text-gray-500',
            'btn'   => 'border-red-300 bg-red-600 text-white hover:bg-red-700',
        ],
        'checkpoint' => [
            'row'   => 'bg-amber-50/30',
            'label' => 'text-gray-500',
            'btn'   => 'border-amber-200 text-amber-700 hover:bg-amber-50',
        ],
        default => [ // awaiting
            'row'   => 'bg-gray-50/60',
            'label' => 'text-gray-400',
            'btn'   => 'text-gray-500 hover:text-gray-700',
        ],
    };
@endphp

<div class="flex items-center justify-between gap-3 rounded px-3 py-2 {{ $toneStyle['row'] }}">
    <div class="min-w-0">
        <span class="text-[13px] font-bold text-gray-900">{{ $item['vessel_name'] }}</span>
        <span class="text-[11px] {{ $toneStyle['label'] }} ml-1.5">{{ $item['label'] }}</span>
    </div>

    @if ($item['action_type'] === 'modal')
        <button wire:click="openOpModal({{ $item['voyage_id'] }}, '{{ $item['modal_type'] }}')"
            class="shrink-0 px-2.5 py-1 rounded border text-[10px] font-semibold transition {{ $toneStyle['btn'] }}">
            {{ $item['action_label'] }}
        </button>
    @else
        <button wire:click="openDrawer({{ $item['voyage_id'] }})"
            class="shrink-0 text-[10px] font-medium transition {{ $toneStyle['btn'] }}">
            {{ $item['action_label'] }} →
        </button>
    @endif
</div>
