<?php

namespace App\Http\Controllers;

use App\Models\ArticleOption;
use Illuminate\Http\Request;

class ArticleOptionController extends Controller
{
    public function index(Request $request)
    {
        $options = ArticleOption::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();

        $grouped = [
            'search_intent' => [],
            'format' => [],
            'word_count' => [],
            'tone' => [],
            'target_audience' => [],
            'language' => [],
        ];

        foreach ($options as $opt) {
            $cat = $opt->category;
            if (! isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $opt;
        }

        return response()->json([
            'options' => $options,
            'grouped' => $grouped,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|in:search_intent,format,word_count,tone,target_audience,language',
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $option = ArticleOption::create([
            'category' => $request->category,
            'label' => $request->label,
            'value' => $request->value,
            'description' => $request->description,
            'is_default' => $request->is_default ?? false,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return response()->json([
            'message' => 'Article option created successfully.',
            'option' => $option,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $option = ArticleOption::findOrFail($id);

        $request->validate([
            'category' => 'sometimes|required|in:search_intent,format,word_count,tone,target_audience,language',
            'label' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $option->update($request->only([
            'category',
            'label',
            'value',
            'description',
            'is_default',
            'sort_order',
        ]));

        return response()->json([
            'message' => 'Article option updated successfully.',
            'option' => $option,
        ]);
    }

    public function destroy($id)
    {
        $option = ArticleOption::findOrFail($id);
        $option->delete();

        return response()->json([
            'message' => 'Article option deleted successfully.',
        ]);
    }
}
