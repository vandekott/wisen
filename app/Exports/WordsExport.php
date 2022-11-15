<?php

namespace App\Exports;

use App\Models\Tas\Word;
use Maatwebsite\Excel\Concerns\FromCollection;

class WordsExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Word::all()->map(fn($word) => [
            'word' => $word->word,
            'score' => $word->score->value,
        ]);
    }
}
