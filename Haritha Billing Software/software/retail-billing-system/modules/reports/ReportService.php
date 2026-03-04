<?php
require_once BASE_PATH . '/modules/billing/BillingService.php';
require_once BASE_PATH . '/modules/gst/GstService.php';
require_once BASE_PATH . '/modules/products/ProductService.php';

/**
 * ReportService - PHP 5.6 compatible
 */
class ReportService
{
    private $billingService;
    private $gstService;
    private $productService;

    public function __construct()
    {
        $this->billingService = new BillingService();
        $this->gstService = new GstService();
        $this->productService = new ProductService();
    }

    public function getMonthlySalesReport($month, $year)
    {
        $bills = $this->billingService->getMonthlySales($month, $year);
        // $allGst = $this->gstService->getAll();
        $sheets = new GoogleSheetsService();

        $from = "$year-$month-01";
        $to = date('Y-m-t', strtotime($from));
        
        $filteredGST = $sheets->filterByDateRange(
            SHEET_GST_BILLS,
            'Date',
            $from,
            $to
        );

        // $filteredGST = array();
        // foreach ($allGst as $b) {
        //     $d = isset($b['Date']) ? $b['Date'] : '';
        //     if ($d >= $from && $d <= $to) {
        //         $filteredGST[] = $b;
        //     }
        // }

        $totalSales = 0;
        $totalGSTSales = 0;
        foreach ($bills as $b) {
            $totalSales += isset($b['Final_Amount']) ? (float) $b['Final_Amount'] : 0;
        }
        foreach ($filteredGST as $b) {
            $totalGSTSales += isset($b['Final_Amount']) ? (float) $b['Final_Amount'] : 0;
        }

        return array(
            'month' => date('F Y', strtotime($from)),
            'normal_bills' => array_values($bills),
            'gst_bills' => array_values($filteredGST),
            'total_normal_sales' => round($totalSales, 2),
            'total_gst_sales' => round($totalGSTSales, 2),
            'grand_total' => round($totalSales + $totalGSTSales, 2),
            'bill_count' => count($bills) + count($filteredGST),
        );
    }

    public function getDateRangeReport($from, $to)
    {
        $sheets = new GoogleSheetsService();
        $bills = $sheets->filterByDateRange(SHEET_BILLS, 'Date', $from, $to);
        $gstBills = $sheets->filterByDateRange(SHEET_GST_BILLS, 'Date', $from, $to);

        foreach ($bills as &$b) {
            $b['Items'] = json_decode(isset($b['Items_JSON']) ? $b['Items_JSON'] : '[]', true);
        }
        unset($b);
        foreach ($gstBills as &$b) {
            $b['Items'] = json_decode(isset($b['Items_JSON']) ? $b['Items_JSON'] : '[]', true);
        }
        unset($b);

        $totalBills = 0;
        $totalGST = 0;
        foreach ($bills as $b) {
            $totalBills += isset($b['Final_Amount']) ? (float) $b['Final_Amount'] : 0;
        }
        foreach ($gstBills as $b) {
            $totalGST += isset($b['Final_Amount']) ? (float) $b['Final_Amount'] : 0;
        }

        return array(
            'from' => $from,
            'to' => $to,
            'normal_bills' => array_values($bills),
            'gst_bills' => array_values($gstBills),
            'total_sales' => round($totalBills + $totalGST, 2),
        );
    }

    public function getProductWiseSalesReport()
    {
        $allBills = array_merge(
    $this->billingService->getAll(),
            $this->gstService->getAll()
            );
        $salesData = array();

        foreach ($allBills as $bill) {
            $items = isset($bill['Items']) && is_array($bill['Items']) ? $bill['Items'] : array();
            foreach ($items as $item) {
                $id = isset($item['product_id']) ? $item['product_id'] : '';
                if (empty($id))
                    continue;
                if (!isset($salesData[$id])) {
                    $salesData[$id] = array(
                        'product_id' => $id,
                        'product_name' => isset($item['name']) ? $item['name'] : 'Unknown',
                        'total_qty' => 0,
                        'total_revenue' => 0,
                        'bill_count' => 0,
                    );
                }
                $salesData[$id]['total_qty'] += isset($item['qty']) ? (int) $item['qty'] : 0;
                $salesData[$id]['total_revenue'] += isset($item['item_total']) ? (float) $item['item_total'] : 0;
                $salesData[$id]['bill_count']++;
            }
        }

        $arr = array_values($salesData);
        usort($arr, array('ReportService', 'sortByRevenueDesc'));
        return $arr;
    }

    public static function sortByRevenueDesc($a, $b)
    {
        if ($a['total_revenue'] == $b['total_revenue']) return 0;
        return ($a['total_revenue'] < $b['total_revenue']) ? 1 : -1;
    }
}
