# Siding to RR Generation – Complete Operational Flow

This document describes the complete operational flow from truck unloading at siding to Railway Receipt (RR) generation and reconciliation.

---

# Phase 1 – Truck Arrival and Coal Unloading

## 1. Truck Arrival at Siding

A coal-loaded truck arrives at the railway siding.

The siding operator:
- Creates a vehicle unload entry in the system
- Records challan details
- Sends truck for arrival weighment

---

## 2. Arrival Weighment Validation

The system compares:

- Railway weighbridge gross/net weight  
- Mine weighment slip data  

If mismatch is detected:
- Unloading is cancelled
- Process ends

If weight matches:
- Truck is allowed to proceed to unloading

---

## 3. Coal Unloading

The truck unloads coal at the siding yard.

System State: UNLOADING

---

## 4. Empty Tare Weighment

After unloading:
- Truck returns to weighbridge
- Empty tare weight is recorded
- Compared against mine tare weight

Validation rule:

If absolute difference > fixed tolerance:
- Coal is still inside truck
- Truck is sent back for further unloading
- Tare validation repeated

If difference within tolerance:
- Unloading is considered successful
- Stock ledger updated

This completes one truck cycle.

---

# Phase 2 – Stock Accumulation

Every successful unloading:

- Updates the coal stock ledger
- Increases available stock

Important:
Indent creation is not automatic.
It depends on business decision and stock level.

---

# Phase 3 – Indent Creation

When sufficient stock is available:

Siding In-Charge raises an indent.

Indent lifecycle:

RAISED  
→ APPROVAL_PENDING  
→ APPROVED  
or  
→ REJECTED  

If approved:
- Indent becomes eligible for rake allocation

If rake is created:
- Indent state becomes RAKE_ALLOCATED

---

# Phase 4 – Rake Arrival and TXR Inspection

After indent approval:

Railway provides rake at siding.

System creates rake entry.

Rake lifecycle (Inspection Phase):

CREATED  
→ ARRIVED  
→ TXR_PENDING  
→ TXR_IN_PROGRESS  
→ TXR_COMPLETED  

TXR Officer:
- Inspects each wagon
- Marks wagon as fit or unfit
- Issues memo for unfit wagons

Important:
Timer has NOT started yet.

---

# Phase 5 – Rake Dispatch and Timer Start

After TXR completion:

Railway dispatches rake to loading line.

At this moment:
- Timer starts
- Free loading window = 3 hours (180 minutes)

State: DISPATCHED

---

# Phase 6 – Wagon Loading

Loading team begins loading coal into wagons.

State: LOADING

Loading is based on:
- Wagon capacity
- Permissible weight limits
- Precision filling target

---

# Phase 7 – Guard Inspection

After loading:

Railway guard inspects:
- Coal leveling
- Spillage around rake
- Track safety compliance

State: GUARD_INSPECTION

---

# Phase 8 – In-Motion Weighment

Train moves slowly (5–7 kmph).

In-motion weighment is performed.

State: WEIGHMENT_IN_PROGRESS

Decision point:

If overload detected:
- Remove excess coal
- State: RELOADING
- Perform guard inspection again
- Perform weighment again
- Timer continues

If weighment passes:
- State: READY_FOR_FINAL_DISPATCH

Loop continues until weighment passes.

---

# Phase 9 – Final Dispatch

Rake is dispatched toward power plant.

State: COMPLETED

Timer stops at this moment.

---

# Phase 10 – Demurrage Calculation

System calculates:

Total Time = Completion Time − Dispatch Time

If Total Time ≤ 180 minutes:
- No penalty

If Total Time > 180 minutes:
- Extra Minutes = Total − 180
- Penalty Hours = CEIL(Extra Minutes / 60)
- Even 2 minutes extra = 1 full hour penalty

Penalty recorded in rake_extra_penalties table.

---

# Phase 11 – RR Prediction

System calculates predicted Railway Receipt amount using:

- Chargeable weight
- Distance
- Freight rate per MT
- GST percentage
- Demurrage charges
- Overload penalties
- Other applicable charges

Stored in:
rr_predictions

This represents expected railway billing.

---

# Phase 12 – Actual RR Received

Railway issues official RR later.

Finance team uploads:

- RR number
- Chargeable weight
- Freight amount
- GST
- Demurrage
- Other charges

Stored in:
rr_actuals

---

# Phase 13 – Reconciliation

System compares:

Predicted RR  
vs  
Actual RR  

Differences are analyzed.

Financial closure completed.

---

# Structural Overview

There are three main operational streams:

1. Stock Stream  
   Truck unloading → Stock ledger accumulation  

2. Rake Stream  
   Indent → TXR → Dispatch → Loading → Weighment → Completion  

3. Financial Stream  
   Prediction → Actual RR → Reconciliation  

These streams intersect but remain logically separated.

---

# Critical Business Rules

- Arrival weight mismatch → Cancel unloading  
- Tare mismatch → Repeat unloading  
- Timer starts only at rake dispatch  
- Timer never pauses during reload  
- Reload loop allowed multiple times  
- Even small delay causes full-hour demurrage  
- Rake cannot dispatch before TXR completion  
- Indent cannot be cancelled after rake allocation  

---

End of Document
