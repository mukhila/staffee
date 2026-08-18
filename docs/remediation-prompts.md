# Staffee — Remediation & Build Prompts

Prompt pack derived from the codebase audit of branch `main` @ `cb34885` (18 Aug 2026).

Every prompt below is **self-contained**: it names the files, the defect, the fix and the
acceptance criteria, so an agent or developer can act without reading the audit first.

> **Line numbers are as of `cb34885`.** They drift as fixes land. Locate symbols by name
> (`grep`), not by line, and verify the described condition still exists before changing
> anything. If a defect is already fixed, say so and stop — do not invent replacement work.

---

## 0. How to use this pack

| Section | Use when |
|---|---|
| §1 Shared context | Paste at the top of **every** prompt below. Not optional. |
| §2 Master prompt | One agent runs the whole remediation sequence. |
| §3 Defect prompts | One focused change per session/PR. **Preferred.** |
| §4 Pending-work prompts | Payroll phase 3 and other designed-but-unbuilt work. |
| §5 New module prompts | Greenfield modules from the audit's proposal list. |
| §6 Order & definition of done | Sequencing and merge criteria. |

**Run `P-01` first and alone.** It restores the test suite, and every prompt after it
assumes tests can run.

---

## 1. Shared context — prepend to every prompt

```text
PROJECT CONTEXT

Staffee is a Laravel 12 / PHP 8.2 HRMS and workforce platform.
Stack: MySQL, Blade + Bootstrap 5, Vite, Laravel Reverb (broadcasting), queue on database.
Scale: 15 modules, 342 routes, 76 controllers, 81 models, 85 tables, 172 Blade views,
16 services under app/Services, 7 scheduled jobs in routes/console.php.

Roles are a plain string column on users: 'admin' | 'pm' | 'staff'.
Admin routes sit under prefix('admin')->name('admin.')->middleware('role:admin').
Staff-facing controllers live in app/Http/Controllers/Staff/.
Domain logic for leave, shift, time and payroll lives in app/Services/<Domain>/.
Money is decimal(18,6) and is calculated with BCMath — never floats, never round().

CONVENTIONS TO FOLLOW
- Match the surrounding code: same naming, comment density and structure. Read two or
  three neighbouring files before writing anything.
- Controllers validate with $request->validate() and pass the validated array onward.
  Never pass $request->all() into create() or update().
- Ownership checks use abort_if(...) / abort_unless(...) at the top of the method,
  matching Staff/LeaveController and Payroll/PayrollSlipController.
- New tables get a new migration; never edit a migration that has already shipped.
- New domain logic goes in a service under app/Services/<Domain>/, not in a controller.
- Every behavioural change ships with a test under tests/Feature/ or tests/Unit/.

HOW TO VERIFY
  php artisan test                    # full suite
  php artisan route:list              # route + middleware audit
  php artisan migrate:fresh --seed    # schema sanity (destructive — local only)

CONSTRAINTS
- No destructive git commands. Do not commit or push unless asked.
- Do not add a dependency without justifying it in your summary.
- If a described defect no longer exists, report that and stop.
- Report honestly: if tests fail after your change, show the output and say so.
```

---

## 2. Master prompt — full remediation sequence

Use when one agent should carry the whole programme.

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK

Work the Staffee remediation queue in the order below. The order is a dependency chain,
not a preference — step 1 restores the ability to test, and later steps are verified by
tests that cannot run until it lands.

  1. P-01  Fix the leave migration so the test suite boots
  2. P-03  Close the unauthenticated screenshot exposure
     P-07  Harden agent token handling
  3. P-02  Fix the full & final settlement crash
     P-09  Fix settlement worked-days calculation
     P-10  Implement notice-period recovery
     P-11  Fix gratuity ceiling/rounding and leave encashability
  4. P-04  Resolve the unused permission layer (wire it up or remove it)
  5. P-05  Fix routes pointing at missing controller methods
     P-06  Gate chat channel joins
     P-14  Remove mass assignment from five update paths
  6. P-08  Turn on real-time broadcasting
     P-13  Generate payslips as PDF
  7. P-12  Add monitoring data retention
     P-15  Fix the settlement audit-trail fallback
     P-16  Delete duplicate models
     P-17  Move the unrelated disk-forensics-app out of the repo
  8. Payroll phase 3 (§4) — only once the above is green

