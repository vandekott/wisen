<?php

namespace App\Filament\Resources;

use App\Enums\ScoreWord\WordScoreWeights;
use App\Filament\Resources\WordResource\Pages;
use App\Models\Tas\Word;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WordResource extends Resource
{
    protected static ?string $model = Word::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    protected static ?string $navigationLabel = 'Слова';

    protected static ?string $title = 'Слова';

    protected static ?string $label = 'Слово';

    protected static ?string $pluralLabel = 'Слова';

    protected static ?string $navigationGroup = 'TAS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('word')
                    ->label('Слово')
                    ->required()
                    ->autofocus()
                    ->lazy()
                    ->rules(['unique:words,word'])
                    ->placeholder('Слово'),
                Forms\Components\Select::make('score')
                    ->label('Оценка')
                    ->options([
                        1 => 'Низкий',
                        2 => 'Средней',
                        3 => 'Высокий',
                    ])
                    ->required()
                    ->placeholder('Приоритет'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('word')
                    ->label('Слово')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('score')
                    ->label('Приоритет')
                    ->sortable()
                    ->getStateUsing(fn($record) => match ($record->score->value) {
                        1 => 'Низкий',
                        2 => 'Средний',
                        3 => 'Высокий',
                    })
                    ->color(fn($record) => match ($record->score) {
                        WordScoreWeights::LOW => 'secondary',
                        WordScoreWeights::MEDIUM => 'primary',
                        WordScoreWeights::HIGH => 'success',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWords::route('/'),
        ];
    }
}
