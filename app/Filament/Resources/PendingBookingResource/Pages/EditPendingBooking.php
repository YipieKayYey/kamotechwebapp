<?php

namespace App\Filament\Resources\PendingBookingResource\Pages;

use App\Filament\Resources\PendingBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPendingBooking extends EditRecord
{
    protected static string $resource = PendingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Quick confirm action from edit page
            Actions\Action::make('confirm')
                ->label('Confirm Booking')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm Booking')
                ->modalDescription(fn () => "Confirm booking #{$this->record->booking_number}?")
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'confirmed',
                        'confirmed_at' => now(),
                        'confirmed_by' => auth()->id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Booking Confirmed')
                        ->body("Booking #{$this->record->booking_number} has been confirmed.")
                        ->success()
                        ->send();

                    // Redirect to list after confirming
                    $this->redirect(PendingBookingResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\ViewAction::make(),

            // Cancel action
            Actions\Action::make('cancel')
                ->label('Cancel Booking')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Booking')
                ->modalDescription('This will cancel the booking. This action cannot be undone.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'cancelled',
                        'payment_status' => 'unpaid',
                        'cancellation_processed_at' => now(),
                        'cancellation_processed_by' => auth()->id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Booking Cancelled')
                        ->body("Booking #{$this->record->booking_number} has been cancelled.")
                        ->danger()
                        ->send();

                    // Redirect to list after cancelling
                    $this->redirect(PendingBookingResource::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    public function getTitle(): string
    {
        return "Edit Pending Booking #{$this->record->booking_number}";
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        // Ensure we're only editing pending bookings
        if ($this->record->status !== 'pending') {
            \Filament\Notifications\Notification::make()
                ->title('Cannot Edit')
                ->body('This booking is no longer pending.')
                ->danger()
                ->send();

            $this->redirect(PendingBookingResource::getUrl('index'));
        }
    }
}
