# Architecture Overview

This application follows a strict, type-safe architecture with clear separation of concerns.

## Key Principles

- **Actions-Oriented**: Business logic lives in single-purpose Action classes
- **Type Safety**: 100% type coverage with explicit types everywhere
- **Immutable-First**: Data structures favor immutability
- **Fail-Fast**: Errors caught at compile-time

## Architecture Layers

```
┌─────────────────────────────────────────┐
│         Inertia Pages (React)            │
│    resources/js/pages/**/*.tsx          │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Controllers                      │
│    app/Http/Controllers/*.php         │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Actions                         │
│    app/Actions/*.php                   │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│         Models & Database               │
│    app/Models/*.php                    │
└─────────────────────────────────────────┘
```

## Request Flow

1. **Route** → Defined in `routes/web.php`
2. **Controller** → Handles HTTP request, validates input
3. **Action** → Contains business logic
4. **Model** → Interacts with database
5. **Response** → Returns Inertia response with data
6. **Page** → React component renders with data

## Documentation

For detailed documentation on each layer:
- [Actions](./../backend/actions/README.md)
- [Controllers](./../backend/controllers/README.md)
- [Pages](./../frontend/pages/README.md)
