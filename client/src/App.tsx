import { Routes, Route } from 'react-router-dom';
import { ToastProvider } from './context/ToastContext';
import PublicLayout from './layouts/PublicLayout/PublicLayout.tsx';
import AdminLayout from './layouts/AdminLayout.tsx';
import Home from './pages/Home.tsx';
import Teams from './pages/Teams.tsx';
import Login from './pages/Login.tsx';
import Articles from './pages/Articles.tsx';
import ArticleDetails from './pages/ArticleDetails.tsx';
import Tournaments from './pages/Tournaments.tsx';
import TournamentDetails from './pages/TournamentDetails.tsx';
import Cabinet from './pages/Cabinet.tsx';
import ProtectedRoute from './routes/ProtectedRoute.tsx';
import { AuthProvider } from './context/AuthProvider.tsx';
import PlayerProfilePage from './pages/PlayerProfile.tsx';
import TeamDetails from './pages/TeamDetails.tsx';

export default function App() {
  return (
    <AuthProvider>
      <ToastProvider>
      <Routes>
        <Route element={<PublicLayout />}>
          <Route path="/" element={<Home />} />
          <Route path="/teams" element={<Teams />} />
          <Route path="/teams/:documentId" element={<TeamDetails />} />
          <Route path="/articles" element={<Articles />} />
          <Route path="/articles/:identifier" element={<ArticleDetails />} />
          <Route path="/tournaments" element={<Tournaments />} />
          <Route path="/tournaments/:documentId" element={<TournamentDetails />} />
        </Route>

        <Route element={<ProtectedRoute />}>
          <Route path="/cabinet" element={<Cabinet />} />
          <Route path="/cabinet/profile" element={<PlayerProfilePage />} />
          <Route path="/admin" element={<AdminLayout />} />
        </Route>
        
        <Route path="/login" element={<Login />} />
      </Routes>
      </ToastProvider>
    </AuthProvider>
  );
}
