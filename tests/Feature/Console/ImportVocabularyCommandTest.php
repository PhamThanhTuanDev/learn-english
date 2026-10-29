<?php

namespace Tests\Feature\Console;

use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportVocabularyCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = ['word', 'pronunciation', 'meaning', 'description', 'description_vi', 'example_sentence', 'example_vi', 'fixed_phrase', 'related_words', 'notes', 'type', 'level'];

    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->files);

        parent::tearDown();
    }

    public function test_it_imports_rows_and_normalizes_levels_and_parts_of_speech(): void
    {
        $path = $this->csv([
            ['better', '/ˈbetə(r)/', 'hơn, tốt hơn', 'of a higher standard', 'có tiêu chuẩn cao hơn', 'We hope for better weather.', 'Mong thời tiết tốt hơn.', '', '', '', 'adjective', 'A1'],
            ['briefcase', '/ˈbriːfkeɪs/', 'cặp tài liệu', '', '', '', '', '', '', '', 'noun', 'undefined'],
            ['short-form content', '', 'nội dung ngắn', '', '', '', '', '', '', '', 'noun phrase', 'intermediate'],
            ['overview', '', 'tổng quan', '', '', '', '', '', '', '', 'noun', 'Symbol'],
            ['jeans', '', 'quần bò', '', '', '', '', '', '', '', '', ''],
            [' itemize ', '', 'liệt kê', '', '', '', '', '', '', '', 'undefined', 'b2'],
        ]);

        $this->artisan('app:import-vocabulary', ['path' => $path])
            ->expectsOutput('Đã nhập 6 từ, bỏ qua 0 dòng.')
            ->assertSuccessful();

        $better = Word::where('word', 'better')->sole();
        $this->assertSame('adjective', $better->part_of_speech);
        $this->assertSame('/ˈbetə(r)/', $better->pronunciation);
        $this->assertSame('hơn, tốt hơn', $better->meaning);
        $this->assertSame('of a higher standard', $better->definition);
        $this->assertSame('có tiêu chuẩn cao hơn', $better->definition_vi);
        $this->assertSame('We hope for better weather.', $better->example);
        $this->assertSame('Mong thời tiết tốt hơn.', $better->example_vi);
        $this->assertSame('A1', $better->level);

        $this->assertSame([
            'better' => 'A1',
            'briefcase' => null,
            'itemize' => 'B2',
            'jeans' => null,
            'overview' => null,
            'short-form content' => null,
        ], Word::orderBy('word')->pluck('level', 'word')->all());
        $this->assertSame('', Word::where('word', 'jeans')->value('part_of_speech'));
        $this->assertSame('', Word::where('word', 'itemize')->value('part_of_speech'));
        $this->assertNull(Word::where('word', 'jeans')->value('pronunciation'));
    }

    public function test_running_it_again_updates_rows_in_place_and_keeps_dropped_words(): void
    {
        $this->artisan('app:import-vocabulary', ['path' => $this->csv([
            ['better', '', 'hơn', '', '', '', '', '', '', '', 'adjective', 'A1'],
            ['better', '', 'tốt hơn', '', '', '', '', '', '', '', 'adverb', 'A2'],
        ])])->assertSuccessful();
        $id = Word::where('word', 'better')->where('part_of_speech', 'adjective')->value('id');

        $this->artisan('app:import-vocabulary', ['path' => $this->csv([
            ['better', '/ˈbetə(r)/', 'hơn, tốt hơn', '', '', '', '', '', '', '', 'adjective', 'B1'],
        ])])->assertSuccessful();

        $this->assertSame(2, Word::count());
        $better = Word::findOrFail($id);
        $this->assertSame('hơn, tốt hơn', $better->meaning);
        $this->assertSame('/ˈbetə(r)/', $better->pronunciation);
        $this->assertSame('B1', $better->level);
        $this->assertSame('tốt hơn', Word::where('part_of_speech', 'adverb')->value('meaning'));
    }

    public function test_it_skips_incomplete_rows_and_keeps_the_last_duplicate(): void
    {
        $path = $this->csv([
            ['', '', 'không có từ', '', '', '', '', '', '', '', 'noun', 'A1'],
            ['cup', '', '', '', '', '', '', '', '', '', 'noun', 'A1'],
            ['cup', 'tách'],
            ['cup', '', 'tách', '', '', '', '', '', '', '', 'noun', 'A1'],
            ['cup', '', 'tách, chén', '', '', '', '', '', '', '', 'noun', 'A1'],
        ]);

        $this->artisan('app:import-vocabulary', ['path' => $path])
            ->expectsOutput('Đã nhập 1 từ, bỏ qua 3 dòng.')
            ->assertSuccessful();

        $this->assertSame('tách, chén', Word::sole()->meaning);
    }

    public function test_it_fails_without_a_readable_file_or_the_required_columns(): void
    {
        $this->artisan('app:import-vocabulary', ['path' => '/missing/vocabulary.csv'])
            ->expectsOutput('Không đọc được file: /missing/vocabulary.csv')
            ->assertFailed();

        $this->artisan('app:import-vocabulary', ['path' => $this->csv([['cup', 'noun']], ['word', 'type'])])
            ->expectsOutput('File thiếu cột: meaning')
            ->assertFailed();

        $this->assertSame(0, Word::count());
    }

    public function test_it_imports_the_bundled_csv(): void
    {
        $this->artisan('app:import-vocabulary')
            ->expectsOutput('Đã nhập 7463 từ, bỏ qua 0 dòng.')
            ->assertSuccessful();

        $this->assertSame(7463, Word::count());
        $this->assertEqualsCanonicalizing(
            ['A1', 'A2', 'B1', 'B2', 'C1', 'C2', null],
            Word::distinct()->pluck('level')->all(),
        );
    }

    /**
     * Write rows to a temporary CSV file with a UTF-8 BOM, like the exported file.
     *
     * @param  list<list<string>>  $rows
     * @param  list<string>  $header
     */
    private function csv(array $rows, array $header = self::HEADER): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'vocabulary');
        $this->files[] = $path;

        $handle = fopen($path, 'w');
        $this->assertNotFalse($handle);
        fwrite($handle, "\xEF\xBB\xBF");
        foreach ([$header, ...$rows] as $row) {
            fputcsv($handle, $row, escape: '');
        }
        fclose($handle);

        return $path;
    }
}
