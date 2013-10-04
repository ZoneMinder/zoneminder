<?php $this->layout = 'bootstrap2'; ?>
<?php $this->set('title_for_layout', 'Bootstrap2 examples'); ?>

<div class="row">
	<div class="span3">
		<ul class="nav nav-tabs nav-stacked affix">
			<li><a href="#forms"><i class="icon-chevron-right pull-right"></i> Forms</a></li>
			<li><a href="#pagination"><i class="icon-chevron-right pull-right"></i> Pagination</a></li>
			<li><a href="#alerts"><i class="icon-chevron-right pull-right"></i> Alerts</a></li>
		</ul>
	</div>
	<div class="span9">
		<h1>BoostCake Examples <small>Bootstrap Version 2.3.2</small></h1>

		<section id="forms">
			<div class="page-header">
				<h2>Forms</h2>
			</div>

			<h3>Default styles</h3>
			<p>Individual form controls receive styling, but without any required base class on the <code>&lt;form&gt;</code> or large changes in markup. Results in stacked, left-aligned labels on top of form controls.</p>

			<?php echo $this->Form->create('BoostCake', array(
				'inputDefaults' => array(
					'div' => false,
					'wrapInput' => false
				),
				'class' => 'well'
			)); ?>
				<fieldset>
					<legend>Legend</legend>
					<?php echo $this->Form->input('text', array(
						'label' => 'Label name',
						'placeholder' => 'Type something…',
						'after' => '<span class="help-block">Example block-level help text here.</span>'
					)); ?>
					<?php echo $this->Form->input('checkbox', array(
						'label' => 'Check me out'
					)); ?>
					<?php echo $this->Form->submit('Submit', array(
						'div' => false,
						'class' => 'btn'
					)); ?>
				</fieldset>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => false,
		'wrapInput' => false
	),
	'class' => 'well'
)); ?>
	<fieldset>
		<legend>Legend</legend>
		<?php echo \$this->Form->input('text', array(
			'label' => 'Label name',
			'placeholder' => 'Type something…',
			'after' => '<span class=\"help-block\">Example block-level help text here.</span>'
		)); ?>
		<?php echo \$this->Form->input('checkbox', array(
			'label' => 'Check me out'
		)); ?>
		<?php echo \$this->Form->submit('Submit', array(
			'div' => false,
			'class' => 'btn'
		)); ?>
	</fieldset>
<?php echo \$this->Form->end(); ?>");
			?></pre>
			<hr>

			<h3>Search form</h3>
			<p>Add <code>.form-search</code> to the form and <code>.search-query</code> to the <code>&lt;input&gt;</code> for an extra-rounded text input.</p>

			<?php echo $this->Form->create('BoostCake', array(
				'inputDefaults' => array(
					'div' => false,
					'wrapInput' => false
				),
				'class' => 'well form-search'
			)); ?>
				<?php echo $this->Form->input('text', array(
					'label' => false,
					'class' => 'input-medium search-query',
				)); ?>
				<?php echo $this->Form->submit('Search', array(
					'div' => false,
					'class' => 'btn'
				)); ?>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => false,
		'wrapInput' => false
	),
	'class' => 'well form-search'
)); ?>
	<?php echo \$this->Form->input('text', array(
		'label' => false,
		'class' => 'input-medium search-query',
	)); ?>
	<?php echo \$this->Form->submit('Search', array(
		'div' => false,
		'class' => 'btn'
	)); ?>
