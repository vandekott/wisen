<?php

namespace App\Providers;

use Filament\Forms\Components\{Checkbox, Grid, Section, TextInput};
use Illuminate\Support\ServiceProvider;
use Reworck\FilamentSettings\FilamentSettings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        FilamentSettings::setFormFields([
            Section::make('Скоринг')->schema([
                TextInput::make('min_score')
                    ->reactive()
                    ->minValue(1.0)
                    ->maxValue(3.0)
                    ->numeric()
                    ->step(0.1)
                    ->label('Минимальный балл')
                    ->hint('Минимальный балл, при котором сообщение будет отправлено менеджерам')
                    ->required()
                    ->placeholder('Минимальный балл'),
                Grid::make()->schema([
                    Checkbox::make('activate_grammar')
                        ->reactive()
                        ->label('Активировать грамматику')
                        ->required()
                ]),
                TextInput::make('notifier_chat_id')
                    ->reactive()
                    ->label('ID чата для уведомлений')
                    ->required(),
            ])
        ]);
    }
}
