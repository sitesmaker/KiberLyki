import { factories } from '@strapi/strapi';
import { errors } from '@strapi/utils';

const { ValidationError: BadRequestError, ForbiddenError, NotFoundError } = errors;

function requireOrganizer(ctx: any) {
  if (ctx.state.user?.role?.type !== 'organizer') {
    throw new ForbiddenError('Действие доступно только организатору турниров');
  }
}

export default factories.createCoreController('api::tournament-registration.tournament-registration', ({ strapi }) => ({
  async listForTournament(ctx) {
    requireOrganizer(ctx);
    const tournament = await strapi.documents('api::tournament.tournament').findOne({ documentId: ctx.params.documentId });
    if (!tournament) throw new NotFoundError('Турнир не найден');
    const data = await strapi.db.query('api::tournament-registration.tournament-registration').findMany({
      where: { tournament: tournament.id },
      populate: ['team.logo', 'submittedBy', 'players.player'],
      orderBy: { submittedAt: 'asc' },
    });
    return ctx.send({ data });
  },

  async mine(ctx) {
    const data = await strapi.db.query('api::tournament-registration.tournament-registration').findMany({
      where: { submittedBy: ctx.state.user.id },
      populate: ['tournament.discipline', 'tournament.cover', 'team.logo', 'players.player'],
      orderBy: { submittedAt: 'desc' },
    });
    return ctx.send({ data });
  },

  async registerTeam(ctx) {
    const userId = ctx.state.user.id;
    const { tournamentId, playerIds } = ctx.request.body?.data ?? {};
    if (!tournamentId) throw new BadRequestError('Не выбран турнир');

    const tournament = await strapi.documents('api::tournament.tournament').findOne({
      documentId: tournamentId,
      status: 'published',
      populate: ['discipline'],
    });
    if (!tournament) throw new NotFoundError('Турнир не найден');

    const now = Date.now();
    if (tournament.phase !== 'registration') throw new BadRequestError('Регистрация на турнир закрыта');
    if (now < new Date(tournament.registrationStartsAt).getTime() || now > new Date(tournament.registrationEndsAt).getTime()) {
      throw new BadRequestError('Сейчас регистрация на турнир недоступна');
    }

    const team = await strapi.db.query('api::team.team').findOne({
      where: { captain: userId, isActive: true },
      populate: ['discipline', 'memberships.player'],
    });
    if (!team) throw new BadRequestError('Сначала создайте команду или войдите как её капитан');
    const teamDisciplines = Array.isArray(team.discipline) ? team.discipline : team.discipline ? [team.discipline] : [];
    if (!teamDisciplines.some((discipline: any) => discipline.id === tournament.discipline?.id)) {
      throw new BadRequestError('Команда не участвует в дисциплине этого турнира');
    }

    const duplicate = await strapi.db.query('api::tournament-registration.tournament-registration').findOne({
      where: { tournament: tournament.id, team: team.id, registrationStatus: { $ne: 'withdrawn' } },
    });
    if (duplicate) throw new BadRequestError('Команда уже подавала заявку на этот турнир');

    const occupied = await strapi.db.query('api::tournament-registration.tournament-registration').count({
      where: { tournament: tournament.id, registrationStatus: { $in: ['pending', 'approved'] } },
    });
    if (occupied >= tournament.maxTeams) throw new BadRequestError('Достигнут лимит команд');

    const activeMembers = team.memberships.filter((item: any) => item.membershipStatus === 'active' && item.position !== 'coach');
    const selectedIds = Array.isArray(playerIds) && playerIds.length > 0 ? playerIds : activeMembers.map((item: any) => item.player.id);
    const selected = activeMembers.filter((item: any) => selectedIds.includes(item.player.id));
    if (selected.length !== tournament.teamSize) {
      throw new BadRequestError(`В заявке должно быть ${tournament.teamSize} игроков`);
    }

    const registration = await strapi.db.transaction(async () => {
      const created = await strapi.documents('api::tournament-registration.tournament-registration').create({
        data: {
          tournament: tournament.documentId,
          team: team.documentId,
          submittedBy: userId,
          registrationStatus: 'pending',
          submittedAt: new Date().toISOString(),
        },
      });

      for (const membership of selected) {
        const profile = await strapi.db.query('api::player-profile.player-profile').findOne({
          where: { user: membership.player.id },
        });
        await strapi.documents('api::registration-player.registration-player').create({
          data: {
            registration: created.documentId,
            player: membership.player.id,
            nicknameSnapshot: profile?.nickname ?? membership.player.username,
            position: 'main',
          },
        });
      }
      return created;
    });

    ctx.status = 201;
    return ctx.send({ data: registration });
  },

  async withdraw(ctx) {
    const registration = await strapi.documents('api::tournament-registration.tournament-registration').findOne({
      documentId: ctx.params.documentId,
      populate: ['submittedBy', 'tournament'],
    });
    if (!registration) throw new NotFoundError('Заявка не найдена');
    if (registration.submittedBy?.id !== ctx.state.user.id) throw new ForbiddenError('Это не ваша заявка');
    if (!['pending', 'approved'].includes(registration.registrationStatus)) throw new BadRequestError('Заявку уже нельзя отозвать');
    if (registration.tournament?.phase === 'active') throw new BadRequestError('Турнир уже начался');

    const data = await strapi.documents('api::tournament-registration.tournament-registration').update({
      documentId: registration.documentId,
      data: { registrationStatus: 'withdrawn' },
    });
    return ctx.send({ data });
  },

  async review(ctx) {
    requireOrganizer(ctx);
    const { registrationStatus, rejectionReason, seed } = ctx.request.body?.data ?? {};
    if (!['approved', 'rejected'].includes(registrationStatus)) throw new BadRequestError('Допустимы статусы approved и rejected');

    const registration = await strapi.documents('api::tournament-registration.tournament-registration').findOne({
      documentId: ctx.params.documentId,
    });
    if (!registration) throw new NotFoundError('Заявка не найдена');
    if (registration.registrationStatus !== 'pending') throw new BadRequestError('Решение по заявке уже принято');

    const data = await strapi.documents('api::tournament-registration.tournament-registration').update({
      documentId: registration.documentId,
      data: { registrationStatus, rejectionReason: registrationStatus === 'rejected' ? rejectionReason : null, seed: registrationStatus === 'approved' ? seed : null },
    });
    return ctx.send({ data });
  },
}));
