export default {
  routes: [
    { method: 'POST', path: '/tournaments/:documentId/generate-bracket', handler: 'tournament.generateBracket' },
    { method: 'PUT', path: '/tournaments/:documentId/matches/:matchId/result', handler: 'tournament.setResult' }
  ]
};
