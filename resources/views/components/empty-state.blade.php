<div {{ $attributes->merge(['class' => 'sv-empty-state text-center']) }}>
    <div class="display-6 mb-2">{{ $icon ?? '—' }}</div>
    <h2 class="h5">{{ $title }}</h2>
    <p class="text-muted mb-0">{{ $message }}</p>
</div>
