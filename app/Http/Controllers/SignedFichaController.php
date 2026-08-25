<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SignedFichaController extends Controller
{
    public function __invoke(Student $student): Response
    {
        $disk = Storage::disk(config('fichas.disk'));

        abort_if(
            ! $student->termo_arquivo || ! $disk->exists($student->termo_arquivo),
            404,
        );

        return $disk->download(
            $student->termo_arquivo,
            $student->termo_arquivo_nome ?? 'ficha-assinada',
        );
    }
}
