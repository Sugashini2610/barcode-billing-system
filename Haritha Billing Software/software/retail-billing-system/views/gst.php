<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="section-header">
    <div>
        <h2>GST Invoice</h2>
        <p>Generate GST invoices with CGST / SGST / IGST breakdown</p>
    </div>
    <span class="badge badge-info" id="gstBillNoDisplay">Invoice No: —</span>
</div>

<div class="billing-layout">
    <!-- Left -->
    <div>
        <!-- Customer Details -->
        <div class="card mb-16" style="margin-bottom:16px">
            <div class="card-header"><span class="card-title">Customer Details</span></div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Customer Name <span style="color:#dc2626">*</span></label>
                        <input type="text" id="gstCustomerName" class="form-control"
                            placeholder="Company / Individual name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">GSTIN</label>
                        <input type="text" id="gstCustomerGSTIN" class="form-control" placeholder="22ABCDE1234F1Z5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" id="gstCustomerAddress" class="form-control" placeholder="Customer address">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" id="gstCustomerState" class="form-control" placeholder="e.g. Tamil Nadu">
                    </div>
                </div>
                <div class="d-flex align-center gap-8" style="margin-top:4px">
                    <input type="checkbox" id="interStateToggle" onchange="toggleInterState()">
                    <label for="interStateToggle"
                        style="margin-bottom:0;font-size:.85rem;font-weight:500;cursor:pointer">
                        Inter-State Supply (IGST applies instead of CGST+SGST)
                    </label>
                </div>
            </div>
        </div>

        <!-- Product Entry -->
        <div class="card mb-16" style="margin-bottom:16px">
            <div class="card-body">
                <label class="form-label"><i class="bi bi-upc-scan"></i> Scan Barcode / Search</label>
                <div class="input-group">
                    <input type="text" id="gstBarcodeInput" class="form-control" placeholder="Scan or type product...">
                    <button class="btn btn-primary" onclick="gstSearchBarcode()"><i class="bi bi-search"></i></button>
                </div>
                <div id="gstSearchResults" style="display:none;margin-top:8px">
                    <div class="table-wrap"
                        style="max-height:200px;overflow-y:auto;border:1px solid var(--border);border-radius:6px">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>GST%</th>
                                    <th>Stock</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="gstSearchBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Invoice Items</span>
                <button class="btn btn-ghost btn-sm" onclick="clearGSTCart()"><i class="bi bi-trash"></i></button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>HSN</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Taxable</th>
                            <th>CGST</th>
                            <th>SGST</th>
                            <th>IGST</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="gstCartBody">
                        <tr id="gstEmptyCart">
                            <td colspan="11" class="text-center text-muted" style="padding:28px">
                                <i class="bi bi-file-earmark"
                                    style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.3"></i>
                                No items — scan a product to start
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Invoice Summary -->
    <div class="bill-summary card">
        <div class="bill-summary-head"><i class="bi bi-file-earmark-ruled"></i> Invoice Summary</div>
        <div class="bill-summary-body">
            <div class="summary-row"><span>Taxable Amount</span><span id="gstTaxable">₹0.00</span></div>
            <div class="summary-row" id="cgstRow"><span>CGST</span><span id="gstCGST">₹0.00</span></div>
            <div class="summary-row" id="sgstRow"><span>SGST</span><span id="gstSGST">₹0.00</span></div>
            <div class="summary-row" id="igstRow" style="display:none"><span>IGST</span><span id="gstIGST">₹0.00</span>
            </div>
            <div class="summary-row"><span>Total GST</span><span id="gstTotalGST">₹0.00</span></div>
            <div class="summary-row">
                <span>Discount</span>
                <input type="number" id="gstDiscountInput" value="0" min="0" class="form-control"
                    style="width:70px;padding:4px 8px;font-size:.82rem" onchange="gstRecalculate()">
            </div>
            <div class="summary-row total"><span>Final Amount</span><span id="gstFinal">₹0.00</span></div>

            <div style="margin-top:14px;margin-bottom:14px">
                <label class="form-label">Payment Mode</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">
                    <button class="payment-btn active" data-mode="Cash" onclick="selectGSTPayment(this)">Cash</button>
                    <button class="payment-btn" data-mode="Card" onclick="selectGSTPayment(this)">Card</button>
                    <button class="payment-btn" data-mode="UPI" onclick="selectGSTPayment(this)">UPI</button>
                    <button class="payment-btn" data-mode="Credit" onclick="selectGSTPayment(this)">Credit</button>
                </div>
            </div>

            <button class="btn btn-success btn-w-full" onclick="createGSTBill()">
                <i class="bi bi-check-circle"></i> Generate GST Invoice
            </button>
            <button class="btn btn-outline btn-w-full" style="margin-top:8px" onclick="printGSTInvoice()">
                <i class="bi bi-printer"></i> Print Invoice
            </button>
        </div>
    </div>
</div>

<iframe id="gstPrintFrame" style="display:none"></iframe>
<?php include BASE_PATH . '/views/layouts/footer.php'; ?>