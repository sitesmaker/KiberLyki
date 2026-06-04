import { Routes, Route } from 'react-router-dom';
import PublicLayout from './layouts/PublicLayout/PublicLayout.tsx';
import AdminLayout from './layouts/AdminLayout.tsx';
import Home from './pages/Home.tsx';
import Login from './pages/Login.tsx';

export default function App() {
  return (
    <Routes>
      <Route element={<PublicLayout />}>
        <Route path="/" element={<Home />} />
      </Route>
      
      <Route path="/admin" element={<AdminLayout />}>
      </Route>
      

      <Route path="/login" element={<Login />} />
    </Routes>
  );
}