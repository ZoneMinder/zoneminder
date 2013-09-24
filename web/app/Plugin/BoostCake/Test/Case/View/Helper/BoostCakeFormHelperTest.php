<?php
App::uses('BoostCakeFormHelper', 'BoostCake.View/Helper');
App::uses('View', 'View');

class Contact extends CakeTestModel {

/**
 * useTable property
 *
 * @var bool false
 */
	public $useTable = false;

/**
 * Default schema
 *
 * @var array
 */
	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'),
		'name' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'phone' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'password' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'published' => array('type' => 'date', 'null' => true, 'default' => null, 'length' => null),
		'created' => array('type' => 'date', 'null' => '1', 'default' => '', 'length' => ''),
		'updated' => array('type' => 'datetime', 'null' => '1', 'default' => '', 'length' => null),
		'age' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => null)
	);

}

class BoostCakeFormHelperTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		$this->View = new View();
		$this->Form = new BoostCakeFormHelper($this->View);

		ClassRegistry::addObject('Contact', new Contact());
	}

	public function tearDown() {
		unset($this->View);
		unset($this->Form);
	}

	public function testInput() {
		$result = $this->Form->input('name');
		$this->assertTags($result, array(
			array('div' => array()),
			'label' => array('for' => 'name'),
			'Name',
			'/label',
			array('div' => array('class' => 'input text')),
			array('input' => array('name' => 'data[name]', 'type' => 'text', 'id' => 'name')),
			'/div',
			'/div'
		));

		$result = $this->Form->input('name', array(
			'div' => 'row',
			'wrapInput' => 'col col-lg-10',
			'label' => array(
				'class' => 'col col-lg-2 control-label'
			)
		));
		$this->assertTags($result, array(
			array('div' => array('class' => 'row')),
			'label' => array('for' => 'name', 'class' => 'col col-lg-2 control-label'),
			'Name',
			'/label',
			array('div' => array('class' => 'col col-lg-10')),
			array('input' => array('name' => 'data[name]', 'type' => 'text', 'id' => 'name')),
			'/div',
			'/div'
		));

		$result = $this->Form->input('name', array('div' => false));
		$this->assertTags($result, array(
			'label' => array('for' => 'name'),
			'Name',
			'/label',
			array('div' => array('class' => 'input text')),
			array('input' => array('name' => 'data[name]', 'type' => 'text', 'id' => 'name')),
			'/div'
		));

		$result = $this->Form->input('name', array('wrapInput' => false));
		$this->assertTags($result, array(
			array('div' => array()),
			'label' => array('for' => 'name'),
			'Name',
			'/label',
			array('input' => array('name' => 'data[name]', 'type' => 'text', 'id' => 'name')),
			'/div'
		));

		$result = $this->Form->input('name', array(
			'div' => false,
			'wrapInput' => false
		));
		$this->assertTags($result, array(
			'label' => array('for' => 'name'),
			'Name',
			'/label',
			array('input' => array('name' => 'data[name]', 'type' => 'text', 'id' => 'name'))
		));
	}

	public function testBeforeInputAfterInput() {
		$result = $this->Form->input('name', array(
			'beforeInput' => 'Before Input',
			'afterInput' => 'After Input',
		));
		$this->assertTags($result, array(
			array('div' => array()),
			'label' => array('for' => 'name'),
			'Name',
			'/label',
			array('div' => array('class' => 'input text')),
			'Before Input',
			array('input' => array('name' => 'data[name]', 'type' => 'text', 'id' => 'name')),
			'After Input',
			'/div',
			'/div'
		));
	}

	public function testCheckbox() {
		$result = $this->Form->input('name', array('type' => 'checkbox'));
		$this->assertTags($result, array(
			array('div' => array()),
			array('div' => array('class' => 'input checkbox')),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'hidden', 'name' => 'data[name]', 'id' => 'name_', 'value' => '0')),
			'label' => array('for' => 'name'),
			array('input' => array('name' => 'data[name]', 'type' => 'checkbox', 'value' => '1', 'id' => 'name')),
			' Name',
			'/label',
			'/div',
			'/div',
			'/div'
		));

		$result = $this->Form->input('name', array(
			'type' => 'checkbox',
			'before' => '<label>Name</label>',
			'label' => false
		));
		$this->assertTags($result, array(
			array('div' => array()),
			array('label' => array()),
			'Name',
			'/label',
			array('div' => array('class' => 'input checkbox')),
			array('div' => array('class' => 'checkbox')),
			array('input' => array('type' => 'hidden', 'name' => 'data[name]', 'id' => 'name_', 'value' => '0')),
			array('input' => array('name' => 'data[name]', 'type' => 'checkbox', 'value' => '1', 'id' => 'name')),
			'/div',
			'/div',
			'/div'
		));

		$result = $this->Form->input('name', array(
			'type' => 'checkbox',
			'checkboxDiv' => false
		));
		$this->assertTags($result, array(
			array('div' => array()),
			array('div' => array('class' => 'input checkbox')),
			array('input' => array('type' => 'hidden', 'name' => 'data[name]', 'id' => 'name_', 'value' => '0')),
			'label' => array('for' => 'name'),
			array('input' => array('name' => 'data[name]', 'type' => 'checkbox', 'value' => '1', 'id' => 'name')),
			' Name',
			'/label',
			'/div',
			'/div'
		));
	}

	public function testSelectMultipleCheckbox() {
		$result = $this->Form->select('name',
			array(
				1 => 'one',
				2 => 'two',
				3 => 'three'
			),
			array(
				'multiple' => 'checkbox',
				'class' => 'checkbox-inline'
			)
		);
		$this->assertTags($result, array(
			array('input' => array('type' => 'hidden', 'name' => 'data[name]', 'value' => '', 'id' => 'name')),
			array('label' => array('for' => 'Name1', 'class' => 'checkbox-inline')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[name][]', 'value' => '1', 'id' => 'Name1')),
			' one',
			'/label',
			array('label' => array('for' => 'Name2', 'class' => 'checkbox-inline')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[name][]', 'value' => '2', 'id' => 'Name2')),
			' two',
			'/label',
			array('label' => array('for' => 'Name3', 'class' => 'checkbox-inline')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[name][]', 'value' => '3', 'id' => 'Name3')),
			' three',
			'/label'
		));

		$result = $this->Form->select('name',
			array(
				1 => 'bill',
				'Smith' => array(
					2 => 'fred',
					3 => 'fred jr.'
				)
			),
			array(
				'multiple' => 'checkbox',
				'class' => 'checkbox-inline'
			)
		);
		$this->assertTags($result, array(
			array('input' => array('type' => 'hidden', 'name' => 'data[name]', 'value' => '', 'id' => 'name')),
			array('label' => array('for' => 'Name1', 'class' => 'checkbox-inline')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[name][]', 'value' => '1', 'id' => 'Name1')),
			' bill',
			'/label',
			'fieldset' => array(),
			'legend' => array(),
			'Smith',
			'/legend',
			array('label' => array('for' => 'Name2', 'class' => 'checkbox-inline')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[name][]', 'value' => '2', 'id' => 'Name2')),
			' fred',
			'/label',
			array('label' => array('for' => 'Name3', 'class' => 'checkbox-inline')),
			array('input' => array('type' => 'checkbox', 'name' => 'data[name][]', 'value' => '3', 'id' => 'Name3')),
			' fred jr.',
			'/label',
			'/fieldset'
		));
	}

	public function testErrorMessage() {
		$Contact = ClassRegistry::getObject('Contact');
		$Contact->validationErrors['password'] = array('Please provide a password');

		$result = $this->Form->input('Contact.password', array(
			'div' => 'row',
			'label' => array(
				'class' => 'col col-lg-2 control-label'
			),
			'class' => 'input-with-feedback'
		));
		$this->assertTags($result, array(
			array('div' => array('class' => 'row has-error error')),
			'label' => array('for' => 'ContactPassword', 'class' => 'col col-lg-2 control-label'),
			'Password',
			'/label',
			array('div' => array('class' => 'input password')),
			'input' => array(
				'type' => 'password', 'name' => 'data[Contact][password]',
				'id' => 'ContactPassword', 'class' => 'input-with-feedback form-error'
			),
			array('span' => array('class' => 'help-block text-danger')),
			'Please provide a password',
			'/span',
			'/div',
			'/div'
		));

		$result = $this->Form->input('Contact.password', array(
			'div' => 'row',
			'label' => array(
				'class' => 'col col-lg-2 control-label'
			),
			'class' => 'input-with-feedback',
			'errorMessage' => false
		));
		$this->assertTags($result, array(
			array('div' => array('class' => 'row has-error error')),
			'label' => array('for' => 'ContactPassword', 'class' => 'col col-lg-2 control-label'),
			'Password',
			'/label',
			array('div' => array('class' => 'input password')),
			'input' => array(
				'type' => 'password', 'name' => 'data[Contact][password]',
				'id' => 'ContactPassword', 'class' => 'input-with-feedback form-error'
			),
			'/div',
			'/div'
		));
	}

	public function testPostLink() {
		$result = $this->Form->postLink('Delete', '/posts/delete/1', array(
			'block' => 'form'
		));
		$this->assertTags($result, array(
			'a' => array('href' => '#', 'onclick' => 'preg:/document\.post_\w+\.submit\(\); event\.returnValue = false; return false;/'),
			'Delete',
			'/a'
		));

		$result = $this->View->fetch('form');
		$this->assertTags($result, array(
			'form' => array(
				'method' => 'post', 'action' => '/posts/delete/1',
				'name' => 'preg:/post_\w+/', 'id' => 'preg:/post_\w+/', 'style' => 'display:none;'
			),
			'input' => array('type' => 'hidden', 'name' => '_method', 'value' => 'POST'),
			'/form'
		));
	}

}
