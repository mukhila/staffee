<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll\PayrollRun;
use App\Models\Payroll\PayrollSlip;
use Illuminate\Http\Request;

class PayrollBankExportController extends Controller
{
    public function index()
    {
        $runs = PayrollRun::completed()->orderByDesc('for_year')->orderByDesc('for_month')->get();
        return view('admin.payroll.bank-export', compact('runs'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'payroll_run_id' => 'required|exists:payroll_runs,id',
            'format'         => 'required|in:csv,nacha,sepa',
            'effective_date' => 'required|date',
        ]);

        $run   = PayrollRun::findOrFail($data['payroll_run_id']);
        $slips = PayrollSlip::where('payroll_run_id', $run->id)
            ->with('employee')
            ->get();

        $format        = $data['format'];
        $effectiveDate = $data['effective_date'];

        return match ($format) {
            'csv'   => $this->generateCsv($run, $slips),
            'nacha' => $this->generateNacha($run, $slips, $effectiveDate),
            'sepa'  => $this->generateSepa($run, $slips, $effectiveDate),
        };
    }

    private function generateCsv(PayrollRun $run, $slips)
    {
        $filename = "payroll_bank_{$run->for_year}_{$run->for_month}.csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($slips) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Employee Name', 'Employee ID', 'Email', 'Bank Account', 'IFSC/Sort Code', 'Net Pay', 'Currency', 'Payment Reference']);
            foreach ($slips as $slip) {
                $emp = $slip->employee;
                fputcsv($h, [
                    $emp->name,
                    $emp->id,
                    $emp->email,
                    $emp->bank_account ?? '',
                    $emp->bank_ifsc    ?? '',
                    number_format((float) $slip->net_pay, 2, '.', ''),
                    $slip->currency_code ?? 'INR',
                    $slip->slip_number,
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function generateNacha(PayrollRun $run, $slips, string $effectiveDate)
    {
        $filename    = "payroll_nacha_{$run->for_year}_{$run->for_month}.ach";
        $companyId   = str_pad(config('app.nacha_company_id', '1234567890'), 10);
        $companyName = str_pad(substr(config('app.name', 'STAFFEE'), 0, 16), 16);
        $routingNum  = config('app.nacha_routing_number', '021000021');
        $dateStr     = date('ymd', strtotime($effectiveDate));

        $lines = [];

        // File Header
        $lines[] = '1' . str_pad($routingNum, 9) . '          ' . $companyId . str_pad($companyName, 16)
            . '     ' . 'PPD' . $dateStr . '      ' . '094' . '10' . '1' . str_pad('1', 6, '0', STR_PAD_LEFT) . str_pad($routingNum, 9) . str_pad('00000001', 8, '0', STR_PAD_LEFT);

        // Batch Header
        $lines[] = '5' . '200' . $companyName . str_pad('PAYROLL', 10) . '  ' . $companyId . 'PPD' . str_pad('PAYROLL', 10) . $dateStr . $dateStr . '   ' . '1' . str_pad($routingNum, 8) . '0000001';

        $entryCount  = 0;
        $totalCredit = 0;
        $entryDetail = [];

        foreach ($slips as $idx => $slip) {
            $emp        = $slip->employee;
            $netPay     = (int) round((float) $slip->net_pay * 100); // cents
            $acct       = str_pad(substr($emp->bank_account ?? '00000000', 0, 17), 17);
            $name       = str_pad(substr($emp->name, 0, 22), 22);
            $traceNum   = str_pad($routingNum . str_pad($idx + 1, 7, '0', STR_PAD_LEFT), 15);

            $lines[]    = '6' . '22' . $routingNum . $acct . str_pad($netPay, 10, '0', STR_PAD_LEFT) . $name . '  ' . $traceNum;
            $entryCount++;
            $totalCredit += $netPay;
        }

        // Batch Control
        $lines[] = '8' . '200' . str_pad($entryCount, 6, '0', STR_PAD_LEFT) . str_pad($routingNum, 10, '0', STR_PAD_LEFT)
            . str_pad($totalCredit, 12, '0', STR_PAD_LEFT) . str_pad($totalCredit, 12, '0', STR_PAD_LEFT)
            . str_pad($companyId, 10) . str_repeat(' ', 25) . '1' . str_pad($routingNum, 8) . '0000001';

        // File Control
        $blockCount = (int) ceil((count($lines) + 1) / 10);
        $lines[] = '9' . '000001' . str_pad($blockCount, 6, '0', STR_PAD_LEFT)
            . str_pad($entryCount, 8, '0', STR_PAD_LEFT) . str_pad($routingNum, 10, '0', STR_PAD_LEFT)
            . str_pad($totalCredit, 12, '0', STR_PAD_LEFT) . str_pad($totalCredit, 12, '0', STR_PAD_LEFT)
            . str_repeat(' ', 39);

        $content = implode("\n", $lines) . "\n";

        return response($content, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function generateSepa(PayrollRun $run, $slips, string $effectiveDate)
    {
        $filename     = "payroll_sepa_{$run->for_year}_{$run->for_month}.xml";
        $msgId        = 'PAYROLL-' . $run->for_year . '-' . $run->for_month . '-' . date('His');
        $pmtInfoId    = 'PMT-' . $run->id;
        $companyName  = config('app.name', 'STAFFEE');
        $companyIban  = config('app.sepa_iban', 'GB29NWBK60161331926819');
        $companyBic   = config('app.sepa_bic', 'NWBKGB2L');
        $totalAmount  = $slips->sum(fn($s) => (float) $s->net_pay);
        $txCount      = $slips->count();

        $txXml = '';
        foreach ($slips as $slip) {
            $emp     = $slip->employee;
            $iban    = $emp->bank_iban ?? 'GB00XXXX00000000000000';
            $bic     = $emp->bank_bic  ?? 'XXXXGB22';
            $amount  = number_format((float) $slip->net_pay, 2, '.', '');
            $ref     = htmlspecialchars($slip->slip_number);
            $empName = htmlspecialchars(substr($emp->name, 0, 70));
            $txXml  .= <<<XML

        <CdtTrfTxInf>
          <PmtId><EndToEndId>{$ref}</EndToEndId></PmtId>
          <Amt><InstdAmt Ccy="EUR">{$amount}</InstdAmt></Amt>
          <CdtrAgt><FinInstnId><BIC>{$bic}</BIC></FinInstnId></CdtrAgt>
          <Cdtr><Nm>{$empName}</Nm></Cdtr>
          <CdtrAcct><Id><IBAN>{$iban}</IBAN></Id></CdtrAcct>
          <RmtInf><Ustrd>SALARY {$ref}</Ustrd></RmtInf>
        </CdtTrfTxInf>
XML;
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.003.03">
  <CstmrCdtTrfInitn>
    <GrpHdr>
      <MsgId>{$msgId}</MsgId>
      <CreDtTm>{$effectiveDate}T00:00:00</CreDtTm>
      <NbOfTxs>{$txCount}</NbOfTxs>
      <CtrlSum>{$totalAmount}</CtrlSum>
      <InitgPty><Nm>{$companyName}</Nm></InitgPty>
    </GrpHdr>
    <PmtInf>
      <PmtInfId>{$pmtInfoId}</PmtInfId>
      <PmtMtd>TRF</PmtMtd>
      <NbOfTxs>{$txCount}</NbOfTxs>
      <CtrlSum>{$totalAmount}</CtrlSum>
      <PmtTpInf><SvcLvl><Cd>SEPA</Cd></SvcLvl></PmtTpInf>
      <ReqdExctnDt>{$effectiveDate}</ReqdExctnDt>
      <Dbtr><Nm>{$companyName}</Nm></Dbtr>
      <DbtrAcct><Id><IBAN>{$companyIban}</IBAN></Id></DbtrAcct>
      <DbtrAgt><FinInstnId><BIC>{$companyBic}</BIC></FinInstnId></DbtrAgt>
      {$txXml}
    </PmtInf>
  </CstmrCdtTrfInitn>
</Document>
XML;

        return response($xml, 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
