<?php
/**
 * JSON Response Helper - PHP 5.6 compatible
 */
class Response
{
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success($data = null, $message = 'Success')
    {
        self::json(array(
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c'),
        ));
    }

    public static function error($message = 'Error', $statusCode = 400, $errors = null)
    {
        self::json(array(
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c'),
        ), $statusCode);
    }

    public static function notFound($message = 'Resource not found')
    {
        self::error($message, 404);
    }

    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, 401);
    }

    public static function validationError($errors)
    {
        self::error('Validation failed', 422, $errors);
    }
}
