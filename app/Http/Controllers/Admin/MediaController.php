<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MediaController extends Controller
{
    /** Full library page */
    public function index(Request $request)
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');

        return Inertia::render('Admin/Media/Index', [
            'media'   => $this->query($request)->paginate(40)->withQueryString(),
            'filters' => array_merge(['search' => $request->get('search', '')], $dr->filters()),
        ]);
    }

    /** JSON list for the picker modal */
    public function list(Request $request)
    {
        return response()->json(
            $this->query($request)->paginate(40)
        );
    }

    private function query(Request $request)
    {
        $query = \App\Support\DateRange::fromRequest($request, 'all')->applyTo(MediaFile::latest());
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /** Upload one or more images into the library */
    public function store(Request $request)
    {
        $request->validate([
            'files'   => 'required|array',
            'files.*' => 'image|mimes:jpg,jpeg,png,webp,svg|max:5120',
        ]);

        $created = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('media', 'public');
            $full = Storage::disk('public')->path($path);

            $dimensions = @getimagesize($full);

            $media = MediaFile::create([
                'file_name'     => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'file_path'     => $path,
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
                'width'         => $dimensions[0] ?? null,
                'height'        => $dimensions[1] ?? null,
                'uploaded_by'   => $request->user()?->id,
            ]);

            $created[] = $media;
        }

        return back()->with('success', count($created) . ' image' . (count($created) !== 1 ? 's' : '') . ' uploaded.');
    }

    /** Upload via the picker — returns JSON of the created media */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->store('media', 'public');
        $full = Storage::disk('public')->path($path);
        $dimensions = @getimagesize($full);

        $media = MediaFile::create([
            'file_name'     => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'file_path'     => $path,
            'mime_type'     => $file->getClientMimeType(),
            'file_size'     => $file->getSize(),
            'width'         => $dimensions[0] ?? null,
            'height'        => $dimensions[1] ?? null,
            'uploaded_by'   => $request->user()?->id,
        ]);

        return response()->json($media);
    }

    public function destroy(MediaFile $media)
    {
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return back()->with('success', 'Image deleted.');
    }

    /** Check whether a media file is referenced anywhere */
    public function usage(MediaFile $media)
    {
        $path = $media->file_path;
        $used = [];

        if (Product::where('featured_image', $path)->exists()) {
            $used[] = 'a product main image';
        }
        if (ProductImage::where('image', $path)->exists()) {
            $used[] = 'a product gallery';
        }
        // Settings that store image paths (logo / favicon / banners)
        $settingHit = WebsiteSetting::where('value', $path)
            ->orWhere('value', $media->file_url)
            ->exists();
        if ($settingHit) {
            $used[] = 'website settings';
        }

        return response()->json([
            'in_use' => count($used) > 0,
            'where'  => $used,
        ]);
    }
}
