import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '../ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';
import AdminLayout from '../shared/AdminLayout';
import { useAppContext } from '../../context/AppContext';
import { Plus, UserCheck, Mail, Key } from 'lucide-react';
import { toast } from 'sonner@2.0.3';

export default function JudgesManagement() {
  const { state, dispatch } = useAppContext();
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [formData, setFormData] = useState({
    full_name: '',
    email: ''
  });

  const handleAddJudge = () => {
    if (!formData.full_name || !formData.email) {
      toast.error('Please fill in all required fields');
      return;
    }

    // Check for duplicate email
    const existingEmail = state.judges.find(j => j.email === formData.email);
    if (existingEmail) {
      toast.error('A judge with this email already exists');
      return;
    }

    const newJudge = {
      id: Date.now().toString(),
      ...formData,
      user_id: `judge_${Date.now()}`
    };

    dispatch({ type: 'ADD_JUDGE', payload: newJudge });
    setFormData({ full_name: '', email: '' });
    setIsAddModalOpen(false);
    toast.success('Judge added successfully');
  };

  return (
    <AdminLayout 
      title="Judges Management" 
      description="Add and manage pageant judges"
    >
      <div className="space-y-6">
        {/* Stats & Add Button */}
        <div className="flex items-center justify-between">
          <Card className="w-64">
            <CardContent className="p-4">
              <div className="flex items-center gap-2">
                <UserCheck className="w-5 h-5 text-green-600" />
                <div>
                  <p className="text-2xl font-bold">{state.judges.length}</p>
                  <p className="text-sm text-gray-600">Total Judges</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
            <DialogTrigger asChild>
              <Button className="bg-green-600 hover:bg-green-700">
                <Plus className="w-4 h-4 mr-2" />
                Add Judge
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
              <DialogHeader>
                <DialogTitle>Add New Judge</DialogTitle>
                <DialogDescription>
                  Enter the judge's information. They will receive login credentials.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-4">
                <div>
                  <Label htmlFor="judge-name">Full Name *</Label>
                  <Input
                    id="judge-name"
                    value={formData.full_name}
                    onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
                    placeholder="Dr. Jane Smith"
                  />
                </div>

                <div>
                  <Label htmlFor="judge-email">Email Address *</Label>
                  <Input
                    id="judge-email"
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    placeholder="judge@email.com"
                  />
                </div>

                <div className="flex gap-2">
                  <Button onClick={handleAddJudge} className="flex-1 bg-green-600 hover:bg-green-700">
                    Add Judge
                  </Button>
                  <Button 
                    variant="outline" 
                    onClick={() => setIsAddModalOpen(false)}
                    className="flex-1"
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            </DialogContent>
          </Dialog>
        </div>

        {/* Judges Table */}
        <Card>
          <CardHeader>
            <CardTitle>Judge Panel</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>User ID</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {state.judges.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8 text-gray-500">
                      No judges added yet. Click "Add Judge" to get started.
                    </TableCell>
                  </TableRow>
                ) : (
                  state.judges.map((judge) => (
                    <TableRow key={judge.id}>
                      <TableCell className="font-medium">{judge.full_name}</TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Mail className="w-4 h-4 text-gray-400" />
                          {judge.email}
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Key className="w-4 h-4 text-gray-400" />
                          <code className="bg-gray-100 px-2 py-1 rounded text-sm">
                            {judge.user_id}
                          </code>
                        </div>
                      </TableCell>
                      <TableCell>
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          Active
                        </span>
                      </TableCell>
                      <TableCell>
                        <div className="flex gap-2">
                          <Button variant="outline" size="sm">
                            Edit
                          </Button>
                          <Button variant="outline" size="sm">
                            Resend Login
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        {/* Judge Instructions */}
        <Card>
          <CardHeader>
            <CardTitle>Judge Instructions</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="bg-blue-50 p-4 rounded-lg">
                <h4 className="font-medium mb-2">For Judges:</h4>
                <ul className="space-y-1 text-sm text-gray-700">
                  <li>• Use your assigned User ID to log in to the judge portal</li>
                  <li>• Scores should be submitted for each criterion on a scale of 1-10</li>
                  <li>• All judges must submit scores before a round can be closed</li>
                  <li>• Contact the admin if you experience any technical difficulties</li>
                </ul>
              </div>

              <div className="bg-amber-50 p-4 rounded-lg">
                <h4 className="font-medium mb-2">For Administrators:</h4>
                <ul className="space-y-1 text-sm text-gray-700">
                  <li>• Ensure all judges are added before starting any rounds</li>
                  <li>• Minimum recommended: 3 judges for fair scoring</li>
                  <li>• Judges will receive their login credentials automatically</li>
                  <li>• Monitor judge progress in the Live Control panel</li>
                </ul>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Judge Statistics */}
        {state.judges.length > 0 && (
          <div className="grid md:grid-cols-3 gap-4">
            <Card>
              <CardContent className="p-4">
                <div className="text-center">
                  <p className="text-2xl font-bold text-green-600">{state.judges.length}</p>
                  <p className="text-sm text-gray-600">Total Judges</p>
                </div>
              </CardContent>
            </Card>
            
            <Card>
              <CardContent className="p-4">
                <div className="text-center">
                  <p className="text-2xl font-bold text-blue-600">{state.judges.length}</p>
                  <p className="text-sm text-gray-600">Ready to Score</p>
                </div>
              </CardContent>
            </Card>
            
            <Card>
              <CardContent className="p-4">
                <div className="text-center">
                  <p className="text-2xl font-bold text-purple-600">0</p>
                  <p className="text-sm text-gray-600">Rounds Completed</p>
                </div>
              </CardContent>
            </Card>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}