<?php

namespace App\Filament\Resources\Leads\Concerns;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use App\Support\Notes\NoteHistory;

trait SavesNotesInline
{
    public function saveNoteOnly(string $note): void
    {
        $note = trim($note);

        if ($note === '') {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $record = $this->getRecord();

        if (! $record instanceof Lead) {
            return;
        }

        $record->forceFill([
            'internal_notes' => NoteHistory::append(
                $record->internal_notes,
                $note,
                $user->name,
                $user->id,
            ),
        ])->save();
    }

    public function saveGuarantorNoteOnly(int $guarantorId, string $note): void
    {
        $note = trim($note);

        if ($note === '') {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $record = $this->getRecord();

        if (! $record instanceof Lead) {
            return;
        }

        $guarantor = $record->guarantors()->find($guarantorId);

        if (! $guarantor instanceof LeadGuarantor) {
            return;
        }

        $guarantor->forceFill([
            'internal_notes' => NoteHistory::append(
                $guarantor->internal_notes,
                $note,
                $user->name,
                $user->id,
            ),
        ])->save();
    }
}