RULES OF ENGAGEMENT
- One logical change per commit, prompt ID in the message (e.g. "P-02: ...").
- Run `php artisan test` after every step. Do not proceed on a red suite.
- If a step turns out larger than described, stop and report rather than half-landing it.
- After each step report: what changed, what you verified, what you deliberately did not touch.
```

---

## 3. Defect prompts

### P-01 — Restore the test suite `CRITICAL`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Make the test suite runnable.

CURRENT STATE
`php artisan test` reports 47 failed, 1 passed. Every feature test dies during migration.

ROOT CAUSE
database/migrations/2026_04_23_030006_alter_leave_requests_table.php issues raw MySQL DDL
in both up() and down():

    DB::statement("ALTER TABLE leave_requests MODIFY COLUMN status
        ENUM('pending','manager_approved','approved','rejected','cancelled','auto_approved')
        NOT NULL DEFAULT 'pending'")

MODIFY COLUMN is MySQL-only. phpunit.xml runs DB_CONNECTION=sqlite, DB_DATABASE=:memory:,
so it raises: SQLSTATE[HY000]: General error: 1 near "MODIFY": syntax error

WHAT TO DO
Make the migration portable across MySQL and SQLite. In order of preference:
  a) Branch on the connection driver — keep the raw ENUM change for MySQL, and use a
     Schema-builder change (or a documented no-op, since SQLite does not enforce ENUM)
     otherwise.
  b) Replace the ENUM with a string column plus application-level validation.

Apply the same treatment to down(). The resulting MySQL schema must be byte-identical to
today's, so no production migration is implied.

ACCEPTANCE CRITERIA
- `php artisan test` runs to completion with no migration errors.
- Pass count materially higher than 1/48 — report the exact figure.
- `php artisan migrate:fresh` still succeeds against MySQL.
- Any test still failing afterwards is a genuine assertion failure, not a schema error.
  List those separately as follow-up work; they are not part of this task.
```

### P-02 — Fix the full & final settlement crash `CRITICAL`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Repair the leave-encashment query in full & final settlement.

CURRENT STATE
app/Services/Payroll/SettlementService.php, in calculateFullAndFinal():

    $encashableLeaveDays = (string) \App\Models\Leave\LeaveBalance::where('user_id', $user->id)
        ->whereHas('leaveType', fn ($query) => $query->where('is_paid', true))
        ->sum('balance');

There is no `balance` column on leave_balances. The columns are:
  opening_balance, carry_forward_days, accrued_days, used_days, pending_days,
  available_balance  (stored generated: opening + carry_forward + accrued - used)

Both callers in app/Http/Controllers/Admin/Payroll/SettlementController.php — the preview
and the slip generation — throw "Column not found". Exit settlement has never worked.

SECOND DEFECT, SAME QUERY
No ->where('year', ...) filter. leave_balances is unique on (user_id, leave_type_id, year),
so once the column name is corrected the query sums every year the employee ever accrued
into a single payout.

WHAT TO DO
1. Sum the correct column.
2. Scope to the settlement year, derived from $lastWorkingDate — not from now().
3. Decide deliberately whether pending_days should reduce the encashable figure. The
   Leave\LeaveBalance model already exposes an `effective_available` accessor that
   subtracts pending requests. State your choice and reasoning in the summary.

ACCEPTANCE CRITERIA
- Both SettlementController endpoints return a preview without error.
- New feature test: a user with paid-leave balances across two years is encashed for the
  settlement year only.
- Arithmetic still routes through PayrollCalculationService (BCMath), not floats.

OUT OF SCOPE — separate prompts: P-09, P-10, P-11, P-15.
```

### P-03 — Close the unauthenticated screenshot exposure `CRITICAL`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Stop serving employee monitoring screenshots to unauthenticated requests.

CURRENT STATE
app/Http/Controllers/Api/AgentController.php, screenshot(), stores uploads as:
    $path = $request->file('file')->store($dir, 'public');
with $dir = "monitoring/screenshots/{user_id}/Y/m/d".

app/Models/Monitoring/MonitoringScreenshot.php exposes them as public URLs:
    getUrlAttribute()          => asset('storage/' . $this->file_path)
    getThumbnailUrlAttribute() => asset('storage/' . ($this->thumbnail_path ?? $this->file_path))

`php artisan route:list` confirms `GET storage/{path}` carries NO middleware. Anyone
holding a URL has permanent unauthenticated access, and links leak via referrers, browser
history and forwarded messages.

NOTE: public/storage currently has no symlink, so the gallery is silently broken today.
Do NOT "fix" that by running storage:link — that activates the exposure.

WHAT TO DO
1. Store captures on the private `local` disk instead of `public`.
2. Add an authorised streaming route behind a controller authorization check. Follow the
   pattern in Staff/ProfileController::downloadDocument(). Decide whether the subject
   employee may view their own captures — ask if the product intent is unclear.
3. Repoint getUrlAttribute() and getThumbnailUrlAttribute() at the new route.
4. Update MonitoringScreenshotController's delete paths to the same disk.
5. Provide a one-off command to relocate existing files, and state clearly in your summary
   whether you ran it.

ACCEPTANCE CRITERIA
- A logged-out request for a screenshot URL returns 403 or 404, never the image.
- A non-admin staff user cannot fetch another employee's screenshot.
- The admin screenshot gallery still renders.
- A feature test covers all three cases.
```

### P-04 — Resolve the unused permission layer `CRITICAL` `DECISION`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Decide and fully implement the fate of Staffee's granular permission system.

