<?php
App::uses('View', 'View');
App::uses('Helper', 'View');
App::uses('BoostCakePaginatorHelper', 'BoostCake.View/Helper');

/**
 * BootstrapPaginatorHelper Test Case
 *
 */
class BoostCakePaginatorHelperTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$View = new View();
		$this->Paginator = new BoostCakePaginatorHelper($View);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Paginator);

		parent::tearDown();
	}

/**
 * testPaginationEmpty
 *
 * @return void
 */
	public function testPaginationEmpty() {
		$this->Paginator->request->params['paging']['Post'] = array(
			'page' => 1,
			'current' => 0,
			'count' => 0,
			'prevPage' => false,
			'nextPage' => false,
			'pageCount' => 1,
			'order' => null,
			'limit' => 20,
			'options' => array(
				'page' => 1,
				'conditions' => array()
			),
			'paramType' => 'named'
		);
		$numbers = $this->Paginator->pagination(array('model' => 'Post'));
		$this->assertSame('', $numbers);
	}

/**
 * testPaginationTwoModel
 *
 * @return void
 */
	public function testPaginationTwoModel() {
		$this->Paginator->request->params['paging']['Post'] = array(
			'page' => 1,
			'current' => 0,
			'count' => 0,
			'prevPage' => false,
			'nextPage' => false,
			'pageCount' => 1,
			'order' => null,
			'limit' => 20,
			'options' => array(
				'page' => 1,
				'conditions' => array()
			),
			'paramType' => 'named'
		);
		$this->Paginator->request->params['paging']['Article'] = array(
			'page' => 1,
			'current' => 0,
			'count' => 40,
			'prevPage' => false,
			'nextPage' => true,
			'pageCount' => 2,
			'order' => null,
			'limit' => 20,
			'options' => array(
				'page' => 1,
				'conditions' => array()
			),
			'paramType' => 'named'
		);

		$result = $this->Paginator->pagination(array(
			'model' => 'Article',
			'div' => 'pagination'
		));

		$version = (float)Configure::version();
		$pageOne = '/index/page:1';
		if ($version >= 2.4) {
			$pageOne = '/';
		}

		$this->assertTags($result, array(
			'div' => array('class' => 'pagination'),
			'ul' => array(),
			array('li' => array('class' => 'disabled')),
			array('a' => array('href' => $pageOne)),
			'&lt;',
			'/a',
			'/li',
			array('li' => array('class' => 'current disabled')),
			array('a' => array('href' => '#')),
			'1',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:2')),
			'2',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:2', 'rel' => 'next')),
			'&gt;',
			'/a',
			'/li',
			'/ul',
			'/div'
		));
	}

/**
 * testPaginationTwo
 *
 * @return void
 */
	public function testPaginationTwo() {
		$this->Paginator->request->params['paging']['Post'] = array(
			'page' => 1,
			'current' => 0,
			'count' => 40,
			'prevPage' => false,
			'nextPage' => true,
			'pageCount' => 2,
			'order' => null,
			'limit' => 20,
			'options' => array(
				'page' => 1,
				'conditions' => array()
			),
			'paramType' => 'named'
		);

		$result = $this->Paginator->pagination(array(
			'model' => 'Post',
			'div' => 'pagination'
		));

		$version = (float)Configure::version();
		$pageOne = '/index/page:1';
		if ($version >= 2.4) {
			$pageOne = '/';
		}

		$this->assertTags($result, array(
			'div' => array('class' => 'pagination'),
			'ul' => array(),
			array('li' => array('class' => 'disabled')),
			array('a' => array('href' => $pageOne)),
			'&lt;',
			'/a',
			'/li',
			array('li' => array('class' => 'current disabled')),
			array('a' => array('href' => '#')),
			'1',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:2')),
			'2',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:2', 'rel' => 'next')),
			'&gt;',
			'/a',
			'/li',
			'/ul',
			'/div'
		));

		$result = $this->Paginator->pagination(array(
			'model' => 'Post',
			'ul' => 'pagination'
		));

		$version = (float)Configure::version();
		$pageOne = '/index/page:1';
		if ($version >= 2.4) {
			$pageOne = '/';
		}

		$this->assertTags($result, array(
			'ul' => array('class' => 'pagination'),
			array('li' => array('class' => 'disabled')),
			array('a' => array('href' => $pageOne)),
			'&lt;',
			'/a',
			'/li',
			array('li' => array('class' => 'current disabled')),
			array('a' => array('href' => '#')),
			'1',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:2')),
			'2',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:2', 'rel' => 'next')),
			'&gt;',
			'/a',
			'/li',
			'/ul'
		));
	}

