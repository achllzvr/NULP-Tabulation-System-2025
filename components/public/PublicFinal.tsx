import { useParams } from 'react-router-dom';
import { Card, CardContent } from '../ui/card';
import { Badge } from '../ui/badge';
import { Crown, Trophy, Award } from 'lucide-react';

export default function PublicFinal() {
  const { code } = useParams();

  return (
    <div className="min-h-screen bg-gradient-to-br from-purple-50 to-pink-100">
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <Crown className="w-8 h-8 text-purple-600" />
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Final Results</h1>
                <p className="text-gray-600">Pageant Code: {code}</p>
              </div>
            </div>
            <Badge className="bg-purple-100 text-purple-800">
              FINAL RESULTS
            </Badge>
          </div>
        </div>
      </header>

      <div className="max-w-4xl mx-auto px-6 py-8">
        <Card>
          <CardContent className="p-8 text-center">
            <Trophy className="w-16 h-16 text-yellow-500 mx-auto mb-4" />
            <h3 className="text-2xl font-semibold mb-4">Final Round</h3>
            <p className="text-gray-600">Final results will be displayed here when the round is complete.</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}