<?php

namespace App\Filament\Resources\Leads\Widgets;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use App\Support\Notes\NoteHistory;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class NoteHistoryChatWidget extends Widget
{
    protected string $view = 'filament.resources.leads.widgets.note-history-chat-widget';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public ?int $leadId = null;

    public ?int $guarantorId = null;

    public string $newMessage = '';

    public ?int $editingIndex = null;

    public string $editingBody = '';

    public function mount(?int $leadId = null, ?int $guarantorId = null): void
    {
        $this->leadId = $leadId;
        $this->guarantorId = $guarantorId;
    }

    public function send(): void
    {
        $note = trim($this->newMessage);

        if ($note === '') {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $owner = $this->resolveNoteOwner();

        if ($owner === null) {
            return;
        }

        $owner->forceFill([
            'internal_notes' => NoteHistory::append(
                $owner->internal_notes,
                $note,
                $user->name,
                $user->id,
            ),
        ])->save();

        $this->newMessage = '';
    }

    public function startEditing(int $index): void
    {
        $owner = $this->resolveNoteOwner();

        if ($owner === null) {
            return;
        }

        $entries = NoteHistory::entries($owner->internal_notes);

        if (! isset($entries[$index])) {
            return;
        }

        if (! NoteHistory::canEditEntry($entries[$index], Auth::id(), Auth::user()?->name)) {
            return;
        }

        $this->editingIndex = $index;
        $this->editingBody = $entries[$index]['body'];
    }

    public function cancelEditing(): void
    {
        $this->editingIndex = null;
        $this->editingBody = '';
    }

    public function saveEdit(): void
    {
        if ($this->editingIndex === null) {
            return;
        }

        $body = trim($this->editingBody);

        if ($body === '') {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $owner = $this->resolveNoteOwner();

        if ($owner === null) {
            return;
        }

        $entries = NoteHistory::entries($owner->internal_notes);

        if (! isset($entries[$this->editingIndex])) {
            return;
        }

        $entries[$this->editingIndex]['body'] = $body;

        $owner->forceFill([
            'internal_notes' => NoteHistory::replace(
                $owner->internal_notes,
                $entries,
                $user->name,
                $user->id,
            ),
        ])->save();

        $this->editingIndex = null;
        $this->editingBody = '';
    }

    public function deleteEntry(int $index): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $owner = $this->resolveNoteOwner();

        if ($owner === null) {
            return;
        }

        $owner->forceFill([
            'internal_notes' => NoteHistory::deleteEntry(
                $owner->internal_notes,
                $index,
                $user->id,
                $user->name,
            ),
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $owner = $this->resolveNoteOwner();
        $notes = $owner?->internal_notes;
        $entries = NoteHistory::entries($notes);
        $currentUserId = Auth::id();
        $currentUserName = Auth::user()?->name;

        $messages = [];

        foreach ($entries as $index => $entry) {
            $authorId = $entry['author_id'] ?? null;
            $authorName = $entry['author'] ?? 'Служител';

            $isMe = ($authorId !== null && $currentUserId !== null && $authorId === $currentUserId)
                || (
                    $currentUserName !== null
                    && mb_strtolower(trim($authorName)) === mb_strtolower(trim($currentUserName))
                );

            $messages[] = [
                'index' => $index,
                'body' => $entry['body'],
                'author' => $authorName,
                'letter' => mb_strtoupper(mb_substr($authorName, 0, 1)),
                'timestamp' => $entry['timestamp'] ?? '',
                'isMe' => $isMe,
                'canEdit' => $isMe && NoteHistory::canEditEntry($entry, $currentUserId, $currentUserName),
                'editedAt' => $entry['edited_at'] ?? null,
                'editedBy' => $entry['edited_by'] ?? null,
            ];
        }

        return [
            'messages' => $messages,
        ];
    }

    private function resolveNoteOwner(): Lead|LeadGuarantor|null
    {
        if ($this->guarantorId !== null) {
            return LeadGuarantor::find($this->guarantorId);
        }

        if ($this->leadId !== null) {
            return Lead::find($this->leadId);
        }

        return null;
    }
}