/**
 * testNumbersEmpty
 *
 * @return void
 */
	public function testNumbersEmpty() {
		$this->Paginator->request->params['paging']['Post'] = array(
			'page' => 1,
			'current' => 0,
			'count' => 0,
			'prevPage' => false,
			'nextPage' => false,
			'pageCount' => 1,
			'order' => null,
			'limit' => 20,
			'options' => array(
				'page' => 1,
				'conditions' => array()
			),
			'paramType' => 'named'
		);
		$numbers = $this->Paginator->numbers(array('model' => 'Post'));
		$this->assertSame('', $numbers);
	}

/**
 * testNumbersSimple
 *
 * @return void
 */
	public function testNumbersSimple() {
		$this->Paginator->request->params['paging']['Post'] = array(
			'page' => 1,
			'current' => 20,
			'count' => 100,
			'prevPage' => false,
			'nextPage' => true,
			'pageCount' => 5,
			'order' => null,
			'limit' => 20,
			'options' => array(
				'page' => 1,
				'conditions' => array()
			),
			'paramType' => 'named'
		);

		$result = $this->Paginator->numbers(array('model' => 'Post'));
		$this->assertTags($result, array(
			array('li' => array('class' => 'current disabled')),
			array('a' => array('href' => '#')),
			'1',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:2')),
			'2',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:3')),
			'3',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:4')),
			'4',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:5')),
			'5',
			'/a',
			'/li'
		));
	}

/**
 * testNumbersElipsis
 *
 * @return void
 */
	public function testNumbersElipsis() {
		$this->Paginator->request->params['paging']['Post'] = array(
			'page' => 10,
			'current' => 20,
			'count' => 1000,
			'prevPage' => true,
			'nextPage' => true,
			'pageCount' => 200,
			'order' => null,
			'limit' => 20,
			'options' => array(
				'page' => 1,
				'conditions' => array()
			),
			'paramType' => 'named'
		);

		$result = $this->Paginator->numbers(array(
			'model' => 'Post',
			'modulus' => 8,
			'first' => 1,
			'last' => 1,
		));

		$version = (float)Configure::version();
		$pageOne = '/index/page:1';
		if ($version >= 2.4) {
			$pageOne = '/';
		}

		$this->assertTags($result, array(
			array('li' => array()),
			array('a' => array('href' => $pageOne)),
			'1',
			'/a',
			'/li',
			array('li' => array('class' => 'disabled')),
			array('a' => array('href' => '#')),
			'…',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:6')),
			'6',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:7')),
			'7',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:8')),
			'8',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:9')),
			'9',
			'/a',
			'/li',
			array('li' => array('class' => 'current disabled')),
			array('a' => array('href' => '#')),
			'10',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:11')),
			'11',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:12')),
			'12',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:13')),
			'13',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:14')),
			'14',
			'/a',
			'/li',
			array('li' => array('class' => 'disabled')),
			array('a' => array('href' => '#')),
			'…',
			'/a',
			'/li',
			array('li' => array()),
			array('a' => array('href' => '/index/page:200')),
			'200',
			'/a',
			'/li',
		));
	}

/**
 * testPager
 *
 * @return void
 */
	public function testPager() {
		$this->Paginator->request->params['paging']['Post'] = array(
			'page' => 10,
			'current' => 20,
			'count' => 1000,
			'prevPage' => true,
			'nextPage' => true,
			'pageCount' => 200,
			'order' => null,
			'limit' => 20,
			'options' => array(
				'page' => 1,
				'conditions' => array()
			),
			'paramType' => 'named'
		);

		$result = $this->Paginator->pager();
		$this->assertTags($result, array(
			'ul' => array('class' => 'pager'),
			array('li' => array('class' => 'previous')),
			array('a' => array('href' => '/index/page:9', 'rel' => 'prev')),
			'Previous',
			'/a',
			'/li',
			array('li' => array('class' => 'next')),
			array('a' => array('href' => '/index/page:11', 'rel' => 'next')),
			'Next',
			'/a',
			'/li',
			'/ul'
		));
	}

}
