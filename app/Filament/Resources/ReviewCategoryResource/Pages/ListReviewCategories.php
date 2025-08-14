<?php

namespace App\Filament\Resources\ReviewCategoryResource\Pages;

use App\Filament\Resources\ReviewCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReviewCategories extends ListRecords
{
    protected static string $resource = ReviewCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
