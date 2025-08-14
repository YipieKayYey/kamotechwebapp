<?php

namespace App\Filament\Resources\ReviewCategoryResource\Pages;

use App\Filament\Resources\ReviewCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReviewCategory extends EditRecord
{
    protected static string $resource = ReviewCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
