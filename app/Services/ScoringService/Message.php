<?php

namespace App\Services\ScoringService;

use App\Enums\ScoreWord\WordScoreWeights;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Onnov\DetectEncoding\EncodingDetector;
use Wkhooy\ObsceneCensorRus;
use function Amp\call;

class Message
{
    public string $message;
    public Collection $tokens;
    public Collection $occurrences;
    public Collection $words;

    public function __construct(string $message)
    {
        $this->message = $message;
        $this->tokens = collect();
        $this->occurrences = collect();
        $this->words = resolve(Store::class)->getCollection();
    }

    public function work(): array|false
    {
        return $this
            /* ЦЕНЗУРА */
            ->censoring()
            /* ФИКС КОДИРОВКИ */
            ->encode()
            /* ПРЕОБРАЗОВАНИЕ В НИЖНИЙ РЕГИСТР */
            ->lowercase()
            /* РАЗБИЕНИЕ НА ТОКЕНЫ */
            ->tokenize()
            /* ПРОВЕРКА НА ВХОЖДЕНИЯ */
            ->find()
            /* РЕЗУЛЬТАТ */
            ->summary();
    }

    private function censoring(): Message
    {
        if (!ObsceneCensorRus::isAllowed($this->message)) {
            Log::alert("Сообщение не прошло цензуру {$this->message}");
            $this->message = ObsceneCensorRus::getFiltered($this->message);
            Log::alert("Сообщение после цензуры {$this->message}");
        }

        return $this;
    }

    private function find(): Message
    {
        foreach ($this->tokens as $token) {
            foreach ($this->words as $score => $words) {
                if (
                    isset($words[$token]) ||
                    isset($words[mb_strtolower($token)]) ||
                    in_array($token, $words) ||
                    in_array(mb_strtolower($token), $words) //||
                    //stripos(implode(' ', $words), $token)
                ) {
                    $this->occurrences->push([
                        'word' => $token,
                        'score' => match ($score) {
                            'low' => WordScoreWeights::LOW->value,
                            'medium' => WordScoreWeights::MEDIUM->value,
                            'high' => WordScoreWeights::HIGH->value,
                        },
                    ]);

                    Log::debug("Совпадение {$token} ({$score})");
                }
            }
        }

        Log::debug("Всего совпадений: {$this->occurrences->count()}");

        return $this;
    }

    private function encode(): Message
    {
        $encoding = resolve(EncodingDetector::class)->getEncoding($this->message);

        if ($encoding !== EncodingDetector::UTF_8) {
            $this->message = resolve(EncodingDetector::class)->iconvXtoEncoding($this->message);
            Log::alert("Преобразование кодировки: {$encoding} -> UTF-8\n{$this->message}");
        }

        return $this;
    }

    private function lowercase(): Message
    {
        $this->message = mb_strtolower($this->message);
        Log::debug("Преобразование в нижний регистр\n{$this->message}");

        return $this;
    }

    private function tokenize(): Message
    {
        $this->tokens = collect(
            preg_split(
                "/(?:\s|^)([\-+]?)(?:(\")([^\"]+)\"|(')([^']+)'|()(\S+))/i",
                $this->message,
                -1,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            )
        )->map(function ($token) {
            if (empty($token)) return false;
            return strtok(trim($token), ' \n\t,.!?:;-+()');
        });

        Log::debug("Разбиение на токены:\n{$this->tokens->implode(', ')}");

        return $this;
    }

    private function summary()
    {
        if ($this->occurrences->count() === 0) return false;

        return [
            'words' => $this->occurrences->pluck('word')->unique()->toArray(),
            'scoring' => $this->occurrences->sum('score') / $this->occurrences->count(),
        ];
    }
}
