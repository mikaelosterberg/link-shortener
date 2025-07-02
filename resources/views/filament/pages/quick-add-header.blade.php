<!-- Quick Add Form -->
<div class="fi-section-content">
    <form wire:submit="quickAddLink">
        {{ $form }}
    </form>
</div>

<script>
document.addEventListener('livewire:initialized', () => {
    Livewire.on('focusShortUrl', () => {
        // Small delay to ensure form is reset first
        setTimeout(() => {
            const shortUrlField = document.querySelector('input[wire\\:model="quickAddData.custom_slug"]');
            if (shortUrlField) {
                shortUrlField.focus();
            }
        }, 100);
    });
});
</script>

<!-- Original Header with Actions -->
<div class="flex flex-col gap-y-6 py-6">
    <div class="flex flex-col gap-y-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                Links
            </h1>
        </div>
        @if($headerActions)
            <div class="fi-ac gap-3 flex flex-wrap items-center justify-start">
                @foreach($headerActions as $action)
                    {{ $action }}
                @endforeach
            </div>
        @endif
    </div>
</div>