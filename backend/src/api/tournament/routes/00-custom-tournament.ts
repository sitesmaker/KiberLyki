export default {
  routes: [
    { method: 'GET', path: '/tournaments/:documentId/details', handler: 'tournament.details', config: { auth: false } },
    { method: 'POST', path: '/tournaments/:documentId/generate-bracket', handler: 'tournament.generateBracket' },
    { method: 'PUT', path: '/tournaments/:documentId/matches/:matchId/result', handler: 'tournament.setResult' }
  ]
};
