<?php
require_once BASE_PATH . '/services/GoogleSheetsService.php';
require_once BASE_PATH . '/services/BarcodeService.php';

/**
 * ProductService - PHP 5.6 compatible
 */
class ProductService
{
    private $sheets;
    private $headers = array('ID', 'Name', 'Category', 'Price', 'GST_Percent', 'Barcode', 'Quantity', 'Unit', 'Description', 'Created_At', 'Updated_At');

    public function __construct()
    {
        $this->sheets = new GoogleSheetsService();
        $this->sheets->initializeSheet(SHEET_PRODUCTS, $this->headers);
    }

    public function getAll()
    {
        return array_values($this->sheets->readSheet(SHEET_PRODUCTS));
    }

    public function getById($id)
    {
        return $this->sheets->findOne(SHEET_PRODUCTS, 'ID', $id);
    }

    public function getByBarcode($barcode)
    {
        return $this->sheets->findOne(SHEET_PRODUCTS, 'Barcode', $barcode);
    }

    public function create($data)
    {
        $barcode = BarcodeService::generateUniqueBarcode();
        while ($this->getByBarcode($barcode)) {
            $barcode = BarcodeService::generateUniqueBarcode();
        }

        $id = 'PRD' . date('YmdHis') . mt_rand(10, 99);
        $row = array(
            $id,
            sanitize($data['name']),
            sanitize(isset($data['category']) ? $data['category'] : 'General'),
            (float) (isset($data['price']) ? $data['price'] : 0),
            (float) (isset($data['gst_percent']) ? $data['gst_percent'] : 0),
            $barcode,
            (int) (isset($data['quantity']) ? $data['quantity'] : 0),
            sanitize(isset($data['unit']) ? $data['unit'] : 'Piece'),
            sanitize(isset($data['description']) ? $data['description'] : ''),
            nowDateTime(),
            nowDateTime(),
        );

        $this->sheets->appendRow(SHEET_PRODUCTS, $row);
        $this->logStock($id, $data['name'], (int) $data['quantity'], 'STOCK_IN', 'Initial stock');

        return array('id' => $id, 'barcode' => $barcode);
    }

    /**
     * createWithBarcode — same as create() but uses a user-supplied barcode.
     * Used by the Generate Barcode page so the label barcode is scannable on billing screen.
     */
    public function createWithBarcode($data)
    {
        $barcode = sanitize($data['barcode']);
        $id = 'PRD' . date('YmdHis') . mt_rand(10, 99);
        $qty = (int) (isset($data['quantity']) ? $data['quantity'] : 0);

        $row = array(
            $id,
            sanitize($data['name']),
            sanitize(isset($data['category']) ? $data['category'] : 'General'),
            (float) (isset($data['price']) ? $data['price'] : 0),
            (float) (isset($data['gst_percent']) ? $data['gst_percent'] : 0),
            $barcode,
            $qty,
            sanitize(isset($data['unit']) ? $data['unit'] : 'Piece'),
            sanitize(isset($data['description']) ? $data['description'] : 'Generated label product'),
            nowDateTime(),
            nowDateTime(),
        );

        $this->sheets->appendRow(SHEET_PRODUCTS, $row);
        if ($qty > 0) {
            $this->logStock($id, $data['name'], $qty, 'STOCK_IN', 'Label product initial stock');
        }

        return array('id' => $id, 'barcode' => $barcode);
    }

    public function update($id, $data)
    {
        $product = $this->getById($id);
        if (!$product)
            return false;

        $row = array(
            $id,
            sanitize(isset($data['name']) ? $data['name'] : $product['Name']),
            sanitize(isset($data['category']) ? $data['category'] : $product['Category']),
            (float) (isset($data['price']) ? $data['price'] : $product['Price']),
            (float) (isset($data['gst_percent']) ? $data['gst_percent'] : $product['GST_Percent']),
            $product['Barcode'],
            (int) (isset($data['quantity']) ? $data['quantity'] : $product['Quantity']),
            sanitize(isset($data['unit']) ? $data['unit'] : $product['Unit']),
            sanitize(isset($data['description']) ? $data['description'] : $product['Description']),
            $product['Created_At'],
            nowDateTime(),
        );

        return $this->sheets->updateRow(SHEET_PRODUCTS, $product['_row'], $row);
    }

    public function delete($id)
    {
        $product = $this->getById($id);
        if (!$product)
            return false;
        return $this->sheets->deleteRow(SHEET_PRODUCTS, $product['_row']);
    }

    public function reduceStock($productId, $quantity, $billNo = '')
    {
        $product = $this->getById($productId);
        if (!$product)
            return false;

        $newQty = max(0, (int) $product['Quantity'] - $quantity);
        $data = array(
            'ID' => $product['ID'],
            'Name' => $product['Name'],
            'Category' => $product['Category'],
            'Price' => $product['Price'],
            'GST_Percent' => $product['GST_Percent'],
            'Barcode' => $product['Barcode'],
            'Quantity' => $newQty,
            'Unit' => $product['Unit'],
            'Description' => $product['Description'],
            'Created_At' => $product['Created_At'],
            'Updated_At' => nowDateTime(),
        );

        $result = $this->sheets->updateRow(SHEET_PRODUCTS, $product['_row'], array_values($data));

        if ($result) {
            $this->logStock($productId, $product['Name'], -$quantity, 'SALE', "Bill: $billNo");
        }
        return $result;
    }

    public function getLowStock()
    {
        $all = $this->getAll();
        $result = array();
        foreach ($all as $p) {
            $qty = (int) $p['Quantity'];
            if ($qty > 0 && $qty <= STOCK_LOW_THRESHOLD) {
                $result[] = $p;
            }
        }
        return $result;
    }

    public function getOutOfStock()
    {
        $all = $this->getAll();
        $result = array();
        foreach ($all as $p) {
            if ((int) $p['Quantity'] <= 0) {
                $result[] = $p;
            }
        }
        return $result;
    }

    private function logStock($productId, $productName, $change, $type, $note)
    {
        $row = array(
            'LOG' . date('YmdHis'),
            $productId,
            $productName,
            $change > 0 ? "+$change" : "$change",
            $type,
            $note,
            nowDateTime(),
        );
        $this->sheets->appendRow(SHEET_STOCK_LOG, $row);
    }
}
