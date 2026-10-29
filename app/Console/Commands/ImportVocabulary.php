<?php

namespace App\Console\Commands;

use App\Models\Word;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:import-vocabulary
    {path? : CSV file (defaults to database/data/vocabulary.csv)}')]
#[Description('Load or update the word list from the vocabulary CSV')]
class ImportVocabulary extends Command
{
    private const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    /**
     * Rows are matched by word + part of speech and updated in place, so ids
     * never change and it can run on every deploy. Words that disappear from
     * the CSV are kept (D19).
     */
    public function handle(): int
    {
        $path = $this->argument('path');
        $path = is_string($path) ? $path : database_path('data/vocabulary.csv');

        $handle = is_readable($path) ? fopen($path, 'r') : false;
        if ($handle === false) {
            $this->error("Không đọc được file: {$path}");

            return self::FAILURE;
        }

        $header = fgetcsv($handle, escape: '') ?: [];
        $header = array_map(fn (?string $column) => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $column)), $header);
        $missing = array_diff(['word', 'meaning'], $header);
        if ($missing !== []) {
            fclose($handle);
            $this->error('File thiếu cột: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $words = [];
        $skipped = 0;
        while (($row = fgetcsv($handle, escape: '')) !== false) {
            if ($row === [null]) {
                continue;
            }

            $word = count($row) === count($header) ? $this->normalize(array_combine($header, $row)) : null;
            if ($word === null) {
                $skipped++;

                continue;
            }

            // The same word and part of speech twice: the last row wins.
            $words[mb_strtolower($word['word']).'|'.$word['part_of_speech']] = $word;
        }
        fclose($handle);

        DB::transaction(function () use ($words) {
            foreach (array_chunk(array_values($words), 500) as $chunk) {
                Word::upsert($chunk, ['word', 'part_of_speech'], [
                    'pronunciation', 'meaning', 'definition', 'definition_vi', 'example', 'example_vi', 'level',
                ]);
            }
        });

        $this->info(sprintf('Đã nhập %d từ, bỏ qua %d dòng.', count($words), $skipped));

        return self::SUCCESS;
    }

    /**
     * Map a CSV row to the words columns, or null when it has no word or
     * meaning.
     *
     * @param  array<string, string|null>  $row
     * @return array{word: string, part_of_speech: string, pronunciation: string|null, meaning: string, definition: string|null, definition_vi: string|null, example: string|null, example_vi: string|null, level: string|null}|null
     */
    private function normalize(array $row): ?array
    {
        $value = function (string $column) use ($row): ?string {
            $value = trim((string) ($row[$column] ?? ''));

            return $value === '' ? null : $value;
        };

        $word = $value('word');
        $meaning = $value('meaning');
        if ($word === null || $meaning === null) {
            return null;
        }

        $partOfSpeech = mb_strtolower($value('type') ?? '');
        $level = strtoupper($value('level') ?? '');

        return [
            'word' => $word,
            'part_of_speech' => $partOfSpeech === 'undefined' ? '' : $partOfSpeech,
            'pronunciation' => $value('pronunciation'),
            'meaning' => $meaning,
            'definition' => $value('description'),
            'definition_vi' => $value('description_vi'),
            'example' => $value('example_sentence'),
            'example_vi' => $value('example_vi'),
            // "undefined", "intermediate", "advanced", "Symbol"… are not CEFR levels.
            'level' => in_array($level, self::LEVELS, true) ? $level : null,
        ];
    }
}
