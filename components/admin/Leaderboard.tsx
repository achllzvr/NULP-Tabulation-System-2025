import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';
import AdminLayout from '../shared/AdminLayout';
import { useAppContext } from '../../context/AppContext';
import { Trophy, Crown, TrendingUp, Award, CheckCircle, Clock } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner@2.0.3';

export default function Leaderboard() {
  const { state } = useAppContext();
  const navigate = useNavigate();

  // Mock preliminary results with realistic scores
  const preliminaryResults = [
    // Mr Division
    { 
      id: '1', 
      rank: 1, 
      division: 'Mr', 
      number_label: '01', 
      full_name: 'Alexander Johnson', 
      total_score: 87.5,
      appearance: 28.5, 
      poise: 30.2, 
      communication: 28.8,
      qualified: true
    },
    { 
      id: '3', 
      rank: 2, 
      division: 'Mr', 
      number_label: '03', 
      full_name: 'Marcus Thompson', 
      total_score: 85.1,
      appearance: 27.8, 
      poise: 28.9, 
      communication: 28.4,
      qualified: true
    },
    // Ms Division
    { 
      id: '2', 
      rank: 1, 
      division: 'Ms', 
      number_label: '02', 
      full_name: 'Isabella Rodriguez', 
      total_score: 89.2,
      appearance: 29.1, 
      poise: 31.0, 
      communication: 29.1,
      qualified: true
    },
    { 
      id: '4', 
      rank: 2, 
      division: 'Ms', 
      number_label: '04', 
      full_name: 'Sophia Chen', 
      total_score: 86.7,
      appearance: 28.2, 
      poise: 29.5, 
      communication: 29.0,
      qualified: true
    }
  ];

  const handleGenerateTop5 = () => {
    toast.success('Top 5 advancement list generated');
    navigate('/admin/advancement');
  };

  const mrResults = preliminaryResults.filter(r => r.division === 'Mr').sort((a, b) => a.rank - b.rank);
  const msResults = preliminaryResults.filter(r => r.division === 'Ms').sort((a, b) => a.rank - b.rank);

  const prelimRound = state.rounds.find(r => r.type === 'PRELIMINARY');
  const isRoundClosed = prelimRound?.status === 'CLOSED';

  return (
    <AdminLayout 
      title="Leaderboard" 
      description="View preliminary round results and rankings"
    >
      <div className="space-y-6">
        {/* Status Banner */}
        <Card className={`border-2 ${isRoundClosed ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50'}`}>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                {isRoundClosed ? (
                  <CheckCircle className="w-5 h-5 text-green-600" />
                ) : (
                  <Clock className="w-5 h-5 text-amber-600" />
                )}
                <div>
                  <p className="font-semibold">
                    {isRoundClosed ? 'Preliminary Round Complete' : 'Preliminary Round In Progress'}
                  </p>
                  <p className="text-sm text-gray-600">
                    {isRoundClosed 
                      ? 'All judges have submitted their scores. Results are final.' 
                      : 'Waiting for all judges to submit their scores.'
                    }
                  </p>
                </div>
              </div>
              {isRoundClosed && (
                <Button onClick={handleGenerateTop5} className="bg-blue-600 hover:bg-blue-700">
                  <TrendingUp className="w-4 h-4 mr-2" />
                  Generate Top 5
                </Button>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Division Results */}
        <div className="grid lg:grid-cols-2 gap-6">
          {/* Mr Division */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-blue-600">
                <Crown className="w-5 h-5" />
                Mr Division
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-16">Rank</TableHead>
                    <TableHead>Contestant</TableHead>
                    <TableHead className="text-right">Score</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {mrResults.map((result) => (
                    <TableRow key={result.id} className={result.qualified ? 'bg-green-50' : ''}>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Badge variant={result.rank <= 2 ? 'default' : 'secondary'}>
                            #{result.rank}
                          </Badge>
                          {result.qualified && <Trophy className="w-4 h-4 text-gold-500" />}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div>
                          <p className="font-medium">#{result.number_label} {result.full_name}</p>
                          {isRoundClosed && (
                            <p className="text-xs text-gray-500">
                              A: {result.appearance} | P: {result.poise} | C: {result.communication}
                            </p>
                          )}
                        </div>
                      </TableCell>
                      <TableCell className="text-right font-bold">
                        {isRoundClosed ? result.total_score.toFixed(1) : '---'}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          {/* Ms Division */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-pink-600">
                <Crown className="w-5 h-5" />
                Ms Division
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-16">Rank</TableHead>
                    <TableHead>Contestant</TableHead>
                    <TableHead className="text-right">Score</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {msResults.map((result) => (
                    <TableRow key={result.id} className={result.qualified ? 'bg-green-50' : ''}>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Badge variant={result.rank <= 2 ? 'default' : 'secondary'}>
                            #{result.rank}
                          </Badge>
                          {result.qualified && <Trophy className="w-4 h-4 text-gold-500" />}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div>
                          <p className="font-medium">#{result.number_label} {result.full_name}</p>
                          {isRoundClosed && (
                            <p className="text-xs text-gray-500">
                              A: {result.appearance} | P: {result.poise} | C: {result.communication}
                            </p>
                          )}
                        </div>
                      </TableCell>
                      <TableCell className="text-right font-bold">
                        {isRoundClosed ? result.total_score.toFixed(1) : '---'}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </div>

        {/* Scoring Breakdown */}
        {isRoundClosed && (
          <Card>
            <CardHeader>
              <CardTitle>Detailed Scoring Breakdown</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Division</TableHead>
                    <TableHead>Contestant</TableHead>
                    <TableHead className="text-center">Appearance (30%)</TableHead>
                    <TableHead className="text-center">Poise (35%)</TableHead>
                    <TableHead className="text-center">Communication (35%)</TableHead>
                    <TableHead className="text-center">Total Score</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {preliminaryResults
                    .sort((a, b) => {
                      if (a.division !== b.division) return a.division.localeCompare(b.division);
                      return a.rank - b.rank;
                    })
                    .map((result) => (
                      <TableRow key={result.id}>
                        <TableCell>
                          <Badge variant={result.division === 'Mr' ? 'default' : 'secondary'}>
                            {result.division}
                          </Badge>
                        </TableCell>
                        <TableCell className="font-medium">
                          #{result.number_label} {result.full_name}
                        </TableCell>
                        <TableCell className="text-center">{result.appearance.toFixed(1)}</TableCell>
                        <TableCell className="text-center">{result.poise.toFixed(1)}</TableCell>
                        <TableCell className="text-center">{result.communication.toFixed(1)}</TableCell>
                        <TableCell className="text-center font-bold">
                          {result.total_score.toFixed(1)}
                        </TableCell>
                      </TableRow>
                    ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        )}

        {/* Next Steps */}
        {isRoundClosed && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Award className="w-5 h-5" />
                Next Steps
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="grid md:grid-cols-2 gap-4">
                  <Button 
                    onClick={handleGenerateTop5}
                    className="bg-blue-600 hover:bg-blue-700 justify-start"
                  >
                    <TrendingUp className="w-4 h-4 mr-2" />
                    Generate Top 5 Advancement
                  </Button>
                  <Button 
                    variant="outline"
                    onClick={() => navigate('/admin/settings')}
                    className="justify-start"
                  >
                    <Trophy className="w-4 h-4 mr-2" />
                    Reveal Results to Public
                  </Button>
                </div>
                
                <div className="bg-blue-50 p-4 rounded-lg">
                  <p className="text-sm text-blue-800">
                    <strong>Next:</strong> Review the preliminary results and generate the Top 5 advancement list. 
                    The top 2 contestants from each division will automatically qualify for the final round.
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        )}
      </div>
    </AdminLayout>
  );
}