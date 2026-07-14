<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\LeadService;
use App\Support\Lead\ClientHistoryLookup;
use Illuminate\Console\Command;

class BackfillLeadClientData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'leads:backfill-client-data
        {leads?* : ID на заявка(и), чиито празни лични данни да се попълнят от историята на клиента}
        {--all : Обработва еднократно всички съществуващи заявки}';

    /**
     * @var string
     */
    protected $description = 'Попълва празните лични данни на съществуващи заявки от предишните заявки на същия клиент (съвпадащи телефон и имена)';

    public function handle(LeadService $leadService): int
    {
        $leadIds = $this->argument('leads');

        if ($this->option('all') && $leadIds !== []) {
            $this->error('Използвайте или конкретни ID-та, или --all, но не двете едновременно.');

            return self::FAILURE;
        }

        if (! $this->option('all') && $leadIds === []) {
            $this->error('Посочете ID на заявка или използвайте --all.');

            return self::FAILURE;
        }

        if ($this->option('all')) {
            return $this->backfillAllLeads($leadService);
        }

        return $this->backfillLeadsById($leadService, $leadIds);
    }

    /**
     * @param  array<int, string>  $leadIds
     */
    private function backfillLeadsById(LeadService $leadService, array $leadIds): int
    {
        $exitCode = self::SUCCESS;

        foreach ($leadIds as $leadId) {
            $lead = Lead::query()->find($leadId);

            if (! $lead instanceof Lead) {
                $this->error("Заявка #{$leadId} не е намерена.");
                $exitCode = self::FAILURE;

                continue;
            }

            $filledFields = $leadService->backfillClientDataFromHistory($lead);

            if ($filledFields === []) {
                $this->warn(
                    "Заявка #{$lead->id}: няма данни за попълване"
                    .' (полетата са попълнени, липсва предишна заявка или имената не съвпадат).',
                );

                continue;
            }

            $this->info("Заявка #{$lead->id}: попълнени полета — ".$this->formatFieldLabels($filledFields).'.');
        }

        return $exitCode;
    }

    private function backfillAllLeads(LeadService $leadService): int
    {
        $backfilledCount = 0;

        Lead::query()
            ->orderBy('id')
            ->chunkById(100, function ($leads) use ($leadService, &$backfilledCount): void {
                foreach ($leads as $lead) {
                    $filledFields = $leadService->backfillClientDataFromHistory($lead);

                    if ($filledFields === []) {
                        continue;
                    }

                    $backfilledCount++;
                    $this->info("Заявка #{$lead->id}: попълнени полета — ".$this->formatFieldLabels($filledFields).'.');
                }
            });

        $this->info("Готово: попълнени данни по {$backfilledCount} заявки.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $filledFields
     */
    private function formatFieldLabels(array $filledFields): string
    {
        return implode(', ', array_map(
            static fn (string $field): string => ClientHistoryLookup::BACKFILL_FIELD_LABELS[$field] ?? $field,
            array_keys($filledFields),
        ));
    }
}