CURRENT STATE
The repo contains a complete, working, entirely unused RBAC layer:
  - `permissions` and `permission_role` tables (with a category column)
  - app/Http/Middleware/CheckPermission.php, registered as 'permission' in bootstrap/app.php
  - app/Services/PermissionService.php
  - app/Traits/HasPermissions.php
  - a role-permission matrix UI (route admin.roles.matrix)
  - tests: tests/Unit/HasPermissionsTest.php, tests/Feature/PermissionEnforcementTest.php
  - database/seeders/PermissionSeeder.php, granting curated sets to 'pm' and 'staff'

`grep -c "permission:" routes/web.php` returns 0. All 236 admin routes gate on `role:admin`
alone. A 'pm' user is therefore refused every admin screen despite holding seeded
permissions, and HR/finance/team-lead separation of duties is impossible.

THIS IS A DECISION, NOT ONLY A PATCH. Pick one, justify it, implement it fully.

OPTION A — Wire it up  (recommended if multi-role access is on the roadmap)
  - Apply `permission:<slug>` middleware to each admin route group, using slugs already
    defined in PermissionSeeder.
  - Keep `role:admin` only where a capability must stay admin-exclusive (settings, role
    management, payroll publish).
  - Update resources/views/layouts/partials/sidebar.blade.php to hide links the user
    lacks permission for, via the HasPermissions trait.
  - Verify a 'pm' user reaches exactly what PermissionSeeder grants.

OPTION B — Remove it
  - Delete the middleware, service, trait, matrix UI, seeder entries, tables and tests.
  - Only if the product genuinely has three fixed roles forever.

ACCEPTANCE CRITERIA (Option A)
- Feature test signs in as 'pm'; allowed routes return 200, denied routes return 403.
- No admin route is left ungated.
- The sidebar shows a 'pm' user only what they can open.

Do not half-land this. RBAC applied to some routes and not others is worse than either end.
```

### P-05 — Fix routes pointing at missing controller methods `CRITICAL`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Resolve six routes that raise HTTP 500.

CURRENT STATE
routes/web.php registers three full resource controllers:
    Route::resource('test-cases', Staff\TestCaseController::class, ['as' => 'staff']);
    Route::resource('bugs', Staff\BugController::class, ['as' => 'staff']);
    Route::resource('daily-status-reports', Staff\DailyStatusReportController::class, ['as' => 'staff']);

Each registers seven actions. None of the three controllers defines show() or destroy().
GET /bugs/{bug}, DELETE /bugs/{bug} and their four siblings raise BadMethodCallException.

No Blade view links to these routes today, so the breakage is latent — but the routes are
published and any bookmark or direct navigation returns a server error.

WHAT TO DO — choose per resource, based on whether the product wants the screen:
  a) Wanted: implement show() and destroy() with the ownership checks the sibling actions
     already use —
       BugController:      $bug->reported_by === auth()->id() || $bug->assigned_to === auth()->id()
       TestCaseController: $testCase->created_by === auth()->id()
       DSRController:      $dailyStatusReport->user_id === auth()->id()
     ...plus the Blade views and links from the index pages.
  b) Not wanted: add ->except(['show', 'destroy']) to the resource registration.

State which you chose per resource and why.

ACCEPTANCE CRITERIA
- `php artisan route:list` exposes no route without a backing method.
- Feature test asserts each remaining route responds correctly, and that one user cannot
  view or delete another user's bug / test case / DSR.
```

### P-06 — Gate chat channel joins `HIGH`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Stop any authenticated user from joining any chat channel.

CURRENT STATE
app/Http/Controllers/ChatChannelController.php:

    public function join(ChatChannel $channel)
    {
        $channel->members()->syncWithoutDetaching([Auth::id()]);
        return back()->with('success', 'Joined channel.');
    }

No membership, department or project check. Every other method on this controller
correctly gates on membership with
    abort_if(!$channel->members()->where('user_id', Auth::id())->exists(), 403);
— which join() then hands out to anyone who asks. A staff member can enrol into a
department or project channel they have no part in and read the entire backlog.

The chat_channels table has a `type` column (general | department | project) but no
privacy flag, so there is nothing to check against for invite-only channels.

WHAT TO DO
1. Gate join() by channel type:
     - general    → open to all authenticated users (confirm this is intended)
     - department → only users whose department_id matches the channel's department
     - project    → only users assigned to the project (project_user pivot)
2. Add an `is_private` boolean to chat_channels via a new migration. Private channels are
   invite-only: join() must refuse them outright; membership comes from the creator adding
   people in store().
3. Audit index() — ChatChannel::forUser() scope — so private channels a user does not
   belong to are not even listed.

