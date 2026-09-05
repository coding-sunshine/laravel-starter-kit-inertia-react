# Rake Management System
## Complete User Flow Storyboard (Unloading to RR Prediction)

This document explains the full end-to-end operational flow of the system from truck arrival at siding to RR prediction and rake completion.

It is written in functional sequence order so developers can clearly understand:
- What the user sees
- What actions are available
- What state transitions occur
- What visual indicators must be shown
- What conditional logic applies

This is a UI + Flow oriented document.

------------------------------------------------------------

# 1. Dashboard Overview

When the user logs in, they land on the Dashboard.

The dashboard shows:

- Active Rakes Count
- Available Coal Stock (MT)
- Pending Indents Count
- Rakes In Process
- Rakes Completed Today

This page is informational only.

------------------------------------------------------------

# 2. Truck Unloading Module

Purpose:
Manage truck arrival, unloading, validation, and stock update.

## 2.1 Truck List View

User sees:

- Vehicle Number
- Challan Number
- Mine Net Weight
- Current Status
- Open Button

Statuses:

- ARRIVED
- UNDER_VALIDATION
- READY_FOR_UNLOAD
- UNLOADING
- TARE_VALIDATION
- COMPLETED
- STOCK_ADDED
- CANCELLED

## 2.2 Truck Detail View

When user clicks "Open":

User sees:

- Vehicle details
- Challan details
- Mine Net Weight
- Current Status
- Available action buttons (based on state)

## 2.3 State Flow

ARRIVED
→ Start Validation
→ UNDER_VALIDATION

UNDER_VALIDATION
→ Approve Validation → READY_FOR_UNLOAD
→ Cancel Truck → CANCELLED

READY_FOR_UNLOAD
→ Start Unloading → UNLOADING

UNLOADING
→ Complete Unloading → TARE_VALIDATION

TARE_VALIDATION
→ Validate Tare → COMPLETED

If tare mismatch:
- Repeat unloading process
- Minor deviation allowed as per business rule

COMPLETED
→ Add to Stock → STOCK_ADDED

STOCK_ADDED
- No further actions

CANCELLED
- No further actions

## 2.4 Business Rule

Tare weight mismatch means:
- Coal not fully unloaded
- Unloading must repeat
- Minor acceptable deviation threshold must be defined

------------------------------------------------------------

# 3. Stock Ledger Module

Purpose:
Track coal inventory at siding.

## 3.1 View

User sees:

- Date
- Vehicle
- Quantity Added
- Running Balance

Only trucks with status STOCK_ADDED appear here.

Running balance updates cumulatively.

------------------------------------------------------------

# 4. Indent Module

Purpose:
Request rake allocation from Railways.

## 4.1 Indent List View

User sees:

- Indent Number
- Status
- Start Time
- End Time
- Open Button

Statuses:

- RAISED
- APPROVED
- REJECTED
- CANCELLED
- RAKE_ARRIVED
- RAKE_CREATED

## 4.2 Create Indent

User clicks "Create New Indent":

- New indent created
- Status = RAISED
- Start Time = Current Time

## 4.3 State Flow

RAISED
→ Approve → APPROVED
→ Reject → REJECTED
→ Cancel → CANCELLED

APPROVED
→ Mark Rake Arrived → RAKE_ARRIVED

RAKE_ARRIVED
- TXR inspection ongoing
- Show TXR info section
- Show Unfit Wagon count
- Show Memo Number
- Button: Create Rake

RAKE_CREATED
- Rake now managed in Rake Module
- Button: Open Rake Panel

------------------------------------------------------------

# 5. TXR Flow (Part of Indent)

When status = RAKE_ARRIVED:

Railway performs wagon inspection.

For each wagon:
- Mark Fit or Unfit
- Record memo reference
- Unfit wagons cannot be used for loading

User sees:
- Total wagons inspected
- Unfit wagon count
- Memo number
- Button to view wagon list

End of TXR Flow:
- User creates Rake entry
- Status moves to RAKE_CREATED

------------------------------------------------------------

# 6. Rake Module

Purpose:
Manage loading, inspection, weighment, RR prediction.

## 6.1 Rake List View

User sees:

- Rake Number
- Current Status
- Weighment Count
- Elapsed Time (Live Timer if active)
- Open Button

Statuses:

- TXR
- LOADING
- GUARD_INSPECTION
- IN_MOTION_WEIGHMENT
- COMPLETED

## 6.2 Rake Detail View

Displays:

- Rake Number
- Current Stage
- Weighment Count
- Live Timer (starts at dispatch)
- Wagon Theatre View (grid layout)
- Wagon Detail Panel
- Predict RR Button

## 6.3 Wagon Theatre View

Visual grid representation.

Color Coding:

- Red = Unfit Wagon
- Orange = Overloaded Wagon
- Blue = Normal
- Default = Fit and within limit

Clicking wagon shows:

- Wagon Number
- Capacity
- Loaded Weight
- Fit Status
- Overload Amount

## 6.4 Rake Stage Flow

1. TXR (Inspection Phase)

2. Dispatch (Timer Starts)

3. LOADING
   - Load coal into wagons
   - Update wagon loaded weight

4. GUARD_INSPECTION
   - Railway guard checks for spillage

5. IN_MOTION_WEIGHMENT
   - Train moves at 5-7 kmph
   - Record weight for each wagon
   - Update weighment count

If overload detected:
- Remove extra coal
- Repeat guard inspection
- Repeat in-motion weighment
- Increase weighment count

If pass:
- Mark as COMPLETED
- Ready for dispatch to power plant

------------------------------------------------------------

# 7. Demurrage Logic

Timer starts at Dispatch.

Free Time = 3 hours (180 minutes)

If total loading time exceeds 180 minutes:
- Extra time calculated
- Even 1 minute extra = 1 full hour penalty
- Penalty Hours = CEIL(extra_minutes / 60)
- Demurrage calculated

------------------------------------------------------------

# 8. RR Prediction (From Rake Screen)

User clicks "Predict RR"

System calculates:

Total Weight = Sum of all loaded fit wagons

Freight = Total Weight × Rate Per MT

GST = Freight × GST %

Total = Freight + GST

Displayed:

- Total Weight
- Rate
- Freight
- GST
- Total Payable

This is predictive only.

------------------------------------------------------------

# 9. Final Dispatch

After successful weighment:

- Rake marked COMPLETED
- RR predicted
- Rake dispatched to power plant

------------------------------------------------------------

# Complete High-Level Storyboard

Truck Arrival
→ Validation
→ Unloading
→ Tare Validation
→ Stock Updated
→ Indent Raised
→ Approved
→ Rake Arrived
→ TXR Inspection
→ Rake Created
→ Dispatch (Timer Start)
→ Loading
→ Guard Inspection
→ In-Motion Weighment
→ If Fail → Reload Loop
→ If Pass → Demurrage Calculation
→ Predict RR
→ Dispatch to Power Plant

------------------------------------------------------------

This completes the full user flow for the Rake Management System UI Prototype.
