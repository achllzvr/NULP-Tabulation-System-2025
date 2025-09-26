import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Input } from '../ui/input';
import { Label } from '../ui/label';
import { Textarea } from '../ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '../ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../ui/table';
import { Switch } from '../ui/switch';
import AdminLayout from '../shared/AdminLayout';
import { useAppContext } from '../../context/AppContext';
import { Plus, Edit, Trash2, Users } from 'lucide-react';
import { toast } from 'sonner@2.0.3';

export default function ParticipantsManagement() {
  const { state, dispatch } = useAppContext();
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [formData, setFormData] = useState({
    division: '',
    number_label: '',
    full_name: '',
    advocacy: ''
  });

  const handleAddParticipant = () => {
    if (!formData.division || !formData.number_label || !formData.full_name) {
      toast.error('Please fill in all required fields');
      return;
    }

    // Check for duplicate number
    const existingNumber = state.participants.find(
      p => p.number_label === formData.number_label && p.division === formData.division
    );
    
    if (existingNumber) {
      toast.error('This number already exists for this division');
      return;
    }

    const newParticipant = {
      id: Date.now().toString(),
      ...formData,
      advocacy_short: formData.advocacy.substring(0, 50) + (formData.advocacy.length > 50 ? '...' : ''),
      is_active: true
    };

    dispatch({ type: 'ADD_PARTICIPANT', payload: newParticipant });
    setFormData({ division: '', number_label: '', full_name: '', advocacy: '' });
    setIsAddModalOpen(false);
    toast.success('Participant added successfully');
  };

  const toggleParticipantStatus = (participantId: string) => {
    const participant = state.participants.find(p => p.id === participantId);
    if (participant) {
      dispatch({
        type: 'UPDATE_PARTICIPANT',
        payload: { ...participant, is_active: !participant.is_active }
      });
      toast.success(`Participant ${participant.is_active ? 'deactivated' : 'activated'}`);
    }
  };

  const participantsByDivision = {
    Mr: state.participants.filter(p => p.division === 'Mr'),
    Ms: state.participants.filter(p => p.division === 'Ms')
  };

  const generateNextNumber = (division: 'Mr' | 'Ms') => {
    const existing = participantsByDivision[division].map(p => parseInt(p.number_label));
    const highest = existing.length > 0 ? Math.max(...existing) : 0;
    return (highest + 1).toString().padStart(2, '0');
  };

  return (
    <AdminLayout 
      title="Participants Management" 
      description="Add and manage pageant contestants"
    >
      <div className="space-y-6">
        {/* Stats & Add Button */}
        <div className="flex items-center justify-between">
          <div className="grid grid-cols-3 gap-4">
            <Card>
              <CardContent className="p-4">
                <div className="flex items-center gap-2">
                  <Users className="w-5 h-5 text-blue-600" />
                  <div>
                    <p className="text-2xl font-bold">{state.participants.length}</p>
                    <p className="text-sm text-gray-600">Total Participants</p>
                  </div>
                </div>
              </CardContent>
            </Card>
            
            <Card>
              <CardContent className="p-4">
                <div>
                  <p className="text-2xl font-bold text-blue-600">{participantsByDivision.Mr.length}</p>
                  <p className="text-sm text-gray-600">Mr Division</p>
                </div>
              </CardContent>
            </Card>
            
            <Card>
              <CardContent className="p-4">
                <div>
                  <p className="text-2xl font-bold text-pink-600">{participantsByDivision.Ms.length}</p>
                  <p className="text-sm text-gray-600">Ms Division</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
            <DialogTrigger asChild>
              <Button className="bg-blue-600 hover:bg-blue-700">
                <Plus className="w-4 h-4 mr-2" />
                Add Participant
              </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
              <DialogHeader>
                <DialogTitle>Add New Participant</DialogTitle>
                <DialogDescription>
                  Enter the contestant's information below.
                </DialogDescription>
              </DialogHeader>
              <div className="space-y-4">
                <div>
                  <Label htmlFor="division">Division *</Label>
                  <Select 
                    value={formData.division} 
                    onValueChange={(value) => {
                      setFormData({ 
                        ...formData, 
                        division: value,
                        number_label: generateNextNumber(value as 'Mr' | 'Ms')
                      });
                    }}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select division" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Mr">Mr</SelectItem>
                      <SelectItem value="Ms">Ms</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label htmlFor="number">Number *</Label>
                  <Input
                    id="number"
                    value={formData.number_label}
                    onChange={(e) => setFormData({ ...formData, number_label: e.target.value })}
                    placeholder="01"
                  />
                </div>

                <div>
                  <Label htmlFor="name">Full Name *</Label>
                  <Input
                    id="name"
                    value={formData.full_name}
                    onChange={(e) => setFormData({ ...formData, full_name: e.target.value })}
                    placeholder="Enter full name"
                  />
                </div>

                <div>
                  <Label htmlFor="advocacy">Advocacy Platform</Label>
                  <Textarea
                    id="advocacy"
                    value={formData.advocacy}
                    onChange={(e) => setFormData({ ...formData, advocacy: e.target.value })}
                    placeholder="Describe their advocacy or platform (optional)"
                    rows={3}
                  />
                </div>

                <div className="flex gap-2">
                  <Button onClick={handleAddParticipant} className="flex-1 bg-blue-600 hover:bg-blue-700">
                    Add Participant
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

        {/* Participants Table */}
        <Card>
          <CardHeader>
            <CardTitle>All Participants</CardTitle>
          </CardHeader>
          <CardContent>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Number</TableHead>
                  <TableHead>Division</TableHead>
                  <TableHead>Name</TableHead>
                  <TableHead>Advocacy</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {state.participants.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-8 text-gray-500">
                      No participants added yet. Click "Add Participant" to get started.
                    </TableCell>
                  </TableRow>
                ) : (
                  state.participants
                    .sort((a, b) => {
                      if (a.division !== b.division) {
                        return a.division.localeCompare(b.division);
                      }
                      return parseInt(a.number_label) - parseInt(b.number_label);
                    })
                    .map((participant) => (
                      <TableRow key={participant.id}>
                        <TableCell className="font-medium">
                          {participant.number_label}
                        </TableCell>
                        <TableCell>
                          <Badge variant={participant.division === 'Mr' ? 'default' : 'secondary'}>
                            {participant.division}
                          </Badge>
                        </TableCell>
                        <TableCell>{participant.full_name}</TableCell>
                        <TableCell className="max-w-xs">
                          <span className="text-sm text-gray-600" title={participant.advocacy}>
                            {participant.advocacy_short || 'No advocacy set'}
                          </span>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Switch
                              checked={participant.is_active}
                              onCheckedChange={() => toggleParticipantStatus(participant.id)}
                            />
                            <span className="text-sm">
                              {participant.is_active ? 'Active' : 'Inactive'}
                            </span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="flex gap-2">
                            <Button variant="ghost" size="sm">
                              <Edit className="w-4 h-4" />
                            </Button>
                            <Button variant="ghost" size="sm" className="text-red-600 hover:text-red-700">
                              <Trash2 className="w-4 h-4" />
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

        {/* Division Breakdown */}
        <div className="grid md:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="text-blue-600">Mr Division</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {participantsByDivision.Mr.length === 0 ? (
                  <p className="text-gray-500 text-center py-4">No Mr contestants yet</p>
                ) : (
                  participantsByDivision.Mr.map((participant) => (
                    <div key={participant.id} className="flex items-center justify-between p-2 border rounded">
                      <span className="font-medium">#{participant.number_label} {participant.full_name}</span>
                      <Badge variant={participant.is_active ? 'default' : 'secondary'}>
                        {participant.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </div>
                  ))
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-pink-600">Ms Division</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {participantsByDivision.Ms.length === 0 ? (
                  <p className="text-gray-500 text-center py-4">No Ms contestants yet</p>
                ) : (
                  participantsByDivision.Ms.map((participant) => (
                    <div key={participant.id} className="flex items-center justify-between p-2 border rounded">
                      <span className="font-medium">#{participant.number_label} {participant.full_name}</span>
                      <Badge variant={participant.is_active ? 'default' : 'secondary'}>
                        {participant.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </div>
                  ))
                )}
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </AdminLayout>
  );
}