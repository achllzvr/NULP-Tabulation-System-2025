import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from './ui/dialog';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { Crown, Users, Eye, Shield } from 'lucide-react';
import { useAppContext } from '../context/AppContext';
import { toast } from 'sonner@2.0.3';

export default function LandingPage() {
  const navigate = useNavigate();
  const { dispatch } = useAppContext();
  const [publicCode, setPublicCode] = useState('');
  const [isPublicModalOpen, setIsPublicModalOpen] = useState(false);

  const handleAdminAccess = () => {
    dispatch({ 
      type: 'SET_USER', 
      payload: { 
        id: 'admin_001', 
        role: 'admin', 
        full_name: 'System Administrator' 
      }
    });
    navigate('/admin/dashboard');
  };

  const handleJudgeLogin = () => {
    dispatch({ 
      type: 'SET_USER', 
      payload: { 
        id: 'judge_001', 
        role: 'judge', 
        full_name: 'Dr. Sarah Mitchell' 
      }
    });
    navigate('/judge/active-round');
  };

  const handlePublicView = () => {
    if (!publicCode.trim()) {
      toast.error('Please enter a pageant code');
      return;
    }
    
    // In a real app, this would validate the code
    if (publicCode.toUpperCase() === 'DEMO2025') {
      navigate(`/public/prelim/${publicCode.toUpperCase()}`);
      setIsPublicModalOpen(false);
    } else {
      toast.error('Invalid pageant code');
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="w-full max-w-4xl">
        {/* Header */}
        <div className="text-center mb-12">
          <div className="flex items-center justify-center mb-6">
            <Crown className="w-12 h-12 text-blue-600 mr-3" />
            <h1 className="text-4xl font-bold text-gray-900">Pageant Tabulation System</h1>
          </div>
          <p className="text-gray-600 max-w-2xl mx-auto">
            Professional scoring and management system for beauty pageants, 
            talent competitions, and similar events requiring real-time judging and public displays.
          </p>
        </div>

        {/* Role Selection Cards */}
        <div className="grid md:grid-cols-3 gap-6 mb-8">
          {/* Admin Portal */}
          <Card className="hover:shadow-lg transition-shadow cursor-pointer border-2 hover:border-blue-300" 
                onClick={handleAdminAccess}>
            <CardHeader className="text-center">
              <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Shield className="w-8 h-8 text-blue-600" />
              </div>
              <CardTitle className="text-xl">Admin Portal</CardTitle>
              <CardDescription>
                Manage participants, judges, rounds, and control the entire pageant flow
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button className="w-full bg-blue-600 hover:bg-blue-700">
                Access Admin Dashboard
              </Button>
            </CardContent>
          </Card>

          {/* Judge Login */}
          <Card className="hover:shadow-lg transition-shadow cursor-pointer border-2 hover:border-amber-300" 
                onClick={handleJudgeLogin}>
            <CardHeader className="text-center">
              <div className="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Users className="w-8 h-8 text-amber-600" />
              </div>
              <CardTitle className="text-xl">Judge Portal</CardTitle>
              <CardDescription>
                Submit scores for active rounds and view your judging history
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Button className="w-full bg-amber-600 hover:bg-amber-700">
                Judge Login
              </Button>
            </CardContent>
          </Card>

          {/* Public Results */}
          <Dialog open={isPublicModalOpen} onOpenChange={setIsPublicModalOpen}>
            <DialogTrigger asChild>
              <Card className="hover:shadow-lg transition-shadow cursor-pointer border-2 hover:border-green-300">
                <CardHeader className="text-center">
                  <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Eye className="w-8 h-8 text-green-600" />
                  </div>
                  <CardTitle className="text-xl">Public Results</CardTitle>
                  <CardDescription>
                    View live leaderboards, final results, and award announcements
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <Button className="w-full bg-green-600 hover:bg-green-700">
                    View Public Display
                  </Button>
                </CardContent>
              </Card>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
              <DialogHeader>
                <DialogTitle>Enter Pageant Code</DialogTitle>
                <DialogDescription>
                  Please enter the pageant code to view public results and leaderboards.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-4">
                <div>
                  <Label htmlFor="pageant-code">Pageant Code</Label>
                  <Input
                    id="pageant-code"
                    placeholder="e.g., DEMO2025"
                    value={publicCode}
                    onChange={(e) => setPublicCode(e.target.value)}
                    className="uppercase"
                    onKeyPress={(e) => e.key === 'Enter' && handlePublicView()}
                  />
                </div>
                <div className="flex gap-2">
                  <Button 
                    onClick={handlePublicView}
                    className="flex-1 bg-green-600 hover:bg-green-700"
                  >
                    View Results
                  </Button>
                  <Button 
                    variant="outline" 
                    onClick={() => setIsPublicModalOpen(false)}
                    className="flex-1"
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            </DialogContent>
          </Dialog>
        </div>

        {/* Quick Info */}
        <div className="grid md:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">System Features</CardTitle>
            </CardHeader>
            <CardContent>
              <ul className="space-y-2 text-gray-600">
                <li>• Real-time scoring and leaderboards</li>
                <li>• Multi-round competition support</li>
                <li>• Automated tie resolution</li>
                <li>• Public display screens</li>
                <li>• Comprehensive audit trails</li>
              </ul>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Demo Access</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2 text-gray-600">
                <p><strong>Pageant Code:</strong> DEMO2025</p>
                <p><strong>Admin:</strong> Full access (no password required)</p>
                <p><strong>Judge:</strong> Dr. Sarah Mitchell (demo login)</p>
                <p className="text-sm text-gray-500 mt-3">
                  This is a demonstration system with sample data pre-loaded.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}