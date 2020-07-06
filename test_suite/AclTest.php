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
 * @package   AclSetup
 * @author    Author's name <author@mail.com>
 * @copyright 2020 Author's name
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   CVS: $Id:$
 * @link      http://pear.php.net/package/AclSetup
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

$aclSetup = null;

/**
 * Short description for class
 *
 * Long description (if any) ...
 *
 * @category  CategoryName
 * @package   AclSetup
 * @author    Author's name <author@mail.com>
 * @copyright 2020 Author's name
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/AclSetup
 * @see       References to other sections (if any)...
 */
class AclSetup
{

    /**
     * Description for public
     *
     * @var    object
     * @access public
     */
    public $gaclApi;

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $acoSection = [];

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $aco        = [];

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $aroSection = [];

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $aro        = [];

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $aroGroup   = [];

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $axoSection = [];

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $axo        = [];

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $axoGroup   = [];

    /**
     * Description for public
     *
     * @var    array
     * @access public
     */
    public $acl = [];

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function __construct()
    {
        $this->gaclApi = &$GLOBALS['gaclApi'];
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function setup()
    {
        // ACO
        $this->acoSection[] = $this->gaclApi->addObjectSection('Test', 'test_aco', 0, 0, 'ACO');
        $this->aco[] = $this->gaclApi->addObject('test_aco', 'Access', 'access', 0, 0, 'ACO');

        // ARO
        $this->aroSection[] = $this->gaclApi->addObjectSection('Human', 'test_human', 0, 0, 'ARO');
        $this->aro[] = $this->gaclApi->addObject('test_human', 'Han', 'han', 0, 0, 'ARO');
        $this->aro[] = $this->gaclApi->addObject('test_human', 'Lando', 'lando', 0, 0, 'ARO');
        $this->aro[] = $this->gaclApi->addObject('test_human', 'Obi-wan', 'obiwan', 0, 0, 'ARO');
        $this->aro[] = $this->gaclApi->addObject('test_human', 'Luke', 'luke', 0, 0, 'ARO');

        $this->aroSection[] = $this->gaclApi->addObjectSection('Android', 'test_android', 0, 0, 'ARO');
        $this->aro[] = $this->gaclApi->addObject('test_android', 'R2D2', 'r2d2', 0, 0, 'ARO');
        $this->aro[] = $this->gaclApi->addObject('test_android', 'C3PO', 'c3po', 0, 0, 'ARO');

        $this->aroSection[] = $this->gaclApi->addObjectSection('Alien', 'test_alien', 0, 0, 'ARO');
        $this->aro[] = $this->gaclApi->addObject('test_alien', 'Chewie', 'chewie', 0, 0, 'ARO');
        $this->aro[] = $this->gaclApi->addObject('test_alien', 'Hontook', 'hontook', 0, 0, 'ARO');

        // ARO groups
        $this->aroGroup['root'] = $this->gaclApi->addGroup('millennium_falcon_passengers', 'Millennium Falcon Passengers', 0, 'ARO');
        $this->aroGroup['crew'] = $this->gaclApi->addGroup('crew', 'Crew', $this->aroGroup['root'], 'ARO');
        $this->aroGroup['passengers'] = $this->gaclApi->addGroup('passengers', 'Passengers', $this->aroGroup['root'], 'ARO');
        $this->aroGroup['jedi'] = $this->gaclApi->addGroup('jedi', 'Jedi', $this->aroGroup['passengers'], 'ARO');
        $this->aroGroup['engineers'] = $this->gaclApi->addGroup('engineers', 'Engineers', $this->aroGroup['root'], 'ARO');

        // add AROs to groups
        $this->gaclApi->addGroupObject($this->aroGroup['crew'], 'test_alien', 'chewie', 'ARO');
        $this->gaclApi->addGroupObject($this->aroGroup['crew'], 'test_human', 'han', 'ARO');
        $this->gaclApi->addGroupObject($this->aroGroup['crew'], 'test_human', 'lando', 'ARO');

        $this->gaclApi->addGroupObject($this->aroGroup['passengers'], 'test_android', 'c3po', 'ARO');
        $this->gaclApi->addGroupObject($this->aroGroup['passengers'], 'test_android', 'r2d2', 'ARO');

        $this->gaclApi->addGroupObject($this->aroGroup['jedi'], 'test_human', 'luke', 'ARO');
        $this->gaclApi->addGroupObject($this->aroGroup['jedi'], 'test_human', 'obiwan', 'ARO');

        $this->gaclApi->addGroupObject($this->aroGroup['engineers'], 'test_alien', 'hontook', 'ARO');
        $this->gaclApi->addGroupObject($this->aroGroup['engineers'], 'test_android', 'r2d2', 'ARO');
        $this->gaclApi->addGroupObject($this->aroGroup['engineers'], 'test_human', 'han', 'ARO');

        // AXO
        $this->axoSection[] = $this->gaclApi->addObjectSection('Location', 'test_location', 0, 0, 'AXO');
        $this->axo[] = $this->gaclApi->addObject('test_location', 'Engines', 'engines', 0, 0, 'AXO');
        $this->axo[] = $this->gaclApi->addObject('test_location', 'Lounge', 'lounge', 0, 0, 'AXO');
        $this->axo[] = $this->gaclApi->addObject('test_location', 'Cockpit', 'cockpit', 0, 0, 'AXO');
        $this->axo[] = $this->gaclApi->addObject('test_location', 'Guns', 'guns', 0, 0, 'AXO');

        // AXO Groups
        $this->axoGroup['locations'] = $this->gaclApi->addGroup('locations', 'Locations', 0, 'AXO');

        // add AXOs to groups
        $this->gaclApi->addGroupObject($this->axoGroup['locations'], 'test_location', 'engines', 'AXO');
        $this->gaclApi->addGroupObject($this->axoGroup['locations'], 'test_location', 'lounge', 'AXO');
        $this->gaclApi->addGroupObject($this->axoGroup['locations'], 'test_location', 'cockpit', 'AXO');
        $this->gaclApi->addGroupObject($this->axoGroup['locations'], 'test_location', 'guns', 'AXO');

        // create ACLs
        $this->acl[] = $this->gaclApi->addAcl(
            ['test_aco'=> ['access']],
            null,
            [$this->aroGroup['crew']],
            null,
            [$this->axoGroup['locations']],
            1,
            1,
            null,
            'Crew can go anywhere'
        );
        $this->acl[] = $this->gaclApi->addAcl(
            ['test_aco'=> ['access']],
            ['test_alien'=> ['chewie']],
            null,
            ['test_location'=> ['engines']],
            null,
            0,
            1,
            null,
            'Chewie can\'t access the engines'
        );
        $this->acl[] = $this->gaclApi->addAcl(
            ['test_aco'=> ['access']],
            null,
            [$this->aroGroup['passengers']],
            ['test_location'=> ['lounge']],
            null,
            1,
            1,
            null,
            'Passengers are allowed in the lounge'
        );
        $this->acl[] = $this->gaclApi->addAcl(
            ['test_aco'=> ['access']],
            null,
            [$this->aroGroup['jedi']],
            ['test_location'=> ['cockpit']],
            null,
            1,
            1,
            null,
            'Jedi are allowed in the cockpit'
        );
        $this->acl[] = $this->gaclApi->addAcl(
            ['test_aco'=> ['access']],
            ['test_human'=> ['luke']],
            null,
            ['test_location'=> ['guns']],
            null,
            1,
            1,
            null,
            'Luke can access the guns'
        );
        $this->acl[] = $this->gaclApi->addAcl(
            ['test_aco'=> ['access']],
            null,
            [$this->aroGroup['engineers']],
            ['test_location'=> ['engines','guns']],
            null,
            1,
            1,
            null,
            'Engineers can access the engines and guns'
        );
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function teardown()
    {
        // delete ACLs
        foreach ($this->acl as $id) {
            $this->gaclApi->delAcl($id);
        }

        // delete AXO groups
        foreach (array_reverse($this->axoGroup) as $id) {
            $this->gaclApi->delGroup($id, true, 'AXO');
        }

        // delete AXOs
        foreach ($this->axo as $id) {
            $this->gaclApi->delObject($id, 'AXO');
        }

        // delete AXO sections
        foreach ($this->axoSection as $id) {
            $this->gaclApi->delObjectSection($id, 'AXO');
        }

        // delete ARO groups
        foreach (array_reverse($this->aroGroup) as $id) {
            $this->gaclApi->delGroup($id, true, 'ARO');
        }

        // delete AROs
        foreach ($this->aro as $id) {
            $this->gaclApi->delObject($id, 'ARO');
        }

        // delete ARO sections
        foreach ($this->aroSection as $id) {
            $this->gaclApi->delObjectSection($id, 'ARO');
        }

        // delete ACOs
        foreach ($this->aco as $id) {
            $this->gaclApi->delObject($id, 'ACO');
        }

        // delete ACO sections
        foreach ($this->acoSection as $id) {
            $this->gaclApi->delObjectSection($id, 'ACO');
        }
    }

    public static function factory()
    {
        global $aclSetup;

        if (is_null($aclSetup)) {
            $aclSetup = new AclSetup();
            $aclSetup->setup();
        }

        return $aclSetup;
    }
}

/**
 * Short description for class
 *
 * Long description (if any) ...
 *
 * @category  CategoryName
 * @package   AclSetup
 * @author    Author's name <author@mail.com>
 * @copyright 2020 Author's name
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/AclSetup
 * @see       References to other sections (if any)...
 */
class AclTest extends GaclTestCase
{

    /**
     * Description for public
     *
     * @var    object
     * @access public
     */
    public $aclSetup;

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @param string $name     Parameter description (if any) ...
     * @param array  $data     Parameter description (if any) ...
     * @param string $dataName Parameter description (if any) ...
     *
     * @return void
     *
     * @access public
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        global $aclSetup;

        parent::__construct($name, $data, $dataName);

        // set up test environment
        if (is_null($aclSetup)) {
            $aclSetup = AclSetup::factory();
        }

        $this->aclSetup = &$aclSetup;
    }

    public function __destruct()
    {
        $this->aclSetup->teardown();
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testCheckLukeLounge()
    {
        $result = $this->gaclApi->aclCheck(
            'test_aco',
            'access',
            'test_human',
            'luke',
            'test_location',
            'lounge'
        );
        $message = 'Luke should have access to the Lounge';
        $this->assertEquals(true, $result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testCheckLukeEngines()
    {
        $result = $this->gaclApi->aclCheck(
            'test_aco',
            'access',
            'test_human',
            'luke',
            'test_location',
            'engines'
        );
        $message = 'Luke shouldn\'t have access to the Engines';
        $this->assertEquals(false, $result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testCheckChewieGuns()
    {
        $result = $this->gaclApi->aclCheck(
            'test_aco',
            'access',
            'test_alien',
            'chewie',
            'test_location',
            'guns'
        );
        $message = 'Chewie should have access to the Guns';
        $this->assertEquals(true, $result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testCheckChewieEngines()
    {
        $result = $this->gaclApi->aclCheck(
            'test_aco',
            'access',
            'test_alien',
            'chewie',
            'test_location',
            'engines'
        );
        $message = 'Chewie shouldn\'t have access to the Engines';
        $this->assertEquals(false, $result, $message);
    }

    /**
     * Short description for function
     *
     * Long description (if any) ...
     *
     * @return void
     * @access public
     */
    public function testQueryLukeLounge()
    {
        $result = $this->gaclApi->aclQuery(
            'test_aco',
            'access',
            'test_human',
            'luke',
            'test_location',
            'lounge'
        );

        $expected = [
            'acl_id'       => $this->aclSetup->acl[2],
            'return_value' => '',
            'allow'        => true
        ];
        $message = 'Luke should have access to the Lounge';

        $this->assertEquals($expected, $result, $message);
    }
}
