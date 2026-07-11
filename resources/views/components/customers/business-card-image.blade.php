@props([
    'path' => null,
    'side' => '名刺',
    'alt' => '名刺画像',
])

@php
    $directory = 'customers/business-cards/';

    $isManagedPath = is_string($path)
        && $path !== ''
        && str_starts_with($path, $directory);

    $imageExists = $isManagedPath
        && \Illuminate\Support\Facades\Storage::disk('public')
            ->exists($path);

    $fallbackText = $path
        ? '画像ファイルが見つかりません'
        : "{$side}は未登録です";
@endphp

<figure class="business-card-preview">
    <div class="business-card-preview__media">
        @if ($imageExists)
            <img
                src="{{ asset('storage/' . $path) }}"
                alt="{{ $alt }}"
                class="business-card-preview__image"
                loading="lazy"
            >
        @else
            <div
                class="business-card-preview__fallback"
                role="img"
                aria-label="{{ $fallbackText }}"
            >
                <svg
                    class="business-card-preview__fallback-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <rect
                        x="3"
                        y="5"
                        width="18"
                        height="14"
                        rx="2"
                    ></rect>

                    <path d="M7 10h4"></path>
                    <path d="M7 13h7"></path>
                    <path d="M16 9h2"></path>
                    <path d="M16 12h2"></path>
                </svg>

                <p class="business-card-preview__fallback-text">
                    {{ $fallbackText }}
                </p>
            </div>
        @endif
    </div>

    <figcaption class="business-card-preview__caption">
        <span class="business-card-preview__side">
            {{ $side }}
        </span>

        @if ($imageExists)
            <span class="business-card-preview__status business-card-preview__status--registered">
                登録済み
            </span>
        @elseif ($path)
            <span class="business-card-preview__status business-card-preview__status--warning">
                要確認
            </span>
        @else
            <span class="business-card-preview__status">
                未登録
            </span>
        @endif
    </figcaption>
</figure>