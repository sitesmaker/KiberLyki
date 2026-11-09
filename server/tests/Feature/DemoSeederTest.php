<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_playable_and_repeatable_without_resetting_edits(): void
    {
        $this->seed(DemoSeeder::class);
        $this->assertDatabaseCount('teams', 32);
        $this->assertDatabaseCount('users', 265);
        $this->assertDatabaseCount('tournaments', 60);
        $this->assertDatabaseCount('matches', 168);
        foreach (TournamentRegistration::with(['players', 'tournament'])->get() as $registration) {
            $this->assertCount($registration->tournament->teamSize, $registration->players);
        }
        $this->assertSame(12, Tournament::where('phase', 'finished')->count());
        $this->assertSame(12, Tournament::where('phase', 'active')->count());
        $this->assertSame(12, Tournament::whereNull('publishedAt')->count());
        $this->assertSame(12, TournamentMatch::where('round', 3)->where('matchStatus', 'finished')->whereNotNull('winner_id')->count());
        $team = Team::first();
        $team->update(['name' => 'Изменено вручную']);
        $this->seed(DemoSeeder::class);
        $this->assertDatabaseCount('users', 265);
        $this->assertDatabaseCount('matches', 168);
        $this->assertSame('Изменено вручную', $team->fresh()->name);
        $this->postJson('/api/auth/local', ['identifier' => 'mock.captain01@example.test', 'password' => DemoSeeder::PASSWORD])->assertOk();
    }
}
