import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Switch } from '../ui/switch';
import { Label } from '../ui/label';
import AdminLayout from '../shared/AdminLayout';
import { useAppContext } from '../../context/AppContext';
import { Eye, EyeOff, Settings as SettingsIcon } from 'lucide-react';
import { toast } from 'sonner@2.0.3';

export default function Settings() {
  const { state, dispatch } = useAppContext();

  const handleVisibilityChange = (setting: string, value: boolean) => {
    dispatch({
      type: 'UPDATE_VISIBILITY',
      payload: { [setting]: value }
    });
    toast.success('Visibility setting updated');
  };

  return (
    <AdminLayout title="Settings" description="Configure visibility and reveal options">
      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <SettingsIcon className="w-5 h-5" />
              Visibility Controls
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label>Show Participant Names</Label>
                <p className="text-sm text-gray-600">Display contestant names in public views</p>
              </div>
              <Switch
                checked={state.visibilitySettings.show_participant_names}
                onCheckedChange={(value) => handleVisibilityChange('show_participant_names', value)}
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label>Reveal Preliminary Results</Label>
                <p className="text-sm text-gray-600">Make preliminary leaderboard visible to public</p>
              </div>
              <Switch
                checked={state.visibilitySettings.prelim_results_revealed}
                onCheckedChange={(value) => handleVisibilityChange('prelim_results_revealed', value)}
              />
            </div>

            <div className="flex items-center justify-between">
              <div className="space-y-0.5">
                <Label>Reveal Final Results</Label>
                <p className="text-sm text-gray-600">Make final results and winners visible to public</p>
              </div>
              <Switch
                checked={state.visibilitySettings.final_results_revealed}
                onCheckedChange={(value) => handleVisibilityChange('final_results_revealed', value)}
              />
            </div>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
}