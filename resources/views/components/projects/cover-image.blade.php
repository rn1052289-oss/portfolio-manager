@props([
    'project',
    'variant' => 'table',
])

@php
    $allowedVariants = [
        'table',
        'card',
        'detail',
    ];

    $resolvedVariant = in_array($variant, $allowedVariants, true)
        ? $variant
        : 'table';

    $disk = \Illuminate\Support\Facades\Storage::disk('public');

    $coverImagePath = $project->cover_image_path;

    $hasCoverImage = filled($coverImagePath)
        && $disk->exists($coverImagePath);

    $coverImageUrl = $hasCoverImage
        ? $disk->url($coverImagePath)
        : null;
@endphp

<div
    {{ $attributes->class([
        'project-cover',
        'project-cover--' . $resolvedVariant,
        'project-cover--fallback' => ! $hasCoverImage,
    ]) }}
>
    @if ($hasCoverImage)
        <img
            src="{{ $coverImageUrl }}"
            alt="{{ $project->title }}の案件画像"
            class="project-cover__image"
            @if ($resolvedVariant !== 'detail')
                loading="lazy"
            @endif
            decoding="async"
        >
    @else
        <span
            class="project-cover__fallback-icon"
            aria-hidden="true"
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <rect
                    x="3"
                    y="4"
                    width="18"
                    height="16"
                    rx="2"
                ></rect>

                <circle
                    cx="8.5"
                    cy="9"
                    r="1.5"
                ></circle>

                <path d="m21 15-5-5L5 20"></path>
            </svg>
        </span>

        <span class="project-cover__fallback-text">
            画像未設定
        </span>
    @endif
</div>