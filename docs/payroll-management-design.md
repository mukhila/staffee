# Payroll Management Design for Staffee

## Goals

Build a payroll module for Laravel 12 Staffee that:

- uses existing `users`, `attendances`, `leave_requests`, `time_trackers`, HR lifecycle, and department data
- supports monthly and bi-weekly payroll
- calculates payroll with BCMath-safe decimal handling
- supports multi-currency salary definitions and payroll runs
- maintains audit history for all inputs, rules, approvals, and outputs
- supports statutory and voluntary deductions
- handles salary revisions and full & final settlement

This design aligns with the current codebase, especially:

- `App\Models\Attendance`
- `App\Models\LeaveRequest`
- `App\Models\TimeTracker`
- `App\Models\HR\SalaryRevision`
- `App\Models\HR\FinalSettlement`

---

## 1. Complete Database Schema

### 1.1 Core master tables

#### `payroll_grade_structures`

Defines reusable salary bands by grade, role family, or department.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| code | string(50) | unique, like `G5-ENG` |
| name | string(150) | human-readable |
| department_id | foreignId nullable | optional scope |
| currency_code | string(3) | ISO currency |
| pay_frequency | enum | `monthly`, `bi_weekly` |
| min_ctc | decimal(18,6) | optional range floor |
| max_ctc | decimal(18,6) | optional range ceiling |
| overtime_policy_id | foreignId nullable | policy link |
| status | enum | `draft`, `active`, `inactive` |
| effective_from | date | |
| effective_to | date nullable | |
| created_by | foreignId | users |
| approved_by | foreignId nullable | users |
| approved_at | timestamp nullable | |
| notes | text nullable | |
| timestamps | timestamps | |

Indexes:

- unique(`code`, `effective_from`)
- index(`department_id`, `status`)

#### `payroll_component_definitions`

Master definition of all possible payroll components.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| code | string(60) | unique, like `BASIC`, `HRA`, `PF_EMP` |
| name | string(150) | |
| category | enum | `earning`, `deduction`, `employer_contribution`, `information` |
| component_type | enum | `basic`, `allowance`, `reimbursement`, `statutory`, `tax`, `loan`, `insurance`, `adjustment`, `benefit`, `encashment`, `gratuity`, `bonus`, `other` |
| calculation_method | enum | `fixed`, `percentage_of_component`, `percentage_of_gross`, `percentage_of_taxable_gross`, `formula`, `slab`, `per_day`, `per_hour`, `manual_input` |
| taxable | boolean | income-tax applicable |
| pro_ratable | boolean | prorate on attendance/pay days |
| affects_gross | boolean | contributes to gross |
| affects_net | boolean | contributes to net |
| employer_only | boolean | for employer PF/ESI etc. |
| arrear_eligible | boolean | whether retro differences can be generated |
| display_order | integer | payslip ordering |
| rounding_scale | unsignedTinyInteger | default `2` |
| status | enum | `active`, `inactive` |
| description | text nullable | |
| formula_expression | text nullable | only if formula-based |
| metadata | json nullable | extensible rules |
| timestamps | timestamps | |

Seed examples:

- `BASIC`
- `DA`
- `HRA`
- `CONVEYANCE`
- `MEDICAL`
- `OTHER_ALLOWANCE`
- `OT_PAY`
- `BONUS`
- `ARREARS`
- `INCOME_TAX`
- `PROF_TAX`
- `PF_EMPLOYEE`
- `PF_EMPLOYER`
- `ESI_EMPLOYEE`
- `ESI_EMPLOYER`
- `LOAN_RECOVERY`
- `INSURANCE`
- `CHARITY`
- `LWP_DEDUCTION`
- `LEAVE_ENCASHMENT`
- `GRATUITY`

#### `payroll_component_dependencies`

For percentage-based calculations.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| component_definition_id | foreignId | target component |
| basis_component_definition_id | foreignId | source component |
| percentage | decimal(10,6) | |
| cap_amount | decimal(18,6) nullable | |
| effective_from | date | |
| effective_to | date nullable | |
| timestamps | timestamps | |

#### `statutory_deduction_rules`

