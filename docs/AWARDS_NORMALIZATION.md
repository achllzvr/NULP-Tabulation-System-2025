# Awards & Normalization Specification

## 1. Award Types
Supported award_type values and computation logic (see `AwardsService`):

1. SINGLE_CRITERION
   - Inputs: `single_criterion_id`
   - Metric: Average of (override_score || raw_score) across all judges for that criterion.
   - Ordering: Descending metric, tie break by participant name.

2. MULTI_CRITERION
   - Inputs: `aggregation_round_ids` (JSON array of round IDs)
   - Metric: Sum over selected rounds & criteria of (score / max_score) * weight.
   - Equivalent to aggregating weighted totals across multiple rounds.

3. OVERALL
   - Semantically identical to MULTI_CRITERION but used to designate final comprehensive awards (branding distinction).

4. PEOPLE_CHOICE
   - Inputs: `v_people_choice_votes` view (participant_id, votes)
   - Metric: votes integer.
   - Requires external ingestion/population of the view or a backing table with aggregated votes.

Edge Handling:
- Missing configuration fields produce an error entry in preview results.
- Persist mode skips problematic awards (returns error) and does not partially persist winners for those.

## 2. Computation Flow
- Admin triggers preview via `compute_awards` (no persistence) or `compute_awards_persist` (writes rows to `award_winners`).
- On persistence: existing `award_winners` rows for each award are deleted then replaced in a single transaction per award.
- Winners persisted include a `rank_position` (1..N) based on ordering; ties are not expanded currently—first ordering stable.

## 3. Normalization Strategies
Normalization is applied automatically in `LeaderboardService` when `rounds.score_normalization_strategy` is set to one of the supported strategies.

### RAW (default)
- No transformation; weighted_total = Σ((score/max)*weight).

### Z_SCORE
- Treat raw weighted_total values as distribution X.
- Compute mean μ and sample standard deviation σ.
- Transform each score to z = (x - μ) / σ.
- Replacement: weighted_total = z (rounded to 6 decimals).
- Ranking: higher z ranks higher.
- Edge: σ=0 (all equal) -> abort normalization (leave raw ordering).

### MIN_MAX_PER_JUDGE
- For each judge, compute their participant totals (raw weighted contributions).
- Per judge apply min-max scaling: (value - min)/(max - min); if range=0 -> 0.5 fallback.
- Participant normalized score = average of scaled judge scores.
- Replacement: weighted_total = normalized average (0..1, 6 decimals).
- Ranking: higher normalized score ranks higher.

Rationale:
- Z_SCORE normalizes distribution to mean 0, std 1, diminishing impact of judges with generally high or low scoring trends only at aggregate level.
- MIN_MAX_PER_JUDGE compensates for lenient/strict judges individually before aggregation, equalizing each judge's influence.

## 4. Data Requirements & Assumptions
- `rounds.score_normalization_strategy` column exists (ENUM or VARCHAR). Missing column silently defaults to RAW.
- `round_criteria.max_score` and `round_criteria.weight` are populated and weights per round sum to 100 before opening.
- `scores` table includes `raw_score` and optional `override_score` for corrections.
- For PEOPLE_CHOICE, the view `v_people_choice_votes` must exist; otherwise award entry returns an error in preview.

## 5. API Endpoints
| Action | Method | Auth | Body Fields | Description |
|--------|--------|------|-------------|-------------|
| compute_awards | POST/GET | ADMIN | pageant_id (optional if in session) | Preview awards (no persistence) |
| compute_awards_persist | POST | ADMIN + CSRF | pageant_id, csrf_token | Compute and persist award winners |
| list_awards | GET | ADMIN | pageant_id | List award definitions |
| award_winners | GET | ADMIN | award_id | List persisted winners |

## 6. UI Behavior
- Admin Awards page: Preview button shows computed winners (not yet persisted). Persist button commits and refreshes preview.
- Public Awards page: Only displays persisted winners; preview output never exposed publicly.

## 7. Auditing
- Preview: `AWARDS_COMPUTE_PREVIEW` logs pageant_id and count.
- Persist: `AWARDS_COMPUTE_PERSIST` logs pageant_id and count.

## 8. Future Enhancements
- Tie expansion: detect equal metric lines and display shared ranks.
- Per-award custom tie-break criteria sequences.
- Award-level normalization override (e.g., force RAW for a special title even if round uses normalization).
- Soft deletion or versioned award winner snapshots.
- UI for configuring round sets & criteria selection for MULTI_CRITERION/OVERALL vs reading JSON.

## 9. Testing Considerations
- Unit test each award type with synthetic data sets: distinct values, tied values, missing config.
- Normalization tests: distributions with constant scores, varying ranges, single participant edge case.
- Performance: confirm MIN_MAX_PER_JUDGE query scales with (#judges * #participants) using index (scores.round_id, judge_user_id, participant_id).

## 10. Limitations
- Overlapping award definitions may recompute identical metrics (no caching yet).
- Normalization occurs per round only; cross-round normalization not applied for awards spanning multiple rounds.
- PEOPLE_CHOICE depends on external pipeline populating the votes view.