ACCEPTANCE CRITERIA
- Feature test: a user outside a department cannot join that department's channel (403).
- Feature test: a user cannot join a private channel, and it does not appear in their list.
- Existing members are unaffected; no regression in ChatChannelControllerTest if present.
```

### P-07 — Harden agent token handling `HIGH`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Fix three weaknesses in desktop-agent authentication.

CURRENT STATE — app/Http/Middleware/AgentAuthenticate.php

1. Query-string tokens:
       return $request->query('agent_token') ?? null;
   Tokens in URLs land in web-server access logs, proxy logs and error reports, where they
   persist indefinitely.

2. Plaintext storage: users.agent_token holds the raw token
       $user = User::where('agent_token', $token)->first();
   A single database read compromises every agent.

3. Model injected into request input:
       $request->merge(['_agent_user' => $user]);
   An Eloquent model in the input bag surfaces in any $request->all().

A leaked token lets an attacker post fabricated activity and screenshots as that employee.

WHAT TO DO
1. Remove the query-string fallback. Accept the Bearer header only. Update
   desktop-agent/api_client.py if it relies on the query form (it currently sends Bearer).
2. Store a hash of the token; compare with hash_equals against the hashed lookup. Update
   token generation and revocation in Admin/Monitoring/MonitoringSettingController so the
   plaintext is shown to the admin exactly once, at issue time.
3. Replace the merge() with $request->setUserResolver(...) or $request->attributes->set(...),
   and update the $request->agentUser() macro and its call sites in Api/AgentController.

ACCEPTANCE CRITERIA
- A request with ?agent_token=... is rejected 401.
- users.agent_token no longer contains a usable plaintext token; a migration handles
  existing rows (state your strategy — rehash or force reissue).
- $request->all() in any agent endpoint contains no `_agent_user` key.
- All six /api/agent/* endpoints still authenticate with a Bearer token; feature test proves it.
```

### P-08 — Turn on real-time broadcasting `HIGH`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Make chat actually real-time.

CURRENT STATE
Everything is wired except the switch:
  - composer.json requires laravel/reverb
  - app/Events/MessageSent.php implements ShouldBroadcastNow
  - resources/js/bootstrap.js configures Echo with broadcaster: 'reverb'
  - .env sets BROADCAST_CONNECTION=log and defines no REVERB_* credentials

routes/channels.php authorises only:
    Broadcast::channel('App.Models.User.{id}', ...)
    Broadcast::channel('chat.{userId}', ...)
Group channels (ChatChannel) have NO broadcast authorisation entry, so they could not go
real-time even with Reverb running.

Users will report this as "messages don't arrive", not "a feature is missing".

WHAT TO DO
1. Configure Reverb: BROADCAST_CONNECTION=reverb plus REVERB_APP_ID / REVERB_APP_KEY /
   REVERB_APP_SECRET / REVERB_HOST / REVERB_PORT / REVERB_SCHEME. Add all of them to
   .env.example — never commit real credentials.
2. Add a private broadcast channel authorisation for chat channels, authorising on
   ChatChannel membership (the same check ChatChannelController already uses).
3. Broadcast an event when a channel message is sent (ChatChannelController::sendMessage),
   mirroring MessageSent.
4. Subscribe on the client in the channel and DM views; append incoming messages without
   a page reload.
5. Document how to run `php artisan reverb:start` in README or docs/.

ACCEPTANCE CRITERIA
- A DM appears in the recipient's open window with no refresh.
- A channel message appears for every member, and for no non-member.
- Broadcast authorisation refuses a non-member subscribing to a channel.
- .env.example lists every REVERB_* key with placeholder values.
```

### P-09 — Fix settlement worked-days calculation `HIGH`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Stop paying the calendar day-of-month as days worked.

CURRENT STATE
app/Services/Payroll/SettlementService.php, calculateFullAndFinal():

    $pendingSalaryDays   = (int) now()->parse($lastWorkingDate)->day;
    $pendingSalaryAmount = $this->calculationService->multiplyAmount($dailyRate, (string) $pendingSalaryDays);

$dailyRate comes from calculateDailyRate($baseSalary, standard_work_days ?? 26).

So the calendar day number of the last working date is multiplied by a 26-day-month daily
rate. An employee leaving on the 31st is paid 31 days at a 26-day rate — roughly 119% of a
month's salary. One leaving on the 3rd is paid three days regardless of whether they joined
mid-month or took unpaid leave.

WHAT TO DO
Derive actual payable days for the final month the way the regular payroll run already
does — from attendance and the payroll calendar, net of loss-of-pay and unpaid leave.
Reuse PayrollCalculationService rather than reimplementing day counting.
Read app/Services/Payroll/PayrollCalculationService.php first and follow its input
aggregation approach.

ACCEPTANCE CRITERIA
- Unit test: employee leaving on the 31st with full attendance is paid one full month,
  not 119%.
- Unit test: employee with N days of unpaid leave in the final month is paid accordingly.
- Unit test: employee who joined mid-month and left the same month is paid only the days
  between joining and leaving.
- Arithmetic stays in BCMath.
```

### P-10 — Implement notice-period recovery `HIGH`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Calculate notice-period shortfall instead of hardcoding zero.

CURRENT STATE
app/Services/Payroll/SettlementService.php, generateSettlementSlip(), writes literals:
    'notice_shortfall_days'      => 0,
    'notice_shortfall_deduction' => '0.00',
    'bonus'                      => '0.00',
    'other_earnings'             => [],
    'other_deductions'           => [],

