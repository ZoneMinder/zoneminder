<?php $this->layout = 'bootstrap3'; ?>
<?php $this->set('title_for_layout', 'Bootstrap3 examples'); ?>

<div class="row">
	<div class="col col-md-3">
		<ul class="nav nav-pills nav-stacked affix">
			<li><a href="#forms"><span class="glyphicon glyphicon-chevron-right pull-right"></span> Forms</a></li>
			<li><a href="#pagination"><span class="glyphicon glyphicon-chevron-right pull-right"></span> Pagination</a></li>
			<li><a href="#alerts"><span class="glyphicon glyphicon-chevron-right pull-right"></span> Alerts</a></li>
		</ul>
	</div>
	<div class="col col-md-9">
		<h1>BoostCake Examples <small>Bootstrap Version 3.0.0</small></h1>

		<section id="forms">
			<div class="page-header">
				<h2>Forms</h2>
			</div>

			<h3>Default styles</h3>
			<p>Individual form controls receive styling, but without any required base class on the <code>&lt;form&gt;</code> or large changes in markup. Results in stacked, left-aligned labels on top of form controls.</p>

			<?php echo $this->Form->create('BoostCake', array(
				'inputDefaults' => array(
					'div' => 'form-group',
					'wrapInput' => false,
					'class' => 'form-control'
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
						'label' => 'Check me out',
						'class' => false
					)); ?>
					<?php echo $this->Form->submit('Submit', array(
						'div' => false,
						'class' => 'btn btn-default'
					)); ?>
				</fieldset>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => 'form-group',
		'wrapInput' => false,
		'class' => 'form-control'
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
			'label' => 'Check me out',
			'class' => false
		)); ?>
		<?php echo \$this->Form->submit('Submit', array(
			'div' => false,
			'class' => 'btn btn-default'
		)); ?>
	</fieldset>
<?php echo \$this->Form->end(); ?>");
			?></pre>
			<hr>

			<h3>Inline form</h3>
			<p>Add <code>.form-inline</code> for left-aligned labels and inline-block controls for a compact layout.</p>

			<?php echo $this->Form->create('BoostCake', array(
				'inputDefaults' => array(
					'div' => false,
					'label' => false,
					'wrapInput' => false,
					'class' => 'form-control'
				),
				'class' => 'well form-inline'
			)); ?>
				<?php echo $this->Form->input('email', array(
					'placeholder' => 'Email',
					'style' => 'width:180px;'
				)); ?>
				<?php echo $this->Form->input('password', array(
					'placeholder' => 'Password',
					'style' => 'width:180px;'
				)); ?>
				<?php echo $this->Form->input('remember', array(
					'label' => 'Remember me',
					'class' => false
				)); ?>
				<?php echo $this->Form->submit('Sign in', array(
					'div' => false,
					'class' => 'btn btn-default'
				)); ?>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => false,
		'label' => false,
		'wrapInput' => false,
		'class' => 'form-control'
	),
	'class' => 'well form-inline'
)); ?>
	<?php echo \$this->Form->input('email', array(
		'placeholder' => 'Email',
		'style' => 'width:180px;'
	)); ?>
	<?php echo \$this->Form->input('password', array(
		'placeholder' => 'Password',
		'style' => 'width:180px;'
	)); ?>
	<?php echo \$this->Form->input('remember', array(
		'label' => 'Remember me',
		'class' => false
	)); ?>
	<?php echo \$this->Form->submit('Sign in', array(
		'div' => false,
		'class' => 'btn btn-default'
	)); ?>
