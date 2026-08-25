import type { Match } from '../../types/domain';
import './Bracket.css';

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
                <div className={match.winner?.id === match.teamA?.id ? 'bracket-match__winner' : ''}>
                  <span>{match.teamA?.name ?? 'Ожидается'}</span><strong>{match.scoreA ?? '—'}</strong>
                </div>
                <div className={match.winner?.id === match.teamB?.id ? 'bracket-match__winner' : ''}>
                  <span>{match.teamB?.name ?? 'Ожидается'}</span><strong>{match.scoreB ?? '—'}</strong>
                </div>
              </article>
            ))}
          </div>
        </section>
      ))}
    </div>
  );
}
