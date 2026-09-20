import { factories } from '@strapi/strapi';
import { errors } from '@strapi/utils';

const { ValidationError: BadRequestError, ForbiddenError, NotFoundError } = errors;

function createSlug(value: string) {
  return value
    .toLowerCase()
    .normalize('NFKD')
    .replace(/[^\p{L}\p{N}]+/gu, '-')
    .replace(/^-|-$/g, '');
}

async function findCaptainTeam(strapi: any, documentId: string, userId: number) {
  const team = await strapi.documents('api::team.team').findOne({
    documentId,
    populate: ['captain', 'discipline', 'memberships.player'],
  });

  if (!team) throw new NotFoundError('Команда не найдена');
  if (team.captain?.id !== userId) throw new ForbiddenError('Действие доступно только капитану команды');
  return team;
}

async function withPlayerProfiles(strapi: any, team: any) {
  if (!team) return null;
  const memberships = team.memberships ?? [];
  const playerIds = memberships.map((membership: any) => membership.player?.id).filter(Boolean);
  const profiles = playerIds.length
    ? await strapi.db.query('api::player-profile.player-profile').findMany({
        where: { user: { id: { $in: playerIds } } },
        populate: ['user', 'avatar'],
      })
    : [];
  const profileByUserId = new Map(profiles.map((profile: any) => [profile.user?.id, profile]));

  return {
    ...team,
    memberships: memberships.map((membership: any) => ({
      ...membership,
      player: membership.player
        ? { ...membership.player, profile: profileByUserId.get(membership.player.id) ?? null }
        : null,
    })),
  };
}

