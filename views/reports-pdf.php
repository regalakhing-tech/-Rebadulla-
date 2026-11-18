<?php
// Downloadable PDF report: summary tables plus every appointment in the period.
[$start, $end] = report_period();
$data = report_data($start, $end);
$list = rows(
    "SELECT a.appt_date, a.appt_time, a.booking_ref, a.status, p.name AS pet_name, u.full_name AS owner_name, s.name AS service_name
    FROM appointments a JOIN pets p ON p.id = a.pet_id JOIN users u ON u.id = p.owner_id JOIN services s ON s.id = a.service_id
    WHERE a.appt_date BETWEEN ? AND ? ORDER BY a.appt_date, a.appt_time, a.id",
    [$start, $end],
);
$period = schedule_label($start) . " – " . schedule_label($end);
$counts = array_column($data["byStatus"], "total", "status");

$pdf = new class extends FPDF {
    public string $period = "";
    public string $by = "";

    // FPDF's core fonts use Windows-1252, so UTF-8 text is converted first.
    public function t(string $text): string
    {
        return (string) iconv("UTF-8", "windows-1252//TRANSLIT", $text);
    }

    public function Header(): void
    {
        $this->SetFillColor(10, 75, 255);
        $this->Rect(0, 0, 210, 30, "F");
        $this->SetFillColor(255, 255, 255);
        $this->Rect(11, 5, 17, 20, "F");
        $this->Image(ROOT . "/assets/email-logo.png", 12.5, 6, 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont("Helvetica", "B", 16);
        $this->SetXY(31, 8);
        $this->Cell(100, 7, "Rebadulla Animal Care");
        $this->SetFont("Helvetica", "", 9);
        $this->SetXY(31, 15);
        $this->Cell(100, 5, $this->t("CHVH Bldg, Cerdeña St, San Jose, Antique · 0955 029 5738"));
        $this->SetFont("Helvetica", "B", 12);
        $this->SetXY(120, 8);
        $this->Cell(78, 7, "Appointment Report", 0, 0, "R");
        $this->SetFont("Helvetica", "", 9);
        $this->SetXY(120, 15);
        $this->Cell(78, 5, $this->t($this->period), 0, 0, "R");
        $this->SetY(38);
        $this->SetTextColor(11, 21, 51);
    }

    public function Footer(): void
    {
        $this->SetY(-14);
        $this->SetDrawColor(230, 235, 244);
        $this->Line(12, $this->GetY(), 198, $this->GetY());
        $this->SetFont("Helvetica", "", 8);
        $this->SetTextColor(125, 136, 156);
        $this->Cell(120, 8, $this->t($this->by));
        $this->Cell(66, 8, "Page " . $this->PageNo() . " of {nb}", 0, 0, "R");
    }

    public function heading(string $text): void
    {
        if ($this->GetY() > 250) {
            $this->AddPage();
        }
        $this->Ln(4);
        $this->SetFont("Helvetica", "B", 12);
        $this->SetTextColor(11, 21, 51);
        $this->Cell(0, 8, $this->t($text), 0, 1);
    }

    // Table with a blue header row; $widths in mm; numeric columns align right.
    public function table(array $headers, array $widths, array $rows, array $right = []): void
    {
        $head = function () use ($headers, $widths, $right): void {
            $this->SetFont("Helvetica", "B", 8.5);
            $this->SetFillColor(238, 243, 255);
            $this->SetTextColor(10, 75, 255);
            foreach ($headers as $i => $header) {
                $this->Cell($widths[$i], 8, $this->t($header), 0, 0, in_array($i, $right, true) ? "R" : "L", true);
            }
            $this->Ln();
        };
        $head();
        $this->SetFont("Helvetica", "", 8.5);
        $this->SetTextColor(11, 21, 51);
        $this->SetDrawColor(230, 235, 244);
        foreach ($rows as $n => $row) {
            if ($this->GetY() > 270) {
                $this->AddPage();
                $head();
                $this->SetFont("Helvetica", "", 8.5);
                $this->SetTextColor(11, 21, 51);
            }
            $this->SetFillColor(...($n % 2 ? [248, 250, 253] : [255, 255, 255]));
            foreach ($row as $i => $value) {
                $text = $this->t((string) $value);
                while ($this->GetStringWidth($text) > $widths[$i] - 3 && strlen($text) > 3) {
                    $text = substr($text, 0, -4) . "...";
                }
                $this->Cell($widths[$i], 7, $text, "B", 0, in_array($i, $right, true) ? "R" : "L", true);
            }
            $this->Ln();
        }
    }
};
$pdf->period = $period;
$pdf->by = "Generated " . date("M j, Y g:i A") . " by " . $user["full_name"];
$pdf->SetTitle("Rebadulla appointment report " . $start . " to " . $end, true);
$pdf->SetAuthor("Rebadulla Animal Care", true);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AliasNbPages();
$pdf->AddPage();

// Summary boxes.
$boxes = [
    ["Total appointments", $data["total"], [10, 75, 255]],
    ["Completed", $counts["completed"] ?? 0, [11, 138, 85]],
    ["Upcoming", ($counts["pending"] ?? 0) + ($counts["proposed"] ?? 0) + ($counts["confirmed"] ?? 0), [91, 63, 214]],
    ["Missed / cancelled", ($counts["missed"] ?? 0) + ($counts["cancelled"] ?? 0), [207, 53, 53]],
];
$x = 12;
foreach ($boxes as [$label, $value, $color]) {
    $pdf->SetFillColor(...$color);
    $pdf->Rect($x, 38, 44, 22, "F");
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont("Helvetica", "B", 18);
    $pdf->SetXY($x + 4, 41);
    $pdf->Cell(36, 9, (string) $value);
    $pdf->SetFont("Helvetica", "", 8);
    $pdf->SetXY($x + 4, 51);
    $pdf->Cell(36, 5, $pdf->t($label));
    $x += 46.7;
}
$pdf->SetY(64);

if (!$data["total"]) {
    $pdf->heading("No appointments in this period");
} else {
    $pdf->heading("By status");
    $pdf->table(
        ["Status", "Appointments"],
        [140, 46],
        array_map(fn($r) => [ucfirst($r["status"]), $r["total"]], $data["byStatus"]),
        [1],
    );
    $pdf->heading("By service");
    $pdf->table(
        ["Service", "Total", "Completed", "Missed", "Cancelled"],
        [90, 24, 24, 24, 24],
        array_map(fn($r) => [$r["name"], $r["total"], $r["completed"], $r["missed"], $r["cancelled"]], $data["byService"]),
        [1, 2, 3, 4],
    );
    $pdf->heading("By date");
    $pdf->table(
        ["Date", "Appointments"],
        [140, 46],
        array_map(fn($r) => [(new DateTimeImmutable($r["appt_date"]))->format("D, M j, Y"), $r["total"]], $data["byDate"]),
        [1],
    );
    $pdf->heading("All appointments (" . count($list) . ")");
    $pdf->table(
        ["Date", "Time", "Reference", "Pet", "Owner", "Service", "Status"],
        [22, 16, 24, 22, 30, 52, 20],
        array_map(
            fn($r) => [
                (new DateTimeImmutable($r["appt_date"]))->format("M j, Y"),
                (new DateTimeImmutable($r["appt_time"]))->format("g:i A"),
                $r["booking_ref"],
                $r["pet_name"],
                $r["owner_name"],
                $r["service_name"],
                ucfirst($r["status"]),
            ],
            $list,
        ),
    );
}

while (ob_get_level()) {
    ob_end_clean();
}
header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="rebadulla-report-' . $start . "-to-" . $end . '.pdf"');
echo $pdf->Output("S");
exit();
