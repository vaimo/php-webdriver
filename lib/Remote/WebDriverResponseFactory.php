<?php
// Copyright 2004-present Facebook. All Rights Reserved.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

namespace Facebook\WebDriver\Remote;

use Facebook\WebDriver\Exception\WebDriverException;

class WebDriverResponseFactory
{
    /**
     * @param mixed $result
     * @param WebDriverDialect|null $dialect
     * @throws WebDriverException
     * @return WebDriverResponse
     */
    public static function create($result, WebDriverDialect $dialect = null)
    {
        if (null === $dialect) {
            $dialect = WebDriverDialect::guessByNewSessionResultBody($result);
        }
        self::checkExecutorResult($dialect, $result);

        return $dialect->isW3C()
            ? self::createW3CProtocol($result)
            : self::createJsonWireProtocol($result);
    }

    /**
     * @param WebDriverDialect $dialect
     * @param mixed $result
     * @throws WebDriverException
     */
    public static function checkExecutorResult(WebDriverDialect $dialect, $result)
    {
        if (!\is_array($result)) {
            throw new WebDriverException('Invalid result state');
        }
        if ($dialect->isW3C()) {
            if (!empty($result['value']['error'])) {
                WebDriverException::throwExceptionForW3c($result['value']['error'], $result);
            }
        } else {
            $status = !empty($result['status']) ? $result['status'] : null;
            if (is_numeric($result['status']) && $result['status'] > 0) {
                WebDriverException::throwException(
                    $status,
                    !empty($result['message']) ? $result['message'] : null,
                    $result
                );
            }
        }
    }

    /**
     * @param mixed $results
     * @throws WebDriverException
     * @return WebDriverResponse
     */
    private static function createJsonWireProtocol($results)
    {
        if (!isset($results['status'])) {
            return null;
        }

        $value = null;
        if (is_array($results) && array_key_exists('value', $results)) {
            $value = $results['value'];
        }

        $sessionId = null;
        if (is_array($results) && array_key_exists('sessionId', $results)) {
            $sessionId = $results['sessionId'];
        }

        $status = $results['status'];
        if ($status !== 0) {
            $message = null;
            if (is_array($value) && array_key_exists('message', $value)) {
                $message = $value['message'];
            }
            WebDriverException::throwException($status, $message, $results);
        }

        $response = new WebDriverResponse($sessionId);

        return $response
            ->setStatus($status)
            ->setValue($value);
    }

    /**
     * @param mixed $results
     * @throws WebDriverException
     * @return WebDriverResponse
     */
    private static function createW3CProtocol($results)
    {
        $value = $results['value'];

        $sessionId = null;
        if (is_array($value)) {
            if (!empty($value['sessionId'])) {
                $sessionId = $value['sessionId'];
                unset($value['sessionId']);
            } elseif (!empty($results['sessionId'])) {
                $sessionId = $results['sessionId'];
            }

            if (!empty($value['capabilities'])) {
                $value = $value['capabilities'];
            } elseif (null !== $sessionId && empty($value['moz:profile'])) {
                return null;
            }
        }

        if (!empty($value['error'])) {
            WebDriverException::throwExceptionForW3c($value['error'], $results);
        }

        $response = new WebDriverResponse($sessionId);

        return $response
            ->setValue($value);
    }
}
