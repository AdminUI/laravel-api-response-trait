<?php
/**
 * User: Bawa, Lakhveer
 * Email: iamdeep.dhaliwal@gmail.com
 * Date: 2020-06-14
 * Time: 12:18 p.m.
 */

namespace AdminUI\AdminUIAdmin\Traits;

use Illuminate\Support\Arr;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    /**
     * Return generic json response with the given data.
     *
     * @param       $data
     * @param int $statusCode
     * @param array $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function apiResponse($data = [], $statusCode = 200, $headers = [])
    {
        // This bit is a little to hacky but acheives what it needs to
        if (!isset($data['result'])) {
            $data['result'] = (object)[];
        }
        if (!isset($data['result']->meta)) {
            $data['result']->meta = (object)[];
        }

        // Add the meta data to every request
        $data['result']->meta->company = [
            'website' => env('APP_URL', 'AdminUI Api'),
            'email'   => env('MAIL_FROM_ADDRESS', 'api@adminui.co.uk'),
        ];
        $data['result']->meta->api = [
            'version' => config('adminui.api-version', '1.0.1'),
            'author'  => 'https://www.adminui.co.uk',
            'email'   => 'support@adminui.co.uk',
        ];

        // Build response structure
        $responseStructure = [
            'status'  => $data['success'] ?? 'failed',
            'message' => $data['message'] ?? null,
            'data'    => $data['result']->data ?? null,
            'links'   => $data['result']->links ?? null,
            'meta'    => $data['result']->meta ?? null,
        ];
        // Are there any errors
        if (isset($data['errors'])) {
            $responseStructure['errors'] = $data['errors'];
        }
        // Get status code
        if (isset($data['status'])) {
            $statusCode = $data['status'];
        }
        // Is there any exceptions set
        if (isset($data['exception']) && ($data['exception'] instanceof \Error || $data['exception'] instanceof \Exception)) {
            // restrict what is sent back to avoid displaying whole stack
            $responseStructure['exception'] = [
                'message' => $data['exception']->getMessage(),
                'file'    => $data['exception']->getFile(),
                'line'    => $data['exception']->getLine(),
                'code'    => $data['exception']->getCode()
            ];
            // Set status code of 500
            if ($statusCode === 200) {
                $statusCode = 500;
            }
        }

        // if success is not set throw error code (from handler)
        if ($data['success'] === false) {
            if (isset($data['error_code'])) {
                $responseStructure['error_code'] = $data['error_code'];
            } else {
                $responseStructure['error_code'] = 1;
            }
        }

        // return the json
        return response()->json(
            $responseStructure, $statusCode, $headers
        );
    }

    /*
     * For a single resource result
     * Just a wrapper to facilitate abstract
     */
    protected function respondWithResource(JsonResource $resource, $message = null, $statusCode = 200, $headers = [])
    {
        return $this->apiResponse(
            [
                'success' => 'success',
                'result'  => $resource->response()->getData(),
                'message' => $message
            ], $statusCode, $headers
        );
    }

    /*
     * For a collection result
     * Just a wrapper to facilitate abstract
     */
    protected function respondWithResourceCollection(ResourceCollection $resourceCollection, $message = null, $statusCode = 200, $headers = [])
    {
        return $this->apiResponse(
            [
                'success' => 'success',
                'result'  => $resourceCollection->response()->getData(),
                'message' => $message
            ], $statusCode, $headers
        );
    }

    /**
     * Respond with error.
     *
     * @param $message
     * @param int $statusCode
     *
     * @param \Exception|null $exception
     * @param bool|null $error_code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondError($message, int $statusCode = 400, \Exception $exception = null, int $error_code = 1)
    {
        return $this->apiResponse(
            [
                'success'    => 'failed',
                'message'    => $message ?? null,
                'exception'  => $exception,
                'error_code' => $error_code
            ], $statusCode
        );
    }

    /**
     * Respond with success.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondSuccess($message = '')
    {
        return $this->apiResponse(['success' => 'success', 'message' => $message]);
    }

    /**
     * Respond with created.
     *
     * @param $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondCreated($data)
    {
        return $this->apiResponse($data, 201);
    }

    /**
     * Respond with no content.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondNoContent($message = 'No Content Found')
    {
        return $this->apiResponse(['success' => 'success', 'message' => $message], 200);
    }

    /**
     * Respond with unauthorized.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondUnAuthorized($message = 'Unauthorized')
    {
        return $this->respondError($message, 401);
    }

    /**
     * Respond with forbidden.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondForbidden($message = 'Forbidden')
    {
        return $this->respondError($message, 403);
    }

    /**
     * Respond with not found.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondNotFound($message = 'Not Found')
    {
        return $this->respondError($message, 404);
    }

    // /**
    //  * Respond with failed login.
    //  *
    //  * @return \Illuminate\Http\JsonResponse
    //  */
    // protected function respondFailedLogin()
    // {
    //     return $this->apiResponse([
    //         'errors' => [
    //             'email or password' => 'is invalid',
    //         ]
    //     ], 422);
    // }

    /**
     * Respond with internal error.
     *
     * @param string $message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondInternalError($message = 'Internal Error')
    {
        return $this->respondError($message, 500);
    }
}
