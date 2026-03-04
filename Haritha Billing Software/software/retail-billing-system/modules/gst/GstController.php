<?php
require_once BASE_PATH . '/modules/gst/GstService.php';
require_once BASE_PATH . '/core/response.php';

class GstController
{
    private $service;

    public function __construct()
    {
        $this->service = new GstService();
    }

    public function index()
    {
        try {
            Response::success($this->service->getAll());
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

            if (empty($data['customer_name'])) {
                Response::error('Customer name is required');
                return;
            }
            if (empty($data['items'])) {
                Response::error('No items in GST bill');
                return;
            }

            $result = $this->service->createGSTBill($data);
            Response::success($result, 'GST Invoice created: ' . $result['bill_no']);
        } catch (Exception $e) {
            logMessage('GstController::store - ' . $e->getMessage(), 'ERROR');
            Response::error($e->getMessage());
        }
    }

    public function show($billNo)
    {
        try {
            $bill = $this->service->getByBillNo($billNo);
            if (!$bill) {
                Response::notFound("GST Bill not found: $billNo");
                return;
            }
            Response::success($bill);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
