<?php

namespace App\Services\Word;

use App\Enums\ScoreWord\WordScoreWeights;
use App\Models\Tas\Word;
use Brick\VarExporter\ExportException;
use Brick\VarExporter\VarExporter;

/* Хранение слов в файлах, ускорение за счёт OPCache */
class Store
{
    private string $lowFile;
    private string $mediumFile;
    private string $highFile;

    public function __construct()
    {
        if (!is_dir(storage_path('app/words')) || !file_exists(storage_path('app/words'))) {
            mkdir(storage_path('app/words'));
        }

        $this->lowFile = storage_path('app/words/low.php');
        if (!file_exists($this->lowFile))
            touch($this->lowFile) && file_put_contents($this->lowFile, '<?php return [];');

        $this->mediumFile = storage_path('app/words/medium.php');
        if (!file_exists($this->mediumFile))
            touch($this->mediumFile) && file_put_contents($this->mediumFile, '<?php return [];');

        $this->highFile = storage_path('app/words/high.php');
        if (!file_exists($this->highFile))
            touch($this->highFile) && file_put_contents($this->highFile, '<?php return [];');
    }

    public function update(): bool
    {
        $low = $this->set(
            Word::where('score', WordScoreWeights::LOW->value)->pluck('word')->toArray(),
            $this->lowFile
        );

        $medium = $this->set(
            Word::where('score', WordScoreWeights::MEDIUM->value)->pluck('word')->toArray(),
            $this->mediumFile
        );

        $high = $this->set(
            Word::where('score', WordScoreWeights::HIGH->value)->pluck('word')->toArray(),
            $this->highFile
        );

        return $low && $medium && $high;

    }

    public function getLow(): array
    {
        return include $this->lowFile;
    }

    public function getMedium(): array
    {
        return include $this->mediumFile;
    }

    public function getHigh(): array
    {
        return include $this->highFile;
    }


    /**
     * @throws ExportException
     */
    private function set(array $words, string $file): bool
    {
        return file_put_contents($file, '<?php return ' . VarExporter::export($words, VarExporter::INLINE_ARRAY) . ';');
    }

}
