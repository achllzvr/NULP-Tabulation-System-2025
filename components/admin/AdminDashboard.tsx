import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Progress } from '../ui/progress';
import AdminLayout from '../shared/AdminLayout';
import { useAppContext } from '../../context/AppContext';
import { useNavigate } from 'react-router-dom';
import { 
  Users, 
  UserCheck, 
  Trophy, 
  CheckCircle, 
  Clock, 
  AlertCircle,
  TrendingUp,
  Crown
} from 'lucide-react';

export default function AdminDashboard() {
  const { state } = useAppContext();
  const navigate = useNavigate();

  const stats = {
    participants: state.participants.length,
    activeParticipants: state.participants.filter(p => p.active).length,
    judges: state.judges.length,
    roundsCompleted: state.rounds.filter(r => r.status === 'CLOSED').length,
    totalRounds: state.rounds.length
  };

  const prelimRound = state.rounds.find(r => r.type === 'PRELIMINARY');
  const finalRound = state.rounds.find(r => r.type === 'FINAL');

  const setupProgress = [
    {
      step: 'Participants Added',
      completed: state.participants.length > 0,
      count: state.participants.length,
      action: () => navigate('/admin/participants')
    },
    {
      step: 'Judges Assigned',
      completed: state.judges.length >= 3,
      count: state.judges.length,
      action: () => navigate('/admin/judges')
    },
    {
      step: 'Preliminary Round',
      completed: prelimRound?.status === 'CLOSED',
      status: prelimRound?.status,
      action: () => navigate('/admin/live-control')
    },
    {
      step: 'Final Round',
      completed: finalRound?.status === 'CLOSED',
      status: finalRound?.status,
      action: () => navigate('/admin/final-round')
    }
  ];

  const completedSteps = setupProgress.filter(step => step.completed).length;
  const progressPercentage = (completedSteps / setupProgress.length) * 100;

  const recentActivity = [
    { action: 'Preliminary round closed', time: '2 hours ago', type: 'success' },
    { action: '3 judges submitted scores', time: '2 hours ago', type: 'info' },
    { action: 'Round opened for judging', time: '3 hours ago', type: 'info' },
    { action: '4 participants added', time: '1 day ago', type: 'success' }
  ];

  return (
    <AdminLayout 
      title="Dashboard" 
      description="Pageant setup progress and system overview"
    >
      <div className="space-y-6">
        {/* Progress Overview */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <TrendingUp className="w-5 h-5" />
              Setup Progress
            </CardTitle>
            <CardDescription>
              Complete these steps to run your pageant
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <span>Overall Progress</span>
                <span className="font-medium">{completedSteps}/{setupProgress.length} steps</span>
              </div>
              <Progress value={progressPercentage} className="h-2" />
              
              <div className="grid md:grid-cols-2 gap-4 mt-6">
                {setupProgress.map((step, index) => (
                  <div 
                    key={index}
                    className="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                    onClick={step.action}
                  >
                    <div className="flex items-center gap-3">
                      {step.completed ? (
                        <CheckCircle className="w-5 h-5 text-green-600" />
                      ) : (
                        <Clock className="w-5 h-5 text-gray-400" />
                      )}
                      <div>
                        <p className="font-medium">{step.step}</p>
                        {step.count !== undefined && (
                          <p className="text-sm text-gray-600">{step.count} added</p>
                        )}
                        {step.status && (
                          <Badge variant={step.status === 'CLOSED' ? 'default' : 'secondary'}>
                            {step.status}
                          </Badge>
                        )}
                      </div>
                    </div>
                    <Button variant="ghost" size="sm">
                      {step.completed ? 'View' : 'Setup'}
                    </Button>
                  </div>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Stats Grid */}
        <div className="grid md:grid-cols-4 gap-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Participants</CardTitle>
              <Users className="w-4 h-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.participants}</div>
              <p className="text-xs text-muted-foreground">
                {stats.activeParticipants} active
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Judges</CardTitle>
              <UserCheck className="w-4 h-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.judges}</div>
              <p className="text-xs text-muted-foreground">
                Ready to score
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Rounds</CardTitle>
              <Trophy className="w-4 h-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.roundsCompleted}/{stats.totalRounds}</div>
              <p className="text-xs text-muted-foreground">
                Completed
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Status</CardTitle>
              <Crown className="w-4 h-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">
                {finalRound?.status === 'CLOSED' ? 'Complete' : 'In Progress'}
              </div>
              <p className="text-xs text-muted-foreground">
                Pageant status
              </p>
            </CardContent>
          </Card>
        </div>

        {/* Current Status & Quick Actions */}
        <div className="grid md:grid-cols-2 gap-6">
          {/* Current Status */}
          <Card>
            <CardHeader>
              <CardTitle>Current Status</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                  <div>
                    <p className="font-medium">Preliminary Round</p>
                    <p className="text-sm text-gray-600">Judging completed</p>
                  </div>
                  <Badge variant="default">CLOSED</Badge>
                </div>

                <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <p className="font-medium">Final Round</p>
                    <p className="text-sm text-gray-600">Awaiting setup</p>
                  </div>
                  <Badge variant="secondary">PENDING</Badge>
                </div>

                {prelimRound?.status === 'CLOSED' && (
                  <Button 
                    onClick={() => navigate('/admin/advancement')}
                    className="w-full"
                  >
                    Review Top 5 Advancement
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Recent Activity */}
          <Card>
            <CardHeader>
              <CardTitle>Recent Activity</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {recentActivity.map((activity, index) => (
                  <div key={index} className="flex items-start gap-3">
                    <div className={`w-2 h-2 rounded-full mt-2 ${
                      activity.type === 'success' ? 'bg-green-500' : 'bg-blue-500'
                    }`} />
                    <div className="flex-1">
                      <p className="text-sm font-medium">{activity.action}</p>
                      <p className="text-xs text-gray-500">{activity.time}</p>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Quick Actions */}
        <Card>
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
            <CardDescription>
              Common administrative tasks
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid md:grid-cols-3 gap-4">
              <Button 
                variant="outline" 
                onClick={() => navigate('/admin/participants')}
                className="justify-start"
              >
                <Users className="w-4 h-4 mr-2" />
                Manage Participants
              </Button>
              <Button 
                variant="outline" 
                onClick={() => navigate('/admin/live-control')}
                className="justify-start"
              >
                <Trophy className="w-4 h-4 mr-2" />
                Control Rounds
              </Button>
              <Button 
                variant="outline" 
                onClick={() => navigate('/admin/leaderboard')}
                className="justify-start"
              >
                <TrendingUp className="w-4 h-4 mr-2" />
                View Leaderboard
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
}