docs/payroll-management-design.md §5 specifies notice recovery as a required settlement
component. The columns exist and always read zero, so the gap is invisible on the slip —
employees who leave without serving notice are never charged.

WHAT TO DO
1. Determine the contractual notice period. Check HR\EmployeeProfile and the resignation /
   termination request models for an existing field; if none exists, add one (profile-level
   with a configurable org default) via a new migration.
2. Compute shortfall days = required notice − actual notice served
   (resignation submitted_at → last_working_date). Floor at zero.
3. Compute the deduction using the same daily rate basis as P-09 and BCMath.
4. Surface both figures on the settlement slip view so an approver can see them.
5. Decide whether an approver may waive the recovery, and if so record who waived it.

ACCEPTANCE CRITERIA
- Unit test: an employee serving full notice has zero shortfall.
- Unit test: an employee serving half the required notice is deducted the balance.
- Unit test: an employee serving more than required is not credited a negative deduction.
- The settlement slip renders both the days and the amount.

DEPENDS ON: P-02 (settlement must run at all), P-09 (shared daily-rate basis).
```

### P-11 — Fix gratuity limits and leave encashability `HIGH`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Correct two statutory errors in exit payouts.

CURRENT STATE — app/Services/Payroll/SettlementService.php

1. calculateGratuity() applies base × (15/26) × years with:
     - no statutory ceiling
     - $yearsOfService = (int) floor($profile?->years_of_service ?? 0)
   Indian practice rounds a part-year of six months or more upward, so a resignation at
   9 years 11 months is paid as 9. It also applies no upper cap.

2. Encashment sums every leave type where is_paid = true, because leave_types has no
   is_encashable column. Sick leave and casual leave are encashed alongside earned leave.

Net effect: gratuity under-pays long-tenure leavers while encashment over-pays everyone.

WHAT TO DO
1. Add `is_encashable` (boolean, default false) to leave_types via a new migration; expose
   it in the leave-type admin form (Admin/Leave/LeaveTypeController and its views); filter
   the encashment query on it.
2. Make the gratuity ceiling and the year-rounding rule configurable rather than hardcoded
   — these are country-specific and the app already models country via tax_regimes.
   Put the values in config/ or a settings row, not in the service body.
3. Apply ≥6-month upward rounding to years of service under the configured rule.

ACCEPTANCE CRITERIA
- Unit test: 9 years 7 months rounds to 10; 9 years 2 months stays 9.
- Unit test: gratuity above the configured ceiling is capped at it.
- Unit test: a paid-but-not-encashable leave type is excluded from encashment.
- Existing leave types default to is_encashable = false; the migration does not silently
  make everything encashable.

DEPENDS ON: P-02.
```

### P-12 — Add monitoring data retention `MEDIUM`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Add a retention policy for employee monitoring data.

CURRENT STATE
A codebase-wide search for retention / purge / cleanup logic touching
monitoring_screenshots, monitoring_activity_logs or monitoring_idle_periods returns
nothing. Screenshots accumulate at agent capture cadence, indefinitely.

Two problems: unbounded disk growth, and a data-protection exposure in any jurisdiction
with a storage-limitation rule.

WHAT TO DO
1. Add a retention window setting (days) to monitoring_settings — the table and its admin
   UI already exist in Admin/Monitoring/MonitoringSettingController.
2. Write an Artisan command (follow app/Console/Commands/MonitoringCheckIdle.php as the
   local pattern) that deletes expired rows AND their underlying files from disk.
3. Schedule it in routes/console.php with ->withoutOverlapping(), matching the seven jobs
   already registered there.
4. Chunk the deletion — this table will be large — and log a summary of what was removed.
5. Consider whether flagged or escalated screenshots should survive the purge for an
   investigation window. State your decision.

ACCEPTANCE CRITERIA
- Feature test: records and files older than the window are removed; newer ones survive.
- The command is idempotent and safe to run twice.
- Deleting a row never leaves an orphaned file, and vice versa.
- The retention window is visible and editable in the monitoring settings UI.

RELATED: run after P-03, since that changes where the files live.
```

### P-13 — Generate payslips as PDF `MEDIUM`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Make payslip downloads real PDFs.

CURRENT STATE
app/Http/Controllers/Payroll/PayrollSlipController::downloadSlip() renders a Blade view
and streams it as {slip_number}.html with Content-Type: text/html.

composer.json requires only laravel/framework, laravel/reverb and laravel/tinker — there
is no PDF library in the project. Payslips therefore cannot be filed, signed, or submitted
anywhere that expects a PDF.

docs/payroll-management-design.md already anticipates a GeneratePayrollSlipPdfJob.

WHAT TO DO
1. Add a PDF renderer (state which and why in your summary).
2. Render resources/views/payroll/slips/download.blade.php to PDF. Check it prints
   cleanly — page breaks, table widths, no clipped columns.
3. Generate asynchronously via a queued job for payroll runs (the queue is on `database`
   and already in use), and synchronously for a single on-demand download.
4. Keep the existing authorization check intact:
     auth()->id() === $payrollSlip->user_id || auth()->user()?->role === 'admin'
5. Store generated PDFs on a PRIVATE disk — not `public`. See P-03 for why.

ACCEPTANCE CRITERIA
- Downloading a payslip yields a valid PDF with a .pdf extension and correct MIME type.
- A staff user cannot download another employee's payslip (existing test or new one).
- Bulk generation for a payroll run completes via the queue without timing out.
- The settlement slip is handled the same way, or the gap is explicitly noted as follow-up.
```

