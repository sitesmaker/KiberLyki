export default {
  routes: [
    { method: 'GET', path: '/player-profile/me', handler: 'player-profile.mine' },
    { method: 'PUT', path: '/player-profile/me', handler: 'player-profile.updateMine' }
  ]
};
