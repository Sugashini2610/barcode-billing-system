<?php
require_once BASE_PATH . '/services/GoogleSheetsService.php';
require_once BASE_PATH . '/modules/products/ProductService.php';

/**
 * BillingService - PHP 5.6 compatible
 */
class BillingService
{
    private $sheets;
    private $productService;
    private $billHeaders = array(
        'Bill_No',
        'Date',
        'Customer_Name',
        'Customer_Phone',
        'Items_JSON',
        'Subtotal',
        'GST_Amount',
        'Discount',
        'Net_Total',
        'Round_Off',
        'Final_Amount',
        'Payment_Mode',
        'Bill_Type',
        'Status',
        'Created_At'
    );

    public function __construct()
    {
        $this->sheets = new GoogleSheetsService();
        $this->productService = new ProductService();
        $this->sheets->initializeSheet(SHEET_BILLS, $this->billHeaders);
    }

    public function createBill($data)
    {
        $billNo = generateBillNumber(BILL_PREFIX);
        $items = isset($data['items']) ? $data['items'] : array();
        $subtotal = 0;
        $totalGST = 0;
        $processedItems = array();

        foreach ($items as $item) {
            $product = $this->productService->getById($item['product_id']);
            if (!$product)
                continue;

            $qty = (int) (isset($item['quantity']) ? $item['quantity'] : 1);
            $price = (float) $product['Price'];
            $gstPct = (float) (isset($product['GST_Percent']) ? $product['GST_Percent'] : 0);
            $itemTotal = $price * $qty;
            $gstData = calculateGST($itemTotal, $gstPct, 'Exclusive');

            $subtotal += $itemTotal;
            $totalGST += $gstData['gst'];

            $processedItems[] = array(
                'product_id' => $product['ID'],
                'name' => $product['Name'],
                'barcode' => $product['Barcode'],
                'price' => $price,
                'qty' => $qty,
                'gst_percent' => $gstPct,
                'item_total' => $itemTotal,
                'gst_amount' => $gstData['gst'],
            );

            $this->productService->reduceStock($product['ID'], $qty, $billNo);
        }

        $discount = (float) (isset($data['discount']) ? $data['discount'] : 0);
        $netTotal = $subtotal + $totalGST - $discount;
        $roundOffData = roundOff($netTotal);

        $row = array(
            $billNo,
            date('Y-m-d'),
            sanitize(isset($data['customer_name']) ? $data['customer_name'] : 'Walk-in Customer'),
            sanitize(isset($data['customer_phone']) ? $data['customer_phone'] : ''),
            json_encode($processedItems),
            round($subtotal, 2),
            round($totalGST, 2),
            $discount,
            round($netTotal, 2),
            $roundOffData['difference'],
            $roundOffData['rounded'],
            sanitize(isset($data['payment_mode']) ? $data['payment_mode'] : PAYMENT_CASH),
            BILL_NORMAL,
            'Paid',
            nowDateTime(),
        );

        $this->sheets->appendRow(SHEET_BILLS, $row);

        return array(
            'bill_no' => $billNo,
            'items' => $processedItems,
            'subtotal' => round($subtotal, 2),
            'gst_amount' => round($totalGST, 2),
            'discount' => $discount,
            'net_total' => round($netTotal, 2),
            'round_off' => $roundOffData['difference'],
            'final_amount' => $roundOffData['rounded'],
            'payment_mode' => isset($data['payment_mode']) ? $data['payment_mode'] : PAYMENT_CASH,
            'date' => date('d/m/Y'),
        );
    }

    public function getAll()
    {
        $bills = $this->sheets->readSheet(SHEET_BILLS);
        foreach ($bills as &$bill) {
            $bill['Items'] = json_decode(isset($bill['Items_JSON']) ? $bill['Items_JSON'] : '[]', true);
        }
        return array_values($bills);
    }

    public function getByBillNo($billNo)
    {
        $bill = $this->sheets->findOne(SHEET_BILLS, 'Bill_No', $billNo);
        if ($bill) {
            $bill['Items'] = json_decode(isset($bill['Items_JSON']) ? $bill['Items_JSON'] : '[]', true);
        }
        return $bill;
    }

    public function getRecent($limit = 10)
    {
        $bills = $this->getAll();
        return array_slice(array_reverse($bills), 0, $limit);
    }

    public function getTodaySales()
    {
        $today = date('Y-m-d');
        $bills = $this->sheets->filterByDateRange(SHEET_BILLS, 'Date', $today, $today);
        $total = 0;
        foreach ($bills as $b) {
            $total += isset($b['Final_Amount']) ? (float) $b['Final_Amount'] : 0;
        }
        return $total;
    }

    public function getMonthlySales($month = null, $year = null)
    {
        if (!$month)
            $month = date('m');
        if (!$year)
            $year = date('Y');
        $from = "$year-$month-01";
        $to = date("Y-m-t", strtotime($from));
        return array_values($this->sheets->filterByDateRange(SHEET_BILLS, 'Date', $from, $to));
    }
}
