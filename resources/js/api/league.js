import axios from 'axios';

const http = axios.create({ baseURL: '/api/league' });

export const getState    = ()           => http.get('/state').then(r => r.data);
export const playWeek    = ()           => http.post('/play-week').then(r => r.data);
export const playAll     = ()           => http.post('/play-all').then(r => r.data);
export const resetLeague = ()           => http.post('/reset').then(r => r.data);
export const editMatch   = (id, h, a)   => http.patch(`/match/${id}`, { home_goals: h, away_goals: a }).then(r => r.data);
