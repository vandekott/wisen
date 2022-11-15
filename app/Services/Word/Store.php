<?php

namespace App\Services\Word;

use App\Enums\ScoreWord\WordScoreWeights;
use App\Models\Tas\Word;
use Brick\VarExporter\ExportException;
use Brick\VarExporter\VarExporter;
use Goodby\CSV\Export\Standard\CsvFileObject;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;

/* Хранение слов в файлах, ускорение за счёт OPCache */
class Store
{
    private string $lowFile;
    private array $lowWords = [];
    private string $mediumFile;
    private array $mediumWords = [];
    private string $highFile;
    private array $highWords = [];

    public function __construct()
    {
        if (!is_dir(storage_path('app/words')) || !file_exists(storage_path('app/words'))) {
            mkdir(storage_path('app/words'));
        }

        $this->lowFile = storage_path('app/words/low.php');
        if (!file_exists($this->lowFile))
            touch($this->lowFile) && file_put_contents($this->lowFile, '<?php return [];');
        $this->lowWords = include $this->lowFile;

        $this->mediumFile = storage_path('app/words/medium.php');
        if (!file_exists($this->mediumFile))
            touch($this->mediumFile) && file_put_contents($this->mediumFile, '<?php return [];');
        $this->mediumWords = include $this->mediumFile;

        $this->highFile = storage_path('app/words/high.php');
        if (!file_exists($this->highFile))
            touch($this->highFile) && file_put_contents($this->highFile, '<?php return [];');
        $this->highWords = include $this->highFile;
    }

    public function update(): bool
    {
        $low = $this->set(
            Word::where('score', WordScoreWeights::LOW->value)->pluck('word')
                ->map(fn($word) => (string) str($word)->lower())
                ->toArray(),
            $this->lowFile
        );

        $medium = $this->set(
            Word::where('score', WordScoreWeights::MEDIUM->value)->pluck('word')
                ->map(fn($word) => (string) str($word)->lower())
                ->toArray(),
            $this->mediumFile
        );

        $high = $this->set(
            Word::where('score', WordScoreWeights::HIGH->value)->pluck('word')
                ->map(fn($word) => (string) str($word)->lower())
                ->toArray(),
            $this->highFile
        );

        return $low && $medium && $high;

    }

    public function getLow(): array
    {
        return $this->lowWords;
    }

    public function getMedium(): array
    {
        return $this->mediumWords;
    }

    public function getHigh(): array
    {
        return $this->highWords;
    }

    /**
     * @throws ExportException
     */
    private function set(array $words, string $file): bool
    {
        return file_put_contents($file, '<?php return ' . VarExporter::export($words, VarExporter::INLINE_ARRAY) . ';');
    }

    public function import($data): bool
    {

    }

    public function export()
    {
        $config = new ExporterConfig();

        $exporter = new Exporter($config);

        $words = Word::all()->map(fn($i) => [$i->word, $i->score->value])->toArray();

        return response()
            ->header('Content-Type', 'text/csv')
            ->streamDownload(function () use ($exporter, $words) {
                $exporter->export('php://output', $words);
            }, "words.csv");
    }

}
