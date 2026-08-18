<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::withCount('invoices')->orderBy('name')->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'email'          => 'nullable|email|max:180',
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string',
            'gst_number'     => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:60',
            'currency'       => 'nullable|string|size:3',
            'is_active'      => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        Client::create($validated);

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client created successfully.');
    }

    public function show(Client $client)
    {
        $client->load(['invoices' => fn ($q) => $q->latest()->limit(10)]);

        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:150',
            'email'          => 'nullable|email|max:180',
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string',
            'gst_number'     => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:60',
            'currency'       => 'nullable|string|size:3',
            'is_active'      => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        $client->update($validated);

        return redirect()->route('admin.clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('admin.clients.index')
            ->with('success', 'Client deleted.');
    }
}
