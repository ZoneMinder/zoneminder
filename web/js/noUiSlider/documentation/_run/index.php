<!DOCTYPE html>

<html lang="en-US">
<head>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $description; ?>">

    <title><?php echo $title; ?> | Refreshless.com</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">

    <link href="/nouislider/documentation/assets/base.css?v=4" rel="stylesheet">
    <link href="/nouislider/documentation/assets/prism.css" rel="stylesheet">
    <script src="/nouislider/documentation/assets/wNumb.js"></script>

    <link href="<?php echo $distribute; ?>/nouislider.css?v=<?php echo $plain_version; ?>" rel="stylesheet">
    <script src="<?php echo $distribute; ?>/nouislider.js?v=<?php echo $plain_version; ?>"></script>

    <link rel="canonical" href="<?php echo $canonical; ?>">

</head>

<body class="language-javascript">

<?php if ($page == 'index'): ?>

    <div class="index-demo">

        <h1>noUiSlider</h1>
        <h2>JavaScript Range Slider</h2>

        <div class="quick">

            <div id="slider"></div>
            <pre><code>&lt;div id=&quot;slider&quot;&gt;&lt;/div&gt;</code></pre>
            <?php run('minimal'); ?>
            <?php code('minimal'); ?>

            <div class="viewer-header">Installation</div>

            <div class="viewer-content">

                <pre><code class="language-console">npm <span class="token function">install</span> <span
                            class="token keyword">nouislider</span></code></pre>
                <pre><code class="language-console">yarn <span class="token function">add</span> <span
                                class="token keyword">nouislider</span></code></pre>

                <?php code('install'); ?>
            </div>

            <iframe style="margin: 30px auto 0; display: block;"
                    src="https://ghbtns.com/github-btn.html?user=leongersen&repo=noUiSlider&type=star&count=true&size=large"
                    frameborder="0" scrolling="0" width="160px" height="30px"></iframe>
        </div>
    </div>

<?php endif; ?>

<?php include $file_menu; ?>

<div class="content">
    <?php echo $content; ?>
</div>

<script>

    var headers = document.getElementsByClassName('viewer-header');

    for (var i = 0; i < headers.length; i++) {
        headers[i].addEventListener('click', function () {
            this.classList.toggle('open');
        });
    }

</script>

<script src="/nouislider/documentation/assets/prism.js"></script>

</body>
</html>
