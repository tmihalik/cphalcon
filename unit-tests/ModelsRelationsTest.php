<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2012 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

class ModelsRelationsTest extends PHPUnit_Framework_TestCase
{

	public function __construct()
	{
		spl_autoload_register(array($this, 'modelsAutoloader'));
	}

	public function __destruct()
	{
		spl_autoload_unregister(array($this, 'modelsAutoloader'));
	}

	public function modelsAutoloader($className)
	{
		$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
		if (file_exists('unit-tests/models/'.$className.'.php')) {
			require 'unit-tests/models/'.$className.'.php';
		}
	}

	protected function _getDI()
	{

		Phalcon\DI::reset();

		$di = new Phalcon\DI();

		$di->set('modelsManager', function(){
			return new Phalcon\Mvc\Model\Manager();
		});

		$di->set('modelsMetadata', function(){
			return new Phalcon\Mvc\Model\Metadata\Memory();
		});

		return $di;
	}

	public function testModelsMysql()
	{

		$di = $this->_getDI();

		$di->set('db', function(){
			require 'unit-tests/config.db.php';
			return new Phalcon\Db\Adapter\Pdo\Mysql($configMysql);
		});

		$this->_executeTestsNormal($di);
		$this->_executeTestsRenamed($di);

	}

	public function testModelsPostgresql()
	{

		$di = $this->_getDI();

		$di->set('db', function(){
			require 'unit-tests/config.db.php';
			return new Phalcon\Db\Adapter\Pdo\Postgresql($configPostgresql);
		});

		$this->_executeTestsNormal($di);
		$this->_executeTestsRenamed($di);

	}

	public function testModelsSqlite()
	{

		$di = $this->_getDI();

		$di->set('db', function(){
			require 'unit-tests/config.db.php';
			return new Phalcon\Db\Adapter\Pdo\Sqlite($configSqlite);
		});

		$this->_executeTestsNormal($di);
		$this->_executeTestsRenamed($di);

	}

	public function _executeTestsNormal($di)
	{

		$manager = $di->getShared('modelsManager');

		$success = $manager->existsBelongsTo('RobotsParts', 'Robots');
		$this->assertTrue($success);

		$success = $manager->existsBelongsTo('RobotsParts', 'Parts');
		$this->assertTrue($success);

		$success = $manager->existsHasMany('Robots', 'RobotsParts');
		$this->assertTrue($success);

		$success = $manager->existsHasMany('Parts', 'RobotsParts');
		$this->assertTrue($success);

		$robot = Robots::findFirst();
		$this->assertNotEquals($robot, false);

		$robotsParts = $robot->getRobotsParts();
		$this->assertEquals(get_class($robotsParts), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robotsParts), 3);

