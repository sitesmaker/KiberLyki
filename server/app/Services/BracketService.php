<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class BracketService
{
    public static function seedOrder(int $size): array
    {
        if ($size < 2 || ($size & ($size - 1))) {
            throw new \InvalidArgumentException('Размер сетки должен быть степенью двойки');
        }
        $order = [1, 2];
        while (count($order) < $size) {
            $sum = count($order) * 2 + 1;
            $order = array_merge(...array_map(fn ($seed) => [$seed, $sum - $seed], $order));
        }

        return $order;
    }

    public function generate(Tournament $t): array
    {
        return DB::transaction(function () use ($t) {
            $t = Tournament::whereKey($t->id)->lockForUpdate()->firstOrFail();
            abort_if($t->matches()->exists(), 422, 'Сетка уже создана');
            abort_if(in_array($t->phase, ['finished', 'cancelled']), 422, 'Турнир завершён или отменён');
            $registrations = $t->registrations()->where('registrationStatus', 'approved')->orderByRaw('seed IS NULL')->orderBy('seed')->orderBy('submittedAt')->get();
            abort_if($registrations->count() < 2, 422, 'Нужно минимум две одобренные команды');
            $size = 2;
            while ($size < $registrations->count()) {
                $size *= 2;
            }
            $rounds = (int) log($size, 2);
            $byRound = [];
            for ($round = $rounds; $round >= 1; $round--) {
                for ($pos = 1; $pos <= $size / (2 ** $round); $pos++) {
                    $next = $byRound[$round + 1][intdiv($pos - 1, 2)] ?? null;
                    $byRound[$round][] = TournamentMatch::create(['tournament_id' => $t->id, 'round' => $round, 'position' => $pos, 'next_match_id' => $next?->id, 'nextSlot' => $next ? ($pos % 2 ? 'A' : 'B') : null]);
                }
            }
            $slots = array_map(fn ($seed) => $registrations[$seed - 1]->team_id ?? null, self::seedOrder($size));
            foreach ($byRound[1] as $index => $match) {
                $a = $slots[$index * 2];
                $b = $slots[$index * 2 + 1];
                $winner = $a && ! $b ? $a : ($b && ! $a ? $b : null);
                $match->update(['team_a_id' => $a, 'team_b_id' => $b, 'winner_id' => $winner, 'matchStatus' => $winner ? 'finished' : 'pending']);
                if ($winner) {
                    $this->advance($match, $winner);
                }
            }
            $t->update(['phase' => 'active']);

            return ['bracketSize' => $size, 'rounds' => $rounds];
        });
    }

    private function advance(TournamentMatch $m, int $winner): void
    {
        if ($m->next_match_id) {
            TournamentMatch::whereKey($m->next_match_id)->update([$m->nextSlot === 'A' ? 'team_a_id' : 'team_b_id' => $winner]);
        } else {
            $m->tournament()->update(['phase' => 'finished']);
        }
    }

    public function result(Tournament $t, TournamentMatch $m, int $a, int $b): void
    {
        DB::transaction(function () use ($t, $m, $a, $b) {
            $t = Tournament::whereKey($t->id)->lockForUpdate()->firstOrFail();
            $m = TournamentMatch::whereKey($m->id)->lockForUpdate()->firstOrFail();
            abort_unless($m->tournament_id === $t->id, 404);
            abort_unless($t->phase === 'active', 422, 'Турнир не активен');
            abort_if($a < 0 || $b < 0 || $a === $b, 422, 'Укажите разный неотрицательный счёт');
            abort_unless($m->team_a_id && $m->team_b_id, 422, 'Обе команды ещё не определены');
            abort_if($m->matchStatus === 'finished', 422, 'Результат уже записан');
            $winner = $a > $b ? $m->team_a_id : $m->team_b_id;
            $m->update(['scoreA' => $a, 'scoreB' => $b, 'winner_id' => $winner, 'matchStatus' => 'finished']);
            $this->advance($m, $winner);
        });
    }
}
