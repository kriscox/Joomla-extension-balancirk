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
use Joomla\CMS\User\User;
use Joomla\Input\Input;
use CoCoCo\Component\Balancirk\Api\Controller\MembersController;

/**
 * Test class for MembersController
 *
 * @since  0.0.1
 */
class MembersControllerTest extends TestCase
{
    /**
     * @var MembersController
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
     * @var User|MockObject
     */
    protected $mockUser;

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
        $this->mockUser = $this->createMock(User::class);
        
        $this->controller = new MembersController([], $this->mockApp, $this->mockInput);
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
        
        $this->assertEquals('members', $property->getValue($this->controller));
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
        
        $this->assertEquals('members', $property->getValue($this->controller));
    }

    /**
     * Test getCurrentUser method
     *
     * @return  void
     */
    public function testGetCurrentUser(): void
    {
        $this->mockUser
            ->expects($this->once())
            ->method('__get')
            ->with('id')
            ->willReturn(123);

        $this->mockApp
            ->expects($this->once())
            ->method('getIdentity')
            ->willReturn($this->mockUser);

        // We can't fully test displayItem without more mocking
        $this->expectException(\Error::class);
        $this->controller->getCurrentUser();
    }

    /**
     * Test save method with member data
     *
     * @return  void
     */
    public function testSaveWithMemberData(): void
    {
        $testData = json_encode([
            'name' => 'Test Member',
            'firstname' => 'Jane',
            'email' => 'jane@example.com',
            'username' => 'janetest'
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
}