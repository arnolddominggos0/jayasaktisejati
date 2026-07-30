@php
    use Filament\Notifications\Livewire\Notifications;
    use Filament\Support\Enums\Alignment;
    use Filament\Support\Enums\VerticalAlignment;
    use Illuminate\Support\Arr;

    $color = $getColor() ?? 'gray';
    $isInline = $isInline();
    $status = $getStatus();
    $title = $getTitle();
    $hasTitle = filled($title);
    $date = $getDate();
    $hasDate = filled($date);
    $body = $getBody();
    $hasBody = filled($body);
@endphp

<x-filament-notifications::notification
    :notification="$notification"
    :x-transition:enter-start="
        Arr::toCssClasses([
            'opacity-0',
            ($this instanceof Notifications)
            ? match (static::$alignment) {
                Alignment::Start, Alignment::Left => '-translate-x-12',
                Alignment::End, Alignment::Right => 'translate-x-12',
                Alignment::Center => match (static::$verticalAlignment) {
                    VerticalAlignment::Start => '-translate-y-12',
                    VerticalAlignment::End => 'translate-y-12',
                    default => null,
                },
                default => null,
            }
            : null,
        ])
    "
    :x-transition:leave-end="
        Arr::toCssClasses([
            'opacity-0',
            'scale-95' => ! $isInline,
        ])
    "
    @class([
        'fi-no-notification w-full overflow-hidden transition duration-300',
        ...match ($isInline) {
            true => [
                'fi-inline',
            ],
            false => [
                'max-w-sm rounded-xl bg-white shadow-lg ring-1 dark:bg-gray-900',
                match ($color) {
                    'gray' => 'ring-gray-950/5 dark:ring-white/10',
                    default => 'fi-color-custom ring-custom-600/20 dark:ring-custom-400/30',
                },
                is_string($color) ? 'fi-color-' . $color : null,
                'fi-status-' . $status => $status,
            ],
        },
    ])
    @style([
        \Filament\Support\get_color_css_variables(
            $color,
            shades: [50, 400, 600],
            alias: 'notifications::notification',
        ) => ! ($isInline || $color === 'gray'),
    ])
>
    <div
    @class([
        'flex items-start gap-4 px-5 py-5 transition-all duration-200',
        'hover:bg-gray-50 dark:hover:bg-white/5',
        match ($color) {
            'gray' => null,
            default => 'bg-custom-50 dark:bg-custom-400/10',
        },
    ])
>

    {{-- Icon --}}
    @if ($icon = $getIcon())
        <div class="mt-1 shrink-0">
            <x-filament-notifications::icon
                :color="$getIconColor()"
                :icon="$icon"
                :size="$getIconSize()"
            />
        </div>
    @endif

    {{-- Content --}}
    <div class="flex-1 min-w-0">

        @if ($hasTitle)
            <div class="flex items-center justify-between gap-3">

                <x-filament-notifications::title
                    class="font-semibold text-sm text-gray-900 dark:text-white"
                >
                    {{ str($title)->sanitizeHtml()->toHtmlString() }}
                </x-filament-notifications::title>

                @if (method_exists($notification, 'unread') && $notification->unread())
                    <span class="h-2.5 w-2.5 rounded-full bg-primary-600 shrink-0"></span>
                @endif

            </div>
        @endif

        @if ($hasBody)
            <x-filament-notifications::body
                class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400"
            >
                {{ str($body)->sanitizeHtml()->toHtmlString() }}
            </x-filament-notifications::body>
        @endif

        @if ($hasDate)
            <div class="mt-3 text-xs text-gray-400">
                {{ $date }}
            </div>
        @endif

        @if ($actions = $getActions())
            <div class="mt-4">
                <x-filament-notifications::actions
                    :actions="$actions"
                />
            </div>
        @endif

    </div>

</div>
</x-filament-notifications::notification>
