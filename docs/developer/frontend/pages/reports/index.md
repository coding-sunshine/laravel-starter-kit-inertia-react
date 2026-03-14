# reports/index

## Purpose

Reports landing page displaying a grid of available report types. Each report card links to its detail page.

## Location

`resources/js/pages/reports/index.tsx`

## Route

`GET /reports` → `ReportController@index`

## Props

```ts
{
  reports: Array<{
    id: string;
    title: string;
    description: string;
    icon: string;
    href: string;
  }>;
}
```