<?php echo \$this->Form->end(); ?>");
			?></pre>
			<hr>

			<h3>Horizontal form</h3>
			<p>
				Use Bootstrap's predefined grid classes to align labels and groups of form controls in a horizontal layout.
			</p>

			<?php echo $this->Form->create('BoostCake', array(
				'inputDefaults' => array(
					'div' => 'form-group',
					'label' => array(
						'class' => 'col col-md-2 control-label'
					),
					'wrapInput' => 'col col-md-10',
					'class' => 'form-control'
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
					'wrapInput' => 'col col-md-10 col-md-offset-2',
					'label' => 'Remember me',
					'class' => false,
					'afterInput' => $this->Form->submit('Sign in', array(
						'class' => 'btn btn-default'
					))
				)); ?>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => 'form-group',
		'label' => array(
			'class' => 'col col-md-2 control-label'
		),
		'wrapInput' => 'col col-md-10',
		'class' => 'form-control'
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
		'wrapInput' => 'col col-md-10 col-md-offset-2',
		'label' => 'Remember me',
		'class' => false,
		'afterInput' => \$this->Form->submit('Sign in', array(
			'class' => 'btn btn-default'
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
					'div' => 'form-group',
					'label' => array(
						'class' => 'col col-md-2 control-label'
					),
					'wrapInput' => 'col col-md-10',
					'class' => 'form-control'
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
					'class' => 'checkbox-inline',
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
					'before' => '<label class="col col-md-2 control-label">Radio</label>',
					'legend' => false,
					'class' => false,
					'options' => array(
						1 => 'Option one is this and that—be sure to include why it\'s great',
						2 => 'Option two can be something else and selecting it will deselect option one'
					)
				)); ?>
				<?php echo $this->Form->input('username', array(
					'placeholder' => 'Username',
					'label' => array(
						'text' => 'Prepend',
					),
					'between' => '<div class="col col-md-10">',
					'wrapInput' => 'input-group',
					'beforeInput' => '<span class="input-group-addon">@</span>',
					'after' => '</div>'
				)); ?>
				<?php echo $this->Form->input('price', array(
					'label' => array(
						'text' => 'Append',
					),
					'between' => '<div class="col col-md-10">',
					'wrapInput' => 'input-group',
					'afterInput' => '<span class="input-group-addon">.00</span>',
					'after' => '</div>'
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
					'wrapInput' => 'col col-md-10 col-md-offset-2',
					'label' => array('class' => null),
					'class' => false,
					'afterInput' => '<span class="help-block">Checkbox Bootstrap Style</span>'
				)); ?>
				<?php echo $this->Form->input('checkbox', array(
					'before' => '<label class="col col-md-2 control-label">Checkbox</label>',
					'label' => false,
					'class' => false,
					'wrapInput' => 'col col-md-10',
					'afterInput' => '<span class="help-block">Checkbox CakePHP Style</span>'
				)); ?>
				<div class="row">
					<div class="col col-md-10 col-md-offset-2">
						<?php echo $this->Form->submit('Save changes', array(
							'div' => false,
							'class' => 'btn btn-primary'
						)); ?>
						<button type="button" class="btn btn-default">Cancel</button>
					</div>
				</div>
			<?php echo $this->Form->end(); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Form->create('BoostCake', array(
	'inputDefaults' => array(
		'div' => 'form-group',
		'label' => array(
			'class' => 'col col-md-2 control-label'
		),
		'wrapInput' => 'col col-md-10',
		'class' => 'form-control'
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
		'class' => 'checkbox-inline',
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
		'before' => '<label class=\"col col-md-2 control-label\">Radio</label>',
		'legend' => false,
		'class' => false,
		'options' => array(
			1 => 'Option one is this and that—be sure to include why it\'s great',
			2 => 'Option two can be something else and selecting it will deselect option one'
		)
	)); ?>
	<?php echo \$this->Form->input('username', array(
		'placeholder' => 'Username',
		'label' => array(
			'text' => 'Prepend',
		),
		'between' => '<div class=\"col col-md-10\">',
		'wrapInput' => 'input-group',
		'beforeInput' => '<span class=\"input-group-addon\">@</span>',
		'after' => '</div>'
	)); ?>
	<?php echo \$this->Form->input('price', array(
		'label' => array(
			'text' => 'Append',
		),
		'between' => '<div class=\"col col-md-10\">',
		'wrapInput' => 'input-group',
		'afterInput' => '<span class=\"input-group-addon\">.00</span>',
		'after' => '</div>'
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
		'wrapInput' => 'col col-md-10 col-md-offset-2',
		'label' => array('class' => null),
		'class' => false,
		'afterInput' => '<span class=\"help-block\">Checkbox Bootstrap Style</span>'
	)); ?>
	<?php echo \$this->Form->input('checkbox', array(
		'before' => '<label class=\"col col-md-2 control-label\">Checkbox</label>',
		'label' => false,
		'class' => false,
		'wrapInput' => 'col col-md-10',
		'afterInput' => '<span class=\"help-block\">Checkbox CakePHP Style</span>'
	)); ?>
	<div class=\"row\">
		<div class=\"col col-md-10 col-md-offset-2\">
			<?php echo \$this->Form->submit('Save changes', array(
				'div' => false,
				'class' => 'btn btn-primary'
			)); ?>
			<button type=\"button\" class=\"btn btn-default\">Cancel</button>
		</div>
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
				'ul' => 'pagination'
			)); ?>

			<pre class="prettyprint"><?php
			echo h("<?php echo \$this->Paginator->pagination(array('ul' => 'pagination')); ?>");
			?></pre>

			<h3>Sizes</h3>
			<p>
				Fancy larger or smaller pagination? Add .pagination-lg,
				<code>.pagination-sm</code>, or <code>.pagination-mini</code> for additional sizes.
			</p>

			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'ul' => 'pagination pagination-lg'
			)); ?>
			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'ul' => 'pagination'
			)); ?>
			<?php echo $this->Paginator->pagination(array(
				'model' => 'Post',
				'ul' => 'pagination pagination-sm'
			)); ?>

			<pre class="prettyprint"><?php
echo h("<?php echo \$this->Paginator->pagination(array(
	'ul' => 'pagination pagination-lg'
)); ?>
<?php echo \$this->Paginator->pagination(array(
	'ul' => 'pagination'
)); ?>
<?php echo \$this->Paginator->pagination(array(
	'ul' => 'pagination pagination-sm'
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

			<?php echo $this->Session->flash('success'); ?>
			<?php echo $this->Session->flash('info'); ?>
			<?php echo $this->Session->flash('warning'); ?>
			<?php echo $this->Session->flash('danger'); ?>

			<pre class="prettyprint"><?php
echo h("<?php
// View
echo \$this->Session->flash();

// Controller
\$this->Session->setFlash(__('Alert success message testing...'), 'alert', array(
	'plugin' => 'BoostCake',
	'class' => 'alert-success'
));

\$this->Session->setFlash(__('Alert info message testing...'), 'alert', array(
	'plugin' => 'BoostCake',
	'class' => 'alert-info'
));

\$this->Session->setFlash(__('Alert warning message testing...'), 'alert', array(
	'plugin' => 'BoostCake',
	'class' => 'alert-warning'
));

\$this->Session->setFlash(__('Alert danger message testing...'), 'alert', array(
	'plugin' => 'BoostCake',
	'class' => 'alert-danger'
));
?>");
			?></pre>
		</section>
	</div>
</div>
