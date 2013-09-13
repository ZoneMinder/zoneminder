<?php $this->layout = 'bootstrap3'; ?>
<?php $this->set('title_for_layout', 'Bootstrap Plugin for CakePHP'); ?>

<div class="jumbotron">
	<h1>
		BoostCake
		<iframe src="http://ghbtns.com/github-btn.html?user=slywalker&repo=cakephp-plugin-boost_cake&type=watch&count=true&size=large" allowtransparency="true" frameborder="0" scrolling="0" width="170" height="30"></iframe>
	</h1>
	<p>
		This is a plugin for CakePHP using Bootstrap
	</p>
	<p>
		<a href="https://travis-ci.org/slywalker/cakephp-plugin-boost_cake">
			<img src="https://travis-ci.org/slywalker/cakephp-plugin-boost_cake.png" alt="Build Status">
		</a>
		<a href="https://packagist.org/packages/slywalker/boost_cake">
			<img src="https://poser.pugx.org/slywalker/boost_cake/d/total.png" alt="Total Downloads">
		</a>
		<a href="https://packagist.org/packages/slywalker/boost_cake">
			<img src="https://poser.pugx.org/slywalker/boost_cake/v/stable.png" alt="Latest Stable Version">
		</a>
	</p>
	<p>
		<a href="https://github.com/slywalker/cakephp-plugin-boost_cake" class="btn btn-primary btn-large">
			Github Project <i class="icon-chevron-right icon-white"></i>
		</a>
		<a href="https://packagist.org/packages/slywalker/boost_cake" class="btn btn-primary btn-large">
			Packagist <i class="icon-chevron-right icon-white"></i>
		</a>
	</p>
</div>

<div class="page-header">
	<h2>Installation</h2>
</div>

<h3>Composer</h3>
<p>
	Ensure require is present in <code>composer.json</code>.
	This will install the plugin into <code>Plugin/BoostCake</code>:
</p>

<pre class="prettyprint">{
	"require": {
		"slywalker/boost_cake": "*"
	}
}</pre>

<h3>Enable plugin</h3>
<p>You need to enable the plugin in your app/Config/bootstrap.php file:</p>
<pre class="prettyprint">CakePlugin::load('BoostCake');</pre>
<p>If you are already using <code>CakePlugin::loadAll();</code>, then this is not necessary.</p>

<h3>Add helpers</h3>
<p>You need to add helpers at controller.</p>
<pre class="prettyprint"><?php echo h("<?php
class AppController extends Controller {

	public \$helpers = array(
		'Session',
		'Html' => array('className' => 'BoostCake.BoostCakeHtml'),
		'Form' => array('className' => 'BoostCake.BoostCakeForm'),
		'Paginator' => array('className' => 'BoostCake.BoostCakePaginator'),
	);

}"); ?></pre>

<h3>AuthComponent setting</h3>
<p>Substitute alert-error with alert-danger if Bootstrap 3.</p>
<pre class="prettyprint"><?php echo h("<?php
class AppController extends Controller {

	public \$components = array(
		'Auth' => array(
			'flash' => array(
				'element' => 'alert',
				'key' => 'auth',
				'params' => array(
					'plugin' => 'BoostCake',
					'class' => 'alert-error'
				)
			)
		)
	);

}"); ?></pre>
