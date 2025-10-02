To Do for Tabulation System

PARTICIPANTS
- [ ] Participants Photo Upload - Admin
- [x] ~~Participants Photo Display - Judges~~

DB
- [x] ~~Criteria for Final Round in Database~~
- [x] ~~Create First Round - Admin, DB~~
    - [x] ~~Adjust Consequential Rounds~~

GATE CHECKS
- [x] ~~Gate for Final Round~~
    - [x] ~~Final Round can’t open without Advancements Validations being complete~~
- [ ] Gate for Ties
    - [ ] With the ongoing current round, the next round will not be available unless all ties have been settled.

SETTINGS
- [x] ~~Admin settings for toggling name visibility and image for judges in judging panel~~

TIE RESOLUTION
- [x] ~~Tie Resolution UI~~
    - [x] ~~If tie, admin opens Tie Breaker UI for Judges Panels~~
        - [x] ~~2 min countdown~~
        - [x] ~~Judges can save or wait for timer to finish and system will automatically refresh and update scores based on new scores from judges.~~
            - [x] ~~Have modals saying “By clicking save, you confirm your…”~~
        - [x] ~~Admin side, can see which judges have saved their scores.~~
        - [x] ~~Once timer is done, admin’s close tie breaker panel button activates.~~
        - [x] ~~Once closed, finalize button will be activated~~
            - [x] ~~Modal “You confirm…”~~
            - [x] ~~Have revert buttons for fallbacks~~
    - [x] ~~If not tie, system will not show it~~

ADVANCEMENT PAGE
- [x] ~~Disable advancement until there is a tie~~
    - [x] ~~Have redirect to Tie Resolution Page~~
- [x] ~~Once preliminary round is done, admin can now open advancements validation panel for judges~~
    - [x] ~~Judges’ panel, shows the scores they’ve put for each participant.~~
    - [x] ~~One central button that says “By pressing this button, you confirm and sign that the scores you’ve placed are correct and participants can proceed to advancements”~~
        - [x] ~~Judges will save and will wait for admin/ host to close advancements validation.~~
    - [x] ~~Admin side, can see which judges have saved their scores.~~
    - [x] ~~Once all judges have saved their scores, button for Close Advancements Validation Panel will activate~~
    - [x] ~~Once closed, finalize button will be activated~~
        - [x] ~~Modal “You confirm…”~~
        - [x] ~~Have revert buttons for fallbacks~~

LEADERBOARD AND AWARDS PAGE REWORK
- [x] ~~Leaderboards and Awards Page rework~~
    - [x] ~~Centralized Leaderboards and Awards Page~~
    - [x] ~~Current Rankings in Leaderboard should display both Ambassador and Ambassadress side by side and their rankings are based on what division they are in. That means we’ll remove the divison selector and just have a Round Selector and the round should be Pageant and Final Q&A Round.~~
    - [x] ~~Also have “Raw Tabulated Data View” and will show the tabulated form of the scores per criteria (horizontal top row) per participant (vertical column on start), per judge (selector) and can be filtered by round, by criteria~~
        - [x] ~~So the sequence will be drop-downs for judge and round.~~
            - [x] ~~then the table will show the judges’ scores for all participants per criteria.~~
        - [x] ~~In case of major/ minor errors in score calculation/ saving, manual override can be made for the editing of the scores through a password protected gate~~
            - [x] ~~For extra security, the respective judge the score is being fixed/ edited should be required to input their credentials to allow the edit to happen.~~
    - [x] ~~Leaderboards are automatically calculated by the scores saved~~
        - [x] ~~Formula to be followed is in /TBD–References_only/formula.md~~
    - [x] ~~Awards are automatically calculated~~
        - [x] ~~Awards will ONLY BE shown to public IF host/ admin has pressed the “Publish” awards button so that the awards and awardees will be saved in the database and the public viewing page for the awards will fetch those saved awards and awardees data.~~
        - [x] ~~Once published, no fallbacks for reversions but admin can toggle showing and hiding awards page~~

REWORK
- [x] ~~Create Participants Duos.~~
    - [x] ~~Can be separate bridge table from participants or maybe the divisions table.~~
    - [x] ~~Admin will setup who the Duos/ Pairs are.~~
    - [x] ~~Duos/ Pairs participant type will be used in Pre-Pageant Round (Advocacy and Talent rounds).~~
- [x] ~~Add Pre-Pageant Round Type.~~
    - [x] ~~Pre-Pageant Round Type uses Duos/ by pair scoring.~~
- [x] ~~REQUIRED: Assign Judges on Round Feature.~~
- [ ] In Live Control Center, instead of Live Activity, Have a live judge monitoring similar to the advancements panel. This will display the judges and their progress FOR THE CURRENT ACTIVE ROUND.
- [x] ~~If there is ongoing round, blur the rows in the results page and an indicator that indicates “Ongoing Round. Results will reveal afterwards”~~
- [x] ~~Participant Details Modal. Display Criteria and Score.~~
- [ ] Judges’ Advancements Validation UI Fix
    - [ ] Ongoing Advancement Validation but UI still displays the No Active Round below the Advancements Validation Card
    - [ ] Also, details are not showing up.
- [ ] Advancements Page
    - [ ] Once the first automatic finalization of the advancements is done, stop the listener to cancel checking for completion of judges loop.
    - [ ] The auto advancement should be from the Advocacy up to Sports Wear (Entire Pre-Q&A Round)
    - [ ] Once Advancements are confirmed, disable page again.
- [ ] Results Page and Advancements Computations
    - [ ] Scores for all rounds should be a max of 100% (as per guidelines) and computations of the scores and weights of individual round will be followed as per guidelines.
- [x] ~~Remove the Awards Page since we’ll be using the Awards in the Results page.~~
- [x] ~~Make Public View fetch real data.~~

Questions
- [x] What does Propose in Assign Major Awards do and What does Save Winners do.
- [x] For Auto-Generate in Award Results, this shows up:
	“Data truncated for column 'division_scope' at row 1”

OTHERS
- [ ] Participants Page Filter by button/ dropdown.
- [ ] Activity Cards/ Containers - live_control Live Activity, Recent Activity - index, Scoring Criteria - rounds
    - [ ] Have a limit/ make scrollable after 4 entries
    - [ ] For Scoring Criteria, have More Details to show card modal of all  criteria
- [ ] Ensure Public Display Settings are functional.
- [ ] Add “NU x AVR” in footer for landing page and public viewing page.

PORTFOLIO
- [ ] Redo Portfolio for new visitors.
    - [ ] Logo show up in first 2 seconds then animate out
    - [ ] A live loop of background of a moving grid
    - [ ] Bento Box style with modals/ expansions per click on cards
    - [ ] Responsive
    - [ ] White and Red
    - [ ] Cards:
        - [ ] About Me
        - [ ] Certifications
        - [ ] Technical Skills with Proficiency
        - [ ] My Works
        - [ ] Experiences
        - [ ] Testimonials
        - [ ] My Links
