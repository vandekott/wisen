<?php

namespace App\Models;

use App\Enums\ScoreWord\ScoreWordType;
use App\Enums\ScoreWord\ScoreWordWeights;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreWord extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'word',
        'score',
    ];

    protected $casts = [
        'score' => ScoreWordWeights::class,
    ];
}
