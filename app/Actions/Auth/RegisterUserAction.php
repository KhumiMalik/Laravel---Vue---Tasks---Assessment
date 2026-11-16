<?php

namespace App\Actions\Auth;

use App\Models\User;

class RegisterUserAction
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function execute(array $data): User
    {
        // password is hashed by the model's 'hashed' cast
        return User::create($data);
    }
}
