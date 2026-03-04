<?php
require_once BASE_PATH . '/services/GoogleSheetsService.php';
require_once BASE_PATH . '/modules/products/ProductService.php';

class GstService
{
    private $sheets;
    private $productService;
    private $headers = array(
        'GST_Bill_No',
        'Date',
        'Customer_Name',
        'Customer_GSTIN',
        'Customer_Address',
        'Customer_State',
        'Items_JSON',
        'Taxable_Amount',
        'CGST_Amount',
        'SGST_Amount',
        'IGST_Amount',
        'Total_GST',
        'Discount',
        'Net_Total',
        'Round_Off',
        'Final_Amount',
        'Payment_Mode',
        'Inter_State',
        'Status',
        'Created_At'
    );

    public function __construct()
    {
        $this->sheets = new GoogleSheetsService();
        $this->productService = new ProductService();
        $this->sheets->initializeSheet(SHEET_GST_BILLS, $this->headers);
    }

    public function createGSTBill($data)
    {
        $billNo = generateBillNumber(GST_BILL_PREFIX);
        $items = isset($data['items']) ? $data['items'] : array();
        $interState = !empty($data['inter_state']);

        $taxableAmount = 0;
        $totalCGST = 0;
        $totalSGST = 0;
        $totalIGST = 0;
        $processedItems = array();

        foreach ($items as $item) {
            $product = $this->productService->getById($item['product_id']);
            if (!$product)
                continue;

            $qty = (int) (isset($item['quantity']) ? $item['quantity'] : 1);
            $price = (float) $product['Price'];
            $gstPct = (float) (isset($product['GST_Percent']) ? $product['GST_Percent'] : 18);
            $itemTaxable = $price * $qty;
            $gstSplit = gstSplit($gstPct, $interState);

            $cgstAmt = ($itemTaxable * $gstSplit['CGST']) / 100;
            $sgstAmt = ($itemTaxable * $gstSplit['SGST']) / 100;
            $igstAmt = ($itemTaxable * $gstSplit['IGST']) / 100;

            $taxableAmount += $itemTaxable;
            $totalCGST += $cgstAmt;
            $totalSGST += $sgstAmt;
            $totalIGST += $igstAmt;

            $processedItems[] = array(
                'product_id' => $product['ID'],
                'hsn_code' => isset($item['hsn_code']) ? $item['hsn_code'] : '',
                'name' => $product['Name'],
                'price' => $price,
                'qty' => $qty,
                'unit' => $product['Unit'],
                'taxable_amount' => $itemTaxable,
                'gst_percent' => $gstPct,
                'cgst_percent' => $gstSplit['CGST'],
                'sgst_percent' => $gstSplit['SGST'],
                'igst_percent' => $gstSplit['IGST'],
                'cgst_amount' => round($cgstAmt, 2),
                'sgst_amount' => round($sgstAmt, 2),
                'igst_amount' => round($igstAmt, 2),
            );

            $this->productService->reduceStock($product['ID'], $qty, $billNo);
        }

        $totalGST = $totalCGST + $totalSGST + $totalIGST;
        $discount = (float) (isset($data['discount']) ? $data['discount'] : 0);
        $netTotal = $taxableAmount + $totalGST - $discount;
        $roundOffData = roundOff($netTotal);

        $row = array(
            $billNo,
            date('Y-m-d'),
            sanitize(isset($data['customer_name']) ? $data['customer_name'] : ''),
            sanitize(isset($data['customer_gstin']) ? $data['customer_gstin'] : ''),
            sanitize(isset($data['customer_address']) ? $data['customer_address'] : ''),
            sanitize(isset($data['customer_state']) ? $data['customer_state'] : ''),
            json_encode($processedItems),
            round($taxableAmount, 2),
            round($totalCGST, 2),
            round($totalSGST, 2),
            round($totalIGST, 2),
            round($totalGST, 2),
            $discount,
            round($netTotal, 2),
            $roundOffData['difference'],
            $roundOffData['rounded'],
            sanitize(isset($data['payment_mode']) ? $data['payment_mode'] : PAYMENT_CASH),
            $interState ? '1' : '0',
            'Paid',
            nowDateTime(),
        );

        $this->sheets->appendRow(SHEET_GST_BILLS, $row);

        return array(
            'bill_no' => $billNo,
            'items' => $processedItems,
            'taxable_amount' => round($taxableAmount, 2),
            'cgst' => round($totalCGST, 2),
            'sgst' => round($totalSGST, 2),
            'igst' => round($totalIGST, 2),
            'total_gst' => round($totalGST, 2),
            'discount' => $discount,
            'net_total' => round($netTotal, 2),
            'round_off' => $roundOffData['difference'],
            'final_amount' => $roundOffData['rounded'],
            'date' => date('d/m/Y'),
            'inter_state' => $interState,
        );
    }

    public function getAll()
    {
        $bills = $this->sheets->readSheet(SHEET_GST_BILLS);
        foreach ($bills as &$bill) {
            $bill['Items'] = json_decode(isset($bill['Items_JSON']) ? $bill['Items_JSON'] : '[]', true);
        }
        return array_values($bills);
    }

    public function getByBillNo($billNo)
    {
        $bill = $this->sheets->findOne(SHEET_GST_BILLS, 'GST_Bill_No', $billNo);
        if ($bill)
            $bill['Items'] = json_decode(isset($bill['Items_JSON']) ? $bill['Items_JSON'] : '[]', true);
        return $bill;
    }
}
