<?php
namespace App\Classes;
use TCPDF;

class CustomPdfGenerator extends TCPDF 
{
    public function Header() 
    {
        $this->Cell(0, 15, '', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln();
        $this->writeHTML("<h1>Frostshock</h1>", true, false, false, false, 'R');

        $this->Write(0, "\n", '', 0, 'C', true, 0, false, false, 0);
 
        $this->writeHTML("Die Straße", true, false, false, false, 'R');
        $this->writeHTML("45721 Haltern am See", true, false, false, false, 'R');
        $this->writeHTML("frostshock@gmail.com", true, false, false, false, 'R');
        $this->Write(0, "\n", '', 0, 'C', true, 0, false, false, 0);
    }
 
    public function Footer() 
    {
        $this->SetY(-30);
        $this->SetFont('helvetica', 'I', 8);
       // $this->Cell(0, 10, 'Thank you for your business!', 0, false, 'C', 0, '', 0, false, 'T', 'M');
       $html = '
       <hr>
       <br>
       <table cellspacing="0" style="">
       <tr>
       <th><b>Bankverbindung</b></th>
       <th><b>Steuernummer</b></th>
       </tr>

       <tr>
       <td>Beispielbank (BLZ: 200 10020)</td>
       <td>123/14123/234</td>
       </tr>

       <tr>
       <td>Kontonummer: 235245354</td>
       </tr>

       <tr>
       <td>IBAN: DE99 132 165 4651 6464</td>
       </tr>

       </table>';

       $this->writeHTML($html, false, true, false, true);
    }
 
    public function printTable($header, $data)
    {
        $this->SetFillColor(191);
        $this->SetTextColor(0);
        //$this->SetDrawColor(128, 0, 0);
        //$this->SetLineWidth(0.3);
        $this->SetFont('', 'B', 12);
 
        //row width
        $w = array(60, 30, 30, 30, 30);

        $num_headers = count($header);
        for($i = 0; $i < $num_headers; ++$i) {
            $this->Cell($w[$i], 7, $header[$i], 0, 0, 'L', 1);
        }
        $this->Ln();
 
        // Color and font restoration
       // $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
 
        // table data
        $fill = 0;
        $total = 0;
 
        foreach($data as $row) {
            $this->Cell($w[0], 4, $row[0], 0, 0, 'L', $fill);
            $this->Cell($w[1], 4, $row[1], 0, 0, 'L', $fill);
            $this->Cell($w[2], 4, $row[2], 0, 0, 'L', $fill);
            $this->Cell($w[3], 4, number_format($row[3]) . ' €', 0, 0, 'C', $fill);
            $this->Cell($w[4], 4, number_format($row[4]). ' €' , 0, 0, 'C', $fill);
            $this->Ln();
            $fill=!$fill;
            $total+=$row[4];
        }
 
        $this->Cell($w[0], 4, '', 0, 0, 'L', $fill);
        $this->Cell($w[1], 4, '', 0, 0, 'L', $fill);
        $this->Cell($w[2], 4, '', 0, 0, 'R', $fill);
        $this->Cell($w[3], 4, '', 0, 0, 'L', $fill);
        $this->Cell($w[4], 4, '', 0, 0, 'R', $fill);
        $this->Ln();

        $this->SetFont('', 'B', 12);
        $this->Cell($w[0], 4, "Gesamtsumme:", 'T', 0, 'L', $fill);
        $this->Cell($w[1], 4, '', 'T', 0, 'R', $fill);
        $this->Cell($w[2], 4, '', 'T', 0, 'R', $fill);
        $this->Cell($w[3], 4, '', 'T', 0, 'L', $fill);
        $this->Cell($w[4], 4, number_format($total) . ' €', 'T', 0, 'C', $fill);
        $this->Ln();
 
        $this->Cell(array_sum($w), 0, '', '');
    }
}