Country or entity-specific PF/ESI/PT rules.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| country_code | string(2) | default `IN` |
| state_code | string(10) nullable | needed for PT |
| rule_type | enum | `pf`, `esi`, `professional_tax`, `income_tax_support` |
| employee_rate | decimal(10,6) nullable | |
| employer_rate | decimal(10,6) nullable | |
| wage_ceiling | decimal(18,6) nullable | |
| min_wage | decimal(18,6) nullable | |
| max_amount | decimal(18,6) nullable | |
| slab_json | json nullable | PT slabs or state slabs |
| effective_from | date | |
| effective_to | date nullable | |
| status | enum | `active`, `inactive` |
| timestamps | timestamps | |

#### `tax_regimes`

Tax regime definitions per fiscal year.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| country_code | string(2) | |
| fiscal_year | string(9) | ex. `2026-2027` |
| regime_code | string(30) | ex. `old`, `new` |
| name | string(120) | |
| standard_deduction | decimal(18,6) default 0 | |
| rebate_amount | decimal(18,6) default 0 | |
| surcharge_json | json nullable | |
| cess_percent | decimal(10,6) default 0 | |
| status | enum | `active`, `inactive` |
| effective_from | date | |
| effective_to | date nullable | |
| timestamps | timestamps | |

#### `tax_brackets`

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| tax_regime_id | foreignId | |
| income_from | decimal(18,6) | |
| income_to | decimal(18,6) nullable | open-ended |
| rate_percent | decimal(10,6) | |
| fixed_tax_amount | decimal(18,6) default 0 | optional |
| rebate_eligible | boolean default false | |
| timestamps | timestamps | |

---

### 1.2 Employee salary structure tables

#### `employee_salary_structures`

This is the canonical effective salary structure per employee.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| user_id | foreignId | users |
| grade_structure_id | foreignId nullable | grade template |
| pay_frequency | enum | `monthly`, `bi_weekly` |
| currency_code | string(3) | |
| annual_ctc | decimal(18,6) nullable | optional |
| monthly_base_salary | decimal(18,6) | |
| standard_work_days | unsignedSmallInteger | usually 26/30/31 policy-driven |
| standard_work_hours | decimal(8,4) | |
| overtime_eligible | boolean | |
| tax_regime_id | foreignId nullable | selected regime |
| professional_tax_state_code | string(10) nullable | |
| pf_enabled | boolean default true | |
| esi_enabled | boolean default false | |
| status | enum | `draft`, `pending_approval`, `active`, `superseded`, `inactive` |
| effective_from | date | |
| effective_to | date nullable | |
| approval_status | enum | `draft`, `pending`, `approved`, `rejected` |
| approved_by | foreignId nullable | users |
| approved_at | timestamp nullable | |
| created_by | foreignId | users |
| reason | text nullable | |
| source_revision_id | foreignId nullable | back-link to salary revision request |
| timestamps | timestamps | |

Indexes:

- index(`user_id`, `status`)
- unique(`user_id`, `effective_from`)

#### `employee_salary_components`

Stores resolved component values for an employee structure.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| salary_structure_id | foreignId | employee_salary_structures |
| component_definition_id | foreignId | payroll_component_definitions |
| amount_type | enum | `fixed`, `percentage` |
| amount | decimal(18,6) nullable | fixed amount |
| percentage | decimal(10,6) nullable | if percentage-based |
| basis_component_definition_id | foreignId nullable | source component |
| min_amount | decimal(18,6) nullable | |
| max_amount | decimal(18,6) nullable | |
| sequence | unsignedInteger | ordering |
| is_active | boolean | |
| notes | text nullable | |
| metadata | json nullable | formula params |
| timestamps | timestamps | |

Unique:

- unique(`salary_structure_id`, `component_definition_id`)

#### `salary_revision_requests`

Use this instead of overloading the current `salary_revisions` history table.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| user_id | foreignId | |
| current_salary_structure_id | foreignId nullable | |
| proposed_grade_structure_id | foreignId nullable | |
| revision_type | enum | `joining`, `promotion`, `annual_increment`, `market_adjustment`, `correction`, `demotion`, `transfer`, `other` |
| effective_date | date | |
| retroactive_from | date nullable | if arrears needed |
| proposed_currency_code | string(3) | |
| proposed_base_salary | decimal(18,6) | |
| old_gross_monthly | decimal(18,6) nullable | snapshot |
| new_gross_monthly | decimal(18,6) | snapshot |
| impact_summary | json nullable | delta by component |
| reason | text nullable | |
| status | enum | `draft`, `pending_manager`, `pending_hr`, `pending_finance`, `approved`, `rejected`, `implemented` |
| submitted_by | foreignId | users |
| approved_by | foreignId nullable | users |
| approved_at | timestamp nullable | |
| rejected_by | foreignId nullable | users |
| rejected_at | timestamp nullable | |
| rejection_reason | text nullable | |
| timestamps | timestamps | |

