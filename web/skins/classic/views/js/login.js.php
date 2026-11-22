var failed = <?php echo (isset($_SESSION['loginFailed']) and (($_POST['action'] ?? '') === 'login')) ? 'true' : 'false' ?>;