export default factories.createCoreController('api::team.team', ({ strapi }) => ({
  async details(ctx) {
    const team = await strapi.documents('api::team.team').findOne({
      documentId: ctx.params.documentId,
      populate: ['captain', 'discipline', 'logo', 'memberships.player'],
    });
    if (!team || !team.isActive) throw new NotFoundError('Команда не найдена');
    return ctx.send({ data: await withPlayerProfiles(strapi, team) });
  },

  async mine(ctx) {
    const userId = ctx.state.user.id;
    const captainTeam = await strapi.db.query('api::team.team').findOne({
      where: { captain: userId, isActive: true },
      populate: ['captain', 'discipline', 'logo', 'memberships.player'],
    });

    if (captainTeam) return ctx.send({ data: await withPlayerProfiles(strapi, captainTeam), meta: { isCaptain: true } });

    const membership = await strapi.db.query('api::team-membership.team-membership').findOne({
      where: { player: userId, membershipStatus: 'active' },
      populate: ['team.captain', 'team.discipline', 'team.logo', 'team.memberships.player'],
    });

    return ctx.send({ data: await withPlayerProfiles(strapi, membership?.team), meta: { isCaptain: false } });
  },

  async createMine(ctx) {
    const userId = ctx.state.user.id;
    const { name, description, disciplines } = ctx.request.body?.data ?? {};
    if (!name?.trim() || !Array.isArray(disciplines) || disciplines.length === 0) {
      throw new BadRequestError('Укажите название и хотя бы одну дисциплину');
    }

    const existing = await strapi.db.query('api::team.team').findOne({
      where: { captain: userId, isActive: true },
    });
    if (existing) throw new BadRequestError('Вы уже являетесь капитаном активной команды');

    const team = await strapi.db.transaction(async () => {
      const created = await strapi.documents('api::team.team').create({
        data: {
          name: name.trim(),
          slug: createSlug(name),
          description: description?.trim() || null,
          discipline: disciplines,
          captain: userId,
          isActive: true,
        },
      });

      await strapi.documents('api::team-membership.team-membership').create({
        data: {
          team: created.documentId,
          player: userId,
          membershipStatus: 'active',
          position: 'main',
          joinedAt: new Date().toISOString(),
        },
      });
      return created;
    });

    ctx.status = 201;
    return ctx.send({ data: team });
  },

  async updateMine(ctx) {
    const userId = ctx.state.user.id;
    const { documentId } = ctx.params;
    await findCaptainTeam(strapi, documentId, userId);
    const { name, description, disciplines, logo } = ctx.request.body?.data ?? {};

    const data: Record<string, unknown> = {};
    if (typeof name === 'string' && name.trim()) {
      data.name = name.trim();
      data.slug = createSlug(name);
    }
    if (typeof description === 'string' || description === null) data.description = description;
    if (Array.isArray(disciplines)) {
      if (disciplines.length === 0) throw new BadRequestError('У команды должна остаться хотя бы одна дисциплина');
      data.discipline = disciplines;
    }
    if (logo !== undefined) data.logo = logo;

    const team = await strapi.documents('api::team.team').update({ documentId, data });
    return ctx.send({ data: team });
  },

  async addPlayer(ctx) {
    const captainId = ctx.state.user.id;
    const { documentId } = ctx.params;
    const team = await findCaptainTeam(strapi, documentId, captainId);
    const { username, email, password, nickname, position = 'main' } = ctx.request.body?.data ?? {};

    if (!username?.trim() || !email?.trim() || !nickname?.trim() || !password) {
      throw new BadRequestError('Укажите username, email, nickname и временный пароль');
    }
    if (password.length < 6) throw new BadRequestError('Пароль должен содержать минимум 6 символов');
    if (!['main', 'substitute', 'coach'].includes(position)) throw new BadRequestError('Некорректная позиция игрока');

    const duplicate = await strapi.db.query('plugin::users-permissions.user').findOne({
      where: { $or: [{ email: email.toLowerCase() }, { username: username.trim() }] },
    });
    if (duplicate) throw new BadRequestError('Пользователь с таким email или username уже существует');

    const role = await strapi.db.query('plugin::users-permissions.role').findOne({ where: { type: 'authenticated' } });
    if (!role) throw new BadRequestError('В Strapi не найдена роль Authenticated');

    const result = await strapi.db.transaction(async () => {
      const player = await strapi.plugin('users-permissions').service('user').add({
        username: username.trim(),
        email: email.trim().toLowerCase(),
        password,
        provider: 'local',
        confirmed: true,
        blocked: false,
        role: role.id,
      });

      await strapi.documents('api::player-profile.player-profile').create({
        data: { nickname: nickname.trim(), user: player.id },
      });
      const membership = await strapi.documents('api::team-membership.team-membership').create({
        data: {
          team: team.documentId,
          player: player.id,
          membershipStatus: 'active',
          position,
          joinedAt: new Date().toISOString(),
        },
      });
      return { player: { id: player.id, username: player.username, email: player.email }, membership };
    });

    ctx.status = 201;
    return ctx.send({ data: result });
  },

  async addExistingPlayer(ctx) {
    const captainId = ctx.state.user.id;
    const { documentId } = ctx.params;
    const team = await findCaptainTeam(strapi, documentId, captainId);
    const { identifier, position = 'main' } = ctx.request.body?.data ?? {};

    if (!identifier?.trim()) throw new BadRequestError('Укажите username или email игрока');
    if (!['main', 'substitute', 'coach'].includes(position)) throw new BadRequestError('Некорректная позиция игрока');

    const normalizedIdentifier = identifier.trim();
    const player = await strapi.db.query('plugin::users-permissions.user').findOne({
      where: {
        $or: [
          { username: normalizedIdentifier },
          { email: normalizedIdentifier.toLowerCase() },
        ],
      },
    });
    if (!player || player.blocked) throw new NotFoundError('Активный пользователь с таким username или email не найден');

    const activeMembership = await strapi.db.query('api::team-membership.team-membership').findOne({
      where: { player: player.id, membershipStatus: 'active' },
      populate: ['team'],
    });
    if (activeMembership?.team?.id === team.id) throw new BadRequestError('Игрок уже находится в составе этой команды');
    if (activeMembership) throw new BadRequestError('Игрок уже состоит в другой активной команде');

    const previousMembership = await strapi.db.query('api::team-membership.team-membership').findOne({
      where: { team: team.id, player: player.id, membershipStatus: { $in: ['left', 'removed'] } },
    });

    const membership = previousMembership
      ? await strapi.documents('api::team-membership.team-membership').update({
          documentId: previousMembership.documentId,
          data: { membershipStatus: 'active', position, joinedAt: new Date().toISOString(), leftAt: null },
        })
      : await strapi.documents('api::team-membership.team-membership').create({
          data: {
            team: team.documentId,
            player: player.id,
            membershipStatus: 'active',
            position,
            joinedAt: new Date().toISOString(),
          },
        });

    ctx.status = 201;
    return ctx.send({ data: { membership, player: { id: player.id, username: player.username } } });
  },

  async removePlayer(ctx) {
    const captainId = ctx.state.user.id;
    const { documentId, membershipId } = ctx.params;
    const team = await findCaptainTeam(strapi, documentId, captainId);
    const membership = await strapi.documents('api::team-membership.team-membership').findOne({
      documentId: membershipId,
      populate: ['team', 'player'],
    });

    if (!membership || membership.team?.id !== team.id || membership.membershipStatus !== 'active') {
      throw new NotFoundError('Активный игрок не найден в составе команды');
    }
    if (membership.player?.id === captainId) {
      throw new BadRequestError('Капитана нельзя удалить. Сначала передайте права другому игроку');
    }

    const updated = await strapi.documents('api::team-membership.team-membership').update({
      documentId: membership.documentId,
      data: { membershipStatus: 'removed', leftAt: new Date().toISOString() },
    });
    return ctx.send({ data: updated });
  },

  async transferCaptain(ctx) {
    const captainId = ctx.state.user.id;
    const { documentId } = ctx.params;
    const { playerId } = ctx.request.body?.data ?? {};
    if (!playerId) throw new BadRequestError('Не выбран новый капитан');
    if (playerId === captainId) throw new BadRequestError('Этот игрок уже является капитаном');

    const team = await findCaptainTeam(strapi, documentId, captainId);
    const membership = await strapi.db.query('api::team-membership.team-membership').findOne({
      where: { team: team.id, player: playerId, membershipStatus: 'active' },
    });
    if (!membership) throw new BadRequestError('Новый капитан должен быть активным игроком команды');

    await strapi.db.transaction(async () => {
      await strapi.documents('api::team.team').update({
        documentId,
        data: { captain: playerId },
      });
      await strapi.documents('api::captain-transfer.captain-transfer').create({
        data: {
          team: documentId,
          previousCaptain: captainId,
          nextCaptain: playerId,
          transferredAt: new Date().toISOString(),
        },
      });
    });

    return ctx.send({ data: { captain: playerId } });
  },
}));
