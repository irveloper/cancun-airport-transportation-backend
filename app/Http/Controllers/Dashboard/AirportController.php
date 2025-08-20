<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\City;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AirportController extends Controller
{
    public function index(): Response
    {
        $airports = Airport::with('city')
            ->orderBy('name')
            ->get();

        return Inertia::render('dashboard/airports/Index', [
            'airports' => $airports
        ]);
    }

    public function create(): Response
    {
        $cities = City::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('dashboard/airports/Create', [
            'cities' => $cities
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:airports,code',
            'city_id' => 'required|exists:cities,id'
        ]);

        Airport::create($validated);

        return redirect()->route('dashboard.airports.index')
            ->with('success', 'Airport created successfully.');
    }

    public function show(Airport $airport): Response
    {
        $airport->load('city');

        return Inertia::render('dashboard/airports/Show', [
            'airport' => $airport
        ]);
    }

    public function edit(Airport $airport): Response
    {
        $cities = City::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('dashboard/airports/Edit', [
            'airport' => $airport,
            'cities' => $cities
        ]);
    }

    public function update(Request $request, Airport $airport): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:airports,code,' . $airport->id,
            'city_id' => 'required|exists:cities,id'
        ]);

        $airport->update($validated);

        return redirect()->route('dashboard.airports.index')
            ->with('success', 'Airport updated successfully.');
    }

    public function destroy(Airport $airport): RedirectResponse
    {
        $airport->delete();

        return redirect()->route('dashboard.airports.index')
            ->with('success', 'Airport deleted successfully.');
    }
}
