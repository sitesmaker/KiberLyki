import type { Match } from '../../types/domain';
import { Link } from 'react-router-dom';
import './Bracket.css';

const statusLabels = { pending: 'Ожидает соперников', scheduled: 'Запланирован', live: 'Идёт сейчас', finished: 'Завершён' };

function TeamSlot({ match, side }: { match: Match; side: 'A' | 'B' }) {
  const team = side === 'A' ? match.teamA : match.teamB;
  const score = side === 'A' ? match.scoreA : match.scoreB;
  const isWinner = Boolean(team && match.winner?.id === team.id);
  const isLoser = Boolean(team && match.matchStatus === 'finished' && match.winner && !isWinner);
  return (
    <div className={isWinner ? 'bracket-match__winner' : isLoser ? 'bracket-match__loser' : ''}>
      {team ? <Link to={`/teams/${team.documentId}`}>{team.name}{isWinner && <span className="bracket-result">Победа</span>}{isLoser && <span className="bracket-result">Выбыла</span>}</Link> : <span>Ожидается</span>}
      <strong>{score ?? '—'}</strong>
    </div>
  );
}

export default function Bracket({ matches }: { matches: Match[] }) {
  const rounds = [...new Set(matches.map((match) => match.round))].sort((a, b) => a - b);
  if (rounds.length === 0) return <p>Сетка ещё не сформирована.</p>;

  return (
    <div className="bracket" aria-label="Турнирная сетка">
      {rounds.map((round) => (
        <section className="bracket__round" key={round}>
          <h3>{round === rounds.at(-1) ? 'Финал' : `Раунд ${round}`}</h3>
          <div className="bracket__matches">
            {matches.filter((match) => match.round === round).sort((a, b) => a.position - b.position).map((match) => (
              <article className="bracket-match" key={match.documentId}>
                <header><span>Матч {match.position}</span><small>{statusLabels[match.matchStatus]}</small></header>
                <TeamSlot match={match} side="A" />
                <TeamSlot match={match} side="B" />
              </article>
            ))}
          </div>
        </section>
      ))}
    </div>
  );
}
