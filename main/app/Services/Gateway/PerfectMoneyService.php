<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Deposit;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Class PerfectMoneyService
 */
class PerfectMoneyService extends BaseAdapter
{
    protected string $accountId;
    protected string $passphrase;
    protected string $altPassphrase;
    protected string $merchantId;

    /**
     * @var array
     */
    protected array $sslFix = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]];

    public function __construct()
    {
        $this->accountId = (string)config('perfectmoney.account_id');
        $this->passphrase = (string)config('perfectmoney.passphrase');
        $this->altPassphrase = (string)config('perfectmoney.alternate_passphrase');
        $this->merchantId = (string)config('perfectmoney.marchant_id');
    }

    /**
     * Get the balance for the wallet.
     */
    public function getBalance(): array
    {
        $url = file_get_contents('https://perfectmoney.is/acct/balance.asp?AccountID=' . $this->accountId . '&PassPhrase=' . $this->passphrase, false, stream_context_create($this->sslFix));
        
        if (!$url) {
            return $this->returnError('Connection error');
        }

        if (!preg_match_all("/<input name='(.*)' type='hidden' value='(.*)'>/", $url, $result, PREG_SET_ORDER)) {
            return $this->returnError('Invalid output');
        }

        $data = [];
        foreach ($result as $item) {
            if ($item[1] == 'ERROR') {
                return $this->returnError($item[2]);
            }
            
            $data['balance'] = [
                'currency' => $item[1],
                'balance' => $item[2]
            ];
        }

        return $this->successResponse('Balance retrieved', $data);
    }

    /**
     * Send Money.
     */
    public function sendMoney(string $account, float $amount, string $description = '', string $paymentId = ''): array
    {
        $url = file_get_contents('https://perfectmoney.is/acct/confirm.asp?AccountID=' . urlencode(trim($this->accountId)) . '&PassPhrase=' . urlencode(trim($this->passphrase)) . '&Payer_Account=' . urlencode(trim($this->merchantId)) . '&Payee_Account=' . urlencode(trim($account)) . '&Amount=' . $amount . (empty($description) ? '' : '&Memo=' . urlencode(trim($description))) . (empty($paymentId) ? '' : '&PAYMENT_ID=' . urlencode(trim($paymentId))), false, stream_context_create($this->sslFix));

        if (!$url) {
            return $this->returnError('Connection error');
        }

        if (!preg_match_all("/<input name='(.*)' type='hidden' value='(.*)'>/", $url, $result, PREG_SET_ORDER)) {
            return $this->returnError('Invalid output');
        }

        $data = [];
        foreach ($result as $item) {
            if ($item[1] == 'ERROR') {
                return $this->returnError($item[2]);
            }
            
            $data['data'][$item[1]] = $item[2];
        }

        return $this->successResponse('Money sent', $data);
    }

    /**
     * Render form.
     */
    public static function render(array $data = [], string $view = 'perfectmoney')
    {
        $viewData = [
            'PAYEE_ACCOUNT' => $data['PAYEE_ACCOUNT'] ?? config('perfectmoney.marchant_id'),
            'PAYEE_NAME' => $data['PAYEE_NAME'] ?? config('perfectmoney.marchant_name'),
            'PAYMENT_AMOUNT' => $data['PAYMENT_AMOUNT'] ?? '',
            'PAYMENT_UNITS' => $data['PAYMENT_UNITS'] ?? config('perfectmoney.units'),
            'PAYMENT_ID' => $data['PAYMENT_ID'] ?? null,
            'PAYMENT_URL' => $data['PAYMENT_URL'] ?? config('perfectmoney.payment_url'),
            'NOPAYMENT_URL' => $data['NOPAYMENT_URL'] ?? config('perfectmoney.nopayment_url'),
        ];

        $viewData['STATUS_URL'] = $data['STATUS_URL'] ?? config('perfectmoney.status_url');
        $viewData['PAYMENT_URL_METHOD'] = $data['PAYMENT_URL_METHOD'] ?? config('perfectmoney.payment_url_method');
        $viewData['NOPAYMENT_URL_METHOD'] = $data['NOPAYMENT_URL_METHOD'] ?? config('perfectmoney.nopayment_url_method');
        $viewData['MEMO'] = $data['SUGGESTED_MEMO'] ?? config('perfectmoney.suggested_memo');

        if (view()->exists('laravelperfectmoney::' . $view)) {
            return view('laravelperfectmoney::' . $view, $viewData);
        }

        return view('laravelperfectmoney::perfectmoney', $viewData);
    }

    /**
     * Query account history.
     */
    public function getHistory($start_day = null, $start_month = null, $start_year = null, $end_day = null, $end_month = null, $end_year = null, array $data = []): array
    {
        $start_day = $start_day ?? Carbon::now()->subYear()->day;
        $start_month = $start_month ?? Carbon::now()->subYear()->month;
        $start_year = $start_year ?? Carbon::now()->subYear()->year;
        $end_day = $end_day ?? Carbon::now()->day;
        $end_month = $end_month ?? Carbon::now()->month;
        $end_year = $end_year ?? Carbon::now()->year;

        $url = 'https://perfectmoney.is/acct/historycsv.asp?startmonth=' . $start_month . '&startday=' . $start_day . '&startyear=' . $start_year . '&endmonth=' . $end_month . '&endday=' . $end_day . '&endyear=' . $end_year . '&AccountID=' . urlencode(trim($this->accountId)) . '&PassPhrase=' . urlencode(trim($this->passphrase));

        if (isset($data['payment_id'])) {
            $url .= '&payment_id=' . $data['payment_id'];
        }
        if (isset($data['batchfilter'])) {
            $url .= '&batchfilter=' . $data['batchfilter'];
        }
        if (isset($data['counterfilter'])) {
            $url .= '&counterfilter=' . $data['counterfilter'];
        }
        // ... other filters ...

        $content = file_get_contents($url, false, stream_context_create($this->sslFix));
        
        if (!$content) {
            return $this->returnError('Connection error');
        }

        if (str_starts_with($content, 'Time,Type,Batch,Currency,Amount,Fee,Payer Account,Payee Account')) {
            $lines = explode("\n", $content);
            $rows = explode(",", $lines[0]);
            $history = [];

            for ($i = 1; $i < count($lines); $i++) {
                if (empty($lines[$i])) break;
                
                $items = explode(',', $lines[$i]);
                $historyLine = [];
                foreach ($items as $key => $value) {
                    $historyLine[str_replace(' ', '_', strtolower($rows[$key]))] = $value;
                }
                $history[] = $historyLine;
            }

            return $this->successResponse('History retrieved', ['history' => $history]);
        }

        return $this->returnError($content);
    }

    /**
     * Generate Hash.
     */
    public function generateHash(Request $request): string
    {
        $string = $request->input('PAYMENT_ID') . ':' .
                 $request->input('PAYEE_ACCOUNT') . ':' .
                 $request->input('PAYMENT_AMOUNT') . ':' .
                 $request->input('PAYMENT_UNITS') . ':' .
                 $request->input('PAYMENT_BATCH_NUM') . ':' .
                 $request->input('PAYER_ACCOUNT') . ':' .
                 strtoupper(md5($this->altPassphrase)) . ':' .
                 $request->input('TIMESTAMPGMT');
                 
        return strtoupper(md5($string));
    }

    /**
     * Handle success callback from Perfect Money.
     */
    public function success(Request $request): array
    {
        $paymentId = $request->input('PAYMENT_ID');
        $type = session('type');

        if ($type === 'deposit') {
            $deposit = Deposit::where('trx', $paymentId)->first();
        } else {
            $deposit = Payment::where('trx', $paymentId)->first();
        }

        if (!$deposit) {
            return $this->returnError('Transaction not found');
        }

        $gateway = $deposit->gateway->parameter;
        $unit = $request->input('PAYMENT_UNITS');
        $amount = (float)$request->input('PAYMENT_AMOUNT');
        $payeeAccount = $request->input('PAYEE_ACCOUNT');

        if ($payeeAccount === $gateway->accountid && $unit === $gateway->gateway_currency && $amount === (float)$deposit->total) {
            $this->handlePaymentSuccess($deposit, 0.0, (string)$request->input('PAYMENT_ID'));

            return $this->returnSuccess('Payment Successful');
        }

        return $this->returnError('Payment verification failed');
    }
}
