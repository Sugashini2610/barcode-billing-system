<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="section-header">
    <div>
        <h2>New Bill</h2>
        <p>Scan barcode or search products to create a bill</p>
    </div>
    <span class="badge badge-primary" id="billNoDisplay">Bill No: —</span>
</div>

<div class="billing-layout">
    <!-- Left: Product Entry -->
    <div>
        <!-- Barcode Scan -->
        <div class="card mb-16" style="margin-bottom:16px">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><i class="bi bi-upc-scan"></i> Scan Barcode / Search Product</label>
                    <div class="input-group">
                        <input type="text" id="barcodeInput" class="form-control"
                            placeholder="Scan barcode or type product name..." autofocus>
                        <button class="btn btn-primary" onclick="searchBarcode()"><i class="bi bi-search"></i></button>
                    </div>
                    <div class="form-text">Press Enter or click Search after scanning</div>
                </div>

                <!-- Product Search Results -->
                <div id="productSearchResults" style="display:none;margin-top:8px">
                    <div class="table-wrap"
                        style="max-height:200px;overflow-y:auto;border:1px solid var(--border);border-radius:6px">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Barcode</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="searchResultsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="bi bi-cart3"></i> Cart Items</span>
                <button class="btn btn-ghost btn-sm" onclick="clearCart()"><i class="bi bi-trash"></i> Clear</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>GST</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartBody">
                        <tr id="emptyCart">
                            <td colspan="7" class="text-center text-muted" style="padding:28px">
                                <i class="bi bi-cart"
                                    style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.3"></i>
                                Cart is empty — scan a barcode to add products
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Bill Summary -->
    <div class="bill-summary card">
        <div class="bill-summary-head"><i class="bi bi-receipt-cutoff"></i> Bill Summary</div>
        <div class="bill-summary-body">

            <!-- Customer -->
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">Customer Name</label>
                <input type="text" id="customerName" class="form-control" placeholder="Walk-in Customer"
                    value="Walk-in Customer">
            </div>
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Customer Phone</label>
                <input type="text" id="customerPhone" class="form-control" placeholder="Optional">
            </div>

            <!-- Totals -->
            <div class="summary-row">
                <span>Subtotal</span><span id="subTotal">₹0.00</span>
            </div>
            <div class="summary-row">
                <span>GST</span><span id="gstTotal">₹0.00</span>
            </div>
            <div class="summary-row">
                <span>Discount</span>
                <div class="d-flex align-center gap-8">
                    <input type="number" id="discountInput" class="form-control" value="0" min="0"
                        style="width:70px;padding:4px 8px;font-size:.82rem" onchange="recalculate()">
                </div>
            </div>
            <div class="summary-row">
                <span>Net Total</span><span id="netTotal">₹0.00</span>
            </div>
            <div class="summary-row total">
                <span>Round Off Total</span><span id="finalTotal">₹0.00</span>
            </div>

            <!-- Payment Mode -->
            <div style="margin-top:14px; margin-bottom:14px">
                <label class="form-label">Payment Mode</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">
                    <button class="payment-btn active" data-mode="Cash" onclick="selectPayment(this)">Cash</button>
                    <button class="payment-btn" data-mode="Card" onclick="selectPayment(this)">Card</button>
                    <button class="payment-btn" data-mode="UPI" onclick="selectPayment(this)">UPI</button>
                    <button class="payment-btn" data-mode="Credit" onclick="selectPayment(this)">Credit</button>
                </div>
            </div>

            <button class="btn btn-success btn-w-full" onclick="createBill()">
                <i class="bi bi-check-circle"></i> Generate Bill
            </button>
            <button class="btn btn-outline btn-w-full" style="margin-top:8px" onclick="printLastBill()">
                <i class="bi bi-printer"></i> Print Last Bill
            </button>
        </div>
    </div>
</div>

<!-- Print Frame -->
<iframe id="printFrame" style="display:none"></iframe>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>