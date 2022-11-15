<b>Сессии не авторизованы!</b>

@foreach($userbots as $userbot)
<b>Бот {{ $userbot->phone }}</b> ({{ $userbot->getApi()->updateStatus()->name }})
@endforeach
