<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserbotResource\Pages;
use App\Models\Tas\Userbot;
use App\Services\Tas\Enums\AuthStatus;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class UserbotResource extends Resource
{
    protected static ?string $model = Userbot::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Боты';

    protected static ?string $title = 'Боты';

    protected static ?string $label = 'Бот';

    protected static ?string $pluralLabel = 'Боты';

    protected static ?string $navigationGroup = 'TAS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('phone')
                    ->rules([
                        /* Валидация номера телефона для соответствия формату сессий */
                        fn() => function ($attribute, $value, $fail) {
                            if (!preg_match('/^\+7\d{10}$/', $value))
                                $fail('Номер телефона должен быть в формате +7XXXXXXXXXX');
                        },
                    ])
                    ->required()
                    ->autofocus()
                    ->placeholder('Номер телефона'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone')
                    ->label('Номер телефона')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('current_status')
                    ->label('Статус')
                    ->sortable()
                    ->getStateUsing(fn($record) => match ($record->getApi()->updateStatus()) {
                        AuthStatus::NOT_LOGGED_IN => 'НЕ АВТОРИЗОВАН',
                        AuthStatus::LOGGED_IN => 'АВТОРИЗОВАН',
                        AuthStatus::WAITING_CODE => 'ОЖИДАЕТ КОД',
                        AuthStatus::WAITING_PASSWORD => 'ОЖИДАЕТ ПАРОЛЬ 2FA',
                        AuthStatus::WAITING_SIGNUP => 'ОЖИДАЕТ РЕГИСТРАЦИЮ',
                        AuthStatus::NOT_EXIST => 'НЕ СУЩЕСТВУЕТ!',
                    })
                    ->color(fn($record) => match ($record->getApi()->updateStatus()) {
                        AuthStatus::LOGGED_IN => 'success',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('groups_count')
                    ->label('Количество групп')
                    ->getStateUsing(function ($record) {
                        $groups = $record->getApi()->getChats();
                        return ($groups) ? count($groups) : 0;
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('phoneLogin')
                    ->hidden(function ($record) {
                        return !($record->getApi()->updateStatus() == AuthStatus::NOT_LOGGED_IN);
                    })
                    ->label('Начать авторизацию')
                    ->icon('heroicon-o-key')
                    ->modalHeading('Введите код из служебных сообщений')
                    ->action(function ($record) {
                        $record->getApi()->phoneLogin();
                    }),
                Tables\Actions\Action::make('sendCode')
                    ->hidden(function ($record) {
                        return !$record->getApi()->waitingForCode();
                    })
                    ->label('Ввести код')
                    ->icon('heroicon-o-key')
                    ->form([Forms\Components\TextInput::make('code')
                        ->required()
                        ->autofocus()
                        ->placeholder('Код'),
                    ])
                    ->modalHeading('Введите код из служебных сообщений')
                    ->action(function ($record, $data) {
                        $record->getApi()->completePhoneLogin($data['code']);
                    }),
                Tables\Actions\Action::make('send2fa')
                    ->hidden(function ($record) {
                        return !$record->getApi()->waitingForPassword();
                    })
                    ->label('Ввести пароль 2FA')
                    ->modalHeading('Введите пароль двухфакторной аутентификации')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->required()
                            ->minLength(2)
                            ->autofocus()
                            ->placeholder('Пароль'),
                    ])
                    ->action(function ($record, $data) {
                        $record->getApi()->complete2faLogin($data['password']);
                        sleep(10);
                    }),
                Tables\Actions\Action::make('joinChat')
                    ->hidden(function ($record) {
                        return !($record->getApi()->updateStatus() === AuthStatus::LOGGED_IN);
                    })
                    ->label('Присоединиться к чату')
                    ->icon('heroicon-o-chat')
                    ->modalHeading('Введите инвайт-ссылку')
                    ->form([
                        Forms\Components\TextInput::make('link')
                            ->url()
                            ->required()
                            ->autofocus()
                            ->placeholder('Ссылка приглашения в чат'),
                    ])
                    ->action(function ($record, $data) {
                        $record->getApi()->joinChat($data['link']);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                /*Tables\Actions\BulkAction::make('rejoinChats')
                    ->label('Переподписаться на чаты')
                    ->icon('heroicon-o-chat')
                    ->modalHeading('Введите инвайт-ссылки')
                    ->form([
                        Forms\Components\Textarea::make('links')
                            ->required()
                            ->autofocus()
                            ->placeholder('Ссылки приглашения в чаты'),
                    ])
                    ->action(function ($records, $data) {
                        foreach ($records as $record) {
                            foreach (explode("\n", $data['links']) as $link) {
                                $record->getApi()->joinChat($link);
                            }
                        }
                    }),*/
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUserbots::route('/'),
        ];
    }
}
