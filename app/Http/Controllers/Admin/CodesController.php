<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CodesController extends Controller
{
    public function show(): JsonResponse
    {
        $path = $this->path();
        abort_unless(File::isFile($path), 404);
        $data = json_decode(File::get($path), true);
        abort_unless(is_array($data), 404);

        return response()->json(['codes' => $data]);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'codes' => ['required', 'array'],
        ]);
        $json = json_encode($payload['codes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        abort_unless($json !== false, 422);
        File::put($this->path(), $json."\n");

        return response()->json(['ok' => true]);
    }

    private function path(): string
    {
        return resource_path('seo/sab/codes.json');
    }
}
