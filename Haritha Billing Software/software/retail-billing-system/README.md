# Haritha Billing Software - Retail Billing System

A comprehensive **Shop Billing & Inventory Management System** built with PHP (modular architecture) using **Google Sheets as the primary cloud database**.

---

## 🚀 Quick Start

### 1. Prerequisites
- **WAMP/XAMPP** with PHP 8.1+
- **PHP extensions**: `openssl`, `curl`, `json`
- **Google Cloud** project with Sheets API enabled

### 2. Setup Google Sheets API

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project → Enable **Google Sheets API**
3. Go to **IAM & Admin → Service Accounts** → Create a service account
4. Download the JSON key as `config/credentials.json`
5. Create a new **Google Spreadsheet** and share it with the service account email
6. Create these sheets inside the spreadsheet:
   - `Products`
   - `Bills`
   - `GST_Bills`
   - `Stock_Log`

### 3. Configure Environment

Edit `.env`:
```env
GOOGLE_SPREADSHEET_ID=your_spreadsheet_id_here
GOOGLE_CLIENT_EMAIL=your-service-account@project.iam.gserviceaccount.com
COMPANY_NAME=Your Store Name
COMPANY_GSTIN=YOUR_GSTIN_NUMBER
```

### 4. Access the System

Open: `http://localhost/Haritha%20Billing%20Software/software/retail-billing-system/`

**Default Login:**
- Username: `admin`
- Password: `admin@123`

---

## 📁 Project Structure

```
retail-billing-system/
├── index.php              # Front controller
├── .htaccess              # URL routing
├── .env                   # Environment config
│
├── config/                # App & Google config
├── core/                  # Auth, Session, Router, Helpers
├── services/              # GoogleSheetsService, BarcodeService
├── modules/               # All business logic modules
│   ├── products/
│   ├── billing/
│   ├── gst/
│   ├── stock/
│   ├── dashboard/
│   └── reports/
├── api/                   # AJAX entry point
├── views/                 # PHP templates
└── public/                # CSS, JS, assets
```

---

## 🔄 Data Flow

```
User Action → AJAX → api/api.php → Router → Controller → Service → GoogleSheetsService → Google Sheet
```

---

## 📊 Google Sheets Structure

| Sheet | Columns |
|-------|---------|
| Products | ID, Name, Category, Price, GST_Percent, Barcode, Quantity, Unit, Description, Created_At, Updated_At |
| Bills | Bill_No, Date, Customer_Name, Customer_Phone, Items_JSON, Subtotal, GST_Amount, Discount, Net_Total, Round_Off, Final_Amount, Payment_Mode, Bill_Type, Status, Created_At |
| GST_Bills | GST_Bill_No, Date, Customer_Name, Customer_GSTIN, Customer_Address, Customer_State, Items_JSON, Taxable_Amount, CGST_Amount, SGST_Amount, IGST_Amount, Total_GST, Discount, Net_Total, Round_Off, Final_Amount, Payment_Mode, Inter_State, Status, Created_At |
| Stock_Log | Log_ID, Product_ID, Product_Name, Change, Type, Note, Created_At |

---

## ✨ Features

- ✅ **Barcode Generation** — Auto-generated EAN-13 barcodes (no external library)
- ✅ **Barcode Scanning** — Real-time barcode lookup via keyboard input
- ✅ **Normal Billing** — Customer details, multiple products, GST, round-off
- ✅ **GST Invoice** — CGST/SGST/IGST split, inter-state support
- ✅ **Stock Management** — Auto stock reduction on billing, manual adjustments, log
- ✅ **Dashboard** — Sales trend (Chart.js), top products, stock alerts
- ✅ **Reports** — Monthly, date-range, product-wise
- ✅ **Print** — Bill receipt, GST invoice, reports
- ✅ **Dark/Light Theme** — Toggle in topbar
- ✅ **Responsive** — Works on mobile and desktop

---

## 🔧 Configuration Files

| File | Purpose |
|------|---------|
| `.env` | All secrets and app settings |
| `config/app.php` | Constants from .env |
| `config/google_config.php` | Google API credentials setup |
| `config/constants.php` | System-wide constants |

---

## 🏗️ Architecture

- **No MySQL** — Pure Google Sheets as database
- **Service Account Auth** — JWT-based OAuth 2.0 (no browser flow needed)
- **Controller → Service → GoogleSheetsService** pattern
- **AJAX-first** frontend with JSON API
- **Modular** — Each feature is a self-contained module

---

## 🖥️ Desktop Conversion

This system is built for easy conversion to a desktop app using:
- **Electron.js** (wrap in browser window)
- **NW.js** (Node-native packaging)
- Keep the PHP backend running locally via WAMP

---

*Built for Haritha Stores — © 2024-2025*
