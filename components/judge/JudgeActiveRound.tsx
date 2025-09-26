import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui/select';
import { Slider } from '../ui/slider';
import { useAppContext } from '../../context/AppContext';
import { useNavigate } from 'react-router-dom';
import { LogOut, User, Save, CheckCircle, Clock } from 'lucide-react';
import { toast } from 'sonner@2.0.3';

export default function JudgeActiveRound() {
  const { state, dispatch } = useAppContext();
  const navigate = useNavigate();
  const [selectedParticipant, setSelectedParticipant] = useState<string>('');
  const [scores, setScores] = useState<Record<string, number>>({});
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);

  // Get active round (for demo, we'll use preliminary)
  const activeRound = state.rounds.find(r => r.status === 'OPEN') || state.rounds.find(r => r.type === 'PRELIMINARY');
  const activeCriteria = state.criteria.filter(c => 
    c.round_type === (activeRound?.type || 'PRELIMINARY')
  );

  // Initialize scores for the selected participant
  useEffect(() => {
    if (selectedParticipant) {
      const participantScores: Record<string, number> = {};
      activeCriteria.forEach(criteria => {
        participantScores[criteria.id] = 5; // Default score
      });
      setScores(participantScores);
      setHasUnsavedChanges(false);
    }
  }, [selectedParticipant, activeCriteria]);

  const handleScoreChange = (criteriaId: string, value: number[]) => {
    setScores(prev => ({
      ...prev,
      [criteriaId]: value[0]
    }));
    setHasUnsavedChanges(true);
  };

  const handleSaveScores = () => {
    if (!selectedParticipant) {
      toast.error('Please select a participant');
      return;
    }

    // Save scores to context (in real app, this would save to backend)
    Object.entries(scores).forEach(([criteriaId, score]) => {
      const newScore = {
        participant_id: selectedParticipant,
        criteria_id: criteriaId,
        score,
        judge_id: state.currentUser?.id || ''
      };
      dispatch({ type: 'ADD_SCORE', payload: newScore });
    });

    setHasUnsavedChanges(false);
    toast.success('Scores saved successfully');
  };

  const handleSubmitRound = () => {
    if (hasUnsavedChanges) {
      toast.error('Please save your current scores before submitting');
      return;
    }

    // Check if all participants have been scored
    const scoredParticipants = new Set(
      state.scores
        .filter(s => s.judge_id === state.currentUser?.id)
        .map(s => s.participant_id)
    );

    if (scoredParticipants.size < state.participants.filter(p => p.is_active).length) {
      toast.error('Please score all participants before submitting');
      return;
    }

    toast.success('Round submitted successfully');
    // In real app, this would mark the judge as completed
  };

  const handleLogout = () => {
    if (hasUnsavedChanges) {
      if (!confirm('You have unsaved changes. Are you sure you want to logout?')) {
        return;
      }
    }
    dispatch({ type: 'SET_USER', payload: null });
    navigate('/');
  };

  const getScoreColor = (score: number) => {
    if (score >= 8) return 'text-green-600';
    if (score >= 6) return 'text-yellow-600';
    return 'text-red-600';
  };

  const selectedParticipantData = state.participants.find(p => p.id === selectedParticipant);

  return (
    <div className="min-h-screen bg-slate-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="flex items-center space-x-2">
                <User className="w-8 h-8 text-green-600" />
                <h1 className="text-2xl font-bold text-gray-900">Judge Portal</h1>
              </div>
              <Badge variant="outline" className="bg-green-100 text-green-800">
                {activeRound?.name || 'No Active Round'}
              </Badge>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-gray-600">Welcome, {state.currentUser?.full_name}</span>
              <Button variant="outline" onClick={handleLogout}>
                <LogOut className="w-4 h-4 mr-2" />
                Logout
              </Button>
            </div>
          </div>
        </div>
      </header>

      <div className="p-6 max-w-4xl mx-auto">
        {!activeRound || activeRound.status !== 'OPEN' ? (
          <Card>
            <CardContent className="p-8 text-center">
              <Clock className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <h3 className="text-xl font-semibold mb-2">No Active Round</h3>
              <p className="text-gray-600">
                There is currently no active round for judging. Please wait for the administrator to open a round.
              </p>
            </CardContent>
          </Card>
        ) : (
          <div className="space-y-6">
            {/* Participant Selection */}
            <Card>
              <CardHeader>
                <CardTitle>Select Participant to Score</CardTitle>
              </CardHeader>
              <CardContent>
                <Select value={selectedParticipant} onValueChange={setSelectedParticipant}>
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Choose a participant to score" />
                  </SelectTrigger>
                  <SelectContent>
                    {state.participants
                      .filter(p => p.is_active)
                      .sort((a, b) => {
                        if (a.division !== b.division) return a.division.localeCompare(b.division);
                        return parseInt(a.number_label) - parseInt(b.number_label);
                      })
                      .map((participant) => (
                        <SelectItem key={participant.id} value={participant.id}>
                          <div className="flex items-center gap-3">
                            <Badge variant={participant.division === 'Mr' ? 'default' : 'secondary'}>
                              {participant.division}
                            </Badge>
                            <span>#{participant.number_label} {participant.full_name}</span>
                          </div>
                        </SelectItem>
                      ))}
                  </SelectContent>
                </Select>

                {selectedParticipantData && (
                  <div className="mt-4 p-4 bg-blue-50 rounded-lg">
                    <h4 className="font-medium">#{selectedParticipantData.number_label} {selectedParticipantData.full_name}</h4>
                    <p className="text-sm text-gray-600 mt-1">
                      <strong>Division:</strong> {selectedParticipantData.division}
                    </p>
                    {selectedParticipantData.advocacy && (
                      <p className="text-sm text-gray-600 mt-1">
                        <strong>Advocacy:</strong> {selectedParticipantData.advocacy}
                      </p>
                    )}
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Scoring Interface */}
            {selectedParticipant && (
              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle>Score Criteria</CardTitle>
                    {hasUnsavedChanges && (
                      <Badge variant="outline" className="bg-amber-100 text-amber-800">
                        Unsaved Changes
                      </Badge>
                    )}
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="space-y-6">
                    {activeCriteria.map((criteria) => (
                      <div key={criteria.id} className="space-y-3">
                        <div className="flex items-center justify-between">
                          <div>
                            <h4 className="font-medium">{criteria.name}</h4>
                            <p className="text-sm text-gray-600">Weight: {criteria.weight}%</p>
                          </div>
                          <div className="text-right">
                            <span className={`text-2xl font-bold ${getScoreColor(scores[criteria.id] || 5)}`}>
                              {scores[criteria.id] || 5}
                            </span>
                            <p className="text-xs text-gray-500">out of 10</p>
                          </div>
                        </div>
                        
                        <Slider
                          value={[scores[criteria.id] || 5]}
                          onValueChange={(value) => handleScoreChange(criteria.id, value)}
                          max={10}
                          min={1}
                          step={0.1}
                          className="w-full"
                        />
                        
                        <div className="flex justify-between text-xs text-gray-500">
                          <span>1 (Poor)</span>
                          <span>5 (Average)</span>
                          <span>10 (Excellent)</span>
                        </div>
                      </div>
                    ))}

                    <div className="flex gap-3 pt-4">
                      <Button 
                        onClick={handleSaveScores}
                        className="flex-1 bg-blue-600 hover:bg-blue-700"
                        disabled={!hasUnsavedChanges}
                      >
                        <Save className="w-4 h-4 mr-2" />
                        Save Scores
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Round Submission */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <CheckCircle className="w-5 h-5" />
                  Submit Round
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="bg-gray-50 p-4 rounded-lg">
                    <h4 className="font-medium mb-2">Scoring Progress</h4>
                    <div className="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <p className="text-gray-600">Participants Scored:</p>
                        <p className="font-semibold">
                          {new Set(state.scores.filter(s => s.judge_id === state.currentUser?.id).map(s => s.participant_id)).size} / {state.participants.filter(p => p.is_active).length}
                        </p>
                      </div>
                      <div>
                        <p className="text-gray-600">Unsaved Changes:</p>
                        <p className="font-semibold">{hasUnsavedChanges ? 'Yes' : 'No'}</p>
                      </div>
                    </div>
                  </div>

                  <Button 
                    onClick={handleSubmitRound}
                    className="w-full bg-green-600 hover:bg-green-700"
                  >
                    <CheckCircle className="w-4 h-4 mr-2" />
                    Submit All Scores for This Round
                  </Button>

                  <p className="text-xs text-gray-500 text-center">
                    Once submitted, you cannot modify your scores for this round.
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>
        )}
      </div>
    </div>
  );
}