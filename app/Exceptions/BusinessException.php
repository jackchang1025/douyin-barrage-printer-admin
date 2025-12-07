<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * 业务异常基类
 * 
 * 所有业务相关的异常都应该继承此类，
 * 异常会自动被转换为 JSON 响应返回给客户端。
 */
abstract class BusinessException extends Exception
{
    /**
     * HTTP 状态码
     */
    protected int $httpCode = 400;

    /**
     * 错误码（用于前端识别具体错误类型）
     */
    protected string $errorCode = 'BUSINESS_ERROR';

    /**
     * 额外的错误数据
     */
    protected array $data = [];

    /**
     * 渲染异常为 HTTP 响应
     */
    public function render(): JsonResponse
    {
        $response = [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ];

        if (!empty($this->data)) {
            $response['data'] = $this->data;
        }

        return response()->json($response, $this->httpCode);
    }

    /**
     * 是否应该报告此异常（记录日志）
     * 
     * 业务异常默认不记录日志，子类可以覆盖此方法
     */
    public function report(): bool
    {
        return false;
    }

    /**
     * 设置额外数据
     */
    public function withData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取 HTTP 状态码
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * 获取错误码
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}

