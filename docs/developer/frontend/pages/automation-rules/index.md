# Automation Rules Page

## Location

`resources/js/pages/automation-rules/index.tsx`

## Route

`GET /automation-rules` — name: `automation-rules.index`

## Purpose

Lists automation rules for the current organization. Allows creating, enabling/disabling, and deleting rules.

## Props

| Prop | Type | Description |
|------|------|-------------|
| `rules` | `AutomationRule[]` | List of automation rules |

## Features

- Rule cards showing name, event, active status, run count, last run date
- Enable/disable toggle per rule
- Delete with confirmation
- Inline create form: name, event dropdown, description, active checkbox
- Pan analytics: `automation-rules-tab`
