<?php

namespace App\Application\Auth;

use App\Jobs\Auth\SendResetPasswordJob;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordAccessService
{
    public const RESET_TTL_MINUTES = 60;

    public const INVITATION_TTL_HOURS = 24;

    public function requestReset(string $email): void
    {
        /** @var User|null $user */
        $user = User::withoutCompanyScope()->where('email', $email)->first();

        if (! $user instanceof User) {
            return;
        }

        $token = $this->newToken();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $this->hashToken($token),
                'created_at' => now(),
            ]
        );

        SendResetPasswordJob::dispatch((int) $user->getKey(), $token);
        activity()
            ->useLog('auth')
            ->performedOn($user)
            ->event('password_reset_requested')
            ->log('auth.password_reset.requested');
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($record === null || ! $this->validResetToken((string) $record->token, $token, $record->created_at)) {
            return false;
        }

        /** @var User|null $user */
        $user = User::withoutCompanyScope()->where('email', $email)->first();

        if (! $user instanceof User) {
            return false;
        }

        $user->update([
            'password' => Hash::make($password),
            'force_password_reset' => false,
        ]);
        DB::table('password_reset_tokens')->where('email', $email)->update([
            'token' => $this->hashToken($this->newToken()),
            'created_at' => now()->subMinutes(self::RESET_TTL_MINUTES + 1),
        ]);
        $user->tokens()->update(['expires_at' => now()->subSecond()]);

        activity()
            ->useLog('auth')
            ->performedOn($user)
            ->event('password_reset_completed')
            ->log('auth.password_reset.completed');

        return true;
    }

    public function setPassword(string $token, string $password): bool
    {
        /** @var UserInvitation|null $invitation */
        $invitation = UserInvitation::query()
            ->with('user')
            ->where('token_hash', $this->hashToken($token))
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation instanceof UserInvitation || $invitation->expires_at->isPast()) {
            return false;
        }

        $user = $invitation->getRelation('user');

        if (! $user instanceof User) {
            return false;
        }

        $user->update([
            'password' => Hash::make($password),
            'force_password_reset' => false,
        ]);
        $invitation->update(['accepted_at' => now()]);
        $user->tokens()->update(['expires_at' => now()->subSecond()]);

        activity()
            ->useLog('auth')
            ->performedOn($user)
            ->event('invitation_accepted')
            ->log('auth.invitation.accepted');

        return true;
    }

    public function newToken(): string
    {
        return Str::random(64);
    }

    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function validResetToken(string $storedHash, string $token, mixed $createdAt): bool
    {
        if (! hash_equals($storedHash, $this->hashToken($token))) {
            return false;
        }

        return Carbon::parse($createdAt)->addMinutes(self::RESET_TTL_MINUTES)->isFuture();
    }
}
