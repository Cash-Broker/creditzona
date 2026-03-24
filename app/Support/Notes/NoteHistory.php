<?php

namespace App\Support\Notes;

class NoteHistory
{
    public static function normalize(?string $value): ?string
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

    public static function append(?string $existingNotes, ?string $newNote, ?string $authorName = null): ?string
    {
        $normalizedNewNote = static::normalize($newNote);

        if ($normalizedNewNote === null) {
            return static::normalize($existingNotes);
        }

        $entry = sprintf(
            '[%s] %s: %s',
            now('Europe/Sofia')->format('d.m.Y H:i'),
            trim((string) $authorName) !== '' ? trim((string) $authorName) : 'Служител',
            $normalizedNewNote,
        );

        $normalizedExistingNotes = static::normalize($existingNotes);

        if ($normalizedExistingNotes === null) {
            return $entry;
        }

        return $normalizedExistingNotes."\n\n".$entry;
    }

    /**
     * @return array<int, array{timestamp: ?string, author: ?string, body: string}>
     */
    public static function entries(?string $notes): array
    {
        $normalizedNotes = static::normalize($notes);

        if ($normalizedNotes === null) {
            return [];
        }

        $chunks = preg_split("/\n{2,}/u", $normalizedNotes) ?: [];

        return array_values(array_filter(array_map(static function (string $chunk): ?array {
            $chunk = trim($chunk);

            if ($chunk === '') {
                return null;
            }

            if (preg_match('/^\[(?<timestamp>[^\]]+)\]\s+(?<author>[^:]+):\s*(?<body>.*)$/us', $chunk, $matches) === 1) {
                return [
                    'timestamp' => trim((string) $matches['timestamp']) ?: null,
                    'author' => trim((string) $matches['author']) ?: null,
                    'body' => trim((string) $matches['body']),
                ];
            }

            return [
                'timestamp' => null,
                'author' => null,
                'body' => $chunk,
            ];
        }, $chunks)));
    }

    /**
     * @return array{timestamp: ?string, author: ?string, body: string}|null
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
}
