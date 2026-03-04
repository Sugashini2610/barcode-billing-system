<?php include BASE_PATH . '/views/layouts/header.php'; ?>

<div class="section-header">
    <div>
        <h2><i class="bi bi-upc-scan" style="color:#7c3aed;"></i> Generate Barcode</h2>
        <p>Create barcode sticker labels for your products. Saved barcodes can be scanned directly on the billing
            screen.</p>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     BARCODE GENERATOR FORM CARD
═══════════════════════════════════════════════ -->
<div class="card mb-24">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-upc" style="color:#7c3aed;"></i> Label Details
        </span>
        <span style="font-size:.78rem;color:#64748b;">Fill in product info → Generate → Save to Products → Print
            stickers</span>
    </div>

    <!-- Info banner -->
    <div
        style="margin:0 0 0;padding:10px 24px;background:#ede9fe;border-bottom:1px solid #ddd6fe;font-size:.81rem;color:#5b21b6;display:flex;align-items:center;gap:8px;">
        <i class="bi bi-info-circle-fill"></i>
        <span>When you click <strong>Save to Products</strong>, the barcode is added to your Google Sheets product
            database. Scanning it on the billing screen will then auto-fill the product details.</span>
    </div>

    <div style="padding:24px;">

        <!-- Row 1: Product Name + Price + GST + Unit + Copies -->
        <div
            style="display:grid;grid-template-columns:2.5fr 1fr 1fr 1fr 1fr;gap:16px;align-items:end;margin-bottom:16px;">
            <div>
                <label class="form-label">Product Name <span style="color:#dc2626;">*</span></label>
                <input type="text" id="bcProductName" class="form-control" placeholder="e.g. Tata Salt 1 kg"
                    oninput="bcPreviewUpdate()">
            </div>
            <div>
                <label class="form-label">Price (₹) <span style="color:#dc2626;">*</span></label>
                <input type="number" id="bcPrice" class="form-control" placeholder="0.00" step="0.01" min="0"
                    oninput="bcPreviewUpdate()">
            </div>
            <div>
                <label class="form-label">GST %</label>
                <select id="bcGst" class="form-control" onchange="bcPreviewUpdate()">
                    <option value="0">No GST (0%)</option>
                    <option value="5">5%</option>
                    <option value="12">12%</option>
                    <option value="18" selected>18%</option>
                    <option value="28">28%</option>
                </select>
            </div>
            <div>
                <label class="form-label">Unit</label>
                <select id="bcUnit" class="form-control" onchange="bcPreviewUpdate()">
                    <option value="Piece">Piece</option>
                    <option value="Kg">Kg</option>
                    <option value="Gram">Gram</option>
                    <option value="Litre">Litre</option>
                    <option value="ML">ML</option>
                    <option value="Pack">Pack</option>
                    <option value="Box">Box</option>
                    <option value="Dozen">Dozen</option>
                </select>
            </div>
            <div>
                <label class="form-label">Copies</label>
                <input type="number" id="bcCopies" class="form-control" value="1" min="1" max="500">
            </div>
        </div>

        <!-- Row 2: Category + Barcode No + Label Size + Checkboxes -->
        <div style="display:grid;grid-template-columns:1fr 1.2fr 1fr 1fr;gap:16px;align-items:end;margin-bottom:24px;">
            <div>
                <label class="form-label">Category</label>
                <input type="text" id="bcCategory" class="form-control" placeholder="e.g. Groceries, FMCG…">
            </div>
            <div>
                <label class="form-label">
                    Barcode Number
                    <span style="font-size:.72rem;color:#94a3b8;">(blank = auto-generate)</span>
                </label>
                <input type="text" id="bcCustomNumber" class="form-control" placeholder="13-digit EAN-13 or leave blank"
                    maxlength="13" oninput="bcPreviewUpdate()">
            </div>
            <div>
                <label class="form-label">Label Size</label>
                <select id="bcLabelSize" class="form-control" onchange="bcPreviewUpdate()">
                    <option value="small">Small (2×1 inch)</option>
                    <option value="medium" selected>Medium (3×1.5 inch)</option>
                    <option value="large">Large (4×2 inch)</option>
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;padding-top:24px;">
                <label style="display:flex;align-items:center;gap:7px;font-size:.84rem;cursor:pointer;">
                    <input type="checkbox" id="bcShowMrp" checked onchange="bcPreviewUpdate()"
                        style="width:15px;height:15px;">
                    Show MRP on label
                </label>
                <label style="display:flex;align-items:center;gap:7px;font-size:.84rem;cursor:pointer;">
                    <input type="checkbox" id="bcShowGst" checked onchange="bcPreviewUpdate()"
                        style="width:15px;height:15px;">
                    Show GST on label
                </label>
            </div>
        </div>

        <!-- Row 3: Preview + action buttons -->
        <div style="display:grid;grid-template-columns:1fr auto;gap:24px;align-items:start;">

            <!-- Live Preview -->
            <div>
                <div
                    style="font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">
                    Live Preview
                </div>
                <div id="bcPreviewArea"
                    style="background:#f8fafc;border:2px dashed #c4b5fd;border-radius:12px;padding:28px;min-height:130px;display:flex;align-items:center;justify-content:center;transition:border-color .2s;">
                    <p style="color:#94a3b8;font-size:.84rem;text-align:center;">
                        <i class="bi bi-upc" style="font-size:1.6rem;display:block;margin-bottom:6px;opacity:.3;"></i>
                        Fill in product name &amp; price to see the label preview
                    </p>
                </div>
                <div style="font-size:.74rem;color:#94a3b8;margin-top:8px;display:flex;align-items:center;gap:5px;">
                    <i class="bi bi-info-circle"></i>
                    Barcode number shown in preview is a placeholder until you click Generate.
                </div>
            </div>

            <!-- Action buttons -->
            <div style="display:flex;flex-direction:column;gap:10px;min-width:180px;padding-top:28px;">
                <button class="btn btn-primary" onclick="bcGenerate()" id="bcGenerateBtn">
                    <i class="bi bi-upc"></i>&nbsp; Generate Barcode
                </button>
                <button class="btn btn-success" onclick="bcSaveProduct()" id="bcSaveBtn" disabled
                    title="Save to Google Sheets Products — enables scan-to-fill on billing screen">
                    <i class="bi bi-cloud-upload"></i>&nbsp; Save to Products
                </button>
                <button class="btn" onclick="bcPrint()" id="bcPrintBtn" disabled style="background:#7c3aed;color:#fff;">
                    <i class="bi bi-printer"></i>&nbsp; Print Labels
                </button>
                <button class="btn btn-outline" onclick="bcReset()" style="color:#64748b;">
                    <i class="bi bi-arrow-counterclockwise"></i>&nbsp; Reset
                </button>
            </div>
        </div>

    </div><!-- /padding -->
</div><!-- /generator card -->

<!-- ══════════════════════════════════════════════
     SAVED PRODUCTS RECENTLY GENERATED
═══════════════════════════════════════════════ -->
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <i class="bi bi-bookmark-star" style="color:#7c3aed;"></i> Recently Generated Label Products
        </span>
        <button class="btn btn-outline btn-sm" onclick="bcLoadSaved()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
    <div id="bcSavedWrap" style="padding:16px 24px;">
        <p style="color:#94a3b8;font-size:.84rem;">Loading saved products…</p>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>