#### `salary_revision_request_components`

Per-component proposal rows for revision requests.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| revision_request_id | foreignId | |
| component_definition_id | foreignId | |
| old_amount | decimal(18,6) nullable | |
| new_amount | decimal(18,6) nullable | |
| old_percentage | decimal(10,6) nullable | |
| new_percentage | decimal(10,6) nullable | |
| change_type | enum | `added`, `updated`, `removed`, `unchanged` |
| timestamps | timestamps | |

#### Existing `salary_revisions` table

Keep it as immutable history, but extend it later with:

- `salary_structure_id` nullable
- `revision_request_id` nullable
- `gross_monthly` decimal(18,6) nullable
- `net_estimate` decimal(18,6) nullable

That table remains the applied ledger after approval, while `salary_revision_requests` is the workflow table.

---

### 1.3 Payroll processing tables

#### `payroll_calendars`

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| company_code | string(30) nullable | optional multi-entity support |
| pay_frequency | enum | `monthly`, `bi_weekly` |
| period_code | string(30) | ex. `2026-04`, `2026-BW-09` |
| period_start | date | |
| period_end | date | |
| pay_date | date | |
| attendance_cutoff_date | date | |
| timesheet_cutoff_date | date | |
| leave_cutoff_date | date | |
| status | enum | `draft`, `open`, `locked`, `processed`, `paid` |
| timestamps | timestamps | |

Unique:

- unique(`period_code`, `pay_frequency`)

#### `payroll_runs`

One batch run for a payroll calendar.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| payroll_calendar_id | foreignId | |
| run_type | enum | `regular`, `supplementary`, `adjustment`, `full_final` |
| currency_code | string(3) | |
| employee_scope_type | enum | `all`, `department`, `employee_list` |
| employee_scope | json nullable | |
| status | enum | `draft`, `collecting_inputs`, `calculating`, `pending_approval`, `approved`, `posted`, `paid`, `cancelled` |
| generated_at | timestamp nullable | |
| input_snapshot_hash | string(64) nullable | detect data drift |
| totals_json | json nullable | gross/net/tax totals |
| error_log | json nullable | employee-level issues |
| locked_by | foreignId nullable | users |
| locked_at | timestamp nullable | |
| created_by | foreignId | users |
| approved_by | foreignId nullable | users |
| approved_at | timestamp nullable | |
| timestamps | timestamps | |

#### `payroll_run_employees`

Tracks inclusion/exclusion decisions for a run.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| payroll_run_id | foreignId | |
| user_id | foreignId | |
| salary_structure_id | foreignId nullable | snapshot source |
| employment_status_snapshot | string(50) nullable | active, resigned, terminated |
| inclusion_status | enum | `included`, `excluded`, `hold` |
| exclusion_reason | text nullable | |
| source_summary | json nullable | attendance/leave/time summary |
| timestamps | timestamps | |

Unique:

- unique(`payroll_run_id`, `user_id`)

#### `payroll_slips`

Per employee payroll result.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| payroll_run_id | foreignId | |
| payroll_calendar_id | foreignId | denormalized query speed |
| user_id | foreignId | |
| salary_structure_id | foreignId nullable | |
| currency_code | string(3) | |
| pay_frequency | enum | |
| period_start | date | |
| period_end | date | |
| payable_days | decimal(8,4) | |
| worked_days | decimal(8,4) | |
| paid_leave_days | decimal(8,4) | |
| unpaid_leave_days | decimal(8,4) | |
| overtime_hours | decimal(10,4) | |
| gross_earnings | decimal(18,6) | |
| total_deductions | decimal(18,6) | |
| employer_contributions | decimal(18,6) default 0 | |
| taxable_income | decimal(18,6) | |
| tax_amount | decimal(18,6) | |
| net_pay | decimal(18,6) | |
| ytd_gross | decimal(18,6) default 0 | |
| ytd_tax | decimal(18,6) default 0 | |
| ytd_net | decimal(18,6) default 0 | |
| status | enum | `draft`, `approved`, `published`, `paid`, `cancelled` |
| pdf_path | string nullable | |
| emailed_at | timestamp nullable | digital distribution |
| published_at | timestamp nullable | employee visible |
| paid_at | timestamp nullable | |
| payment_mode | string nullable | |
| payment_reference | string nullable | |
| calculation_version | unsignedInteger default 1 | recompute tracking |
| snapshot_json | json | resolved inputs + settings |
| timestamps | timestamps | |

