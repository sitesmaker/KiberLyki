<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\Discipline;
use App\Models\Media;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Services\BracketService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $u = User::where('email', $d['email'])->first();
        if (! $u || $u->blocked || ! $u->canAccessAdmin() || ! Hash::check($d['password'], $u->password)) {
            return back()->withErrors(['email' => 'Неверный логин или нет доступа к панели'])->onlyInput('email');
        }
        Auth::login($u);
        $r->session()->regenerate();
        $r->session()->put('password_hash_web', $u->getAuthPassword());
        $r->session()->put('password_user_web', $u->id);
        $r->session()->put('admin_session_token', (string) $u->remember_token);

        return redirect('/admin');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/admin/login');
    }

    public function home(Request $request)
    {
        if (! $request->user()->isOrganizer()) {
            return view('admin.home');
        }
        return view('admin.home', ['teams' => Team::count(), 'tournaments' => Tournament::count(), 'pending' => TournamentRegistration::where('registrationStatus', 'pending')->count()]);
    }

    private function spec(string $section): array
    {
        return config('admin.'.$section) ?? abort(404);
    }

    private function model(string $section): string
    {
        return match ($section) {
            'disciplines' => Discipline::class, 'tournaments' => Tournament::class, default => Content::class
        };
    }

    private function query(string $section)
    {
        $q = $this->model($section)::query();
        if ($this->model($section) === Content::class) {
            $q->where('kind', $section);
        }

return $q;
    }

    private function search(Request $request, $query, array $columns)
    {
        $request->validate(['q' => 'nullable|string|max:200']);
        $term = trim((string) $request->query('q', ''));
        if ($term !== '') {
            $connection = $query->getConnection();
            if ($connection->getDriverName() === 'sqlite') {
                $connection->getPdo()->sqliteCreateFunction('LOWER', fn ($value) => mb_strtolower($value ?? ''), 1);
            }
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($nested) use ($columns, $pattern, $connection) {
                foreach ($columns as $column) {
                    $nested->orWhereRaw('LOWER('.$connection->getQueryGrammar()->wrap($column).") LIKE ? ESCAPE '!'", [$pattern]);
                }
            });
        }

        return $query;
    }

    public function listing(Request $request, string $section)
    {
        $spec = $this->spec($section);

        $columns = $this->model($section) === Content::class ? ['payload->title', 'payload->firstName', 'payload->lastName', 'payload->description'] : ($section === 'tournaments' ? ['title', 'slug'] : ['name', 'slug']);

        return view('admin.list', ['section' => $section, 'spec' => $spec, 'items' => $this->search($request, $this->query($section), $columns)->orderByDesc('id')->paginate(30)->withQueryString()]);
    }

    public function edit(string $section, ?string $id = null)
    {
        $spec = $this->spec($section);
        $item = $id ? $this->query($section)->where('documentId', $id)->firstOrFail() : null;
        if (! $id && ($spec['single'] ?? false) && ($existing = $this->query($section)->first())) {
            return redirect('/admin/content/'.$section.'/'.$existing->documentId.'/edit');
        }

        $values = $item instanceof Content ? $item->payload : ($item?->toArray() ?? []);

        return view('admin.edit', ['section' => $section, 'spec' => $spec, 'item' => $item, 'values' => $values, 'disciplines' => Discipline::all()]);
    }

    public function save(Request $r, string $section, ?string $id = null)
    {
        $spec = $this->spec($section);
        $class = $this->model($section);
        $item = $id ? $this->query($section)->where('documentId', $id)->firstOrFail() : new $class;
        $rules = array_map(fn ($f) => $f[2], $spec['fields']);
        if (isset($rules['slug']) && $class !== Content::class) {
            $rules['slug'] = [...explode('|', $rules['slug']), Rule::unique($item->getTable(), 'slug')->ignore($item->id)];
        }
        if (isset($rules['gallery'])) {
            $rules['gallery.*'] = 'file|mimes:jpg,jpeg,png,webp|max:5120';
        }
        foreach ($spec['fields'] as $field => $f) {
            if (in_array($f[1], ['image', 'media', 'gallery'])) {
                $rules['remove_'.$field] = 'nullable|boolean';
                if ($f[1] === 'gallery') {
                    $rules['remove_'.$field] = 'nullable|array';
                    $rules['remove_'.$field.'.*'] = 'integer|distinct';
                }
            }
        }
        $d = $r->validate($rules);
        if ($section === 'staffs') {
            $d['sortOrder'] = (int) ($d['sortOrder'] ?? 0);
        }
        if ($item instanceof Tournament) {
            foreach (['registrationStartsAt', 'registrationEndsAt', 'startsAt'] as $field) {
                $d[$field] = Carbon::parse($d[$field], 'Europe/Moscow')->utc();
            }
        }
        if ($item instanceof Tournament && $item->exists && $item->matches()->exists()) {
            foreach (['discipline_id', 'teamSize', 'maxTeams'] as $field) {
                abort_if((int) $d[$field] !== (int) $item->$field, 422, 'После генерации сетки нельзя менять дисциплину и размер турнира');
            }
            abort_unless(in_array($d['phase'], [$item->phase, 'cancelled']), 422, 'Этап обновляется по результатам матчей');
        }
        foreach ($spec['fields'] as $field => $f) {
            if (! in_array($f[1], ['image', 'media', 'gallery'])) {
                continue;
            }
            unset($d[$field]);
            unset($d['remove_'.$field]);
            if ($f[1] === 'gallery') {
                // Only remove entries belonging to this record; never delete shared media files.
                $d[$field] = array_values(array_filter($item->payload[$field] ?? [], fn ($m) => ! in_array((string) $m['id'], array_map('strval', (array) $r->input('remove_'.$field, [])), true)));
            } elseif ($r->boolean('remove_'.$field)) {
                $d[$item instanceof Tournament ? 'cover_id' : $field] = null;
            }
            if ($r->hasFile($field)) {
                $files = $f[1] === 'gallery' ? $r->file($field) : [$r->file($field)];
                $media = [];
                foreach ($files as $file) {
                    $m = Media::create(['user_id' => $r->user()->id, 'url' => '/storage/'.$file->store('content', 'public'), 'mime' => $file->getMimeType(), 'alternativeText' => $file->getClientOriginalName()]);
                    $media[] = $m->toArray();
                }
                if ($item instanceof Tournament) {
                    $d['cover_id'] = $media[0]['id'];
                } else {
                    $d[$field] = $f[1] === 'gallery' ? array_merge($d[$field], $media) : $media[0];
                }
            }
        }
        DB::transaction(function () use ($item, $class, $section, $spec, $d, $r) {
            if ($class === Content::class) {
                if (! $item->exists && ($spec['single'] ?? false)) {
                    abort_if($this->query($section)->exists(), 422, 'Раздел уже создан');
                }
                $item->fill(['kind' => $section, 'payload' => array_merge($item->payload ?? [], $d)]);
            } else {
                $item->fill($d);
            }
            if ($class !== Discipline::class) {
                $item->publishedAt = $r->boolean('published') ? ($item->publishedAt ?? now()) : null;
            }
            $item->save();
        });

        return redirect('/admin/content/'.$section)->with('status', 'Изменения сохранены');
    }

    public function teams(Request $request)
    {
        return view('admin.teams', ['teams' => $this->search($request, Team::with(['captain', 'discipline'])->withCount('memberships'), ['name', 'slug'])->orderBy('name')->paginate(30)->withQueryString(), 'users' => User::where('blocked', false)->get(), 'disciplines' => Discipline::all()]);
    }

    public function team(Team $team)
    {
        return view('admin.team', ['team' => TeamController::view($team), 'disciplines' => Discipline::all()]);
    }

    public function createTeam(Request $r)
    {
        $r->validate(['captain_id' => 'required|exists:users,id']);
        $captain = User::where('blocked', false)->findOrFail($r->integer('captain_id'));
        $r->setUserResolver(fn () => $captain);
        app(TeamController::class)->create($r);

        return back()->with('status', 'Команда создана');
    }

    public function teamAction(Request $r, Team $team, string $action, ?string $member = null)
    {
        $r->setUserResolver(fn () => $team->captain);
        $controller = app(TeamController::class);
        match ($action) {
            'add' => $controller->add($r, $team), 'existing' => $controller->addExisting($r, $team),
            'remove' => $controller->remove($r, $team, TeamMembership::where('documentId', $member)->firstOrFail()),
            'transfer' => $controller->transfer($r, $team), 'update' => $controller->update($r, $team), default => abort(404),
        };

        return back()->with('status', 'Состав обновлён');
    }

    public function tournament(Tournament $tournament)
    {
        return view('admin.tournament', ['tournament' => $tournament->load(['discipline', 'registrations.team', 'registrations.players', 'matches.teamA', 'matches.teamB', 'matches.winner'])]);
    }

    public function export(Tournament $tournament, \App\Services\TournamentExport $export)
    {
        $book = $export->workbook($tournament);

        return response()->streamDownload(function () use ($book) {
            try {
                (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'tournament-'.$tournament->slug.'-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function review(Request $r, TournamentRegistration $registration)
    {
        app(RegistrationController::class)->review($r, $registration);

        return back()->with('status', 'Решение сохранено');
    }

    public function generate(Tournament $tournament, BracketService $service)
    {
        $service->generate($tournament);

        return back()->with('status', 'Сетка создана');
    }

    public function result(Request $r, Tournament $tournament, TournamentMatch $match)
    {
        app(TournamentController::class)->result($r, $tournament, $match, app(BracketService::class));

        return back()->with('status', 'Результат записан');
    }

    public function users(Request $r)
    {
        abort_unless($r->user()->canManagePlayers(), 403);

        $r->validate(['group' => 'nullable|in:administration,players']);
        $group = $r->query('group', $r->user()->canAssignRoles() ? 'administration' : 'players');
        abort_if($group === 'administration' && ! $r->user()->canAssignRoles(), 403);
        $query = $this->search($r, User::query(), ['username', 'email', 'name']);
        $counts = ['administration' => $r->user()->canAssignRoles() ? (clone $query)->whereIn('role_type', ['admin', 'manager', 'content_manager', 'organizer'])->count() : 0, 'players' => (clone $query)->where('role_type', 'authenticated')->count()];
        if ($group === 'players') {
            $query->where('role_type', 'authenticated');
        } else {
            $query->whereIn('role_type', ['admin', 'manager', 'content_manager', 'organizer']);
        }

        $availableUsers = $group === 'administration' && $r->user()->canAssignRoles()
            ? User::whereIn('role_type', [...array_keys(User::ADMIN_ROLES), 'organizer'])->whereKeyNot($r->user()->id)->orderBy('username')->get(['id', 'name', 'username', 'email', 'role_type'])
            : collect();

        return view('admin.users', ['group' => $group, 'counts' => $counts, 'users' => $query->with('profile')->orderBy('username')->paginate(30)->withQueryString(), 'availableUsers' => $availableUsers]);
    }

    public function role(Request $r, User $user)
    {
        abort_unless($r->user()->canAssignRoles(), 403);
        abort_if($r->user()->id === $user->id, 422, 'Нельзя изменить собственные права');
        $user->update($r->validate(['role_type' => ['required', Rule::in(['authenticated', ...array_keys(User::ADMIN_ROLES)])]]));

        return back()->with('status', 'Права обновлены');
    }

    public function createUser(Request $request)
    {
        abort_unless($request->user()->canAssignRoles(), 403);
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:72|confirmed',
            'role_type' => ['required', Rule::in(array_keys(User::ADMIN_ROLES))],
        ]);
        DB::transaction(function () use ($data) {
            $user = User::create($data + ['name' => $data['username'], 'blocked' => false]);
            $user->profile()->create(['nickname' => $user->username]);
        });

        return redirect('/admin/users')->with('status', 'Пользователь создан. Для входа используйте указанную почту и пароль.');
    }

    public function setPassword(Request $request, User $user)
    {
        abort_unless($request->user()->canAssignRoles(), 403);
        $data = $request->validate(['password' => 'required|string|min:8|max:72|confirmed']);
        DB::transaction(function () use ($user, $data) {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $target->forceFill(['password' => $data['password'], 'remember_token' => null])->save();
            $target->tokens()->delete();
        });
        if ($request->user()->id === $user->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/login')->with('status', 'Пароль изменён. Войдите с новым паролем.');
        }

        return back()->with('status', 'Пароль изменён. Пользователю потребуется войти заново.');
    }

    public function ownPassword(Request $request)
    {
        $data = $request->validate(['current_password' => 'required|string', 'password' => 'required|string|min:8|max:72|confirmed']);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Текущий пароль неверен.']);
        }
        DB::transaction(function () use ($request, $data) {
            $request->user()->forceFill(['password' => $data['password'], 'remember_token' => null])->save();
            $request->user()->tokens()->delete();
        });
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login')->with('status', 'Пароль изменён. Войдите с новым паролем.');
    }

    public function staffAccess(Request $request, User $user)
    {
        abort_unless($request->user()->canAssignRoles(), 403);
        abort_if($request->user()->id === $user->id, 422, 'Нельзя заблокировать свой аккаунт');
        $request->validate(['blocked' => 'required|boolean']);
        DB::transaction(function () use ($request, $user) {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->canAccessAdmin(), 403, 'Выберите сотрудника администрации');
            $target->update(['blocked' => $request->boolean('blocked')]);
            if ($target->blocked) {
                $target->tokens()->delete();
                // Invalidate existing web sessions, including after the account is unblocked.
                $target->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->save();
            }
        });

        return back()->with('status', $request->boolean('blocked') ? 'Сотрудник заблокирован.' : 'Доступ сотрудника восстановлен.');
    }

    public function deleteStaff(Request $request, User $user)
    {
        abort_unless($request->user()->canAssignRoles(), 403);
        abort_if($request->user()->id === $user->id, 422, 'Нельзя удалить свой аккаунт');
        $request->validate(['confirm_delete' => 'accepted']);
        DB::transaction(function () use ($user) {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->canAccessAdmin(), 403, 'Выберите сотрудника администрации');
            $target->tokens()->delete();
            $target->forceFill(['blocked' => true, 'remember_token' => null])->save();
            $target->delete();
        });

        return back()->with('status', 'Аккаунт сотрудника удалён. Доступ закрыт; история команд и соревнований сохранена.');
    }

    public function promote(Request $request)
    {
        abort_unless($request->user()->canAssignRoles(), 403);
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::notIn([$request->user()->id]), Rule::exists('users', 'id')->whereIn('role_type', [...array_keys(User::ADMIN_ROLES), 'organizer'])],
            'role_type' => ['required', Rule::in(array_keys(User::ADMIN_ROLES))],
        ], ['user_id.exists' => 'Выберите аккаунт сотрудника администрации.', 'user_id.not_in' => 'Нельзя изменить собственные права.']);
        DB::transaction(function () use ($data) {
            $user = User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            abort_unless($user->canAccessAdmin(), 422, 'Аккаунт больше не относится к администрации. Обновите список.');
            $user->update(['role_type' => $data['role_type']]);
        });

        return redirect('/admin/users')->with('status', 'Аккаунт добавлен в администрацию');
    }

    public function playerAccess(Request $request, User $user)
    {
        abort_unless($request->user()->canManagePlayers(), 403);
        $request->validate(['blocked' => 'required|boolean']);
        DB::transaction(function () use ($request, $user) {
            $target = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->role_type === 'authenticated', 403, 'Можно управлять доступом только игроков');
            $target->update(['blocked' => $request->boolean('blocked')]);
            if ($target->blocked) {
                $target->tokens()->delete();
            }
        });

        return back()->with('status', $request->boolean('blocked') ? 'Доступ игрока заблокирован' : 'Доступ игрока восстановлен');
    }
}
