<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DeliveryCity;
use App\Support\PageContent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentController extends Controller
{
    public function __construct(private readonly PageContent $content) {}

    public function index()
    {
        return Inertia::render('Admin/Content/Index', [
            'groups' => $this->content->groups(),
        ]);
    }

    public function edit(string $group)
    {
        abort_unless($this->content->exists($group), 404);

        $fields = $this->content->fields($group);

        // Provide live category options when the group has a `categories` picker field.
        $categoryOptions = [];
        if (collect($fields)->contains(fn ($f) => ($f['type'] ?? '') === 'categories')) {
            $categoryOptions = Category::whereNull('parent_id')->where('is_active', true)
                ->orderBy('sort_order')->orderBy('name')
                ->get(['name', 'slug'])
                ->map(fn ($c) => ['value' => $c->slug, 'label' => $c->name])
                ->all();
        }

        // Provide a full category dropdown (parents + children) when any repeatable
        // list field carries a single-select `category` sub-field (home page sections).
        $categorySelectOptions = [];
        $hasCategorySub = collect($fields)->contains(fn ($f) => collect($f['item'] ?? [])
            ->contains(fn ($s) => ($s['type'] ?? '') === 'category'));
        if ($hasCategorySub) {
            $categorySelectOptions = Category::where('is_active', true)
                ->with('parent:id,name')
                ->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'slug', 'parent_id'])
                ->map(fn ($c) => [
                    'value' => $c->slug,
                    'label' => $c->parent ? "{$c->parent->name} — {$c->name}" : $c->name,
                ])
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()->all();
        }

        // Provide the delivery cities (with their charges) when the group has a
        // `delivery_charges` field — the embedded manager reads/writes these.
        $deliveryCities = [];
        if (collect($fields)->contains(fn ($f) => ($f['type'] ?? '') === 'delivery_charges')) {
            $deliveryCities = DeliveryCity::orderBy('name')->get(['id', 'name', 'delivery_charge', 'is_active']);
        }

        return Inertia::render('Admin/Content/Editor', [
            'group'           => $group,
            'label'           => data_get($this->content->schema(), "{$group}.label", $group),
            'fields'          => $fields,
            'values'          => $this->content->get($group),
            'categoryOptions' => $categoryOptions,
            'categorySelectOptions' => $categorySelectOptions,
            'deliveryCities'  => $deliveryCities,
        ]);
    }

    public function update(Request $request, string $group)
    {
        abort_unless($this->content->exists($group), 404);

        $data = (array) $request->input('content', []);

        // Coerce number fields so they persist as numbers, not strings.
        foreach ($this->content->fields($group) as $field) {
            $key = $field['key'];
            if (($field['type'] ?? 'text') === 'number' && array_key_exists($key, $data)) {
                $data[$key] = is_numeric($data[$key]) ? 0 + $data[$key] : 0;
            }

            // Coerce number sub-fields inside repeatable list items too.
            if (($field['type'] ?? 'text') === 'list' && is_array($data[$key] ?? null)) {
                foreach ($field['item'] ?? [] as $sub) {
                    if (($sub['type'] ?? 'text') !== 'number') {
                        continue;
                    }
                    foreach ($data[$key] as $i => $item) {
                        if (is_array($item) && array_key_exists($sub['key'], $item)) {
                            $data[$key][$i][$sub['key']] = is_numeric($item[$sub['key']]) ? 0 + $item[$sub['key']] : 0;
                        }
                    }
                }
            }
        }

        $this->content->save($group, $data);

        $label = data_get($this->content->schema(), "{$group}.label", 'Content');

        return back()->with('success', "{$label} saved.");
    }
}
