<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'profile_photo' => ['nullable', 'image', 'max:2048'],
            'password' => $this->passwordRules(),
        ])->validate();

        $profilePhotoPath = null;
        if (($input['profile_photo'] ?? null) instanceof UploadedFile) {
            $profilePhotoPath = $input['profile_photo']->store('profile-photos', 'public');
        }

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'profile_photo_path' => $profilePhotoPath,
        ]);
    }
}
