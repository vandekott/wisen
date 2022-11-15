<?php

namespace App\Filament\Resources\WordResource\Pages;

use App\Exports\WordsExport;
use App\Filament\Resources\WordResource;
use App\Imports\WordsImport;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ManageWords extends ManageRecords
{
    protected static string $resource = WordResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export')
                ->label('Экспорт')
                ->color('primary')
                ->icon('heroicon-o-download')
                ->action(function () {
                    return Excel::download(new WordsExport(), 'words.xlsx');
                }),
            Actions\Action::make('import')
                ->label('Импорт')
                ->color('primary')
                ->icon('heroicon-o-upload')
                ->form([
                    FileUpload::make('file')
                        ->label('Файл')
                        ->required()
                ])
                ->action(function ($data) {
                    // dd(Storage::disk('public')->path($data['file']));
                    return Excel::import(new WordsImport(), Storage::disk('public')->path($data['file']));
                }),
        ];
    }
}
