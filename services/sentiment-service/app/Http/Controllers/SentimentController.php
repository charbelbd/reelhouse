<?php

namespace App\Http\Controllers;

use App\Services\SentimentAnalyzer;
use Illuminate\Http\Request;

class SentimentController extends Controller
{
    public function __construct(private SentimentAnalyzer $analyzer) {}

    public function analyze(Request $request)
    {
        $data = $request->validate(['text' => 'required|string|max:5000']);

        return $this->analyzer->analyze($data['text']);
    }

    public function batch(Request $request)
    {
        $data = $request->validate([
            'texts' => 'required|array|max:100',
            'texts.*' => 'required|string|max:5000',
        ]);

        return collect($data['texts'])->map(fn ($t) => $this->analyzer->analyze($t));
    }
}
