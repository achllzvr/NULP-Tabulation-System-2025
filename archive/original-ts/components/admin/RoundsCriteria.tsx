import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Progress } from '../ui/progress';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '../ui/accordion';
import AdminLayout from '../shared/AdminLayout';
import { useAppContext } from '../../context/AppContext';
import { Target, Info, Lock } from 'lucide-react';
import { Alert, AlertDescription } from '../ui/alert';

export default function RoundsCriteria() {
  const { state } = useAppContext();

  const preliminaryCriteria = state.criteria.filter(c => c.round_type === 'PRELIMINARY');
  const finalCriteria = state.criteria.filter(c => c.round_type === 'FINAL');

  const preliminaryTotal = preliminaryCriteria.reduce((sum, c) => sum + c.weight, 0);
  const finalTotal = finalCriteria.reduce((sum, c) => sum + c.weight, 0);

  return (
    <AdminLayout 
      title="Rounds & Criteria" 
      description="Review judging criteria and scoring weights"
    >
      <div className="space-y-6">
        {/* Info Alert */}
        <Alert>
          <Info className="h-4 w-4" />
          <AlertDescription>
            Criteria weights are pre-configured for this pageant system. 
            Contact system administrator for weight modifications.
          </AlertDescription>
        </Alert>

        {/* Rounds Overview */}
        <div className="grid md:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Target className="w-5 h-5 text-blue-600" />
                Preliminary Round
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex justify-between items-center">
                  <span>Total Weight:</span>
                  <Badge variant={preliminaryTotal === 100 ? 'default' : 'destructive'}>
                    {preliminaryTotal}%
                  </Badge>
                </div>
                <Progress value={preliminaryTotal} className="h-2" />
                <div className="space-y-2">
                  {preliminaryCriteria.map((criteria) => (
                    <div key={criteria.id} className="flex justify-between text-sm">
                      <span>{criteria.name}</span>
                      <span className="font-medium">{criteria.weight}%</span>
                    </div>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Target className="w-5 h-5 text-purple-600" />
                Final Round
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex justify-between items-center">
                  <span>Total Weight:</span>
                  <Badge variant={finalTotal === 100 ? 'default' : 'destructive'}>
                    {finalTotal}%
                  </Badge>
                </div>
                <Progress value={finalTotal} className="h-2" />
                <div className="space-y-2">
                  {finalCriteria.map((criteria) => (
                    <div key={criteria.id} className="flex justify-between text-sm">
                      <span>{criteria.name}</span>
                      <span className="font-medium">{criteria.weight}%</span>
                    </div>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Detailed Criteria */}
        <Card>
          <CardHeader>
            <CardTitle>Detailed Criteria Breakdown</CardTitle>
          </CardHeader>
          <CardContent>
            <Accordion type="single" collapsible className="w-full">
              <AccordionItem value="preliminary">
                <AccordionTrigger>
                  <div className="flex items-center gap-2">
                    <Badge className="bg-blue-100 text-blue-800">Preliminary</Badge>
                    <span>Preliminary Round Criteria</span>
                  </div>
                </AccordionTrigger>
                <AccordionContent>
                  <div className="space-y-4 pt-4">
                    {preliminaryCriteria.map((criteria) => (
                      <div key={criteria.id} className="border rounded-lg p-4">
                        <div className="flex justify-between items-start mb-2">
                          <h4 className="font-semibold">{criteria.name}</h4>
                          <Badge variant="outline">{criteria.weight}% weight</Badge>
                        </div>
                        <div className="text-sm text-gray-600 space-y-2">
                          {criteria.id === 'appearance' && (
                            <div>
                              <p><strong>Evaluation Focus:</strong></p>
                              <ul className="list-disc list-inside space-y-1 ml-2">
                                <li>Overall presentation and grooming</li>
                                <li>Appropriate attire and styling</li>
                                <li>Physical fitness and posture</li>
                                <li>Personal style and elegance</li>
                              </ul>
                            </div>
                          )}
                          {criteria.id === 'poise' && (
                            <div>
                              <p><strong>Evaluation Focus:</strong></p>
                              <ul className="list-disc list-inside space-y-1 ml-2">
                                <li>Confidence and stage presence</li>
                                <li>Grace and composure under pressure</li>
                                <li>Body language and movement</li>
                                <li>Professional demeanor</li>
                              </ul>
                            </div>
                          )}
                          {criteria.id === 'communication' && (
                            <div>
                              <p><strong>Evaluation Focus:</strong></p>
                              <ul className="list-disc list-inside space-y-1 ml-2">
                                <li>Clarity and articulation</li>
                                <li>Engagement with audience</li>
                                <li>Thoughtfulness of responses</li>
                                <li>Personality and charisma</li>
                              </ul>
                            </div>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </AccordionContent>
              </AccordionItem>

              <AccordionItem value="final">
                <AccordionTrigger>
                  <div className="flex items-center gap-2">
                    <Badge className="bg-purple-100 text-purple-800">Final</Badge>
                    <span>Final Round Criteria</span>
                  </div>
                </AccordionTrigger>
                <AccordionContent>
                  <div className="space-y-4 pt-4">
                    {finalCriteria.map((criteria) => (
                      <div key={criteria.id} className="border rounded-lg p-4">
                        <div className="flex justify-between items-start mb-2">
                          <h4 className="font-semibold">{criteria.name}</h4>
                          <Badge variant="outline">{criteria.weight}% weight</Badge>
                        </div>
                        <div className="text-sm text-gray-600 space-y-2">
                          {criteria.id === 'final_question' && (
                            <div>
                              <p><strong>Evaluation Focus:</strong></p>
                              <ul className="list-disc list-inside space-y-1 ml-2">
                                <li>Quality and depth of response</li>
                                <li>Relevance to advocacy platform</li>
                                <li>Intelligence and insight demonstrated</li>
                                <li>Ability to think quickly and clearly</li>
                              </ul>
                            </div>
                          )}
                          {criteria.id === 'final_poise' && (
                            <div>
                              <p><strong>Evaluation Focus:</strong></p>
                              <ul className="list-disc list-inside space-y-1 ml-2">
                                <li>Confidence during final presentation</li>
                                <li>Grace under pressure</li>
                                <li>Consistency in performance</li>
                                <li>Overall stage presence</li>
                              </ul>
                            </div>
                          )}
                          {criteria.id === 'final_appearance' && (
                            <div>
                              <p><strong>Evaluation Focus:</strong></p>
                              <ul className="list-disc list-inside space-y-1 ml-2">
                                <li>Final presentation quality</li>
                                <li>Attention to detail</li>
                                <li>Professional appearance</li>
                                <li>Overall polish and preparation</li>
                              </ul>
                            </div>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
          </CardContent>
        </Card>

        {/* Scoring Information */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Lock className="w-5 h-5" />
              Scoring Guidelines
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid md:grid-cols-2 gap-6">
              <div>
                <h4 className="font-semibold mb-3">Scoring Scale</h4>
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between p-2 bg-red-50 rounded">
                    <span>1-3 Points</span>
                    <span className="font-medium text-red-700">Below Average</span>
                  </div>
                  <div className="flex justify-between p-2 bg-yellow-50 rounded">
                    <span>4-6 Points</span>
                    <span className="font-medium text-yellow-700">Average</span>
                  </div>
                  <div className="flex justify-between p-2 bg-blue-50 rounded">
                    <span>7-8 Points</span>
                    <span className="font-medium text-blue-700">Above Average</span>
                  </div>
                  <div className="flex justify-between p-2 bg-green-50 rounded">
                    <span>9-10 Points</span>
                    <span className="font-medium text-green-700">Excellent</span>
                  </div>
                </div>
              </div>

              <div>
                <h4 className="font-semibold mb-3">Judge Instructions</h4>
                <ul className="space-y-2 text-sm text-gray-600">
                  <li>• Score each criterion independently</li>
                  <li>• Use the full 1-10 range when appropriate</li>
                  <li>• Consider the contestant's overall performance</li>
                  <li>• Be consistent in your scoring standards</li>
                  <li>• Submit scores promptly after each round</li>
                </ul>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
}