<?php

namespace App\Filament\Resources\UserbotResource\Pages;

use App\Filament\Resources\UserbotResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUserbots extends ManageRecords
{
    protected static string $resource = UserbotResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
