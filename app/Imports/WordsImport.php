<?php

namespace App\Imports;

use App\Enums\ScoreWord\WordScoreWeights;
use App\Models\Telegram\Word;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;

class WordsImport implements ToModel, WithUpserts, WithUpsertColumns
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Word([
            'word' => trim($row[0]),
            'score' => WordScoreWeights::tryFrom(((int) $row[1])) ?? WordScoreWeights::MEDIUM,
        ]);
    }

    public function uniqueBy()
    {
        return 'word';
    }

    public function upsertColumns(): array
    {
        return ['score'];
    }
}