### P-14 — Remove mass assignment from five update paths `MEDIUM`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Replace $request->all() with validated input.

CURRENT STATE — unvalidated request data flows straight into create()/update():
  app/Http/Controllers/Staff/TestCaseController.php    — $testCase->update($request->all())
  app/Http/Controllers/Admin/TaskController.php        — $task->update($request->all())
  app/Http/Controllers/Admin/AttendanceController.php  — $attendance->update($request->all())
  app/Http/Controllers/Admin/DepartmentController.php  — create($request->all()) and update($request->all())

TestCaseController is the one that matters: it is staff-reachable, and its $fillable
includes created_by and project_id, so a crafted request can reassign ownership or move a
test case into another project. The admin ones are lower risk but the same defect.

WHAT TO DO
For each: add a $request->validate([...]) call with explicit rules and pass the validated
array to update()/create(). Mirror the validation style already used in the store() method
of the same controller — several of them already validate on create but not on update.

Never allow the client to set an ownership or scoping column (created_by, user_id,
project_id where it implies a permission change). Set those server-side.

ACCEPTANCE CRITERIA
- No $request->all() remains in any create() or update() call in app/Http/Controllers.
  Verify with: grep -rn "all()" app/Http/Controllers --include=*.php
- Feature test: posting created_by/project_id to the test-case update endpoint does not
  change the record's owner.
- Existing happy-path behaviour is unchanged for all five endpoints.
```

### P-15 — Fix the settlement audit-trail fallback `MEDIUM`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Stop attributing settlements to an arbitrary user.

CURRENT STATE
app/Services/Payroll/SettlementService.php, generateSettlementSlip():
    'calculated_by' => Auth::id() ?? User::query()->value('id'),

Run from a queue worker, console command or scheduled job there is no authenticated user,
so the settlement is attributed to whichever row the database returns first. A financial
record silently names the wrong person as its author.

WHAT TO DO
1. Make the acting user an explicit constructor or method argument on the service.
2. Remove the User::query()->value('id') fallback entirely. If no actor is supplied, throw.
3. Update both call sites in Admin/Payroll/SettlementController to pass the authenticated
   user.
4. Audit the rest of app/Services and app/Jobs for the same pattern and report anything
   you find (do not necessarily fix it in this change).

ACCEPTANCE CRITERIA
- Unit test: calling the service without an actor throws rather than guessing.
- Feature test: a settlement generated through the controller records the acting admin.
- grep for `?? User::query()` returns nothing in app/.
```

### P-16 — Delete duplicate models `MEDIUM`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Remove two dead model classes.

CURRENT STATE
  app/Models/LeaveBalance.php   duplicates  app/Models/Leave/LeaveBalance.php
  app/Models/LeaveType.php      duplicates  app/Models/Leave/LeaveType.php

A codebase-wide search finds no reference to either root-namespace class. The root
LeaveBalance additionally lists `available_balance` in $fillable, which is a stored
generated column and cannot be written — so if anything ever did use it, it would fail.

This is dead code that invites an import of the wrong class.

WHAT TO DO
1. Confirm zero references before deleting:
     grep -rn "Models\\\\LeaveBalance\b" app/ database/ tests/ resources/
     grep -rn "Models\\\\LeaveType\b"    app/ database/ tests/ resources/
   (watch for unqualified `use App\Models\LeaveType;` imports too)
2. Delete both files.
3. Run the full suite.

ACCEPTANCE CRITERIA
- Both files removed; `php artisan test` still passes at the P-01 baseline.
- No new import of App\Models\Leave\* was needed anywhere, confirming they were dead.
```

### P-17 — Move the unrelated project out of the repo `MEDIUM`

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Extract disk-forensics-app/ from the Staffee repository.

CURRENT STATE
disk-forensics-app/ contains a complete FastAPI backend (upload, hashing, timeline and
recovery routers, disk_analyzer and hash services) and a React + Vite frontend, with its
own SETUP.md. It shares no code, dependency or route with Staffee.

It inflates clones, confuses dependency scanning, and misleads anyone reading the tree.

WHAT TO DO
1. Confirm nothing in Staffee references it:
     grep -rn "disk-forensics" --include=* . --exclude-dir=disk-forensics-app --exclude-dir=node_modules --exclude-dir=vendor --exclude-dir=.git
2. Preserve its history if it has any of its own — prefer `git subtree split` over a
   plain copy, so the extracted repo keeps its commits.
3. ASK THE USER BEFORE REMOVING IT. Deleting a directory of someone else's work is not a
   call to make unilaterally. Propose the extraction, show what you would run, and wait.

ACCEPTANCE CRITERIA
- A concrete extraction plan presented to the user, with the exact commands.
- Nothing deleted until they confirm.
```

