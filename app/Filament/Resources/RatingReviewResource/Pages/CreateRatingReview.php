<?php

namespace App\Filament\Resources\RatingReviewResource\Pages;

use App\Filament\Resources\RatingReviewResource;
use App\Models\CategoryScore;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRatingReview extends CreateRecord
{
    protected static string $resource = RatingReviewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract category scores from form data
        $categoryScores = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'category_score_')) {
                $categoryId = str_replace('category_score_', '', $key);
                $categoryScores[$categoryId] = $value;
                unset($data[$key]); // Remove from main data
            }
        }
        
        // Store category scores for later processing
        $data['_category_scores'] = $categoryScores;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        
        // Get category scores from the original form data
        $categoryScores = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'category_score_')) {
                $categoryId = str_replace('category_score_', '', $key);
                $categoryScores[$categoryId] = $value;
            }
        }
        
        // Create category scores if they exist
        if (!empty($categoryScores)) {
            foreach ($categoryScores as $categoryId => $score) {
                if ($score !== null) {
                    CategoryScore::create([
                        'review_id' => $this->record->id,
                        'category_id' => $categoryId,
                        'score' => $score,
                    ]);
                }
            }
            
            // Overall rating will be calculated automatically by CategoryScore model events
        }
    }
}
