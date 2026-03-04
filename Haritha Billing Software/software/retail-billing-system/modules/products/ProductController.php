<?php
require_once BASE_PATH . '/modules/products/ProductService.php';
require_once BASE_PATH . '/core/response.php';
require_once BASE_PATH . '/core/validator.php';

/**
 * ProductController - PHP 5.6 compatible
 */
class ProductController
{
    private $service;

    public function __construct()
    {
        $this->service = new ProductService();
    }

    public function index()
    {
        try {
            $products = $this->service->getAll();
            foreach ($products as &$p) {
                $p['Stock_Status'] = getStockStatus((int) $p['Quantity']);
                $p['Barcode_Image'] = BarcodeService::getBarcodeDataURI($p['Barcode']);
            }
            Response::success($products, count($products) . ' products found');
        } catch (Exception $e) {
            logMessage('ProductController::index - ' . $e->getMessage(), 'ERROR');
            Response::error($e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $product = $this->service->getById($id);
            if (!$product) {
                Response::notFound("Product not found: $id");
                return;
            }
            $product['Stock_Status'] = getStockStatus((int) $product['Quantity']);
            $product['Barcode_Image'] = BarcodeService::getBarcodeDataURI($product['Barcode']);
            Response::success($product);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function findByBarcode()
    {
        try {
            $barcode = sanitize(isset($_GET['barcode']) ? $_GET['barcode'] : '');
            if (empty($barcode)) {
                Response::error('Barcode is required');
                return;
            }
            $product = $this->service->getByBarcode($barcode);
            if (!$product) {
                Response::notFound('Product not found for barcode: ' . $barcode);
                return;
            }
            $product['Stock_Status'] = getStockStatus((int) $product['Quantity']);
            Response::success($product);
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function store()
    {
        try {
            // Prefer JSON body (API.post sends JSON for plain objects)
            $rawInput = file_get_contents('php://input');
            $data = null;
            if ($rawInput) {
                $decoded = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                }
            }
            if (empty($data)) {
                $data = $_POST;
            }

            $validator = Validator::make($data, array(
                'name' => 'required|min:2|max:100',
                'price' => 'required|numeric|positive',
                'quantity' => 'required|numeric',
            ));

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            $result = $this->service->create($data);
            Response::success($result, 'Product created successfully');
        } catch (Exception $e) {
            logMessage('ProductController::store - ' . $e->getMessage(), 'ERROR');
            Response::error($e->getMessage());
        }
    }

    /**
     * storeLabel — save a barcode-generator label product with a user-supplied barcode.
     * Called from the Generate Barcode page via API POST products/store-label
     */
    public function storeLabel()
    {
        try {
            // Prefer JSON body (API.post sends JSON when data is a plain object)
            $rawInput = file_get_contents('php://input');
            $data = null;

            if ($rawInput) {
                $decoded = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                }
            }

            // Fall back to $_POST (multipart/form-data or urlencoded)
            if (empty($data)) {
                $data = $_POST;
            }

            // Log what we received for debugging
            logMessage('storeLabel received name=' . (isset($data['name']) ? $data['name'] : 'MISSING') . ' barcode=' . (isset($data['barcode']) ? $data['barcode'] : 'MISSING'), 'DEBUG');

            // Basic validation
            if (empty($data['name'])) {
                Response::error('Product name is required');
                return;
            }
            if (empty($data['barcode'])) {
                Response::error('Barcode is required');
                return;
            }
            if (!preg_match('/^\d{13}$/', $data['barcode'])) {
                Response::error('Barcode must be a 13-digit EAN-13 number');
                return;
            }

            // Check if barcode already exists — update price/GST if so
            $existing = $this->service->getByBarcode($data['barcode']);
            if ($existing) {
                $updateData = array(
                    'name' => sanitize($data['name']),
                    'category' => sanitize(isset($data['category']) ? $data['category'] : $existing['Category']),
                    'price' => (float) (isset($data['price']) ? $data['price'] : $existing['Price']),
                    'gst_percent' => (float) (isset($data['gst_percent']) ? $data['gst_percent'] : $existing['GST_Percent']),
                    'unit' => sanitize(isset($data['unit']) ? $data['unit'] : $existing['Unit']),
                    'description' => 'Generated label product',
                );
                $this->service->update($existing['ID'], $updateData);
                Response::success(array('barcode' => $data['barcode']), 'Label product updated successfully');
                return;
            }

            $result = $this->service->createWithBarcode($data);
            Response::success($result, 'Label product saved successfully');
        } catch (Exception $e) {
            logMessage('ProductController::storeLabel - ' . $e->getMessage(), 'ERROR');
            Response::error($e->getMessage());
        }
    }

    public function update($id)
    {
        try {
            $rawInput = file_get_contents('php://input');
            $data = null;
            if ($rawInput) {
                $decoded = json_decode($rawInput, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data = $decoded;
                }
            }
            if (empty($data)) {
                $data = $_POST;
            }
            $result = $this->service->update($id, $data);
            if ($result) {
                Response::success(null, 'Product updated successfully');
            } else {
                Response::error('Failed to update product');
            }
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $result = $this->service->delete($id);
            if ($result) {
                Response::success(null, 'Product deleted successfully');
            } else {
                Response::notFound('Product not found');
            }
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }
}
