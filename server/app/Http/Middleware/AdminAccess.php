<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAccess
{
    public function handle(Request $r, Closure $next)
    {
        if (! $r->user()) {
            return redirect('/admin/login');
        }
        $user = $r->user();
        if ((int) $r->session()->get('password_user_web') === $user->id
            && (! hash_equals($user->getAuthPassword(), (string) $r->session()->get('password_hash_web'))
                || ! hash_equals((string) $user->remember_token, (string) $r->session()->get('admin_session_token')))) {
            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            $r->session()->invalidate();
            $r->session()->regenerateToken();

            return redirect('/admin/login')->with('status', 'Сеанс завершён. Войдите заново.');
        }
        $r->session()->put(['password_user_web' => $user->id, 'password_hash_web' => $user->getAuthPassword(), 'admin_session_token' => (string) $user->remember_token]);
        abort_unless(! $user->blocked && $user->canAccessAdmin(), 403, 'Нет доступа к панели');
        $method = $r->route()->getActionMethod();
        $allowed = match (true) {
            $r->is('admin', 'admin/logout', 'admin/account', 'admin/account/password') => true,
            $r->is('admin/seo', 'admin/seo/*') => $user->canManageContent(),
            $r->route('section') !== null => in_array($r->route('section'), ['tournaments', 'disciplines']) ? $user->isOrganizer() : $user->canManageContent(),
            $r->is('admin/users*') => in_array($method, ['role', 'promote', 'createUser', 'setPassword', 'staffAccess', 'deleteStaff']) ? $user->canAssignRoles() : $user->canManagePlayers(),
            $r->is('admin/teams*', 'admin/tournaments*', 'admin/registrations*') => $user->isOrganizer(),
            default => false,
        };
        abort_unless($allowed, 403, 'У вашей роли нет доступа к этому разделу');

        return $next($r);
    }
}
