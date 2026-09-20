'use strict';

async function renameOrCopyColumn(knex, tableName, oldColumn, newColumn) {
  const hasOldColumn = await knex.schema.hasColumn(tableName, oldColumn);
  if (!hasOldColumn) return;

  const hasNewColumn = await knex.schema.hasColumn(tableName, newColumn);
  if (!hasNewColumn) {
    await knex.schema.alterTable(tableName, (table) => {
      table.renameColumn(oldColumn, newColumn);
    });
    return;
  }

  await knex(tableName).update(newColumn, knex.ref(oldColumn));
  await knex.schema.alterTable(tableName, (table) => {
    table.dropColumn(oldColumn);
  });
}

module.exports = {
  async up(knex) {
    await renameOrCopyColumn(knex, 'tournaments', 'status', 'phase');
    await renameOrCopyColumn(knex, 'team_memberships', 'status', 'membership_status');
    await renameOrCopyColumn(knex, 'tournament_registrations', 'status', 'registration_status');
    await renameOrCopyColumn(knex, 'tournament_matches', 'status', 'match_status');
  },
};
