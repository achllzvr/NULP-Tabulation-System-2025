
# Pageant Scoring System - Formulas and Equations

## Pre-Q&A Scoring (100%)
**Formula:**
```
PreQnA_Total = (Advocacy × 0.20) +
(Talent × 0.10) +
(Production.StagePresence × 0.10) +
(Production.Coordination × 0.10) +
(Uniform.Confidence × 0.05) +
(Uniform.Neatness × 0.05) +
(Formal.Elegance × 0.05) +
(Formal.Confidence × 0.05) +
(Sports.Fitness × 0.05) +
(Sports.Presentation × 0.05) +
(Poise.NaturalBeauty × 0.10) +
(Poise.EleganceCharm × 0.10)
```

---

## Top 5 Advancing
**Logic:**
```
Top5 = Participants with the 5 highest PreQnA_Total scores
```

---

## Final Q&A Round (100%)
**Formula:**
```
Final_Score = (Q&A × 0.60) + (BeautyElegancePoise × 0.40)
```

---

## Special Awards
**Formula:**
```
BestInCriterion_i = max_over_participants( Score_criterion / MaxScore )
```
