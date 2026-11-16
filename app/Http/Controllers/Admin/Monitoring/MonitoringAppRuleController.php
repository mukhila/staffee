<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Monitoring\MonitoringAppRule;
use App\Services\Monitoring\ProductivityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonitoringAppRuleController extends Controller
{
    public function __construct(private readonly ProductivityService $productivity) {}

    public function index()
    {
        $rules = MonitoringAppRule::orderBy('category')->orderBy('pattern')->get()->groupBy('category');

        $uncategorised = $this->productivity->uncategorisedApps(now()->subDays(14), now());

        return view('admin.monitoring.app-rules', compact('rules', 'uncategorised'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pattern'    => 'required|string|min:2|max:255',
            'match_type' => ['required', Rule::in(MonitoringAppRule::MATCH_TYPES)],
            'category'   => ['required', Rule::in(MonitoringAppRule::CATEGORIES)],
        ]);

        $data['pattern'] = trim($data['pattern']);

        // Adding a pattern that already exists simply moves it to the chosen category.
        MonitoringAppRule::updateOrCreate(
            ['match_type' => $data['match_type'], 'pattern' => $data['pattern']],
            ['category' => $data['category']]
        );

        return back()->with('success', "\"{$data['pattern']}\" is now counted as {$data['category']}.");
    }

    public function update(Request $request, MonitoringAppRule $rule)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(MonitoringAppRule::CATEGORIES)],
        ]);

        $rule->update($data);

        return back()->with('success', "\"{$rule->pattern}\" is now counted as {$rule->category}.");
    }

    public function destroy(MonitoringAppRule $rule)
    {
        $rule->delete();

        return back()->with('success', "Rule for \"{$rule->pattern}\" removed.");
    }
}
