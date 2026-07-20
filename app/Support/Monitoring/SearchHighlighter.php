<?php

namespace App\Support\Monitoring;

use Illuminate\Support\HtmlString;

/**
 * Reusable, case-insensitive search-term highlighter. Wraps every
 * occurrence of `$term` inside `$text` with a <mark class="mon-hl"> so
 * the operator can instantly see why a row matched their query.
 *
 * The text is HTML-escaped before replacement so user input can never
 * inject markup. Falls back to the escaped text when the term is empty
 * (no active search), making it safe to call unconditionally from Blade.
 *
 * Used by the monitoring table partial for Unit, SPPB, Voyage, Customer,
 * and Chassis columns — any rendered text that might carry a search hit.
 */
final class SearchHighlighter
{
    /**
     * Highlight every case-insensitive occurrence of `$term` in `$text`.
     * Returns an HtmlString so Blade can render it with `{!! ... !!}`
     * without double-escaping.
     */
    public static function highlight(?string $text, string $term): HtmlString
    {
        $text ??= '';

        if ($term === '') {
            return new HtmlString(e($text));
        }

        $escaped      = e($text);
        $escapedTerm   = preg_quote(e($term), '/');

        return new HtmlString(
            preg_replace('/(' . $escapedTerm . ')/iu', '<mark class="mon-hl">$1</mark>', $escaped) ?: $escaped
        );
    }
}