Indexes:

- index(`user_id`, `period_start`, `period_end`)
- index(`payroll_run_id`, `status`)

#### `payroll_slip_lines`

Normalized earnings/deduction lines.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| payroll_slip_id | foreignId | |
| component_definition_id | foreignId nullable | null for ad hoc |
| line_code | string(60) | |
| line_name | string(150) | |
| line_category | enum | `earning`, `deduction`, `employer_contribution`, `information` |
| source_type | enum | `salary_structure`, `attendance`, `leave`, `time_tracking`, `manual_adjustment`, `tax_engine`, `settlement`, `arrear`, `statutory` |
| source_reference_type | string nullable | model/table name |
| source_reference_id | unsignedBigInteger nullable | |
| calculation_basis | string(255) nullable | explain basis |
| quantity | decimal(12,4) nullable | hours/days |
| rate | decimal(18,6) nullable | |
| amount | decimal(18,6) | signed positive amount |
| taxable_amount | decimal(18,6) default 0 | |
| is_ytd_included | boolean default true | |
| display_order | unsignedInteger | |
| metadata | json nullable | |
| timestamps | timestamps | |

#### `payroll_adjustments`

Manual entries outside standard salary structure.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| user_id | foreignId | |
| payroll_calendar_id | foreignId nullable | optional pre-run target |
| component_definition_id | foreignId | |
| adjustment_type | enum | `earning`, `deduction` |
| amount | decimal(18,6) | |
| quantity | decimal(12,4) nullable | |
| reason | text | |
| recurrence | enum | `one_time`, `repeat_until`, `fixed_installments` |
| start_period | string(30) nullable | |
| end_period | string(30) nullable | |
| remaining_installments | unsignedInteger nullable | |
| source_type | string nullable | loan/insurance/manual |
| source_id | unsignedBigInteger nullable | |
| status | enum | `draft`, `pending_approval`, `approved`, `processed`, `cancelled` |
| created_by | foreignId | |
| approved_by | foreignId nullable | |
| approved_at | timestamp nullable | |
| timestamps | timestamps | |

#### `payroll_input_snapshots`

Stores raw summarized inputs per employee before calculation.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| payroll_run_id | foreignId | |
| user_id | foreignId | |
| attendance_summary | json nullable | |
| leave_summary | json nullable | |
| time_summary | json nullable | |
| deduction_summary | json nullable | |
| salary_structure_snapshot | json nullable | |
| tax_context_snapshot | json nullable | |
| snapshot_hash | string(64) | |
| timestamps | timestamps | |

Unique:

- unique(`payroll_run_id`, `user_id`)

#### `payroll_calculation_logs`

Audit trail for every compute pass.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| payroll_slip_id | foreignId nullable | |
| payroll_run_id | foreignId nullable | |
| user_id | foreignId nullable | |
| stage | enum | `input_collection`, `earning_calc`, `deduction_calc`, `tax_calc`, `net_calc`, `approval`, `publish`, `payment` |
| action | string(100) | |
| input_payload | json nullable | |
| output_payload | json nullable | |
| formula_used | text nullable | |
| performed_by | foreignId nullable | user or system |
| performed_at | timestamp | |
| timestamps | timestamps | |

#### `payroll_audits`

General compliance audit trail.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| auditable_type | string | |
| auditable_id | unsignedBigInteger | |
| event | string(100) | created, updated, approved, recalculated |
| old_values | json nullable | |
| new_values | json nullable | |
| ip_address | string nullable | |
| user_agent | text nullable | |
| actor_id | foreignId nullable | |
| timestamps | timestamps | |

---

### 1.4 Loans, insurance, declarations, digital delivery

#### `employee_loans`

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| user_id | foreignId | |
| loan_type | string(80) | |
| principal_amount | decimal(18,6) | |
| issued_date | date | |
| recovery_start_period | string(30) | |
| installment_amount | decimal(18,6) | |
| total_installments | unsignedInteger | |
| remaining_balance | decimal(18,6) | |
| status | enum | `active`, `completed`, `cancelled`, `hold` |
| notes | text nullable | |
| timestamps | timestamps | |

