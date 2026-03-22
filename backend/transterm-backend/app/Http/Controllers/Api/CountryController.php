<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryCollection;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request): CountryCollection
    {
        $query = Country::query();

        if ($request->filled('id')) {
            $query->where('id', $request->integer('id'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where('name', 'like', "%{$search}%");
        }

        return new CountryCollection(
            $query->orderBy('name')
                ->paginate($request->integer('per_page', 10))
                ->withQueryString()
        );
    }

    public function show(Country $country): CountryResource
    {
        return new CountryResource($country);
    }
}
