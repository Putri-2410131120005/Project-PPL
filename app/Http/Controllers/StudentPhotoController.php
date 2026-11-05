<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Services\StudentPhotoAccess;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StudentPhotoController extends Controller
{
    public function __invoke(Siswa $siswa, StudentPhotoAccess $access): Response
    {
        abort_unless(config('deployment.protected_student_photos'), 404);
        abort_unless($siswa->foto && $access->canView($siswa), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($siswa->foto), 404);

        $response = response()->file($disk->path($siswa->foto));
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
