<?php
    $REC_DEFAULTS = [
        "weights" => [
            "avgScore" => 5, // weighted avg rating from users who rated the diff
            "descriptorScore" => 4, // Overall multiplier for the descriptor scores provided in ../descriptors.json
            "monthProximity" => 2, // when ranked within settings.yearWindow years of the seed
            "sharedNominator" => 1, // per nominator shared with the set
            "sharedMapper" => 4, // per mapper shared with the diff
            "cohortLift" => 8, // how much higher the seed users rate the diff vs everyone else
            "cohortCoverage" => 16, // share of the seed users vs everyone so big fanbases of the diff get no bump in this
            "correlation" => 1, // how the similar users rated both diffs generally (PEARSON CORRELATION COEFF)
            "srProximity" => 1, // how close the diffs are in star rating
        ],

        "settings" => [
            "proximityMonths" => 24,  // abs(diff rank date - TARGET) <= window
            "maxScoreShare" => 0.9, // max fraction of fans
            "maxScoreFloor" => 80, // avoid overfiltering cuz of the share settings
            "liftShrink" => 10, // n = u need 50% of the cohortLift value
            "coverageFade" => 80, // diminish the effect of cohort if the # of people rating is n large
            "coverageCurve" => 2, // exponent setting so 90% of people rating is more than twice vs 45% fans
            "corrShrink" => 10, // similar to bayes avg, correlations are shrunk by n/(n+this)
            "minRaters" => 5, // diffs need at least this many users who rated BOTH maps
            "minCorrelation" => 0, // ignore candidates correlated below this (0 = anything negatively),
            "srWindow" => 0.5, // SR diff via fraction, so 0.5 = 50% of the diff's SR as the limit
        ],

        "weightDescriptions" => [
            "avgScore" => "weighted avg rating from users who rated the diff",
            "descriptorScore" => "Overall multiplier for the descriptor scores provided in ../assets/descriptors.json",
            "monthProximity" => "when ranked within settings.proximityMonths months of the seed",
            "sharedNominator" => "per nominator shared with the set",
            "sharedMapper" => "per mapper shared with the diff",
            "cohortLift" => "how much higher the cohort rates the diff vs everyone else",
            "cohortCoverage" => "share of the cohort vs everyone so big playerbases get less bump",
            "correlation" => "pearson correlation of how similar users rated both diffs",
            "srProximity" => "how close the diffs are in star rating",
        ],

        "settingDescriptions" => [
            "proximityMonths" => "abs(diff rank date - TARGET) <= window",
            "maxScoreShare" => "max fraction of raters",
            "maxScoreFloor" => "avoid overfiltering cuz of the share settings",
            "liftShrink" => "n = u need 50% of the cohortLift value",
            "coverageFade" => "diminish the effect of cohort if the rater base is n large",
            "coverageCurve" => "exponent setting so 90% shared raters is more than twice vs 45%",
            "corrShrink" => "similar to bayes avg, correlations are shrunk by n/(n+this)",
            "minRaters" => "diffs need at least this many shared raters to use cohort math",
            "minCorrelation" => "ignore candidates correlated below this (0 = anything negatively)",
            "srWindow" => "SR diff via fraction, so 0.5 = 50% of the diff's SR as the limit",
        ],
    ];