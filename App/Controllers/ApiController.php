<?php
namespace App\Controllers;

use Core\Request;
use Core\Response;

abstract class ApiController
{
    protected function validate(Request $request, array $rules): array
    {
        $data = $request->all();
        $errors = [];

        foreach ($rules as $field => $rule) {
            $rulesParts = explode('|', $rule);

            foreach ($rulesParts as $rulePart) {
                if ($rulePart === 'required') {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        $errors[$field][] = "The {$field} field is required.";
                    }
                } elseif (strpos($rulePart, 'min:') === 0) {
                    $min = substr($rulePart, 4);

                    if (isset($data[$field]) && strlen($data[$field]) < $min) {
                        $errors[$field][] = "The {$field} field must be at least {$min} characters.";
                    }
                } elseif (strpos($rulePart, 'max:') === 0) {
                    $max = substr($rulePart, 4);

                    if (isset($data[$field]) && strlen($data[$field]) > $max) {
                        $errors[$field][] = "The {$field} field may not be greater than {$max} characters.";
                    }
                } elseif ($rulePart === 'email') {
                    if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "The {$field} field must be a valid email address.";
                    }
                } elseif ($rulePart === 'numeric') {
                    if (isset($data[$field]) && !is_numeric($data[$field])) {
                        $errors[$field][] = "The {$field} field must be a number.";
                    }
                }
            }
        }

        return $errors;
    }

    protected function validateRequest(Request $request, array $rules): ?Response
    {
        $errors = $this->validate($request, $rules);

        if(!empty($errors)) {
            return Response::badRequest([
                'status' => 'error',
                'message' =>'Validation failed',
                'errors' => $errors
            ]);
        }

        return null;
    }

    protected function respondWithPagination(array $items, int $total, int $perPage, int $page): Response
    {
        $lastPage = ceil($total / $perPage);

        return Response::ok([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }
}
