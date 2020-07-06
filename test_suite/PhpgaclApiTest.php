<?php
/**
 * Short description for file
 *
 * Long description (if any) ...
 *
 * PHP version 5
 *
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * + Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * + Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 * + Neither the name of the <ORGANIZATION> nor the names of its contributors
 * may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  CategoryName
 * @package   GaclTestResult
 * @author    Author's name <author@mail.com>
 * @copyright 2020 Author's name
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   CVS: $Id:$
 * @link      http://pear.php.net/package/gaclTestResult
 * @see       References to other sections (if any)...
 */

use PHPUnit\Framework\TestCase;

/**
 * Description for require_once
 */
require_once dirname(__FILE__) . '/../admin/gacl_admin.inc.php';

/**
 * Description for require_once
 */
require_once __DIR__ . '/GaclTestCase.php';

/**
 * Short description for class
 *
 * Long description (if any) ...
 *
 * @category  CategoryName
 * @package   PhpgaclApiTest
 * @author    Author's name <author@mail.com>
 * @copyright 2020 Author's name
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PhpgaclApiTest
 * @see       References to other sections (if any)...
 */
class PhpgaclApiTest extends GaclTestCase
{

    /* VERSION */

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testGetVersion()
    {
        $result = $this->gaclApi->getVersion();
        //$expected = '/^[0-9]{1,2}.[0-9]{1,2}.[0-9]{1,2}$/i';
        $expected = '/^[0-9]{1,2}.[0-9]{1,2}.[0-9]{1,2}[a-zA-Z]{0,1}[0-9]{0,1}$/i';

        $this->assertRegexp($expected, $result, 'Version incorrect.');
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testGetSchemaVersion()
    {
        $result = $this->gaclApi->getSchemaVersion();
        $expected = '/^[0-9]{1,2}.[0-9]{1,2}$/i';

        $this->assertRegexp($expected, $result, 'Schema Version incorrect.');
    }

    /* GENERAL */

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testCountAll()
    {
        //Create array
        $arr = [
            'Level1a' => [
                'Level2a' => [
                    'Level3a' => 1,
                    'Level3b' => 2
                ],
                'Level2b' => 3,
            ],
            'Level1b' => 4,
            'Level1c' => [
                'Level2c' => [
                    'Level3c' => 5,
                    'Level3d' => 6
                ],
                'Level2d' => 7,
            ],
            'Level1d' => 8
        ];

        //Keep in mind count_all only counts actual values. So array()'s don't count as +1
        $result = $this->gaclApi->countAll($arr);

        $this->assertEquals(8, $result, 'Incorrect array count, Should be 8.');
    }

    /* ACO SECTION */

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddObjectSectionAco()
    {
        $result = $this->gaclApi->addObjectSection('unit_test', 'unit_test', 999, 0, 'ACO');
        $message = 'addObjectSection failed';

        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetObjectSectionSectionIdAco()
    {
        $result = $this->gaclApi->getObjectSectionSectionId('unit_test', 'unit_test', 'ACO');
        $message = 'getObjectSectionSectionId failed';

        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddObjectAco()
    {
        $result = $this->gaclApi->addObject('unit_test', 'Enable - Tests', 'enable_tests', 999, 0, 'ACO');
        $message = 'addObject failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetObjectIdAco()
    {
        $result = $this->gaclApi->getObjectId('unit_test', 'enable_tests', 'ACO');
        $message = 'getObjectId failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddObjectSectionAro()
    {
        $result = $this->gaclApi->addObjectSection('unit_test', 'unit_test', 999, 0, 'ARO');
        $message = 'addObjectSection failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetObjectSectionSectionIdAro()
    {
        $result = $this->gaclApi->getObjectSectionSectionId('unit_test', 'unit_test', 'ARO');
        $this->acoSectionId = $result;
        $message = 'getObjectSectionSectionId failed';
        $this->assertTrue(($result >= 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddObjectAro()
    {
        $result = $this->gaclApi->addObject('unit_test', 'John Doe', 'john_doe', 999, 0, 'ARO');
        $message = 'addObject failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testEditObjectSectionAro()
    {
        $objectId = $this->testGetObjectSectionSectionIdAro();

        $renameResult = $this->gaclApi->editObjectSection($objectId, 'unit_test_tmp', 'unit_test_tmp', 999, 0, 'ARO');
        $renameTwoResult = $this->gaclApi->editObjectSection($objectId, 'unit_test', 'unit_test', 999, 0, 'ARO');

        if ($renameResult === true AND $renameTwoResult === true) {
            $result = true;
        } else {
            $result = false;
        }

        $message = 'editObjectSection failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetObjectIdAro()
    {
        $result = $this->gaclApi->getObjectId('unit_test', 'john_doe', 'ARO');
        $message = 'getObjectId failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddObjectTwoAro()
    {
        $result = $this->gaclApi->addObject('unit_test', 'Jane Doe', 'jane_doe', 998, 0, 'ARO');
        $message = 'addObjectTwo failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetObjectTwoIdAro()
    {
        $result = $this->gaclApi->getObjectId('unit_test', 'jane_doe', 'ARO');
        $message = 'getObjectTwoId failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddObjectSectionAxo()
    {
        $result = $this->gaclApi->addObjectSection('unit_test', 'unit_test', 999, 0, 'AXO');
        $this->acoSectionId = $result;
        $message = 'addObjectSection failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetObjectSectionSectionIdAxo()
    {
        $result = $this->gaclApi->getObjectSectionSectionId('unit_test', 'unit_test', 'AXO');
        $message = 'getObjectSectionSectionId failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddObjectAxo()
    {
        $result = $this->gaclApi->addObject('unit_test', 'Object 1', 'object_1', 999, 0, 'AXO');
        $message = 'addObject failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetObjectIdAxo()
    {
        $result = $this->gaclApi->getObjectId('unit_test', 'object_1', 'AXO');
        $message = 'getObjectId failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddGroupParentAro()
    {
        $result = $this->gaclApi->addGroup('group_1', 'ARO Group 1', 0, 'ARO');
        $message = 'addGroupParentAro failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testEditGroupParentAro()
    {
        $groupId = $this->testGetGroupIdParentAro();

        $firstRename = $this->gaclApi->editGroup($groupId, 'group_1_tmp', 'ARO Group 1 - tmp', 0, 'ARO');
        $secondRename = $this->gaclApi->editGroup($groupId, 'group_1', 'ARO Group 1', 0, 'ARO');
        $reparentToSelf = $this->gaclApi->editGroup($groupId, 'group_1', 'ARO Group 1', $groupId, 'ARO');

        if ($firstRename === true AND $secondRename === true AND $reparentToSelf === false) {
            $result = true;
        } else {
            $result = false;
        }
        $message = 'editGroupParentAro failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetGroupIdParentAro()
    {
        $result = $this->gaclApi->getGroupId(null, 'ARO Group 1', 'ARO');
        $message = 'getGroupIdParentAro failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return boolean Return description (if any) ...
     * @access public
     */
    public function testGetGroupDataAro()
    {
        list($id, $parentId, $value, $name, $lft, $rgt) = $this->gaclApi->getGroupData($this->testGetGroupIdParentAro(), 'ARO');
        //Check all values in the resulting array.
        if ($id > 0 AND $parentId >= 0 AND strlen($name) > 0 AND $lft >= 1 AND $rgt > 1) {
            $result = true;
        } else {
            $result = false;
        }
        $message = 'getGroupDataAro failed';
        $this->assertTrue($result, $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddGroupChildAro()
    {
        $result = $this->gaclApi->addGroup('group_2', 'ARO Group 2', $this->testGetGroupIdParentAro(), 'ARO');
        $message = 'addGroupChild failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetGroupIdChildAro()
    {
        $result = $this->gaclApi->getGroupId(null, 'ARO Group 2', 'ARO');
        $message = 'getGroupIdChildAro failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return boolean Return description (if any) ...
     * @access public
     */
    public function testGetGroupParentIdAro()
    {
        $parentId = $this->gaclApi->getGroupParentId($this->testGetGroupIdChildAro(), 'ARO');
        //Make sure it matches with the actual parent.
        if ($parentId === $this->testGetGroupIdParentAro()) {
            $result = true;
        } else {
            $result = false;
        }
        $message = 'getGroupParentIdAro failed';
        $this->assertTrue($result, $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddParentGroupObjectAro()
    {
        $result = $this->gaclApi->addGroupObject($this->testGetGroupIdParentAro(), 'unit_test', 'john_doe', 'ARO');
        $message = 'addParentGroupObject failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddChildGroupObjectAro()
    {
        $result = $this->gaclApi->addGroupObject($this->testGetGroupIdChildAro(), 'unit_test', 'jane_doe', 'ARO');
        $message = 'addChildGroupObject failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return boolean Return description (if any) ...
     * @access public
     */
    public function testGetParentGroupObjectsAro()
    {
        $groupObjects = $this->gaclApi->getGroupObjects($this->testGetGroupIdParentAro(), 'ARO');
        if (count($groupObjects, COUNT_RECURSIVE) == 2 AND $groupObjects['unit_test'][0] == 'john_doe') {
            $result = true;
        } else {
            $result = false;
        }
        $message = 'getParentGroupObjectsAro failed';
        $this->assertTrue($result, $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return boolean Return description (if any) ...
     * @access public
     */
    public function testGetParentGroupObjectsRecurseAro()
    {
        $groupObjects = $this->gaclApi->getGroupObjects($this->testGetGroupIdParentAro(), 'ARO', 'RECURSE');

        switch (true) {
        case count($groupObjects) != 1 :
        case !isset($groupObjects['unit_test']) :
        case count($groupObjects['unit_test']) != 2 :
        case !in_array('john_doe', $groupObjects['unit_test']) :
        case !in_array('jane_doe', $groupObjects['unit_test']) :
            $result = false;
            break;
        default :
            $result = true;
        }

        $message = 'getParentGroupObjectsRecurseAro failed';
        $this->assertTrue($result, $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddGroupParentAxo()
    {
        $result = $this->gaclApi->addGroup('group_1', 'AXO Group 1', 0, 'AXO');
        $message = 'addGroup failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetGroupIdParentAxo()
    {
        $result = $this->gaclApi->getGroupId(null, 'AXO Group 1', 'AXO');
        $message = 'getGroupIdParentAro failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return boolean Return description (if any) ...
     * @access public
     */
    public function testGetGroupDataAxo()
    {
        list($id, $parentId, $value, $name, $lft, $rgt) = $this->gaclApi->getGroupData($this->testGetGroupIdParentAxo(), 'AXO');
        //Check all values in the resulting array.
        if ($id > 0 AND $parentId >= 0 AND strlen($name) > 0 AND $lft >= 1 AND $rgt > 1) {
            $result = true;
        } else {
            $result = false;
        }
        $message = 'getGroupDataAxo failed';
        $this->assertTrue($result, $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddGroupChildAxo()
    {
        $result = $this->gaclApi->addGroup('group_2', 'AXO Group 2', $this->testGetGroupIdParentAxo(), 'AXO');
        $message = 'addGroup failed';
        $this->assertTrue(($result > 0 ? true : false), $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return integer Return description (if any) ...
     * @access public
     */
    public function testGetGroupIdChildAxo()
    {
        $result = $this->gaclApi->getGroupId(null, 'AXO Group 2', 'AXO');
        $message = 'getGroupIdChildAxo failed';
        $this->assertTrue(($result > 0 ? true : false), $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testAddGroupObjectAxo()
    {
        $result = $this->gaclApi->addGroupObject($this->testGetGroupIdParentAxo(), 'unit_test', 'object_1', 'AXO');
        $message = 'addGroupObject failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return boolean Return description (if any) ...
     * @access public
     */
    public function testGetGroupParentIdAxo()
    {
        $parentId = $this->gaclApi->getGroupParentId($this->testGetGroupIdChildAxo(), 'AXO');
        //Make sure it matches with the actual parent.
        if ($parentId === $this->testGetGroupIdParentAxo()) {
            $result = true;
        } else {
            $result = false;
        }
        $message = 'getGroupParentIdAro failed';
        $this->assertTrue($result, $message);

        return $result;
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelParentGroupObjectAro()
    {
        $result = $this->gaclApi->delGroupObject($this->testGetGroupIdParentAro(), 'unit_test', 'john_doe', 'ARO');
        $message = 'delGroupObject failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelChildGroupObjectAro()
    {
        $result = $this->gaclApi->delGroupObject($this->testGetGroupIdChildAro(), 'unit_test', 'jane_doe', 'ARO');
        $message = 'delChildGroupObject failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelGroupChildAro()
    {
        $result = $this->gaclApi->delGroup($this->testGetGroupIdChildAro(), true, 'ARO');
        $message = 'delGroup failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelGroupParentAro()
    {
        $result = $this->gaclApi->delGroup($this->testGetGroupIdParentAro(), true, 'ARO');
        $message = 'delGroupParentAro failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelGroupObjectAxo()
    {
        $result = $this->gaclApi->delGroupObject($this->testGetGroupIdParentAxo(), 'unit_test', 'object_1', 'AXO');
        $message = 'delGroupObject failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelGroupChildAxo()
    {
        $result = $this->gaclApi->delGroup($this->testGetGroupIdChildAxo(), true, 'AXO');
        $message = 'delGroup failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelGroupParentAxo()
    {
        $result = $this->gaclApi->delGroup($this->testGetGroupIdParentAxo(), true, 'AXO');
        $message = 'delGroup failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelObjectAco()
    {
        $result = $this->gaclApi->delObject($this->testGetObjectIdAco(), 'ACO');
        $message = 'delObject failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelObjectSectionAco()
    {
        $result = $this->gaclApi->delObjectSection($this->testGetObjectSectionSectionIdAco(), 'ACO');
        $message = 'delObjectSection failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelGroupParentNoReparentAro()
    {
        $this->testAddGroupParentAro();
        $this->testAddGroupChildAro();
        $this->testAddParentGroupObjectAro();
        $this->testAddChildGroupObjectAro();

        $result = $this->gaclApi->delGroup($this->testGetGroupIdParentAro(), false, 'ARO');

        $message = 'delGroupParentReparentAro failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelGroupParentReparentAro()
    {
        $this->testAddGroupParentAro();
        $this->testAddGroupChildAro();
        $this->testAddParentGroupObjectAro();
        $this->testAddChildGroupObjectAro();

        $result = $this->gaclApi->delGroup($this->testGetGroupIdParentAro(), true, 'ARO');

        $this->testDelChildGroupObjectAro();
        $this->testDelGroupChildAro();

        $message = 'delGroupParentNoReparentAro failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelObjectAro()
    {
        $result = $this->gaclApi->delObject($this->testGetObjectIdAro(), 'ARO');
        $message = 'delObject failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelObjectTwoAro()
    {
        $result = $this->gaclApi->delObject($this->testGetObjectTwoIdAro(), 'ARO');
        $message = 'delObjectTwo failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelObjectSectionAro()
    {
        $result = $this->gaclApi->delObjectSection($this->testGetObjectSectionSectionIdAro(), 'ARO');
        $message = 'delObjectSection failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelObjectAxo()
    {
        $result = $this->gaclApi->delObject($this->testGetObjectIdAxo(), 'AXO');
        $message = 'delObject failed';
        $this->assertTrue($result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testDelObjectSectionAxo()
    {
        $result = $this->gaclApi->delObjectSection($this->testGetObjectSectionSectionIdAxo(), 'AXO');
        $message = 'delObjectSection failed';
        $this->assertTrue($result, $message);
    }
}
