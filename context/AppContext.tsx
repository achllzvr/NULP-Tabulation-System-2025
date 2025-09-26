import React, { createContext, useContext, useReducer, ReactNode } from 'react';

// Types
interface Participant {
  id: string;
  number_label: string;
  division: 'Mr' | 'Ms';
  full_name: string;
  advocacy: string;
  advocacy_short: string;
  is_active: boolean;
}

interface Judge {
  id: string;
  full_name: string;
  email: string;
  user_id: string;
}

interface Round {
  id: string;
  name: string;
  status: 'PENDING' | 'OPEN' | 'CLOSED';
  type: 'PRELIMINARY' | 'FINAL';
}

interface Criteria {
  id: string;
  name: string;
  weight: number;
  round_type: 'PRELIMINARY' | 'FINAL';
}

interface Score {
  participant_id: string;
  criteria_id: string;
  score: number;
  judge_id: string;
}

interface AppState {
  currentUser: {
    id: string;
    role: 'admin' | 'judge' | null;
    full_name: string;
  } | null;
  pageantCode: string;
  participants: Participant[];
  judges: Judge[];
  rounds: Round[];
  criteria: Criteria[];
  scores: Score[];
  visibilitySettings: {
    show_participant_names: boolean;
    prelim_results_revealed: boolean;
    final_results_revealed: boolean;
  };
}

type AppAction = 
  | { type: 'SET_USER'; payload: AppState['currentUser'] }
  | { type: 'SET_PAGEANT_CODE'; payload: string }
  | { type: 'SET_PARTICIPANTS'; payload: Participant[] }
  | { type: 'ADD_PARTICIPANT'; payload: Participant }
  | { type: 'UPDATE_PARTICIPANT'; payload: Participant }
  | { type: 'SET_JUDGES'; payload: Judge[] }
  | { type: 'ADD_JUDGE'; payload: Judge }
  | { type: 'SET_ROUNDS'; payload: Round[] }
  | { type: 'UPDATE_ROUND'; payload: Round }
  | { type: 'SET_CRITERIA'; payload: Criteria[] }
  | { type: 'SET_SCORES'; payload: Score[] }
  | { type: 'ADD_SCORE'; payload: Score }
  | { type: 'UPDATE_VISIBILITY'; payload: Partial<AppState['visibilitySettings']> };

const initialState: AppState = {
  currentUser: null,
  pageantCode: 'DEMO2025',
  participants: [
    {
      id: '1',
      number_label: '01',
      division: 'Mr',
      full_name: 'Alexander Johnson',
      advocacy: 'Promoting youth education and mentorship programs in underserved communities.',
      advocacy_short: 'Youth Education...',
      is_active: true
    },
    {
      id: '2',
      number_label: '02',
      division: 'Ms',
      full_name: 'Isabella Rodriguez',
      advocacy: 'Environmental sustainability and climate change awareness initiatives.',
      advocacy_short: 'Environmental Sustain...',
      is_active: true
    },
    {
      id: '3',
      number_label: '03',
      division: 'Mr',
      full_name: 'Marcus Thompson',
      advocacy: 'Mental health awareness and support for young adults.',
      advocacy_short: 'Mental Health...',
      is_active: true
    },
    {
      id: '4',
      number_label: '04',
      division: 'Ms',
      full_name: 'Sophia Chen',
      advocacy: 'Technology access and digital literacy for elderly populations.',
      advocacy_short: 'Digital Literacy...',
      is_active: true
    }
  ],
  judges: [
    {
      id: '1',
      full_name: 'Dr. Sarah Mitchell',
      email: 'sarah.mitchell@email.com',
      user_id: 'judge_001'
    },
    {
      id: '2',
      full_name: 'Prof. Michael Davis',
      email: 'michael.davis@email.com',
      user_id: 'judge_002'
    },
    {
      id: '3',
      full_name: 'Ms. Jennifer Adams',
      email: 'jennifer.adams@email.com',
      user_id: 'judge_003'
    }
  ],
  rounds: [
    {
      id: 'prelim',
      name: 'Preliminary Round',
      status: 'CLOSED',
      type: 'PRELIMINARY'
    },
    {
      id: 'final',
      name: 'Final Round',
      status: 'PENDING',
      type: 'FINAL'
    }
  ],
  criteria: [
    { id: 'appearance', name: 'Appearance', weight: 30, round_type: 'PRELIMINARY' },
    { id: 'poise', name: 'Poise & Confidence', weight: 35, round_type: 'PRELIMINARY' },
    { id: 'communication', name: 'Communication Skills', weight: 35, round_type: 'PRELIMINARY' },
    { id: 'final_question', name: 'Final Question', weight: 40, round_type: 'FINAL' },
    { id: 'final_poise', name: 'Poise & Confidence', weight: 30, round_type: 'FINAL' },
    { id: 'final_appearance', name: 'Overall Appearance', weight: 30, round_type: 'FINAL' }
  ],
  scores: [],
  visibilitySettings: {
    show_participant_names: true,
    prelim_results_revealed: true,
    final_results_revealed: false
  }
};

function appReducer(state: AppState, action: AppAction): AppState {
  switch (action.type) {
    case 'SET_USER':
      return { ...state, currentUser: action.payload };
    case 'SET_PAGEANT_CODE':
      return { ...state, pageantCode: action.payload };
    case 'SET_PARTICIPANTS':
      return { ...state, participants: action.payload };
    case 'ADD_PARTICIPANT':
      return { ...state, participants: [...state.participants, action.payload] };
    case 'UPDATE_PARTICIPANT':
      return {
        ...state,
        participants: state.participants.map(p => 
          p.id === action.payload.id ? action.payload : p
        )
      };
    case 'SET_JUDGES':
      return { ...state, judges: action.payload };
    case 'ADD_JUDGE':
      return { ...state, judges: [...state.judges, action.payload] };
    case 'SET_ROUNDS':
      return { ...state, rounds: action.payload };
    case 'UPDATE_ROUND':
      return {
        ...state,
        rounds: state.rounds.map(r => 
          r.id === action.payload.id ? action.payload : r
        )
      };
    case 'SET_CRITERIA':
      return { ...state, criteria: action.payload };
    case 'SET_SCORES':
      return { ...state, scores: action.payload };
    case 'ADD_SCORE':
      return { ...state, scores: [...state.scores, action.payload] };
    case 'UPDATE_VISIBILITY':
      return {
        ...state,
        visibilitySettings: { ...state.visibilitySettings, ...action.payload }
      };
    default:
      return state;
  }
}

const AppContext = createContext<{
  state: AppState;
  dispatch: React.Dispatch<AppAction>;
} | null>(null);

export function AppProvider({ children }: { children: ReactNode }) {
  const [state, dispatch] = useReducer(appReducer, initialState);

  return (
    <AppContext.Provider value={{ state, dispatch }}>
      {children}
    </AppContext.Provider>
  );
}

export function useAppContext() {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('useAppContext must be used within AppProvider');
  }
  return context;
}