		/** Passing parameters to magic methods **/
		$robotsParts = $robot->getRobotsParts("parts_id = 1");
		$this->assertEquals(get_class($robotsParts), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robotsParts), 1);

		$robotsParts = $robot->getRobotsParts(array(
			"parts_id > :parts_id:",
			"bind" => array("parts_id" => 1)
		));
		$this->assertEquals(get_class($robotsParts), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robotsParts), 2);
		$this->assertEquals($robotsParts->getFirst()->parts_id, 2);

		$robotsParts = $robot->getRobotsParts(array(
			"parts_id > :parts_id:",
			"bind" => array("parts_id" => 1),
			"order" => "parts_id DESC"
		));
		$this->assertEquals(get_class($robotsParts), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robotsParts), 2);
		$this->assertEquals($robotsParts->getFirst()->parts_id, 3);

		/** Magic counting */
		$number = $robot->countRobotsParts();
		$this->assertEquals($number, 3);

		$part = Parts::findFirst();
		$this->assertNotEquals($part, false);

		$robotsParts = $part->getRobotsParts();
		$this->assertEquals(get_class($robotsParts), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robotsParts), 1);

		$number = $part->countRobotsParts();
		$this->assertEquals($number, 1);

		$robotPart = RobotsParts::findFirst();
		$this->assertNotEquals($robotPart, false);

		$robot = $robotPart->getRobots();
		$this->assertEquals(get_class($robot), 'Robots');

		$part = $robotPart->getParts();
		$this->assertEquals(get_class($part), 'Parts');

		/** Relations in namespaced models */
		$robot = Some\Robots::findFirst();
		$this->assertNotEquals($robot, false);

		$robotsParts = $robot->getRobotsParts();
		$this->assertEquals(get_class($robotsParts), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robotsParts), 3);

		$robotsParts = $robot->getRobotsParts("parts_id = 1");
		$this->assertEquals(get_class($robotsParts), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robotsParts), 1);

		$robotsParts = $robot->getRobotsParts(array(
			"parts_id > :parts_id:",
			"bind" => array("parts_id" => 1),
			"order" => "parts_id DESC"
		));
		$this->assertEquals(get_class($robotsParts), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robotsParts), 2);
		$this->assertEquals($robotsParts->getFirst()->parts_id, 3);

	}

	public function _executeTestsRenamed($di)
	{

		$manager = $di->getShared('modelsManager');

		$success = $manager->existsBelongsTo('RobottersDeles', 'Robotters');
		$this->assertTrue($success);

		$success = $manager->existsBelongsTo('RobottersDeles', 'Deles');
		$this->assertTrue($success);

		$success = $manager->existsHasMany('Robotters', 'RobottersDeles');
		$this->assertTrue($success);

		$success = $manager->existsHasMany('Deles', 'RobottersDeles');
		$this->assertTrue($success);

		$robotter = Robotters::findFirst();
		$this->assertNotEquals($robotter, false);

		$robottersDeles = $robotter->getRobottersDeles();
		$this->assertEquals(get_class($robottersDeles), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robottersDeles), 3);

		/** Passing parameters to magic methods **/
		$robottersDeles = $robotter->getRobottersDeles("delesCode = 1");
		$this->assertEquals(get_class($robottersDeles), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robottersDeles), 1);

		$robottersDeles = $robotter->getRobottersDeles(array(
			"delesCode > :delesCode:",
			"bind" => array("delesCode" => 1)
		));
		$this->assertEquals(get_class($robottersDeles), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robottersDeles), 2);
		$this->assertEquals($robottersDeles->getFirst()->delesCode, 2);

		$robottersDeles = $robotter->getRobottersDeles(array(
			"delesCode > :delesCode:",
			"bind" => array("delesCode" => 1),
			"order" => "delesCode DESC"
		));
		$this->assertEquals(get_class($robottersDeles), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robottersDeles), 2);
		$this->assertEquals($robottersDeles->getFirst()->delesCode, 3);

		/** Magic counting */
		$number = $robotter->countRobottersDeles();
		$this->assertEquals($number, 3);

		$dele = Deles::findFirst();
		$this->assertNotEquals($dele, false);

		$robottersDeles = $dele->getRobottersDeles();
		$this->assertEquals(get_class($robottersDeles), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robottersDeles), 1);

		$number = $dele->countRobottersDeles();
		$this->assertEquals($number, 1);

		$robotterDele = RobottersDeles::findFirst();
		$this->assertNotEquals($robotterDele, false);

		$robotter = $robotterDele->getRobotters();
		$this->assertEquals(get_class($robotter), 'Robotters');

		$dele = $robotterDele->getDeles();
		$this->assertEquals(get_class($dele), 'Deles');

		/** Relations in namespaced models */
		$robotter = Some\Robotters::findFirst();
		$this->assertNotEquals($robotter, false);

		$robottersDeles = $robotter->getRobottersDeles();
		$this->assertEquals(get_class($robottersDeles), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robottersDeles), 3);

		$robottersDeles = $robotter->getRobottersDeles("delesCode = 1");
		$this->assertEquals(get_class($robottersDeles), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robottersDeles), 1);

		$robottersDeles = $robotter->getRobottersDeles(array(
			"delesCode > :delesCode:",
			"bind" => array("delesCode" => 1),
			"order" => "delesCode DESC"
		));
		$this->assertEquals(get_class($robottersDeles), 'Phalcon\Mvc\Model\Resultset\Simple');
		$this->assertEquals(count($robottersDeles), 2);
		$this->assertEquals($robottersDeles->getFirst()->delesCode, 3);

	}

}