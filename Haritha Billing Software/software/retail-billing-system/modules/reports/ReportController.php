<?php
require_once BASE_PATH . '/modules/reports/ReportService.php';
require_once BASE_PATH . '/core/response.php';

class ReportController
{
    private $service;

    public function __construct()
    {
        $this->service = new ReportService();
    }

    public function monthly()
    {
        try {
            $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');
            $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);
            $data = $this->service->getMonthlySalesReport($month, $year);
            Response::success($data);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function dateRange()
    {
        try {
            $from = sanitize(isset($_GET['from']) ? $_GET['from'] : date('Y-m-01'));
            $to = sanitize(isset($_GET['to']) ? $_GET['to'] : date('Y-m-d'));
            $data = $this->service->getDateRangeReport($from, $to);
            Response::success($data);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function productWise()
    {
        try {
            $data = $this->service->getProductWiseSalesReport();
            Response::success($data);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