#### `employee_loan_installments`

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| loan_id | foreignId | |
| payroll_calendar_id | foreignId nullable | |
| due_period | string(30) | |
| scheduled_amount | decimal(18,6) | |
| recovered_amount | decimal(18,6) default 0 | |
| status | enum | `pending`, `processed`, `skipped`, `waived` |
| payroll_slip_line_id | foreignId nullable | |
| timestamps | timestamps | |

#### `employee_benefit_deductions`

For voluntary insurance, charity, society, etc.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| user_id | foreignId | |
| component_definition_id | foreignId | |
| amount | decimal(18,6) nullable | |
| percentage | decimal(10,6) nullable | |
| start_date | date | |
| end_date | date nullable | |
| status | enum | `active`, `inactive` |
| metadata | json nullable | policy number etc. |
| timestamps | timestamps | |

#### `employee_tax_declarations`

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| user_id | foreignId | |
| tax_regime_id | foreignId | |
| fiscal_year | string(9) | |
| declaration_status | enum | `draft`, `submitted`, `verified`, `locked` |
| declared_amounts | json | per section |
| proof_status | json nullable | |
| submitted_at | timestamp nullable | |
| verified_by | foreignId nullable | |
| verified_at | timestamp nullable | |
| timestamps | timestamps | |

#### `payroll_documents`

Stores payslips and settlement slips.

| Column | Type | Notes |
| --- | --- | --- |
| id | bigint | PK |
| user_id | foreignId | |
| documentable_type | string | `payroll_slips`, `final_settlements` |
| documentable_id | unsignedBigInteger | |
| document_type | enum | `payslip`, `tax_sheet`, `settlement_slip` |
| file_path | string | |
| file_name | string | |
| mime_type | string | |
| delivery_channel | enum | `portal`, `email`, `download` |
| delivered_at | timestamp nullable | |
| timestamps | timestamps | |

---

### 1.5 Existing table changes recommended

#### Existing `users`

Add:

- `currency_code` string(3) nullable
- `tax_identifier` string nullable
- `uan_number` string nullable
- `esi_number` string nullable
- `payment_mode` string nullable
- `bank_account_last4` string(4) nullable
- `joining_salary_structure_id` nullable

#### Existing `leave_types`

Add:

- `is_paid` boolean default true
- `affects_payroll` boolean default true
- `encashable` boolean default false
- `encashment_rate_basis` enum nullable: `basic`, `gross`, `custom`

#### Existing `time_categories`

Add:

- `counts_as_overtime` boolean default false
- `payroll_multiplier` decimal(10,4) default 1.0000
- `earning_component_definition_id` foreignId nullable

#### Existing `attendance_exceptions`

Add optional payroll flags:

- `is_payroll_resolved` boolean default false
- `payroll_impact_json` json nullable

#### Existing `final_settlements`

Keep current table, but add:

- `payroll_run_id` nullable
- `settlement_slip_number` string nullable
- `ytd_tax` decimal(18,6) nullable
- `tax_adjustment` decimal(18,6) default 0
- `settlement_snapshot` json nullable

---

## 2. Salary Structure Design

### Design principles

1. Use `employee_salary_structures` as the effective contract-like snapshot.
2. Use `payroll_component_definitions` as reusable master components.
3. Use `employee_salary_components` to resolve a user’s actual amounts for a date range.
4. Use `payroll_grade_structures` to standardize salary bands by grade.
5. Keep `salary_revisions` immutable as applied history.

### Structure model

Each employee has one active salary structure for a given date range.

Example:

- Base salary: 50,000
- DA: fixed 4,000
- HRA: 40% of Basic
- Conveyance: fixed 1,600
- Medical: fixed 1,250
- PF: 12% of Basic with ceiling rules
- ESI: rate-based when applicable

### Grade-based structures

Recommended flow:

1. Create `payroll_grade_structures` for bands such as `G1`, `G2`, `M1`, `M2`.
2. Attach default earning and deduction templates to each grade.
3. When onboarding or promoting a user, generate a personalized `employee_salary_structure`.
4. Allow controlled overrides per employee while preserving grade defaults as reference.

### Effective dating

Every salary structure and component is date-effective.

Rules:

- never overwrite an approved active structure in place
- supersede it by setting `effective_to`
- create a new row starting from the revision date
- if revision is retroactive, generate arrears in the next payroll run or supplementary run

---

## 3. Payroll Calculation Algorithm

### Calculation sequence

Run payroll in this order:

