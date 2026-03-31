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

    public function editNoteEntry(int $entryIndex, string $newBody): void
    {
        $newBody = trim($newBody);

        if ($newBody === '') {
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

        $entries = NoteHistory::entries($record->internal_notes);

        if (! isset($entries[$entryIndex])) {
            return;
        }

        $entries[$entryIndex]['body'] = $newBody;

        $record->forceFill([
            'internal_notes' => NoteHistory::replace(
                $record->internal_notes,
                $entries,
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

    public function editGuarantorNoteEntry(int $guarantorId, int $entryIndex, string $newBody): void
    {
        $newBody = trim($newBody);

        if ($newBody === '') {
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

        $entries = NoteHistory::entries($guarantor->internal_notes);

        if (! isset($entries[$entryIndex])) {
            return;
        }

        $entries[$entryIndex]['body'] = $newBody;

        $guarantor->forceFill([
            'internal_notes' => NoteHistory::replace(
                $guarantor->internal_notes,
                $entries,
                $user->name,
                $user->id,
            ),
        ])->save();
    }
}
