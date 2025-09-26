import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import AdminLayout from '../shared/AdminLayout';
import { useAppContext } from '../../context/AppContext';
import { useNavigate } from 'react-router-dom';
import { Crown, TrendingUp, CheckCircle, Trophy } from 'lucide-react';
import { toast } from 'sonner@2.0.3';

export default function AdvancementReview() {
  const { state } = useAppContext();
  const navigate = useNavigate();

  // Mock top 5 results based on preliminary scores
  const top5Results = [
    // Mr Division Top 2
    {
      id: '1',
      division: 'Mr',
      rank: 1,
      number_label: '01',
      full_name: 'Alexander Johnson',
      total_score: 87.5,
      qualified: true,
      advancement_reason: 'Top performer in Mr division'
    },
    {
      id: '3',
      division: 'Mr',
      rank: 2,
      number_label: '03',
      full_name: 'Marcus Thompson',
      total_score: 85.1,
      qualified: true,
      advancement_reason: 'Second place in Mr division'
    },
    // Ms Division Top 2
    {
      id: '2',
      division: 'Ms',
      rank: 1,
      number_label: '02',
      full_name: 'Isabella Rodriguez',
      total_score: 89.2,
      qualified: true,
      advancement_reason: 'Top performer in Ms division'
    },
    {
      id: '4',
      division: 'Ms',
      rank: 2,
      number_label: '04',
      full_name: 'Sophia Chen',
      total_score: 86.7,
      qualified: true,
      advancement_reason: 'Second place in Ms division'
    }
  ];

  const mrFinalists = top5Results.filter(r => r.division === 'Mr' && r.qualified);
  const msFinalists = top5Results.filter(r => r.division === 'Ms' && r.qualified);

  const handleConfirmAdvancement = () => {
    // In a real app, this would update the database
    toast.success('Top 5 advancement confirmed! Participants notified.');
    navigate('/admin/final-round');
  };

  const handleModifyAdvancement = () => {
    toast.info('Manual advancement modification not available in demo');
  };

  return (
    <AdminLayout 
      title="Advancement Review" 
      description="Review and confirm the Top 5 finalists for the final round"
    >
      <div className="space-y-6">
        {/* Status Banner */}
        <Card className="border-2 border-blue-200 bg-blue-50">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <TrendingUp className="w-5 h-5 text-blue-600" />
              <div>
                <p className="font-semibold">Preliminary Round Complete</p>
                <p className="text-sm text-gray-600">
                  Top performers from each division have been automatically selected based on preliminary scores.
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Advancement Summary */}
        <div className="grid md:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-center text-2xl">
                Total Finalists: {mrFinalists.length + msFinalists.length}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 gap-4">
                <div className="text-center p-4 bg-blue-50 rounded-lg">
                  <p className="text-2xl font-bold text-blue-600">{mrFinalists.length}</p>
                  <p className="text-sm text-gray-600">Mr Division</p>
                </div>
                <div className="text-center p-4 bg-pink-50 rounded-lg">
                  <p className="text-2xl font-bold text-pink-600">{msFinalists.length}</p>
                  <p className="text-sm text-gray-600">Ms Division</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Advancement Criteria</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3 text-sm">
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-4 h-4 text-green-600" />
                  <span>Top 2 from Mr Division</span>
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-4 h-4 text-green-600" />
                  <span>Top 2 from Ms Division</span>
                </div>
                <div className="flex items-center gap-2">
                  <CheckCircle className="w-4 h-4 text-green-600" />
                  <span>Based on preliminary total scores</span>
                </div>
                <p className="text-xs text-gray-500 mt-2">
                  Automatic selection ensures fairness and eliminates bias in finalist selection.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Finalists by Division */}
        <div className="grid lg:grid-cols-2 gap-6">
          {/* Mr Division Finalists */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-blue-600">
                <Crown className="w-5 h-5" />
                Mr Division Finalists
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {mrFinalists.map((finalist) => (
                  <div key={finalist.id} className="border rounded-lg p-4 bg-blue-50">
                    <div className="flex items-start justify-between mb-2">
                      <div>
                        <h4 className="font-semibold">#{finalist.number_label} {finalist.full_name}</h4>
                        <p className="text-sm text-gray-600">{finalist.advancement_reason}</p>
                      </div>
                      <div className="text-right">
                        <Badge className="bg-blue-600">Rank #{finalist.rank}</Badge>
                        <p className="text-sm font-bold mt-1">{finalist.total_score} pts</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Trophy className="w-4 h-4 text-gold-500" />
                      <span className="text-sm text-green-700 font-medium">Qualified for Final Round</span>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Ms Division Finalists */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-pink-600">
                <Crown className="w-5 h-5" />
                Ms Division Finalists
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {msFinalists.map((finalist) => (
                  <div key={finalist.id} className="border rounded-lg p-4 bg-pink-50">
                    <div className="flex items-start justify-between mb-2">
                      <div>
                        <h4 className="font-semibold">#{finalist.number_label} {finalist.full_name}</h4>
                        <p className="text-sm text-gray-600">{finalist.advancement_reason}</p>
                      </div>
                      <div className="text-right">
                        <Badge className="bg-pink-600">Rank #{finalist.rank}</Badge>
                        <p className="text-sm font-bold mt-1">{finalist.total_score} pts</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <Trophy className="w-4 h-4 text-gold-500" />
                      <span className="text-sm text-green-700 font-medium">Qualified for Final Round</span>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Action Buttons */}
        <Card>
          <CardHeader>
            <CardTitle>Confirm Advancement</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="bg-amber-50 p-4 rounded-lg">
                <p className="text-sm text-amber-800">
                  <strong>Important:</strong> Once confirmed, these finalists will be notified and the final round can begin. 
                  Make sure you review the selection carefully.
                </p>
              </div>

              <div className="flex gap-4">
                <Button 
                  onClick={handleConfirmAdvancement}
                  className="flex-1 bg-green-600 hover:bg-green-700"
                >
                  <CheckCircle className="w-4 h-4 mr-2" />
                  Confirm Top 5 Advancement
                </Button>
                <Button 
                  variant="outline"
                  onClick={handleModifyAdvancement}
                  className="flex-1"
                >
                  Modify Selection
                </Button>
              </div>

              <div className="text-center">
                <Button 
                  variant="ghost"
                  onClick={() => navigate('/admin/leaderboard')}
                  className="text-blue-600"
                >
                  ← Back to Preliminary Results
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Next Steps */}
        <Card>
          <CardHeader>
            <CardTitle>Next Steps</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3 text-sm">
              <div className="flex items-start gap-3">
                <div className="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold text-xs">1</div>
                <div>
                  <p className="font-medium">Confirm advancement and notify finalists</p>
                  <p className="text-gray-600">Participants will be informed of their advancement status</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold text-xs">2</div>
                <div>
                  <p className="font-medium">Prepare for final round</p>
                  <p className="text-gray-600">Set up final round criteria and open judging when ready</p>
                </div>
              </div>
              <div className="flex items-start gap-3">
                <div className="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold text-xs">3</div>
                <div>
                  <p className="font-medium">Conduct final round</p>
                  <p className="text-gray-600">Judges will score finalists on final round criteria</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
}