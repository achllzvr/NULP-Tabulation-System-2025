import { useParams } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';
import { Trophy, Crown, Eye } from 'lucide-react';

export default function PublicPrelim() {
  const { code } = useParams();

  // Mock preliminary results for public display
  const preliminaryResults = [
    // Mr Division
    { 
      rank: 1, 
      division: 'Mr', 
      number_label: '01', 
      display_name: 'Alexander Johnson', 
      score: 87.5
    },
    { 
      rank: 2, 
      division: 'Mr', 
      number_label: '03', 
      display_name: 'Marcus Thompson', 
      score: 85.1
    },
    // Ms Division
    { 
      rank: 1, 
      division: 'Ms', 
      number_label: '02', 
      display_name: 'Isabella Rodriguez', 
      score: 89.2
    },
    { 
      rank: 2, 
      division: 'Ms', 
      number_label: '04', 
      display_name: 'Sophia Chen', 
      score: 86.7
    }
  ];

  const mrResults = preliminaryResults.filter(r => r.division === 'Mr');
  const msResults = preliminaryResults.filter(r => r.division === 'Ms');

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
      {/* Header */}
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <Eye className="w-8 h-8 text-blue-600" />
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Preliminary Standings</h1>
                <p className="text-gray-600">Pageant Code: {code}</p>
              </div>
            </div>
            <Badge className="bg-green-100 text-green-800">
              LIVE RESULTS
            </Badge>
          </div>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-6 py-8">
        <div className="grid lg:grid-cols-2 gap-8">
          {/* Mr Division */}
          <Card className="border-2 border-blue-200">
            <CardHeader className="bg-blue-50">
              <CardTitle className="flex items-center gap-2 text-blue-700 text-xl">
                <Crown className="w-6 h-6" />
                Mr Division
              </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-20">Rank</TableHead>
                    <TableHead>Contestant</TableHead>
                    <TableHead className="text-right">Score</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {mrResults.map((result, index) => (
                    <TableRow 
                      key={index}
                      className={`${result.rank === 1 ? 'bg-gold-50' : result.rank === 2 ? 'bg-silver-50' : ''}`}
                    >
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Badge 
                            variant={result.rank === 1 ? 'default' : 'secondary'}
                            className={result.rank === 1 ? 'bg-yellow-500' : ''}
                          >
                            #{result.rank}
                          </Badge>
                          {result.rank <= 2 && <Trophy className="w-4 h-4 text-yellow-600" />}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div>
                          <p className="font-semibold text-lg">#{result.number_label}</p>
                          <p className="text-gray-600">{result.display_name}</p>
                        </div>
                      </TableCell>
                      <TableCell className="text-right">
                        <span className="text-xl font-bold">{result.score}</span>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          {/* Ms Division */}
          <Card className="border-2 border-pink-200">
            <CardHeader className="bg-pink-50">
              <CardTitle className="flex items-center gap-2 text-pink-700 text-xl">
                <Crown className="w-6 h-6" />
                Ms Division
              </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-20">Rank</TableHead>
                    <TableHead>Contestant</TableHead>
                    <TableHead className="text-right">Score</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {msResults.map((result, index) => (
                    <TableRow 
                      key={index}
                      className={`${result.rank === 1 ? 'bg-gold-50' : result.rank === 2 ? 'bg-silver-50' : ''}`}
                    >
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Badge 
                            variant={result.rank === 1 ? 'default' : 'secondary'}
                            className={result.rank === 1 ? 'bg-yellow-500' : ''}
                          >
                            #{result.rank}
                          </Badge>
                          {result.rank <= 2 && <Trophy className="w-4 h-4 text-yellow-600" />}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div>
                          <p className="font-semibold text-lg">#{result.number_label}</p>
                          <p className="text-gray-600">{result.display_name}</p>
                        </div>
                      </TableCell>
                      <TableCell className="text-right">
                        <span className="text-xl font-bold">{result.score}</span>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </div>

        {/* Status */}
        <Card className="mt-8">
          <CardContent className="p-6 text-center">
            <h3 className="text-xl font-semibold mb-2">Preliminary Round Complete</h3>
            <p className="text-gray-600">
              Top 2 contestants from each division have qualified for the final round.
            </p>
            <p className="text-sm text-gray-500 mt-2">
              Final round results will be announced upon completion.
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}