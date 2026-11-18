<?php

namespace App\Http\Controllers;

use App\Services\SparqlService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(private readonly SparqlService $sparql) {}

    public function index(): View
    {
        $malls = $this->sparql->query(<<<'SPARQL'
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX schema: <http://schema.org/>
SELECT ?id ?name ?city WHERE {
    ?id a mm:Mall ; schema:name ?name ; mm:inCity ?cityResource .
    ?cityResource schema:name ?city .
}
ORDER BY ?name
SPARQL);

        return view('budget.index', ['malls' => $malls, 'estimate' => null]);
    }

    public function estimate(Request $request): View
    {
        $validated = $request->validate([
            'mall' => ['required', 'string', 'regex:/\Ahttp:\/\/sumateramall\.org\/resource\/Mall\/[a-z0-9_]+\z/'],
            'people' => ['required', 'integer', 'between:1,20'],
            'price_level' => ['nullable', 'integer', 'between:1,4'],
        ]);
        $mallIri = $this->sparql->internalResource($validated['mall'], 'Mall');
        $result = $this->sparql->query(<<<SPARQL
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
SELECT (AVG(xsd:integer(?priceLevel)) AS ?averagePriceLevel) (COUNT(?priceLevel) AS ?pricedStores) WHERE {
    ?store a mm:Store ; mm:locatedIn {$mallIri} ; mm:priceLevel ?priceLevel .
    FILTER(xsd:integer(?priceLevel) >= 1 && xsd:integer(?priceLevel) <= 4)
}
SPARQL);

        $hasPriceData = isset($result[0]['averagePriceLevel']) && (int) ($result[0]['pricedStores'] ?? 0) > 0;
        $priceLevel = $hasPriceData
            ? max(1.0, min(4.0, (float) $result[0]['averagePriceLevel']))
            : (float) ($validated['price_level'] ?? 2);
        $estimates = config('services.budget.price_level_estimates');
        $lowerLevel = (int) floor($priceLevel);
        $upperLevel = (int) ceil($priceLevel);
        $lowerEstimate = (int) ($estimates[$lowerLevel] ?? $estimates[2]);
        $upperEstimate = (int) ($estimates[$upperLevel] ?? $estimates[4]);
        $estimatePerPerson = (int) round(
            $lowerEstimate + ($upperEstimate - $lowerEstimate) * ($priceLevel - $lowerLevel),
        );
        $malls = $this->sparql->query(<<<'SPARQL'
PREFIX mm: <http://sumateramall.org/ontology#>
PREFIX schema: <http://schema.org/>
SELECT ?id ?name ?city WHERE {
    ?id a mm:Mall ; schema:name ?name ; mm:inCity ?cityResource .
    ?cityResource schema:name ?city .
}
ORDER BY ?name
SPARQL);

        return view('budget.index', [
            'malls' => $malls,
            'estimate' => [
                'mall' => $validated['mall'],
                'people' => (int) $validated['people'],
                'priceLevel' => $priceLevel,
                'perPerson' => $estimatePerPerson,
                'total' => $estimatePerPerson * (int) $validated['people'],
                'hasPriceData' => $hasPriceData,
                'pricedStores' => (int) ($result[0]['pricedStores'] ?? 0),
            ],
        ]);
    }
}
