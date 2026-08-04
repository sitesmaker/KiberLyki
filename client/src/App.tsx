import { Routes, Route } from 'react-router-dom';
import { ToastProvider } from './context/ToastContext';
import PublicLayout from './layouts/PublicLayout/PublicLayout.tsx';
import AdminLayout from './layouts/AdminLayout.tsx';
import Home from './pages/Home.tsx';
import Teams from './pages/Teams.tsx';
import Login from './pages/Login.tsx';
import Articles from './pages/Articles.tsx';

export default function App() {
  return (
    <ToastProvider>
      <Routes>
        <Route element={<PublicLayout />}>
          <Route path="/" element={<Home />} />
          <Route path="/teams" element={<Teams />} />
          <Route path="/articles" element={<Articles />} />
        </Route>
        
        <Route path="/admin" element={<AdminLayout />}>
        </Route>
        
        <Route path="/login" element={<Login />} />
      </Routes>
    </ToastProvider>
  );
}