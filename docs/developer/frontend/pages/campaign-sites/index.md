# Campaign Sites Index

## Purpose

Lists all campaign sites for the current organization with options to edit via Puck or preview publicly.

## Location

`resources/js/pages/campaign-sites/index.tsx`

## Route Information

- **URL**: `/campaign-sites`
- **Route Name**: `campaign-sites.index`
- **HTTP Method**: GET
- **Middleware**: auth, tenant

## Props (from Controller)

| Prop | Type | Description |
|------|------|-------------|
| sites | PaginatedSites | Paginated list of campaign sites |