1. Lock payroll calendar and selected employees.
2. Snapshot salary structure, tax regime, attendance, leaves, time tracking, and manual adjustments.
3. Compute payable days and overtime quantities.
4. Compute earnings.
5. Compute statutory and voluntary deductions.
6. Compute taxable income.
7. Compute period tax.
8. Compute net pay.
9. Persist slip lines and audit logs.
10. Route for approval and publish.

### Input aggregation

For each employee and payroll period:

- attendance summary:
  - scheduled days
  - present days
  - absent days
  - late/exception days
  - overtime minutes/hours
- leave summary:
  - paid leave days
  - unpaid leave days
  - encashable leave days
- time tracking summary:
  - OT-eligible hours from categories with `counts_as_overtime = true`
  - billable hours for analytics
- deductions summary:
  - loan installments due
  - insurance deductions
  - charitable/voluntary deductions
  - prior-period arrears or recoveries

### Day-rate formulas

Support three payroll policies:

- calendar day basis: monthly salary / calendar days in month
- fixed payroll day basis: monthly salary / configured payroll days, often 30
- working day basis: monthly salary / payable working days, often 26

Store this as company payroll policy, not hard-coded.

### Earning calculation

For each earning component:

- if fixed and proratable:
  - prorated_amount = component_amount * payable_days / standard_work_days
- if fixed and non-proratable:
  - amount = component_amount
- if percentage-based:
  - resolve basis component amount first, then apply percent
- OT:
  - hourly_rate = monthly_base_salary / standard_work_hours_per_month
  - overtime_amount = overtime_hours * hourly_rate * OT multiplier
- manual earnings:
  - bonus, incentive, arrears, reimbursement, encashment

### Deduction calculation

Order matters:

1. leave without pay deduction
2. PF / ESI
3. professional tax
4. voluntary deductions
5. income tax
6. loan and recovery adjustments

### Net calculation

```text
gross_earnings =
  sum(all earnings affecting gross)

total_deductions =
  sum(all deductions affecting net)

net_pay =
  gross_earnings - total_deductions
```

### BCMath-safe implementation rules

In service code:

- store amounts as strings while calculating
- use `bcadd`, `bcsub`, `bcmul`, `bcdiv`, `bccomp`
- standardize internal precision to 6
- round only at line level or final display level
- persist database decimals as `decimal(18,6)` and display as 2 decimals

### Pseudocode

```php
$gross = '0.000000';
$deductions = '0.000000';

$payableDays = bcsub(
    bcadd($workedDays, $paidLeaveDays, 4),
    $unpaidLeaveDaysAdjustments,
    4
);

foreach ($earningComponents as $component) {
    $amount = $earningEngine->calculate($component, $context);
    $gross = bcadd($gross, $amount, 6);
    $slip->addLine($component, 'earning', $amount, $context);
}

foreach ($deductionComponents as $component) {
    $amount = $deductionEngine->calculate($component, $context);
    $deductions = bcadd($deductions, $amount, 6);
    $slip->addLine($component, 'deduction', $amount, $context);
}

$taxableIncome = $taxEngine->monthlyTaxableIncome($slip, $context);
$incomeTax = $taxEngine->periodTax($user, $taxableIncome, $context);
$deductions = bcadd($deductions, $incomeTax, 6);

$net = bcsub($gross, $deductions, 6);
```

### Recalculation controls

If attendance, leave, or timesheets change after snapshot:

- regular run remains locked
- create adjustment run or supplementary run
- never silently mutate an approved slip

---

## 4. Tax Calculation Rules

### Income tax

Use annualized projection model:

1. Project annual taxable income from current recurring salary structure.
2. Add one-time earnings actually paid or planned.
3. Subtract allowed exemptions/declarations based on selected tax regime.
4. Apply tax bracket slabs.
5. Apply surcharge, rebate, cess.
6. Divide remaining annual tax by remaining payroll periods.
7. Recalculate each payroll run using YTD actual tax paid.

Formula:

```text
projected_annual_tax =
  slab_tax(projected_annual_taxable_income)
  - rebate
  + surcharge
  + cess

current_period_tax =
  (projected_annual_tax - ytd_tax_paid) / remaining_periods
```

### Professional tax

State-based slab lookup using `statutory_deduction_rules`.

Rule examples:

- state-specific monthly fixed amount
- some months may have a higher PT amount
- apply only if salary crosses slab threshold

### Provident Fund (PF)

Typical rule model:

- employee contribution = eligible_basic * employee_rate
- employer contribution = eligible_basic * employer_rate
- eligible_basic may be capped at statutory wage ceiling

Configurable fields:

