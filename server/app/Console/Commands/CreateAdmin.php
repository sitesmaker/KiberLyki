<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'cyberluki:admin {email?}';

    protected $description = 'Создать администратора панели (пароль вводится скрыто)';

    public function handle(): int
    {
        $d = ['email' => $this->argument('email') ?? $this->ask('Email'), 'username' => $this->ask('Логин'), 'password' => $this->secret('Пароль (минимум 8 символов)')];
        $v = Validator::make($d, ['email' => 'required|email|unique:users,email', 'username' => 'required|string|max:100|unique:users,username', 'password' => 'required|string|min:8']);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        User::create($d + ['name' => $d['username'], 'role_type' => 'admin']);
        $this->info('Администратор создан. Вход: /admin');

        return self::SUCCESS;
    }
}
