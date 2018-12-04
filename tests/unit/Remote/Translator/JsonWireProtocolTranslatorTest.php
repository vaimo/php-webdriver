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

namespace Facebook\WebDriver\Remote\Translator;

use Facebook\WebDriver\Remote\DriverCommand;
use Facebook\WebDriver\Remote\ExecutableWebDriverCommand;
use Facebook\WebDriver\Remote\WebDriverCommand;
use PHPUnit\Framework\TestCase;

class JsonWireProtocolTranslatorTest extends TestCase
{
    public function testShouldTranslateElement()
    {
        $expectedElementId = 'uuid-3423dsa-sdfsd';

        $systemUnderTest = new JsonWireProtocolTranslator();
        $this->assertEquals($expectedElementId, $systemUnderTest->translateElement(['ELEMENT' => $expectedElementId]));
    }

    public function testShouldTranslateCommand()
    {
        $command = new WebDriverCommand(
            $sessionId = 'session-ID-890',
            $name = DriverCommand::FIND_ELEMENT,
            $parameters = [
                'ELEMENT' => 'element-ID-123',
            ]
        );

        $systemUnderTest = new JsonWireProtocolTranslator();
        $translatedCommand = $systemUnderTest->translateCommand($command);
        $this->assertInstanceOf(ExecutableWebDriverCommand::class, $translatedCommand);
    }

    public function testShouldThrowExceptionForNotValidCommand()
    {
        $this->expectException(\InvalidArgumentException::class);

        $command = new WebDriverCommand(
            $sessionId = 'session-ID-890',
            $name = 'some_not_valid_command',
            $parameters = [
                'ELEMENT' => 'element-ID-123',
            ]
        );

        $systemUnderTest = new JsonWireProtocolTranslator();
        $systemUnderTest->translateCommand($command);
    }

    /**
     * @dataProvider getParametersDataProvider
     * @param string $commandName
     * @param array $params
     */
    public function testShouldTranslateParameters($commandName, $params)
    {
        $systemUnderTest = new JsonWireProtocolTranslator();
        $this->assertNotEquals($params, $systemUnderTest->translateParameters($commandName, $params));
    }

    /**
     * @return array
     */
    public function getParametersDataProvider()
    {
        return [
            DriverCommand::SEND_KEYS_TO_ELEMENT => [
                DriverCommand::SEND_KEYS_TO_ELEMENT,
                [
                    'value' => 'Test text',
                ],
            ],
            DriverCommand::SEND_KEYS_TO_ACTIVE_ELEMENT => [
                DriverCommand::SEND_KEYS_TO_ACTIVE_ELEMENT,
                [
                    'value' => 'Test text',
                ],
            ],
        ];
    }
}
