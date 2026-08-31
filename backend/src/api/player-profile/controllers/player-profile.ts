import { factories } from '@strapi/strapi';
import { errors } from '@strapi/utils';

const { ValidationError } = errors;

export default factories.createCoreController('api::player-profile.player-profile', ({ strapi }) => ({
  async mine(ctx) {
    const profile = await strapi.db.query('api::player-profile.player-profile').findOne({
      where: { user: ctx.state.user.id },
      populate: ['avatar'],
    });
    return ctx.send({ data: profile ?? null });
  },

  async updateMine(ctx) {
    const user = ctx.state.user;
    const { nickname, firstName, lastName, bio, country, avatar } = ctx.request.body?.data ?? {};
    if (!nickname?.trim()) throw new ValidationError('Никнейм обязателен');

    const data = {
      nickname: nickname.trim(),
      firstName: firstName?.trim() || null,
      lastName: lastName?.trim() || null,
      bio: bio?.trim() || null,
      country: country?.trim() || null,
      ...(avatar !== undefined ? { avatar } : {}),
    };
    const existing = await strapi.db.query('api::player-profile.player-profile').findOne({
      where: { user: user.id },
    });

    const profile = existing
      ? await strapi.documents('api::player-profile.player-profile').update({
          documentId: existing.documentId,
          data,
          populate: ['avatar'],
        })
      : await strapi.documents('api::player-profile.player-profile').create({
          data: { ...data, user: user.id },
          populate: ['avatar'],
        });

    return ctx.send({ data: profile });
  },
}));
