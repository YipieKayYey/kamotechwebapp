<?php

namespace App\Filament\Resources\RatingReviewResource\Pages;

use App\Filament\Resources\RatingReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRatingReviews extends ListRecords
{
    protected static string $resource = RatingReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
