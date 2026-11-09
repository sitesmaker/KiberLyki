<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Seeder;

class HomepageContentSeeder extends Seeder
{
    public function run(): void
    {
        // Merge editorial fields only: the existing uploaded video stays in place.
        $screen = Content::firstOrNew(['kind' => 'first-screen']);
        $screen->payload = array_merge($screen->payload ?? [], [
            'title' => "ТВОЯ ИГРА.\nТВОЯ КОМАНДА.",
            'content' => 'Собирай состав. Принимай вызов. Выходи в игру вместе с киберспортивным сообществом Великих Лук.',
        ]);
        $screen->publishedAt ??= now();
        $screen->save();
        $about = Content::firstOrNew(['kind' => 'about']);
        $about->payload = array_merge($about->payload ?? [], [
            'title' => "Объединяет игра.\nПобеждает команда.",
            'description' => "Место встречи игроков, команд и тех, кто любит киберспорт. Здесь можно собрать свой состав, заявиться на турнир и пройти путь от первого матча до финала.\n\nМы за азарт соревнований, уважение к сопернику и игру, в которой каждый становится сильнее.",
        ]);
        $about->publishedAt ??= now();
        $about->save();
    }
}
