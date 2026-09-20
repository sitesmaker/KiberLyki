import { factories } from '@strapi/strapi';
import { errors } from '@strapi/utils';
import { nextPowerOfTwo, seedOrder } from '../../../utils/bracket';

const { ValidationError: BadRequestError, ForbiddenError, NotFoundError } = errors;

function requireOrganizer(ctx: any) {
  if (ctx.state.user?.role?.type !== 'organizer') throw new ForbiddenError('Действие доступно только организатору турниров');
}

export default factories.createCoreController('api::tournament.tournament', ({ strapi }) => ({
  async details(ctx) {
    const tournament = await strapi.db.query('api::tournament.tournament').findOne({
      where: { documentId: ctx.params.documentId, publishedAt: { $notNull: true } },
      populate: ['discipline', 'cover', 'matches.teamA', 'matches.teamB', 'matches.winner', 'registrations.team'],
    });
    if (!tournament) throw new NotFoundError('Турнир не найден');

    const teamView = (team: any) => team ? ({ id: team.id, documentId: team.documentId, name: team.name, slug: team.slug }) : null;
    return ctx.send({
      data: {
        ...tournament,
        matches: (tournament.matches ?? []).map((match: any) => ({
          id: match.id,
          documentId: match.documentId,
          round: match.round,
          position: match.position,
          scoreA: match.scoreA,
          scoreB: match.scoreB,
          matchStatus: match.matchStatus,
          scheduledAt: match.scheduledAt,
          teamA: teamView(match.teamA),
          teamB: teamView(match.teamB),
          winner: teamView(match.winner),
        })),
        registrations: (tournament.registrations ?? [])
          .filter((registration: any) => registration.registrationStatus === 'approved')
          .map((registration: any) => ({
            id: registration.id,
            documentId: registration.documentId,
            registrationStatus: registration.registrationStatus,
            seed: registration.seed,
            submittedAt: registration.submittedAt,
            team: teamView(registration.team),
          })),
      },
    });
  },

  async generateBracket(ctx) {
    requireOrganizer(ctx);
    const tournament = await strapi.documents('api::tournament.tournament').findOne({
      documentId: ctx.params.documentId,
    });
    if (!tournament) throw new NotFoundError('Турнир не найден');
    if (tournament.format !== 'single_elimination') throw new BadRequestError('Поддерживается только single elimination');

    const existingMatches = await strapi.db.query('api::match.match').count({ where: { tournament: tournament.id } });
    if (existingMatches > 0) throw new BadRequestError('Сетка для турнира уже создана');

    const registrations = await strapi.db.query('api::tournament-registration.tournament-registration').findMany({
      where: { tournament: tournament.id, registrationStatus: 'approved' },
      populate: ['team'],
      orderBy: [{ seed: 'asc' }, { submittedAt: 'asc' }],
    });
    if (registrations.length < 2) throw new BadRequestError('Для сетки нужно минимум две одобренные команды');

    const bracketSize = nextPowerOfTwo(registrations.length);
    const rounds = Math.log2(bracketSize);
    const byRound = new Map<number, any[]>();

    await strapi.db.transaction(async () => {
      for (let round = rounds; round >= 1; round -= 1) {
        const matchCount = bracketSize / (2 ** round);
        const matches: any[] = [];
        for (let position = 1; position <= matchCount; position += 1) {
          const next = byRound.get(round + 1)?.[Math.floor((position - 1) / 2)];
          const match = await strapi.documents('api::match.match').create({
            data: {
              tournament: tournament.documentId,
              round,
              position,
              matchStatus: 'pending',
              nextMatch: next?.documentId,
              nextSlot: next ? (position % 2 === 1 ? 'A' : 'B') : null,
            },
          });
          matches.push(match);
        }
        byRound.set(round, matches);
      }

      const slots = seedOrder(bracketSize).map((seed) => registrations[seed - 1]?.team ?? null);
      const firstRound = byRound.get(1) ?? [];
      for (let index = 0; index < firstRound.length; index += 1) {
        const teamA = slots[index * 2];
        const teamB = slots[index * 2 + 1];
        const automaticWinner = teamA && !teamB ? teamA : teamB && !teamA ? teamB : null;
        const match = firstRound[index];

        await strapi.documents('api::match.match').update({
          documentId: match.documentId,
          data: {
            teamA: teamA?.documentId ?? null,
            teamB: teamB?.documentId ?? null,
            winner: automaticWinner?.documentId ?? null,
            matchStatus: automaticWinner ? 'finished' : 'pending',
          },
        });

        if (automaticWinner && match.nextMatch) {
          await strapi.documents('api::match.match').update({
            documentId: match.nextMatch.documentId ?? match.nextMatch,
            data: { [match.nextSlot === 'A' ? 'teamA' : 'teamB']: automaticWinner.documentId },
          });
        }
      }

      await strapi.documents('api::tournament.tournament').update({
        documentId: tournament.documentId,
        data: { phase: 'active' },
      });
    });

    return ctx.send({ data: { bracketSize, rounds } });
  },

  async setResult(ctx) {
    requireOrganizer(ctx);
    const { scoreA, scoreB } = ctx.request.body?.data ?? {};
    if (!Number.isInteger(scoreA) || !Number.isInteger(scoreB) || scoreA < 0 || scoreB < 0 || scoreA === scoreB) {
      throw new BadRequestError('Счёт должен состоять из разных неотрицательных целых чисел');
    }

    const match = await strapi.documents('api::match.match').findOne({
      documentId: ctx.params.matchId,
      populate: ['teamA', 'teamB', 'nextMatch', 'tournament'],
    });
    if (!match || match.tournament?.documentId !== ctx.params.documentId) throw new NotFoundError('Матч не найден');
    if (!match.teamA || !match.teamB) throw new BadRequestError('У матча ещё нет обеих команд');
    if (match.matchStatus === 'finished') throw new BadRequestError('Результат матча уже зафиксирован');

    const winner = scoreA > scoreB ? match.teamA : match.teamB;
    await strapi.db.transaction(async () => {
      await strapi.documents('api::match.match').update({
        documentId: match.documentId,
        data: { scoreA, scoreB, winner: winner.documentId, matchStatus: 'finished' },
      });

      if (match.nextMatch) {
        await strapi.documents('api::match.match').update({
          documentId: match.nextMatch.documentId,
          data: { [match.nextSlot === 'A' ? 'teamA' : 'teamB']: winner.documentId },
        });
      } else {
        await strapi.documents('api::tournament.tournament').update({
          documentId: match.tournament.documentId,
          data: { phase: 'finished' },
        });
      }
    });

    return ctx.send({ data: { winner: winner.documentId } });
  },
}));
