<?php

/**
 * @package     Balancirk.UnitTest
 * @subpackage  Admin
 *
 * @copyright   Copyright (C) 2022 CoCoCo. All rights reserved.
 * @license     GNU General Public License version 3.
 */

// ---------------------------------------------------------------------------
// Joomla stubs required for loading the Admin MemberModel class.
// Bracketed namespace syntax is required when mixing multiple namespace blocks.
// ---------------------------------------------------------------------------

namespace Joomla\CMS\MVC\Model {
    if (!class_exists('Joomla\\CMS\\MVC\\Model\\AdminModel')) {
        abstract class AdminModel
        {
            // No typed property declarations — subclasses freely redeclare them.
            public function setError(string $error): void
            {
            }
            public function getError(): string
            {
                return '';
            }
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

namespace CoCoCo\Component\Balancirk\Tests\Unit\Admin\Model {
    use CoCoCo\Component\Balancirk\Administrator\Model\MemberModel as AdminMemberModel;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for AdminMemberModel::save() dispatch logic.
     *
     * save() was introduced in v1.3.17 to unify API/admin save flows: an
     * id of 0 (or absent) routes to register(); a positive id routes to edit().
     * Regressions in this routing would silently call the wrong path.
     *
     * @since  1.3.17
     */
    class MemberModelSaveDispatchTest extends TestCase
    {
        /**
         * save() without an id must dispatch to register().
         *
         * @return void
         */
        public function testSaveWithNoIdCallsRegister(): void
        {
            $registerCalled = false;
            $editCalled     = false;

            $model = new class ($registerCalled, $editCalled) extends AdminMemberModel {
                public function __construct(
                    private bool &$registerCalled,
                    private bool &$editCalled
                ) {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return null;
                }
                public function register($data): bool
                {
                    $this->registerCalled = true;
                    return true;
                }
                public function edit($data): bool
                {
                    $this->editCalled = true;
                    return true;
                }
            };

            $result = $model->save([]);

            $this->assertTrue($result);
            $this->assertTrue($registerCalled, 'register() must be called when no id is provided');
            $this->assertFalse($editCalled, 'edit() must NOT be called when no id is provided');
        }

        /**
         * save() with id=0 must dispatch to register().
         *
         * @return void
         */
        public function testSaveWithIdZeroCallsRegister(): void
        {
            $registerCalled = false;
            $editCalled     = false;

            $model = new class ($registerCalled, $editCalled) extends AdminMemberModel {
                public function __construct(
                    private bool &$registerCalled,
                    private bool &$editCalled
                ) {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return null;
                }
                public function register($data): bool
                {
                    $this->registerCalled = true;
                    return true;
                }
                public function edit($data): bool
                {
                    $this->editCalled = true;
                    return true;
                }
            };

            $result = $model->save(['id' => 0]);

            $this->assertTrue($result);
            $this->assertTrue($registerCalled, 'register() must be called when id=0');
            $this->assertFalse($editCalled, 'edit() must NOT be called when id=0');
        }

        /**
         * save() with a positive id must dispatch to edit().
         *
         * @return void
         */
        public function testSaveWithPositiveIdCallsEdit(): void
        {
            $registerCalled = false;
            $editCalled     = false;

            $model = new class ($registerCalled, $editCalled) extends AdminMemberModel {
                public function __construct(
                    private bool &$registerCalled,
                    private bool &$editCalled
                ) {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return null;
                }
                public function register($data): bool
                {
                    $this->registerCalled = true;
                    return false;
                }
                public function edit($data): bool
                {
                    $this->editCalled = true;
                    return true;
                }
            };

            $result = $model->save(['id' => 7]);

            $this->assertTrue($result);
            $this->assertFalse($registerCalled, 'register() must NOT be called when id>0');
            $this->assertTrue($editCalled, 'edit() must be called when id>0');
        }

        /**
         * save() must forward the id key into the data array passed to edit().
         *
         * @return void
         */
        public function testSaveForwardsIdToEdit(): void
        {
            $capturedData = null;

            $model = new class ($capturedData) extends AdminMemberModel {
                public function __construct(private mixed &$capturedData)
                {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return null;
                }
                public function register($data): bool
                {
                    return false;
                }
                public function edit($data): bool
                {
                    $this->capturedData = $data;
                    return true;
                }
            };

            $model->save(['id' => 12, 'firstname' => 'Kris']);

            $this->assertIsArray($capturedData);
            $this->assertSame(12, $capturedData['id'] ?? null);
        }

        /**
         * save() must return false when edit() returns false.
         *
         * @return void
         */
        public function testSaveReturnsFalseWhenEditFails(): void
        {
            $model = new class extends AdminMemberModel {
                public function __construct()
                {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return null;
                }
                public function register($data): bool
                {
                    return false;
                }
                public function edit($data): bool
                {
                    return false;
                }
            };

            $this->assertFalse($model->save(['id' => 3]));
        }

        /**
         * save() must return false when register() returns false.
         *
         * @return void
         */
        public function testSaveReturnsFalseWhenRegisterFails(): void
        {
            $model = new class extends AdminMemberModel {
                public function __construct()
                {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return null;
                }
                public function register($data): bool
                {
                    return false;
                }
                public function edit($data): bool
                {
                    return true;
                }
            };

            $this->assertFalse($model->save([]));
        }

        /**
         * save() without a data id but with a positive state 'member.id' must
         * fall back to the state value and dispatch to edit().
         *
         * This path is used by API calls where the member id is in model state
         * rather than in the request body.
         *
         * @return void
         */
        public function testSaveWithNoDataIdButPositiveStateCallsEdit(): void
        {
            $registerCalled = false;
            $editCalled     = false;

            $model = new class ($registerCalled, $editCalled) extends AdminMemberModel {
                public function __construct(
                    private bool &$registerCalled,
                    private bool &$editCalled
                ) {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return $property === 'member.id' ? 15 : null;
                }
                public function register($data): bool
                {
                    $this->registerCalled = true;
                    return true;
                }
                public function edit($data): bool
                {
                    $this->editCalled = true;
                    return true;
                }
            };

            $result = $model->save([]);

            $this->assertTrue($result);
            $this->assertFalse($registerCalled, 'register() must NOT be called when state has a positive member.id');
            $this->assertTrue($editCalled, 'edit() must be called when state has a positive member.id');
        }

        /**
         * save() with no data id and state 'member.id' = 0 must call register().
         *
         * @return void
         */
        public function testSaveWithNoDataIdAndZeroStateCallsRegister(): void
        {
            $registerCalled = false;
            $editCalled     = false;

            $model = new class ($registerCalled, $editCalled) extends AdminMemberModel {
                public function __construct(
                    private bool &$registerCalled,
                    private bool &$editCalled
                ) {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return $property === 'member.id' ? 0 : null;
                }
                public function register($data): bool
                {
                    $this->registerCalled = true;
                    return true;
                }
                public function edit($data): bool
                {
                    $this->editCalled = true;
                    return true;
                }
            };

            $result = $model->save([]);

            $this->assertTrue($result);
            $this->assertTrue($registerCalled, 'register() must be called when state member.id is 0');
            $this->assertFalse($editCalled, 'edit() must NOT be called when state member.id is 0');
        }

        /**
         * save() state fallback must forward the state-derived id into the data
         * array passed to edit().
         *
         * @return void
         */
        public function testSaveStateIdIsForwardedToEdit(): void
        {
            $capturedData = null;

            $model = new class ($capturedData) extends AdminMemberModel {
                public function __construct(private mixed &$capturedData)
                {
                }
                public function getState($property = null, $default = null): mixed
                {
                    return $property === 'member.id' ? 42 : null;
                }
                public function register($data): bool
                {
                    return false;
                }
                public function edit($data): bool
                {
                    $this->capturedData = $data;
                    return true;
                }
            };

            $model->save(['firstname' => 'Test']);

            $this->assertIsArray($capturedData);
            $this->assertSame(42, $capturedData['id'] ?? null, 'State-derived id must be injected into data[id]');
        }
    }
}
