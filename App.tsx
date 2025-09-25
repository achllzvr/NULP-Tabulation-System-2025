import { Routes, Route, Navigate, BrowserRouter } from 'react-router-dom';
import { Toaster } from './components/ui/sonner';

// Import screens
import LandingPage from './components/LandingPage';
import AdminDashboard from './components/admin/AdminDashboard';
import ParticipantsManagement from './components/admin/ParticipantsManagement';
import JudgesManagement from './components/admin/JudgesManagement';
import RoundsCriteria from './components/admin/RoundsCriteria';
import LiveControl from './components/admin/LiveControl';
import Leaderboard from './components/admin/Leaderboard';
import AdvancementReview from './components/admin/AdvancementReview';
import FinalRound from './components/admin/FinalRound';
import Awards from './components/admin/Awards';
import TieResolution from './components/admin/TieResolution';
import Settings from './components/admin/Settings';
import JudgeActiveRound from './components/judge/JudgeActiveRound';
import PublicPrelim from './components/public/PublicPrelim';
import PublicFinal from './components/public/PublicFinal';
import PublicAwards from './components/public/PublicAwards';

// Context for app state
import { AppProvider } from './context/AppContext';

export default function App() {
  return (
    <BrowserRouter>
      <AppProvider>
        <div className="min-h-screen bg-slate-50">
          <Routes>
            <Route path="/" element={<LandingPage />} />
            
            {/* Admin Routes */}
            <Route path="/admin/dashboard" element={<AdminDashboard />} />
            <Route path="/admin/participants" element={<ParticipantsManagement />} />
            <Route path="/admin/judges" element={<JudgesManagement />} />
            <Route path="/admin/rounds" element={<RoundsCriteria />} />
            <Route path="/admin/live-control" element={<LiveControl />} />
            <Route path="/admin/leaderboard" element={<Leaderboard />} />
            <Route path="/admin/advancement" element={<AdvancementReview />} />
            <Route path="/admin/final-round" element={<FinalRound />} />
            <Route path="/admin/awards" element={<Awards />} />
            <Route path="/admin/tie-resolution" element={<TieResolution />} />
            <Route path="/admin/settings" element={<Settings />} />
            
            {/* Judge Routes */}
            <Route path="/judge/active-round" element={<JudgeActiveRound />} />
            
            {/* Public Routes */}
            <Route path="/public/prelim/:code" element={<PublicPrelim />} />
            <Route path="/public/final/:code" element={<PublicFinal />} />
            <Route path="/public/awards/:code" element={<PublicAwards />} />
            
            {/* Redirect unknown routes */}
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
          
          <Toaster />
        </div>
      </AppProvider>
    </BrowserRouter>
  );
}