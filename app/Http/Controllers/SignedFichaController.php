<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SignedFichaController extends Controller
{
    public function __invoke(Student $student): Response
    {
        abort_if(
            ! $student->termo_arquivo || ! Storage::disk('local')->exists($student->termo_arquivo),
            404,
        );

        return Storage::disk('local')->download(
            $student->termo_arquivo,
            $student->termo_arquivo_nome ?? 'ficha-assinada',
        );
    }
}
