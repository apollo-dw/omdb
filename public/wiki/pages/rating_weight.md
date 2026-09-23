---
Title: Rating Weight
---

OMDB uses a **rating weight** to determine how much influence a user's ratings have on the charts. The weight is intended to favour users who have demonstrated a broad and consistent use of the rating system, while reducing the influence of inactive or otherwise deweighted accounts. The system is designed so that the vast majority of users have full weighting, with only outlier cases receiving noticably lower weights.

A user's weight is calculated from several factors:

## Rating Count

Users receive more weight as they submit more ratings, linearly increasing up to **50 ratings.**

- 0 ratings: 0x
- 25 ratings: 0.5x
- 50 or more ratings: 1x

## Rating Diversity

The system also considers how varied a user's ratings are by using [**entropy**](https://en.wikipedia.org/wiki/Entropy). Users who have a rating distribution that are concentrated on only a subset of the full possible rating scale are likely to be deweighted.

## Inactivity

Users who have not accessed the site for **90 days or more** receive a **30% reduction** to their calculated weight. This allows the charts to be reflective of the active userbase on the site.

## Abuse

Users who are identified as abusing the rating system may have their weighting manually lowered to **0**.

## Overall Calculation

All factors are multiplied together to calculate a user's final weight. Except for users who are manually deweighted for abuse, the minimum weight that a user can have is **0.1**.
