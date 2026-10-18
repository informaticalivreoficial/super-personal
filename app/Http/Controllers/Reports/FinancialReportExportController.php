<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportação do relatório financeiro do treinador em CSV.
 *
 * Fica fora do Livewire (download é um GET simples) e reaproveita os mesmos
 * filtros/serviço da tela — o isolamento por tenant vem do global scope.
 */
class FinancialReportExportController extends Controller
{
    public function __invoke(Request $request, FinancialReportService $service): StreamedResponse
    {
        $filters = $service->filters($request->only([
            'from', 'to', 'student_id', 'channel', 'status',
        ]));

        $filename = sprintf(
            'relatorio-financeiro-%s-a-%s.csv',
            $filters['from'],
            $filters['to'],
        );

        return response()->streamDownload(function () use ($service, $filters) {
            $handle = fopen('php://output', 'w');

            // BOM: o Excel reconhece o UTF-8 (acentos) sem pedir importação.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($service->csvRows($filters) as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
