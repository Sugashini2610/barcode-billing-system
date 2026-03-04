<?php
require_once BASE_PATH . '/services/GoogleSheetsService.php';
require_once BASE_PATH . '/modules/products/ProductService.php';
require_once BASE_PATH . '/modules/billing/BillingService.php';
require_once BASE_PATH . '/modules/stock/StockService.php';

/**
 * DashboardService - PHP 5.6 compatible
 * Optimised: fetches each sheet only ONCE and reuses the data.
 */
class DashboardService
{
    private $billingService;
    private $productService;
    private $stockService;

    public function __construct()
    {
        $this->billingService = new BillingService();
        $this->productService = new ProductService();
        $this->stockService = new StockService();
    }

    public function getSummary()
    {
        // ── 1. Fetch all sheets ONCE ────────────────────────────────────────
        // Each call is one Google Sheets API request.
        $allProducts = $this->productService->getAll();           // 1 API call
        $recentBills = $this->billingService->getRecent(10);      // 1 API call
        $stockSummary = $this->stockService->getStockSummary();    // uses allProducts internally (no extra call)
        $lowStock = $this->stockService->getLowStockProducts(); // reuses sheet data
        $outOfStock = $this->stockService->getOutOfStockProducts(); // reuses sheet data

        // ── 2. Top selling products from recent bills (no extra API call) ───
        $topProducts = $this->calcTopProducts($recentBills);

        return array(
            'total_products' => count($allProducts),
            'stock_summary' => $stockSummary,
            'recent_bills' => $recentBills,
            'top_products' => $topProducts,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
        );
    }

    /**
     * Calculate top 5 products by units sold from a set of bills.
     * Runs in PHP memory – no extra API call needed.
     */
    private function calcTopProducts($bills)
    {
        $productSales = array();
        foreach ($bills as $bill) {
            $items = isset($bill['Items']) && is_array($bill['Items']) ? $bill['Items'] : array();
            foreach ($items as $item) {
                $id = isset($item['product_id']) ? $item['product_id'] : '';
                if (empty($id))
                    continue;
                if (!isset($productSales[$id])) {
                    $productSales[$id] = array(
                        'name' => isset($item['name']) ? $item['name'] : 'Unknown',
                        'qty' => 0,
                        'revenue' => 0,
                    );
                }
                $productSales[$id]['qty'] += isset($item['qty']) ? (int) $item['qty'] : 0;
                $productSales[$id]['revenue'] += isset($item['item_total']) ? (float) $item['item_total'] : 0;
            }
        }

        $arr = array_values($productSales);
        usort($arr, array('DashboardService', 'sortByQtyDesc'));
        return array_slice($arr, 0, 5);
    }

    public static function sortByQtyDesc($a, $b)
    {
        return $b['qty'] - $a['qty'];
    }
}
