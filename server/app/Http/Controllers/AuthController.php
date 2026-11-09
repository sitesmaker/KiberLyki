<?php

namespace App\Http\Controllers;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $r->merge(['email' => strtolower(trim((string) $r->input('email')))]);
        $d = $r->validate(['username' => 'required|string|max:100|unique:users', 'email' => 'required|email|max:255|unique:users', 'password' => 'required|string|min:8|max:255']);
        $u = DB::transaction(function () use ($d) {
            $u = User::create($d + ['name' => $d['username']]);
            PlayerProfile::create(['user_id' => $u->id, 'nickname' => $u->username]);

            return $u;
        });

        return response()->json($this->session($u), 201);
    }

    public function login(Request $r)
    {
        $d = $r->validate(['identifier' => 'required|string', 'password' => 'required|string']);
        $u = User::where('username', $d['identifier'])->orWhere('email', strtolower($d['identifier']))->first();
        abort_unless($u && ! $u->blocked && Hash::check($d['password'], $u->password), 401, 'Неверный логин или пароль');

        return $this->session($u);
    }

    private function session(User $u): array
    {
        return ['jwt' => $u->createToken('react', ['*'], now()->addDays(7))->plainTextToken, 'user' => $u];
    }

    public function me(Request $r)
    {
        return $r->user()->load('profile.avatar');
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    public function password(Request $r)
    {
        $d = $r->validate(['currentPassword' => 'required|string', 'password' => 'required|string|min:8|max:255|same:passwordConfirmation', 'passwordConfirmation' => 'required|string']);
        abort_unless(Hash::check($d['currentPassword'], $r->user()->password), 422, 'Текущий пароль неверен');
        $r->user()->update(['password' => $d['password']]);
        $r->user()->tokens()->where('id', '!=', $r->user()->currentAccessToken()->id)->delete();

        return ['ok' => true];
    }
}
