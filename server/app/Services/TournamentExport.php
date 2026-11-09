<?php

namespace App\Services;

use App\Models\Tournament;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TournamentExport
{
    private const STATUSES = [
        'draft' => 'Подготовка', 'registration' => 'Регистрация', 'check_in' => 'Подтверждение участия',
        'active' => 'Идёт турнир', 'finished' => 'Завершён', 'cancelled' => 'Отменён',
        'pending' => 'На рассмотрении', 'approved' => 'Принята', 'rejected' => 'Отклонена', 'withdrawn' => 'Отозвана',
    ];

    public function workbook(Tournament $tournament): Spreadsheet
    {
        $tournament->load(['discipline', 'cover', 'registrations.team.captain.profile', 'registrations.players.player.profile', 'matches.teamA', 'matches.teamB', 'matches.winner']);
        $book = new Spreadsheet;
        $book->getProperties()->setCreator('КиберЛуки')->setTitle($tournament->title);
        $book->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $meta = [
            ['Название', $tournament->title], ['Адрес', $tournament->slug],
            ['Дисциплина', $tournament->discipline?->name], ['Этап', self::STATUSES[$tournament->phase] ?? $tournament->phase],
            ['Формат', $tournament->format === 'single_elimination' ? 'Олимпийская система' : $tournament->format],
            ['Игроков в заявке', $tournament->teamSize], ['Максимум команд', $tournament->maxTeams],
            ['Начало регистрации (Москва)', $this->date($tournament->registrationStartsAt)],
            ['Конец регистрации (Москва)', $this->date($tournament->registrationEndsAt)],
            ['Начало турнира (Москва)', $this->date($tournament->startsAt)],
            ['Опубликован', $tournament->publishedAt ? 'Да' : 'Нет'],
            ['Описание', $tournament->description], ['Правила', $tournament->rules], ['Обложка', $tournament->cover?->url],
            ['Всего заявок', $tournament->registrations->count()], ['Принято команд', $tournament->registrations->where('registrationStatus', 'approved')->count()],
            ['Дата выгрузки (Москва)', $this->date(now())],
            ['О составах', 'Составы зафиксированы в заявках. Почта и ФИО — текущие данные аккаунтов; пустые значения не заполнены.'],
        ];
        $this->sheet($book, 'Турнир', ['Параметр', 'Значение'], $meta, [35, 95]);
        $teams = [];
        $players = [];
        $rosterGroups = [];
        foreach ($tournament->registrations->sortBy('id') as $registration) {
            $team = $registration->team;
            $captain = $team?->captain;
            $status = self::STATUSES[$registration->registrationStatus] ?? $registration->registrationStatus;
            $teams[] = [$team?->name, $status, $registration->seed, $registration->players->count(), $captain?->profile?->nickname ?? $captain?->username, $captain?->email, $this->date($registration->submittedAt), $registration->rejectionReason, $team?->description];
            $firstRow = count($players) + 2;
            foreach ($registration->players->sortBy('id') as $entry) {
                $player = $entry->player;
                $profile = $player?->profile;
                $players[] = [count($players) + 2 === $firstRow ? $team?->name : '', $entry->nicknameSnapshot, $profile?->firstName, $profile?->lastName, ['main' => 'Основной состав', 'substitute' => 'Замена', 'coach' => 'Тренер'][$entry->position] ?? $entry->position, $entry->player_id === $team?->captain_id ? 'Да' : '', $player?->email, $profile?->country];
            }
            if (count($players) + 1 >= $firstRow) {
                $rosterGroups[] = [$firstRow, count($players) + 1];
            }
        }
        $this->sheet($book, 'Команды', ['Команда', 'Статус заявки', 'Посев', 'Игроков', 'Капитан (текущий)', 'Почта капитана', 'Подана (Москва)', 'Причина отклонения', 'Описание команды'], $teams, [28, 22, 10, 12, 25, 38, 24, 40, 50]);
        $this->sheet($book, 'Составы', ['Команда', 'Никнейм в заявке', 'Имя', 'Фамилия', 'Позиция', 'Капитан (текущий)', 'Почта', 'Страна'], $players, [28, 26, 20, 24, 23, 23, 38, 20]);
        $roster = $book->getSheetByName('Составы');
        foreach ($rosterGroups as $index => [$first, $last]) {
            if ($last > $first) {
                $roster->mergeCells("A{$first}:A{$last}");
            }
            $roster->getStyle("A{$first}")->getAlignment()->setVertical('center');
            $roster->getStyle("A{$first}")->getFont()->setBold(true);
            $roster->getStyle("A{$first}:H{$last}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($index % 2 === 0 ? 'FFF0F5F4' : 'FFFFFFFF');
        }
        $matches = [];
        foreach ($tournament->matches as $match) {
            $matches[] = [$match->round, $match->position, $match->teamA?->name, $match->scoreA, $match->teamB?->name, $match->scoreB, $match->winner?->name, ['pending' => 'Ожидается', 'scheduled' => 'Запланирован', 'live' => 'Идёт', 'finished' => 'Завершён'][$match->matchStatus] ?? $match->matchStatus, $this->date($match->scheduledAt)];
        }
        $this->sheet($book, 'Матчи', ['Раунд', 'Матч в раунде', 'Команда A', 'Счёт A', 'Команда B', 'Счёт B', 'Победитель', 'Статус матча', 'Начало (Москва)'], $matches, [12, 18, 28, 12, 28, 12, 28, 22, 24]);
        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function date($value): string
    {
        return $value ? Carbon::parse($value)->timezone('Europe/Moscow')->format('d.m.Y H:i') : '';
    }

    private function sheet(Spreadsheet $book, string $title, array $headers, array $rows, array $widths): void
    {
        $sheet = $book->getSheetCount() === 1 && $book->getActiveSheet()->getHighestRow() === 1 && $book->getActiveSheet()->getCell('A1')->getValue() === null
            ? $book->getActiveSheet() : $book->createSheet();
        $sheet->setTitle($title);
        foreach ([$headers, ...$rows] as $r => $row) {
            foreach ($row as $c => $value) {
                // User input must remain text, never executable Excel formulas.
                $sheet->setCellValueExplicit([$c + 1, $r + 1], $value ?? '', is_int($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
            }
        }
        $last = Coordinate::stringFromColumnIndex(count($headers));
        $end = count($rows) + 1;
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true)->getColor()->setARGB('FF0B2927');
        $sheet->getStyle("A1:{$last}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF9BF7D0');
        $sheet->getStyle("A1:{$last}{$end}")->getAlignment()->setVertical('top')->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(34);
        foreach ($widths as $i => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($width);
        }
        for ($r = 2; $r <= $end; $r++) {
            if ($r % 2 === 0) {
                $sheet->getStyle("A{$r}:{$last}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF0F5F4');
            }
        }
        $sheet->freezePane('A2');
        if (! in_array($title, ['Турнир', 'Составы']) && $rows) {
            $sheet->setAutoFilter("A1:{$last}{$end}");
        }
        $sheet->getSheetView()->setZoomScale(90);
        $sheet->getPageSetup()->setOrientation('landscape')->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
    }
}
