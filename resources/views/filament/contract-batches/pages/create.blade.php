<x-filament-panels::page>
    <form wire:submit="create" class="cz-cb-form">
        @include('filament.contract-batches.partials.step-one', ['layout' => $data['document_layout'] ?? null])

        <div class="cz-cb-actions">
            <button type="submit" class="cz-btn cz-btn-primary">Към Документи</button>
            <a href="{{ \App\Filament\Resources\ContractBatches\ContractBatchResource::getUrl('index') }}" class="cz-btn cz-btn-ghost">Отказ</a>
        </div>
    </form>
</x-filament-panels::page>
