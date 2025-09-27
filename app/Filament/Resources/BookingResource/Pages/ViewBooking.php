<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Confirm action for pending bookings
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

                    // Send SMS notification
                    $smsService = new \App\Services\SemaphoreSmsService;
                    $smsService->sendBookingConfirmation($this->record);

                    \Filament\Notifications\Notification::make()
                        ->title('Booking Confirmed')
                        ->body("Booking #{$this->record->booking_number} has been confirmed.")
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\EditAction::make(),

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

                    // Send SMS notification
                    $smsService = new \App\Services\SemaphoreSmsService;
                    $smsSent = $smsService->sendBookingCancellation($this->record);

                    $notification = \Filament\Notifications\Notification::make()
                        ->title('Booking Cancelled')
                        ->body("Booking #{$this->record->booking_number} has been cancelled.");

                    if ($smsSent) {
                        $notification->body("Booking #{$this->record->booking_number} has been cancelled. Customer notified via SMS.");
                    }

                    $notification->danger()->send();
                })
                ->visible(fn () => in_array($this->record->status, ['pending', 'confirmed', 'cancel_requested'])),

            // Approve cancellation request
            Actions\Action::make('approve_cancellation')
                ->label('Approve Cancellation')
                ->icon('heroicon-m-check')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Approve Cancellation Request')
                ->modalDescription('This will approve the customer\'s cancellation request.')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'cancelled',
                        'payment_status' => 'unpaid',
                        'cancellation_processed_at' => now(),
                        'cancellation_processed_by' => auth()->id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Cancellation Approved')
                        ->body("Booking #{$this->record->booking_number} cancellation has been approved.")
                        ->warning()
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'cancel_requested'),
        ];
    }
}