<?php echo \$this->Form->end(); ?>");
			?></pre>
			<hr>

			<h3>Inline form</h3>
			<p>Add <code>.form-inline</code> for left-aligned labels and inline-block controls for a compact layout.</p>

			<?php echo $this->Form->create('BoostCake', array(
				'inputDefaults' => array(
					'div' => false,
					'label' => false,
					'wrapInput' => false
				),
				'class' => 'well form-inline'
			)); ?>
				<?php echo $this->Form->input('email', array(
					'class' => 'input-small',
					'placeholder' => 'Email'
				)); ?>
				<?php echo $this->Form->input('password', array(
					'class' => 'input-small',
					'placeholder' => 'Password'
				)); ?>
				<?php echo $this->Form->input('remember', array(
					'label' => array(
						'text' => 'Remember me',
						'class' => 'checkbox'
					),
					'checkboxDiv' => false
				)); ?>
				<?php echo $this->Form->submit('Sign in', array(
					'div' => false,
					'class' => 'btn'
				)); ?>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => false,
		'label' => false,
		'wrapInput' => false
	),
	'class' => 'well form-inline'
)); ?>
	<?php echo \$this->Form->input('email', array(
		'class' => 'input-small',
		'placeholder' => 'Email'
	)); ?>
	<?php echo \$this->Form->input('password', array(
		'class' => 'input-small',
		'placeholder' => 'Password'
	)); ?>
	<?php echo \$this->Form->input('remember', array(
		'label' => array(
			'text' => 'Remember me',
			'class' => 'checkbox'
		),
		'checkboxDiv' => false
	)); ?>
	<?php echo \$this->Form->submit('Sign in', array(
		'div' => false,
		'class' => 'btn'
	)); ?>
<?php echo \$this->Form->end(); ?>");
			?></pre>
			<hr>

			<h3>Horizontal form</h3>
			<p>
				Right align labels and float them to the left to make them appear on the same line as controls.
				Requires the most markup changes from a default form:
			</p>
			<ul>
				<li>Add <code>.form-horizontal</code> to the form</li>
				<li>Wrap labels and controls in <code>.control-group</code></li>
				<li>Add <code>.control-label</code> to the label</li>
				<li>Wrap any associated controls in <code>.controls</code> for proper alignment</li>
			</ul>

			<?php echo $this->Form->create('BoostCake', array(
				'inputDefaults' => array(
					'div' => 'control-group',
					'label' => array(
						'class' => 'control-label'
					),
					'wrapInput' => 'controls'
				),
				'class' => 'well form-horizontal'
			)); ?>
				<?php echo $this->Form->input('email', array(
					'placeholder' => 'Email'
				)); ?>
				<?php echo $this->Form->input('password', array(
					'placeholder' => 'Password'
				)); ?>
				<?php echo $this->Form->input('remember', array(
					'label' => 'Remember me',
					'afterInput' => $this->Form->submit('Sign in', array(
						'class' => 'btn'
					))
				)); ?>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => 'control-group',
		'label' => array(
			'class' => 'control-label'
		),
		'wrapInput' => 'controls'
	),
	'class' => 'well form-horizontal'
)); ?>
	<?php echo \$this->Form->input('email', array(
		'placeholder' => 'Email'
	)); ?>
	<?php echo \$this->Form->input('password', array(
		'placeholder' => 'Password'
	)); ?>
	<?php echo \$this->Form->input('remember', array(
		'label' => 'Remember me',
		'afterInput' => \$this->Form->submit('Sign in', array(
			'class' => 'btn'
		))
	)); ?>
