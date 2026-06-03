<?php

namespace App\Application\Auth;

use App\Models\User;

class UserPreferencesService
{
    public function updateLanguagePreference(User $user, string $languagePreference): User
    {
        $user->update([
            'language_preference' => $languagePreference,
        ]);

        return $user->refresh();
    }
}
