<?php

namespace App\Support\Notes;

use Illuminate\Support\Str;

class NoteHistory
{
    private const VERSION = 1;

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $entries = static::parseStructuredPayload($value);

        if ($entries !== null) {
            return static::serializeEntries($entries);
        }

        return static::normalizePlainText($value);
    }

    public static function append(
        ?string $existingNotes,
        ?string $newNote,
        ?string $authorName = null,
        ?int $authorId = null,
    ): ?string {
        $normalizedNewNote = static::normalizePlainText($newNote);

        if ($normalizedNewNote === null) {
            return static::serializeEntries(static::entries($existingNotes));
        }

        $entries = static::entries($existingNotes);
        $entries[] = static::buildEntry(
            body: $normalizedNewNote,
            author: trim((string) $authorName) !== '' ? trim((string) $authorName) : 'Служител',
            authorId: $authorId,
            timestamp: now('Europe/Sofia')->format('d.m.Y H:i'),
        );

        return static::serializeEntries($entries);
    }

    public static function replace(
        ?string $existingNotes,
        array $entries,
        ?string $editedByName = null,
        ?int $actorId = null,
    ): ?string {
        $currentEntries = collect(static::entries($existingNotes))->keyBy('id');
        $normalizedEntries = [];

        foreach (static::normalizeEntries($entries) as $entry) {
            $existingEntry = $currentEntries->get($entry['id']);

            if (! is_array($existingEntry)) {
                continue;
            }

            $canEditEntry = static::canEditEntry($existingEntry, $actorId, $editedByName);
            $updatedBody = $canEditEntry ? $entry['body'] : $existingEntry['body'];

            $normalizedEntry = static::buildEntry(
                body: $updatedBody,
                author: $existingEntry['author'] ?? null,
                authorId: $existingEntry['author_id'] ?? null,
                timestamp: $existingEntry['timestamp'] ?? null,
                id: $existingEntry['id'] ?? null,
                editedAt: $existingEntry['edited_at'] ?? null,
                editedBy: $existingEntry['edited_by'] ?? null,
            );

            if (($existingEntry['body'] ?? null) !== $updatedBody) {
                $normalizedEntry['edited_at'] = now('Europe/Sofia')->format('d.m.Y H:i');
                $normalizedEntry['edited_by'] = trim((string) $editedByName) !== '' ? trim((string) $editedByName) : null;
            }

            $normalizedEntries[] = $normalizedEntry;
        }

        return static::serializeEntries($normalizedEntries);
    }

    public static function deleteEntry(
        ?string $existingNotes,
        int $entryIndex,
        ?int $actorId = null,
        ?string $actorName = null,
    ): ?string {
        $entries = static::entries($existingNotes);

        if (! isset($entries[$entryIndex])) {
            return $existingNotes;
        }

        if (! static::canEditEntry($entries[$entryIndex], $actorId, $actorName)) {
            return $existingNotes;
        }

        array_splice($entries, $entryIndex, 1);

        return static::serializeEntries($entries);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    public static function canEditEntry(array $entry, ?int $actorId, ?string $actorName): bool
    {
        $entryAuthorId = static::normalizeMetaInteger($entry['author_id'] ?? null);
        $normalizedEntryAuthor = static::normalizeComparableText($entry['author'] ?? null);
        $normalizedActorName = static::normalizeComparableText($actorName);

        if ($entryAuthorId !== null && $actorId !== null && $entryAuthorId === $actorId) {
            return true;
        }

        return $normalizedEntryAuthor !== null
            && $normalizedActorName !== null
            && $normalizedEntryAuthor === $normalizedActorName;
    }

    /**
     * @return array<int, array{id: string, timestamp: ?string, author: ?string, author_id: ?int, body: string, edited_at: ?string, edited_by: ?string}>
     */
    public static function entries(?string $notes): array
    {
        $structuredEntries = static::parseStructuredPayload($notes);

        if ($structuredEntries !== null) {
            return static::normalizeEntries($structuredEntries);
        }

        $normalizedNotes = static::normalizePlainText($notes);

        if ($normalizedNotes === null) {
            return [];
        }

        $chunks = preg_split("/\n{2,}/u", $normalizedNotes) ?: [];

        return array_values(array_filter(array_map(static function (string $chunk, int $index): ?array {
            $chunk = trim($chunk);

            if ($chunk === '') {
                return null;
            }

            if (preg_match('/^\[(?<timestamp>[^\]]+)\]\s+(?<author>[^:]+):\s*(?<body>.*)$/us', $chunk, $matches) === 1) {
                return static::buildEntry(
                    body: trim((string) $matches['body']),
                    author: trim((string) $matches['author']) ?: null,
                    timestamp: trim((string) $matches['timestamp']) ?: null,
                    id: 'legacy-'.$index,
                );
            }

            return static::buildEntry(
                body: $chunk,
                id: 'legacy-'.$index,
            );
        }, $chunks, array_keys($chunks))));
    }

    /**
     * @return array<int, array{id: string, timestamp: ?string, author: ?string, author_id: ?int, body: string, edited_at: ?string, edited_by: ?string}>
     */
    public static function formEntries(?string $notes): array
    {
        return static::entries($notes);
    }

    /**
     * @return array{timestamp: ?string, author: ?string, author_id?: ?int, body: string, id?: string, edited_at?: ?string, edited_by?: ?string}|null
     */
    public static function latestEntry(?string $notes): ?array
    {
        $entries = static::entries($notes);

        if ($entries === []) {
            return null;
        }

        return $entries[array_key_last($entries)];
    }

    public static function latestPreview(?string $notes, int $limit = 140): ?string
    {
        $entry = static::latestEntry($notes);

        if ($entry === null) {
            return null;
        }

        $preview = preg_replace('/\s+/u', ' ', trim($entry['body'])) ?? trim($entry['body']);

        if ($preview === '') {
            return null;
        }

        if (mb_strlen($preview) <= $limit) {
            return $preview;
        }

        return rtrim(mb_substr($preview, 0, $limit - 1)).'…';
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public static function serializeEntries(array $entries): ?string
    {
        $normalizedEntries = static::normalizeEntries($entries);

        if ($normalizedEntries === []) {
            return null;
        }

        return json_encode([
            'version' => self::VERSION,
            'entries' => $normalizedEntries,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array{id: string, timestamp: ?string, author: ?string, author_id: ?int, body: string, edited_at: ?string, edited_by: ?string}>
     */
    public static function normalizeEntries(array $entries): array
    {
        $normalizedEntries = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $body = static::normalizePlainText(isset($entry['body']) ? (string) $entry['body'] : null);

            if ($body === null) {
                continue;
            }

            $normalizedEntries[] = static::buildEntry(
                body: $body,
                author: isset($entry['author']) ? static::normalizeMetaText($entry['author']) : null,
                authorId: static::normalizeMetaInteger($entry['author_id'] ?? null),
                timestamp: isset($entry['timestamp']) ? static::normalizeMetaText($entry['timestamp']) : null,
                id: isset($entry['id']) && filled($entry['id']) ? (string) $entry['id'] : null,
                editedAt: isset($entry['edited_at']) ? static::normalizeMetaText($entry['edited_at']) : null,
                editedBy: isset($entry['edited_by']) ? static::normalizeMetaText($entry['edited_by']) : null,
            );
        }

        return $normalizedEntries;
    }

    /**
     * @return array<int, array{id: string, timestamp: ?string, author: ?string, author_id: ?int, body: string, edited_at: ?string, edited_by: ?string}>|null
     */
    private static function parseStructuredPayload(?string $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmedValue = trim($value);

        if ($trimmedValue === '' || ! str_starts_with($trimmedValue, '{')) {
            return null;
        }

        try {
            $payload = json_decode($trimmedValue, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['entries']) || ! is_array($payload['entries'])) {
            return null;
        }

        return $payload['entries'];
    }

    private static function normalizePlainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = preg_replace('/<br\s*\/?>/iu', "\n", $value) ?? $value;
        $text = preg_replace('/<\/p>/iu', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\/div>/iu', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
        $text = preg_replace("/\r\n|\r/u", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
        $text = trim($text);

        return $text !== '' ? $text : null;
    }

    private static function normalizeMetaText(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }

    private static function normalizeMetaInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private static function normalizeComparableText(mixed $value): ?string
    {
        $text = static::normalizeMetaText($value);

        return $text !== null ? mb_strtolower($text) : null;
    }

    /**
     * @return array{id: string, timestamp: ?string, author: ?string, author_id: ?int, body: string, edited_at: ?string, edited_by: ?string}
     */
    private static function buildEntry(
        string $body,
        ?string $author = null,
        ?int $authorId = null,
        ?string $timestamp = null,
        ?string $id = null,
        ?string $editedAt = null,
        ?string $editedBy = null,
    ): array {
        return [
            'id' => $id ?? (string) Str::uuid(),
            'timestamp' => $timestamp,
            'author' => $author,
            'author_id' => $authorId,
            'body' => $body,
            'edited_at' => $editedAt,
            'edited_by' => $editedBy,
        ];
    }
}
