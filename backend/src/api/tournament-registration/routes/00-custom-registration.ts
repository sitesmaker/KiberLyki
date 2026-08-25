export default {
  routes: [
    { method: 'GET', path: '/tournaments/:documentId/registrations', handler: 'tournament-registration.listForTournament' },
    { method: 'GET', path: '/tournament-registrations/mine', handler: 'tournament-registration.mine' },
    { method: 'POST', path: '/tournament-registrations/register', handler: 'tournament-registration.registerTeam' },
    { method: 'POST', path: '/tournament-registrations/:documentId/withdraw', handler: 'tournament-registration.withdraw' },
    { method: 'PUT', path: '/tournament-registrations/:documentId/review', handler: 'tournament-registration.review' }
  ]
};
