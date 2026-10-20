<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Enums\TrainingPlanStatus;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Ficha do aluno em PDF (A4) — leitura pura, sem escrita.
 *
 * A foto/logo são embutidas em base64 (redimensionadas com o Intervention),
 * então o PDF não depende de rede nem do `APP_URL`. O isolamento por tenant
 * vem do global scope do `Student` carregado pela rota + Policy.
 */
class StudentProfilePdfService
{
    /** Rótulos usados pelo formulário do aluno (1 = domingo ... 7 = sábado). */
    private const DAYS = [
        1 => 'Domingo',
        2 => 'Segunda',
        3 => 'Terça',
        4 => 'Quarta',
        5 => 'Quinta',
        6 => 'Sexta',
        7 => 'Sábado',
    ];

    public function download(Student $student): Response
    {
        return $this->make($student)->download($this->filename($student));
    }

    public function make(Student $student): DomPdf
    {
        return Pdf::loadView('pdf.student-profile', $this->data($student))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'enable_font_subsetting' => true,
                'dpi' => 110,
            ]);
    }

    public function filename(Student $student): string
    {
        return 'ficha-aluno-'.(Str::slug((string) $student->name) ?: 'aluno').'.pdf';
    }

    /**
     * @return array<string, mixed>
     */
    public function data(Student $student): array
    {
        $student->loadMissing('teacher');

        $availableDays = collect($student->available_days ?? [])
            ->map(fn ($day) => self::DAYS[(int) $day] ?? null)
            ->filter()
            ->values()
            ->all();

        return [
            'student' => $student,
            'teacher' => $student->teacher,
            'photo' => $this->imageDataUri($student->profile_photo, 460, 'jpeg'),
            'teacherLogo' => $this->imageDataUri($student->teacher?->logo, 300, 'png', false),
            'initials' => $student->initials(),
            'age' => $student->birth_date?->age,
            'genders' => StudentGender::labels(),
            'levels' => StudentLevel::labels(),
            'paymentStatus' => PaymentStatus::labels(),
            'availableDays' => $availableDays,
            'activePlan' => $student->trainingPlans()
                ->where('status', TrainingPlanStatus::ACTIVE->value)
                ->first(),
            'recentPayments' => $student->payments()
                ->orderByDesc('due_date')
                ->limit(6)
                ->get(),
            'latestProgress' => $student->progress()->first(),
            'generatedAt' => now(),
        ];
    }

    /**
     * Lê uma imagem do disco `public` e devolve um data URI já redimensionado
     * (foto quadrada por recorte; logo com a largura preservando proporção).
     */
    private function imageDataUri(?string $path, int $size, string $format, bool $crop = true): ?string
    {
        if (empty($path) || str_starts_with($path, 'http')) {
            return null;
        }

        try {
            $disk = Storage::disk();

            if (! $disk->exists($path)) {
                return null;
            }

            $binary = $disk->get($path);

            if ($binary === null || $binary === '') {
                return null;
            }

            $manager = new ImageManager(new Driver);
            $image = $manager->read($binary);

            $image = $crop ? $image->cover($size, $size) : $image->scale(width: $size);

            if ($format === 'png') {
                return 'data:image/png;base64,'.base64_encode((string) $image->toPng());
            }

            return 'data:image/jpeg;base64,'.base64_encode((string) $image->toJpeg(82));
        } catch (Throwable $e) {
            // Foto/logo corrompida não pode impedir a ficha: cai no fallback.
            report($e);

            return null;
        }
    }
}
