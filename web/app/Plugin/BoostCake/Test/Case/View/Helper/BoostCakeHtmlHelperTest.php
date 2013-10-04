<?php
App::uses('BoostCakeHtmlHelper', 'BoostCake.View/Helper');
App::uses('View', 'View');

class BoostCakeHtmlHelperTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$View = new View();
		$this->Html = new BoostCakeHtmlHelper($View);
	}

	public function tearDown() {
		unset($this->Html);
	}

	public function testUseTag() {
		$result = $this->Html->useTag(
			'radio', 'one', 'two', array('three' => 'four'), '<label for="one">label</label>'
		);
		$this->assertTags($result, array(
			'label' => array('class' => 'radio', 'for' => 'one'),
			'input' => array('type' => 'radio', 'name' => 'one', 'id' => 'two', 'three' => 'four'),
			' label',
			'/label'
		));

		$result = $this->Html->useTag(
			'radio', 'one', 'two', array('class' => 'radio-inline', 'three' => 'four'), '<label for="one">label</label>'
		);
		$this->assertTags($result, array(
			'label' => array('class' => 'radio-inline', 'for' => 'one'),
			'input' => array('type' => 'radio', 'name' => 'one', 'id' => 'two', 'three' => 'four'),
			' label',
			'/label'
		));
	}

	public function testImage() {
		$result = $this->Html->image('', array('data-src' => 'holder.js/24x24'));
		$this->assertTags($result, array(
			'img' => array('src' => '/', 'data-src' => 'holder.js/24x24', 'alt' => '')
		));

		$result = $this->Html->image('some.jpg', array('data-src' => 'holder.js/24x24'));
		$this->assertTags($result, array(
			'img' => array('src' => '/img/some.jpg', 'alt' => '')
		));
	}

}
