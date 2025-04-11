<?php
require_once 'auth.php';
require_once 'db.php';
require __DIR__ . './vendor/autoload.php'; // For FPDF

use FPDF as GlobalFPDF;
use setasign\Fpdf\Fpdf;

class PDFGenerator {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function generateWeeklySchedulePDF($startDate) {
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
        
        $scheduleManager = new ScheduleManager($this->db);
        $masses = $scheduleManager->getMassesByDateRange($startDate, $endDate);
        
        $pdf = new GlobalFPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Title
        $pdf->Cell(0, 10, 'Weekly Mass Reader Schedule', 0, 1, 'C');
        $pdf->Cell(0, 10, date('F j, Y', strtotime($startDate)) . ' to ' . date('F j, Y', strtotime($endDate)), 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->SetFont('Arial', '', 12);
        
        // Group masses by date
        $massesByDate = [];
        foreach ($masses as $mass) {
            $massesByDate[$mass['mass_date']][] = $mass;
        }
        
        ksort($massesByDate);
        
        foreach ($massesByDate as $date => $dailyMasses) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, date('l, F j', strtotime($date)), 0, 1);
            $pdf->SetFont('Arial', '', 12);
            
            foreach ($dailyMasses as $mass) {
                $pdf->Cell(40, 7, date('g:i a', strtotime($mass['mass_time'])) . ' - ' . $mass['mass_type'], 0, 0);
                $pdf->Cell(0, 7, $mass['location'], 0, 1);
                
                $assignments = $scheduleManager->getAssignmentsForMass($mass['mass_id']);
                
                if (empty($assignments)) {
                    $pdf->Cell(0, 7, 'No readers assigned', 0, 1);
                    $pdf->Ln(5);
                    continue;
                }
                
                foreach ($assignments as $assignment) {
                    $pdf->Cell(20, 7, '', 0, 0); // Indent
                    $pdf->Cell(40, 7, $assignment['role'], 0, 0);
                    $pdf->Cell(0, 7, $assignment['name'], 0, 1);
                    
                    if (!empty($assignment['scripture_reference'])) {
                        $pdf->Cell(20, 7, '', 0, 0); // Indent
                        $pdf->Cell(0, 7, 'Reading: ' . $assignment['scripture_reference'], 0, 1);
                    }
                }
                
                $pdf->Ln(5);
            }
        }
        
        return $pdf->Output('S', 'weekly_schedule_' . $startDate . '.pdf');
    }
    
    public function generateMonthlySchedulePDF($year, $month) {
        $firstDay = "$year-$month-01";
        $lastDay = date('Y-m-t', strtotime($firstDay));
        
        $scheduleManager = new ScheduleManager($this->db);
        $masses = $scheduleManager->getMassesByDateRange($firstDay, $lastDay);
        
        $pdf = new GlobalFPDF('L'); // Landscape
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Title
        $pdf->Cell(0, 10, 'Monthly Mass Reader Schedule - ' . date('F Y', strtotime($firstDay)), 0, 1, 'C');
        $pdf->Ln(10);
        
        $pdf->SetFont('Arial', '', 10);
        
        // Table header
        $pdf->Cell(30, 10, 'Date', 1, 0, 'C');
        $pdf->Cell(20, 10, 'Time', 1, 0, 'C');
        $pdf->Cell(30, 10, 'Type', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Location', 1, 0, 'C');
        $pdf->Cell(40, 10, 'First Reading', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Second Reading', 1, 0, 'C');
        $pdf->Cell(40, 10, 'Preaching', 1, 1, 'C');
        
        // Group masses by date
        $massesByDate = [];
        foreach ($masses as $mass) {
            $massesByDate[$mass['mass_date']][] = $mass;
        }
        
        ksort($massesByDate);
        
        foreach ($massesByDate as $date => $dailyMasses) {
            $dateFormatted = date('D, M j', strtotime($date));
            
            foreach ($dailyMasses as $mass) {
                $assignments = $scheduleManager->getAssignmentsForMass($mass['mass_id']);
                
                $firstReading = '';
                $secondReading = '';
                $preaching = '';
                
                foreach ($assignments as $assignment) {
                    switch ($assignment['role']) {
                        case 'First Reading':
                            $firstReading = $assignment['name'];
                            break;
                        case 'Second Reading':
                            $secondReading = $assignment['name'];
                            break;
                        case 'Preaching':
                            $preaching = $assignment['name'];
                            break;
                    }
                }
                
                $pdf->Cell(30, 10, $dateFormatted, 1, 0);
                $pdf->Cell(20, 10, date('g:i a', strtotime($mass['mass_time'])), 1, 0);
                $pdf->Cell(30, 10, $mass['mass_type'], 1, 0);
                $pdf->Cell(40, 10, $mass['location'], 1, 0);
                $pdf->Cell(40, 10, $firstReading, 1, 0);
                $pdf->Cell(40, 10, $secondReading, 1, 0);
                $pdf->Cell(40, 10, $preaching, 1, 1);
                
                // Only show date once
                $dateFormatted = '';
            }
        }
        
        return $pdf->Output('S', 'monthly_schedule_' . $year . '_' . $month . '.pdf');
    }
}
?>