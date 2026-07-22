<?php

namespace App\Services;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    public function sendResetLink(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = url('/reset-password?token='.$token.'&email='.urlencode($email));

        Mail::to($user->email)->send(new PasswordResetMail($resetUrl, $user->name));
    }

    /**
     * @throws ValidationException
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        $status = Password::reset(
            [
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'token' => $token,
            ],
            function (User $user, string $newPassword): void {
                $user->forceFill(['password' => $newPassword])->save();
                $this->revokeAllTokens($user);
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }

    public function revokeAllTokens(User $user): void
    {
        \Laravel\Passport\Token::query()
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->each(fn (\Laravel\Passport\Token $token) => $token->revoke());
    }
}
