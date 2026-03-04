<?php
require_once BASE_PATH . '/services/GoogleSheetsService.php';
require_once BASE_PATH . '/modules/products/ProductService.php';

class StockService
{
    private $sheets;
    private $productService;

    public function __construct()
    {
        $this->sheets = new GoogleSheetsService();
        $this->productService = new ProductService();
        $this->sheets->initializeSheet(SHEET_STOCK_LOG, array(
            'Log_ID',
            'Product_ID',
            'Product_Name',
            'Change',
            'Type',
            'Note',
            'Created_At'
        ));
    }

    public function getStockSummary()
    {
        $products = $this->productService->getAll();
        $summary = array('in_stock' => 0, 'low_stock' => 0, 'out_of_stock' => 0);
        foreach ($products as $p) {
            $qty = (int) $p['Quantity'];
            if ($qty <= 0)
                $summary['out_of_stock']++;
            elseif ($qty <= STOCK_LOW_THRESHOLD)
                $summary['low_stock']++;
            else
                $summary['in_stock']++;
        }
        return $summary;
    }

    public function getLowStockProducts()
    {
        return array_values($this->productService->getLowStock());
    }

    public function getOutOfStockProducts()
    {
        return array_values($this->productService->getOutOfStock());
    }

    public function getAllStock()
    {
        $products = $this->productService->getAll();
        foreach ($products as &$p) {
            $p['Stock_Status'] = getStockStatus((int) $p['Quantity']);
            if ($p['Stock_Status'] === STOCK_IN) {
                $p['Status_Class'] = 'success';
            } elseif ($p['Stock_Status'] === STOCK_LOW) {
                $p['Status_Class'] = 'warning';
            } elseif ($p['Stock_Status'] === STOCK_OUT) {
                $p['Status_Class'] = 'danger';
            } else {
                $p['Status_Class'] = 'secondary';
            }
        }
        return array_values($products);
    }

    public function getStockLog($limit = 50)
    {
        $logs = $this->sheets->readSheet(SHEET_STOCK_LOG);
        return array_slice(array_reverse(array_values($logs)), 0, $limit);
    }

    public function adjustStock($productId, $quantity, $note = '')
    {
        $product = $this->productService->getById($productId);
        if (!$product) return false;

        $newQty = max(0, (int)$product['Quantity'] + $quantity);

        $updated = $this->productService->update($productId, array(
            'quantity' => $newQty
        ));

        if ($updated) {
            $this->sheets->appendRow(SHEET_STOCK_LOG, array(
                uniqid(),
                $productId,
                $product['Name'],
                $quantity,
                'ADJUSTMENT',
                $note,
                nowDateTime()
            ));
        }

        return $updated;
    }
}
