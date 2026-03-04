<?php
require_once BASE_PATH . '/modules/stock/StockService.php';
require_once BASE_PATH . '/core/response.php';

class StockController
{
    private $service;

    public function __construct()
    {
        $this->service = new StockService();
    }

    public function index()
    {
        try {
            Response::success($this->service->getAllStock());
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function summary()
    {
        try {
            Response::success($this->service->getStockSummary());
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function lowStock()
    {
        try {
            Response::success($this->service->getLowStockProducts());
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function outOfStock()
    {
        try {
            Response::success($this->service->getOutOfStockProducts());
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function log()
    {
        try {
            Response::success($this->service->getStockLog());
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function adjust()
    {
        try {
            $rawInput = file_get_contents('php://input');
            $data = $rawInput ? json_decode($rawInput, true) : $_POST;
            if (!$data)
                $data = $_POST;

            $productId = isset($data['product_id']) ? $data['product_id'] : '';
            $quantity = (int) (isset($data['quantity']) ? $data['quantity'] : 0);
            $note = sanitize(isset($data['note']) ? $data['note'] : '');

            if (empty($productId)) {
                Response::error('Product ID is required');
                return;
            }

            $result = $this->service->adjustStock($productId, $quantity, $note);
            if ($result)
                Response::success(null, 'Stock adjusted');
            else
                Response::error('Failed to adjust stock');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
