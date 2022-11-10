<?php

namespace App\Services\ScoreService;

use App\Models\Madeline\Userbot;
use App\Models\ScoreWord;
use Wkhooy\ObsceneCensorRus;

class Filter
{
    public static ?Filter $instance;

    private array $words;

    public function __construct()
    {
        $this->words = cache()->remember('words', 600, function () {
            return ScoreWord::get();
        });
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Функция фильтрации сообщения, вернёт false если сообщение не прошло фильтрацию
     * и напротив вернёт среднее арифметическое
     * @param string $message
     * @return bool|int
     */
    public static function filter(string $message): bool|int
    {
        $instance = self::getInstance();
        $words = $instance->words;

        /* фильтруем маты */
        if (!ObsceneCensorRus::isAllowed($message)) {
            return false;
        }

        /* начинаем считать баллы */
        $matches = collect([]);

        /* проходка по сообщению */
        foreach ($words as $word) {
            if (str_contains($message, $word->word)) {
                $matches->push($word);
            }
        }

        return round($matches->sum('score') / $matches->count());
    }

    /*  */

}