<?php
require_once BASE_PATH . '/modules/dashboard/DashboardService.php';
require_once BASE_PATH . '/core/response.php';

class DashboardController
{
    private $service;

    public function __construct()
    {
        $this->service = new DashboardService();
    }

    public function index()
    {
        try {
            $data = $this->service->getSummary();
            Response::success($data, 'Dashboard data loaded');
        } catch (Exception $e) {
            logMessage('DashboardController::index - ' . $e->getMessage(), 'ERROR');
            Response::error($e->getMessage());
        }
    }
}
