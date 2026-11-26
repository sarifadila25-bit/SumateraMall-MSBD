<?php

namespace App\Http\Controllers;

use App\Services\SparqlService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(private readonly SparqlService $sparql) {}

    public function recommend(Request $request): View
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'regex:/\Ahttp:\/\/sumateramall\.org\/resource\/Category\/[a-z0-9_]+\z/'],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
        ]);
        $categoryFilter = isset($validated['category']) && $validated['category'] !== ''
            ? 'FILTER(?catRes = '.$this->sparql->internalResource($validated['category'], 'Category').')'
            : '';
        $minRating = (float) ($validated['min_rating'] ?? 4.0);
        $categories = $this->sparql->query(<<<'SPARQL'
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX schema: <http://schema.org/>
SELECT DISTINCT ?id ?name WHERE {
    ?id a mm:Category ; schema:name ?name .
}
ORDER BY ?name
SPARQL, 3600);

        $recommendations = $this->sparql->query(<<<SPARQL
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX schema: <http://schema.org/>
SELECT DISTINCT ?store ?storeName ?mallName ?city ?category ?rating ?priceLevel ?status
WHERE {
    ?store a mm:Store ;
           schema:name ?storeName ;
           mm:locatedIn ?mall ;
           mm:locatedIn/mm:inCity ?cityRes ;
           mm:hasCategory ?catRes ;
           mm:rating ?rating .
    ?mall schema:name ?mallName .
    ?cityRes schema:name ?city .
    ?catRes schema:name ?category .
    OPTIONAL { ?store mm:priceLevel ?priceLevel }
    OPTIONAL { ?store mm:businessStatus ?status }
    {$categoryFilter}
    FILTER(?rating >= {$minRating})
}
ORDER BY DESC(?rating) DESC(?priceLevel)
LIMIT 20
SPARQL);

        return view('recommendation.index', compact('recommendations', 'categories'));
    }
}
