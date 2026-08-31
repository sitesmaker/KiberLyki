export default {
  routes: [
    { method: 'GET', path: '/teams/mine', handler: 'team.mine' },
    { method: 'POST', path: '/teams/mine', handler: 'team.createMine' },
    { method: 'PUT', path: '/teams/:documentId/mine', handler: 'team.updateMine' },
    { method: 'POST', path: '/teams/:documentId/players', handler: 'team.addPlayer' },
    { method: 'POST', path: '/teams/:documentId/players/existing', handler: 'team.addExistingPlayer' },
    { method: 'DELETE', path: '/teams/:documentId/players/:membershipId', handler: 'team.removePlayer' },
    { method: 'POST', path: '/teams/:documentId/transfer-captain', handler: 'team.transferCaptain' }
  ]
};
