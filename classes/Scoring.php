<?php

class Scoring {
    // Normalize a weight value which may be stored as either 0..1 or 0..100
    public static function weightFactor($weight) {
        if ($weight === null) return 0.0;
        $w = (float)$weight;
        return ($w > 1.0) ? ($w / 100.0) : $w;
    }

    // Returns override score if present, otherwise raw score
    public static function effectiveScore($raw, $override) {
        if ($override !== null && $override !== '') {
            return (float)$override;
        }
        return (float)($raw ?? 0.0);
    }

    // Compute weighted contribution from raw/override and weight
    public static function weighted($raw, $override, $weight) {
        return self::effectiveScore($raw, $override) * self::weightFactor($weight);
    }

    // Compute normalized weighted score: (score/max) * weight, scaled 0..1
    public static function normalizedWeighted($raw, $override, $weight, $maxScore) {
        $score = self::effectiveScore($raw, $override);
        $max = (float)($maxScore ?? 0.0);
        if ($max <= 0.0) return 0.0;
        return ($score / $max) * self::weightFactor($weight);
    }

    // Helper to scale a 0..1 value to percentage 0..100
    public static function toPercent($value01) {
        return ((float)$value01) * 100.0;
    }
}

?>