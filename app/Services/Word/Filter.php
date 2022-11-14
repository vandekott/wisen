<?php

namespace App\Services\Word;

use DonatelloZa\RakePlus\RakePlus;
use Illuminate\Support\Facades\Log;
use Mekras\Speller\Hunspell\Hunspell;
use Mekras\Speller\Source\StringSource;
use Wkhooy\ObsceneCensorRus;

class Filter
{
    public static ?Filter $instance = null;

    private Store $store;

    private ?Hunspell $hunspell;

    public function __construct()
    {
        $this->store = new Store();
        $this->hunspell = new Hunspell();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function run(string $message)
    {
        return self::getInstance()->filter($message);
    }

    /**
     * Функция фильтрации сообщения, вернёт false если сообщение не прошло фильтрацию
     * и напротив вернёт среднее арифметическое
     * @param string $message
     * @return bool|float
     */
    public function filter(string $message): bool|array
    {
        $message = str($message)->lower();
        Log::info("Фильтрация сообщения: {$message}");
        /* фильтруем маты */
        if (!ObsceneCensorRus::isAllowed($message)) {
            Log::info("Сообщение не прошло фильтрацию по матам");
            return false;
        }

        $spelled = collect($this->hunspell->checkText((new StringSource($message)), ['ru_RU']))
            ->each(function ($item) use (&$message) {
                if (count($item->suggestions) > 0) {
                    $message = str_replace($item->word, $item->suggestions[0], $message);
                }
            });

        Log::info("Сообщение после фильтрации по hunspell: {$message}");

        /*$keywords = (new RakePlus($spelled,'ru_RU', 3, true))
            ->keywords();*/

        $keywords = str($message)->toArray();

        $score = 0;
        $count = 0;
        $found = [];

        foreach ($keywords as $item) {
            if (in_array(str($item)->lower(), $this->store->getLow())) {
                $score++;
                $count++;
                $found[] = str($item)->lower();
                Log::info("Слово {$item} прошло фильтрацию по низкому рейтингу");
            } elseif (in_array(str($item)->lower(), $this->store->getMedium())) {
                $score += 2;
                $count++;
                Log::info("Слово {$item} прошло фильтрацию по среднему рейтингу");
                $found[] = str($item)->lower();
            } elseif (in_array(str($item)->lower(), $this->store->getHigh())) {
                $score += 3;
                $count++;
                Log::info("Слово {$item} прошло фильтрацию по высокому рейтингу");
                $found[] = str($item)->lower();
            }
        }

        if ($count === 0) {
            Log::info("Сообщение не содержит слов из базы");
            return false;
        }

        Log::info("Сообщение прошло фильтрацию, среднее арифметическое: " . $score / $count);

        return [
            'score' => $score / $count,
            'found' => $found
        ];
    }



}
