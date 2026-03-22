<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  API
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

namespace CoCoCo\Component\Balancirk\Tests\Unit\Api\Controller;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Joomla\CMS\Factory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Input\Input;
use CoCoCo\Component\Balancirk\Api\Controller\LessonsController;

/**
 * Test class for LessonsController
 *
 * @since  0.0.1
 */
class LessonsControllerTest extends TestCase
{
    /**
     * @var LessonsController
     */
    protected $controller;

    /**
     * @var CMSApplication|MockObject
     */
    protected $mockApp;

    /**
     * @var Input|MockObject
     */
    protected $mockInput;

    /**
     * Setup for testing
     *
     * @return  void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApp = $this->createMock(CMSApplication::class);
        $this->mockInput = $this->createMock(Input::class);
        
        $this->controller = new LessonsController([], $this->mockApp, $this->mockInput);
    }

    /**
     * Test content type property
     *
     * @return  void
     */
    public function testContentType(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('contentType');
        $property->setAccessible(true);
        
        $this->assertEquals('lessons', $property->getValue($this->controller));
    }

    /**
     * Test default view property
     *
     * @return  void
     */
    public function testDefaultView(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $property = $reflection->getProperty('default_view');
        $property->setAccessible(true);
        
        $this->assertEquals('lessons', $property->getValue($this->controller));
    }

    /**
     * Test save method with lesson data
     *
     * @return  void
     */
    public function testSaveWithLessonData(): void
    {
        $testData = json_encode([
            'name' => 'Test Lesson',
            'type' => 1,
            'fee' => 50.00,
            'year' => 2024,
            'start' => '2024-09-01',
            'end' => '2024-12-20',
            'start_registration' => '2024-07-01',
            'end_registration' => '2024-08-31',
            'max_students' => 20,
            'state' => 'published'
        ]);

        $this->mockInput
            ->expects($this->once())
            ->method('json')
            ->willReturn((object) ['raw' => $testData]);

        $this->mockInput
            ->expects($this->once())
            ->method('set')
            ->with('data', $this->isType('array'));

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('save');
        $method->setAccessible(true);

        $this->expectException(\Error::class);
        $method->invoke($this->controller);
    }

    /**
     * Test save method with missing required fields
     *
     * @return  void
     */
    public function testSaveWithMissingRequiredFields(): void
    {
        $testData = json_encode([
            'name' => 'Test Lesson'
            // Missing required fields
        ]);

        $this->mockInput
            ->expects($this->once())
            ->method('json')
            ->willReturn((object) ['raw' => $testData]);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('save');
        $method->setAccessible(true);

        $this->expectException(\Error::class);
        $method->invoke($this->controller);
    }
}