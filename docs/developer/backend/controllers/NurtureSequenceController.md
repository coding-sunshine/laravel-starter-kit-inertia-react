# NurtureSequenceController

## Purpose

Manage nurture sequences and enroll contacts in them.

## Location

`app/Http/Controllers/NurtureSequenceController.php`

## Routes

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | /nurture-sequences | nurture-sequences.index | List all sequences |
| POST | /nurture-sequences | nurture-sequences.store | Create a new sequence |
| POST | /nurture-sequences/enroll/{contact} | nurture-sequences.enroll | Enroll a contact |

## Related Components

- **Action**: `EnrollInNurtureSequenceAction`
- **Page**: `nurture-sequences/index`
