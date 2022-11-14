@php
    $humanReadable = function ($value) {
        if ($value <= 3.0 && $value > 2.4) return 'Высокий';
        if ($value <= 2.4 && $value > 1.6) return 'Средний';
        if ($value <= 1.6) return 'Низкий';
    };
@endphp
<b>Новый отчёт</b>
<i>Группа</i>: {{ $group['title'] }}
<i>Скоринг</i>: {{ round($scoring, 3) }} из 3 ({{ $humanReadable(round($scoring, 2)) }})
<i>Ключевые слова</i>: {{ implode(', ', $found ?? []) }}

<u>Сообщение</u>:
{{ $message }}

{{--@if($useInlineMention)
Автор: <a href="tg://user?id={$user['id']}">{{ $user['first_name'] }}{{ (isset($user['last_name'])) ? " {$user['last_name']}" : ''}}</a>
@endif--}}

