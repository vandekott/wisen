<?php

namespace App\Services\Word;

use DonatelloZa\RakePlus\RakePlus;
use Illuminate\Support\Facades\Log;
use Mekras\Speller\Hunspell\Hunspell;
use Mekras\Speller\Source\StringSource;
use Onnov\DetectEncoding\EncodingDetector;
use Wkhooy\ObsceneCensorRus;

class Filter
{
    private string $lowWords;
    private string $mediumWords;
    private string $highWords;

    private ?Hunspell $hunspell;
    private EncodingDetector $encoder;

    private ?string $message = '';
    private array $tokens = [];

    private array $occurrences = [];
    private int $occurrencesSummary = 0;

    public function __construct()
    {
        $this->lowWords = str(implode(' ', resolve(Store::class)->getLow()))->lower();
        $this->mediumWords = str(implode(' ', resolve(Store::class)->getMedium()))->lower();
        $this->highWords = str(implode(' ', resolve(Store::class)->getHigh()))->lower();

        $this->hunspell = new Hunspell();
        $this->encoder = new EncodingDetector();
    }

    /**
     * Запуск фильтрации сообщения
     * @param string $message
     * @return array|bool|float
     */
    public function run(string $message)
    {
        Log::info("Фильтрация сообщения: {$message}");

        $this->setMessage($message);

        if (!$this->censored()) return false;

        return $this
            // ->spell()
            ->ensureEncoding()
            ->ensureLowercase()
            ->tokenize()
            ->process();
    }

    /**
     * Функция фильтрации сообщения, вернёт false если сообщение не прошло фильтрацию
     * и напротив вернёт среднее арифметическое
     * @return bool|float
     */
    public function process(): bool|array
    {
        Log::info("Фильтрация сообщения: " . implode(', ', $this->tokens));

        foreach ($this->tokens as $token) {
            $scan = $this->scan($token);
            if ($scan === false) continue;

            $this->occurrences[] = $scan['word'];
            $this->occurrencesSummary += $scan['score'];

            unset($scan);
        }

        if (count($this->occurrences) === 0) {
            Log::info("Сообщение не содержит слов из базы");
            return false;
        }

        Log::info("Сообщение прошло фильтрацию, среднее арифметическое: " . $this->occurrencesSummary / count($this->occurrences));

        return $this->scoring();
    }

    /**
     * Функция проверки на ошибки в словах
     * @return $this
     */
    private function spell(): self
    {
        collect(
            $this->hunspell->checkText(
                (new StringSource($this->message, 'UTF-8')),
                ['ru_RU']
            )
        )->each(function ($item) {
            if (count($item->suggestions) > 0) {
                $this->message = str_replace($item->word, $item->suggestions[0], $this->message);
            }
        });

        Log::info("Сообщение после фильтрации по hunspell: {$this->message}");

        return $this;
    }

    /**
     * Функция приводит сообщение к нижнему регистру
     * @return $this
     */
    private function ensureLowercase(): self
    {
        $this->message = str($this->message)->lower();

        return $this;
    }

    /**
     * Функция преобразует сообщение в UTF-8
     * @return $this
     */
    private function ensureEncoding(): self
    {
        $encoding = $this->encoder->getEncoding($this->message);

        if ($encoding !== EncodingDetector::UTF_8) {
            Log::info('Сообщение не в кодировке UTF-8, преобразование...');
        }

        $this->message = match ($encoding) {
            EncodingDetector::UTF_8 => $this->message,
            default => $this->encoder->iconvXtoEncoding($this->message)
        };

        return $this;
    }

    /**
     * Проверяет вхождение слова в базу
     * @param string $word
     * @return array<string>|bool<false>
     */
    private function scan(string $word): array|bool
    {
        if (mb_stripos($this->lowWords, $word)) {
            Log::info("Слово {$word} прошло фильтрацию по низкому рейтингу");
            return ['word' => $word, 'score' => 1];
        } elseif (mb_stripos($this->mediumWords, $word)) {
            Log::info("Слово {$word} прошло фильтрацию по среднему рейтингу");
            return ['word' => $word, 'score' => 2];
        } elseif (mb_stripos($this->highWords, $word)) {
            Log::info("Слово {$word} прошло фильтрацию по высокому рейтингу");
            return ['word' => $word, 'score' => 3];
        }
        return false;
    }

    /**
     * Функция токенизации сообщения
     * @return $this
     */
    private function tokenize(): self
    {
        $this->tokens = collect(
            RakePlus::create($this->message, 'ru_RU', 1, false)->keywords()
        )->map(function ($word) {
            if ($word == '0' || $word == 'false') return false;
            return str($word)->lower();
        })->toArray();

        return $this;
    }

    /**
     * Сеттер для сообщения
     * @param string|null $message
     */
    private function setMessage(?string $message): self
    {
        $this->message = ($message);
        return $this;
    }

    /**
     * Проверяет отсутствие в сообщении матов
     * @return bool
     */
    private function censored(): bool
    {
        return ObsceneCensorRus::isAllowed($this->message);
    }

    private function scoring(): array
    {
        return [
            'score' => $this->occurrencesSummary / count($this->occurrences),
            'found' => $this->occurrences
        ];
    }

}