---

## 4. Pending-work prompts — designed but never built

`docs/payroll-management-design.md` is the only design document in the repo and specifies
considerably more than exists. Phases 1 and 2 landed; **phase 3 did not**.

### P-20 — Payroll phase 3: loans, benefits, declarations, delivery

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Build payroll phase 3 as specified in docs/payroll-management-design.md.

READ FIRST: docs/payroll-management-design.md, sections §1.4, §3, §4 and §7. The schema,
the calculation sequence and the reporting requirements are already written there. Follow
that document — do not redesign it. Where it is ambiguous, ask rather than guess.

CONFIRMED MISSING (verified against migrations and app/Services/Payroll/)

  Schema — designed in §1.4, never migrated:
    employee_loans, employee_loan_installments
    employee_benefit_deductions
    employee_tax_declarations
    payroll_documents

  Services — named in the design, absent from app/Services/Payroll/:
    TaxComputationService        (tax_regimes and tax_brackets are CRUD-only today —
                                  there is no engine consuming them)
    Statutory\PfService          (statutory_deduction_rules exists but holds no logic)
    Statutory\EsiService
    SalaryStructureService       (logic currently inline in controllers)
    SalaryRevisionWorkflowService
    PayrollSlipService

  Jobs — named in the design, absent from app/Jobs/:
    GeneratePayrollSlipPdfJob    (see P-13)
    EmailPayrollSlipJob
    RecomputeTaxProjectionJob

  Reporting — §4 "Quarterly and annual reporting" has no controller, route or view:
    Form 16 / 24Q statutory output

SEQUENCE — build in this order; each depends on the one before:
  1. Loans + installments      (schema, CRUD, recovery hook into the payroll run)
  2. Benefit deductions        (schema, CRUD, deduction hook)
  3. Tax declarations          (schema, employee self-service capture, proof upload)
  4. PF / ESI / PT engines     (consume statutory_deduction_rules)
  5. TaxComputationService     (consume tax_regimes + tax_brackets + declarations)
  6. Statutory reporting       (Form 16 / 24Q)
  7. payroll_documents + digital delivery (depends on P-13 for PDF)

NON-NEGOTIABLE CONSTRAINTS
- BCMath throughout, decimal(18,6). Never floats, never round().
- Deductions must respect the component dependency ordering already modelled in
  payroll_component_dependencies.
- Never mutate a published payroll run — the design mandates supplementary runs for retro
  changes (§ "Key decisions", item 5).
- Freeze inputs via payroll_input_snapshots before approval, as the existing run flow does.
- Every calculation path gets a unit test with worked examples. Payroll has zero test
  coverage today; do not extend that.

DELIVER ONE NUMBERED STEP PER PR. Do not attempt all seven in one change.
```

### P-21 — Test coverage for existing business logic

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Add test coverage for the domains that have none.

CURRENT STATE (after P-01 lands — this prompt is blocked until then)
tests/ contains only Laravel auth scaffolding tests, plus HasPermissionsTest and
PermissionEnforcementTest. There is no coverage at all for:
  - Payroll calculation and processing (app/Services/Payroll/, 887 lines)
  - Leave accrual, balance and approval (app/Services/Leave/, 4 services)
  - Shift assignment and attendance validation (app/Services/Shift/)
  - Full & final settlement (app/Services/Payroll/SettlementService.php)
  - Monitoring ingestion (app/Http/Controllers/Api/AgentController.php)
  - Time tracking, utilisation and revenue (app/Services/Time/, 4 services)

Every defect in §3 of this document would have been caught by tests that cannot run today.

PRIORITY ORDER — highest financial/legal risk first:
  1. PayrollCalculationService — worked examples for earnings, deductions, net, day-rates
  2. SettlementService — after P-02/P-09/P-10/P-11 land, lock the corrected behaviour in
  3. LeaveAccrualService + LeaveBalanceService — accrual, carry-forward, expiry
  4. AttendanceValidationService — late, half-day, absent detection against shifts
  5. AgentController — auth, session lifecycle, payload validation
  6. Time services — utilisation and revenue arithmetic

WHAT TO DO
- Unit tests for services; feature tests for HTTP surfaces.
- Use factories; add any that are missing under database/factories/.
- Test the boundaries, not the happy path alone: zero balances, mid-month joiners,
  leavers, negative adjustments, missing salary structures, empty payroll runs.
- Do not chase a coverage percentage. Cover the paths where being wrong costs money.

ACCEPTANCE CRITERIA
- Each domain above has a test file with meaningful assertions.
- `php artisan test` is green.
- Report which behaviours you found to be wrong while writing the tests — expect some.
```

---

## 5. New module prompts

Staffee covers hire-to-exit administration thoroughly. It has no answer for performance,
cost, hiring or self-service at scale. Each module below leans on tables that already exist.

### P-30 — Module scaffold template

Substitute the bracketed fields and use for any module in the table that follows.

