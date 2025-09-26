import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Progress } from '../ui/progress';
import AdminLayout from '../shared/AdminLayout';
import { useAppContext } from '../../context/AppContext';
import { Play, Square, Clock, CheckCircle, AlertCircle, Users } from 'lucide-react';
import { toast } from 'sonner@2.0.3';

export default function LiveControl() {
  const { state, dispatch } = useAppContext();

  const openRound = (roundId: string) => {
    const round = state.rounds.find(r => r.id === roundId);
    if (round) {
      dispatch({
        type: 'UPDATE_ROUND',
        payload: { ...round, status: 'OPEN' }
      });
      toast.success(`${round.name} opened for judging`);
    }
  };

  const closeRound = (roundId: string) => {
    const round = state.rounds.find(r => r.id === roundId);
    if (round) {
      dispatch({
        type: 'UPDATE_ROUND',
        payload: { ...round, status: 'CLOSED' }
      });
      toast.success(`${round.name} closed. Scores finalized.`);
    }
  };

  // Mock judge progress data
  const judgeProgress = state.judges.map(judge => ({
    ...judge,
    preliminary_status: 'SUBMITTED',
    preliminary_submitted_at: '2025-01-28 19:30',
    final_status: 'NOT_STARTED',
    final_submitted_at: null
  }));

  const getProgressStats = (roundType: 'PRELIMINARY' | 'FINAL') => {
    const statusField = roundType === 'PRELIMINARY' ? 'preliminary_status' : 'final_status';
    const submitted = judgeProgress.filter(j => j[statusField] === 'SUBMITTED').length;
    const inProgress = judgeProgress.filter(j => j[statusField] === 'IN_PROGRESS').length;
    const notStarted = judgeProgress.filter(j => j[statusField] === 'NOT_STARTED').length;
    
    return { submitted, inProgress, notStarted, total: judgeProgress.length };
  };

  const prelimStats = getProgressStats('PRELIMINARY');
  const finalStats = getProgressStats('FINAL');

  return (
    <AdminLayout 
      title="Live Control" 
      description="Manage round states and monitor judge progress"
    >
      <div className="space-y-6">
        {/* Round Control Cards */}
        <div className="grid md:grid-cols-2 gap-6">
          {state.rounds.map((round) => {
            const stats = round.type === 'PRELIMINARY' ? prelimStats : finalStats;
            const completionPercentage = (stats.submitted / stats.total) * 100;
            
            return (
              <Card key={round.id} className="border-2">
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                      {round.type === 'PRELIMINARY' ? (
                        <Clock className="w-5 h-5 text-blue-600" />
                      ) : (
                        <CheckCircle className="w-5 h-5 text-purple-600" />
                      )}
                      {round.name}
                    </CardTitle>
                    <Badge 
                      variant={round.status === 'OPEN' ? 'default' : round.status === 'CLOSED' ? 'secondary' : 'outline'}
                      className={
                        round.status === 'OPEN' ? 'bg-green-100 text-green-800' :
                        round.status === 'CLOSED' ? 'bg-blue-100 text-blue-800' : ''
                      }
                    >
                      {round.status}
                    </Badge>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {/* Judge Progress */}
                    <div>
                      <div className="flex justify-between text-sm mb-2">
                        <span>Judge Progress</span>
                        <span>{stats.submitted}/{stats.total} submitted</span>
                      </div>
                      <Progress value={completionPercentage} className="h-2" />
                    </div>

                    {/* Statistics */}
                    <div className="grid grid-cols-3 gap-2 text-center text-sm">
                      <div className="bg-green-50 p-2 rounded">
                        <p className="font-semibold text-green-700">{stats.submitted}</p>
                        <p className="text-green-600">Submitted</p>
                      </div>
                      <div className="bg-yellow-50 p-2 rounded">
                        <p className="font-semibold text-yellow-700">{stats.inProgress}</p>
                        <p className="text-yellow-600">In Progress</p>
                      </div>
                      <div className="bg-gray-50 p-2 rounded">
                        <p className="font-semibold text-gray-700">{stats.notStarted}</p>
                        <p className="text-gray-600">Not Started</p>
                      </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex gap-2">
                      {round.status === 'PENDING' && (
                        <Button 
                          onClick={() => openRound(round.id)}
                          className="flex-1 bg-green-600 hover:bg-green-700"
                        >
                          <Play className="w-4 h-4 mr-2" />
                          Open Round
                        </Button>
                      )}
                      
                      {round.status === 'OPEN' && (
                        <Button 
                          onClick={() => closeRound(round.id)}
                          className="flex-1 bg-red-600 hover:bg-red-700"
                          disabled={stats.submitted < stats.total}
                        >
                          <Square className="w-4 h-4 mr-2" />
                          Close Round
                        </Button>
                      )}
                      
                      {round.status === 'CLOSED' && (
                        <Button variant="outline" className="flex-1" disabled>
                          <CheckCircle className="w-4 h-4 mr-2" />
                          Round Complete
                        </Button>
                      )}
                    </div>

                    {round.status === 'OPEN' && stats.submitted < stats.total && (
                      <div className="bg-amber-50 p-3 rounded-lg">
                        <div className="flex items-start gap-2">
                          <AlertCircle className="w-4 h-4 text-amber-600 mt-0.5" />
                          <div className="text-sm">
                            <p className="font-medium text-amber-800">Waiting for judge submissions</p>
                            <p className="text-amber-700">
                              {stats.total - stats.submitted} judge(s) still need to submit scores
                            </p>
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>

        {/* Judge Progress Matrix */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Users className="w-5 h-5" />
              Judge Progress Matrix
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b">
                    <th className="text-left p-3">Judge Name</th>
                    <th className="text-center p-3">Preliminary Round</th>
                    <th className="text-center p-3">Final Round</th>
                  </tr>
                </thead>
                <tbody>
                  {judgeProgress.map((judge) => (
                    <tr key={judge.id} className="border-b">
                      <td className="p-3 font-medium">{judge.full_name}</td>
                      <td className="p-3 text-center">
                        <div className="flex flex-col items-center gap-1">
                          <Badge 
                            variant={judge.preliminary_status === 'SUBMITTED' ? 'default' : 'secondary'}
                            className={
                              judge.preliminary_status === 'SUBMITTED' 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-gray-100 text-gray-600'
                            }
                          >
                            {judge.preliminary_status}
                          </Badge>
                          {judge.preliminary_submitted_at && (
                            <span className="text-xs text-gray-500">
                              {judge.preliminary_submitted_at}
                            </span>
                          )}
                        </div>
                      </td>
                      <td className="p-3 text-center">
                        <div className="flex flex-col items-center gap-1">
                          <Badge 
                            variant={judge.final_status === 'SUBMITTED' ? 'default' : 'secondary'}
                            className={
                              judge.final_status === 'SUBMITTED' 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-gray-100 text-gray-600'
                            }
                          >
                            {judge.final_status}
                          </Badge>
                          {judge.final_submitted_at && (
                            <span className="text-xs text-gray-500">
                              {judge.final_submitted_at}
                            </span>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>

        {/* Quick Actions */}
        <Card>
          <CardHeader>
            <CardTitle>Quick Actions</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid md:grid-cols-3 gap-4">
              <Button variant="outline" className="justify-start">
                <CheckCircle className="w-4 h-4 mr-2" />
                View Current Scores
              </Button>
              <Button variant="outline" className="justify-start">
                <AlertCircle className="w-4 h-4 mr-2" />
                Send Reminder to Judges
              </Button>
              <Button variant="outline" className="justify-start">
                <Users className="w-4 h-4 mr-2" />
                Override Score (Admin)
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
}