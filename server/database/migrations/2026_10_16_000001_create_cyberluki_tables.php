<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('username')->unique();
            $t->string('role_type')->default('authenticated');
            $t->boolean('blocked')->default(false);
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->text('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
        $base = function (Blueprint $t) {
            $t->id();
            $t->string('documentId')->unique();
            $t->timestamp('createdAt')->nullable();
            $t->timestamp('updatedAt')->nullable();
        };
        Schema::create('media', function (Blueprint $t) use ($base) {
            $base($t);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->text('url');
            $t->string('mime');
            $t->string('alternativeText')->nullable();
        });
        Schema::create('disciplines', function (Blueprint $t) use ($base) {
            $base($t);
            $t->string('name');
            $t->string('slug')->unique();
            $t->unsignedInteger('teamSize')->default(5);
            $t->foreignId('icon_id')->nullable()->constrained('media')->nullOnDelete();
        });
        Schema::create('player_profiles', function (Blueprint $t) use ($base) {
            $base($t);
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('nickname');
            foreach (['firstName', 'lastName', 'country'] as $field) {
                $t->string($field)->nullable();
            }
            $t->text('bio')->nullable();
            $t->foreignId('avatar_id')->nullable()->constrained('media')->nullOnDelete();
        });
        Schema::create('teams', function (Blueprint $t) use ($base) {
            $base($t);
            $t->string('name');
            $t->string('slug');
            $t->text('description')->nullable();
            $t->boolean('isActive')->default(true);
            $t->foreignId('captain_id')->constrained('users');
            $t->foreignId('logo_id')->nullable()->constrained('media')->nullOnDelete();
        });
        Schema::create('discipline_team', function (Blueprint $t) {
            $t->foreignId('team_id')->constrained()->cascadeOnDelete();
            $t->foreignId('discipline_id')->constrained();
            $t->primary(['team_id', 'discipline_id']);
        });
        Schema::create('team_memberships', function (Blueprint $t) use ($base) {
            $base($t);
            $t->foreignId('team_id')->constrained();
            $t->foreignId('player_id')->constrained('users');
            $t->string('membershipStatus')->default('active');
            $t->string('position')->default('main');
            $t->timestamp('joinedAt')->nullable();
            $t->timestamp('leftAt')->nullable();
            $t->unique(['team_id', 'player_id']);
        });
        Schema::create('captain_transfers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('team_id')->constrained();
            $t->foreignId('previous_captain_id')->constrained('users');
            $t->foreignId('next_captain_id')->constrained('users');
            $t->timestamp('transferredAt');
        });
        Schema::create('tournaments', function (Blueprint $t) use ($base) {
            $base($t);
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->text('rules')->nullable();
            $t->string('format')->default('single_elimination');
            $t->unsignedInteger('teamSize')->default(5);
            $t->unsignedInteger('maxTeams')->default(16);
            $t->string('phase')->default('draft');
            foreach (['registrationStartsAt', 'registrationEndsAt', 'startsAt', 'publishedAt'] as $field) {
                $t->timestamp($field)->nullable();
            }
            $t->foreignId('discipline_id')->constrained();
            $t->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
        });
        Schema::create('tournament_registrations', function (Blueprint $t) use ($base) {
            $base($t);
            $t->foreignId('tournament_id')->constrained();
            $t->foreignId('team_id')->constrained();
            $t->foreignId('submitted_by_id')->constrained('users');
            $t->string('registrationStatus')->default('pending');
            $t->timestamp('submittedAt');
            $t->text('rejectionReason')->nullable();
            $t->unsignedInteger('seed')->nullable();
        });
        Schema::create('registration_players', function (Blueprint $t) use ($base) {
            $base($t);
            $t->foreignId('registration_id')->constrained('tournament_registrations');
            $t->foreignId('player_id')->constrained('users');
            $t->string('nicknameSnapshot');
            $t->string('position')->default('main');
            $t->unique(['registration_id', 'player_id']);
        });
        Schema::create('matches', function (Blueprint $t) use ($base) {
            $base($t);
            $t->foreignId('tournament_id')->constrained();
            $t->unsignedInteger('round');
            $t->unsignedInteger('position');
            foreach (['team_a_id', 'team_b_id', 'winner_id'] as $field) {
                $t->foreignId($field)->nullable()->constrained('teams');
            }
            $t->foreignId('next_match_id')->nullable()->constrained('matches');
            $t->string('nextSlot')->nullable();
            $t->unsignedInteger('scoreA')->nullable();
            $t->unsignedInteger('scoreB')->nullable();
            $t->string('matchStatus')->default('pending');
            $t->timestamp('scheduledAt')->nullable();
            $t->unique(['tournament_id', 'round', 'position']);
        });
        Schema::create('content', function (Blueprint $t) use ($base) {
            $base($t);
            $t->string('kind')->index();
            $t->json('payload');
            $t->timestamp('publishedAt')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['content', 'matches', 'registration_players', 'tournament_registrations', 'tournaments', 'captain_transfers', 'team_memberships', 'discipline_team', 'teams', 'player_profiles', 'disciplines', 'media', 'personal_access_tokens'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['username', 'role_type', 'blocked']));
    }
};
