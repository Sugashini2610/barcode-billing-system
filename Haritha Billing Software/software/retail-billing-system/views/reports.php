<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="section-header">
    <div>
        <h2>Reports</h2>
        <p>Sales analytics, monthly reports and product performance</p>
    </div>
</div>

<!-- Filter Tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:0">
    <button class="btn btn-ghost report-tab active" onclick="switchTab('monthly', this)"
        style="border-radius:6px 6px 0 0;border-bottom:2px solid var(--primary)">Monthly</button>
    <button class="btn btn-ghost report-tab" onclick="switchTab('daterange', this)"
        style="border-radius:6px 6px 0 0">Date Range</button>
    <button class="btn btn-ghost report-tab" onclick="switchTab('product', this)"
        style="border-radius:6px 6px 0 0">Product Wise</button>
</div>

<!-- Monthly Tab -->
<div id="tab-monthly">
    <div class="card" style="margin-bottom:16px">
        <div class="card-body">
            <div class="d-flex align-center gap-8">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Month</label>
                    <select id="reportMonth" class="form-select" style="width:140px">
                        <?php
                        $months = array('01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December');
                        $curMonth = date('m');
                        foreach ($months as $val => $label):
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $val === $curMonth ? 'selected' : ''; ?>>
                                <?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Year</label>
                    <select id="reportYear" class="form-select" style="width:100px">
                        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>>
                                <?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div style="margin-top:18px">
                    <button class="btn btn-primary" onclick="loadMonthlyReport()"><i class="bi bi-search"></i>
                        Generate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Summary -->
    <div class="grid grid-3 mb-24" id="monthlySummary" style="display:none;margin-bottom:16px">
        <div class="stat-card primary">
            <div class="stat-icon"><i class="bi bi-receipt"></i></div>
            <div class="stat-value" id="mBillCount">0</div>
            <div class="stat-label">Total Bills</div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-value" id="mNormalSales">₹0</div>
            <div class="stat-label">Normal Sales</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="bi bi-file-earmark-ruled"></i></div>
            <div class="stat-value" id="mGSTSales">₹0</div>
            <div class="stat-label">GST Sales</div>
        </div>
    </div>

    <div class="card" id="monthlyBillsCard" style="display:none">
        <div class="card-header">
            <span class="card-title" id="monthlyTitle">Bills</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Mode</th>
                    </tr>
                </thead>
                <tbody id="monthlyBillsBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Date Range Tab -->
<div id="tab-daterange" style="display:none">
    <div class="card" style="margin-bottom:16px">
        <div class="card-body">
            <div class="d-flex align-center gap-8">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">From Date</label>
                    <input type="date" id="dateFrom" class="form-control" value="<?php echo date('Y-m-01'); ?>"
                        style="width:160px">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">To Date</label>
                    <input type="date" id="dateTo" class="form-control" value="<?php echo date('Y-m-d'); ?>"
                        style="width:160px">
                </div>
                <div style="margin-top:18px">
                    <button class="btn btn-primary" onclick="loadDateRangeReport()"><i class="bi bi-search"></i>
                        Generate</button>
                </div>
            </div>
        </div>
    </div>
    <div class="card" id="dateRangeCard" style="display:none">
        <div class="card-header">
            <span class="card-title" id="dateRangeTitle">Bills</span>
            <span class="badge badge-success" id="dateRangeTotal"></span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody id="dateRangeBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Product Wise Tab -->
<div id="tab-product" style="display:none">
    <div style="margin-bottom:12px">
        <button class="btn btn-primary btn-sm" onclick="loadProductReport()"><i class="bi bi-bar-chart"></i> Load
            Report</button>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Product Wise Sales</span></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Total Qty Sold</th>
                        <th>Revenue</th>
                        <th>Bill Count</th>
                    </tr>
                </thead>
                <tbody id="productReportBody">
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding:28px">Click "Load Report" to fetch
                            data</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>