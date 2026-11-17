<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\People\CelebrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Staff directory and organisation chart, open to every signed-in employee.
 */
class PeopleController extends Controller
{
    public function __construct(private readonly CelebrationService $celebrations) {}

    public function directory(Request $request)
    {
        $filters = $request->validate([
            'q'             => 'nullable|string|max:100',
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);

        $people = $this->activePeople()
            ->with(['department:id,name', 'manager:id,name'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)
                    ->orWhere('designation', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', $like)));
            })
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->where('department_id', $d))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('people.directory', [
            'people'       => $people,
            'filters'      => $filters,
            'departments'  => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'celebrations' => $this->celebrations->upcoming(now(), 30),
            // Phone numbers are often personal mobiles, so only admins see them.
            'showPhone'    => $request->user()->isAdmin(),
        ]);
    }

    public function orgChart(Request $request)
    {
        $data = $request->validate(['root' => 'nullable|integer|exists:users,id']);

        $people = $this->activePeople()
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'designation', 'department_id', 'reporting_to', 'avatar']);

        $byId       = $people->keyBy('id');
        $byManager  = $people->groupBy(fn ($p) => $byId->has($p->reporting_to) ? $p->reporting_to : 0);

        if (isset($data['root']) && $byId->has($data['root'])) {
            $roots = collect([$byId[$data['root']]]);
            $focus = $byId[$data['root']];
        } else {
            // Top of the chart: anyone whose manager is not an active employee.
            $roots = $byManager->get(0, collect());
            $focus = null;
        }

        // Count each person's whole team once, guarding against reporting loops.
        $teamSize = [];
        $count = function (int $id, array $path = []) use (&$count, &$teamSize, $byManager): int {
            if (isset($teamSize[$id])) return $teamSize[$id];
            if (in_array($id, $path, true)) return 0;
            $total = 0;
            foreach ($byManager->get($id, collect()) as $report) {
                $total += 1 + $count($report->id, [...$path, $id]);
            }
            return $teamSize[$id] = $total;
        };
        $people->each(fn ($p) => $count($p->id));

        // Chain of managers above the focused person, for the breadcrumb.
        $chain = collect();
        if ($focus) {
            $seen = [];
            for ($m = $byId->get($focus->reporting_to); $m && !isset($seen[$m->id]); $m = $byId->get($m->reporting_to)) {
                $seen[$m->id] = true;
                $chain->prepend($m);
            }
        }

        return view('people.org-chart', [
            'roots'     => $roots,
            'byManager' => $byManager,
            'teamSize'  => $teamSize,
            'focus'     => $focus,
            'chain'     => $chain,
            'total'     => $people->count(),
        ]);
    }

    private function activePeople()
    {
        return User::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('employment_status')->orWhereNotIn('employment_status', ['terminated', 'resigned']));
    }
}
