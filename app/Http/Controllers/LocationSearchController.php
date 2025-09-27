<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LocationSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:province,city,barangay',
            'q' => 'nullable|string|min:1',
            'parent_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1|max:25',
        ]);

        $type = $validated['type'];
        $q = trim($validated['q'] ?? '');
        $parentId = $validated['parent_id'] ?? null;
        $limit = $validated['limit'] ?? 10;

        $cacheKey = sprintf('loc-search:%s:%s:%s:%s', $type, $parentId ?? 'null', mb_strtolower($q), $limit);

        $results = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($type, $q, $parentId, $limit) {
            return match ($type) {
                'province' => $this->searchProvinces($q, $limit),
                'city' => $this->searchCities($q, $parentId, $limit),
                'barangay' => $this->searchBarangays($q, $parentId, $limit),
            };
        });

        return response()->json($results);
    }

    protected function searchProvinces(string $q, int $limit): array
    {
        $query = Province::query();
        if ($q !== '') {
            $query->where('name', 'like', $q.'%')
                ->orWhere('name', 'like', '% '.$q.'%');
        }

        return $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn ($row) => ['id' => $row->id, 'text' => $row->name])
            ->all();
    }

    protected function searchCities(string $q, ?int $provinceId, int $limit): array
    {
        $query = City::query();
        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', $q.'%')
                    ->orWhere('name', 'like', '% '.$q.'%');
            });
        }

        return $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn ($row) => ['id' => $row->id, 'text' => $row->name])
            ->all();
    }

    protected function searchBarangays(string $q, ?int $cityId, int $limit): array
    {
        $query = Barangay::query();
        if ($cityId) {
            $query->where('city_id', $cityId);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', $q.'%')
                    ->orWhere('name', 'like', '% '.$q.'%');
            });
        }

        return $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name'])
            ->map(fn ($row) => ['id' => $row->id, 'text' => $row->name])
            ->all();
    }
}