<?php echo \$this->Form->end(); ?>");
			?></pre>
			<hr>

			<h3>Other form example</h3>
			<?php
			$BoostCake = ClassRegistry::getObject('BoostCake');
			$BoostCake->validationErrors['password'] = array('Please provide a password');
			?>
			<?php echo $this->Form->create('BoostCake', array(
				'inputDefaults' => array(
					'div' => 'control-group',
					'label' => array(
						'class' => 'control-label'
					),
					'wrapInput' => 'controls'
				),
				'class' => 'well form-horizontal'
			)); ?>
				<?php echo $this->Form->input('select', array(
					'label' => array(
						'text' => 'Select Nested Options'
					),
					'empty' => '選択してください',
					'options' => array(
						'東京' => array(
							1 => '渋谷',
							2 => '秋葉原'
						),
						'大阪' => array(
							3 => '梅田',
							4 => '難波'
						)
					),
				)); ?>
				<?php echo $this->Form->input('select', array(
					'label' => array(
						'text' => 'Select Nested Options Checkbox'
					),
					'class' => 'checkbox inline',
					'multiple' => 'checkbox',
					'options' => array(
						'東京' => array(
							1 => '渋谷',
							2 => '秋葉原'
						),
						'大阪' => array(
							3 => '梅田',
							4 => '難波'
						)
					)
				)); ?>
				<?php echo $this->Form->input('radio', array(
					'type' => 'radio',
					'before' => '<label class="control-label">Radio</label>',
					'legend' => false,
					'options' => array(
						1 => 'Option one is this and that—be sure to include why it\'s great',
						2 => 'Option two can be something else and selecting it will deselect option one'
					)
				)); ?>
				<?php echo $this->Form->input('username', array(
					'placeholder' => 'Username',
					'div' => 'control-group input-prepend',
					'label' => array(
						'text' => 'Prepend',
					),
					'wrapInput' => 'controls',
					'beforeInput' => '<span class="add-on">@</span>'
				)); ?>
				<?php echo $this->Form->input('price', array(
					'div' => 'control-group input-append',
					'label' => array(
						'text' => 'Append',
					),
					'wrapInput' => 'controls',
					'afterInput' => '<span class="add-on">.00</span>'
				)); ?>
				<?php echo $this->Form->input('password', array(
					'label' => array(
						'text' => 'Show Error Message'
					),
					'placeholder' => 'Password'
				)); ?>
				<?php echo $this->Form->input('password', array(
					'label' => array(
						'text' => 'Hide Error Message'
					),
					'placeholder' => 'Password',
					'errorMessage' => false
				)); ?>
				<?php echo $this->Form->input('checkbox', array(
					'label' => array('class' => null),
					'afterInput' => '<span class="help-block">Checkbox Bootstrap Style</span>'
				)); ?>
				<?php echo $this->Form->input('checkbox', array(
					'div' => false,
					'label' => false,
					'before' => '<label class="control-label">Checkbox</label>',
					'wrapInput' => 'controls',
					'afterInput' => '<span class="help-block">Checkbox CakePHP Style</span>'
				)); ?>
				<div class="form-actions">
					<?php echo $this->Form->submit('Save changes', array(
						'div' => false,
						'class' => 'btn btn-primary'
					)); ?>
					<button type="button" class="btn">Cancel</button>
				</div>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => 'control-group',
		'label' => array(
			'class' => 'control-label'
		),
		'wrapInput' => 'controls'
	),
	'class' => 'well form-horizontal'
)); ?>
	<?php echo \$this->Form->input('select', array(
		'label' => array(
			'text' => 'Select Nested Options'
		),
		'empty' => '選択してください',
		'options' => array(
			'東京' => array(
				1 => '渋谷',
				2 => '秋葉原'
			),
			'大阪' => array(
				3 => '梅田',
				4 => '難波'
			)
		),
	)); ?>
	<?php echo \$this->Form->input('select', array(
		'label' => array(
			'text' => 'Select Nested Options Checkbox'
		),
		'class' => 'checkbox inline',
		'multiple' => 'checkbox',
		'options' => array(
			'東京' => array(
				1 => '渋谷',
				2 => '秋葉原'
			),
			'大阪' => array(
				3 => '梅田',
				4 => '難波'
			)
		)
	)); ?>
	<?php echo \$this->Form->input('radio', array(
		'type' => 'radio',
		'before' => '<label class=\"control-label\">Radio</label>',
		'legend' => false,
		'options' => array(
			1 => 'Option one is this and that—be sure to include why it\'s great',
			2 => 'Option two can be something else and selecting it will deselect option one'
		)
	)); ?>
	<?php echo \$this->Form->input('username', array(
		'placeholder' => 'Username',
		'div' => 'control-group input-prepend',
		'label' => array(
			'text' => 'Prepend',
		),
		'wrapInput' => 'controls',
		'beforeInput' => '<span class=\"add-on\">@</span>'
	)); ?>
	<?php echo \$this->Form->input('price', array(
		'div' => 'control-group input-append',
		'label' => array(
			'text' => 'Append',
		),
		'wrapInput' => 'controls',
		'afterInput' => '<span class=\"add-on\">.00</span>'
	)); ?>
	<?php echo \$this->Form->input('password', array(
		'label' => array(
			'text' => 'Show Error Message'
		),
		'placeholder' => 'Password'
	)); ?>
	<?php echo \$this->Form->input('password', array(
		'label' => array(
			'text' => 'Hide Error Message'
		),
		'placeholder' => 'Password',
		'errorMessage' => false
	)); ?>
	<?php echo \$this->Form->input('checkbox', array(
		'label' => array('class' => null),
		'afterInput' => '<span class=\"help-block\">Checkbox Bootstrap Style</span>'
	)); ?>
	<?php echo \$this->Form->input('checkbox', array(
		'div' => false,
		'label' => false,
		'before' => '<label class=\"control-label\">Checkbox</label>',
		'wrapInput' => 'controls',
		'afterInput' => '<span class=\"help-block\">Checkbox CakePHP Style</span>'
	)); ?>
	<div class=\"form-actions\">
		<?php echo \$this->Form->submit('Save changes', array(
			'div' => false,
			'class' => 'btn btn-primary'
		)); ?>
		<button type=\"button\" class=\"btn\">Cancel</button>
	</div>
