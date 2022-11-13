<?php

namespace App\Filament\Resources\WordResource\Pages;

use App\Filament\Resources\WordResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWords extends ManageRecords
{
    protected static string $resource = WordResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
