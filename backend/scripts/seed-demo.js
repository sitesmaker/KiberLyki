/* eslint-disable no-console */
const fs = require('node:fs/promises');
const path = require('node:path');
const { createStrapi } = require('@strapi/strapi');

const PASSWORD = 'Cyber2026!';
const teamsData = [
  {
    name: 'Northern Wolves', slug: 'northern-wolves', disciplines: ['dota-2', 'counter-strike-2'],
    description: 'Северная команда с агрессивным стилем. Играет быстрые раунды в CS2 и любит активные драфты в Dota 2.',
    players: [
      ['frostcaptain', 'Frost', 'Алексей', 'Морозов', 'Россия', 'Капитан и координатор. Отвечает за драфты и спокойные решения в ключевых матчах.'],
      ['blizzard', 'Blizzard', 'Илья', 'Ветров', 'Россия', 'Универсальный игрок основного состава, предпочитает инициативные роли.'],
      ['northstar', 'NorthStar', 'Мария', 'Северина', 'Беларусь', 'Тактический игрок и главный аналитик соперников внутри состава.'],
      ['polar', 'Polar', 'Денис', 'Орлов', 'Казахстан', 'Сильный механический игрок, создающий пространство для команды.'],
      ['tundrafox', 'TundraFox', 'Антон', 'Лисов', 'Россия', 'Надёжный игрок поддержки, сохраняющий темп в сложных матчах.'],
    ],
  },
  {
    name: 'Volga Storm', slug: 'volga-storm', disciplines: ['dota-2', 'valorant'],
    description: 'Молодой состав из Поволжья. Команда делает ставку на дисциплину, подготовку и неожиданные тактики.',
    players: [
      ['stormcaptain', 'StormLead', 'Никита', 'Громов', 'Россия', 'Капитан, который собирает стратегию команды и контролирует коммуникацию.'],
      ['spark', 'Spark', 'Елена', 'Искрова', 'Россия', 'Инициатор команды с хорошим пониманием карты.'],
      ['river', 'River', 'Павел', 'Речной', 'Россия', 'Гибкий игрок, быстро адаптирующийся к плану соперника.'],
      ['volt', 'Volt', 'Максим', 'Токарев', 'Казахстан', 'Любит рискованные выходы и часто открывает раунды.'],
      ['anchor', 'Anchor', 'София', 'Волкова', 'Беларусь', 'Опора состава: удерживает позиции и помогает молодым игрокам.'],
    ],
  },
  {
    name: 'Cyber Bears', slug: 'cyber-bears', disciplines: ['counter-strike-2', 'valorant'],
    description: 'Опытная команда с сильной стрельбой и размеренным контролем карты. Регулярно участвует в региональных лигах.',
    players: [
      ['bearcaptain', 'OldBear', 'Сергей', 'Медведев', 'Россия', 'Опытный капитан и внутриигровой лидер.'],
      ['claw', 'Claw', 'Роман', 'Когтев', 'Россия', 'Снайпер команды, предпочитает терпеливую игру.'],
      ['honey', 'Honey', 'Дарья', 'Медова', 'Беларусь', 'Игрок поддержки, отвечающий за гранаты и информацию.'],
      ['forest', 'ForestX', 'Артём', 'Лесной', 'Россия', 'Уверенно действует в одиночных позициях.'],
      ['roar', 'Roar', 'Вадим', 'Рыков', 'Казахстан', 'Энергичный энтри-фрагер основного состава.'],
    ],
  },
  {
    name: 'Neon Dragons', slug: 'neon-dragons', disciplines: ['dota-2', 'counter-strike-2', 'valorant'],
    description: 'Мультидисциплинарная команда с ярким атакующим стилем. Один состав тренируется сразу в трёх направлениях.',
    players: [
      ['dragoncaptain', 'Neon', 'Алина', 'Драконова', 'Россия', 'Капитан и основатель команды, отвечает за расписание и турнирные заявки.'],
      ['ember', 'Ember', 'Олег', 'Жаров', 'Россия', 'Атакующий игрок, уверенно принимает быстрые решения.'],
      ['pixel', 'Pixel', 'Кирилл', 'Точкин', 'Беларусь', 'Технический игрок с большим пулом ролей.'],
      ['nova', 'Nova', 'Виктория', 'Звёздная', 'Казахстан', 'Специалист по поздним стадиям матчей.'],
      ['scale', 'Scale', 'Михаил', 'Чешуев', 'Россия', 'Командный игрок, который закрывает необходимые позиции.'],
    ],
  },
];

async function deleteAll(strapi, uid) {
  const rows = await strapi.db.query(uid).findMany({ select: ['id'] });
  for (const row of rows) await strapi.db.query(uid).delete({ where: { id: row.id } });
}