- PF enabled per employee
- ceiling apply yes/no
- restricted wage basis components, usually Basic + DA

### ESI

Typical rule model:

- if gross <= eligibility threshold:
  - employee contribution = gross * employee_rate
  - employer contribution = gross * employer_rate
- if over threshold:
  - no ESI

### Tax-saving deductions

Use `employee_tax_declarations` with verification status.

Treatment:

- only `verified` amounts reduce taxable income after cut-off
- `submitted` but unverified can reduce provisionally if company policy allows
- freeze declarations after configured payroll month cut-off

### Quarterly and annual reporting

Persist computed tax basis per period so reports can show:

- monthly tax withheld
- quarterly remittance support
- annual employee tax statement

---

## 5. Full & Final Settlement Process

Staffee already has `final_settlements`. Expand it into a payroll-driven process.

### Trigger

On approved termination or accepted resignation with LWD:

1. create settlement draft
2. lock attendance/leave/time inputs up to last working date
3. compute final salary and terminal benefits
4. route for HR + Finance approval
5. generate settlement slip

### Settlement components

Earnings:

- pending salary for days worked in final month
- unpaid approved reimbursements
- leave encashment
- gratuity
- bonus / incentives
- arrears
- recovery reversals if applicable

Deductions:

- notice pay shortfall
- loan outstanding
- advance salary recovery
- asset recovery or penalty adjustments
- statutory tax adjustment

### Settlement algorithm

```text
final_month_salary =
  prorated recurring earnings up to last_working_date

terminal_earnings =
  leave_encashment + gratuity + bonus + arrears + other_earnings

settlement_deductions =
  notice_shortfall + loans + advances + tax_adjustment + other_deductions

net_settlement =
  final_month_salary + terminal_earnings - settlement_deductions
```

### Leave encashment

Use `leave_balances` and `leave_types.encashable`.

Policy-driven basis:

- Basic only
- Basic + DA
- Gross salary
- fixed encashment rate

### Gratuity

Keep formula configurable by jurisdiction and tenure policy.

Suggested config:

- minimum service years
- formula basis salary
- formula constant
- whether partial years qualify

### Settlement slip

Settlement slip should contain:

- employee details
- separation date and reason
- final month pay summary
- terminal earnings
- deductions and recoveries
- net payable
- YTD tax and adjustment summary

---

## 6. Integration with Leave, Attendance, and Time Tracking

### Attendance integration

Source: `attendances`

Use:

- `date`
- `status`
- `worked_minutes`
- `overtime_minutes`
- `shift_id`
- `is_shift_day`

Rules:

- payable workdays derive from validated attendance and approved leave
- unresolved attendance exceptions can place employee on payroll hold
- daily-rate staff can be paid off actual present days
- monthly salaried staff use attendance mainly for LWP and OT

Recommended service:

- `App\Services\Payroll\Input\AttendancePayrollInputService`

Output summary:

```json
{
  "scheduled_days": 26,
  "present_days": 24,
  "worked_days": "24.0000",
  "overtime_hours": "8.5000",
  "exceptions_pending": 1
}
```

### Leave integration

Source:

- `leave_requests`
- `leave_types`
- `leave_balances`

Rules:

- approved paid leave counts toward payable days
- approved unpaid leave creates `LWP_DEDUCTION`
- half-day leave should reduce days by `0.5`
- leave encashment uses final available encashable balance

Recommended service:

- `App\Services\Payroll\Input\LeavePayrollInputService`

### Time tracking integration

Source:

- `time_trackers`
- `time_categories`
- `billable_rates`

Rules:

- only completed entries are considered
- OT payroll uses categories marked `counts_as_overtime = true`
- billable hours should feed reporting, not salary by default
- if certain roles are hourly, payroll can use approved hours directly

Recommended service:

- `App\Services\Payroll\Input\TimeTrackingPayrollInputService`

### HR integration

Source:

- `salary_revisions`
- `promotion_requests`
- `termination_requests`
- `final_settlements`

Rules:

- approved salary revision creates new active salary structure
- approved promotion can trigger revision workflow
- termination drives full & final run inclusion

---

## 7. Reporting Structure

### Core reports

#### Monthly payroll summary

Filters:

- period
- department
- grade
- status
- currency

Columns:

- employee count
- total gross
- total deductions
- employer contributions
- total net
- total tax

#### Employee salary report

Shows:

- active salary structure
- component-wise amounts
- revision history
- YTD gross/tax/net

