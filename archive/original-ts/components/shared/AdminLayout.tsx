import { ReactNode } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { Button } from '../ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { 
  Home, 
  Users, 
  UserCheck, 
  Target, 
  Radio, 
  Trophy, 
  TrendingUp, 
  Crown, 
  Award,
  GitMerge,
  Settings,
  LogOut,
  ChevronRight
} from 'lucide-react';
import { useAppContext } from '../../context/AppContext';

interface AdminLayoutProps {
  children: ReactNode;
  title: string;
  description?: string;
}

const navigation = [
  { name: 'Dashboard', href: '/admin/dashboard', icon: Home },
  { name: 'Participants', href: '/admin/participants', icon: Users },
  { name: 'Judges', href: '/admin/judges', icon: UserCheck },
  { name: 'Rounds & Criteria', href: '/admin/rounds', icon: Target },
  { name: 'Live Control', href: '/admin/live-control', icon: Radio },
  { name: 'Leaderboard', href: '/admin/leaderboard', icon: Trophy },
  { name: 'Advancement', href: '/admin/advancement', icon: TrendingUp },
  { name: 'Final Round', href: '/admin/final-round', icon: Crown },
  { name: 'Awards', href: '/admin/awards', icon: Award },
  { name: 'Tie Resolution', href: '/admin/tie-resolution', icon: GitMerge },
  { name: 'Settings', href: '/admin/settings', icon: Settings },
];

export default function AdminLayout({ children, title, description }: AdminLayoutProps) {
  const navigate = useNavigate();
  const location = useLocation();
  const { state, dispatch } = useAppContext();

  const handleLogout = () => {
    dispatch({ type: 'SET_USER', payload: null });
    navigate('/');
  };

  const getProgressSteps = () => {
    const steps = [
      { name: 'Participants', completed: state.participants.length > 0, href: '/admin/participants' },
      { name: 'Judges', completed: state.judges.length > 0, href: '/admin/judges' },
      { name: 'Rounds Setup', completed: true, href: '/admin/rounds' },
      { name: 'Live Control', completed: state.rounds.some(r => r.status === 'CLOSED'), href: '/admin/live-control' },
      { name: 'Final Round', completed: false, href: '/admin/final-round' },
      { name: 'Awards', completed: false, href: '/admin/awards' }
    ];
    return steps;
  };

  const progressSteps = getProgressSteps();
  const completedSteps = progressSteps.filter(step => step.completed).length;

  return (
    <div className="min-h-screen bg-slate-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="flex items-center space-x-2">
                <Crown className="w-8 h-8 text-blue-600" />
                <h1 className="text-2xl font-bold text-gray-900">Pageant Admin</h1>
              </div>
              <Badge variant="secondary" className="bg-blue-100 text-blue-800">
                {state.pageantCode}
              </Badge>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-gray-600">Welcome, {state.currentUser?.full_name}</span>
              <Button variant="outline" onClick={handleLogout}>
                <LogOut className="w-4 h-4 mr-2" />
                Logout
              </Button>
            </div>
          </div>
        </div>
      </header>

      <div className="flex">
        {/* Sidebar */}
        <aside className="w-64 bg-white shadow-sm min-h-screen">
          <div className="p-6">
            {/* Progress Overview */}
            <Card className="mb-6">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium">Setup Progress</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span>Completion</span>
                    <span>{completedSteps}/{progressSteps.length}</span>
                  </div>
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      className="bg-blue-600 h-2 rounded-full transition-all"
                      style={{ width: `${(completedSteps / progressSteps.length) * 100}%` }}
                    />
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Navigation */}
            <nav className="space-y-1">
              {navigation.map((item) => {
                const isActive = location.pathname === item.href;
                const Icon = item.icon;
                
                return (
                  <button
                    key={item.name}
                    onClick={() => navigate(item.href)}
                    className={`w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                      isActive
                        ? 'bg-blue-100 text-blue-700 border-r-2 border-blue-600'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                    }`}
                  >
                    <Icon className="w-4 h-4 mr-3" />
                    {item.name}
                    {isActive && <ChevronRight className="w-4 h-4 ml-auto" />}
                  </button>
                );
              })}
            </nav>
          </div>
        </aside>

        {/* Main Content */}
        <main className="flex-1 p-6">
          <div className="max-w-7xl mx-auto">
            {/* Page Header */}
            <div className="mb-6">
              <h2 className="text-3xl font-bold text-gray-900">{title}</h2>
              {description && (
                <p className="mt-2 text-gray-600">{description}</p>
              )}
            </div>

            {/* Page Content */}
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}