async function main() {
  const app = await createStrapi({ distDir: path.resolve(__dirname, '..', 'dist') }).load();
  try {
    console.log('Очистка старых турнирных данных и команд...');
    for (const uid of [
      'api::registration-player.registration-player', 'api::match.match',
      'api::tournament-registration.tournament-registration', 'api::captain-transfer.captain-transfer',
      'api::team-membership.team-membership', 'api::team.team', 'api::tournament.tournament',
    ]) await deleteAll(app, uid);

    const demoUsers = await app.db.query('plugin::users-permissions.user').findMany({ where: { email: { $endsWith: '@demo.kiberlyki.local' } } });
    for (const user of demoUsers) {
      const profile = await app.db.query('api::player-profile.player-profile').findOne({ where: { user: user.id } });
      if (profile) await app.db.query('api::player-profile.player-profile').delete({ where: { id: profile.id } });
      await app.db.query('plugin::users-permissions.user').delete({ where: { id: user.id } });
    }

    const authenticatedRole = await app.db.query('plugin::users-permissions.role').findOne({ where: { type: 'authenticated' } });
    const organizerRole = await app.db.query('plugin::users-permissions.role').findOne({ where: { type: 'organizer' } });
    if (!authenticatedRole) throw new Error('Роль Authenticated не найдена. Сначала один раз запустите Strapi.');

    const disciplineSpecs = [
      ['Dota 2', 'dota-2', 5], ['Counter-Strike 2', 'counter-strike-2', 5], ['Valorant', 'valorant', 5],
    ];
    const disciplines = {};
    for (const [name, slug, teamSize] of disciplineSpecs) {
      let item = await app.db.query('api::discipline.discipline').findOne({ where: { slug } });
      if (!item) item = await app.documents('api::discipline.discipline').create({ data: { name, slug, teamSize } });
      disciplines[slug] = item;
    }

    const createdTeams = [];
    const credentials = [];
    for (const teamSpec of teamsData) {
      const users = [];
      for (let index = 0; index < teamSpec.players.length; index += 1) {
        const [username, nickname, firstName, lastName, country, bio] = teamSpec.players[index];
        const email = `${username}@demo.kiberlyki.local`;
        const user = await app.plugin('users-permissions').service('user').add({
          username, email, password: PASSWORD, provider: 'local', confirmed: true, blocked: false, role: authenticatedRole.id,
        });
        await app.documents('api::player-profile.player-profile').create({
          data: { nickname, firstName, lastName, country, bio, user: user.id },
        });
        users.push(user);
        credentials.push({ team: teamSpec.name, captain: index === 0, username, email, nickname });
      }
      const team = await app.documents('api::team.team').create({
        data: {
          name: teamSpec.name, slug: teamSpec.slug, description: teamSpec.description, isActive: true,
          discipline: teamSpec.disciplines.map((slug) => disciplines[slug].documentId), captain: users[0].id,
        },
      });
      for (const user of users) {
        await app.documents('api::team-membership.team-membership').create({
          data: { team: team.documentId, player: user.id, membershipStatus: 'active', position: 'main', joinedAt: new Date().toISOString() },
        });
      }
      createdTeams.push({ ...team, users });
    }

    let organizer = await app.db.query('plugin::users-permissions.user').findOne({ where: { email: 'organizer@demo.kiberlyki.local' } });
    if (!organizer && organizerRole) organizer = await app.plugin('users-permissions').service('user').add({ username: 'demo-organizer', email: 'organizer@demo.kiberlyki.local', password: PASSWORD, provider: 'local', confirmed: true, blocked: false, role: organizerRole.id });

    const now = Date.now();
    const day = 24 * 60 * 60 * 1000;
    const tournamentSpecs = [
      { title: 'Dota 2: Кубок Легенд', slug: 'dota-cup-legends', phase: 'finished', discipline: 'dota-2', start: now - 30 * day, regStart: now - 60 * day, regEnd: now - 35 * day, description: 'Завершённый турнир для демонстрации полной сетки и чемпиона.' },
      { title: 'CS2: Осенний штурм', slug: 'cs2-autumn-assault', phase: 'active', discipline: 'counter-strike-2', start: now - day, regStart: now - 30 * day, regEnd: now - 5 * day, description: 'Турнир идёт сейчас: полуфиналы завершены, финальный матч продолжается.' },
      { title: 'Valorant: Neon Open', slug: 'valorant-neon-open', phase: 'registration', discipline: 'valorant', start: now + 21 * day, regStart: now - day, regEnd: now + 14 * day, description: 'Будущий открытый турнир. Капитаны подходящих команд могут отправить заявку.' },
    ];
    const tournaments = [];
    for (const spec of tournamentSpecs) {
      const tournament = await app.documents('api::tournament.tournament').create({
        status: 'published',
        data: { title: spec.title, slug: spec.slug, description: spec.description, rules: 'Формат: Single Elimination. Проигравшая команда выбывает. Победитель проходит в следующий раунд.\n\nПеред матчем капитаны подтверждают состав. Результат фиксирует организатор.', format: 'single_elimination', teamSize: 5, maxTeams: 4, registrationStartsAt: new Date(spec.regStart).toISOString(), registrationEndsAt: new Date(spec.regEnd).toISOString(), startsAt: new Date(spec.start).toISOString(), phase: spec.phase, discipline: disciplines[spec.discipline].documentId },
      });
      tournaments.push(tournament);
    }

    async function registration(tournament, team, seed, registrationStatus = 'approved') {
      const item = await app.documents('api::tournament-registration.tournament-registration').create({ data: { tournament: tournament.documentId, team: team.documentId, submittedBy: team.users[0].id, registrationStatus, seed, submittedAt: new Date().toISOString() } });
      for (const user of team.users) {
        const profile = await app.db.query('api::player-profile.player-profile').findOne({ where: { user: user.id } });
        await app.documents('api::registration-player.registration-player').create({ data: { registration: item.documentId, player: user.id, nicknameSnapshot: profile.nickname, position: 'main' } });
      }
      return item;
    }
    for (const tournament of tournaments.slice(0, 2)) for (let i = 0; i < createdTeams.length; i += 1) await registration(tournament, createdTeams[i], i + 1);
    await registration(tournaments[2], createdTeams[1], 1);
    await registration(tournaments[2], createdTeams[2], 2, 'pending');

    async function match(tournament, round, position, teamA, teamB, scoreA, scoreB, winner, matchStatus) {
      return app.documents('api::match.match').create({ data: { tournament: tournament.documentId, round, position, teamA: teamA?.documentId ?? null, teamB: teamB?.documentId ?? null, scoreA, scoreB, winner: winner?.documentId ?? null, matchStatus, scheduledAt: new Date(now + (round + position) * 3600000).toISOString() } });
    }
    const [wolves, storm, bears, dragons] = createdTeams;
    await match(tournaments[0], 1, 1, wolves, dragons, 2, 1, wolves, 'finished');
    await match(tournaments[0], 1, 2, storm, bears, 0, 2, bears, 'finished');
    await match(tournaments[0], 2, 1, wolves, bears, 3, 1, wolves, 'finished');
    await match(tournaments[1], 1, 1, wolves, bears, 13, 9, wolves, 'finished');
    await match(tournaments[1], 1, 2, storm, dragons, 7, 13, dragons, 'finished');
    await match(tournaments[1], 2, 1, wolves, dragons, 1, 0, null, 'live');

    const lines = [
      'KiberLyki — демонстрационные доступы',
      `Создано: ${new Date().toLocaleString('ru-RU')}`,
      '',
      `Общий временный пароль для всех аккаунтов: ${PASSWORD}`,
      'После входа игрок может изменить пароль в разделе «Мой профиль».',
      '',
      'ОРГАНИЗАТОР',
      'Логин: demo-organizer',
      'Email: organizer@demo.kiberlyki.local',
      `Пароль: ${PASSWORD}`,
      '',
    ];
    for (const team of teamsData) {
      lines.push(`КОМАНДА: ${team.name}`);
      for (const account of credentials.filter((item) => item.team === team.name)) lines.push(`${account.captain ? '[КАПИТАН]' : '[ИГРОК]'} ${account.nickname} — логин: ${account.username}; email: ${account.email}; пароль: ${PASSWORD}`);
      lines.push('');
    }
    lines.push('Что проверить:', '1. Войти капитаном и открыть /cabinet.', '2. Добавить зарегистрированного игрока по username/email или удалить игрока.', '3. Открыть /teams и перейти в карточку команды.', '4. Открыть любой турнир: завершённый показывает чемпиона, текущий — live-финал, будущий — регистрацию.', '5. Войти организатором, чтобы принимать заявки и фиксировать результаты матчей.');
    const credentialsPath = path.resolve(__dirname, '..', '..', 'docs', 'demo-access.txt');
    await fs.mkdir(path.dirname(credentialsPath), { recursive: true });
    await fs.writeFile(credentialsPath, `${lines.join('\r\n')}\r\n`, 'utf8');
    console.log(`Готово: ${createdTeams.length} команды, ${credentials.length} игроков, ${tournaments.length} турнира.`);
    console.log(`Доступы: ${credentialsPath}`);
  } finally {
    await app.destroy();
  }
}

main().catch((error) => { console.error(error); process.exitCode = 1; });
