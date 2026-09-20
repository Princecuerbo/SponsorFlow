@props(['filled' => false])

<svg
    fill-rule="{{ $filled ? 'evenodd' : 'nonzero' }}"
    {{ $attributes->merge([
        'viewBox' => '0 0 24 24',
        'fill' => $filled ? 'currentColor' : 'none',
        'stroke' => $filled ? 'none' : 'currentColor',
        'stroke-width' => '2',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
        'style' => 'width:24px;height:24px',
    ]) }}>
    @if ($filled)
        <path
            d="M5 2h14v3.414L13.414 12 19 17.586V22H5v-4.414L10.586 12 5 5.414V2zm2 2v2.586L13 13l-6 6.414V20h10v-.586L11 13l6-6.414V4H7z" />
    @else
        <path d="M5 22h14" />
        <path d="M5 2h14" />
        <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22" />
        <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
    @endif
</svg>