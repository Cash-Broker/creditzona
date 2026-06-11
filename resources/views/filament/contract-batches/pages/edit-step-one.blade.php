<x-filament-panels::page>
    <form wire:submit="save" class="cz-cb-form">
        @include('filament.contract-batches.partials.step-one', ['layout' => $data['document_layout'] ?? null])

        <div class="cz-cb-actions">
            <button type="submit" class="cz-btn cz-btn-primary">Към Документи</button>
            <button type="button" class="cz-btn cz-btn-danger" wire:click="mountAction('deleteFromForm')">Изтрий</button>
        </div>
    </form>
</x-filament-panels::page>
