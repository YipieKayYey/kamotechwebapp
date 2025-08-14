<?php

namespace App\Filament\Resources\TechnicianResource\Pages;

use App\Filament\Resources\TechnicianResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateTechnician extends CreateRecord
{
    protected static string $resource = TechnicianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If no user_id is provided, create a new user
        if (empty($data['user_id']) && isset($data['user'])) {
            $userData = $data['user'];
            
            // Hash the password
            if (!empty($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }
            
            // Set role to technician
            $userData['role'] = 'technician';
            $userData['is_active'] = true;
            
            // Create the user
            $user = User::create($userData);
            
            // Set the user_id for the technician
            $data['user_id'] = $user->id;
        }
        
        // Remove the nested user data as it's no longer needed
        unset($data['user']);
        
        return $data;
    }
}