#### Department payroll report

Shows:

- department total gross
- department total net
- OT cost
- leave deduction amount
- headcount by payroll status

#### Tax reports

Shows:

- monthly withholding
- quarterly tax total
- annual tax projection
- employee-wise tax ledger

#### Deduction register

Shows:

- PF
- ESI
- PT
- loans
- insurance
- charity
- other recoveries

### Supporting reports

- payroll exception report
- unpublished payslips report
- arrears report
- full & final settlement report
- salary revision impact report
- employer contribution register
- bank transfer register

### Reporting architecture

Recommended query layer:

- `App\Services\Payroll\Reports\PayrollSummaryReportService`
- `App\Services\Payroll\Reports\EmployeePayrollReportService`
- `App\Services\Payroll\Reports\TaxReportService`

Use materialized snapshots from `payroll_slips` and `payroll_slip_lines`, not live recalculation.

---

## 8. Approval Workflows

### A. Salary structure approval

Flow:

1. HR drafts employee salary structure or grade assignment
2. Manager review optional
3. Finance review mandatory
4. HR/Admin final approval
5. structure becomes active on effective date

Statuses:

- `draft`
- `pending_manager`
- `pending_finance`
- `pending_hr_head`
- `approved`
- `rejected`

### B. Salary revision approval

Flow:

1. manager or HR raises revision request
2. HR validates policy fit and grade band
3. Finance validates cost impact
4. final approver approves
5. system creates new `employee_salary_structure`
6. system writes immutable entry into `salary_revisions`
7. if retroactive, generate arrear adjustment queue

### C. Payroll run approval

Flow:

1. payroll admin creates calendar and run
2. system collects inputs and calculates slips
3. payroll admin reviews exceptions
4. finance approves run
5. authorized signatory posts/publishes
6. employee slips released digitally
7. payment marked complete after bank confirmation

Statuses:

- `draft`
- `collecting_inputs`
- `calculating`
- `pending_approval`
- `approved`
- `posted`
- `paid`

### D. Manual adjustment approval

Applies to:

- bonuses
- one-time deductions
- reimbursements
- loan waivers

Flow:

1. created by HR/payroll
2. finance approval
3. included in next eligible payroll run

### E. Full & final approval

Flow:

1. HR opens settlement after termination approval
2. payroll computes final month and terminal items
3. department confirms recoveries/assets
4. finance approves payment
5. settlement published and paid

Statuses:

- `draft`
- `pending_department_clearance`
- `pending_finance`
- `approved`
- `paid`

---

## Recommended Laravel service architecture

Suggested services:

- `App\Services\Payroll\PayrollCalendarService`
- `App\Services\Payroll\PayrollRunService`
- `App\Services\Payroll\PayrollCalculationService`
- `App\Services\Payroll\PayrollSlipService`
- `App\Services\Payroll\SalaryStructureService`
- `App\Services\Payroll\SalaryRevisionWorkflowService`
- `App\Services\Payroll\Tax\TaxComputationService`
- `App\Services\Payroll\Statutory\PfService`
- `App\Services\Payroll\Statutory\EsiService`
- `App\Services\Payroll\Settlement\FullFinalSettlementService`

Suggested jobs:

- `GeneratePayrollRunJob`
- `GeneratePayrollSlipPdfJob`
- `EmailPayrollSlipJob`
- `RecomputeTaxProjectionJob`

Suggested policies/permissions:

- `payroll.view`
- `payroll.manage`
- `payroll.run.approve`
- `salary.structure.approve`
- `salary.revision.approve`
- `settlement.approve`
- `tax.report.view`

---

## Recommended implementation phases

### Phase 1

- schema for salary structures, payroll calendars, runs, slips, lines
- salary structure CRUD
- regular monthly payroll run
- payslip generation

### Phase 2

- tax engine
- PF/ESI/PT engine
- approval workflows
- reporting

### Phase 3

- salary revision workflow with arrears
- full & final settlement integration
- digital document distribution
- audit dashboards

---

## Key decisions for Staffee

1. Keep existing `salary_revisions` and `final_settlements`, but treat them as applied-history/settlement tables and add dedicated workflow + payroll tables around them.
2. Use normalized `payroll_component_definitions` and `employee_salary_components` instead of storing all allowances as columns.
3. Store money as `decimal(18,6)` and calculate with BCMath.
4. Freeze payroll inputs with snapshots before approval.
5. Use supplementary runs for retro changes instead of mutating published payroll.