<?php echo \$this->Form->end(); ?>");
			?></pre>
		</section>

		<section id="pagination">
			<div class="page-header">
				<h2>Pagination</h2>
			</div>

			<h3>Standard pagination</h3>
			<p>
				Simple pagination inspired by Rdio, great for apps and search results.
				The large block is hard to miss, easily scalable, and provides large click areas.
			</p>

			<?php
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
			?>
			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'div' => 'pagination'
			)); ?>

			<pre class="prettyprint"><?php
			echo h("<?php echo \$this->Paginator->pagination(array('div' => 'pagination')); ?>");
			?></pre>

			<h3>Sizes</h3>
			<p>
				Fancy larger or smaller pagination? Add .pagination-large,
				<code>.pagination-small</code>, or <code>.pagination-mini</code> for additional sizes.
			</p>

			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'div' => 'pagination pagination-large'
			)); ?>
			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'div' => 'pagination'
			)); ?>
			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'div' => 'pagination pagination-small'
			)); ?>
			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'div' => 'pagination pagination-mini'
			)); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Paginator->pagination(array(
	'div' => 'pagination pagination-large'
)); ?>
<?php echo \$this->Paginator->pagination(array(
	'div' => 'pagination'
)); ?>
<?php echo \$this->Paginator->pagination(array(
	'div' => 'pagination pagination-small'
)); ?>
<?php echo \$this->Paginator->pagination(array(
	'div' => 'pagination pagination-mini'
)); ?>");
			?></pre>

			<h3>Alignment</h3>
			<p>
				Add one of two optional classes to change the alignment of pagination links:
				<code>.pagination-centered</code> and <code>.pagination-right</code>.
			</p>

			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'div' => 'pagination pagination-centered'
			)); ?>
			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'div' => 'pagination pagination-right'
			)); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Paginator->pagination(array(
	'div' => 'pagination pagination-centered'
)); ?>
<?php echo \$this->Paginator->pagination(array(
	'div' => 'pagination pagination-right'
)); ?>");
			?></pre>

			<h3>Pager</h3>
			<p>
				Quick previous and next links for simple pagination implementations with light markup and styles.
				It's great for simple sites like blogs or magazines.
			</p>

			<?php echo $this->Paginator->pager(array(
				'model' => 'Post',
			)); ?>
			<?php echo $this->Paginator->pager(array(
				'model' => 'Post',
				'prev' => '← Older',
				'next' => 'Newer →'
			)); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Paginator->pager(); ?>
<?php echo \$this->Paginator->pager(array(
	'prev' => '← Older',
	'next' => 'Newer →'
)); ?>");
			?></pre>

		</section>

		<section id="alerts">
			<div class="page-header">
				<h2>Alerts</h2>
			</div>

			<?php echo $this->Session->flash('notice'); ?>
			<?php echo $this->Session->flash('success'); ?>
			<?php echo $this->Session->flash('error'); ?>

			<pre class="prettyprint"><?php
echo h("<?php
// View
echo \$this->Session->flash();

// Controller
\$this->Session->setFlash(__('Alert notice message testing...'), 'alert', array(
	'plugin' => 'BoostCake',
));

\$this->Session->setFlash(__('Alert success message testing...'), 'alert', array(
	'plugin' => 'BoostCake',
	'class' => 'alert-success'
));

\$this->Session->setFlash(__('Alert error message testing...'), 'alert', array(
	'plugin' => 'BoostCake',
	'class' => 'alert-error'
));
?>");
			?></pre>
		</section>
	</div>
</div>