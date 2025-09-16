# Database Relationships Overview

## Core Entity Relationships

### 1. User Management
```
users (1) ←→ (1) user_profiles
users (1) ←→ (1) user_stats
users (1) ←→ (n) user_badges
users (1) ←→ (n) notifications
users (1) ←→ (n) activity_feed
```

### 2. Team System
```
users (n) ←→ (n) teams [through team_members]
teams (1) ←→ (1) team_stats
teams (1) ←→ (n) team_members
```

### 3. Competition System
```
competitions (1) ←→ (n) competition_participants
competitions (1) ←→ (n) competition_challenges
users (n) ←→ (n) competitions [through competition_participants]
teams (n) ←→ (n) competitions [through competition_participants]
```

### 4. Challenge System
```
challenges (1) ←→ (n) challenge_submissions
challenge_categories (1) ←→ (n) challenges
competitions (n) ←→ (n) challenges [through competition_challenges]
users (n) ←→ (n) challenges [through challenge_submissions]
```

### 5. Learning System
```
learning_paths (1) ←→ (n) learning_modules
users (n) ←→ (n) learning_paths [through user_learning_progress]
users (n) ←→ (n) learning_modules [through user_learning_progress]
```

### 6. Achievement System
```
badges (n) ←→ (n) users [through user_badges]
```

### 7. Leaderboard System
```
leaderboards (n) ←→ (1) competitions [optional]
leaderboards (n) ←→ (1) users [entity_type = 'user']
leaderboards (n) ←→ (1) teams [entity_type = 'team']
```

## Key Design Decisions

### 1. Flexible Competition System
- Support both individual and team competitions
- Dynamic challenge assignment to competitions
- Flexible scoring and ranking system

### 2. Comprehensive Learning Tracking
- Track progress at both path and module level
- Support multiple content types (video, article, lab, quiz)
- Time tracking and completion status

### 3. Robust Achievement System
- Badge-based achievements with criteria
- Points and rewards system
- Skill-based and participation-based badges

### 4. Performance-Optimized Leaderboards
- Cached leaderboard data for fast queries
- Support for different leaderboard types
- Time-based rankings (monthly, yearly)

### 5. Scalable Notification System
- Type-based notifications
- Related entity tracking
- Read/unread status management