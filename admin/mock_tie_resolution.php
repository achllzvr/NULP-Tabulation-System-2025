<?php
// admin/mock_tie_resolution.php
// This is a mockup page to visualize the new tie resolution admin panel UI and judge panel UI for review and feedback.

include __DIR__ . '/../partials/head.php';
?>
<main class="min-h-screen custom-blue-gradient py-12 px-4">
  <div class="mx-auto max-w-5xl w-full space-y-12">
    <!-- Admin Tie Breaker Panel -->
    <section class="bg-white bg-opacity-20 backdrop-blur-xl rounded-3xl shadow-2xl border border-white border-opacity-25 p-10 mb-10">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Tie Resolution</h1>
          <p class="text-slate-200">Manage and resolve scoring ties between participants</p>
        </div>
        <button class="bg-blue-500 bg-opacity-30 hover:bg-blue-600 hover:bg-opacity-40 text-white font-medium px-6 py-3 rounded-lg transition-colors flex items-center gap-2 border border-white border-opacity-20 backdrop-blur-md">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Scan for Ties
        </button>
      </div>
      <div class="grid md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <h3 class="text-sm font-medium text-slate-200">Total Participants</h3>
          <p class="text-3xl font-bold text-white mb-1">12</p>
          <p class="text-sm text-slate-200">Active contestants</p>
        </div>
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <h3 class="text-sm font-medium text-slate-200">Scored Rounds</h3>
          <p class="text-3xl font-bold text-white mb-1">5</p>
          <p class="text-sm text-slate-200">Completed rounds</p>
        </div>
        <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6">
          <h3 class="text-sm font-medium text-slate-200">Detected Ties</h3>
          <p class="text-3xl font-bold text-white mb-1">2</p>
          <p class="text-sm text-slate-200">Pending resolution</p>
        </div>
      </div>
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20">
        <div class="px-6 py-4 border-b border-white border-opacity-10">
          <h3 class="text-lg font-semibold text-white">Current Ties</h3>
          <p class="text-sm text-slate-200 mt-1">Detected ties requiring resolution</p>
        </div>
        <div class="p-6">
          <div class="space-y-6">
            <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-amber-200 border-opacity-30 p-6">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-amber-200">Tie Group #1 - Score: 9.50</h4>
                <span class="px-3 py-1 text-sm bg-amber-200 bg-opacity-20 text-amber-100 rounded-full">2 participants tied</span>
              </div>
              <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white bg-opacity-10 border border-amber-200 border-opacity-20 rounded-lg p-4">
                  <div class="flex items-center justify-between mb-2">
                    <h5 class="font-medium text-white">#1 John Doe</h5>
                    <span class="text-sm text-slate-200">Score: 9.50</span>
                  </div>
                  <p class="text-sm text-slate-200 mb-3">Mr Division</p>
                </div>
                <div class="bg-white bg-opacity-10 border border-amber-200 border-opacity-20 rounded-lg p-4">
                  <div class="flex items-center justify-between mb-2">
                    <h5 class="font-medium text-white">#3 Mark Lee</h5>
                    <span class="text-sm text-slate-200">Score: 9.50</span>
                  </div>
                  <p class="text-sm text-slate-200 mb-3">Mr Division</p>
                </div>
              </div>
              <!-- Judge Progress Bar -->
              <div class="mb-6">
                <div class="text-sm text-slate-200 mb-2">Judge Progress</div>
                <div class="w-full h-8 flex rounded-lg overflow-hidden border border-white border-opacity-20 bg-white bg-opacity-10 backdrop-blur-md">
                  <div class="flex-1 flex items-center justify-center bg-green-500 bg-opacity-60 text-white font-bold transition-all">Judge A</div>
                  <div class="flex-1 flex items-center justify-center bg-yellow-400 bg-opacity-60 text-white font-bold transition-all">Judge B</div>
                  <div class="flex-1 flex items-center justify-center bg-gray-300 bg-opacity-60 text-slate-700 font-bold transition-all">Judge C</div>
                </div>
                <div class="text-xs text-slate-200 mt-2">Green: Saved, Yellow: Pending, Gray: Not started</div>
              </div>
              <!-- Round Control Buttons -->
              <div class="flex gap-3 mb-6">
                <button class="px-5 py-2 rounded-lg bg-blue-500 bg-opacity-30 hover:bg-blue-600 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition">Start Tie Breaker</button>
                <button class="px-5 py-2 rounded-lg bg-yellow-400 bg-opacity-30 hover:bg-yellow-500 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition">Close Tie Breaker</button>
                <button class="px-5 py-2 rounded-lg bg-green-600 bg-opacity-30 hover:bg-green-700 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition">Finalize</button>
                <button class="px-5 py-2 rounded-lg bg-red-600 bg-opacity-30 hover:bg-red-700 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition">Revert</button>
              </div>
              <div class="flex items-center gap-3 text-sm text-amber-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                The winner will be automatically determined once the tie breaker round ends.
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-8 bg-white bg-opacity-10 border border-blue-400 border-opacity-20 rounded-xl p-6 backdrop-blur-md">
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-blue-300 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div>
            <h4 class="font-semibold text-blue-200 mb-2">About Tie Resolution</h4>
            <div class="text-sm text-blue-100 space-y-2">
              <p>• Ties are automatically detected when participants have identical total scores</p>
              <p>• The system supports multiple tie-breaking methods in order of priority</p>
              <p>• Manual resolution allows judges to make final decisions on close calls</p>
              <p>• All tie resolutions are logged for transparency and audit purposes</p>
              <p>• Tie resolution will be available once scoring rounds are completed</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Judge Tie Breaker Panel -->
    <section class="bg-white bg-opacity-20 backdrop-blur-xl rounded-3xl shadow-2xl border border-white border-opacity-25 p-10 mb-10">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-white">Judge Tie Breaker Panel</h1>
      </div>
      <!-- Round Info Card -->
      <div class="bg-white bg-opacity-15 backdrop-blur-md rounded-xl shadow-sm border border-white border-opacity-20 p-6 mb-8">
        <h2 class="text-lg font-semibold text-white mb-1">Final Q&amp;A Round - Mr Division</h2>
        <p class="text-slate-200 text-sm">Currently judging: 2 tied participants</p>
      </div>
      <!-- Participant Navigation -->
      <div class="bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl p-4 mb-8 backdrop-blur-md">
        <h3 class="text-lg font-semibold text-white mb-3">Select Participant to Score</h3>
        <div class="grid grid-cols-2 gap-3">
          <div class="block text-center p-3 rounded-lg border border-blue-400 border-opacity-30 bg-blue-500 bg-opacity-20 text-white font-semibold shadow-sm">
            <div class="font-semibold">#1 John Doe</div>
            <div class="text-xs mt-1 text-slate-200">Mr Division</div>
          </div>
          <div class="block text-center p-3 rounded-lg border border-white border-opacity-10 bg-white bg-opacity-10 text-slate-200 font-semibold shadow-sm hover:bg-white hover:bg-opacity-20">
            <div class="font-semibold">#3 Mark Lee</div>
            <div class="text-xs mt-1 text-slate-200">Mr Division</div>
          </div>
        </div>
      </div>
      <!-- Scoring Form Card -->
      <div class="bg-white bg-opacity-10 border border-white border-opacity-20 rounded-xl p-6 backdrop-blur-md mb-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-lg font-semibold text-white mb-1">Scoring: Participant #1 John Doe</h3>
            <p class="text-slate-200">John Doe (Mr Division)</p>
          </div>
          <div class="text-sm text-slate-300">Participant 1 of 2</div>
        </div>
        <table class="w-full mb-4">
          <thead>
            <tr class="text-slate-200">
              <th class="px-4 py-2">Criterion</th>
              <th class="px-4 py-2">Score</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="px-4 py-2">Q&amp;A</td>
              <td class="px-4 py-2"><input type="number" class="w-24 px-2 py-1 rounded bg-white bg-opacity-80 border border-slate-300 text-slate-900 font-mono text-lg" value="9.5"></td>
            </tr>
          </tbody>
        </table>
        <button class="px-6 py-2 rounded-lg bg-blue-500 bg-opacity-30 hover:bg-blue-600 hover:bg-opacity-40 text-white font-semibold border border-white border-opacity-20 backdrop-blur-md transition">Save Scores</button>
      </div>
      <!-- Status/Info Alert -->
      <div class="bg-white bg-opacity-10 border border-blue-400 border-opacity-20 rounded-xl px-6 py-3 text-blue-100 text-center font-semibold backdrop-blur-md">
        Please ensure all scores are entered before saving. Auto-save will trigger when timer ends.
      </div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
