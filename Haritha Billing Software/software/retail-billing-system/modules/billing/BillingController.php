<?php
require_once BASE_PATH . '/modules/billing/BillingService.php';
require_once BASE_PATH . '/core/response.php';

class BillingController
{
    private $service;

    public function __construct()
    {
        $this->service = new BillingService();
    }

    public function index()
    {
        try {
            Response::success($this->service->getRecent(20));
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function store()
    {
        try {
            $rawInput = file_get_contents('php://input');
            $data = $rawInput ? json_decode($rawInput, true) : $_POST;
            if (!$data)
                $data = $_POST;

            if (empty($data['items'])) {
                Response::error('No items in bill');
                return;
            }

            $result = $this->service->createBill($data);
            Response::success($result, 'Bill created: ' . $result['bill_no']);
        } catch (Exception $e) {
            logMessage('BillingController::store - ' . $e->getMessage(), 'ERROR');
            Response::error($e->getMessage());
        }
    }

    public function show($billNo)
    {
        try {
            $bill = $this->service->getByBillNo($billNo);
            if (!$bill) {
                Response::notFound("Bill not found: $billNo");
                return;
            }
            Response::success($bill);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function todaySales()
    {
        try {
            $total = $this->service->getTodaySales();
            Response::success(array('total' => $total, 'formatted' => formatCurrency($total)));
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
