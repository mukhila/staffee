<?php

namespace App\Http\Controllers\Admin\Time;

use App\Http\Controllers\Controller;
use App\Models\TimeCategory;
use Illuminate\Http\Request;

class TimeCategoryController extends Controller
{
    public function index()
    {
        $categories = TimeCategory::withCount('timeTrackers')
            ->ordered()
            ->get();

        return view('admin.time.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.time.categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:80|unique:time_categories,name',
            'is_billable'=> 'boolean',
            'color'      => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data['is_billable'] = $request->boolean('is_billable');

        TimeCategory::create($data);
        return redirect()->route('admin.time.categories.index')->with('success', 'Category created.');
    }

    public function edit(TimeCategory $category)
    {
        return view('admin.time.categories.edit', compact('category'));
    }

    public function update(Request $request, TimeCategory $category)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:80|unique:time_categories,name,' . $category->id,
            'is_billable'=> 'boolean',
            'color'      => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'sort_order' => 'required|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_billable'] = $request->boolean('is_billable');
        $data['is_active']   = $request->boolean('is_active', true);

        $category->update($data);
        return redirect()->route('admin.time.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(TimeCategory $category)
    {
        abort_if($category->timeTrackers()->exists(), 422, 'Cannot delete a category with logged time entries.');
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }
}
