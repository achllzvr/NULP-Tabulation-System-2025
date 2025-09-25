import { useParams } from 'react-router-dom';
import { Card, CardContent } from '../ui/card';
import { Badge } from '../ui/badge';
import { Award, Star } from 'lucide-react';

export default function PublicAwards() {
  const { code } = useParams();

  return (
    <div className="min-h-screen bg-gradient-to-br from-yellow-50 to-orange-100">
      <header className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-3">
              <Award className="w-8 h-8 text-yellow-600" />
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Awards Ceremony</h1>
                <p className="text-gray-600">Pageant Code: {code}</p>
              </div>
            </div>
            <Badge className="bg-yellow-100 text-yellow-800">
              AWARDS
            </Badge>
          </div>
        </div>
      </header>

      <div className="max-w-4xl mx-auto px-6 py-8">
        <Card>
          <CardContent className="p-8 text-center">
            <Star className="w-16 h-16 text-yellow-500 mx-auto mb-4" />
            <h3 className="text-2xl font-semibold mb-4">Awards Ceremony</h3>
            <p className="text-gray-600">Award announcements will be displayed here when ready.</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}