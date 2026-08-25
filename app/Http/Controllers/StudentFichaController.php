<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class StudentFichaController extends Controller
{
    public function __invoke(Student $student): Response
    {
        $student->load('responsible');

        return Pdf::loadView('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => now(),
        ])
            ->setPaper('a4')
            ->download('ficha-'.Str::slug($student->name).'.pdf');
    }
}
