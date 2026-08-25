import type { Core } from '@strapi/strapi';

const PUBLIC_ACTIONS = [
  'api::about.about.find',
  'api::article.article.find',
  'api::article.article.findOne',
  'api::discipline.discipline.find',
  'api::discipline.discipline.findOne',
  'api::first-screen.first-screen.find',
  'api::match.match.find',
  'api::match.match.findOne',
  'api::setting.setting.find',
  'api::staff.staff.find',
  'api::staff.staff.findOne',
  'api::team.team.find',
  'api::team.team.findOne',
  'api::tournament.tournament.find',
  'api::tournament.tournament.findOne',
];

const AUTHENTICATED_ACTIONS = [
  ...PUBLIC_ACTIONS,
  'api::team.team.mine',
  'api::team.team.createMine',
  'api::team.team.updateMine',
  'api::team.team.addPlayer',
  'api::team.team.transferCaptain',
  'api::tournament-registration.tournament-registration.mine',
  'api::tournament-registration.tournament-registration.listForTournament',
  'api::tournament-registration.tournament-registration.registerTeam',
  'api::tournament-registration.tournament-registration.withdraw',
  'api::tournament-registration.tournament-registration.review',
  'api::tournament.tournament.generateBracket',
  'api::tournament.tournament.setResult',
];

async function ensurePermission(strapi: Core.Strapi, roleId: number, action: string) {
  const permissionQuery = strapi.db.query('plugin::users-permissions.permission');
  const exists = await permissionQuery.findOne({ where: { role: roleId, action } });
  if (!exists) await permissionQuery.create({ data: { role: roleId, action } });
}

export default {
  /**
   * An asynchronous register function that runs before
   * your application is initialized.
   *
   * This gives you an opportunity to extend code.
   */
  register(/* { strapi }: { strapi: Core.Strapi } */) {},

  /**
   * An asynchronous bootstrap function that runs before
   * your application gets started.
   *
   * This gives you an opportunity to set up your data model,
   * run jobs, or perform some special logic.
   */
  async bootstrap({ strapi }: { strapi: Core.Strapi }) {
    const roleQuery = strapi.db.query('plugin::users-permissions.role');
    const publicRole = await roleQuery.findOne({ where: { type: 'public' } });
    const authenticatedRole = await roleQuery.findOne({ where: { type: 'authenticated' } });

    if (publicRole) {
      for (const action of PUBLIC_ACTIONS) await ensurePermission(strapi, publicRole.id, action);
    }
    if (authenticatedRole) {
      for (const action of AUTHENTICATED_ACTIONS) await ensurePermission(strapi, authenticatedRole.id, action);
    }

    let organizerRole = await roleQuery.findOne({ where: { type: 'organizer' } });
    if (!organizerRole) {
      organizerRole = await roleQuery.create({
        data: {
          name: 'Organizer',
          description: 'Организатор турниров',
          type: 'organizer',
        },
      });
    }
    for (const action of AUTHENTICATED_ACTIONS) await ensurePermission(strapi, organizerRole.id, action);

    const unsafePublicActions = [
      'api::participant.participant.create',
      'api::participant.participant.find',
      'api::participant.participant.findOne',
      'plugin::upload.content-api.upload',
    ];
    for (const action of unsafePublicActions) {
      const permission = await strapi.db.query('plugin::users-permissions.permission').findOne({
        where: { role: publicRole?.id, action },
      });
      if (permission) {
        await strapi.db.query('plugin::users-permissions.permission').delete({ where: { id: permission.id } });
      }
    }
  },
};
