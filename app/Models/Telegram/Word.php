<?php

namespace App\Models\Telegram;

use App\Enums\ScoreWord\ScoreWordType;
use App\Enums\ScoreWord\WordScoreWeights;
use App\Services\ScoringService\Store;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'word',
        'score',
    ];

    protected $casts = [
        'score' => WordScoreWeights::class,
    ];

    protected $guarded = [];

    public function word(): Attribute
    {
        return new Attribute(
            get: fn($value) => mb_strtolower($value),
            set: fn($value) => mb_strtolower($value),
        );
    }

    protected static function boot()
    {
        parent::boot();
    }
}
