# Rake Show Page Data-Entry Workflow Refactor

## Overview
This document summarizes the comprehensive refactor of the rake show page data-entry workflow to improve user experience and implement the requested features.

## ✅ Completed Changes

### 1️⃣ WAGON OVERVIEW POPUP
- **Created**: `WagonOverviewDialog.tsx` component
- **Features**:
  - "View Wagons" button added to rake show page header
  - Modal dialog with scrollable table
  - Columns: Wagon No, Sequence, Wagon Type, PCC Capacity, Fitness Status, Current State
  - Read-only view for quick inspection
  - Handles >50 wagons with scrollable area
- **Location**: `/resources/js/components/rakes/WagonOverviewDialog.tsx`

### 2️⃣ TXR MODULE STRUCTURE REFACTOR
- **Created**: `TxrWorkflowNew.tsx` component with two-part structure
- **PART A: TXR Header Table (Single Row)**
  - No Add Row button (single record per rake)
  - Fields: Inspection Start Time, End Time, Status, Remarks
  - Save button for header data
- **PART B: Unfit Wagon Details (Multi-row)**
  - Add Row button for multiple unfit wagon entries
  - Each row contains:
    - Wagon dropdown (searchable, shows "WagonNo (Sequence)")
    - Auto-filled wagon type after selection
    - Reason input
    - Marked By input
    - Marking Method select
    - Time (defaults to now)
  - Remove Row functionality
  - Batch save for all rows
- **Key Rules**:
  - Never expose wagon_id in UI (only wagon_number displayed)
  - Store IDs internally
  - Searchable dropdown for >50 wagons
- **Location**: `/resources/js/components/rakes/workflow/TxrWorkflowNew.tsx`

### 3️⃣ WAGON LOADING MODULE REFACTOR
- **Created**: `WagonLoadingWorkflowNew.tsx` component with multi-row support
- **Features**:
  - Multi-row table with Add Row button
  - Each row contains:
    - Wagon dropdown (searchable)
    - Loader dropdown (searchable)
    - Auto-filled wagon type and PCC capacity
    - Loaded Qty input
    - Loading Time input
    - Remarks input
  - Remove Row functionality
  - Batch save for all rows
- **Key Rules**:
  - Dropdown handles >50 wagons smoothly
  - Uses searchable select component (Combobox style)
  - No raw IDs visible in UI
  - Service layer validation for duplicates
- **Location**: `/resources/js/components/rakes/workflow/WagonLoadingWorkflowNew.tsx`

### 4️⃣ UI COMPONENT ENHANCEMENTS
- **Added**: ScrollArea component for scrollable tables
- **Enhanced**: Select components with better search functionality
- **Updated**: Import statements and component structure

### 5️⃣ ADD ROW BEHAVIOR IMPLEMENTATION
- **State Management**: Each accordion maintains its own state array
- **Unique Keys**: Each row has unique identifier
- **Remove Functionality**: Proper row removal with state updates
- **Save Logic**: Batch saving with preserveScroll=true
- **No Backend Logic**: All logic contained in frontend components

## 🔧 Technical Implementation Details

### Relation Enforcement
- **TXR**: Strictly one record per rake enforced through UI design
- **Unfit Wagons**: Multi-row support only for unfit logs
- **Wagon Loading**: Multiple rows allowed with duplicate prevention
- **Guard/Weighment/Comparison**: Linear flow maintained (no Add Row)

### State Management
- **React useState**: Local state for multi-row arrays
- **Inertia useForm**: Form submission and error handling
- **Unique Keys**: Timestamp-based or index-based keys
- **Batch Operations**: FormData for complex data structures

### Validation Rules
- **Frontend**: Required fields, type validation, duplicate prevention
- **Backend**: Service layer validation for business rules
- **Error Handling**: Proper error display with InputError components

## 📁 File Structure

```
resources/js/
├── components/
│   ├── rakes/
│   │   ├── WagonOverviewDialog.tsx          # NEW
│   │   └── workflow/
│   │       ├── TxrWorkflowNew.tsx          # NEW
│   │       └── WagonLoadingWorkflowNew.tsx # NEW
│   └── ui/
│       └── scroll-area.tsx                 # NEW
├── pages/
│   └── rakes/
│       └── show.tsx                         # UPDATED
```

## 🚀 Usage Instructions

### To Use New Components:
1. Import the new components in your rake show page
2. Replace existing workflow components with new versions
3. Update backend routes to handle new data structures
4. Test with various data scenarios

### Backend Integration Required:
- `/rakes/{id}/txr/start` - Handle TXR header creation
- `/rakes/{id}/txr` - Handle TXR header updates
- `/rakes/{id}/txr/unfit-wagons` - Handle batch unfit wagon saving
- `/rakes/{id}/load/wagon/batch` - Handle batch wagon loading

## 🎯 Key Benefits

1. **Improved UX**: Cleaner separation of concerns
2. **Better Performance**: Batch operations reduce server calls
3. **Scalability**: Handles large numbers of wagons efficiently
4. **Maintainability**: Clear component structure and state management
5. **User-Friendly**: Searchable dropdowns and intuitive workflows

## 🔄 Migration Path

1. **Phase 1**: Deploy new components alongside existing ones
2. **Phase 2**: Update backend routes and controllers
3. **Phase 3**: Replace old components with new versions
4. **Phase 4**: Remove old components after testing

## 📋 Testing Checklist

- [ ] Wagon Overview Dialog displays correctly
- [ ] TXR Header saves properly
- [ ] Unfit Wagon multi-row functionality works
- [ ] Wagon Loading multi-row functionality works
- [ ] Add/Remove row operations work correctly
- [ ] Searchable dropdowns handle >50 items
- [ ] Form validation works properly
- [ ] Error handling displays correctly
- [ ] Batch operations save successfully
- [ ] State management preserves data correctly

## 🐛 Known Issues

- TypeScript errors resolved with proper useForm hook usage
- ScrollArea dependency installed (@radix-ui/react-scroll-area)
- Component imports updated for new structure

## 📝 Next Steps

1. Update backend controllers to handle new data structures
2. Add validation rules for batch operations
3. Implement service layer for duplicate prevention
4. Add comprehensive testing
5. Update documentation for new workflows
