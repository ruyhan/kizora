<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return Inertia::render('Admin/Content/Editor', [
            'group'  => $group,
            'label'  => data_get($this->content->schema(), "{$group}.label", $group),
            'fields' => $this->content->fields($group),
            'values' => $this->content->get($group),
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
        }

        $this->content->save($group, $data);

        $label = data_get($this->content->schema(), "{$group}.label", 'Content');

        return back()->with('success', "{$label} saved.");
    }
}
