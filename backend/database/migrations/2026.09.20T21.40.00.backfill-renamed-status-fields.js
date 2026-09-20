'use strict';

module.exports = {
  async up(knex) {
    if (await knex.schema.hasColumn('tournaments', 'phase')) {
      await knex('tournaments').whereNull('phase').update({ phase: 'draft' });
    }

    if (await knex.schema.hasColumn('team_memberships', 'membership_status')) {
      await knex('team_memberships')
        .whereNull('membership_status')
        .update({
          membership_status: knex.raw(
            "CASE WHEN left_at IS NULL THEN 'active' ELSE 'removed' END"
          ),
        });
    }

    if (await knex.schema.hasColumn('tournament_registrations', 'registration_status')) {
      await knex('tournament_registrations')
        .whereNull('registration_status')
        .update({ registration_status: 'pending' });
    }

    if (await knex.schema.hasColumn('tournament_matches', 'match_status')) {
      await knex('tournament_matches')
        .whereNull('match_status')
        .update({ match_status: 'pending' });
    }
  },
};
