# Settings: Level & achievements

## Purpose

Displays the authenticated user's gamification data: current level, XP (points), progress toward the next level, and a list of achievements with unlock status. Read-only; no actions on this page. Shown only when the `gamification` feature flag is active.

## Location

`resources/js/pages/settings/achievements.tsx`

## Route Information

- **URL**: `/settings/achievements`
- **Route Name**: `achievements.show`
- **HTTP Method**: GET
- **Middleware**: web, auth, feature:gamification

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| level | number | Current user level (cjmellor/level-up) |
| points | number | Current XP total |
| next_level_percentage | number | 0–100 progress toward next level |
| achievements | AchievementItem[] | Achievements with id, name, description, image, is_secret, progress, unlocked_at |

## User Flow

1. User opens Settings and clicks "Level & achievements" (nav item visible when `features.gamification` is true).
2. Page loads; controller returns level, points, next_level_percentage, and achievements from the gamification package.
3. User sees level, XP, progress bar, and list of achievements (unlocked with date or locked/secret).

## Related Components

- **Controller**: `App\Http\Controllers\Settings\AchievementsController` (show)
- **Route**: `achievements.show`
- **Layouts**: AppLayout, SettingsLayout

## Implementation Details

Uses cjmellor/level-up data provided by the backend. The settings nav item is conditionally rendered from `resources/js/layouts/settings/layout.tsx` using `showAchievements` (Wayfinder) and `features.gamification` from shared props.
