<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentProfilePdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Ficha do aluno em PDF (download direto).
 *
 * Fica fora do Livewire (download é um GET simples) e reusa o
 * `StudentProfilePdfService`. O isolamento por tenant é duplo: o binding do
 * `Student` já passa pelo global scope e a Policy confirma o acesso.
 */
class StudentProfilePdfController extends Controller
{
    public function __invoke(Student $student, StudentProfilePdfService $service): Response
    {
        Gate::authorize('view', $student);

        return $service->download($student);
    }
}