```text
[PASTE §1 SHARED CONTEXT HERE]

TASK: Build the [MODULE NAME] module for Staffee.

WHY THIS MODULE
[RATIONALE — the specific gap it closes, from the table below]

BUILD ON WHAT EXISTS
[EXISTING TABLES / SERVICES / WORKFLOWS TO REUSE]

SCOPE
  Schema      — [TABLES], as migrations. Follow the naming and column conventions of the
                existing HR and payroll migrations. Money is decimal(18,6).
  Models      — under app/Models/[Domain]/, with relationships and casts.
  Service     — app/Services/[Domain]/[Name]Service.php holds the domain logic.
                Controllers stay thin.
  Controllers — admin surface under app/Http/Controllers/Admin/[Domain]/,
                staff self-service under app/Http/Controllers/Staff/.
  Routes      — admin routes inside the existing admin group; staff routes in the
                authenticated group. Follow the existing prefix/name nesting.
  Views       — Blade + Bootstrap 5 under resources/views/, matching the structure and
                visual language of the existing admin screens. Read three neighbouring
                views before writing the first one.
  Navigation  — add entries to resources/views/layouts/partials/sidebar.blade.php.
  Permissions — if P-04 chose Option A, define permission slugs and gate the routes.
                If Option B, gate on role.
  Tests       — feature tests for every route; unit tests for the service logic.
  Seeder      — realistic demo data, following database/seeders/HRSeeder.php.

APPROVAL WORKFLOWS
If this module needs one, copy the shape already used by HR — see
app/Http/Controllers/Admin/HR/PromotionController.php and the resignation two-stage
manager → HR chain. Do not invent a third approval pattern.

ACCEPTANCE CRITERIA
- Every route is reachable, authorised, and covered by a test.
- No route is exposed without a backing controller method (the P-05 defect).
- Ownership is enforced on every staff-facing record access.
- The module appears in the sidebar only for users who can use it.
- `php artisan test` is green.

BEFORE YOU START: confirm the scope above with the user. These are proposals from an
audit, not signed-off requirements.
```

### Module backlog

| ID | Module | Rationale | Build on |
|---|---|---|---|
| P-31 | **Performance & Reviews** | Promotion requests ask approvers to judge merit with no performance record to consult; review outcome is the natural trigger for a salary revision | `promotion_requests`, `salary_revision_requests`, HR approval chain |
| P-32 | **Recruitment & Onboarding** | Completes the lifecycle — a hired candidate should convert straight into an employee profile | `employee_profiles`, `exit_checklists` (mirror it for onboarding) |
| P-33 | **Expenses & Reimbursement** | Claims settle through payroll; the approval chain and adjustment tables need almost no new machinery | `payroll_adjustments`, HR approval pattern |
| P-34 | **Client & Invoicing** | Billable rates, time approval and revenue reports all exist — tracked hours stop at a report instead of becoming an invoice | `billable_rates`, `time_trackers`, `projects`, `RevenueService` |
| P-35 | **Assets & Equipment** | The termination checklist asks for asset return but has no inventory to reconcile against | `exit_checklist_items`, `users` |
| P-36 | **Learning & Certification** | Profiles already store skills and certifications with no way to develop or renew them | `employee_skills`, `employee_certifications` |
| P-37 | **Employee Self-Service Portal** | Document requests, address/bank changes, employment letters and helpdesk tickets currently arrive as ad-hoc messages | `emails`, `notifications`, `employee_documents` |
| P-38 | **Workforce Analytics** | Fifteen modules of operational data surface only as per-module CSV exports | every module's tables; add a reporting read-layer |

---

## 6. Order & definition of done

### Sequence

| Step | Prompts | Why here |
|---|---|---|
| 1 | P-01 | One file. Restores the ability to test anything at all. |
| 2 | P-03, P-07 | Live data exposure; independent of everything else. |
| 3 | P-02, P-09, P-10, P-11 | Money is wrong on every exit — and step 1 now lets you prove the fix. |
| 4 | P-04 | Wire it up or delete it, but the PM role blocks any non-admin rollout until settled. |
| 5 | P-05, P-06, P-14 | Small, contained authorization and routing corrections. |
| 6 | P-08, P-13 | The two gaps users report as "broken" rather than "missing". |
| 7 | P-12, P-15, P-16, P-17 | Compliance and housekeeping; safe to batch. |
| 8 | P-20, P-21, then §5 | Only worth starting once the payroll foundation is trustworthy. |

### Definition of done — applies to every prompt

- [ ] `php artisan test` passes, and the pass count is stated in the summary
- [ ] The change is covered by at least one new test
- [ ] No new route is exposed without a backing method and an authorization check
- [ ] No `$request->all()` reaches a `create()` or `update()`
- [ ] Money arithmetic is BCMath, never float
- [ ] Files touched are listed in the summary, with anything deliberately left alone
- [ ] Any new config key is added to `.env.example` with a placeholder — never a real value
- [ ] If the work revealed a further defect, it is reported rather than silently fixed
