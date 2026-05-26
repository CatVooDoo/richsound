<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?array $memo = null;

    public static function user(): ?array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        $userId = Session::get('auth_user_id');

        if ($userId === null) {
            return null;
        }

        self::$memo = (new User())->findById((int) $userId);

        return self::$memo;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        Session::put('auth_user_id', (int) $user['id']);
        self::$memo = null;
    }

    public static function logout(): void
    {
        Session::forget('auth_user_id');
        self::$memo = null;
    }
}
