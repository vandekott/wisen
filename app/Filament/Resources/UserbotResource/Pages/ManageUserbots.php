<?php

namespace App\Filament\Resources\UserbotResource\Pages;

use App\Filament\Resources\UserbotResource;
use App\Models\Tas\Userbot;
use App\Services\Tas\System;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUserbots extends ManageRecords
{
    protected static string $resource = UserbotResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('reboot')
                ->label('Перезапустить telegram-сервер')
                ->color('warning')
                ->icon('heroicon-o-refresh')
                ->requiresConfirmation()
                ->action(function () {
                    System::getInstance()->reboot();
                    sleep(15);
                }),
            Actions\Action::make('removeSessionFiles')
                ->label('Удалить файлы сессий')
                ->color('warning')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(function () {
                    System::getInstance()->removeSessions(
                        Userbot::all()->pluck('phone')->toArray()
                    );

                    System::getInstance()->reboot();
                    sleep(15);
                }),
        ];
    }
}
